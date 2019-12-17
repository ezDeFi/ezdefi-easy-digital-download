<?php

defined( 'ABSPATH' ) or exit;

class EDD_Ezdefi_Db
{
	public function generate_amount_id( $price, $currency_data )
	{
		global $wpdb;

		$decimal = $currency_data['decimal'];
		$symbol = $currency_data['symbol'];
		$life_time = $currency_data['lifetime'];

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

	public function delete_amount_id_exception($amount_id, $currency, $order_id)
	{
		global $wpdb;

		$table_name = $wpdb->prefix . 'edd_ezdefi_exception';

		if( is_null( $order_id ) ) {
			return $wpdb->query( "DELETE FROM $table_name WHERE amount_id = $amount_id AND currency = '$currency' AND order_id IS NULL" );
		}

		return $wpdb->query( "DELETE FROM $table_name WHERE amount_id = $amount_id AND currency = '$currency' AND order_id = $order_id" );
	}

	public function add_exception( $data )
	{
		global $wpdb;

		$keys = array();
		$values = array();

		foreach ( $data as $key => $value ) {
			$keys[] = "$key";
			$values[] = "'$value'";
		}

		$exception_table = $wpdb->prefix . 'edd_ezdefi_exception';

		$query = "INSERT INTO $exception_table (" . implode( ',', $keys ) . ") VALUES (" . implode( ',', $values ) . ")";

		return $wpdb->query($query);
	}

	public function get_exception( $params = array(), $offset = 0, $per_page = 15 )
	{
		global $wpdb;

		$exception_table = $wpdb->prefix . 'edd_ezdefi_exception';

		$meta_table = $wpdb->prefix . 'postmeta';

		$default = array(
			'amount_id' => '',
			'currency' => '',
			'order_id' => '',
			'email' => '',
			'payment_method' => '',
			'status' => ''
		);

		$params = array_merge( $default, $params );

		$query = "SELECT t1.*, t2.billing_email FROM $exception_table t1 LEFT JOIN ( SELECT post_id as order_id, meta_value as billing_email FROM $meta_table WHERE `meta_key` = '_edd_payment_user_email' ) t2 ON t1.order_id = t2.order_id";

		$sql = array();

		foreach( $params as $column => $param ) {
			if( ! empty( $param ) && in_array( $column, array_keys( $default ) ) ) {
				$sql[] = " $column = '$param' ";
			}
		}

		if( ! empty( $sql ) ) {
			$query .= ' WHERE ' . implode( $sql, 'AND' );
		}

		$query .= " ORDER BY id DESC LIMIT $offset, $per_page";

		return $wpdb->get_results( $query );
	}

	public function get_exception_total()
	{
		global $wpdb;

		$exception_table = $wpdb->prefix . 'edd_ezdefi_exception';

		$query = "SELECT COUNT(*) as total FROM $exception_table";

		return $wpdb->get_results( $query );
	}

	public function update_exception( $wheres = array(), $data = array() )
	{
		global $wpdb;

		$exception_table = $wpdb->prefix . 'edd_ezdefi_exception';

		if( empty( $data ) || empty( $wheres ) ) {
			return;
		}

		$query = "UPDATE $exception_table SET";
		$comma = " ";
		foreach ( $data as $column => $value ) {
			$query .= $comma . $column . " = '" . $value . "'";
			$comma = ", ";
		}
		$conditions = array();
		foreach( $wheres as $column => $value ) {
			if( ! empty( $value ) ) {
				$type = gettype( $value );
				switch ($type) {
					case 'double' :
						$conditions[] = " $column = $value ";
						break;
					case 'integer' :
						$conditions[] = " $column = $value ";
						break;
					case 'string' :
						$conditions[] = " $column = '$value' ";
						break;
					case 'NULL' :
						$conditions[] = " $column IS NULL ";
						break;
				}
			}
		}
		if( ! empty( $conditions ) ) {
			$query .= ' WHERE ' . implode( $conditions, 'AND' );
		}

		return $wpdb->query( $query );
	}
}