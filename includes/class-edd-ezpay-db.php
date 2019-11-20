<?php

defined( 'ABSPATH' ) or exit;

class EDD_Ezpay_Db
{
	public function generate_amount_id( $price, $currency )
	{
		$amount_decimal = $this->get_amount_decimals();

		$amount_ids = $this->get_amount_ids( $price, $amount_decimal, $currency );

		$one_unit = 1 / pow(10, $amount_decimal );

		if( empty( $amount_ids ) ) {
			$amount_id = $price;
			return $this->save_amount_id( $price, $amount_id, $amount_decimal, $currency );
		}

		$valid_index = array_search( '1', array_column( $amount_ids, 'amount_valid' ) );

		if( $valid_index !== false ) {
			return floatval( $amount_ids[$valid_index]['amount_id'] );
		}

		if( count( $amount_ids ) === 1 ) {
			$amount_id = $price + $one_unit;
			return $this->save_amount_id( $price, $amount_id, $amount_decimal, $currency );
		}

		$counts = array_count_values(array_column($amount_ids, 'amount_abs'));

		$abs = null;

		foreach( $counts as $amount_abs => $count ) {
			if( floatval($amount_abs) > 0 && $count < 2 ) {
				$abs = $amount_abs;
				break;
			}
		}

		if( ! $abs ) {
			$id = end($amount_ids)['amount_id'] + $one_unit;
			return $this->save_amount_id( $price, $id, $amount_decimal, $currency );
		}

		$index = array_search( $abs, array_column( $amount_ids, 'amount_abs' ) );

		$amount_id = $amount_ids[$index];

		if( $amount_id['amount_id'] > $amount_id['price'] ) {
			$id = $amount_id['price'] - $abs;
			return $this->save_amount_id( $price, $id, $amount_decimal, $currency );
		} else {
			$id = $amount_id['price'] + $abs;
			return $this->save_amount_id( $price, $id, $amount_decimal, $currency );
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
			$wpdb->prepare( "
			SELECT price, amount_id, amount_decimal, amount_valid, currency, ABS(amount_id - price) as amount_abs
			FROM $table_name 
			WHERE price = %f 
			AND amount_decimal = %d 
			AND currency = %s
			ORDER BY amount_abs
			", $price, $amount_decimal, $currency
			),
			ARRAY_A
		);

		return $amount_ids;
	}

	protected function save_amount_id( $price, $amount_id, $amount_decimal, $currency )
	{
		global $wpdb;

		$acceptable_variation = $this->get_acceptable_variation();

		$variation_percent = $acceptable_variation / 100;
		$min = floatval( $price - ( $price * $variation_percent ) );
		$max = floatval( $price + ( $price * $variation_percent ) );

		if( ( $amount_id < $min ) || ( $amount_id > $max ) ) {
			return false;
		}

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

		return floatval( $amount_id );
	}

	public function set_amount_id_invalid( $amount_id, $currency )
	{
		global $wpdb;

		return $wpdb->update(
			$this->get_amount_table_name(),
			array(
				'amount_valid' => 0
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