<?php

function edd_ezpay_method_callback( $args )
{
	$field_key = 'edd_settings[' . edd_sanitize_key( $args['id'] ) . ']';
    $ezpay_method = edd_get_option( 'ezpay_method' );
	ob_start(); ?>

    <input name="<?php echo $field_key; ?>[amount_id]" id="<?php echo $field_key; ?>[amount_id]" type="checkbox" class="" value="1" <?php echo ( isset( $ezpay_method['amount_id'] ) && $ezpay_method['amount_id'] === '1' ) ? 'checked' : '' ;?>>
    <label for="<?php echo $field_key; ?>[amount_id]">Simple method</label>
    <p class="description">Allow client to pay without using ezPay wallet</p>
    <p></p>
    <input name="<?php echo $field_key; ?>[ezpay_wallet]" id="<?php echo $field_key; ?>[ezpay_wallet]" type="checkbox" class="" value="1" <?php echo ( isset( $ezpay_method['ezpay_wallet'] ) && $ezpay_method['ezpay_wallet'] === '1' ) ? 'checked' : '' ;?>>
    <label for="<?php echo $field_key; ?>[ezpay_wallet]">Use ezPay wallet</label>
    <p class="description">Allow client to pay using ezPay wallet</p>

    <?php echo ob_get_clean();
}