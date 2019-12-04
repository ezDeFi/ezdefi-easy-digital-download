<?php

defined( 'ABSPATH' ) or exit;

class EDD_Ezdefi_Admin_Settings
{
	/**
     * EDD_Ezdefi_Admin_Settings constructor.
     */
	public function __construct() {
		require_once edd_ezdefi()->plugin_path() . '/includes/admin/register-settings.php';

		add_filter( 'edd_settings_sections_gateways', array( $this, 'register_settings_section' ) );
		add_filter( 'edd_settings_gateways', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'edd_ezdefi_currency_section' ) );
	}

	/**
     * Ezdefi settings section callback
     */
	public function register_settings_section($sections)
	{
		$sections['ezdefi'] = __( 'EZDefi', 'edd-ezdefi' );

		return $sections;
	}

	/**
     * Ezdefi settings callback
     */
	public function register_settings($gateway_settings)
	{
		$ezdefi_settings = array(
			'ezdefi_settings' => array(
				'id'   => 'ezdefi_settings',
				'name' => '<strong>' . __( 'EZDefi Settings', 'edd-ezdefi' ) . '</strong>',
				'type' => 'header',
			),
			'ezdefi_api_url' => array(
				'id' => 'ezdefi_api_url',
				'name' => __( 'Gateway API Url', 'edd-ezdefi' ),
				'desc' => __( 'Gateway API Url Description', 'edd-ezdefi' ),
				'type' => 'text',
				'size' => 'regular',
				'class' => 'ezdefi_api_url'
			),
			'ezdefi_api_key' => array(
				'id' => 'ezdefi_api_key',
				'name' => __( 'API Key', 'edd-ezdefi' ),
				'desc' => __( 'API Key Description', 'edd-ezdefi' ),
				'type' => 'text',
				'size' => 'regular',
				'class' => 'ezdefi_api_key'
			),
			'ezdefi_method' => array(
                'id' => 'ezdefi_method',
				'name' => __( 'Payment Method', 'edd-ezdefi' ),
                'desc' => __( 'Description' ),
				'type' => 'ezdefi_method'
			),
			'ezdefi_acceptable_variation' => array(
                'id' => 'ezdefi_acceptable_variation',
				'name' => __( 'Acceptable price variation', 'edd-ezdefi' ),
				'type' => 'number',
				'step' => '0.000001',
				'desc' => __( 'Description' ),
				'default' => 0.01,
                'class' => 'acceptable_variation'
			)
		);

		$gateway_settings['ezdefi'] = $ezdefi_settings;

		return $gateway_settings;
	}

	/**
     * EZDefi currency settings section callback
     */
	public function edd_ezdefi_currency_section() {
		add_settings_field(
			'edd_settings[ezdefi_currency]',
			__( 'Select currency', 'edd-ezdefi' ),
			array( $this, 'edd_ezdefi_currency_table' ),
			'edd_settings_gateways_ezdefi',
			'edd_settings_gateways_ezdefi'
		);
	}

	/**
     * EZDefi currency settings callback
     */
	public function edd_ezdefi_currency_table()
	{
		wp_enqueue_script( 'edd_ezdefi_jquery_tiptip' );
		wp_enqueue_style( 'edd_ezdefi_select2' );
		wp_enqueue_script( 'edd_ezdefi_select2' );
		wp_enqueue_script( 'edd_ezdefi_jquery_validation' );
		wp_enqueue_style( 'edd_ezdefi_admin' );
		wp_enqueue_script( 'edd_ezdefi_admin' );
		ob_start(); ?>
		<table id="ezdefi-currency-table" class="ezdefi-currency-table widefat striped">
			<thead>
			<tr>
				<th scope="col" class="sortable-zone"><span class="dashicons dashicons-editor-help help-tip" title="Drag and drop to re-order currency"></span></th>
				<th scope="col" class="logo"></th>
				<th scope="col" class="name"><?php _e( 'Currency', 'edd-ezdefi' ); ?></th>
				<th scope="col" class="discount"><?php _e( 'Discount', 'edd-ezdefi' ); ?></th>
				<th scope="col" class="lifetime"><?php _e( 'Payment Lifetime', 'edd-ezdefi' ); ?></th>
				<th scope="col" class="wallet"><?php _e( 'Wallet Address', 'edd-ezdefi' ); ?></th>
				<th scope="col" class="distance"><?php _e( 'Block Confirmation', 'edd-ezdefi' ); ?></th>
                <th scope="col" class="decimal"><?php _e( 'Decimal', 'edd-ezdefi' ); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php $currencies = edd_get_option( 'ezdefi_currency' );  ?>
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
							<input class="currency-id" type="hidden" value="<?php echo (isset($c['id'])) ? $c['id'] : ''; ?>" name="edd_settings[ezdefi_currency][<?php echo $index; ?>][id]">
							<input class="currency-symbol" type="hidden" value="<?php echo (isset($c['symbol'])) ? $c['symbol'] : ''; ?>" name="edd_settings[ezdefi_currency][<?php echo $index; ?>][symbol]">
							<input class="currency-name" type="hidden" value="<?php echo (isset($c['name'])) ? $c['name'] : ''; ?>" name="edd_settings[ezdefi_currency][<?php echo $index; ?>][name]">
							<input class="currency-logo" type="hidden" value="<?php echo (isset($c['logo'])) ? $c['logo'] : ''; ?>" name="edd_settings[ezdefi_currency][<?php echo $index; ?>][logo]">
							<input class="currency-desc" type="hidden" value="<?php echo (isset($c['desc'])) ? $c['desc'] : ''; ?>" name="edd_settings[ezdefi_currency][<?php echo $index; ?>][desc]">
                            <input class="currency-chain" type="hidden" value="<?php echo ( isset( $c['chain'] ) ) ? $c['chain'] : ''; ?>" name="edd_settings[ezdefi_currency][<?php echo $index; ?>][chain]">
							<div class="view">
								<span><?php echo (isset($c['name'])) ? $c['name'] : ''; ?></span>
								<div class="actions">
									<a href="" class="editBtn"><?php _e( 'Edit', 'edd-ezdefi' ); ?></a>
									|
									<a href="" class="deleteBtn"><?php _e( 'Delete', 'edd-ezdefi' ); ?></a>
								</div>
							</div>
							<div class="edit">
								<select name="edd_settings[ezdefi_currency][<?php echo $index; ?>][select]" class="select-select2">
									<option selected value="<?php echo (isset($c['name'])) ? $c['name'] : ''; ?>"><?php echo (isset($c['name'])) ? $c['name'] : ''; ?></option>
								</select>
								<div class="actions">
									<a href="" class="cancelBtn"><?php _e( 'Cancel', 'edd-ezdefi' ); ?></a>
								</div>
							</div>
						</td>
						<td class="discount">
							<div class="view">
								<?php echo (isset($c['discount'])) ? $c['discount'] : ''; ?><span> %</span>
							</div>
							<div class="edit">
								<input type="number" class="small-text" name="edd_settings[ezdefi_currency][<?php echo $index; ?>][discount]" value="<?php echo (isset($c['discount'])) ? $c['discount'] : ''; ?>"><span> %</span>
							</div>
						</td>
						<td class="lifetime">
							<div class="view">
								<?php echo (isset($c['lifetime'])) ? $c['lifetime'] : ''; ?>
							</div>
							<div class="edit">
								<input type="number" class="small-text" name="edd_settings[ezdefi_currency][<?php echo $index; ?>][lifetime]" value="<?php echo (isset($c['lifetime'])) ? $c['lifetime'] : ''; ?>">
							</div>
						</td>
						<td class="wallet">
							<div class="view">
								<?php echo (isset($c['wallet'])) ? $c['wallet'] : ''; ?>
							</div>
							<div class="edit">
								<input class="currency-wallet" type="text" name="edd_settings[ezdefi_currency][<?php echo $index; ?>][wallet]" value="<?php echo (isset($c['wallet'])) ? $c['wallet'] : ''; ?>">
							</div>
						</td>
						<td class="distance">
							<div class="view">
								<?php echo (isset($c['distance'])) ? $c['distance'] : ''; ?>
							</div>
							<div class="edit">
								<input type="number" class="small-text" name="edd_settings[ezdefi_currency][<?php echo $index; ?>][distance]" value="<?php echo (isset($c['distance'])) ? $c['distance'] : ''; ?>">
							</div>
						</td>
                        <td class="decimal">
                            <div class="view">
								<?php echo isset( $c['decimal'] ) ? $c['decimal'] : '' ;?>
                            </div>
                            <div class="edit">
                                <input type="number" class="small-text currency-decimal" name="edd_settings[ezdefi_currency][<?php echo $index; ?>][decimal]" value="<?php echo isset( $c['decimal'] ) ? $c['decimal'] : '' ;?>">
                            </div>
                        </td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr class="editing">
					<td class="sortable-handle"><span class="dashicons dashicons-menu"></span></td>
					<td class="logo">
						<img src="https://s2.coinmarketcap.com/static/img/coins/64x64/2714.png" class="ezdefi-currency-logo" alt="">
					</td>
					<td class="name">
						<input class="currency-id" type="hidden" value="" name="edd_settings[ezdefi_currency][0][id]">
						<input class="currency-symbol" type="hidden" value="nusd" name="edd_settings[ezdefi_currency][0][symbol]">
						<input class="currency-name" type="hidden" value="nusd" name="edd_settings[ezdefi_currency][0][name]">
						<input class="currency-logo" type="hidden" value="https://s2.coinmarketcap.com/static/img/coins/64x64/2714.png" name="edd_settings[ezdefi_currency][0][logo]">
						<input class="currency-desc" type="hidden" value="NewSD - Stablecoin token for payment" name="edd_settings[ezdefi_currency][0][desc]">
                        <input class="currency-chain" type="hidden" value="eth" name="edd_settings[ezdefi_currency][0][chain]">
						<div class="view">
							<span>ntf</span>
							<div class="actions">
								<a href="" class="editBtn" data-edit-btn><?php _e( 'Edit', 'edd-ezdefi' ); ?></a>
								|
								<a href="" class="deleteBtn" data-delete-btn><?php _e( 'Delete', 'edd-ezdefi' ); ?></a>
							</div>
						</div>
						<div class="edit">
							<select name="edd_settings[ezdefi_currency][0][select]" class="select-select2">
								<option value="nusd">nusd</option>
							</select>
							<div class="actions">
								<a href="" class="cancelBtn" data-cancel-btn><?php _e( 'Cancel', 'edd-ezdefi' ); ?></a>
							</div>
						</div>
					</td>
					<td class="discount">
						<div class="view">
						</div>
						<div class="edit">
							<input type="number" class="small-text" name="edd_settings[ezdefi_currency][0][discount]" value=""><span> %</span>
						</div>
					</td>
					<td class="lifetime">
						<div class="view">
						</div>
						<div class="edit">
							<input type="number" class="small-text" name="edd_settings[ezdefi_currency][0][lifetime]" value="">
						</div>
					</td>
					<td class="wallet">
						<div class="view">
						</div>
						<div class="edit">
							<input type="text" class="currency-wallet" name="edd_settings[ezdefi_currency][0][wallet]" value="">
						</div>
					</td>
					<td class="distance">
						<div class="view">
						</div>
						<div class="edit">
							<input type="number" class="small-text" name="edd_settings[ezdefi_currency][0][distance]" value="">
						</div>
					</td>
                    <td class="decimal">
                        <div class="view">
                        </div>
                        <div class="edit">
                            <input type="number" class="small-text" name="edd_settings[ezdefi_currency][0][decimal]" value="">
                        </div>
                    </td>
				</tr>
                <tr class="editing">
                    <td class="sortable-handle"><span class="dashicons dashicons-menu"></span></td>
                    <td class="logo">
                        <img src="https://s2.coinmarketcap.com/static/img/coins/64x64/2714.png" class="ezdefi-currency-logo" alt="">
                    </td>
                    <td class="name">
                        <input class="currency-id" type="hidden" value="" name="edd_settings[ezdefi_currency][1][id]">
                        <input class="currency-symbol" type="hidden" value="ntf" name="edd_settings[ezdefi_currency][1][symbol]">
                        <input class="currency-name" type="hidden" value="ntf" name="edd_settings[ezdefi_currency][1][name]">
                        <input class="currency-logo" type="hidden" value="https://s2.coinmarketcap.com/static/img/coins/64x64/2714.png" name="edd_settings[ezdefi_currency][1][logo]">
                        <input class="currency-desc" type="hidden" value="" name="edd_settings[ezdefi_currency][1][desc]">
                        <input class="currency-chain" type="hidden" value="eth" name="edd_settings[ezdefi_currency][1][chain]">
                        <div class="view">
                            <span>ntf</span>
                            <div class="actions">
                                <a href="" class="editBtn" data-edit-btn><?php _e( 'Edit', 'edd-ezdefi' ); ?></a>
                                |
                                <a href="" class="deleteBtn" data-delete-btn><?php _e( 'Delete', 'edd-ezdefi' ); ?></a>
                            </div>
                        </div>
                        <div class="edit">
                            <select name="edd_settings[ezdefi_currency][1][select]" class="select-select2">
                                <option value="ntf">ntf</option>
                            </select>
                            <div class="actions">
                                <a href="" class="cancelBtn" data-cancel-btn><?php _e( 'Cancel', 'edd-ezdefi' ); ?></a>
                            </div>
                        </div>
                    </td>
                    <td class="discount">
                        <div class="view">
                        </div>
                        <div class="edit">
                            <input type="number" class="small-text" name="edd_settings[ezdefi_currency][1][discount]" value=""><span> %</span>
                        </div>
                    </td>
                    <td class="lifetime">
                        <div class="view">
                        </div>
                        <div class="edit">
                            <input type="number" class="small-text" name="edd_settings[ezdefi_currency][1][lifetime]" value="">
                        </div>
                    </td>
                    <td class="wallet">
                        <div class="view">
                        </div>
                        <div class="edit">
                            <input type="text" class="currency-wallet" name="edd_settings[ezdefi_currency][1][wallet]" value="">
                        </div>
                    </td>
                    <td class="distance">
                        <div class="view">
                        </div>
                        <div class="edit">
                            <input type="number" class="small-text" name="edd_settings[ezdefi_currency][1][distance]" value="">
                        </div>
                    </td>
                    <td class="decimal">
                        <div class="view">
                        </div>
                        <div class="edit">
                            <input type="number" class="small-text" name="edd_settings[ezdefi_currency][1][decimal]" value="">
                        </div>
                    </td>
                </tr>
			<?php endif; ?>
			</tbody>
            <tfoot>
                <tr>
                    <td colspan="8">
                        <a href="" class="addBtn button button-secondary">
                            <?php echo __( 'Add Currency', 'woocommerce-gateway-ezdefi' ); ?>
                        </a>
                    </td>
                </tr>
            </tfoot>
		</table>
		<?php echo ob_get_clean();
	}
}

new EDD_Ezdefi_Admin_Settings();