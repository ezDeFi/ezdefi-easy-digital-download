<?php
/**
 * Plugin Name: ezDeFi - Bitcoin, Ethereum and Cryptocurrencies Payment Gateway for Easy Digital Downloads
 * Plugin URI: https://ezdefi.io/
 * Description: Accept Bitcoin, Ethereum and Cryptocurrencies on your Easy Digital Downloads store with ezDeFi
 * Version: 2.0.0
 * Author: ezDeFi
 * Author URI: https://ezdefi.io/
 * License: GPLv2 or later
 * Text Domain: edd-ezdefi
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) or exit;

if( ! EDD_Ezdefi_Loader::is_edd_active() ) {
	return;
}

class EDD_Ezdefi_Loader
{
	protected static $instance;

    protected $version = '2.0.0';

	protected $notices = array();

	/**
	 * Check if EDD is active
	 */
	public static function is_edd_active()
	{
		$active_plugins = (array) get_option( 'active_plugins', array() );

		if( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}

		return in_array( 'easy-digital-downloads/easy-digital-downloads.php', $active_plugins ) || array_key_exists( 'easy-digital-downloads/easy-digital-downloads.php', $active_plugins );
	}

	/**
	 * EDD_Ezdefi_Loader constructor.
	 */
	protected function __construct()
	{
		add_action( 'plugins_loaded', array( $this, 'init_plugin' ) );

        add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );

        add_action( 'admin_init', array(
            $this, 'update_database_notice'
        ) );

        add_action( 'admin_post_edd_ezdefi_update_database', array(
            $this, 'update_database'
        ) );

        add_filter( 'cron_schedules', array(
            $this, 'add_cron_schedule'
        ) );

        add_action( 'edd_ezdefi_weekly_event', array(
            $this, 'clear_database'
        ) );

		register_activation_hook( __FILE__, array( $this, 'activate' ) );

		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		$this->define_constants();
	}

	/**
	 * Initializes the plugin.
	 */
	public function init_plugin()
	{
		require_once plugin_dir_path( __FILE__ ) . 'class-edd-ezdefi.php';

		require_once plugin_dir_path( __FILE__ ) . 'functions.php';

		load_plugin_textdomain( 'edd-ezdefi', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );

		edd_ezdefi();
	}

    /**
     * Notice update database after update plugin
     */
    public function update_database_notice()
    {
        $current_version = get_option( 'edd_ezdefi_version' );

        if( ! empty( $current_version ) && version_compare( $current_version, $this->version, '>=' ) ) {
            return;
        }

        add_action( 'admin_notices', function() {
            ob_start(); ?>
            <div class="error is-dismissible">
                <p>
                    <strong>
                        <?php echo __( 'ezDeFi for Easy Digital Downloads has been updated. You have to update your database to newest version.', 'edd-ezdefi' ); ?>
                    </strong>
                </p>
                <p>
                    <?php echo __( 'The update process may take a little while, so please be patient.', 'edd-ezdefi' ); ?>
                </p>
                <form action="<?php echo admin_url( 'admin-post.php' ); ?>" method="POST">
                    <?php wp_nonce_field( 'edd_ezdefi_update_database', 'edd_ezdefi_update_database_nonce' ); ?>
                    <input type="hidden" name="action" value="edd_ezdefi_update_database">
                    <p><input class="button button-primary" type="submit" value="Update database"></p>
                </form>
            </div>
            <?php echo ob_get_clean();
        });
    }

    public function update_database()
    {
        global $wpdb;

        if(
            ! isset( $_POST['edd_ezdefi_update_database_nonce'] ) ||
            ! wp_verify_nonce( $_POST['edd_ezdefi_update_database_nonce'], 'edd_ezdefi_update_database' )
        ) {
            wp_safe_redirect( admin_url() );
        }

        $amount_table_name = $wpdb->prefix . 'edd_ezdefi_amount';

        $wpdb->query( "DROP TABLE IF EXISTS $amount_table_name" );
        $wpdb->query( "DROP PROCEDURE IF EXISTS `edd_ezdefi_generate_amount_id`" );
        $wpdb->query( "DROP EVENT IF EXISTS `edd_ezdefi_clear_amount_table`" );
        $wpdb->query( "DROP EVENT IF EXISTS `edd_ezdefi_clear_exception_table`" );

        $exception_table_name = $wpdb->prefix . 'edd_ezdefi_exception';

        if( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $exception_table_name ) ) === $exception_table_name ) {
            $wpdb->query(
                "ALTER TABLE $exception_table_name ADD confirmed TinyInt(1) DEFAULT 0, ADD is_show TinyInt(1) DEFAULT 1, ALTER explorer_url SET DEFAULT NULL;"
            );
        }

        update_option( 'edd_ezdefi_version', $this->version );

        if (! wp_next_scheduled ( 'edd_ezdefi_weekly_event' ) ) {
            wp_schedule_event( time(), 'weekly', 'edd_ezdefi_weekly_event' );
        }

        wp_safe_redirect( admin_url() );
    }

    /**
     * Add weekly cron schedule
     *
     * @param $schedules
     *
     * @return mixed
     */
    public function add_cron_schedule( $schedules )
    {
        $schedules['weekly'] = array(
            'interval' => 604800,
            'display' => __( 'Once Weekly' )
        );

        return $schedules;
    }

    /**
     * Create database weekly
     */
    public function clear_database()
    {
        global $wpdb;

        $exception_table_name = $wpdb->prefix . 'edd_ezdefi_exception';

        $wpdb->query( "DELETE FROM $exception_table_name;" );
    }

	/**
	 * Run when activate plugin
	 */
	public function activate()
	{
		global $wpdb;

		$sql = array();

		$charset_collate = $wpdb->get_charset_collate();

		$exception_table_name = $wpdb->prefix . 'edd_ezdefi_exception';

		$sql[] = "CREATE TABLE $exception_table_name (
			id int(11) NOT NULL AUTO_INCREMENT,
			amount_id decimal(60,30) NOT NULL,
			currency varchar(10) NOT NULL,
			order_id int(11),
			status varchar(20),
			payment_method varchar(100),
			explorer_url varchar(200) DEFAULT NULL,
			confirmed tinyint(1) DEFAULT 0 NOT NULL,
            is_show tinyint(1) DEFAULT 1 NOT NULL,
			PRIMARY KEY (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

        if (! wp_next_scheduled ( 'edd_ezdefi_weekly_event' ) ) {
            wp_schedule_event( time(), 'weekly', 'edd_ezdefi_weekly_event' );
        }

        update_option( 'edd_ezdefi_version', '2.0.0' );
	}

    /**
     * Run when deactivate plugin
     */
    public static function deactivate()
    {
        wp_clear_scheduled_hook( 'edd_ezdefi_weekly_event' );
    }

	/**
	 * Define constants
	 */
	public function define_constants()
	{
		define( 'EDD_EZDEFI_VERSION', '1.0.0' );
		define( 'EDD_EZDEFI_MAIN_FILE', __FILE__ );
	}

    /**
     * Add action link
     *
     * @param $links
     *
     * @return array
     */
    public function plugin_action_links( $links )
    {
        $plugin_links = array(
            '<a href="' . admin_url( 'admin.php?page=edd-settings&tab=gateways&section=ezdefi' ) . '">' . __( 'Settings', 'woocommerce-gateway-ezdefi' ) . '</a>'
        );

        return array_merge( $plugin_links, $links );
    }

	/**
	 * Get EDD_Ezdefi_Loader instance
	 */
	public static function instance()
	{
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

EDD_Ezdefi_Loader::instance();