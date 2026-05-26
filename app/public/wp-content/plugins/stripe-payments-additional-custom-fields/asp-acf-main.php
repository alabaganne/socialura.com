<?php

/**
 * Plugin Name: Stripe Payments Additional Custom Fields Addon
 * Plugin URI: https://s-plugins.com/stripe-payments-custom-messages-addon/
 * Description: Stripe Payments Additional Custom Fields Addon.
 * Version: 2.0.9
 * Author: Tips and Tricks HQ, alexanderfoxc
 * Author URI: https://s-plugins.com/
 * License: GPL2
 * Text Domain: asp-acf
 * Domain Path: /languages
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; //Exit if accessed directly
}

class ASPACF_Main {

	var $VERSION = '2.0.9';
	public $helper;
	public $ASPMain;
	public $file;
	var $textdomain        = 'asp-acf';
	var $ADDON_SHORT_NAME  = 'ACF';
	var $ADDON_FULL_NAME   = 'Stripe Payments Additional Custom Fields Addon';
	var $MIN_ASP_VER       = '2.0.84';
	var $SLUG              = 'stripe-payments-additional-custom-fields';
	var $SETTINGS_TAB_NAME = 'acf';

	public function __construct() {
		$this->file = __FILE__;
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ) );
	}

	public function plugins_loaded() {
		if ( class_exists( 'AcceptStripePayments' ) ) {
			$this->ASPMain = AcceptStripePayments::get_instance();
			$this->helper  = new ASPAddonsHelper( $this );
			//check minimum required core plugin version
			if ( ! $this->helper->check_ver() ) {
				return false;
			}
			$this->helper->init_tasks();

			if ( ! wp_doing_ajax() ) {
				add_filter( 'asp_email_body_tags_vals_before_replace', array( $this, 'email_body_tags_vals_before_replace' ), 10, 2 );
				add_filter( 'asp_email_body_after_replace', array( $this, 'email_body_after_replace' ) );
			}

			if ( $this->ASPMain->get_setting( 'acf_enabled' ) && $this->ASPMain->get_setting( 'custom_field_enabled' ) ) {
				add_filter( 'asp_button_output_replace_custom_field', array( $this, 'output_fields' ), 10, 2 );
				add_filter( 'asp_ng_button_output_replace_custom_field', array( $this, 'output_fields_ng' ), 10, 2 );
				add_filter( 'asp_process_custom_fields', array( $this, 'process_custom_fields' ), 10, 2 );
			}
			if ( is_admin() ) {
				include_once plugin_dir_path( $this->file ) . 'admin/asp-acf-admin-menu.php';
				new ASPACF_admin_menu( $this->helper );
			}
		}
	}

	public function register_scripts() {
		wp_register_style( 'asp-acf-jquery-ui', 'https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.11.4/themes/smoothness/jquery-ui.min.css' );
		wp_register_script( 'asp-acf-frontend-scripts', plugin_dir_url( __FILE__ ) . 'public/js/asp-acf-frontend-scripts.js', array( 'jquery', 'jquery-ui-datepicker' ), $this->VERSION, true );
	}

	public function process_custom_fields( $out, $data ) {
		require_once plugin_dir_path( $this->file ) . '/inc/class.asp-acf-fields.php';
		$fields      = new ASPACF_Fields();
		$fields_data = $fields->get_fields_data( $data['product_id'] );
		return $fields_data;
	}

	public function output_fields_ng( $output, $data ) {
		if ( empty( $data['custom_field'] ) ) {
			//custom field disabled for the product
			return $output;
		}
		require_once plugin_dir_path( $this->file ) . '/inc/class.asp-acf-fields.php';
		$fields_class            = new ASPACF_Fields();
		$fields                  = $fields_class->fields;
		$display_specific_fields = get_post_meta( $data['product_id'], 'asp_acf_display_specific_fields', true );
		if ( ! empty( $display_specific_fields ) ) {
			//we should display specific fields only
			$allowed_fields = get_post_meta( $data['product_id'], 'asp_acf_allowed_fields', true );
			if ( ! empty( $allowed_fields ) ) {
				$fields_arr = array();
				foreach ( $allowed_fields as $id => $allowed_field ) {
					$fields_arr[ $id ] = $fields[ $id ];
				}
				$fields = $fields_arr;
			}
		}
		foreach ( $fields as $id => $field ) {
			$output .= $fields_class->get_output( $id, true );
		}
		return $output;
	}

	public function output_fields( $output, $data ) {
		if ( empty( $data['custom_field'] ) ) {
			//custom field disabled for the product
			return $output;
		}
		require_once plugin_dir_path( $this->file ) . '/inc/class.asp-acf-fields.php';
		$fields_class            = new ASPACF_Fields();
		$fields                  = $fields_class->fields;
		$display_specific_fields = get_post_meta( $data['product_id'], 'asp_acf_display_specific_fields', true );
		if ( ! empty( $display_specific_fields ) ) {
			//we should display specific fields only
			$allowed_fields = get_post_meta( $data['product_id'], 'asp_acf_allowed_fields', true );
			if ( ! empty( $allowed_fields ) ) {
				$fields_arr = array();
				foreach ( $allowed_fields as $id => $allowed_field ) {
					$fields_arr[ $id ] = $fields[ $id ];
				}
				$fields = $fields_arr;
			}
		}
		foreach ( $fields as $id => $field ) {
			$output .= $fields_class->get_output( $id );
		}
		return $output;
	}

	public function email_body_tags_vals_before_replace( $tags_vals, $post ) {
		if ( empty( $post['product_id'] ) ) {
			//no product id
			return $tags_vals;
		}
		require_once plugin_dir_path( $this->file ) . '/inc/class.asp-acf-fields.php';
		$fields_class = new ASPACF_Fields();
		$fields       = $fields_class->get_fields_data( $post['product_id'] );
		if ( empty( $fields ) ) {
			//no custom fields
			return $tags_vals;
		}
		//let's add tags and corresponding vals
		foreach ( $fields as $id => $field ) {
			$tags_vals['tags'][] = sprintf( '{custom_field_%d}', $id );
			$tags_vals['vals'][] = sprintf( '%s: %s', $field['name'], $field['value'] );
			$tags_vals['tags'][] = sprintf( '{custom_field_name_%d}', $id );
			$tags_vals['vals'][] = sprintf( '%s', $field['name'] );
			$tags_vals['tags'][] = sprintf( '{custom_field_value_%d}', $id );
			$tags_vals['vals'][] = sprintf( '%s', $field['value'] );
		}
		return $tags_vals;
	}

	public function email_body_after_replace( $body ) {
		//let's remove potential tags leftovers
		$body = preg_replace( array( '/\{custom_field_([0-9])*\}/', '/\{custom_field_name_([0-9])*\}/', '/\{custom_field_value_([0-9])*\}/' ), array( '', '', '' ), $body );
		return $body;
	}

}

new ASPACF_Main();
