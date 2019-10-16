<?php

class EDD_EZPay_Listener
{
    protected $base_url = '';

    public function __construct($base_url)
    {
        $this->base_url = rtrim( $base_url, '/' ) . '/';
    }

    public function listen($url)
    {
        if( strpos( $url, $this->base_url ) !== 0 ) {
            return '';
        }

        $url = substr( $url, strlen( $this->base_url ) );
        $url = parse_url( $url, PHP_URL_PATH );
        $pieces = explode( '/', $url );

        if( !$pieces['0'] ) {
            return '';
        }

        return $pieces['0'];
    }
}