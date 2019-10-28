<?php

class Test_Class_Shortcode extends WP_UnitTestCase
{
    public $object;

    public function setUp()
    {
        parent::setUp();

        require_once 'includes/class-shortcode.php';

        $this->object = EDD_EZPay_Shortcode::instance();
    }

    public function test_init()
    {
        $this->assertFalse( has_filter( 'do_shortcode_tag', array(
            $this->object, 'prepend_content_to_shortcode'
        ) ) );

        $this->object->init();

        $this->assertNotFalse( has_filter( 'do_shortcode_tag', array(
            $this->object, 'prepend_content_to_shortcode'
        ) ) );
    }
}