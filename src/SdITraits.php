<?php

namespace FatturaElettronica;

use App\Http\Controllers\Admin\SdIController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use FatturaElettronicaPhp\FatturaElettronica\DigitalDocument;
use FatturaElettronica\Client;
use FatturaElettronica\Utility;
use FatturaElettronica\FatturaElettronica;
use FatturaElettronica\EsitoCommittente;
use FatturaElettronica\SdIClient\RispostaSdIRiceviFile;
use FatturaElettronica\SdIClient\FileSdIBase;

use App\Models\eFatturaAnagrafica;
use App\Models\eFattura;
use App\Models\eFatturaGen;
use App\Models\eFatturaIVA;
use App\Models\eFatturaDet;
use App\Models\Anagrafica;
use App\Models\eFatturaNotifiche;


/**
 * Traits per il Sistema di Interscambio
 */

trait SdITraits
{

    protected $uty;
    protected $Invoice;

    /**
     * Ricezione Notifiche
     *
     * @param object $request
     * @param type $type
     * @param type $status
     * Process request
     * ----------------------------------------
     * $id                 = $request->IdentificativoSdI;
     * $filename           = $request->NomeFile;
     * $file               = $request->File;
     * $DataOraRicezione   = $request->DataOraRicezione;
     * $Errore             = $request->Errore;
     * $CodiceDestinatario = $request->CodiceDestinatario;
     * $Formato            = $request->Formato;
     * $TentativiInvio     = $request->TentativiInvio;
     * $MessageId          = $request->MessageId;
     * ----------------------------------------
     * For example, to save file:
     * file_put_contents("/some/path/{$request->NomeFile}", $request->File);
     */
    protected static function _Receive($request, $type = null, $status = null)
    {
        // verifico se $request->File è base64 lo decifro
        if (isset($request->File) && preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $request->File)) {
            $request->File = base64_decode($request->File);
        }
        // verifico se $request->File è p7m estraggo solo xml
        if (preg_match('/<\/.+?>/', $request->File)) {
            // skip everything before the XML content
            $string = substr($request->File, strpos($request->File, '<?xml '));

            // skip everything after the XML content
            preg_match_all('/<\/.+?>/', $string, $matches, PREG_OFFSET_CAPTURE);
            $lastMatch = end($matches[0]);

            $request->File = substr($string, 0, $lastMatch[1] + strlen($lastMatch[0]));
        }
        $xml = simplexml_load_string($request->File, 'SimpleXMLElement', LIBXML_NOWARNING);
        $invoice_id = $xml->IdentificativoSdI;
        if ($type === 'NotificaEsito' && null === $status) {
            $esito = $xml->Esito;
            if ($esito == 'EC01') {
                $status = 'I_ACCEPTED';
            } elseif ($esito == 'EC02') {
                $status = 'I_REFUSED';
            }
        }
        $invoice = eFattura::where('remote_id', $invoice_id)->first();
        if (isset($invoice) && !is_null($invoice->id)) {
            self::Notification($request, $type, $status, $invoice);
            $invoice->update(['status' => $status]);
        } else {
            self::Notification($request, $type, $status);
        }
        return;
    }

    /**
     * Aggiunge una notifica in tabella notification
     *
     * @param object $request
     * @param string $type
     * @param string $status
     * @param object $invoice
     *
     * @return array $Notification
     */
    protected static function _Notification($request, $type = null, $status = null, $invoice = null)
    {
        $Notification = eFatturaNotifiche::create(
            [
                'invoice_id' => $invoice->id,
                'remote_id' => $invoice->remote_id,
                'type' => $type,
                'status' => $status,
                'nomefile' => $request->NomeFile,
                'blob' => $request->File,
                'actor' => $invoice->actor,
                //'ctime' => $request->DataOraRicezione
            ]
        );
        return $Notification;
    }

    /**
     * Method transmit
     * send to SDI XML file invoices.
     *
     * @param Request $request
     *
     * @return bool
     */
    protected static function _sendInvoice(Request $request)
    {
        $return = null;
        // verifico se è passato id fattura singola trasmissione
        if (isset($request->id_invoice)) {
            $Invoice = eFattura::find($request->id_invoice);
        }
        // se non c'è una id fattura allora deve sserci il codice prestatore
        else if (isset($request->prestatore)) {
            $Invoices = eFattura::where('status', 'I_UPLOADED')->where('actor', $request->prestatore)->get();
        }
        if ($request->servizio === 'WEBSERVICE') {
            // Set certs and key
            Client::setPrivateKey(config('fatturaelettronica.KEYFILE'));
            Client::setClientCert(config('fatturaelettronica.CLIENT'));
            Client::setCaCert(config('fatturaelettronica.CA_AdE'));
            $client = new Client([
                'endpoint' => config('fatturaelettronica.EndPointRicevi'),
                'wsdl' => config('fatturaelettronica.SdIRiceviFile'),
            ]);
            $fileSdI = new FileSdIBase();
        }
        if ($request->servizio === 'FTP') {
            self::viaFTP($Invoice);
        }
        if (isset($Invoice)) {
            $pp = (is_null($Invoice->blobcode)) ? base64_encode($Invoice->blob) : $Invoice->blobcode;
            try {
                //$fileSdI->load($Invoice->nomefile, $Invoice->blob);
                $fileSdI->load($Invoice->nomefile, $pp);
                $response = new RispostaSdIRiceviFile($client->RiceviFile($fileSdI));
                //$response = new RispostaSdIRiceviFile($client->__soapCall('RiceviFile', [$fileSdI->NomeFile, $fileSdI->File]));
                if ($response->Errore) {
                    eFattura::find($Invoice->id)->update(['status' => 'I_INVALID']);
                    eFatturaNotifiche::create([
                        'invoice_id' => $Invoice->id,
                        'remote_id' => (int) $response->IdentificativoSdI,
                        'type' => 'Notifica Consegna',
                        'status' => 'I_INVALID',
                        'nomefile' => $response->NomeFile,
                        'blob' => $response->File,
                        'actor' => $Invoice->actor,
                        //'ctime' => $request->DataOraRicezione
                    ]);
                } else {
                    list($data, $s) = explode('+', str_replace('T', ' ', $response->DataOraRicezione));
                    eFattura::find($Invoice->id)->update([
                        'status' => 'I_TRANSMITTED',
                        'remote_id' => (int) $response->IdentificativoSdI,
                        'ctime' => $data,
                    ]);
                    eFatturaNotifiche::create([
                        'invoice_id' => $Invoice->id,
                        'remote_id' => (int) $response->IdentificativoSdI,
                        'type' => 'Notifica Consegna',
                        'status' => 'I_TRANSMITTED',
                        'nomefile' => $response->NoneFile,
                        'blob' => $response->File,
                        'actor' => $Invoice->actor,
                        //'ctime' => $request->DataOraRicezione
                    ]);
                }
                $return = json_encode($response);
            } catch (\Exception $e) {
                //eFattura::find($Invoice['id'])->update(['status' => 'I_INVALID']);
                Client::log($e->getMessage(), LOG_ERR);
                $return = json_encode($e);
            }
        } elseif (isset($Invoices)) {
            foreach ($Invoices as $Invoice) {
                $pp = (is_null($Invoice->blobcode)) ? base64_encode($Invoice->blob) : $Invoice->blobcode;
                try {
                    //$fileSdI->load($Invoice->nomefile, $Invoice->blob);
                    $fileSdI->load($Invoice->nomefile, $pp);
                    $response = new RispostaSdIRiceviFile($client->RiceviFile($fileSdI));
                    if ($response->Errore) {
                        eFattura::find($Invoice->id)->update(['status' => 'I_INVALID']);
                        eFatturaNotifiche::create([
                            'invoice_id' => $Invoice->id,
                            'remote_id' => (int) $response->IdentificativoSdI,
                            'type' => 'Notifica Consegna',
                            'status' => 'I_INVALID',
                            'nomefile' => $response->NoneFile,
                            'blob' => $response->File,
                            'actor' => $Invoice->actor,
                            //'ctime' => $request->DataOraRicezione
                        ]);
                    } else {
                        eFattura::find($Invoice->id)->update([
                            'status' => 'I_TRANSMITTED',
                            'remote_id' => (int) $response->IdentificativoSdI,
                            'ctime' => $response->DataOraRicezione,
                        ]);
                        eFatturaNotifiche::create([
                            'invoice_id' => $Invoice->id,
                            'remote_id' => (int) $response->IdentificativoSdI,
                            'type' => 'Notifica Consegna',
                            'status' => 'I_TRANSMITTED',
                            'nomefile' => $response->NoneFile,
                            'blob' => $response->File,
                            'actor' => $Invoice->actor,
                            //'ctime' => $request->DataOraRicezione
                        ]);
                    }
                    $return = json_encode($response);
                } catch (\Exception $e) {
                    //eFattura::find($Invoice['id'])->update(['status' => 'I_INVALID']);
                    Client::log($e->getMessage(), LOG_ERR);
                    $return = json_encode($e);
                }
            }
        } else {
            $return = "NESSUN ID FATTURA O PRESTATORE SELEZIONATO";
        }
        return $return;
    }

    /**
     * Ricevi fatture da SDI
     *  Process request
     *  ------------------------------------------------
     *  $id               = $request->IdentificativoSdI;
     *  $filename         = $request->NomeFile;
     *  $file             = $request->File;
     *  $metadataFilename = $request->NomeFileMetadati;
     *  $metadata         = $request->Metadati;
     *  ------------------------------------------------
     * @param type $request
     * @param type $status
     * @return boolean
     */
    protected static function _receiveInvoice($request, $status = null)
    {
        // verifico se $request->File è base64 lo decifro
        if (isset($request->File) && preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $request->File)) {
            $request->File = base64_decode($request->File);
        }
        // verifico se $request->File è p7m estraggo solo xml
        if (preg_match('/<\/.+?>/', $request->File)) {
            // skip everything before the XML content
            $string = substr($request->File, strpos($request->File, '<?xml '));

            // skip everything after the XML content
            preg_match_all('/<\/.+?>/', $string, $matches, PREG_OFFSET_CAPTURE);
            $lastMatch = end($matches[0]);

            $request->File = substr($string, 0, $lastMatch[1] + strlen($lastMatch[0]));
        }
        // verifico se $request->Metadati è base64 lo decifro
        if (isset($request->Metadati) && preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $request->Metadati)) {
            $request->Metadati = base64_decode($request->Metadati);
        }
        // verifico se $request->File è p7m estraggo solo xml
        if (preg_match('/<\/.+?>/', $request->Metadati)) {
            // skip everything before the XML content
            $string = substr($request->Metadati, strpos($request->Metadati, '<?xml '));

            // skip everything after the XML content
            preg_match_all('/<\/.+?>/', $string, $matches, PREG_OFFSET_CAPTURE);
            $lastMatch = end($matches[0]);

            $request->Metadati = substr($string, 0, $lastMatch[1] + strlen($lastMatch[0]));
        }
        $xml = simplexml_load_string($request->File, 'SimpleXMLElement', LIBXML_NOWARNING);
        $invoice_id = $xml->IdentificativoSdI;
        $metadata = simplexml_load_string($request->Metadati, 'SimpleXMLElement', LIBXML_NOWARNING);;
        $cedente = $xml->FatturaElettronicaHeader->CedentePrestatore->DatiAnagrafici->IdFiscaleIVA->IdCodice;
        $denominazione = $xml->FatturaElettronicaHeader->CedentePrestatore->DatiAnagrafici->Anagrafica->Denominazione;
        $cf = $xml->FatturaElettronicaHeader->CessionarioCommittente->DatiAnagrafici->CodiceFiscale;
        $piva = $xml->FatturaElettronicaHeader->CessionarioCommittente->DatiAnagrafici->IdFiscaleIVA->IdCodice;
        $identificativosdi = $metadata->IdentificativoSdI;
        $anagrafica = \App\Models\Anagrafica::where('piva', '=', $piva)->orwhere('codicefiscale', '=', $cf)->first();
        if (isset($anagrafica) && !is_null($anagrafica->id)) {
            $Invoice = eFattura::create(
                [
                    'remote_id' => $identificativosdi,
                    'cedente' => $cedente . '-' . $denominazione,
                    'nomefile' => $request->NomeFile,
                    'status' => $status,
                    'blob' => $request->File,
                    'actor' => $anagrafica->id,
                ]
            );
            eFatturaNotifiche::create(
                [
                    'invoice_id' => $Invoice->id,
                    'remote_id' => $identificativosdi,
                    'type' => 'Ricevi Fattura',
                    'status' => $status,
                    'nomefile' => $request->NomeFile,
                    'blob' => $request->File,
                    'actor' => $anagrafica->id,
                    //'ctime' => $request->DataOraRicezione
                ]
            );
            return true;
        } else {
            return false;
        }
    }
    /**
     * Send Invoice/s to SdI via Web Service
     *
     * @param object $Invoice
     * @return mixed
     */
    protected static function _viaWebService($Invoice)
    {
        //
    }

    /**
     * Send Invice/s to SdI via FTP
     *
     * @param string $piva
     * @param object $Invoice
     * @return mixed
     */
    protected static function _viaFTP($Invoices, $piva = NULL)
    {
        $user = Auth()->user();
        $params = (object) [
            'SDIFTP'   => '/home/sdiftp',                          // home del sistema d'interscambio
            'BACKUP'   => '/home/sdiftp/backup',                   // cartella di conservazione dei files FI
            'LOG'      => '/var/log/sdiftp',                       // cartella file di log
            'LOGFILE'  => 'trasmissione.log',                      // file di log di esecuzione dello script in presenza di fatture
            'NOLOG'    => 'notrasmissione.log',                    // file di log dello script senza fatture
            'BACKUP'   => '/backup/SDIFTP',                        // cartella backup
            'NODO'     => 'FI.01174380053',                        // idetificativo nodo file zip
            'DATA'     => date('Yz', time()),                       // data corente in formato anno giorno dell'anno
            'SDI'      => '/var/www/clients/client1/web1/private', // path home dei clienti
            'PTEST'    => 901,                                     // Progressivo numerazione di test
            'PPROD'    => 001,                                     //  Progressivo numerazione di produzoione
            'FIRMA'    => 'CERTS/FIRMA.pem',                       // certificato firma
            'CIFRA'    => 'CERTS/sogeiunicocifra.pem',             // certificato sogei
            'DECIFRA'  => 'CERTS/CIFRA.pem',                       // certificato pre cifrare i documenti
            'CA'       => 'CERTS/CAEntrate.pem',                   // CA delle agenzia entrate
            'PASSWORD' => 'S18ef112',                              // password con cui sono stati generati i certificati
            'DatiVersoSdITest' => 'DATA/DatiVersoSdITest',         // path di test verso SdI
            'DatiVersoSdI' => 'DATA/DatiVersoSdI',                 // path di produzione SdI
            'DatiDaSdITest' => 'DATA/DatiDaSdITest',               // path di test da SdI
            'DatiDaSdI' => 'DATA/DatiDaSdI',                       // path di produzione da SdI
        ];
        if (!is_dir($params->BACKUP . '/' . $piva)) {
            exec("mkdir $params->BACKUP/$piva");
        }

        # PATH USATO PER LO SCRIPT
        $ToSdI = $params->DatiVersoSdI;                               # variabile ustata per lo script
        $DaSdI = $params->DatiDaSdI;                                  # variabile usata per lo script
        $PUSO = $params->PPROD;                                       # progressivo in uso dallo script

        $ToSdI = $params->SDIFTP . '/' . $params->DatiVersoSdI;                             # variabile ustata per lo script
        $DaSdI = $params->SDIFTP . '/' . $params->DatiDaSdI;                                # variabile usata per lo script
        $PUSO = $params->PPROD;                                     # progressivo in uso dallo script
        $FLIST = NULL;
        $NUMFAT = count($Invoices);
        $TMP = public_path() . '/tmp/' . $piva;
        $STORAGE = 'public/tmp/' . $piva;

        // se ci sono più fatture
        if ($NUMFAT > 1) {
            foreach ($Invoices as $Invoice) {
                // creao il file della fattura
                Storage::put($STORAGE . '/' . $Invoice->cedente . '/' . $Invoice->nomefile, $Invoice->blob);
                // memorizzo il nome file per la quadratura
                $FLIST[] = $Invoice->nomfile;
            }
        }
        // se ce una sola fattura
        if ($NUMFAT === 1) {
            // creao il file della fattura
            Storage::put($STORAGE . '/' . $Invoice->cedente . '/' . $Invoice->nomefile, $Invoice->blob);
            // memorizzo il nome file per la quadratura
            $FLIST[] = $Invoice->nomfile;
        }

        // costruiamo il nodo fattura
        $ora = date('Hi', time());
        if (Storage::exist('public/ora.txt')) {
            $orat = Storage::get('public/ora.txt');
        } else {
            $orat = $ora;
            Storage::put('public/ora.txt', $ora);
        }
        if (Storage::exist('public/progressivo.txt')) {
            $progressivo = Storage::get('public/progressivo.txt');
        } else {
            $progressivo = $params->PPROD;
            Storage::put('public/progressivo.txt', $params->PPROD);
        }
        if ($ora !== $orat) {
            $progressivo += 1;
            Storage::put('public/progressivo.txt', $params->PPROD);
        }
        $FNODO = $params->FNODO . '.' . $params->DATA . '.' . $ora . '.' . $progressivo;
        $DATAORA = exec('echo $(date +%FT%T.%3N%:z)');
        # creo il file di quadratura
        $XML = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
      <ns2:FileQuadraturaFTP xmlns:ns2="http://www.fatturapa.it/sdi/ftp/v2.0" versione="2.0">
      <IdentificativoNodo>01174380053</IdentificativoNodo>
      <DataOraCreazione>' . $DATAORA . '</DataOraCreazione>
      <NomeSupporto>' . $FNODO . '.zip</NomeSupporto>
      <NumeroFile>
      <File>
      <Tipo>FA</Tipo>
      <Numero>' . $NUMFAT . '</Numero>
      </File>
      </NumeroFile>
      </ns2:FileQuadraturaFTP>
      ';
        Storage::put($STORAGE . '/' . $FNODO . '.xml', $XML);
        // Sposto i files xml e p7m in un file zip
        exec("zip -m $TMP/$FNODO.zip *.xml *.p7m");
        // procediamo alla firma e cifratura del file nodo
        if (Storage::exist("$STORAGE/$FNODO.zip")) {
            $TOIN = $FNODO . '.zip';
            $TOFIRMA = $TOIN . '.p7m';
            $TOCIFRA = $TOFIRMA . 'enc';
            // firmo il file
            exec("openssl smime -sign -in $TOIN -outform der -binary -nodetach -out $TOFIRMA -signer $params->SDIFTP/$params->FIRMA -passin pass:$params->PASSWORD");
            // cirfo il file
            exec("openssl smime -encrypt -in $TOFIRMA -outform der -binary -des3 -out $TOCIFRA $params->SDIFTP/$params->CIFRA");

            if (Storage::exist($STORAGE . '/' . $TOCIFRA)) {
                // sposto il file in cartella ToSdi per il prelievo dello SdI
                exec("mv $TMP/$TOCIFRA $ToSdI/$TOIN");
                exec("chown sdiftp:sdiftp $ToSdI/$TOIN");
                exec("chmod 755 $ToSdI/$TOIN");
                // eseguo il packup del flusso di invio
                foreach ($user->Anagrafica->Fiscale as $fiscale) {
                    if ($fiscale->tipo === 'PIVA') {
                        $piva = $fiscale->valore;
                    }
                }
                exec("cp $params->SDI/$piva/$FNODO.zip $params->BACKUP/$piva/$FNODO.zip");
                exec("chown -R root:root $params->BACKUP");
            }
        }
    }


    /**
     * Invia Accettazione o Rifiuto di una fattura ricevuta
     */
    protected static function _esitoCommittente(Request $request)
    {
        if ($request->id_invoice) {
            $esito = ($request->esito === 'CONFERMA') ? 'EC01' : 'EC02';
            $Invoice = eFattura::find($request->id_invoice);
            $invoice = new FatturaElettronica($Invoice->blob);
            $notice = new EsitoCommittente();
            $notice->setFilenameFromInvoice($invoice, '_EC_001');
            $filename = $notice->getFilename();
            $notice->setValuesFromInvoice($invoice);
            $notice->setValue('IdentificativoSdI', $Invoice->remote_id);
            $notice->setValue('Esito', $esito);
            $notice->setValue('PosizioneFattura', 1);
            $notice->setValue('Descrizione', '');
            $notice->setValue('MessaggioIdCommittente', '');
            $xml = $notice->asXML();
            $xml64 = base64_encode($xml);
            $Notification = eFatturaNotifiche::create(
                [
                    'invoice_id' => $Invoice->id,
                    'remote_id' => $Invoice->remote_id,
                    'type' => 'Esito Committente',
                    'status' => $Invoice->status,
                    'blob' => $xml,
                    'nomefile' => $filename,
                    'actor' => $Invoice->actor,
                    //'ctime' => $request->DataOraRicezione
                ]
            );
            // Set certs and key
            Client::setPrivateKey(config('fatturaelettronica.KEYFILE'));
            Client::setClientCert(config('fatturaelettronica.CLIENT'));
            Client::setCaCert(config('fatturaelettronica.CA_AdE'));

            $client = new Client(array(
                'endpoint' => config('fatturaelettronica.EndPointRiceviNotifica'),
                'wsdl' => config('fatturaelettronica.SdIRiceviNotifica'),
            ));
            $fileSdI = new \FatturaElettronica\SdIClient\FileSdI();
            try {
                $fileSdI->load($filename, $xml);
                $response = new \FatturaElettronica\SdIClient\RispostaSdINotificaEsito($client->NotificaEsito($fileSdI));
                if ($response) {
                    if ($request->esito === 'RIFIUTA') {
                        $Invoice->update([
                            'status' => 'R_REFUSED',
                        ]);
                    }
                    if ($request->esito === 'CONFERMA') {
                        $Invoice->update([
                            'status' => 'R_ACCEPTED',
                        ]);
                    }
                    // verifico se $request->File è base64 lo decifro
                    if (isset($response->ScartoEsito->File) && preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $response->ScartoEsito->File)) {
                        $request->File = base64_decode($response->ScartoEsito->File);
                    }
                    $xml = simplexml_load_string($response->ScartoEsito->File, 'SimpleXMLElement', LIBXML_NOWARNING);
                    $filename = $response->ScartoEsito->NomeFile;
                    $Notification = eFatturaNotifiche::create(
                        [
                            'invoice_id' => $Invoice->id,
                            'remote_id' => $Invoice->remote_id,
                            'type' => 'Scarto Esito',
                            'status' => $response->Esito,
                            'nomefile' => $response->ScartoEsito->NomeFile,
                            'blob' => $xml,
                            'actor' => $Invoice->actor,
                            //'ctime' => $request->DataOraRicezione
                        ]
                    );
                }
                $return = json_encode($response);
            } catch (\Exception $e) {
                Client::log($e->getMessage(), LOG_ERR);
                $return = json_encode($e);
            }
        }
    }

    /**
     * Display a listing of the resource.
     * Visualizza le Fatture di f_gen
     *
     * @return \Illuminate\Http\Response
     */
    protected static function _getFatture(Request $request)
    {
        $fields = ['id', 'tipo', 'causale', 'numero', 'anno', 'data'];
        $fatture = eFatturaGen::select($fields)->where('prestatore_id', '=', $request->prestatore)->get();
        $ret = [];
        foreach ($fatture as $fattura) {
            // controllo se è stata generato il file xml da inviare a SDI
            $invoice = eFattura::where('f_gen_id', $fattura->id)->first();
            // se il file non è stato generato
            if (is_null($invoice)) {
                $ret[] = [
                    'id' => $fattura->id,
                    'tipo' => $fattura->tipo,
                    'causale' => $fattura->causale,
                    'numero' => $fattura->numero,
                    'anno' => $fattura->anno,
                    'data' => $fattura->data,
                    'action' => 'GEN XML',
                ];
            } else {
                $ret[] = [
                    'id' => $fattura->id,
                    'tipo' => $fattura->tipo,
                    'causale' => $fattura->causale,
                    'numero' => $fattura->numero,
                    'anno' => $fattura->anno,
                    'data' => $fattura->data,
                    'action' => '',
                ];
            }
        }
        return $ret;
    }

    /**
     * Carica le fatture da tabella Invoices.
     * Dettinata VUE getFatture
     *
     * @param $request
     * $request->status
     * $request->prestatore
     *
     * @return \Illuminate\Http\Response
     */
    protected static function _getInvoices(Request $request)
    {
        $fields = ['id', 'posizione', 'cedente', 'anno', 'status', 'actor', 'nomefile', 'ctime', 'issuer'];
        if ($request->status) {
            $invoices = eFattura::select($fields)
                ->where('status', $request->status)
                ->where('actor', $request->prestatore)->get();
        } else {
            $invoices = \App\Models\eFattura::select($fields)
                ->where('actor', $request->prestatore)->get();
        }
        $ret = [];
        foreach ($invoices as $invoice) {
            if ($invoice->status === 'R_RECEIVED') {
                $ret[] = [
                    'id' => $invoice->id,
                    'posizione' => $invoice->posizione,
                    'cedente' => $invoice->cedente,
                    'anno' => $invoice->anno,
                    'status' => $invoice->status,
                    'actor' => $invoice->actor,
                    'nomefile' => $invoice->nomefile,
                    'ctime' => $invoice->ctime,
                    'issuer' => $invoice->issuer,
                    'action' => 'ACTION',
                ];
            } else {
                $ret[] = [
                    'id' => $invoice->id,
                    'posizione' => $invoice->posizione,
                    'cedente' => $invoice->cedente,
                    'anno' => $invoice->anno,
                    'status' => $invoice->status,
                    'actor' => $invoice->actor,
                    'nomefile' => $invoice->nomefile,
                    'ctime' => $invoice->ctime,
                    'issuer' => $invoice->issuer,
                    //'conferma' => '',
                    //'rifiuta' => ''
                ];
            }
        }
        return $ret;
    }

    /**
     * Display a listing of the resource.
     * Visualizza le notifiche
     *
     * @return \Illuminate\Http\Response
     */
    protected static function _getNotifiche(Request $request)
    {
        $fields = [
            'id',
            'invoice_id',
            'remote_id',
            'type',
            'status',
            'blob',
            'actor',
            'nomefile',
            'ctime'
        ];
        $Notifications = eFatturaNotifiche::select($fields)->where('invoice_id', '=', $request->id)->get();
        if (isset($Notifications)) {
            $ret = [];
            foreach ($Notifications as $Notice) {
                // Load XML
                $xml = new \DOMDocument();
                $xml->loadXML($Notice->blob);

                // Load XSLT stylesheet
                $xsl = new \DOMDocument;
                if (stristr($Notice->blob, 'AttestazioneTrasmissioneFattura')) {
                    $xsl->load(config('fatturaelettronica.StyleSheetAT'));
                } else if (stristr($Notice->blob, 'NotificaDecorrenzaTermini')) {
                    $xsl->load(config('fatturaelettronica.StyleSheetDT'));
                } else if (stristr($Notice->blob, 'NotificaEsitoCommittente')) {
                    $xsl->load(config('fatturaelettronica.StyleSheetEC'));
                } else if (stristr($Notice->blob, 'NotificaMancataConsegna')) {
                    $xsl->load(config('fatturaelettronica.StyleSheetMC'));
                } else if (stristr($Notice->blob, 'MetadatiInvioFile')) {
                    $xsl->load(config('fatturaelettronica.StyleSheetMT'));
                } else if (stristr($Notice->blob, 'NotificaEsito')) {
                    $xsl->load(config('fatturaelettronica.StyleSheetNE'));
                } else if (stristr($Notice->blob, 'NotificaScarto')) {
                    $xsl->load(config('fatturaelettronica.StyleSheetNS'));
                } else if (stristr($Notice->blob, 'RicevutaConsegna')) {
                    $xsl->load(config('fatturaelettronica.StyleSheetRC'));
                } else if (stristr($Notice->blob, 'RicevutaScarto')) {
                    $xsl->load(config('fatturaelettronica.StyleSheetRS'));
                }

                // Configure the transformer
                $proc = new \XSLTProcessor;
                $proc->importStyleSheet($xsl); // attach the xsl rules

                // determining if output is html document
                $html = $proc->transformToXML($xml);

                // splitting up html document at doctype and doc
                $html_array = explode("\n", $html, 15);

                $html_doc = array_pop($html_array);

                $html_doctype = implode("\n", $html_array);

                // convert XHTML syntax to HTML5
                // <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
                // <!DOCTYPE html>
                $html_doctype = preg_replace("/<!DOCTYPE [^>]+>/", "<!DOCTYPE html>", $html_doctype);

                // <html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
                // <html lang="en">
                $html_doctype = preg_replace('/ xmlns=\"http:\/\/www.w3.org\/1999\/xhtml\"| xml:lang="[^\"]*\"/', '', $html_doctype);
                // <meta http-equiv="content-type" content="text/html; charset=utf-8" />
                // to this --> <meta charset="utf-8" />

                $html_doctype = preg_replace('/<meta http-equiv=\"Content-Type\" content=\"text\/html; charset=(.*[a-z0-9-])\" \/>/i', '<meta charset="\1" />', $html_doctype);
                $html = $html_doctype . "\n" . $html_doc;

                $ret[] = [
                    'id' => $Notice->id,
                    'invoice_id' => $Notice->invoice_id,
                    'remote_id' => $Notice->remote_id,
                    'type' => $Notice->type,
                    'status' => $Notice->status,
                    //'blob' => $Notice->blob,
                    'blob' => $html,
                    'actor' => $Notice->actor,
                    'nomefile' => $Notice->nomefile,
                    'ctime' => $Notice->ctime
                ];
            }
        } else {
            $ret[] = "NESSUN DATO TROVATO";
        }
        return $ret;
    }


    /**
     * Genera file PDF della fattura ricevuta
     *
     * @param Request $request
     * @return void
     */
    protected static function _showXML(Request $request)
    {
        $invoice = eFattura::find($request->id);

        if (is_null($invoice)) {
            return 'Errore La fattura non è stata trovata';
        } else {
            $fat = new FatturaElettronica($invoice->blob);
            if (stristr($invoice->blob, 'FPR12')) {
                $fat->setStylesheet((string) config('fatturaelettronica.StyleSheetPR'));
            } else if (stristr($invoice->blob, 'FPA12')) {
                $fat->setStylesheet((string) config('fatturaelettronica.StyleSheetPA'));
            } else {
                $fat->setStylesheet((string) config('fatturaelettronica.StyleSheetAS'));
            }
            $a = $fat->asHTML();
            $fattura = ['id' => $invoice->id, 'xml' => $a];
            return $fattura;
        }
    }


    /**
     * Genera File XML della fattura e lo registra in Invoices con I-UPLOAD.
     *
     * @return \Illuminate\Http\Response
     */
    protected static function _genXML(Request $request)
    {
        $fattura = eFatturaGen::findOrFail($request->id);
        if (is_null($fattura)) {
            return response()->json('Errore La fattura non è stata trovata');
        } else {

            $invoice = new FatturaElettronica($fattura->trasmissione);
            $progressivo = random_int(00000, 99999);

            $DatiTrasmissione = [
                'IdPaese' => 'IT',
                'IdCodice' => '01174380053',
                'ProgressivoInvio' => $progressivo,
                'FormatoTrasmissione' => $fattura->trasmissione,
                'CodiceDestinatario' => ($fattura->committente->codicedestinatario) ? $fattura->committente->codicedestinatario : 0000000,
                'ContattiTrasmittente/Telefono' => '3389028157',
                'ContattiTrasmittente/Email' => 'stefanmcds@postecert.it',
                'PECDestinatario' => $fattura->committente->pec,
            ];
            $CedentePrestatore = [
                'IdPaese' => $fattura->prestatore->nazione,
                'IdCodice' => $fattura->prestatore->piva,
                'CodiceFiscale' => $fattura->prestatore->codicefiscale,
                'Denominazione' => $fattura->prestatore->denominazone,
                'Nome' => $fattura->prestatore->cognome,
                'Cognome' => $fattura->prestatore->nome,
                'Titolo' => $fattura->prestatore->titilo,
                'CodEORI' => $fattura->prestatore->codeori,
                'AlboProfessionale' => $fattura->prestatore->alboprofessionale,
                'ProvinciaAlbo' => $fattura->prestatore->provinciaalbo,
                'NumeroIscrizioneAlbo' => $fattura->prestatore->numeroiscrizionealbo,
                'DataIscrizioneAlbo' => $fattura->prestatore->dataiscrizionealbo,
                'RegimeFiscale' => $fattura->prestatore->regimefiscale,
                'Sede/Indirizzo' => strtoupper($fattura->prestatore->indirizzo),
                'Sede/NumeroCivico' => $fattura->prestatore->numerocivico,
                'Sede/CAP' => $fattura->prestatore->cap,
                'Sede/Comune' => strtoupper($fattura->prestatore->comune),
                'Sede/Provincia' => strtoupper($fattura->prestatore->provincia),
                'Sede/Nazione' => $fattura->prestatore->nazione,
                'IscrizioneREA/Ufficio' => $fattura->prestatore->rea_ufficio,
                'IscrizioneREA/NumeroREA' => $fattura->prestatore->rea_numero,
                'IscrizioneREA/CapitaleSociale' => $fattura->prestatore->capitale,
                'IscrizioneREA/SocioUnico' => $fattura->prestatore->sociounico,
                'IscrizioneREA/StatoLiquidazione' => $fattura->prestatore->statoliquidazione,
                'Contatti/Telefono' => $fattura->prestatore->telefono,
                'Contatti/Fax' => $fattura->prestatore->fax,
                'Contatti/Email' => $fattura->prestatore->email,
                'RiferimentoAmministrazione' => '',
            ];

            $CessionarioCommittente = [
                'DatiAnagrafici/IdFiscaleIVA/IdPaese' => $fattura->committente->nazione,
                'DatiAnagrafici/IdFiscaleIVA/IdCodice' => $fattura->committente->piva,
                'DatiAnagrafici/CodiceFiscale' => $fattura->committente->codicefiscale,
                'Anagrafica/Denominazione' => $fattura->committente->denominazione,
                'Anagrafica/Nome' => $fattura->committente->nome,
                'Anagrafica/Cognome' => $fattura->committente->cognome,
                'Sede/Indirizzo' => $fattura->committente->indirizzo,
                'Sede/NumeroCivico' => $fattura->committente->numerocivico,
                'Sede/CAP' => $fattura->committente->cap,
                'Sede/Comune' => $fattura->committente->comune,
                'Sede/Provincia' => $fattura->committente->provincia,
                'Sede/Nazione' => $fattura->committente->nazione,
            ];

            $DatiGeneraliDocumento = [
                'TipoDocumento' => $fattura->tipo,
                'Divisa' => $fattura->divisa,
                'Data' => $fattura->data,
                'Numero' => $fattura->numero,
                'Causale' => $fattura->causale,
            ];
            foreach ($fattura->dettaglio as $det) {
                $totale = $det->unitario * $det->qta;
                $DettaglioLinee[$det->nlinea] = [
                    'NumeroLinea' => $det->nlinea,
                    'Descrizione' => $det->descrizione,
                    'Quantita' => $det->qta,
                    'PrezzoUnitario' => $det->unitario,
                    'PrezzoTotale' => $totale,
                    'AliquotaIVA' => $det->aliquotaiva,
                ];
            }
            foreach ($fattura->riepilogo as $rie) {
                $DatiRiepilogo[] = [
                    'AliquotaIVA' => $rie->aliquota,
                    'ImponibileImporto' => $rie->imponibile,
                    'Imposta' => $rie->imposta,
                    'EsigibilitaIVA' => $rie->esigibilitaiva,
                ];
            }
            $DatiPagamento = [
                'CondizioniPagamento' => $fattura->condizionipagamento,
                'ModalitaPagamento' => $fattura->modalitapagamento,
                'DataScadenzaPagamento' => $fattura->datascadenzapagamento,
                'ImportoPagamento' => $fattura->totale,
            ];
            // Nei casi di documenti emessi da un soggetto diverso dal
            // cedente/prestatore va valorizzato l’elemento seguente.
            //'SoggettoEmittente' => ''
            $invoice->addBody(1);
            // Dati Trasmissione
            $invoice->setValues('DatiTrasmissione', $DatiTrasmissione);
            // Cedente Prestatore
            $invoice->setValues('CedentePrestatore', $CedentePrestatore);
            // Cessionario Committente
            $invoice->setValues('CessionarioCommittente', $CessionarioCommittente);
            // Bodies
            $bodies = $invoice->getBodies();
            foreach ($bodies as $k => $body) {
                // Dati Generali
                $invoice->setValues('DatiGeneraliDocumento', $DatiGeneraliDocumento, $body);
                // Dettaglio Linee
                for ($i = 1; $i < count($DettaglioLinee); ++$i) {
                    $invoice->setValues('DettaglioLinee', $DettaglioLinee[$i], $body);
                }
                // Dati Riepilogo
                for ($i = 0; $i < count($DatiRiepilogo); ++$i) {
                    $invoice->setValues('DatiRiepilogo', $DatiRiepilogo[$i], $body);
                }
                // Dati di Pagamento
                $invoice->setValues('DatiPagamento', $DatiPagamento, $body);
            }
            // Validazione
            //$invoice->validate();
            $xml = $invoice->asXML();
            if (isset($xml)) {
                /*
                  $filename = $fattura->prestatore->nazione
                  . $fattura->prestatore->piva
                  . '_' . $fattura->trasmissione
                  . '_' . $fattura->numero . $fattura->anno
                  . str_replace('-', '_', $fattura->data)
                  . '_' . $progressivo . '.xml';
                  $invoice->setFilename($filename);
                 */
                $filename = $invoice->getFilename();

                //$xml = base64_encode($xml);

                self::uploadInvoice($filename, $xml, $fattura);

                return $filename;
            } else {
                return 'NON HO GENERATO IL FILE XML';
            }
        }
    }

    /**
     * Method upload
     * store f_gen or upload xml file to invoices table.
     *
     * @param string $filename
     * @param string $invoice_blob
     * @param object  $fattura
     *
     * @return array
     */
    protected static function _uploadInvoice($filename, $invoice_blob, $fattura = null)
    {
        $invoice = eFattura::create(
            [
                'nomefile' => $filename,
                //'posizione' => '',
                //'cedente' => '',
                //'anno' => '',
                'status' => 'I_UPLOADED',
                'blob' =>  $invoice_blob,
                //'ctime' => date('Y-m-d', time()),
                'actor' => $fattura->prestatore_id,
                'f_gen_id' => ($fattura->id === null) ? null : $fattura->id,
            ]
        );
        return $invoice;
    }

    /**
     * Method upload
     * store f_gen or upload xml file to invoices table.
     *
     * @param string $filename
     * @param string $invoice_blob
     * @param array  $fattura
     *
     * @return array
     */
    protected static function _uploadXML(Request $request)
    {
        $file = $request->file('File');
        foreach ($file as $f) {
            $NomeFile = $f->getClientOriginalName();
            if (stristr($NomeFile, '.p7m')) {
                // salvo il file in una cartella temporanea e lo defirmo per campo blob
                $t = str_replace('.p7m', '', $NomeFile);
                $path = $f->move(public_path() . '/storage/File/', $NomeFile);
                if ($path) {
                    $p7m = exec("openssl smime -verify -inform DER -in /var/www/stedns.it/web/public/storage/File/$NomeFile -noverify -out /var/www/stedns.it/web/public/storage/File/$t");
                    if ($p7m) {
                        $XML = file_get_contents(asset("storage/File/$t"));
                    } else {
                    }
                    $t = base64_encode(file_get_contents($f->getRealPath()));
                }
            } else {
                $XML = file_get_contents($f->getRealPath());
            }
            $invoice = eFattura::create(
                [
                    'nomefile' => $NomeFile,
                    //'posizione' => '',
                    //'cedente' => '',
                    //'anno' => '',
                    'status' => 'I_UPLOADED',
                    'blob' => $XML,
                    'blobcode' => (isset($t)) ? $t : null,
                    //'ctime' => date('Y-m-d', time()),
                    'actor' => $request->Prestatore,
                    //'f_gen_id' => '',
                ]
            );
        }
        return $invoice;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    protected static function _Store(Request $request)
    {
        $user = Auth()->user();
        $txt = '';

        // Aggiungi anagrafiche
        /*
         * Anagrafica prestatore gestita da professionista in inizio
          if ($request->prestatore['id']) {
          $prestatore = \App\Models\Anagrafica::find($request->prestatore['id']);
          } else {
          $prestatore = \App\Models\Anagrafica::where('tipo', '=', $request->prestatore['tipo'])
          ->where('piva', '=', $request->prestatore['piva'])
          ->orwhere('codicefiscale', '=', $request->prestatore['codicefiscale'])
          ->get();
          if (count($prestatore) === 0) {
          $prestatore = \App\Models\Anagrafica::create($request->prestatore);
          $rel['prestatore'] = $prestatore->id;
          }
          }
         *
         */
        if ($request->committente['id']) {
            $committente = Anagrafica::where('id_anagrafica', $request->committente['id'])->get();
        } else {
            $committente = Anagrafica::where('tipo', '=', $request->committente['tipo'])
                ->where('piva', '=', $request->committente['piva'])
                ->orwhere('codicefiscale', '=', $request->committente['codicefiscale'])
                ->get();
            if (count($committente) === 0) {
                $committente = Anagrafica::create($request->committente);
                $rel['committente'] = $committente->id;
            }
        }
        if (isset($rel)) {
            $anarel = [];
            $anarel['professionista'] = $user->id;
            $anarel['prestatore'] = $rel['prestatore'];
            $anarel = eFatturaAnagrafica::create($anarel);
            unset($anarel);
            $anarel = [];
            $anarel['prestatore'] = $rel['prestatore'];
            $anarel['committente'] = $rel['committente'];
            $anarel = eFatturaAnagrafica::create($anarel);
        }
        // popola la fattura
        $fgen = [
            'prestatore_id' => $request->prestatore['id'],
            'committente_id' => $committente->id,
            'trasmissione' => $request->intestazione['trasmissione'],
            'tipo' => $request->intestazione['tipo'],
            'causale' => $request->intestazione['causale'],
            'numero' => $request->intestazione['numero'],
            'data' => datato($request->intestazione['data'], 'en', '-'),
            'anno' => $request->intestazione['anno'],
            'datascadenzapagamento' => datato($request->intestazione['datascadenzapagamento'], 'en', '-'),
            'condizionipagamento' => $request->intestazione['condizionipagamento'],
            'modalitapagamento' => $request->intestazione['modalitapagamento'],
            'imponibile' => $request->riepilogo['totali']['imponibile'],
            'imposta' => $request->riepilogo['totali']['imposta'],
            'totale' => $request->riepilogo['totali']['totale'],
            'divisa' => $request->intestazione['divisa'],
        ];
        $fgen = eFatturaGen::create($fgen);
        foreach ($request->dettaglio as $det) {
            $fdet = [
                'f_gen_id' => $fgen->id,
                'nlinea' => $det['numerolinea'],
                'descrizione' => $det['descrizione'],
                'qta' => $det['quantita'],
                'um' => $det['um'],
                'unitario' => $det['prezzounitario'],
                'aliquotaiva' => $det['aliquotaiva'],
                'tipocessione' => (isset($det['tipocessione'])) ? $det['tipocessione'] : null,
            ];
            $fdet = eFatturaDet::create($fdet);
        }

        foreach ($request->riepilogo['linee'] as $frie) {
            $fiva = [
                'f_gen_id' => $fgen->id,
                'aliquota' => $frie['aliquota'],
                'imponibile' => $frie['imponibile'],
                'imposta' => $frie['imposta'],
                'esigibilitaiva' => $frie['esigibilitaiva'],
            ];
            $fiva = eFatturaIVA::create($fiva);
        }

        return $fgen;
        response()->json(['data' => $fgen->numero]);
    }


    /**
     * Function in_array_r
     * cerca in modo ricorsivo in un array
     *
     * @param string $needle
     * @param array $haystack
     * @param boolean $strict
     * @return void
     *
     * Uso:
     * $b = array(array("Mac", "NT"), array("Irix", "Linux"));
     * echo in_array_r("Irix", $b) ? 'found' : 'not found';
     */
    protected static function in_array_r($needle, $haystack, $strict = false)
    {
        foreach ($haystack as $item) {
            if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && self::in_array_r($needle, $item, $strict))) {
                return true;
            }
        }

        return false;
    }
}
