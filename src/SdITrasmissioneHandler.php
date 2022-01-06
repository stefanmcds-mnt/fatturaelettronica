<?php

namespace FatturaElettronica;

use FatturaElettronica\WebService;
use FatturaElettronica\Utility;
//use App\Http\Controllers\Admin\FatturaController;
use FatturaElettronica\SdIClient\FileSdI;

class SdITrasmissioneHandler
{
    use SdITraits;

    /**
     * Notifica di ricevutaConsegna
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
    public function RicevutaConsegna(\StdClass $parametersIn)
    {
        // SOAP request
        $request = new FileSdI($parametersIn);
        WebService::log(__FUNCTION__ . " $request");
        //$fattura = new FatturaController();
        //$fattura->Receive($request, 'RicevutaConsegna', 'I_DELIVERED');
        return $this->_Receive($request, 'RicevutaConsegna', 'I_DELIVERED');
    }

    /**
     * Notifica di MancataConsegna
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
    public function NotificaMancataConsegna(\StdClass $parametersIn)
    {
        // SOAP request
        $request = new FileSdI($parametersIn);
        WebService::log(__FUNCTION__ . " $request");
        //$fattura = new FatturaController();
        //$fattura->Receive($request, 'NotificaMancataConsegna', 'I_FAILED_DELIVERY');
        return $this->_Receive($request, 'NotificaMancataConsegna', 'I_FAILED_DELIVERY');
    }

    /**
     * Notifica di Scarto
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
    public function NotificaScarto(\StdClass $parametersIn)
    {
        // SOAP request
        $request = new FileSdI($parametersIn);
        WebService::log(__FUNCTION__ . " $request");
        //$fattura = new FatturaController();
        //$fattura->Receive($request, 'NotificaScarto', 'I_INVALID');
        return $this->_Receive($request, 'NotificaScarto', 'I_INVALID');
    }

    /**
     * Notifica Esito
     * @param \StdClass $parametersIn
     * @throws \RuntimeException
     * Process request
     * ----------------------------------------
     * $id = $request->IdentificativoSdI;
     * $filename = $request->NomeFile;
     * $file = $request->File;
     * ----------------------------------------
     * For example, to save file:
     * file_put_contents("/some/path/{$request->NomeFile}", $request->File);
     */
    public function NotificaEsito(\StdClass $parametersIn)
    {
        // SOAP request
        $request = new FileSdI($parametersIn);
        WebService::log(__FUNCTION__ . " $request");
        //$fattura = new FatturaController();
        //$fattura->Receive($request, 'NotificaEsito');
        return $this->_Receive($request, 'NotificaEsito');
    }

    /**
     * Notifica Esito Committente
     * @param \StdClass $parametersIn
     * @throws \RuntimeException
     * Process request
     * ----------------------------------------
     * $id = $request->IdentificativoSdI;
     * $filename = $request->NomeFile;
     * $file = $request->File;
     * ----------------------------------------
     * For example, to save file:
     * file_put_contents("/some/path/{$request->NomeFile}", $request->File);
     */
    public function NotificaEsitoCommittente(\StdClass $parametersIn)
    {
        // SOAP request
        $request = new FileSdI($parametersIn);
        WebService::log(__FUNCTION__ . " $request");
        //$fattura = new FatturaController();
        //$fattura->Receive($request, 'NotificaEsito');
        return $this->_Receive($request, 'NotificaEsito');
    }

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
        //$fattura = new FatturaElettronica();
        //$fattura = new FatturaController();
        //$fattura->Receive($request, 'NotificaDecorrenzaTermini', 'I_EXPIRED');
        return $this->_Receive($request, 'NotificaDecorrenzaTermini', 'I_EXPIRED');
    }

    /**
     * Notifica di Attestazione Trasmissione Fattura
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
    public function AttestazioneTrasmissioneFattura(\StdClass $parametersIn)
    {
        // SOAP request
        $request = new FileSdI($parametersIn);
        WebService::log(__FUNCTION__ . " $request");
        //$fattura = new FatturaController();
        //$fattura->Receive($request, 'AttestazioneTrasmissioneFattura', 'I_TRANSMIT');
        return $this->_Receive($request, 'AttestazioneTrasmissioneFattura', 'I_TRANSMIT');
    }
}
