<?php

defined( 'ABSPATH' ) or exit;

class EDD_Ezdefi_Db
{
    public function get_public_key()
    {
        return edd_get_option( 'ezdefi_public_key' );
    }

    public function delete_exception( $exception_id )
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'edd_ezdefi_exception';

        $query = "DELETE FROM $table_name WHERE id = $exception_id";

        return $wpdb->query( $query );
    }

    public function delete_exceptions( $wheres = array() )
    {
        global $wpdb;

        $exception_table = $wpdb->prefix . 'edd_ezdefi_exception';

        if( empty( $wheres ) ) {
            return;
        }

        $query = "DELETE FROM $exception_table";

        $conditions = array();

        foreach( $wheres as $column => $value ) {
            $type = gettype( $value );
            switch ($type) {
                case 'integer' :
                    $conditions[] = " $column = $value ";
                    break;
                case 'NULL' :
                    $conditions[] = " $column IS NULL ";
                    break;
                default :
                    $conditions[] = " $column = '$value' ";
                    break;
            }
        }

        if( ! empty( $conditions ) ) {
            $query .= ' WHERE ' . implode( $conditions, 'AND' );
        }

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

	public function get_exceptions( $params = array(), $offset = 0, $per_page = 15 )
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
			'status' => '',
            'confirmed' => '',
		);

		$params = array_merge( $default, $params );

		$query = "SELECT SQL_CALC_FOUND_ROWS t1.*, t2.billing_email FROM $exception_table t1 LEFT JOIN ( SELECT post_id as order_id, meta_value as billing_email FROM $meta_table WHERE `meta_key` = '_edd_payment_user_email' ) t2 ON t1.order_id = t2.order_id";

		$sql = array();

        foreach( $params as $column => $param ) {
            if( $column === 'type' ) {
                switch ( $params['type'] ) {
                    case 'pending' :
                        $sql[] = " t1.confirmed = 0 ";
                        $sql[] = " t1.explorer_url IS NOT NULL ";
                        break;
                    case 'confirmed' :
                        $sql[] = " t1.confirmed = 1 ";
                        break;
                    case 'archived' :
                        $sql[] = " t1.confirmed = 0 ";
                        $sql[] = " t1.explorer_url IS NULL ";
                        $sql[] = " t1.is_show = 1 ";
                        break;
                }
            }  elseif ( ! empty( $param ) && in_array( $column, array_keys( $default ) ) ) {
                switch ( $column ) {
                    case 'amount_id' :
                        $sql[] = " t1.amount_id RLIKE '^$param' ";
                        break;
                    case 'email' :
                        $sql[] = " t2.billing_email = '$param' ";
                        break;
                    default :
                        $sql[] = " t1.$column = '$param' ";
                        break;
                }
            }
        }

		if( ! empty( $sql ) ) {
			$query .= ' WHERE ' . implode( $sql, 'AND' );
		}

		$query .= " ORDER BY id DESC LIMIT $offset, $per_page";

		$data = $wpdb->get_results( $query );

		$total = $wpdb->get_var( "SELECT FOUND_ROWS() as total;" );

		return array(
			'data' => $data,
			'total' => $total
		);
	}

	public function update_exceptions( $wheres = array(), $data = array(), $limit = null )
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
				$query .= $comma . $column . " IS NULL";
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
                        $conditions[] = " $column = '$value' ";
                        break;
                }
            }
        }

		if( ! empty( $conditions ) ) {
			$query .= ' WHERE ' . implode( $conditions, 'AND' );
		}

        if( is_numeric( $limit ) ) {
            $query .= " ORDER BY id DESC LIMIT $limit";
        }

		return $wpdb->query( $query );
	}
}