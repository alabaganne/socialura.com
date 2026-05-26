<?php

class ASPACF_Fields {

	public $fields = array();
	public $ng;

	public function __construct() {
		$this->load();
		$this->fields = $this->sort_by_pos();
	}

	public function get_output( $id, $ng = false ) {
		if ( empty( $this->fields[ $id ] ) ) {
			return '';
		}
		$this->ng = $ng;

		$this->fields[ $id ]['id'] = $id;
		$out                       = '<div class="asp_product_custom_field_input_container">';
		if ( method_exists( $this, 'output_' . $this->fields[ $id ]['type'] ) ) {
			$out .= call_user_func( array( $this, 'output_' . $this->fields[ $id ]['type'] ), $this->fields[ $id ] );
		}
		$out .= '</div>';
		if ( ! $ng ) {
			wp_enqueue_script( 'jquery-ui-datepicker' );
			wp_localize_script(
				'asp-acf-frontend-scripts',
				'aspACFFrontendData',
				array(
					'fields' => $this->fields,
					'opts'   => array( 'firstDay' => get_option( 'start_of_week' ) ),
				)
			);
			wp_enqueue_script( 'asp-acf-frontend-scripts' );
			wp_enqueue_style( 'asp-acf-jquery-ui' );
		} else {
			add_filter( 'asp_ng_pp_output_add_scripts', array( $this, 'pp_output_scripts' ) );
			add_filter( 'asp_ng_pp_output_add_styles', array( $this, 'pp_output_styles' ) );
			add_filter( 'asp_ng_pp_output_add_vars', array( $this, 'pp_output_vars' ) );
		}
		return $out;
	}

	public function pp_output_vars( $vars ) {
		$vars['aspACFFrontendData'] = array(
			'fields' => $this->fields,
			'opts'   => array( 'firstDay' => get_option( 'start_of_week' ) ),
		);
		return $vars;
	}

	public function pp_output_styles( $styles ) {
		$styles[] = array(
			'src'    => 'https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.11.4/themes/smoothness/jquery-ui.min.css',
			'footer' => true,
		);
		/*      $styles[] = array(
			'src'    => plugin_dir_url( __FILE__ ) . '../public/css/asp-acf.css',
			'footer' => true,
		); */
		return $styles;
	}

	public function pp_output_scripts( $scripts ) {
		$site_url  = get_site_url();
		$scripts[] = array(
			'src'    => $site_url . '/wp-includes/js/jquery/jquery.js',
			'footer' => true,
		);
		$scripts[] = array(
			'src'    => $site_url . '/wp-includes/js/jquery/ui/core.min.js',
			'footer' => true,
		);
		$scripts[] = array(
			'src'    => $site_url . '/wp-includes/js/jquery/ui/datepicker.min.js',
			'footer' => true,
		);
		$scripts[] = array(
			'src'    => plugin_dir_url( __FILE__ ) . '../public/js/asp-acf-frontend-scripts.js',
			'footer' => true,
		);
		return $scripts;
	}

	private function output_text( $field ) {
		$tpl = '<label class="asp_product_custom_field_label asp_product_custom_field_label_%1$d">%2$s</label>' .
		'<input class="pure-input-1 asp_product_custom_field_input asp_product_custom_field_input_text asp_product_custom_input_%1$d" type="text" placeholder="%3$s" name="stripeCustomFields[%1$d]"%4$s>' .
		'<span class="asp_product_custom_field_error"></span>';
		$out = sprintf( $tpl, esc_attr( $field['id'] ), esc_html( $field['label'] ), esc_attr( $field['descr'] ), ( $field['required'] ? ' data-asp-custom-mandatory="1" required' : '' ) );
		return $out;
	}

	private function output_cb( $field ) {
		$tpl = '<label class="pure-checkbox asp_product_custom_field_label asp_product_custom_field_label_%1$d">
		<input class="asp_product_custom_field_input asp_product_custom_field_input_checkbox asp_product_custom_input_%1$d" type="checkbox" value="1" name="stripeCustomFields[%s]"%4$s%5$s> %2$s</label>' .
		'<div class="asp_product_custom_field_descr">%3$s</div>' .
		'<span class="asp_product_custom_field_error"></span>';
		$out = sprintf( $tpl, esc_attr( $field['id'] ), $field['label'], $field['descr'], ( $field['required'] ? ' data-asp-custom-mandatory="1" required' : '' ), ! empty( $field['opts']['checked'] ) ? ' checked' : '' );
		return $out;
	}

	private function output_datepicker( $field ) {
		$tpl = '<label class="asp_product_custom_field_label asp_product_custom_field_label_%1$d">%2$s</label>' .
		'<input class="pure-input-1 asp_product_custom_field_input asp_product_custom_field_input_datepicker asp_product_custom_input_%1$d" type="text" placeholder="%3$s" name="stripeCustomFields[%1$d]"%4$s>' .
		'<span class="asp_product_custom_field_error"></span>';
		$out = sprintf( $tpl, esc_attr( $field['id'] ), esc_html( $field['label'] ), esc_attr( $field['descr'] ), ( $field['required'] ? ' data-asp-custom-mandatory="1" required' : '' ) );
		return $out;
	}

