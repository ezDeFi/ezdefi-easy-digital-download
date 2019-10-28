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

        require_once 'helpers/class-helper-download.php';
        require_once 'helpers/class-helper-payment.php';
        require_once 'includes/class-ajax.php';

        $this->object = EDD_EZPay_Ajax::instance();
    }

    public function test_init()
    {
        $this->assertFalse( has_action( 'wp_ajax_edd_ezpay_get_currency', array(
            $this->object, 'get_ezpay_currency'
        ) ) );
        $this->assertFalse( has_action( 'wp_ajax_nopriv_edd_ezpay_get_currency', array(
            $this->object, 'get_ezpay_currency'
        ) ) );
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
        $this->assertFalse( has_action( 'wp_ajax_create_ezpay_payment', array(
            $this->object,
            'create_ezpay_payment'
        ) ) );
        $this->assertFalse( has_action( 'wp_ajax_nopriv_create_ezpay_payment', array(
            $this->object,
            'create_ezpay_payment'
        ) ) );

        $this->object->init();

        $this->assertNotFalse( has_action( 'wp_ajax_edd_ezpay_get_currency', array(
            $this->object, 'get_ezpay_currency'
        ) ) );
        $this->assertNotFalse( has_action( 'wp_ajax_nopriv_edd_ezpay_get_currency', array(
            $this->object, 'get_ezpay_currency'
        ) ) );
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
        $this->assertNotFalse( has_action( 'wp_ajax_create_ezpay_payment', array(
            $this->object,
            'create_ezpay_payment'
        ) ) );
        $this->assertNotFalse( has_action( 'wp_ajax_nopriv_create_ezpay_payment', array(
            $this->object,
            'create_ezpay_payment'
        ) ) );
    }

    public function ttest_can_check_wallet()
    {
        $_POST = array(
            'apiUrl' => 'url',
            'apiKey' => 'key'
        );
        $fake_response = array(
            'body' => '{"code":1,"data":[{"status":"INACTIVE","address":"address_inactive"},{"status":"ACTIVE","address":"address_active"}],"message": "ok"}'
        );

        $mock = $this->getMockBuilder(EDD_EZPay_Ajax::class)
            ->setMethods(['call_api'])
            ->getMock();
        $mock->method('call_api')
            ->with( 'url/user/list_wallet', $_POST['apiKey'] )
            ->willReturn($fake_response);

        $_POST['address'] = 'address_inactive';
        try {
            $mock->check_ezpay_wallet();
        } catch (WPAjaxDieStopException $e) {}
        $this->assertTrue( isset($e) );
        $this->assertEquals( 'false', $e->getMessage() );

        $_POST['address'] = 'address_active';
        try {
            $mock->check_ezpay_wallet();
        } catch (WPAjaxDieStopException $e) {}
        $this->assertTrue( isset($e) );
        $this->assertEquals( 'true', $e->getMessage() );
    }

    public function test_can_get_currency()
    {
        $_POST = array(
            'apiUrl' => 'url',
            'keyword' => 'n'
        );
        $fake_response = array(
            'body' => '{"code":1,"data":[{"name":"ntf"},{"name":"nusd"}],"message": "ok"}'
        );
        $mock = $this->getMockBuilder(EDD_EZPay_Ajax::class)
            ->setMethods(['get_api'])
            ->getMock();
        $mock->method('call_api')
            ->with( 'url/token/list?keyword=n' )
            ->willReturn($fake_response);

        $mock->get_ezpay_currency();

        try {
            $this->_handleAjax('get_ezpay_currency');
        } catch (WPAjaxDieContinueException $e) {}

        $this->assertEquals(
            json_decode($fake_response['body']),
            json_decode($this->_last_response)->data->data
        );
    }

    public function ttest_can_check_payment_status()
    {
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

    public function ttest_can_create_ezpay_payment()
    {
        global $edd_options;
        $edd_options['ezpay_currency'] = array(
            array (
                'symbol' => 'nusd',
                'wallet' => 'currency-wallet',
                'lifetime' => 'currency-lifetime',
                'distance' => 'currency-distance',
                'discount' => '10'
            )
        );
        $fake_response = array(
            'body' => '{"code":1,"data":[{"status":"INACTIVE","address":"address_inactive"},{"status":"ACTIVE","address":"address_active"}],"message": "ok"}'
        );
        $payment_id = EDD_Helper_Payment::create_simple_payment();
        $edd_payment = edd_get_payment($payment_id);
        $_POST['uoid'] = $payment_id;
        $_POST['symbol'] = 'nusd';
        $subtotal = intval($edd_payment->subtotal);
        $discount = intval(10);
        $value = $subtotal - ($subtotal * ($discount / 100));
        $data = [
            'uoid' => $payment_id,
            'to' => 'currency-wallet',
            'value' => $value,
            'currency' => $edd_payment->currency . '/' . 'nusd',
            'safedist' => 'currency-distance',
            'ucid' => $edd_payment->user_id,
            'duration' => 'currency-lifetime',
            'callback' => 'http://12f1d7a4.ngrok.io/edd-ezpay/nextypay'
        ];

        $mock_api = $this->getMockBuilder(EDD_EZPay_Api::class)
            ->setMethods(['callApi'])
            ->getMock();
        $mock_api->method('callApi')
            ->with('payment/create', 'post', $data)
            ->willReturn($fake_response);

        $mock = $this->getMockBuilder(EDD_EZPay_Ajax::class)
            ->setMethods(['get_api'])
            ->getMock();
        $mock->method('get_api')->willReturn($mock_api);

        $mock->init();

        try {
            $this->_handleAjax('create_ezpay_payment');
        } catch (WPAjaxDieContinueException $e) {}

        $this->assertEquals(
            json_decode($fake_response['body']),
            json_decode($this->_last_response)->data
        );
    }
}