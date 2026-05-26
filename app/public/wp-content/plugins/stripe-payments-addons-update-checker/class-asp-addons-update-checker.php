<?php
/**
 * Plugin Name: Stripe Payments Addons Update Checker
 * Plugin URI: https://s-plugins.com/
 * Description: Checks for updates to Accept Stripe Payments add-ons.
 * Version: 2.2
 * Author: Tips and Tricks HQ
 * Author URI: https://s-plugins.com/
 * License: GPL2
 * Text Domain: asp-auc
 * Domain Path: /languages
 */

class ASP_Addons_Update_Checker {

	public static $free_addons = array(
		'stripe-payments-addons-update-checker',
		'stripe-payments-country-autodetect',
		'stripe-payments-custom-messages',
		'stripe-payments-alipay',
	);
	public static $lic_opt_func_added = false;
	public static $show_lic_notice    = '0';

	public $helper;
	public $file            = __FILE__;
	public $MIN_ASP_VER 	= '2.0.9';
	public $SLUG            = 'stripe-payments-addons-update-checker';
	public $ADDON_FULL_NAME = 'Stripe Payments Addons Update Checker';

	public $textdomain        = 'asp-auc';
	public $ADDON_SHORT_NAME  = 'auc';
	public $SETTINGS_TAB_NAME = 'auc';
	public $asp_main;
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), 9 );
	}

	public function plugins_loaded() {
		if ( class_exists( 'AcceptStripePayments' ) ) {
			$this->asp_main = AcceptStripePayments::get_instance();
			$this->helper = new ASP_Addons_Helper( $this );
			if ( $this->helper->check_ver() ) {
				$this->helper->init_tasks();
			};

			if ( is_admin() ) {
				include_once plugin_dir_path( __FILE__ ) . 'admin/class-asp-addons-update-checker-admin.php';
				new ASP_Addons_Update_Checker_Admin( $this->helper );
			}
		}
	}

	public static function set_request_options( $options ) {
		$options['timeout'] = 5;
		//ASP_Debug_Logger::log( 'Update checker set_request_options(). Options: ' . print_r( $options, true ), true, 'AUC' );
		return $options;
	}

	public static function set_query_args( $args ) {
		$args['lic_key'] = AcceptStripePayments::get_instance()->get_setting( 'auc_lic_key' );
		//ASP_Debug_Logger::log_array_data( 'set_query_args(). Query args: ', $args, true, 'AUC' );
		return $args;
	}

	public static function process_request_result( $plugin_info, $http_resp ) {
		//ASP_Debug_Logger::log( 'Update checker process_request_result().', true, 'AUC' );
		//ASP_Debug_Logger::log( 'Plugin info: ' . print_r( $plugin_info, true ), true, 'AUC' );
		try {
			//set icon
			if ( ! empty( $plugin_info ) && ! empty( $plugin_info->slug ) ) {
				$icon_path = 'icons/' . $plugin_info->slug . '.png';
				if ( file_exists( plugin_dir_path( __FILE__ ) . $icon_path ) ) {
					$plugin_info->icons = array( 'default' => plugin_dir_url( __FILE__ ) . $icon_path );
				}
			}

			if ( is_wp_error( $http_resp ) ) {
				//ASP_Debug_Logger::log( 'Error! Found WP_error in HTTP response', true, 'AUC' );
				return $plugin_info;
			}
			if ( empty( $http_resp['body'] ) ) {
				//ASP_Debug_Logger::log( 'Error! Found empty body in response', true, 'AUC' );
				return $plugin_info;
			}

			// We have a response body, let's check if it's a valid JSON
			$body = json_decode( $http_resp['body'] );
			//Let's check if the response contains an error code.
			if ( empty( $body->error_code ) ) {
				//No error code found in response. This is a success case. Return the plugin info.
				//ASP_Debug_Logger::log( 'Success! No error_code in response.', true, 'AUC' );
				//ASP_Debug_Logger::log( 'Response Body: ' . print_r( $body, true ), true, 'AUC' );
				return $plugin_info;
			}

			//If we are here, then there is an error code in the response.
			//We can modify the $plugin_info object (PluginInfo.php) here.

			//ASP_Debug_Logger::log( 'Error code found in response: ' . $body->error_code, false, 'AUC' );
			$opt = get_option( 'AcceptStripePayments-settings' );
			if ( ! empty( $opt['auc_customer_verified'] ) || empty( $opt['auc_force_verify'] ) ) {
				$opt['auc_customer_verified'] = false;//Set the flag to false
				$opt['auc_force_verify'] = true; //Set the flag to force re-verify
				if ( $body->error_code == 2 || $body->error_code == 3 || $body->error_code == 4 || $body->error_code == 5 ) {
					$opt['auc_customer_expired'] = true;
				}
				ASP_Debug_Logger::log( 'The License key for the account expired or payment lapsed. License set to expired.', true, 'AUC' );
				unregister_setting( 'AcceptStripePayments-settings-group', 'AcceptStripePayments-settings' );
				update_option( 'AcceptStripePayments-settings', $opt );
			}
		} catch ( \Throwable $e ) {
			ASP_Debug_Logger::log( $e->getMessage(), false, 'AUC' );
		}

		//ASP_Debug_Logger::log( 'Returning from process_request_result().', true, 'AUC' );
		//ASP_Debug_Logger::log( 'Plugin info: ' . print_r( $plugin_info, true ), true, 'AUC' );
		return $plugin_info;
	}

	public static function check_updates( $slug, $file ) {
		//This function runs for admin pages only.

		//Include the update checker library (it uses namespaces so no need to check class_exists)
		require_once  plugin_dir_path( __FILE__ ) . '/lib/plugin-update-checker/plugin-update-checker.php';

		if ( ! in_array( $slug, self::$free_addons, true ) ) {
			self::$show_lic_notice = '1';
		}

		if ( empty( self::$lic_opt_func_added ) ) {
			self::$lic_opt_func_added = true;
			add_action( 'admin_init', ( array( 'ASP_Addons_Update_Checker', 'lic_opt_handler' ) ) );
		}

		//We will only check for updates if the license key is set in the settings.
		$license_key = AcceptStripePayments::get_instance()->get_setting( 'auc_lic_key' );
		if ( empty( $license_key ) ) {
			ASP_Debug_Logger::log( 'License key is empty. Not checking for updates.', true, 'AUC' );
			return;
		}

		//Change timeout from default 10 seconds to 5
		add_filter( 'puc_request_info_options-' . $slug, array( 'ASP_Addons_Update_Checker', 'set_request_options' ) );
		//Add the license key as a query arg in the URL (for the 'get_metadata' request call). Note: the 'download' request is different from the 'get_metadata' request.
		add_filter( 'puc_request_info_query_args-' . $slug, array( 'ASP_Addons_Update_Checker', 'set_query_args' ) );
		//Add a filter to process the request result.
		add_filter( 'puc_request_info_result-' . $slug, array( 'ASP_Addons_Update_Checker', 'process_request_result' ), 10, 2 );

		//Create the update checker object. We will pass the license key as a query arg in the URL.
		$myUpdateChecker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
			'https://s-plugins.com/splm-new/?action=get_metadata&slug=' . $slug,
			$file,
			$slug
		);


		//The following code is ready but we haven't made it live yet. We will make it live when we are ready to force a re-check for updates.
		//When the 'force re-check' button is clicked in the settings menu, we can force a re-check for updates (so the get_metadata values are all refreshed).
		if( isset($_POST['AcceptStripePayments-settings']['auc_force_recheck']) && $_POST['AcceptStripePayments-settings']['auc_force_recheck'] == '1' ) {
			$nonce_val = isset( $_POST['_wpnonce_force_recheck'] ) ? sanitize_text_field(  $_POST['_wpnonce_force_recheck']  ) : '';
			if ( wp_verify_nonce( $nonce_val, 'auc_force_recheck_nonce' ) ) {
				//Nonce is valid. We can force a re-check.
				//ASP_Debug_Logger::log( 'Force re-check option was used. Forcing an update checker run for ' . $slug, true, 'AUC' );
				//We can force a check for updates to execute the 'get_metadata' request instead of waiting for the cron job (which runs 2 times daily).
				//$myUpdateChecker->checkForUpdates();
			}
		}
	}

	public static function lic_opt_handler() {
		$opt = get_option( 'asp_auc_show_lic_notice', '0' );
		if ( self::$show_lic_notice !== $opt ) {
			update_option( 'asp_auc_show_lic_notice', self::$show_lic_notice );
		}
	}

}

new ASP_Addons_Update_Checker();