	private function output_dropdown( $field ) {
		$items_str = '';
		if ( isset( $field['opts']['items'] ) ) {
			foreach ( $field['opts']['items'] as $id => $item ) {
				$items_str .= sprintf( '<option value="%s">%s</option>', $id, $item );
			}
		}
		$descr_str = '';
		/*      if ( $this->ng && ! empty( $field['descr'] ) ) {
			ob_start();
			?>
			<div class="asp_acf_help"><span class="dashicons dashicons-editor-help">?</span>
			<div class="asp_acf_help_text"><?php echo $field['descr'];?></div>
			</div>
			<?php
			$descr_str = ob_get_clean();
		} */
		$tpl = '<label class="asp_product_custom_field_label asp_product_custom_field_label_%1$d">%2$s%6$s</label>' .
		'<select class="pure-input-1 asp_product_custom_field_input asp_product_custom_field_input_dropdown asp_product_custom_input_%1$d" type="text" placeholder="%3$s" name="stripeCustomFields[%1$d]"%4$s>' .
		'%5$s' .
		'</select>' .
		'<span class="asp_product_custom_field_error"></span>';
		$out = sprintf( $tpl, esc_attr( $field['id'] ), esc_html( $field['label'] ), esc_attr( $field['descr'] ), ( $field['required'] ? ' data-asp-custom-mandatory="1" required' : '' ), $items_str, $descr_str );
		return $out;
	}

	public function get_fields_data( $prod_id ) {
		$data  = array();

		$acf_fields = filter_input( INPUT_POST, 'asp_stripeCustomFields', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		if(empty($acf_fields)){
			// Maybe its not a direct html form submission as 'asp_stripeCustomFields[]'.
			// It might already be in an parsed array format. For example: for the case of iDEAL payment addon.
			if ( isset($_POST['asp_stripeCustomFields']) && is_array($_POST['asp_stripeCustomFields']) ) {
				$acf_fields = array_map('sanitize_text_field', $_POST['asp_stripeCustomFields']);
			}
		}

		$input = ! empty( $acf_fields ) ? $acf_fields : array();
		if ( empty( $input ) ) {
			return $data;
		}
		$allowed_fields          = array();
		$display_specific_fields = get_post_meta( $prod_id, 'asp_acf_display_specific_fields', true );
		if ( $display_specific_fields ) {
			$allowed_fields = get_post_meta( $prod_id, 'asp_acf_allowed_fields', true );
		}
		foreach ( $this->fields as $id => $field ) {
			$val = '';
			if ( isset( $input[ $id ] ) ) {
				switch ( $field['type'] ) {
					case 'cb':
						$val = filter_var( $input[ $id ], FILTER_SANITIZE_NUMBER_INT );
						$val = empty( $val ) ? __( 'No', 'asp-acf' ) : __( 'Yes', 'asp-acf' );
						break;
					case 'dropdown':
						$val = filter_var( $input[ $id ], FILTER_SANITIZE_NUMBER_INT );
						$val = isset( $field['opts']['items'][ $val ] ) ? $field['opts']['items'][ $val ] : '';
						break;
					default:
						$val = filter_var( $input[ $id ] );
						break;
				}
			} else {
				if ( 'cb' === $field['type'] ) {
					$val = __( 'No', 'asp-acf' );
				}
			}
			if ( ! $display_specific_fields || ( $display_specific_fields && ! empty( $allowed_fields[ $id ] ) ) ) {
				$data[ $id ] = array(
					'name'  => $field['label'],
					'value' => $val,
				);
			}
		}
		//compatiblity for older core plugin versions (<2.0.8)
		if ( version_compare( WP_ASP_PLUGIN_VERSION, '2.0.8t3' ) < 0 ) {
			if ( ! empty( $data ) && ! isset( $data[0] ) ) {
				$first_item = reset( $data );
				$data[0]    = $first_item;
			}
		}
		return $data;
	}

	public function get_from_post() {
		$this->fields = array();
		if ( empty( $_POST['asp_acf_field_id'] ) ) {
			//no fields posted
			return true;
		}
		$fields = array();
		foreach ( $_POST['asp_acf_field_id'] as $key => $id ) {
			$fields[ $id ]             = array();
			$fields[ $id ]['type']     = $_POST['asp_acf_field_type'][ $id ];
			$fields[ $id ]['label']    = $_POST['asp_acf_field_label'][ $id ];
			$fields[ $id ]['descr']    = stripslashes( $_POST['asp_acf_field_description'][ $id ] );
			$fields[ $id ]['required'] = ! empty( $_POST['asp_acf_field_required'][ $id ] ) ? 1 : 0;
			$fields[ $id ]['pos']      = intval( $_POST['asp_acf_field_pos'][ $id ] );
			$fields[ $id ]['opts']     = isset( $_POST['asp_acf_field_opts'][ $id ] ) ? $_POST['asp_acf_field_opts'][ $id ] : array();
		}
		//sort fields by position
		$fields = $this->sort_by_pos( $fields );

		//reset sort index
		$pos = 0;
		foreach ( $fields as $key => $value ) {
			$pos ++;
			$fields[ $key ]['pos'] = $pos;
		}
		$this->fields = $fields;
		return true;
	}

	private function sort_by_pos( $fields = false ) {
		$fields = $fields === false ? $this->fields : $fields;
		uasort(
			$fields,
			function( $a, $b ) {
				return $a['pos'] - $b['pos'];
			}
		);
		return $fields;
	}

	public function save() {
		update_option( 'asp_acf_fields', $this->fields );
	}

	public function load() {
		$this->fields = get_option( 'asp_acf_fields', array() );
	}

}
