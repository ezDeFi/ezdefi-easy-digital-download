<?php

defined( 'ABSPATH' ) or exit;

class EDD_Ezdefi_Db
{
	public function generate_amount_id( $price, $currency_data )
	{
		global $wpdb;

		$decimal = $currency_data['decimal'];
		$life_time = $currency_data['lifetime'];
		$symbol = $currency_data['symbol'];

		$price = round( $price, $decimal );

		$wpdb->query(
			$wpdb->prepare("
				CALL edd_ezdefi_generate_amount_id(%s, %s, %d, %d, @amount_id)
			", $price, $symbol, $decimal, $life_time)
		);

		$result = $wpdb->get_row( "SELECT @amount_id", ARRAY_A );

		if( ! $result ) {
			return null;
		}

		$amount_id = floatval( $result['@amount_id'] );

		$acceptable_variation = $this->get_acceptable_variation();

		$variation_percent = $acceptable_variation / 100;

		$min = floatval( $price - ( $price * $variation_percent ) );
		$max = floatval( $price + ( $price * $variation_percent ) );

		if( ( $amount_id < $min ) || ( $amount_id > $max ) ) {
			return null;
		}

		return $amount_id;
	}

	public function get_acceptable_variation()
	{
		return edd_get_option( 'ezdefi_acceptable_variation' );
	}
}