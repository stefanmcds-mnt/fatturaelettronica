<?php
namespace FatturaElettronica;

define("PREFISSOTMP", "/tmp/");

class P7MeXML
{
    private $stringaP7M;
    private $stringaXML;
    private $base64 = false;
    private $gzip = false;

    public function __construct($stringa = "", $base64 = false, $gzip = false)
    {
        if (is_bool($gzip)) $this->gzip = $gzip;
        if (is_bool($base64)) $this->base64 = $base64;
        $raw = "";
        if ($this->gzip) $raw = gzuncompress($stringa);
        else $raw = $stringa;
        if ($base64) $this->stringaP7M = base64_decode($raw);
        else $this->stringaP7M = $raw;
        $this->faiXML();
        if (!$this->controllaXML()) {
            $this->base64 = !$this->base64;
            if ($this->base64) $this->stringaP7M = base64_decode($raw);
            else $this->stringaP7M = $raw;
            $this->faiXML();
        }
        if ($this->controllaXML()) return;
        if (is_bool($base64)) $this->base64 = $base64;
        $this->gzip = !$this->gzip;
        if ($this->gzip) $raw = gzuncompress($stringa);
        else $raw = $stringa;
        if ($base64) $this->stringaP7M = base64_decode($raw);
        else $this->stringaP7M = $raw;
        $this->faiXML();
        if (!$this->controllaXML()) {
            $this->base64 = !$this->base64;
            if ($this->base64) $this->stringaP7M = base64_decode($raw);
            else $this->stringaP7M = $raw;
            $this->faiXML();
        }
    }

    public function getXML()
    {
        if (strlen($this->stringaXML) > 0) return $this->stringaXML;
        else return false;
    }

    public function setP7MdaFile($file, $base64 = false)
    {
        if (file_exists($file)) {
            $temp = file_get_contents($file);
            if ($base64) $temp = base64_decode($temp);
            if (strlen($temp) > 0) {
                $this->stringaP7M = $temp;
                $this->faiXML();
            }
        }
    }

    public function setP7MdaStringa($stringa, $base64 = false)
    {
        if (strlen($stringa) > 0) {
            $temp = $stringa;
            if ($base64) $temp = base64_decode($temp);
            if (strlen($temp) > 0) {
                $this->stringaP7M = $temp;
                $this->faiXML();
            }
        }
    }

    private function faiXML()
    {
        if (strlen($this->stringaP7M) > 0) {
            $ora = time();
            file_put_contents(PREFISSOTMP . $ora, $this->stringaP7M);
            system("openssl smime -verify -noverify -in " . PREFISSOTMP . $ora . " -inform DER -out " . PREFISSOTMP . $ora . ".xml");
            if (file_exists(PREFISSOTMP . $ora)) unlink(PREFISSOTMP . $ora);
            if (file_exists(PREFISSOTMP . $ora . ".xml")) {
                $this->stringaXML = file_get_contents(PREFISSOTMP . $ora . ".xml");
                unlink(PREFISSOTMP . $ora . ".xml");
            }
        }
    }

    public function controllaXML()
    {
        $content = trim($this->stringaXML);
        if (empty($content)) {
            return false;
        }

        if (stripos($content, '<!DOCTYPE html>') !== false) {
            return false;
        }

        libxml_use_internal_errors(true);
        simplexml_load_string($content);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        return empty($errors);
    }

    public function getP7M()
    {
        return $this->stringaP7M;
    }

}
