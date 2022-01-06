<?php

namespace FatturaElettronica;

use FatturaElettronica\WebService;
use App\Http\Controllers\Admin\FatturaController;
use FatturaElettronica\SdIServer\FileSdI;
use FatturaElettronica\SdIServer\FileSdIConMetadati;
use FatturaElettronica\SdIServer\RispostaRiceviFatture;

class SdIRicezioneHandler
{
    use SdITraits;

    /**
     * Notifica Decorrenza Termini
     * @param \StdClass $parametersIn
     * Process request
     * ----------------------------------------
     * $id = $request->IdentificativoSdI;
     * $filename = $request->NomeFile;
     * $file = $request->File;
     * ----------------------------------------
     * For example, to save file:
     * file_put_contents("/some/path/{$request->NomeFile}", $request->File);
     */
    public function NotificaDecorrenzaTermini(\StdClass $parametersIn)
    {
        // SOAP request
        $request = new FileSdI($parametersIn);
        WebService::log(__FUNCTION__ . " $request");
        //$fattura = new FatturaController();
        //$fattura->Receive($request, 'NotificaDecorrenzaTermini', 'I_EXPIRED');
        return $this->_Receive($request, 'NotificaDecorrenzaTermini', 'I_EXPIRED');
    }


    /**
     * Ricezione delle Fatture
     * Process request
     * ------------------------------------------------
     * $id = $request->IdentificativoSdI;
     * $filename = $request->NomeFile;
     * $file = $request->File;
     * $metadataFilename = $request->NomeFileMetadati;
     * $metadata = $request->Metadati;
     *  ------------------------------------------------
     *   // For example, to save files:
     *   // file_put_contents("/some/path/{$request->NomeFile}", $request->File);
     *   // file_put_contents("/some/path/{$request->NomeFileMetadati}", $request->Metadati);
     *
     * @param \StdClass $parametersIn
     * @return void
     */
    public function RiceviFatture(\StdClass $parametersIn)
    {
        // SOAP request
        $request = new FileSdIConMetadati($parametersIn);
        WebService::log(__FUNCTION__ . " $request");
        //$fattura = new FatturaController();
        //$fattura->receiveInvoice($request, 'R_RECEIVED');
        $this->_Receive($request, 'R_RECEIVED');
        // SOAP response
        return new RispostaRiceviFatture(RispostaRiceviFatture::ER01);
    }
}
