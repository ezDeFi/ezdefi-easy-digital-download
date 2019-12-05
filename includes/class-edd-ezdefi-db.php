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

	public function get_currency_option( $symbol )
	{
		$currency_data = edd_get_option( 'ezdefi_currency' );

		$index = array_search( $symbol, array_column( $currency_data, 'symbol' ) );

		if( $index === false ) {
			return null;
		}

		return $currency_data[$index];
	}

	public function get_acceptable_variation()
	{
		return edd_get_option( 'ezdefi_acceptable_variation' );
	}

	public function delete_amount_id_exception($amount_id, $currency)
	{
		global $wpdb;

		$table_name = $wpdb->prefix . 'edd_ezdefi_exception';

		$wpdb->delete( $table_name, array( 'amount_id' => $amount_id, 'currency' => $currency ) );
	}

	public function add_uoid_to_exception($amount_id, $currency, $uoid)
	{
		global $wpdb;

		$table_name = $wpdb->prefix . 'edd_ezdefi_exception';

		$wpdb->update(
			$table_name,
			array( 'order_id' => $uoid ),
			array(
				'amount_id' => $amount_id,
				'currency' => $currency
			)
		);
	}
}