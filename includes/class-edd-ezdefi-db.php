<?php

defined( 'ABSPATH' ) or exit;

class EDD_Ezdefi_Db
{
    public function get_public_key()
    {
        return edd_get_option( 'ezdefi_public_key' );
    }

	public function delete_amount_id_exception($amount_id, $currency, $order_id)
	{
		global $wpdb;

		$table_name = $wpdb->prefix . 'edd_ezdefi_exception';

		if( is_null( $order_id ) ) {
			return $wpdb->query( "DELETE FROM $table_name WHERE amount_id = $amount_id AND currency = '$currency' AND order_id IS NULL LIMIT 1" );
		}

		return $wpdb->query( "DELETE FROM $table_name WHERE amount_id = $amount_id AND currency = '$currency' AND order_id = $order_id LIMIT 1" );
	}

	public function delete_exception_by_order_id($order_id)
	{
		global $wpdb;

		$table_name = $wpdb->prefix . 'edd_ezdefi_exception';

		$query = "DELETE FROM $table_name WHERE order_id = $order_id";

		return $wpdb->query( $query );
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

		$query = "SELECT SQL_CALC_FOUND_ROWS t1.*, t2.billing_email FROM $exception_table t1 LEFT JOIN ( SELECT post_id as order_id, meta_value as billing_email FROM $meta_table WHERE `meta_key` = '_edd_payment_user_email' ) t2 ON t1.order_id = t2.order_id";

		$sql = array();

		foreach( $params as $column => $param ) {
			if( ! empty( $param ) && in_array( $column, array_keys( $default ) ) && $column != 'amount_id' ) {
				$sql[] = ( $column === 'email' ) ? " t2.billing_email = '$param' " : " t1.$column = '$param' ";
			}
		}

		if( ! empty( $sql ) ) {
			$query .= ' WHERE ' . implode( $sql, 'AND' );
		}

		if( ! empty( $params['amount_id'] ) ) {
			$amount_id = $params['amount_id'];
			if( ! empty( $sql ) ) {
				$query .= " AND";
			} else {
				$query .= " WHERE";
			}
			$query .= " amount_id RLIKE '^$amount_id'";
		}

		$query .= " ORDER BY id DESC LIMIT $offset, $per_page";

		$data = $wpdb->get_results( $query );

		$total = $wpdb->get_var( "SELECT FOUND_ROWS() as total;" );

		return array(
			'data' => $data,
			'total' => $total
		);
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
			if( is_null( $value ) ) {
				$query .= $comma . $column . " = NULL";
			} else {
				$query .= $comma . $column . " = '" . $value . "'";
			}
			$comma = ", ";
		}
		$conditions = array();
		foreach( $wheres as $column => $value ) {
			if( ! empty( $value ) ) {
				$type = gettype( $value );
				switch ($type) {
					case 'integer' :
						$conditions[] = " $column = $value ";
						break;
					case 'NULL' :
						$conditions[] = " $column IS NULL ";
						break;
					default :
						$conditions[] = " $column LIKE '$value%' ";
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