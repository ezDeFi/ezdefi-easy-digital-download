<?php

function edd_amount_next_run_callback( $args )
{
	$field_key = 'edd_settings[' . edd_sanitize_key( $args['id'] ) . ']';

	$cron_array = _get_cron_array();
	$next_run = '';
	if( edd_get_option( 'ezpay_method' ) != 'ezpay_wallet' ) {
		foreach ( $cron_array as $time => $cron ) {
			if ( in_array( 'edd_ezpay_clear_amount_events', array_keys( $cron ) ) ) {
				$next_run = $time;
			}
		}
		$next_run = get_date_from_gmt( date( 'Y-m-d H:i:s', $next_run ), 'Y-m-d H:i:s' );
	}

	ob_start();
	?>
	<input name="<?php echo $field_key; ?>" id="now" type="radio" class="" value="now()">&nbsp;
	<label for="now">Now</label></br>
	<input name="<?php echo $field_key; ?>" id="tomorrow" type="radio" class="" value="tomorrow()">&nbsp;
	<label for="tomorrow">Tomorrow</label></br>
	<input name="<?php echo $field_key; ?>" id="custom" type="radio" class="" value="custom" checked>&nbsp;
	<label for="custom">At</label>&nbsp;
	<input type="text" name="next_run_custom_date" id="next_run_custom_date" value="<?php echo $next_run; ?>">&nbsp;Format: YYYY-MM-DD HH:MM:SS
	<?php

	echo ob_get_clean();
}