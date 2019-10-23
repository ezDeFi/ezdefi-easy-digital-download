<?php

/**
 * @group ajax
 * @runTestsInSeparateProcesses
 */

class Test_EDD_EZPay_Ajax extends WP_Ajax_UnitTestCase
{
    public $object;

    public function setUp()
    {
        parent::setUp();

        require_once 'includes/class-ajax.php';

        $this->object = EDD_EZPay_Ajax::instance();
    }

    public function test_init()
    {
        $this->assertFalse( has_action( 'wp_ajax_edd_ezpay_check_wallet', array(
            $this->object, 'check_ezpay_wallet'
        ) ) );
        $this->assertFalse( has_action( 'wp_ajax_nopriv_edd_ezpay_check_wallet', array(
            $this->object, 'check_ezpay_wallet'
        ) ) );
        $this->assertFalse( has_action( 'wp_ajax_check_payment_status', array(
            $this->object,
            'check_payment_status'
        ) ) );
        $this->assertFalse( has_action( 'wp_ajax_nopriv_check_payment_status', array(
            $this->object,
            'check_payment_status'
        ) ) );

        $this->object->init();

        $this->assertNotFalse( has_action( 'wp_ajax_edd_ezpay_check_wallet', array(
            $this->object, 'check_ezpay_wallet'
        ) ) );
        $this->assertNotFalse( has_action( 'wp_ajax_nopriv_edd_ezpay_check_wallet', array(
            $this->object, 'check_ezpay_wallet'
        ) ) );
        $this->assertNotFalse( has_action( 'wp_ajax_check_payment_status', array(
            $this->object,
            'check_payment_status'
        ) ) );
        $this->assertNotFalse( has_action( 'wp_ajax_nopriv_check_payment_status', array(
            $this->object,
            'check_payment_status'
        ) ) );
    }

    public function test_can_check_wallet()
    {
        $fake_response = array(
            'body' => '{"code":1,"data":[{"status":"INACTIVE","address":"address_inactive"},{"status":"ACTIVE","address":"address_active"}],"message": "ok"}'
        );
        $mock_api = $this->getMockBuilder(EDD_EZPay_Api::class)
            ->setMethods(['callApi'])
            ->getMock();
        $mock_api->method('callApi')
            ->with('user/list_wallet', 'get')
            ->willReturn($fake_response);

        $mock = $this->getMockBuilder(EDD_EZPay_Ajax::class)
            ->setMethods(['get_api'])
            ->getMock();
        $mock->method('get_api')->willReturn($mock_api);

        $_POST['address'] = 'address_inactive';
        try {
            $mock->check_ezpay_wallet();
        } catch (WPAjaxDieStopException $e) {}
        $this->assertTrue( isset($e) );
        $this->assertEquals( 'INACTIVE', $e->getMessage() );

        $_POST['address'] = 'address_active';
        try {
            $mock->check_ezpay_wallet();
        } catch (WPAjaxDieStopException $e) {}
        $this->assertTrue( isset($e) );
        $this->assertEquals( 'ACTIVE', $e->getMessage() );
    }

    public function test_can_check_payment_status()
    {
        require_once 'helpers/class-helper-download.php';
        require_once 'helpers/class-helper-payment.php';
        $payment_id = EDD_Helper_Payment::create_simple_payment();
        $_POST['paymentId'] = $payment_id;

        try {
            $this->object->check_payment_status();
        } catch (WPAjaxDieStopException $e) {}
        $this->assertTrue( isset($e) );
        $this->assertEquals( 'Pending', $e->getMessage() );

        $payment_id = EDD_Helper_Payment::create_simple_payment();
        edd_update_payment_status( $payment_id, 'publish' );
        $_POST['paymentId'] = $payment_id;

        try {
            $this->object->check_payment_status();
        } catch (WPAjaxDieStopException $e) {}
        $this->assertTrue( isset($e) );
        $this->assertEquals( 'Complete', $e->getMessage() );
    }
}