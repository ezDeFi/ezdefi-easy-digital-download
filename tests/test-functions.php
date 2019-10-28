<?php

class Test_Function extends WP_UnitTestCase
{
    public function setUp()
    {
        parent::setUp();

        require_once 'includes/class-edd-ezpay.php';

        EDD_EZPay_Class::instance()->init();
    }

    public function test_get_ezpay_currency()
    {
        global $edd_options;
        $edd_options['ezpay_currency'] = array(
            array ( 'id' => 'nusd' ),
            array ( 'id' => 'nusd2' )
        );

        $currency = edd_ezpay_get_currency();

        $this->assertEquals( 2, count( $currency ) );
        $this->assertContains( ['id' => 'nusd'], $currency );
        $this->assertContains( ['id' => 'nusd2'], $currency );
    }
}