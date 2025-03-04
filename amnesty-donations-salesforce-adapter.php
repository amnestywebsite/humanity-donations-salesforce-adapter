<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName

/**
 * Plugin Name:       Humanity Donations Salesforce Adapter
 * Plugin URI:        https://github.com/amnestywebsite/humanity-donations-salesforce-adapter
 * Description:       Add Salesforce data synchronisation to the Humanity Donations plugin
 * Version:           1.0.1
 * Author:            Amnesty International
 * Author URI:        https://www.amnesty.org
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       adsa
 * Domain Path:       /languages
 * Network:           true
 * Requires PHP:      8.2
 * Requires at least: 5.8.0
 * Tested up to:      6.7.2
 * Requires Plugins:  humanity-donations, humanity-salesforce-connector
 */

declare( strict_types = 1 );

namespace Amnesty\Donations\Salesforce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'get_plugin_data' ) ) {
	require_once ABSPATH . '/wp-admin/includes/plugin.php';
}

register_deactivation_hook(
	__FILE__,
	function (): void {
		Settings::clear();
	}
);

new Init();

/**
 * Plugin instantiation class
 */
class Init {

	/**
	 * Absolute path to this file
	 *
	 * @var string
	 */
	public static $file = __FILE__;

	/**
	 * Plugin data
	 *
	 * @var array
	 */
	protected $data = [];

	/**
	 * Bind hooks
	 */
	public function __construct() {
		$this->data = get_plugin_data( __FILE__ );

		add_filter( 'register_translatable_package', [ $this, 'register_translatable_package' ], 12 );

		add_action( 'plugins_loaded', [ $this, 'textdomain' ] );
		add_action( 'plugins_loaded', [ $this, 'boot' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
		add_action( 'amnesty_salesforce_connector_settings', [ $this, 'settings' ], 10, 2 );
		add_action( 'woocommerce_payment_complete', [ $this, 'queue' ] );
		add_action( 'amnesty_process_donation_salesforce', [ $this, 'process' ] );
	}

	/**
	 * Register this plugin as a translatable package
	 *
	 * @param array<int,array<string,string>> $packages existing packages
	 *
	 * @return array<int,array<string,string>>
	 */
	public function register_translatable_package( array $packages = [] ): array {
		$packages[] = [
			'id'     => 'humanity-donations-salesforce-adapter',
			'path'   => realpath( __DIR__ ),
			'pot'    => realpath( __DIR__ ) . '/languages/adsa.pot',
			'domain' => 'adsa',
		];

		return $packages;
	}

	/**
	 * Register textdomain
	 *
	 * @return void
	 */
	public function textdomain(): void {
		load_plugin_textdomain( 'adsa', false, basename( __DIR__ ) . '/languages' );
	}

	/**
	 * Load required files
	 *
	 * @return void
	 */
	public function boot(): void {
		require_once __DIR__ . '/includes/abstract-class-singleton.php';
		require_once __DIR__ . '/includes/class-fields.php';
		require_once __DIR__ . '/includes/class-option.php';
		require_once __DIR__ . '/includes/class-settings.php';
		require_once __DIR__ . '/includes/class-salesforce-adapter.php';
		require_once __DIR__ . '/includes/class-page-settings.php';
	}

	/**
	 * Enqueue assets
	 *
	 * @return void
	 */
	public function enqueue(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- not needed
		$page = sanitize_text_field( $_GET['page'] ?? '' );

		if ( 'amnesty_salesforce_donations' !== $page ) {
			return;
		}

		wp_add_inline_style(
			'cmb2-styles',
			'.postbox .inside.cmb-td.cmb-nested.cmb-field-list{max-height:500px;overflow-y:scroll}
			.cmb-td ul:not([class]){padding:1em;list-style:initial}
			.is-hidden{display:none}'
		);

		wp_enqueue_script( 'adsa', plugins_url( '/assets/app.js', __FILE__ ), [ 'wp-hooks' ], $this->data['Version'], true );
	}

	/**
	 * Register settings with CMB2
	 *
	 * @param \CMB2  $settings  the CMB2 settings object
	 * @param string $menu_hook the parent page admin menu hook
	 *
	 * @return void
	 */
	public function settings( \CMB2 $settings, string $menu_hook = 'admin_menu' ): void {
		if ( ! is_admin() || ! wp_get_referer() || false === strpos( wp_get_referer(), '/wp-admin/' ) ) {
			return;
		}

		$settings = new_cmb2_box(
			[
				'id'              => Settings::key(),
				'title'           => __( 'Donations', 'aip-sf' ),
				'object_types'    => [ 'options-page' ],
				'option_key'      => Settings::key(),
				'parent_slug'     => $settings->prop( 'id' ),
				'admin_menu_hook' => $menu_hook,
			]
		);

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- not needed
		$page = sanitize_text_field( $_GET['page'] ?? '' );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- not needed
		$method = sanitize_text_field( $_SERVER['REQUEST_METHOD'] ?? 'GET' );

		if ( 'GET' === strtoupper( $method ) && Settings::key() !== $page ) {
			return;
		}

		new Page_Settings( $settings );
	}

	/**
	 * Queue the sync from WooCommerce to Salesforce
	 *
	 * @param integer $order_id the order id
	 *
	 * @return void
	 */
	public function queue( int $order_id = 0 ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			do_action( 'amnesty_process_donation_salesforce', $order_id );
		} else {
			wp_schedule_single_event( time(), 'amnesty_process_donation_salesforce', [ $order_id ] );
		}
	}

	/**
	 * Process a purchase for synchronisation with Salesforce
	 *
	 * @param integer $order_id the WooCommerce donation order ID
	 *
	 * @return void
	 */
	public function process( int $order_id = 0 ): void {
		new Adapter( $order_id );
	}

}
