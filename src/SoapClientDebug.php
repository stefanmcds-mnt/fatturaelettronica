<?php
namespace FatturaElettronica;

ini_set('soap.wsdl_cache_enabled', '0');
ini_set('soap.wsdl_cache_ttl', '0');

class SoapClientDebug extends \SoapClient
{

    // https://www.binarytides.com/modify-soapclient-request-php/
    public function __doRequest($request, $location, $action, $version, $one_way = null)
    {

        error_log('SoapClientDebug::__doRequest' . PHP_EOL);
        error_log('request: ' . $request . PHP_EOL);
        error_log('location: ' . $location . PHP_EOL);
        error_log('action: ' . $action . PHP_EOL);
        error_log('version: ' . $version . PHP_EOL);
        error_log('one_way: ' . $one_way . PHP_EOL);
/*
        echo('SoapClientDebug::__doRequest' . PHP_EOL);
        echo('request: ' . $request . PHP_EOL);
        echo('location: ' . $location . PHP_EOL);
        echo('action: ' . $action . PHP_EOL);
        echo('version: ' . $version . PHP_EOL);
        echo('one_way: ' . $one_way . PHP_EOL);
         */
        $soap_request = $request;

        $header = array(
            'Content-type: text/xml;charset="utf-8"',
            'Accept: text/xml',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
            'SOAPAction: "$action"',
            'Content-length: ' . strlen($soap_request),
        );

        $soap_do = curl_init();

        $url = $location;

        $options = [
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSLKEY => config('CERTSPATH') . config('KEYFILE'),
            CURLOPT_SSLCERT => config('CERTSPATH') . config('CLIENTCERTFILE'),
            CURLOPT_CAINFO => config('CERTSPATH') . config('CAFILE'),
            CURLOPT_SSL_ENABLE_ALPN => false,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)',
            CURLOPT_VERBOSE => true,
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $soap_request,
            CURLOPT_HTTPHEADER => $header,
        ];

        curl_setopt_array($soap_do, $options);
        $output = curl_exec($soap_do);

        var_dump('curl output = ');
        var_dump($output);
        $info = curl_getinfo($soap_do);
        var_dump('curl info = ');
        var_dump($info);
        var_dump('curl http code = ' . $info['http_code']);
        if ($output === false) {
            $err_num = curl_errno($soap_do);
            $err_desc = curl_error($soap_do);
            $httpcode = curl_getinfo($soap_do, CURLINFO_HTTP_CODE);
            var_dump("â€”CURL FAIL RESPONSE:\ndati={$output}\nerr_num={$err_num}\nerr_desc={$err_desc}\nhttpcode={$httpcode}");
        } else {
            ///Operation completed successfully
            var_dump('success');
        }

/*
        echo ('curl output = ');
        var_dump($output);
        $info = curl_getinfo($soap_do);
        echo ('curl info = ');
        var_dump($info);
        echo('curl http code = ' . $info['http_code']);
        if ($output === false) {
            $err = 'Curl error: ' . curl_error($soap_do);
            print $err;
        } else {
            ///Operation completed successfully
        }
         */

        curl_close($soap_do);

        // Uncomment the following line, if you actually want to do the request
        // return parent::__doRequest($request, $location, $action, $version);

        return $output;
    }

}
