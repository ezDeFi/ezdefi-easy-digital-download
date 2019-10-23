<?php

class Test_Class_Shortcode extends WP_UnitTestCase
{
    public $object;

    public function setUp()
    {
        parent::setUp();

        require_once 'includes/class-edd-ezpay.php';
        require_once 'includes/class-shortcode.php';

        EDD_EZPay_Class::instance()->init();

        $this->object = EDD_EZPay_Shortcode::instance();
    }

    public function test_shortcode_are_registered()
    {
        global $shortcode_tags;

        $this->assertArrayHasKey( 'ezpay_qrcode', $shortcode_tags );
    }
}