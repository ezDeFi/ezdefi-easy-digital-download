<?php

class EDD_Ezpay_Cron
{
	protected $db;

	public function __construct() {
		$this->db = new EDD_Ezpay_Db();

		add_filter( 'cron_schedules', array( $this, 'add_schedules' ) );

		add_action( 'wp', array( $this, 'schedule_clear_amount_event' ) );

		add_action( 'edd_ezpay_clear_amount_events', array( $this, 'clear_amount_table' ) );
	}

	public function add_schedules( $schedules = array() ) {
		$schedules['weekly'] = array(
			'interval' => 604800,
			'display'  => __( 'Once Weekly', 'edd-ezpay' )
		);
		$schedules['monthly'] = array(
			'interval' => 2635200,
			'display'  => __( 'Monthly', 'edd-ezpay' ),
		);
		return $schedules;
	}

	public function schedule_clear_amount_event()
	{
		if( ! wp_next_scheduled( 'edd_ezpay_clear_amount_events' ) ) {
			wp_schedule_event( current_time( 'timestamp', true ), 'weekly', 'edd_ezpay_clear_amount_events' );
		}
	}

	public function clear_amount_table()
	{
		global $wpdb;
		$table_name = $this->db->get_amount_table_name();

		$wpdb->query( "DELETE FROM $table_name" );
	}

	public function unschedule_clear_amount_event()
	{
		$timestamp = wp_next_scheduled( 'edd_ezpay_clear_amount_events' );
		wp_unschedule_event( $timestamp, 'edd_ezpay_clear_amount_events' );
	}

	public function update_clear_amount_event( $next_run, $schedule )
	{
		$next_run = strtotime( $next_run );

		if( $next_run === false ) {
			$next_run = current_time( 'timestamp', true );
		} else {
			$next_run = get_gmt_from_date( date( 'Y-m-d H:i:s', $next_run ), 'U' );
		}

		return wp_schedule_event( $next_run, $schedule, 'edd_ezpay_clear_amount_events' );
	}
}

new EDD_Ezpay_Cron();