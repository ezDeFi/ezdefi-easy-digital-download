<?php

class Test_EDD_EZPay_Admin extends WP_UnitTestCase
{
    public $object;

    public function setUp()
    {
        parent::setUp();

        require_once 'includes/class-admin.php';

        $this->object = new EDD_EZPay_Admin();
    }

    public function tearDown()
    {
        parent::tearDown();

        global $wp_settings_fields;
        if( isset( $wp_settings_fields['edd_settings_gateways_ezpay'] ) ) {
            unset( $wp_settings_fields['edd_settings_gateways_ezpay'] );
        }
    }

    public function test_init()
    {
        $this->assertFalse( has_filter( 'edd_settings_sections_gateways', array(
            $this->object, 'register_settings_section'
        ) ) );
        $this->assertFalse( has_filter( 'edd_settings_gateways', array(
            $this->object, 'register_settings'
        ) ) );
        $this->assertFalse( has_action( 'admin_init', array(
            $this->object, 'edd_ezpay_currency_section'
        ) ) );
        $this->assertFalse( has_action( 'admin_enqueue_scripts', array(
            $this->object, 'enqueue_scripts'
        ) ) );

        $this->object->init();

        $this->assertNotFalse( has_filter( 'edd_settings_sections_gateways', array(
            $this->object, 'register_settings_section'
        ) ) );
        $this->assertNotFalse( has_filter( 'edd_settings_gateways', array(
            $this->object, 'register_settings'
        ) ) );
        $this->assertNotFalse( has_action( 'admin_init', array(
            $this->object, 'edd_ezpay_currency_section'
        ) ) );
        $this->assertNotFalse( has_action( 'admin_enqueue_scripts', array(
            $this->object, 'enqueue_scripts'
        ) ) );
    }

    public function test_register_settings_section()
    {
        global $wp_settings_sections;

        $this->assertFalse( isset( $wp_settings_sections['edd_settings_gateways_ezpay'] ) );

        $this->object->init();
        edd_register_settings();

        $this->assertTrue( isset( $wp_settings_sections['edd_settings_gateways_ezpay'] ) );
    }

    public function test_register_settings()
    {
        global $wp_settings_fields;

        $this->assertFalse( isset( $wp_settings_fields['edd_settings_gateways_ezpay']['edd_settings_gateways_ezpay']['edd_settings[ezpay_settings]'] ) );
        $this->assertFalse( isset( $wp_settings_fields['edd_settings_gateways_ezpay']['edd_settings_gateways_ezpay']['edd_settings[ezpay_api_url]'] ) );
        $this->assertFalse( isset( $wp_settings_fields['edd_settings_gateways_ezpay']['edd_settings_gateways_ezpay']['edd_settings[ezpay_api_key]'] ) );
        $this->assertFalse( isset( $wp_settings_fields['edd_settings_gateways_ezpay']['edd_settings_gateways_ezpay']['edd_settings[ezpay_qrcode_page]'] ) );

        $this->object->init();
        edd_register_settings();

        $this->assertTrue( isset( $wp_settings_fields['edd_settings_gateways_ezpay']['edd_settings_gateways_ezpay']['edd_settings[ezpay_settings]'] ) );
        $this->assertTrue( isset( $wp_settings_fields['edd_settings_gateways_ezpay']['edd_settings_gateways_ezpay']['edd_settings[ezpay_api_url]'] ) );
        $this->assertTrue( isset( $wp_settings_fields['edd_settings_gateways_ezpay']['edd_settings_gateways_ezpay']['edd_settings[ezpay_api_key]'] ) );
        $this->assertTrue( isset( $wp_settings_fields['edd_settings_gateways_ezpay']['edd_settings_gateways_ezpay']['edd_settings[ezpay_qrcode_page]'] ) );
    }

    public function test_add_currency_settings()
    {
        global $wp_settings_fields;

        $this->assertFalse( isset( $wp_settings_fields['edd_settings_gateways_ezpay']['edd_settings_gateways_ezpay']['edd_settings[ezpay_currency]'] ) );

        $this->object->init();
        $this->object->edd_ezpay_currency_section();
        edd_register_settings();

        $this->assertTrue( isset( $wp_settings_fields['edd_settings_gateways_ezpay']['edd_settings_gateways_ezpay']['edd_settings[ezpay_currency]'] ) );
    }

    public function test_enqueue_scripts()
    {
        global $wp_scripts;

        $this->object->init();

        $this->assertFalse( wp_style_is( 'edd_ezpay_select2', 'registered' ) );
        $this->assertFalse( wp_script_is( 'edd_ezpay_select2', 'registered' ) );
        $this->assertFalse( wp_style_is( 'edd_ezpay_currency_table', 'registered' ) );
        $this->assertFalse( wp_script_is( 'edd_ezpay_currency_table', 'registered' ) );

        $data = $wp_scripts->get_data( 'edd_ezpay_admin','data' );
        $this->assertFalse( $data );

        $this->object->enqueue_scripts();

        $this->assertFalse( wp_style_is( 'edd_ezpay_select2', 'enqueued' ) );
        $this->assertFalse( wp_script_is( 'edd_ezpay_select2', 'enqueued' ) );
        $this->assertFalse( wp_style_is( 'edd_ezpay_admin', 'enqueued' ) );
        $this->assertFalse( wp_script_is( 'edd_ezpay_admin', 'enqueued' ) );

        $this->assertTrue( wp_style_is( 'edd_ezpay_select2', 'registered' ) );
        $this->assertTrue( wp_script_is( 'edd_ezpay_select2', 'registered' ) );
        $this->assertTrue( wp_style_is( 'edd_ezpay_admin', 'registered' ) );
        $this->assertTrue( wp_script_is( 'edd_ezpay_admin', 'registered' ) );

        $data = $wp_scripts->get_data( 'edd_ezpay_admin', 'data' );
        $this->assertContains( 'ajax_url', $data );
    }
}