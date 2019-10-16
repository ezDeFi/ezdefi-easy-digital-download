<?php

class EDD_EZPay_Admin {
    private $domain = 'edd-ezpay';

    private $api;

    public function init()
    {
        $this->load_dependencies();
        $this->add_filter();
        $this->add_action();
    }

    private function load_dependencies()
    {
        $config = [];
        $config['url'] = rtrim( edd_get_option( 'ezpay_api_url' ), '/\\' );
        $config['key'] = edd_get_option( 'ezpay_api_key' );

        require_once EZPAY_PLUGIN_PATH . 'class-api.php';
        $this->api = new EDD_EZPay_Api($config);
    }

    private function add_filter()
    {
        add_filter( 'edd_settings_sections_gateways', array( $this, 'register_setting_section' ), 1, 1 );
        add_filter( 'edd_settings_gateways', array( $this, 'register_settings' ), 1, 1 );
    }

    private function add_action()
    {
        add_action( 'admin_init', array( $this, 'add_ezpay_currency_select_section' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'edd_ezpay_nextypay', array( $this, 'nextypay_action_handle' ) );
        add_action( 'wp_ajax_get_ezpay_token', array( $this, 'get_ezpay_token' ) );
        add_action( 'wp_ajax_check_payment_status', array( $this, 'check_payment_status' ) );
    }

    public function enqueue_scripts()
    {
        wp_enqueue_style( 'edd_ezpay_select2_css', EZPAY_PLUGIN_URL . 'assets/select2.min.css' );
        wp_enqueue_script( 'edd_ezpay_select2_script', EZPAY_PLUGIN_URL . 'assets/select2.min.js', array('jquery') );
        wp_enqueue_style( 'edd_ezpay_admin_css', EZPAY_PLUGIN_URL . 'assets/admin.css' );
        wp_enqueue_script( 'edd_ezpay_admin_script', EZPAY_PLUGIN_URL . 'assets/admin.js', array('jquery') );
        wp_localize_script(
            'edd_ezpay_admin_script',
            'ajax_object',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php?action=get_ezpay_token' )
            )
        );
    }

    public function register_setting_section($sections)
    {
        $sections['ezpay'] = __( 'EZPay', $this->domain );

        return $sections;
    }

    public function register_settings($gateway_settings)
    {
        $ezpay_settings = array(
            'ezpay_settings' => array(
                'id'   => 'ezpay_settings',
                'name' => '<strong>' . __( 'EZPay Settings', $this->domain ) . '</strong>',
                'type' => 'header',
            ),
            'ezpay_api_url' => array(
                'id' => 'ezpay_api_url',
                'name' => __( 'Gateway API Url', $this->domain ),
                'desc' => __( 'Gateway API Url Description', $this->domain ),
                'type' => 'text',
                'size' => 'regular'
            ),
            'ezpay_api_key' => array(
                'id' => 'ezpay_api_key',
                'name' => __( 'API Key', $this->domain ),
                'desc' => __( 'API Key Description', $this->domain ),
                'type' => 'text',
                'size' => 'regular'
            ),
            'ezpay_order_status' => array(
                'id' => 'ezpay_order_status',
                'name' => __( 'Order Status', $this->domain ),
                'desc' => __( 'Choose whether status you wish after checkout.', $this->domain ),
                'type' => 'select',
                'options' => edd_get_payment_statuses()
            )
        );

        $gateway_settings['ezpay'] = $ezpay_settings;

        return $gateway_settings;
    }

    public function get_ezpay_token()
    {
        $keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';
        $data = array ( 'keyword' => $keyword );

        echo json_decode( $this->api->callApi( 'token/list', 'get', $data ), true );

        wp_die();
    }

    public function add_ezpay_currency_select_section() {
        add_settings_field(
            'edd_settings[ezpay_crypto]',
            __( 'Select Cryptos', $this->domain ),
            array( $this, 'edd_ezpay_currency_table' ),
            'edd_settings_gateways_ezpay',
            'edd_settings_gateways_ezpay'
        );
    }

