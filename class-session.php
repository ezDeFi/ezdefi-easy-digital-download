<?php

class EDD_EZPay_Session
{
    private $session;

    public function __construct()
    {
        if ( ! defined( 'WP_SESSION_COOKIE' ) ) {
            define( 'WP_SESSION_COOKIE', 'edd_ezpay_wp_session' );
        }

        if( !class_exists( 'Recursive_ArrayAccess' ) ) {
            require_once EZPAY_PLUGIN_PATH . 'libraries/class-recursive-arrayaccess.php';
        }

        if( !class_exists( 'WP_Session' ) ) {
            require_once EZPAY_PLUGIN_PATH . 'libraries/class-wp-session.php';
            require_once EZPAY_PLUGIN_PATH . 'libraries/wp-session.php';
        }

        add_filter( 'wp_session_expiration_variant', array( $this, 'set_expiration_variant_time' ), 99999 );
        add_filter( 'wp_session_expiration', array( $this, 'set_expiration_time' ), 99999 );

        if( empty( $this->session ) ) {
            add_action( 'plugin_loaded', array( $this, 'init'), -1 );
        } else {
            add_action( 'init', array( $this, 'init' ), -1 );
        }
    }

    public function init()
    {
        $this->session = WP_Session::get_instance();

        return $this->session;
    }

    public function get($key) {

        $key    = sanitize_key( $key );
        $return = false;

        if ( isset( $this->session[$key] ) && ! empty( $this->session[$key] ) ) {

            preg_match( '/[oO]\s*:\s*\d+\s*:\s*"\s*(?!(?i)(stdClass))/', $this->session[$key], $matches );
            if ( ! empty( $matches ) ) {
                $this->set( $key, null );
                return false;
            }

            if ( is_numeric( $this->session[$key] ) ) {
                $return = $this->session[$key];
            } else {

                $maybe_json = json_decode( $this->session[ $key ] );

                // Since json_last_error is PHP 5.3+, we have to rely on a `null` value for failing to parse JSON.
                if ( is_null( $maybe_json ) ) {
                    $is_serialized = is_serialized( $this->session[ $key ] );
                    if ( $is_serialized ) {
                        $value = @unserialize( $this->session[ $key ] );
                        $this->set( $key, (array) $value );
                        $return = $value;
                    } else {
                        $return = $this->session[ $key ];
                    }
                } else {
                    $return = json_decode( $this->session[ $key ], true );
                }

            }
        }

        return $return;
    }

    public function set($key, $value)
    {
        $key = sanitize_key( $key );

        if( is_array( $value ) ) {
            $this->session[$key] = wp_json_encode( $value );
        } else {
            $this->session[$key] = esc_attr( $value );
        }

        return $this->session[$key];
    }

    public function set_expiration_variant_time( $exp ) {
        return ( 30 * 60 * 23 );
    }

    public function set_expiration_time( $exp ) {
        return ( 30 * 60 * 24 );
    }
}