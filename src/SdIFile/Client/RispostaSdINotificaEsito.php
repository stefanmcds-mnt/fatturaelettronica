<?php

namespace FatturaElettronica\SdIClient;

use FatturaElettronica\Client;

class RispostaSdINotificaEsito
{
    // Notifica non accettata
    const ES00 = 'ES00';
    // Notifica accettata
    const ES01 = 'ES01';
    // Servizio non disponibile
    const ES02 = 'ES02';

    public $Esito = null;
    public $ScartoEsito = null;

    public function __construct(\StdClass $obj)
    {
        $this->Esito = $obj->Esito;
        if (true === property_exists($obj, 'ScartoEsito')) {
            $this->ScartoEsito = new FileSdIBase($obj->ScartoEsito);
        }

        Client::log($this);
    }

    public function __toString()
    {
        $classArray = explode('\\', __CLASS__);
        $str = array_pop($classArray)
            . " Esito:{$this->Esito}";

        if (null !== $this->ScartoEsito) {
            $str .= " ScartoEsito->NomeFile:{$this->ScartoEsito->NomeFile}";
        }

        return $str;
    }
}
