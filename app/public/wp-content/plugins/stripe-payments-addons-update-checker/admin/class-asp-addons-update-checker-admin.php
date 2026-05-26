<?php

class ASP_Addons_Update_Checker_Admin {

	var $plugin_slug;
	var $ASPAdmin;
	var $helper;
	var $item_hash;

	function __construct( $helper ) {
		$this->ASPAdmin = AcceptStripePayments_Admin::get_instance();
		$this->plugin_slug = $this->ASPAdmin->plugin_slug;
		$this->helper = $helper;
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'show_lic_key_notice' ) );
		add_action( 'asp-settings-page-after-tabs-menu', array( $this, 'after_tabs_menu' ) );
		add_action( 'asp-settings-page-after-tabs', array( $this, 'after_tabs' ) );
		add_filter( 'asp-admin-settings-addon-field-display', array( $this, 'field_display' ), 10, 2 );
		add_filter( 'apm-admin-settings-sanitize-field', array( $this, 'sanitize_settings' ), 10, 2 );
	}

	function sanitize_settings( $output, $input ) {
		//This function will be called when the save or the re-check button is clicked from the settings menu of this addon.

		$lic_key = trim( $input['auc_lic_key'] );
		$output['auc_lic_key'] = $lic_key;
		$curr_lic_key = AcceptStripePayments::get_instance()->get_setting( 'auc_lic_key' );
		$force_verify = AcceptStripePayments::get_instance()->get_setting( 'auc_force_verify' );

		if ( empty( $lic_key ) ) {
			$output['auc_customer_verified'] = false;
		}

		if ( ( ! empty( $lic_key ) && $curr_lic_key !== $lic_key ) || $force_verify || ! empty( $input['auc_force_recheck'] ) ) {

			$url = add_query_arg(
				array(
					'action' => 'check',
					'lic_key' => $lic_key,
					'recheck' => 1,
				),
				'https://s-plugins.com/splm-new/'
			);

			$res = wp_remote_get( $url, array( 'sslverify' => false ) );

			$err_msg = '';

			if ( is_wp_error( $res ) ) {
				$err_msg = $res->get_error_message();
				$output['auc_force_verify'] = true;
			} elseif ( 200 !== $res['response']['code'] ) {
				$err_msg = __( 'Error occurred during request. Error code:', 'asp-auc' ) . ' ' . $res['response']['code'];
				$output['auc_force_verify'] = true;
			}

			if ( empty( $err_msg ) ) {
				$output['auc_force_verify'] = false;
				$body = json_decode( $res['body'] );
				if ( isset( $body->error ) ) {
					$err_msg = $body->error;
					if ( ! empty( $body->error_code ) ) {
						$err_code = $body->error_code;
					}
				}
			}

			if ( ! empty( $err_msg ) ) {
				$output['auc_customer_verified'] = false;
				add_settings_error(
					'auc_lic_key',
					'auc_lic_key',
					__( 'Update Checker Error:', 'asp-auc' ) . ' ' . $err_msg
				);
			} else {
				//Success case (license key check passed)
				$output['auc_customer_verified'] = true;
				if ( ! empty( $this->item_hash ) ) {
					AcceptStripePayments_Admin::remove_admin_notice_by_hash( $this->item_hash );
				}

				//When the license key has been verified successfully, we will reset the following options so they can used again as intended.
				$user_id = get_current_user_id();
				if ( !empty( $user_id ) && current_user_can( 'manage_options' ) ) {
					//Reset the dismissed notice flag. So when the licesne key expires after 1 year, the notice will be shown again.
					update_user_meta( $user_id, 'asp_auc_lic_key_msg_dismissed', false );
				}
			}

			if ( ! empty( $err_code ) && 4 === $err_code ) {
				$output['auc_customer_expired'] = true;
			} else {
				$output['auc_customer_expired'] = false;
			}
		}

		return $output;
	}

	function field_display( $field, $field_value ) {
		$ret = array();
		switch ( $field ) {
			case 'auc_lic_key':
				//The 'License Key' field.
				$ret['field'] = 'auc_lic_key';
				$ret['field_name'] = $field;
				break;
			case 'auc_customer_verified':
				//The 'Status' field.
				$ret['field'] = 'custom';
				$ret['field_name'] = $field;

				$status = '<span style="color:green">' . __( 'Enabled!', 'asp-auc' ) . '</span>';
				$descr = '<p class="description">' . __( 'Add-on updates are enabled.', 'asp-auc' ) . '</p>';
				$verified = AcceptStripePayments::get_instance()->get_setting( 'auc_customer_verified' );
				if ( empty( $verified ) ) {
					$status = '<span style="color:red">' . __( 'Disabled!', 'asp-auc' ) . '</span>';
					$expired = AcceptStripePayments::get_instance()->get_setting( 'auc_customer_expired' );
					if ( ! empty( $expired ) ) {
						$descr = '<p class="description"><strong>' . __( 'Your license has expired. Please make a renewal payment to activate addon updates. You can <a href="https://s-plugins.com/stripe-plugin-support-renewal-payment/" target="_blank">renew from this page</a>.', 'asp-auc' ) . '</strong></p>';
						$descr .= '<p class="description">' . __( 'After renewal, you can click the button below to re-check the license key and activate updates.', 'asp-auc' ) . '</p>';
						$descr .= '<p><button id="auc-recheck" type="button" class="button">' . __( 'Re-check & Activate', 'asp-auc' ) . '</a></p>';
						$descr .= '<script>jQuery(\'#auc-recheck\').click(function(e) {e.preventDefault();jQuery(\'input[name="submit"]\').click();});</script>';
						$descr .= '<input type="hidden" name="AcceptStripePayments-settings[auc_force_recheck]" value="1">';
						$force_recheck_nonce = wp_create_nonce( 'auc_force_recheck_nonce' );
						$descr .= '<input type="hidden" name="_wpnonce_force_recheck" value="' . $force_recheck_nonce . '">';
					} else {
						if ( '1' !== ASP_Addons_Update_Checker::$show_lic_notice ) {
							$status = '<span>' . __( 'Enabled for the free add-ons', 'asp-auc' ) . '</span>';
							$descr = '<p class="description">' . __( 'You don\'t need to enter a license key if only the free add-ons are used.', 'asp-auc' ) . '</p>';
						} else {
							$descr = '<p class="description">' . __( 'Add-on updates are disabled. Please enter a valid license key to enable updates. You can find your <a href="https://s-plugins.com/your-license-key/" target="_blank">license key on this page</a>.', 'asp-auc' ) . '</p>';
						}
					}
				}

				$ret['field_data'] = $status . $descr;
				break;
		}
		if ( ! empty( $ret ) ) {
			return $ret;
		} else {
			return $field;
		}
	}

	function show_section_descr() {

	}

	function register_settings() {
		$this->helper->add_settings_section( __( 'Update Checker', 'asp-auc' ), array( $this, 'show_section_descr' ) );
		$this->helper->add_settings_field( 'auc_customer_verified', __( 'Status', 'asp-auc' ), '' );
		$this->helper->add_settings_field( 'auc_lic_key', __( 'License Key', 'asp-auc' ), __( 'Enter your license key. If you have a paid and active account then you can find your <a href="https://s-plugins.com/your-license-key/" target="_blank">license key on this page</a>.', 'asp-auc' ), 40 );
		//$this->helper->add_settings_field( 'auc_run_update_checker', __( 'Re-run Update Checker', 'asp-auc' ), __( 'If you have recently renewed your license, you can use this option to manually recheck for updates instead of waiting 24 hours for the update checker to run again.', 'asp-auc' ), 40 );
	}

	function after_tabs_menu() {
		?>
		<a href="#auc" data-tab-name="auc" class="nav-tab"><?php echo __( 'Update Checker', 'asp-auc' ); ?></a>
		<?php
	}

	function after_tabs() {
		?>
		<div class="wp-asp-tab-container asp-auc-container" data-tab-name="auc">
			<?php do_settings_sections( $this->plugin_slug . '-auc' ); ?>
		</div>
		<?php
	}

	public function show_lic_key_notice() {

		$show_notice = get_option( 'asp_auc_show_lic_notice' );
		if ( empty( $show_notice ) ) {
			return;
		}

		$verified = AcceptStripePayments::get_instance()->get_setting( 'auc_customer_verified' );
		if ( $verified ) {
			return;
		}

		$user_id = get_current_user_id();
		if ( empty( $user_id ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$notice_dismissed = get_user_meta( $user_id, 'asp_auc_lic_key_msg_dismissed', true );
		if ( ! empty( $notice_dismissed ) ) {
			return;
		}
		$admin_url = get_admin_url();
		$dismiss_url = add_query_arg( 'asp_auc_dismiss_lic_key_msg', '1', $admin_url );
		$dismiss_url = wp_nonce_url( $dismiss_url, 'asp_auc_dismiss_lic_key_msg' );
		$dismiss_msg = '<span class="asp_auc_dismiss_lic_key_msg" style="text-align: right;display:block;"><a style="text-decoration: none; border-bottom: 1px dashed;font-size:0.9em;" href="' . $dismiss_url . '">' . __( 'Don\'t show this message for now', 'asp-auc' ) . '</a></span>';

		$auc_settings_url = add_query_arg(
			array(
				'post_type' => 'asp-products',
				'page' => 'stripe-payments-settings',
			),
			get_admin_url( null, 'edit.php' )
		);

		$auc_settings_url .= '#auc';

		$this->item_hash = AcceptStripePayments_Admin::add_admin_notice(
			'warning',
			// translators: %s is replaced by a link to plugin page
			sprintf( __( '<span><strong>Stripe Payments Add-ons Update Checker</strong></span><br>Enter your license key on the <a target="_blank" href="%s">settings page</a> to enable updates for add-ons.', 'asp-auc' ), $auc_settings_url ) .
			$dismiss_msg,
			false
		);

		$notice_dismissed_get = filter_input( INPUT_GET, 'asp_auc_dismiss_lic_key_msg', FILTER_SANITIZE_NUMBER_INT );
		if ( ! empty( $notice_dismissed_get ) && check_admin_referer( 'asp_auc_dismiss_lic_key_msg' ) ) {
			update_user_meta( $user_id, 'asp_auc_lic_key_msg_dismissed', true );
			AcceptStripePayments_Admin::remove_admin_notice_by_hash( $this->item_hash );
			wp_safe_redirect( get_admin_url() );
			exit;
		}
	}

}
