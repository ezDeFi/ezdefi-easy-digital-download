<?php

defined( 'ABSPATH' ) or exit;

class EDD_Ezpay_Db
{
	public function generate_amount_id( $price, $currency )
	{
		$amount_decimal = $this->get_amount_decimals();
		$acceptable_variation = $this->get_acceptable_variation();

		$variation_percent = $acceptable_variation / 100;
		$min = floatval( $price - ( $price * $variation_percent ) );
		$max = floatval( $price + ( $price * $variation_percent ) );

		$amount_ids = $this->get_amount_ids( $price, $amount_decimal, $currency );

		$one_unit = 1 / pow(10, $amount_decimal );

		if( empty( $amount_ids ) ) {
			$amount_id = $price;
			$this->save_amount_id( $price, $amount_id, $amount_decimal, $currency );
			return floatval( $amount_id );
		}

		$valid_index = array_search( '1', array_column( $amount_ids, 'amount_valid' ) );

		if( $valid_index !== false ) {
			$amount_id = $amount_ids[$valid_index]['amount_id'];
			return floatval( $amount_id );
		}

		$current_max = max( array_column( $amount_ids, 'amount_id' ) );

		$amount_id = $current_max + $one_unit;

		if( $amount_id <= $max ) {
			$this->save_amount_id( $price, $amount_id, $amount_decimal, $currency );
			return floatval( $amount_id );
		}

		$current_min = min( array_column( $amount_ids, 'amount_id' ) );

		$amount_id = $current_min - $one_unit;

		if( $amount_id >= $min ) {
			$this->save_amount_id( $price, $amount_id, $amount_decimal, $currency );
			return floatval( $amount_id );
		}

		return false;
	}

	protected function get_amount_decimals()
	{
		return edd_get_option( 'ezpay_amount_decimals' );
	}

	protected function get_acceptable_variation()
	{
		return edd_get_option( 'ezpay_acceptable_variation' );
	}

	protected function get_amount_ids( $price, $amount_decimal, $currency )
	{
		global $wpdb;

		$table_name = $this->get_amount_table_name();

		$amount_ids = $wpdb->get_results(
			"SELECT * FROM $table_name WHERE price = $price AND amount_decimal = $amount_decimal AND currency = '" . $currency . "'",
			ARRAY_A
		);

		return $amount_ids;
	}

	protected function save_amount_id( $price, $amount_id, $amount_decimal, $currency )
	{
		global $wpdb;

		$result = $wpdb->insert(
			$this->get_amount_table_name(),
			array(
				'price' => $price,
				'amount_id' => $amount_id,
				'amount_decimal' => $amount_decimal,
				'amount_valid' => 1,
				'currency' => $currency
			)
		);

		if( !$result ) {
			return false;
		}
	}

	public function set_amount_id_invalid( $amount_id, $currency )
	{
		global $wpdb;

		return $wpdb->update(
			$this->get_amount_table_name(),
			array(
				'amount_valid' => 0,
				'date_used' => current_time( 'mysql', true ),
			),
			array(
				'amount_id' => $amount_id,
				'currency' => $currency
			)
		);
	}

	public function get_amount_table_name()
	{
		global $wpdb;

		return $wpdb->prefix . 'edd_ezpay_amount';
	}
}