<?php

class Test_EDD_EZPay_Redirect extends WP_UnitTestCase
{
    public $object;

    public function setUp()
    {
        parent::setUp();

        require_once 'includes/functions.php';
        require_once 'includes/class-redirect.php';
        $this->object = EDD_EZPay_Redirect::instance();
    }

    public function test_init()
    {
        $this->assertFalse( has_action( 'edd_ezpay_success', array(
            $this->object, 'edd_ezpay_payment_success'
        ) ) );

        $this->assertFalse( has_action( 'edd_ezpay_timeout', array(
            $this->object, 'edd_ezpay_payment_timeout'
        ) ) );

        $this->object->init();

        $this->assertNotFalse( has_action( 'edd_ezpay_success', array(
            $this->object, 'edd_ezpay_payment_success'
        ) ) );

        $this->assertNotFalse( has_action( 'edd_ezpay_timeout', array(
            $this->object, 'edd_ezpay_payment_timeout'
        ) ) );
    }

    public function test_redirect_when_payment_success()
    {
        $mock = $this->getMockBuilder(EDD_EZPay_Redirect::class)->setMethods(['redirect'])->getMock();
        $mock->expects($this->once())->method('redirect');

        $redirect = $mock->success();

        $this->assertTrue( empty( edd_get_errors() ) );
        $this->assertTrue( true != $redirect );
    }

    public function test_redirect_when_payment_timeout()
    {
        $mock = $this->getMockBuilder(EDD_EZPay_Redirect::class)->setMethods(['redirect'])->getMock();
        $mock->expects($this->once())->method('redirect');

        $redirect = $mock->timeout();

        $this->assertFalse( empty( edd_get_errors() ) );
        $this->assertTrue( isset( edd_get_errors()['ezpay_payment_timeout'] ) );
        $this->assertTrue( true != $redirect );
    }
}