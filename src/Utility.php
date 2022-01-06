<?php

namespace FatturaElettronica;

class Utility
{

    protected $datetime = null;
    protected $real_time = null;
    protected $simulated_time = null;
    protected $speed = null;

    public function __construct()
    {
        $this->datetime = [
            'real_time' => [
                'date' => date('Y-m-d h:m:s.u', time()),
                'timezone_type' => 3,
                'timezone' => 'UTC'
            ],
            'simulated_time' => [
                'date' => date('Y-m-d h:m:s.u', time()),
                'timezone_type' => 2,
                'timezone' => 'Z'
            ],
            'speed' => 0,
        ];
        $this->real_time = $this->datetime['real_time'];
        $this->simulated_time = $this->datetime['simulated_time'];
        $this->speed = $this->datetime['speed'];
    }

    private function persist($data)
    {
        $this->datetime = $data;
    }

    private function retrieve()
    {
        $data = $this->datetime;
        $data['real_time'] = \DateTime::__set_state($data['real_time']);
        $data['simulated_time'] = \DateTime::__set_state($data['simulated_time']);
        return $data;
    }

    public function resetTime()
    {
        $data = array(
            'real_time' => new \DateTime(),
            'simulated_time' => new \DateTime(),
            'speed' => 1.0
        );
        self::persist($data);
    }

    public function setDateTime($datetime)
    {
        $data = self::retrieve();
        $data['real_time'] = new \DateTime();
        $data['simulated_time'] = $datetime;
        self::persist($data);
    }

    public function setSpeed($speed)
    {
        self::getDateTime();
        $data = self::retrieve();
        $data['speed'] = $speed;
        self::persist($data);
    }

    public function getDateTime()
    {
        $data = self::retrieve();
        $real_time_now = new \DateTime();
        $delta_seconds = round(($real_time_now->getTimestamp() - $data['real_time']->getTimestamp()) * $data['speed']);
        $simulated_time_now = $data['simulated_time']->add(new \DateInterval("PT${delta_seconds}S"));
        $data['real_time'] = $real_time_now;
        $data['simulated_time'] = $simulated_time_now;
        self::persist($data);
        return $data['simulated_time'];
    }

    public function unpack($xmlString)
    {
        // defend against XML External Entity Injection
        libxml_disable_entity_loader(true);
        $collapsed_xml_string = preg_replace("/\s+/", "", $xmlString);
        $collapsed_xml_string = $collapsed_xml_string ? $collapsed_xml_string : $xmlString;
        if (preg_match("/\<!DOCTYPE/i", $collapsed_xml_string)) {
            throw new \InvalidArgumentException('Invalid XML: Detected use of illegal DOCTYPE');
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlString, 'SimpleXMLElement', LIBXML_NOWARNING);
        if ($xml === false) {
            throw new \InvalidArgumentException("Cannot load XML\n");
        }
        return $xml;
    }

    public function is_base64_encoded($data)
    {
        $result = false;
        if (preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $data)) {
            $result = base64_decode($data);
        }
        return $result;
    }

    /**
     * stripP7MData
     *
     * removes the PKCS#7 header and the signature info footer from a digitally-signed .xml.p7m file using CAdES format.
     *
     * @param ($string, string) the CAdES .xml.p7m file content (in string format).
     * @return (string) an arguably-valid XML string with the .p7m header and footer stripped away.
     */
    function stripP7MData($string)
    {
        $result = false;
        // verifico se $request->File è p7m estraggo solo xml
        if (preg_match('/<\/.+?>/', $string)) {
            // skip everything before the XML content
            $string = substr($string, strpos($string, '<?xml '));

            // skip everything after the XML content
            preg_match_all('/<\/.+?>/', $string, $matches, PREG_OFFSET_CAPTURE);
            $lastMatch = end($matches[0]);

            $result = substr($string, 0, $lastMatch[1] + strlen($lastMatch[0]));
        }
        return $result;
    }

    /**
     * Removes invalid characters from a UTF-8 XML string
     *
     * @access public
     * @param string a XML string potentially containing invalid characters
     * @return string
     */
    function sanitizeXML($string)
    {
        if (!empty($string)) {
            // remove EOT+NOREP+EOX|EOT+<char> sequence (FatturaPA)
            $string = preg_replace('/(\x{0004}(?:\x{201A}|\x{FFFD})(?:\x{0003}|\x{0004}).)/u', '', $string);

            $regex = '/(
            [\xC0-\xC1] # Invalid UTF-8 Bytes
            | [\xF5-\xFF] # Invalid UTF-8 Bytes
            | \xE0[\x80-\x9F] # Overlong encoding of prior code point
            | \xF0[\x80-\x8F] # Overlong encoding of prior code point
            | [\xC2-\xDF](?![\x80-\xBF]) # Invalid UTF-8 Sequence Start
            | [\xE0-\xEF](?![\x80-\xBF]{2}) # Invalid UTF-8 Sequence Start
            | [\xF0-\xF4](?![\x80-\xBF]{3}) # Invalid UTF-8 Sequence Start
            | (?<=[\x0-\x7F\xF5-\xFF])[\x80-\xBF] # Invalid UTF-8 Sequence Middle
            | (?<![\xC2-\xDF]|[\xE0-\xEF]|[\xE0-\xEF][\x80-\xBF]|[\xF0-\xF4]|[\xF0-\xF4][\x80-\xBF]|[\xF0-\xF4][\x80-\xBF]{2})[\x80-\xBF] # Overlong Sequence
            | (?<=[\xE0-\xEF])[\x80-\xBF](?![\x80-\xBF]) # Short 3 byte sequence
            | (?<=[\xF0-\xF4])[\x80-\xBF](?![\x80-\xBF]{2}) # Short 4 byte sequence
            | (?<=[\xF0-\xF4][\x80-\xBF])[\x80-\xBF](?![\x80-\xBF]) # Short 4 byte sequence (2)
        )/x';
            $string = preg_replace($regex, '', $string);

            $result = "";
            $current = "";
            $length = strlen($string);
            for ($i = 0; $i < $length; $i++) {
                $current = ord($string{
                $i});
                if (($current == 0x9) || ($current == 0xA) || ($current == 0xD) || (($current >= 0x20) && ($current <= 0xD7FF)) || (($current >= 0xE000) && ($current <= 0xFFFD)) || (($current >= 0x10000) && ($current <= 0x10FFFF))
                ) {
                    $result .= chr($current);
                } else {
                    $ret;    // use this to strip invalid character(s)
                    // $ret .= " ";    // use this to replace them with spaces
                }
            }
            $string = $result;
        }
        return $string;
    }
}