    public function edd_ezpay_currency_table()
    {
        ob_start(); ?>
        <table id="edd_ezpay_currency_table" class="widefat" data-currency-table>
            <thead>
            <tr>
                <th scope="col" class="name"><?php _e( 'Currency', $this->domain ); ?></th>
                <th scope="col" class="discount"><?php _e( 'Discount', $this->domain ); ?></th>
                <th scope="col"><?php _e( 'Payment Lifetime', $this->domain ); ?></th>
                <th scope="col"><?php _e( 'Wallet Address', $this->domain ); ?></th>
                <th scope="col"><?php _e( 'Safe Block Distance', $this->domain ); ?></th>
                <th scope="col"><?php _e( 'Remove', $this->domain ); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php $currencies = edd_get_option( 'ezpay_currency' );  ?>
            <?php if( $currencies && !empty( $currencies ) ) : ; ?>
                <?php foreach( $currencies as $index => $c ) : ?>
                <tr>
                    <td class="ezpay_currency_select">
                        <!--                        <select name="edd_settings[ezpay_currency][0][id]" data-currency-select>-->
                        <!--                        </select>-->
                        <input type="hidden" value="<?php echo (isset($c['id'])) ? $c['id'] : ''; ?>" name="edd_settings[ezpay_currency][<?php echo $index; ?>][id]">
                        <?php echo (isset($c['id'])) ? $c['id'] : ''; ?>
                    </td>
                    <td class="ezpay_currency_discount">
                        <input type="text" name="edd_settings[ezpay_currency][<?php echo $index; ?>][discount]" value="<?php echo (isset($c['discount'])) ? $c['discount'] : ''; ?>">
                    </td>
                    <td>
                        <input type="text" name="edd_settings[ezpay_currency][<?php echo $index; ?>][lifetime]" value="<?php echo (isset($c['lifetime'])) ? $c['lifetime'] : ''; ?>">
                    </td>
                    <td>
                        <input type="text" name="edd_settings[ezpay_currency][<?php echo $index; ?>][wallet]" value="<?php echo (isset($c['wallet'])) ? $c['wallet'] : ''; ?>">
                    </td>
                    <td>
                        <input type="text" name="edd_settings[ezpay_currency][<?php echo $index; ?>][distance]" value="<?php echo (isset($c['distance'])) ? $c['distance'] : ''; ?>">
                    </td>
                    <td>
                        <span class="button-secondary" data-remove-currency-btn>Remove</span>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td class="ezpay_currency_select">
                        <select name="edd_settings[ezpay_currency][0][id]" data-currency-select>
                        </select>
                    </td>
                    <td class="ezpay_currency_discount">
                        <input type="text" name="edd_settings[ezpay_currency][0][discount]">
                    </td>
                    <td>
                        <input type="text" name="edd_settings[ezpay_currency][0][lifetime]">
                    </td>
                    <td>
                        <input type="text" name="edd_settings[ezpay_currency][0][wallet]">
                    </td>
                    <td>
                        <input type="text" name="edd_settings[ezpay_currency][0][distance]">
                    </td>
                    <td>
                        <span class="button-secondary" data-remove-currency-btn>Remove</span>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
        <p>
            <span class="button-secondary" data-add-currency-btn>Add Currency</span>
        </p>
        <?php echo ob_get_clean();
    }

    public function check_payment_status()
    {
        $payment_id = $_POST['paymentId'];

        echo edd_get_payment_status( $payment_id, true );

        wp_die();
    }

    public function nextypay_action_handle()
    {
        $edd_payment_id = $_GET['uoid'];
        $ezpay_payment_id = $_GET['paymentid'];

        $data = array(
            'paymentid' => $ezpay_payment_id,
        );

        $ezpay_payment_data = json_decode( $this->api->callApi( 'payment/get', 'get', $data ), true );

        $status = $ezpay_payment_data['data']['payment']['status'];

        if( $status === 'DONE' ) {
            $this->nextypay_success_handle($edd_payment_id);
        }

        die();
    }

    private function nextypay_success_handle($payment_id)
    {
        edd_update_payment_status( $payment_id, 'complete' );
        edd_empty_cart();
    }
}