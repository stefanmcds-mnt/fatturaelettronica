<?php

namespace FatturaElettronica\SdIClient;

use FatturaElettronica\SdIClient\FileSdIBase;

class FileSdI extends FileSdIBase
{
    public $IdentificativoSdI = null;

    public function __construct(\StdClass $parametersIn = null)
    {
        parent::__construct($parametersIn);

        if ($parametersIn) {
            if (!property_exists($parametersIn, 'IdentificativoSdI')) {
                throw new \Exception("Cannot find property 'IdentificativoSdI'");
            }

            $this->IdentificativoSdI = $parametersIn->IdentificativoSdI;
        }
    }

    public function __toString()
    {
        return "IdentificativoSdI:{$this->IdentificativoSdI} " . parent::__toString();
    }

    public function load($file, $contents = null)
    {
        parent::load($file, $contents);

        $xml = null !== $contents && is_string($contents)
            ? simplexml_load_string($contents)
            : simplexml_load_file($file);

        if (!property_exists($xml, 'IdentificativoSdI')) {
            throw new \Exception("Cannot find 'IdentificativoSdI' in '$file'");
        }

        $this->IdentificativoSdI = $xml->IdentificativoSdI;

        return $this;
    }

    public function import($file)
    {
        return $this->load($file);
    }
}
