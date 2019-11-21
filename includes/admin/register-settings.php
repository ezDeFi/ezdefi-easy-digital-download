<?php

function edd_ezdefi_method_callback( $args )
{
	$field_key = 'edd_settings[' . edd_sanitize_key( $args['id'] ) . ']';
    $ezdefi_method = edd_get_option( 'ezdefi_method' );
	ob_start(); ?>

    <input name="<?php echo $field_key; ?>[amount_id]" id="<?php echo $field_key; ?>[amount_id]" type="checkbox" class="" value="1" <?php echo ( isset( $ezdefi_method['amount_id'] ) && $ezdefi_method['amount_id'] === '1' ) ? 'checked' : '' ;?>>
    <label for="<?php echo $field_key; ?>[amount_id]">Simple method</label>
    <p class="description">Allow client to pay without using ezDeFi wallet</p>
    <p></p>
    <input name="<?php echo $field_key; ?>[ezdefi_wallet]" id="<?php echo $field_key; ?>[ezdefi_wallet]" type="checkbox" class="" value="1" <?php echo ( isset( $ezdefi_method['ezdefi_wallet'] ) && $ezdefi_method['ezdefi_wallet'] === '1' ) ? 'checked' : '' ;?>>
    <label for="<?php echo $field_key; ?>[ezdefi_wallet]">Use ezDeFi wallet</label>
    <p class="description">Allow client to pay using ezDeFi wallet</p>

    <?php echo ob_get_clean();
}