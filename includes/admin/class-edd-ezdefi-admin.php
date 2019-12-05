<?php

defined( 'ABSPATH' ) or exit;

class EDD_Ezdefi_Admin
{
	/**
	 * EDD_Ezdefi_Admin constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ), 5 );

		add_action( 'admin_enqueue_scripts', array( $this, 'load_styles_scripts' ) );

		add_filter( 'plugin_action_links', array( $this, 'add_action_links' ), 10, 2 );

		add_action( 'admin_menu', array( $this, 'add_ezdefi_page_link' ), 10 );
	}

	/**
	 * Initializes class
	 */
	public function init()
	{
		require_once edd_ezdefi()->plugin_path() . '/includes/admin/class-edd-ezdefi-admin-settings.php';

		require_once edd_ezdefi()->plugin_path() . '/includes/admin/class-edd-ezdefi-admin-notices.php';
	}

	/**
	 * Load needed CSS and JS file
	 */
	public function load_styles_scripts()
	{
		wp_register_script( 'edd_ezdefi_jquery_tiptip', edd_ezdefi()->plugin_url() . '/assets/jquery.tipTip.js', array('jquery') );
		wp_register_style( 'edd_ezdefi_select2', edd_ezdefi()->plugin_url() . '/assets/select2.min.css' );
		wp_register_script( 'edd_ezdefi_select2', edd_ezdefi()->plugin_url() . '/assets/select2.min.js', array('jquery') );
		wp_register_script( 'edd_ezdefi_jquery_validation', edd_ezdefi()->plugin_url() . '/assets/jquery.validate.min.js', array('jquery') );
		wp_register_style( 'edd_ezdefi_admin', edd_ezdefi()->plugin_url() . '/assets/edd-ezdefi-admin.css' );
		wp_register_script( 'edd_ezdefi_admin', edd_ezdefi()->plugin_url() . '/assets/edd-ezdefi-admin.js', array( 'jquery', 'jquery-ui-sortable', 'jquery-tiptip' ) );
		wp_localize_script( 'edd_ezdefi_admin', 'edd_ezdefi_data',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' )
			)
		);
		wp_register_script( 'edd_ezdefi_blockui', edd_ezdefi()->plugin_url() . '/assets/jquery.blockUI.js', array( 'jquery' ) );
		wp_register_style( 'edd_ezdefi_assign', edd_ezdefi()->plugin_url() . '/assets/edd-ezdefi-assign.css' );
		wp_register_script( 'edd_ezdefi_assign', edd_ezdefi()->plugin_url() . '/assets/edd-ezdefi-assign.js', array( 'jquery' ) );
	}

	/**
	 * Add action links in the plugin's row
	 */
	public function add_action_links( $links )
	{
		$plugin_links = array(
			'<a href="admin.php?page=edd-settings&tab=gateways&section=ezdefi">' . esc_html__( 'Settings', 'edd-ezdefi' ) . '</a>',
			'<a href="https://ezdefi.io">' . esc_html__( 'Support', 'edd-ezdefi' ) . '</a>'
		);

		return array_merge( $plugin_links, $links );
	}

	public function add_ezdefi_page_link()
	{
		add_submenu_page( 'edit.php?post_type=download', __( 'ezDeFi Exception', 'edd-ezdefi' ), __( 'ezDeFi Exception', 'edd-ezdefi' ), 'manage_shop_settings', 'edd-ezdefi', array( $this, 'add_ezdefi_page' ) );
	}

	public function add_ezdefi_page()
	{
	    global $wpdb;

	    $payments = edd_get_payments( array(
	        'status' => 'pending',
            'output' => 'payments'
        ) );

		$data = array();

		foreach ( $payments as $payment ) {
			$data[] = array(
				'id' => $payment->ID,
				'total' => $payment->total,
				'currency' => $payment->currency,
				'billing_email' => $payment->email,
				'amount_id' => $payment->get_meta( '_edd_ezdefi_amount_id' ),
				'token' => $payment->get_meta( '_edd_ezdefi_currency' ),
				'date_created' => $payment->date
			);
		}

		$table_name = $wpdb->prefix . 'edd_ezdefi_exception';

		$exception = $wpdb->get_results( "SELECT * FROM $table_name", ARRAY_A );

		wp_enqueue_script( 'edd_ezdefi_blockui' );
		wp_enqueue_style( 'edd_ezdefi_select2' );
		wp_enqueue_script( 'edd_ezdefi_select2' );
		wp_enqueue_style( 'edd_ezdefi_assign' );
		wp_enqueue_script( 'edd_ezdefi_assign' );
		wp_localize_script( 'edd_ezdefi_assign', 'edd_ezdefi_data',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'orders' => $data
			)
		);

		ob_start(); ?>
		<h2>Manage ezDeFi Exception</h2>
		<table class="widefat" id="edd-ezdefi-order-assign">
			<thead>
                <th>#</th>
				<th><?php _e( 'Received Amount', 'edd-ezdefi' ); ?></th>
				<th><?php _e( 'Currency', 'woocommerce-gateway-ezdefi' ); ?></th>
				<th><?php _e( 'Received At', 'edd-ezdefi' ); ?></th>
				<th><?php _e( 'Assign To', 'edd-ezdefi' ); ?></th>
				<th></th>
			</thead>
			<tbody>
                <?php if( ! empty( $exception ) ) : ?>
                    <?php foreach($exception as $i => $e) : ?>
                        <tr>
                            <td><?php echo $i + 1; ?></td>
                            <td>
                                <?php echo $e['amount_id']; ?>
                                <input type="hidden" value="<?php echo $e['amount_id']; ?>" id="amount-id">
                            </td>
                            <td>
                                <?php echo $e['currency']; ?>
                                <input type="hidden" value="<?php echo $e['currency']; ?>" id="currency">
                            </td>
                            <td><?php echo $e['created_at']; ?></td>
                            <td class="order-select-column">
                                <select name="" class="order-select">
                                	<?php if( $e['order_id'] ) : ?>
                                        <option value="<?php echo $e['order_id']; ?>" selected></option>
                                    <?php endif; ?>
									<?php echo $e['order_id']; ?>
                                </select>
                            </td>
                            <td>
                                <button class="button button-primary assignBtn">Assign</button>
                                <button class="button removeBtn">Remove</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td>Empty</td>
                    </tr>
                <?php endif; ?>
			</tbody>
		</table>
		<?php echo ob_get_clean();
	}
}

new EDD_Ezdefi_Admin();