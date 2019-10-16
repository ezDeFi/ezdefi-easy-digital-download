<?php

class EDD_EZPay_Api
{
    private $api = [];

    public function __construct($api)
    {
        $this->api = $api;
    }

    private function buildApiUrl($path)
    {
        return $this->api['url'] . '/' . $path;
    }

    public function callApi($path, $method = 'GET', $data)
    {
        $url = $this->buildApiUrl($path);
        $method = strtolower($method);

        $curl = curl_init();

        switch ($method) {
            case 'post' :
                curl_setopt($curl, CURLOPT_POST, 1);
                if ($data)
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                break;
            default :
                if ($data)
                    $url = sprintf("%s?%s", $url, http_build_query($data));
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'api-key: ' . $this->api['key'],
            'accept: application/xml',
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

        $result = curl_exec($curl);

        curl_close($curl);

        return $result;
    }
}