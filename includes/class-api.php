<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EDD_EZPay_Api
{
    protected static $instance = null;

    /** Only one instance of EDD_EZPay_Class can be loaded */
    public static function instance()
    {
        if( is_null( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function getApiUrl()
    {
        return rtrim( edd_get_option( 'ezpay_api_url' ), '/' ) . '/';
    }

    private function getApiKey()
    {
        return edd_get_option( 'ezpay_api_key' );
    }

    private function buildApiUrl($path)
    {
        return $this->getApiUrl() . $path;
    }

    public function callApi($path, $method = 'GET', $data = [])
    {
        $url = $this->buildApiUrl( $path) ;
        $method = strtolower( $method );
        $headers = array(
            'api-key' => $this->getApiKey(),
            'accept' => 'application/xml',
        );

        if($method === 'post') {
            return wp_remote_post( $url, array(
                'headers' => $headers,
                'body' => $data
            ) );
        }

        if(!empty($data)) {
            $url = sprintf("%s?%s", $url, http_build_query( $data ) );
        }

        return wp_remote_get(
            $url,
            array( 'headers' => $headers )
        );
    }

    public function callApiOld($path, $method = 'GET', $data)
    {
        $url = $this->buildApiUrl( $path) ;
        $method = strtolower( $method );

        $curl = curl_init();

        switch ( $method ) {
            case 'post' :
                curl_setopt( $curl, CURLOPT_POST, 1 );
                if ( $data )
                    curl_setopt( $curl, CURLOPT_POSTFIELDS, $data );
                break;
            default :
                if ( $data )
                    $url = sprintf("%s?%s", $url, http_build_query( $data ) );
        }

        curl_setopt( $curl, CURLOPT_URL, $url );
        curl_setopt( $curl, CURLOPT_HTTPHEADER, array(
            'api-key: ' . $this->getKey(),
            'accept: application/xml',
        ));
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );

        $result = curl_exec( $curl );

        curl_close( $curl );

        return $result;
    }
}