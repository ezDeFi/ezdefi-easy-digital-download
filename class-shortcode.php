<?php

class EDD_EZPay_Shortcode
{
    private $domain = 'edd-ezpay';

    private $api;

    public function init()
    {
        $this->add_shortcode();
        $this->add_action();
    }

    public function add_shortcode()
    {
        add_shortcode( 'ezpay_qrcode', array( $this, 'ezpay_qrcode_template' ) );
    }

    public function add_action()
    {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function enqueue_scripts()
    {
        global $post;

        if( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'ezpay_qrcode' ) ) {
            wp_enqueue_script( 'edd_ezpay_qrcode_js', EZPAY_PLUGIN_URL . 'assets/qrcode.js', array('jquery'), '', true );
            wp_localize_script(
                'edd_ezpay_qrcode_js',
                'ajax_object',
                array(
                    'ajax_url' => admin_url( 'admin-ajax.php' )
                )
            );
        }
    }

    public function ezpay_qrcode_template()
    {
        $edd_payment_id = EDD_EZPay()->edd_ezpay_session->get( 'edd_payment_id' );
        $ezpay_payment = EDD_EZPay()->edd_ezpay_session->get( 'edd_ezpay_payment' );
        $payment_data = $ezpay_payment['data'];
        $json = array(
            'edd_payment_id' => $edd_payment_id,
            'id' => $payment_data['_doc']['_id'],
            'expiredTime' => $payment_data['_doc']['expiredTime']
        );
        ob_start(); ?>
        <script type="application/json" data-payment-data><?php echo json_encode( $json ); ?></script>
        <div id="edd_ezpay_qrcode_page" data-qrcode-wrapper>
        <p>You have left <span data-count-down></span> to scan this QR Code</p>
        <p><?php echo $payment_data['_doc']['originCurrency'] . $payment_data['_doc']['originValue']; ?> - <?php echo ($payment_data['_doc']['value'] / pow( 10, $payment_data['_doc']['token']['decimal'] )) . $payment_data['_doc']['currency']; ?></p>
        <img data-qrcode-img src="<?php echo $payment_data['qr']; ?>" />
        </div>
        <?php
        $template = ob_get_clean();
        return $template;
    }
}