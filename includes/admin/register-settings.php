<?php

function edd_ezdefi_method_callback( $args )
{
	$field_key = 'edd_settings[' . edd_sanitize_key( $args['id'] ) . ']';
    $ezdefi_method = edd_get_option( 'ezdefi_method' );
	ob_start(); ?>

    <input name="<?php echo $field_key; ?>[amount_id]" id="<?php echo $field_key; ?>[amount_id]" type="checkbox" class="ezdefi_amount_id_input" value="1" <?php echo ( isset( $ezdefi_method['amount_id'] ) && $ezdefi_method['amount_id'] === '1' ) ? 'checked' : '' ;?>>
    <label for="<?php echo $field_key; ?>[amount_id]"><?php _e( 'Pay with any crypto wallet', 'edd-ezdefi' ); ?></label>
    <p class="description"><?php _e( 'This method will adjust payment amount of each order by an acceptable number to help payment gateway identifying the uniqueness of that order.', 'edd-ezdefi' ); ?></p>
    <p></p>
    <input name="<?php echo $field_key; ?>[ezdefi_wallet]" id="<?php echo $field_key; ?>[ezdefi_wallet]" type="checkbox" class="ezdefi_exdefi_wallet_input" value="1" <?php echo ( isset( $ezdefi_method['ezdefi_wallet'] ) && $ezdefi_method['ezdefi_wallet'] === '1' ) ? 'checked' : '' ;?>>
    <label for="<?php echo $field_key; ?>[ezdefi_wallet]"><?php _e( 'Pay with ezDeFi wallet', 'edd-ezdefi' ); ?></label>
    <p class="description"><?php _e( 'This method is more powerful when amount uniqueness above method reaches allowable limit. Users just need to install ezDeFi wallet then import their private key to pay using qrCode.', 'edd-ezdefi' ); ?></p>

    <?php echo ob_get_clean();
}