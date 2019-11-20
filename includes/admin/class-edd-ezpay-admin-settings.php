<?php

defined( 'ABSPATH' ) or exit;

class EDD_Ezpay_Admin_Settings
{
    protected $cron;

	/** EDD_Ezpay_Admin_Settings constructor. */
	public function __construct() {
		require_once edd_ezpay()->plugin_path() . '/includes/admin/register-settings.php';

		add_filter( 'edd_settings_sections_gateways', array( $this, 'register_settings_section' ) );
		add_filter( 'edd_settings_gateways', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'edd_ezpay_currency_section' ) );

		$this->cron = new EDD_Ezpay_Cron();

		add_action( 'init', array( $this, 'update_option' ) );
	}

	public function update_option()
    {
        if( ! isset( $_POST['option_page'] ) || $_POST['option_page'] != 'edd_settings' ) {
            return;
        }

	    $this->cron->unschedule_clear_amount_event();

	    if( $_POST['edd_settings']['ezpay_method'] === 'ezpay_wallet' ) {
		    return;
	    }

	    $schedule = $_POST['edd_settings']['ezpay_amount_clear_recurrence'];

	    return $this->cron->update_clear_amount_event( $schedule );
    }

	/** Ezpay settings section callback */
	public function register_settings_section($sections)
	{
		$sections['ezpay'] = __( 'EZPay', 'edd-ezpay' );

		return $sections;
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
				'size' => 'regular',
				'class' => 'ezpay_api_url'
			),
			'ezpay_api_key' => array(
				'id' => 'ezpay_api_key',
				'name' => __( 'API Key', 'edd-ezpay' ),
				'desc' => __( 'API Key Description', 'edd-ezpay' ),
				'type' => 'text',
				'size' => 'regular',
				'class' => 'ezpay_api_key'
			),
			'ezpay_method' => array(
                'id' => 'ezpay_method',
				'name' => __( 'Payment Method', 'edd-ezpay' ),
                'desc' => __( 'Description' ),
				'type' => 'ezpay_method'
			),
			'ezpay_acceptable_variation' => array(
                'id' => 'ezpay_acceptable_variation',
				'name' => __( 'Acceptable price variation', 'edd-ezpay' ),
				'type' => 'number',
				'step' => '0.000001',
				'desc' => __( 'Description' ),
				'default' => 0.01,
                'class' => 'acceptable_variation'
			),
			'ezpay_amount_decimals' => array(
                'id' => 'ezpay_amount_decimals',
				'name' => __( 'Decimals', 'edd-ezpay' ),
				'type' => 'number',
				'desc' => __( 'Description' ),
				'default' => 6,
                'class' => 'amount_decimals'
			),
			'ezpay_amount_clear_recurrence' => array(
                'id' => 'ezpay_amount_clear_recurrence',
				'name' => __( 'Amount clear recurrence', 'edd-ezpay' ),
				'type' => 'select',
				'desc' => __( 'Description' ),
				'options' => array(
					'daily' => 'Daily',
					'weekly' => 'Weekly',
					'monthly' => 'Monthly'
				),
                'class' => 'recurrence'
			),
		);

		$gateway_settings['ezpay'] = $ezpay_settings;

		return $gateway_settings;
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
		ob_start(); ?>
		<table id="ezpay-currency-table" class="ezpay-currency-table widefat striped">
			<thead>
			<tr>
				<th scope="col" class="sortable-zone"><span class="dashicons dashicons-editor-help help-tip" title="Drag and drop to re-order currency"></span></th>
				<th scope="col" class="logo"></th>
				<th scope="col" class="name"><?php _e( 'Currency', 'edd-ezpay' ); ?></th>
				<th scope="col" class="discount"><?php _e( 'Discount', 'edd-ezpay' ); ?></th>
				<th scope="col" class="lifetime"><?php _e( 'Payment Lifetime', 'edd-ezpay' ); ?></th>
				<th scope="col" class="wallet"><?php _e( 'Wallet Address', 'edd-ezpay' ); ?></th>
				<th scope="col" class="distance"><?php _e( 'Block Confirmation', 'edd-ezpay' ); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php $currencies = edd_get_option( 'ezpay_currency' );  ?>
			<?php if( $currencies && !empty( $currencies ) ) : ; ?>
				<?php foreach( $currencies as $index => $c ) : ?>
					<tr>
						<td class="sortable-handle"><span class="dashicons dashicons-menu"></span></td>
						<td class="logo">
							<?php if( isset( $c['logo'] ) ) : ?>
								<img src="<?php echo $c['logo']; ?>" class="logo-image" alt="">
							<?php endif; ?>
						</td>
						<td class="name">
							<input class="currency-id" type="hidden" value="<?php echo (isset($c['id'])) ? $c['id'] : ''; ?>" name="edd_settings[ezpay_currency][<?php echo $index; ?>][id]">
							<input class="currency-symbol" type="hidden" value="<?php echo (isset($c['symbol'])) ? $c['symbol'] : ''; ?>" name="edd_settings[ezpay_currency][<?php echo $index; ?>][symbol]">
							<input class="currency-name" type="hidden" value="<?php echo (isset($c['name'])) ? $c['name'] : ''; ?>" name="edd_settings[ezpay_currency][<?php echo $index; ?>][name]">
							<input class="currency-logo" type="hidden" value="<?php echo (isset($c['logo'])) ? $c['logo'] : ''; ?>" name="edd_settings[ezpay_currency][<?php echo $index; ?>][logo]">
							<input class="currency-desc" type="hidden" value="<?php echo (isset($c['desc'])) ? $c['desc'] : ''; ?>" name="edd_settings[ezpay_currency][<?php echo $index; ?>][desc]">
                            <input class="currency-chain" type="hidden" value="<?php echo ( isset( $c['chain'] ) ) ? $c['chain'] : ''; ?>" name="edd_settings[ezpay_currency][<?php echo $index; ?>][chain]">
							<div class="view">
								<span><?php echo (isset($c['name'])) ? $c['name'] : ''; ?></span>
								<div class="actions">
									<a href="" class="editBtn">Edit</a>
									|
									<a href="" class="deleteBtn">Delete</a>
								</div>
							</div>
							<div class="edit">
								<select name="edd_settings[ezpay_currency][<?php echo $index; ?>][select]" class="select-select2">
									<option selected value="<?php echo (isset($c['name'])) ? $c['name'] : ''; ?>"><?php echo (isset($c['name'])) ? $c['name'] : ''; ?></option>
								</select>
								<div class="actions">
									<a href="" class="cancelBtn">Cancel</a>
								</div>
							</div>
						</td>
						<td class="discount">
							<div class="view">
								<?php echo (isset($c['discount'])) ? $c['discount'] : ''; ?><span> %</span>
							</div>
							<div class="edit">
								<input type="number" class="small-text" name="edd_settings[ezpay_currency][<?php echo $index; ?>][discount]" value="<?php echo (isset($c['discount'])) ? $c['discount'] : ''; ?>"><span> %</span>
							</div>
						</td>
						<td class="lifetime">
							<div class="view">
								<?php echo (isset($c['lifetime'])) ? $c['lifetime'] : ''; ?>
							</div>
							<div class="edit">
								<input type="number" class="small-text" name="edd_settings[ezpay_currency][<?php echo $index; ?>][lifetime]" value="<?php echo (isset($c['lifetime'])) ? $c['lifetime'] : ''; ?>">
							</div>
						</td>
						<td class="wallet">
							<div class="view">
								<?php echo (isset($c['wallet'])) ? $c['wallet'] : ''; ?>
							</div>
							<div class="edit">
								<input class="currency-wallet" type="text" name="edd_settings[ezpay_currency][<?php echo $index; ?>][wallet]" value="<?php echo (isset($c['wallet'])) ? $c['wallet'] : ''; ?>">
							</div>
						</td>
						<td class="distance">
							<div class="view">
								<?php echo (isset($c['distance'])) ? $c['distance'] : ''; ?>
							</div>
							<div class="edit">
								<input type="number" class="small-text" name="edd_settings[ezpay_currency][<?php echo $index; ?>][distance]" value="<?php echo (isset($c['distance'])) ? $c['distance'] : ''; ?>">
							</div>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr class="editing">
					<td class="sortable-handle"><span class="dashicons dashicons-menu"></span></td>
					<td class="logo">
						<img src="https://s2.coinmarketcap.com/static/img/coins/64x64/2714.png" class="ezpay-currency-logo" alt="">
					</td>
					<td class="name">
						<input class="currency-id" type="hidden" value="" name="edd_settings[ezpay_currency][0][id]">
						<input class="currency-symbol" type="hidden" value="nusd" name="edd_settings[ezpay_currency][0][symbol]">
						<input class="currency-name" type="hidden" value="nusd" name="edd_settings[ezpay_currency][0][name]">
						<input class="currency-logo" type="hidden" value="https://s2.coinmarketcap.com/static/img/coins/64x64/2714.png" name="edd_settings[ezpay_currency][0][logo]">
						<input class="currency-desc" type="hidden" value="NewSD - Stablecoin token for payment" name="edd_settings[ezpay_currency][0][desc]">
						<div class="view">
							<span>ntf</span>
							<div class="actions">
								<a href="" class="editBtn" data-edit-btn>Edit</a>
								|
								<a href="" class="deleteBtn" data-delete-btn>Delete</a>
							</div>
						</div>
						<div class="edit">
							<select name="edd_settings[ezpay_currency][0][select]" class="select-select2">
								<option value="nusd">nusd</option>
							</select>
							<div class="actions">
								<a href="" class="cancelBtn" data-cancel-btn>Cancel</a>
							</div>
						</div>
					</td>
					<td class="discount">
						<div class="view">
						</div>
						<div class="edit">
							<input type="number" class="small-text" name="edd_settings[ezpay_currency][0][discount]" value=""><span> %</span>
						</div>
					</td>
					<td class="lifetime">
						<div class="view">
						</div>
						<div class="edit">
							<input type="number" class="small-text" name="edd_settings[ezpay_currency][0][lifetime]" value="">
						</div>
					</td>
					<td class="wallet">
						<div class="view">
						</div>
						<div class="edit">
							<input type="text" class="currency-wallet" name="edd_settings[ezpay_currency][0][wallet]" value="">
						</div>
					</td>
					<td class="distance">
						<div class="view">
						</div>
						<div class="edit">
							<input type="number" class="small-text" name="edd_settings[ezpay_currency][0][distance]" value="">
						</div>
					</td>
				</tr>
			<?php endif; ?>
			</tbody>
		</table>
		<p>
			<span class="button-secondary addBtn">Add Currency</span>
		</p>
		<?php echo ob_get_clean();
	}
}

new EDD_Ezpay_Admin_Settings();