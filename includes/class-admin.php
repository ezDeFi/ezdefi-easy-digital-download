<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EDD_EZPay_Admin
{
    public function init()
    {
        $this->init_hooks();
        $this->init_settings_section();
        $this->init_settings();
        $this->init_currency_settings_section();
    }

    /** Hook into action and filter */
    public function init_hooks()
    {
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    /** Add Ezpay settings section */
    public function init_settings_section()
    {
        add_filter( 'edd_settings_sections_gateways', array( $this, 'register_settings_section' ), 1, 1 );
    }

    /** Ezpay settings section callback */
    public function register_settings_section($sections)
    {
        $sections['ezpay'] = __( 'EZPay', 'edd-ezpay' );

        return $sections;
    }

    /** Add Ezpay settings */
    public function init_settings()
    {
        add_filter( 'edd_settings_gateways', array( $this, 'register_settings' ), 1, 1 );
    }

    /** Ezpay settings callback */
    public function register_settings($gateway_settings)
    {
        $ezpay_settings = array(
            'ezpay_settings' => array(
                'id'   => 'ezpay_settings',
                'name' => '<strong>' . __( 'EZPay Settings', 'edd-ezpay' ) . '</strong>',
                'type' => 'header',
            ),
            'ezpay_api_url' => array(
                'id' => 'ezpay_api_url',
                'name' => __( 'Gateway API Url', 'edd-ezpay' ),
                'desc' => __( 'Gateway API Url Description', 'edd-ezpay' ),
                'type' => 'text',
                'size' => 'regular'
            ),
            'ezpay_api_key' => array(
                'id' => 'ezpay_api_key',
                'name' => __( 'API Key', 'edd-ezpay' ),
                'desc' => __( 'API Key Description', 'edd-ezpay' ),
                'type' => 'text',
                'size' => 'regular'
            ),
            'ezpay_qrcode_page' => array(
                'id' => 'ezpay_qrcode_page',
                'name' => __( 'QRCode Page', 'edd-ezpay' ),
                'desc'        => __( 'This is the page where buyers can scan QR code', 'edd-ezpay' ),
                'type'        => 'select',
                'options'     => edd_get_pages(),
                'chosen'      => true,
                'placeholder' => __( 'Select a page', 'edd-ezpay' ),
            )
        );

        $gateway_settings['ezpay'] = $ezpay_settings;

        return $gateway_settings;
    }

    /** Add Ezpay currency settings section */
    public function init_currency_settings_section()
    {
        add_action( 'admin_init', array( $this, 'edd_ezpay_currency_section' ) );
    }

    /** EZPay currency settings section callback */
    public function edd_ezpay_currency_section() {
        add_settings_field(
            'edd_settings[ezpay_currency]',
            __( 'Select currency', 'edd-ezpay' ),
            array( $this, 'edd_ezpay_currency_table' ),
            'edd_settings_gateways_ezpay',
            'edd_settings_gateways_ezpay'
        );
    }

    /** EZPay currency settings callback */
    public function edd_ezpay_currency_table()
    {
        wp_enqueue_style( 'edd_ezpay_select2' );
        wp_enqueue_script( 'edd_ezpay_select2' );
        wp_enqueue_style( 'edd_ezpay_admin' );
        wp_enqueue_script( 'edd_ezpay_admin' );
        ob_start(); ?>
        <table id="" class="ezpay-currency-table widefat striped" data-currency-table>
            <thead>
            <tr>
                <th scope="col"><?php _e( 'Currency', 'edd-ezpay' ); ?></th>
                <th scope="col"><?php _e( 'Discount', 'edd-ezpay' ); ?></th>
                <th scope="col"><?php _e( 'Payment Lifetime', 'edd-ezpay' ); ?></th>
                <th scope="col"><?php _e( 'Wallet Address', 'edd-ezpay' ); ?></th>
                <th scope="col"><?php _e( 'Safe Block Distance', 'edd-ezpay' ); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php $currencies = edd_get_option( 'ezpay_currency' );  ?>
            <?php if( $currencies && !empty( $currencies ) ) : ; ?>
                <?php foreach( $currencies as $index => $c ) : ?>
                    <tr>
                        <td class="ezpay-currency-name">
                            <input data-id-input type="hidden" value="<?php echo (isset($c['id'])) ? $c['id'] : ''; ?>" name="edd_settings[ezpay_currency][<?php echo $index; ?>][id]">
                            <input data-symbol-input type="hidden" value="<?php echo (isset($c['symbol'])) ? $c['symbol'] : ''; ?>" name="edd_settings[ezpay_currency][<?php echo $index; ?>][symbol]">
                            <input data-name-input type="hidden" value="<?php echo (isset($c['name'])) ? $c['name'] : ''; ?>" name="edd_settings[ezpay_currency][<?php echo $index; ?>][name]">
                            <input data-logo-input type="hidden" value="<?php echo (isset($c['logo'])) ? $c['logo'] : ''; ?>" name="edd_settings[ezpay_currency][<?php echo $index; ?>][logo]">
                            <div class="view" data-currency-view>
                                <?php if( isset( $c['logo'] ) ) : ?>
                                    <img src="<?php echo $c['logo']; ?>" class="ezpay_currency_logo" alt="">
                                <?php endif; ?>
                                <span class="ezpay_currency_name"><?php echo (isset($c['name'])) ? $c['name'] : ''; ?></span>
                                <div class="actions">
                                    <a href="" class="ezpay-currency-edit" data-edit-btn>Edit</a>
                                    |
                                    <a href="" class="ezpay-currency-delete" data-delete-btn>Delete</a>
                                </div>
                            </div>
                            <div class="edit" data-currency-edit>
<!--                                <select data-currency-select>-->
<!--                                    <option selected value="--><?php //echo (isset($c['id'])) ? $c['id'] : ''; ?><!--">--><?php //echo (isset($c['name'])) ? $c['name'] : ''; ?><!--</option>-->
<!--                                </select>-->
                                <?php if( isset( $c['logo'] ) ) : ?>
                                    <img src="<?php echo $c['logo']; ?>" class="ezpay_currency_logo" alt="">
                                <?php endif; ?>
                                <span class="ezpay_currency_name"><?php echo (isset($c['name'])) ? $c['name'] : ''; ?></span>
                                <div class="actions">
                                    <a href="" class="ezpay-currency-cancel" data-cancel-btn>Cancel</a>
                                </div>
                            </div>
                        </td>
                        <td class="ezpay-currency-discount">
                            <div class="view" data-currency-view>
                                <?php echo (isset($c['discount'])) ? $c['discount'] : ''; ?><span> %</span>
                            </div>
                            <div class="edit" data-currency-edit>
                                <input type="number" name="edd_settings[ezpay_currency][<?php echo $index; ?>][discount]" value="<?php echo (isset($c['discount'])) ? $c['discount'] : ''; ?>"><span> %</span>
                            </div>
                        </td>
                        <td class="ezpay-currency-lifetime">
                            <div class="view" data-currency-view>
                                <?php echo (isset($c['lifetime'])) ? $c['lifetime'] : ''; ?>
                            </div>
                            <div class="edit" data-currency-edit>
                                <input type="number" name="edd_settings[ezpay_currency][<?php echo $index; ?>][lifetime]" value="<?php echo (isset($c['lifetime'])) ? $c['lifetime'] : ''; ?>">
                            </div>
                        </td>
                        <td class="ezpay-currency-wallet">
                            <div class="view" data-currency-view>
                                <?php echo (isset($c['wallet'])) ? $c['wallet'] : ''; ?>
                            </div>
                            <div class="edit" data-currency-edit>
                                <input data-currency-wallet type="text" name="edd_settings[ezpay_currency][<?php echo $index; ?>][wallet]" value="<?php echo (isset($c['wallet'])) ? $c['wallet'] : ''; ?>">
                            </div>
                        </td>
                        <td class="ezpay-currency-distance">
                            <div class="view" data-currency-view>
                                <?php echo (isset($c['distance'])) ? $c['distance'] : ''; ?>
                            </div>
                            <div class="edit" data-currency-edit>
                                <input type="text" name="edd_settings[ezpay_currency][<?php echo $index; ?>][distance]" value="<?php echo (isset($c['distance'])) ? $c['distance'] : ''; ?>">
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr data-blank-row>
                    <td colspan="5">
                        No currency have been added.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
        <script type="text/html" data-table-row-template>
            <tr data-editing-row>
                <td class="ezpay-currency-name">
                    <input data-id-input type="hidden" value="" name="edd_settings[ezpay_currency][0][id]">
                    <input data-symbol-input type="hidden" value="" name="edd_settings[ezpay_currency][0][symbol]">
                    <input data-name-input type="hidden" value="" name="edd_settings[ezpay_currency][0][name]">
                    <input data-logo-input type="hidden" value="" name="edd_settings[ezpay_currency][0][logo]">
                    <div class="view" data-currency-view>
                        <div class="actions">
                            <a href="" class="ezpay-currency-edit" data-edit-btn>Edit</a>
                            |
                            <a href="" class="ezpay-currency-delete" data-delete-btn>Delete</a>
                        </div>
                    </div>
                    <div class="edit" data-currency-edit>
                        <select data-currency-select>
                        </select>
                        <div class="actions">
                            <a href="" class="ezpay-currency-cancel" data-cancel-btn>Cancel</a>
                        </div>
                    </div>
                </td>
                <td class="ezpay-currency-discount">
                    <div class="view" data-currency-view>
                    </div>
                    <div class="edit" data-currency-edit>
                        <input type="number" name="edd_settings[ezpay_currency][0][discount]" value=""><span> %</span>
                    </div>
                </td>
                <td class="ezpay-currency-lifetime">
                    <div class="view" data-currency-view>
                    </div>
                    <div class="edit" data-currency-edit>
                        <input type="number" name="edd_settings[ezpay_currency][0][lifetime]" value="">
                    </div>
                </td>
                <td class="ezpay-currency-wallet">
                    <div class="view" data-currency-view>
                    </div>
                    <div class="edit" data-currency-edit>
                        <input data-currency-wallet type="text" name="edd_settings[ezpay_currency][0][wallet]" value="">
                    </div>
                </td>
                <td class="ezpay-currency-distance">
                    <div class="view" data-currency-view>
                    </div>
                    <div class="edit" data-currency-edit>
                        <input type="text" name="edd_settings[ezpay_currency][0][distance]" value="">
                    </div>
                </td>
            </tr>
        </script>
        <script type="text/html" data-blank-row-template>
            <tr data-blank-row>
                <td colspan="5">
                    <input type="hidden" name="edd_settings[ezpay_currency]" value="">
                    No currency have been added.
                </td>
            </tr>
        </script>
        <p>
            <span class="button-secondary" data-add-btn>Add Currency</span>
        </p>
        <?php echo ob_get_clean();
    }

    /** Load needed CSS and JS file */
    public function enqueue_scripts()
    {
        wp_register_style( 'edd_ezpay_select2', EDD_EZPay()->plugin_url() . '/assets/select2.min.css' );
        wp_register_script( 'edd_ezpay_select2', EDD_EZPay()->plugin_url() . '/assets/select2.min.js', array('jquery') );
        wp_register_style( 'edd_ezpay_admin', EDD_EZPay()->plugin_url() . '/assets/edd-ezpay-admin.css' );
        wp_register_script( 'edd_ezpay_admin', EDD_EZPay()->plugin_url() . '/assets/edd-ezpay-admin.js', array('jquery') );
        wp_localize_script( 'edd_ezpay_admin', 'edd_ezpay_data',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' )
            )
        );
    }
}