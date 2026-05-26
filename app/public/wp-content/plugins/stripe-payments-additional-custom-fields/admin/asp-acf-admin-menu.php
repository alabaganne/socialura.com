<?php

class ASPACF_admin_menu {

	var $plugin_slug;
	var $ASPAdmin;
	var $helper;

	function __construct( $helper ) {
		$this->ASPAdmin    = AcceptStripePayments_Admin::get_instance();
		$this->plugin_slug = $this->ASPAdmin->plugin_slug;
		$this->helper      = $helper;
		require_once plugin_dir_path( $helper->addon->file ) . '/inc/class.asp-acf-fields.php';
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'asp-settings-page-after-styles', array( $this, 'after_styles' ) );
		add_action( 'asp-settings-page-after-tabs-menu', array( $this, 'after_tabs_menu' ) );
		add_action( 'asp-settings-page-after-tabs', array( $this, 'after_tabs' ) );
		add_action( 'asp-settings-page-after-form', array( $this, 'after_form' ) );
		add_filter( 'asp-admin-settings-addon-field-display', array( $this, 'field_display' ), 10, 2 );
		add_filter( 'apm-admin-settings-sanitize-field', array( $this, 'sanitize_settings' ), 10, 2 );
		add_filter( 'asp_get_email_tags_descr', array( $this, 'email_tags_descr' ) );
		add_action( 'asp_acf_settings_page_display_msg', array( $this, 'display_settings_msg' ) );
		add_action( 'asp_product_custom_field_metabox_after', array( $this, 'after_custom_field_metabox' ) );
		add_action( 'asp_save_product_handler', array( $this, 'save_product' ), 10, 3 );
	}

	function save_product( $prod_id, $post, $update ) {
		$allowed_fields = isset( $_POST['asp_acf_allowed_fields'] ) ? $_POST['asp_acf_allowed_fields'] : array();
		update_post_meta( $prod_id, 'asp_acf_allowed_fields', $allowed_fields );
		$display_specific_fields = filter_input( INPUT_POST, 'asp_acf_display_specific_fields', FILTER_SANITIZE_NUMBER_INT );
		update_post_meta( $prod_id, 'asp_acf_display_specific_fields', $display_specific_fields );
	}

	function after_custom_field_metabox( $prod_id ) {
		$fields         = new ASPACF_Fields();
		$display_fields = get_post_meta( $prod_id, 'asp_acf_display_specific_fields', true );
		echo '<p>' . __( 'Select which custom fields are displayed for this product.', 'asp-acf' ) . '</p>';
		echo '<label><input type="radio" value="0" name="asp_acf_display_specific_fields"' . ( empty( $display_fields ) ? ' checked' : '' ) . '>' . __( 'All fields', 'asp-acf' ) . ' </label>';
		echo '<label><input type="radio" value="1" name="asp_acf_display_specific_fields"' . ( ! empty( $display_fields ) ? ' checked' : '' ) . '>' . __( 'Specific fields:', 'asp-acf' ) . '</label> ';
		$allowed_fields = get_post_meta( $prod_id, 'asp_acf_allowed_fields', true );
		echo '<p class="asp-acf-allowed-fields"' . ( empty( $display_fields ) ? ' style="display: none;"' : '' ) . '>';
		if ( ! empty( $fields->fields ) ) {
			$tpl = '<label><input type="checkbox" name="asp_acf_allowed_fields[%d]" value="1"%s> %s</label><br>';
			foreach ( $fields->fields as $id => $field ) {
				$checked = '';
				if ( is_array( $allowed_fields ) ) {
					if ( ! empty( $allowed_fields[ $id ] ) ) {
						$checked = ' checked';
					}
				}
				echo sprintf( $tpl, $id, $checked, $field['label'] );
			}
		} else {
			//no custom fields created yet
			echo __( 'No custom fields created.', 'asp-acf' );
		}
		echo '</p>';
		?>
<script>
jQuery(document).ready(function($) {
	$('input[name="asp_acf_display_specific_fields"]').change(function() {
		if ($(this).val() === "1") {
			$('.asp-acf-allowed-fields').show();
		} else {
			$('.asp-acf-allowed-fields').hide();
		}
	});
});
</script>
		<?php
	}

	function display_settings_msg() {
		if ( $this->helper->addon->ASPMain->get_setting( 'acf_enabled' ) && $this->helper->addon->ASPMain->get_setting( 'custom_field_enabled' ) ) {
			echo '<p style="color:red;">' . sprintf( __( 'Custom Field is superseded by Additional Custom Fields addon. Its settings are availabe %s', 'asp-acf' ), '<a href="#acf" onclick="jQuery(\'a.nav-tab[data-tab-name=acf\').trigger(\'click\');">' . __( 'here', 'asp-acf' ) . '</a>' ) . '</p>';
		}
	}

	function sanitize_settings( $output, $input ) {

		$output['acf_enabled'] = isset( $input['acf_enabled'] ) ? 1 : 0;
		$output['acf_show_fields_below'] = isset( $input['acf_show_fields_below'] ) ? 1 : 0;

		$fields = new ASPACF_Fields();
		$fields->get_from_post();
		$fields->save();

		return $output;
	}

	function field_display( $field, $field_value ) {
		$ret = array();
		switch ( $field ) {
			case 'acf_enabled':
				$ret['field']      = 'checkbox';
				$ret['field_name'] = $field;
				break;
			case 'acf_show_fields_below':
				$ret['field']      = 'checkbox';
				$ret['field_name'] = $field;
				break;
		}
		if ( ! empty( $ret ) ) {
			return $ret;
		} else {
			return $field;
		}
	}

	function register_settings() {
		$this->helper->add_settings_section( __( 'Additional Custom Fields Settings', 'asp-acf' ), array( $this, 'documentation_link' ) );
		$this->helper->add_settings_field( 'acf_enabled', __( 'Enable Additional Custom Fields', 'asp-acf' ), __( 'Enables additional custom fields.', 'asp-acf' ) );
		$this->helper->add_settings_field( 'acf_show_fields_below', __( 'Display Custom Fields Below Card Input Section', 'asp-acf' ), __( 'Select this option to display custom fields beneath the card input fields.', 'asp-acf' ) );
	}

	function documentation_link() {
		echo sprintf( _x( 'You can find additional documentation %s.', '%s is replaced by URL with word "here"', 'asp-acf' ), '<a href="https://s-plugins.com/stripe-payments-additional-custom-fields-addon/" target="_blank">' . _x( 'here', 'Is a part of "You can find additional documentation _here_"', 'asp-acf' ) . '</a>' );
		if ( ! $this->helper->addon->ASPMain->get_setting( 'custom_field_enabled' ) ) {
			//Custom fields are disabled in core plugin. Let's display warning message
			echo '<p style="color:red;">' . sprintf( __( 'Custom Field functionality is disabled in core plugin. You need to enable it %s in order for Additional Custom Fields to be displayed.', 'asp-acf' ), '<a href="#acf" onclick="jQuery(\'a.nav-tab[data-tab-name=advanced\').trigger(\'click\');">' . __( 'here', 'asp-acf' ) . '</a>' ) . '</p>';
		}
	}

	function after_tabs_menu() {
		?>
<a href="#<?php echo $this->helper->addon->SETTINGS_TAB_NAME; ?>"
	data-tab-name="<?php echo $this->helper->addon->SETTINGS_TAB_NAME; ?>"
	class="nav-tab"><?php echo __( 'Additional Custom Fields', 'asp-acf' ); ?></a>
		<?php
		wp_register_script( 'asp_acf_admin_script', plugin_dir_url( $this->helper->addon->file ) . '/admin/js/asp-acf-admin.js', array( 'jquery-ui-datepicker' ), $this->helper->addon->VERSION, true );
	}

	function after_styles() {
		wp_register_style( 'asp-acf-jquery-ui', 'https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.11.4/themes/smoothness/jquery-ui.min.css' );
		?>
<style>
.asp_acf_input_tpl,
.asp_acf_tpl {
	display: none;
}

.asp_acf_input_control a.button {
	margin-left: 7px;
	margin-top: -3px;
	width: 30px;
	height: 28px;
	line-height: 2;
	min-height: 28px;
	padding: 0;
	text-align: center;
}

.asp_acf_input_control span.asp_acf_field_id {
	float: right;
	color: #ababab;
	margin-right: 10px;
	font-size: 80%;
}

.asp_acf_input_control i.dashicons {
	vertical-align: middle;
	line-height: 1rem;
}

table.asp_acf_table th {
	padding: 15px 10px 5px 0;
}

table.asp_acf_table td {
	padding: 5px 5px;
}

table.asp_acf_table textarea {
	width: 100%;
}

table.asp_acf_table input[type="text"] {
	width: 100%;
}

table.asp_acf_table input.asp-acf-input-short {
	max-width: 200px;
	display: inline;
}

table.asp-acf-dropdown-items-table td {
	padding: 0;
	padding-bottom: 5px;
	padding-right: 5px;
}

.asp_acf_help {
	position: relative;
	display: block;
	cursor: help;
	float: right;
	color: #cdcdcd;
}

.asp_acf_help .dashicons {
	vertical-align: super;
}

.asp_acf_help:hover {
	color: blue;
}

.asp_acf_help:hover .asp_acf_help_text {
	visibility: visible;
}

.asp_acf_help .asp_acf_help_text {
	min-width: 200px;
	top: -10px;
	left: 50%;
	transform: translate(-50%, -100%);
	padding: 7px 10px;
	color: #fff;
	background-color: #111;
	font-weight: normal;
	font-size: 13px;
	border-radius: 8px;
	position: absolute;
	z-index: 99999999;
	box-sizing: border-box;
	box-shadow: 0 1px 8px rgba(0, 0, 0, 0.5);
	visibility: hidden;
	text-align: center;
}

.asp_acf_help .asp_acf_help_text::after {
	content: "";
	position: absolute;
	top: 100%;
	left: 50%;
	margin-left: -5px;
	border-width: 5px;
	border-style: solid;
	border-color: black transparent transparent transparent;
}

a.button.asp-acf-inner-icon-button {
	position: absolute;
	top: 0;
	right: 0;
	width: 30px;
	text-align: center;
	padding: 0;
}

.asp-acf-inner-icon-button span.dashicons {
	line-height: inherit;
	font-size: 1rem;
}

.acf-fields-container a.button {
	text-align: center;
}

.acf-fields-container a.button i.dashicons {
	vertical-align: middle;
}

a.button.asp-acf-dropdown-delete-item i.dashicons {
	line-height: 1rem;
}

@media screen and (max-width: 782px) {
	a.button.asp-acf-inner-icon-button {
		display: block;
		float: right;
		height: 30px;
		width: 30px;
	}

	.asp-acf-inner-icon-button span.dashicons {
		line-height: 1.5rem;
		height: 100%;
		width: 100%;
		display: inline-block;
		font-size: 1.2rem;
	}

	.asp_acf_help .asp_acf_help_text::after {
		top: 50%;
		left: 100%;
		/* To the right of the tooltip */
		margin-left: auto;
		margin-top: -5px;
		border-width: 5px;
		border-style: solid;
		border-color: transparent transparent transparent black;
	}

	.asp_acf_help .asp_acf_help_text {
		min-width: 200px;
		top: 50%;
		right: 100%;
		left: auto;
		margin-right: 10px;
		transform: translate(0, -50%);
	}

	.asp_acf_help .dashicons {
		vertical-align: bottom;
	}

	a.button.asp-acf-add-new-btn {
		margin: 0;
	}

	.asp_acf_input_control a.button {
		line-height: 2;
	}
}
</style>
		<?php
	}

	function after_form() {
		?>
<div class="asp_acf_content_tpl_default asp_acf_tpl">
	<table class="form-table asp_acf_table">
		<tr valign="top">
			<th scope="row"><?php _e( 'Label', 'asp-acf' ); ?><div class="asp_acf_help"><i
						class="dashicons dashicons-editor-help"></i>
					<div class="asp_acf_help_text"><?php _e( 'The input field label.', 'asp-acf' ); ?></div>
				</div>
			</th>
			<td>
				<input type="text" name="asp_acf_field_label[%_field_id_%]" value="%_field_label_%" required />
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e( 'Description', 'asp-acf' ); ?>
				<div class="asp_acf_help"><i class="dashicons dashicons-editor-help"></i><span
						class="asp_acf_help_text"><?php _e( "The input field help text that will be shown below the input field. HTML allowed. Leave empty if you don't want to show any help text.", 'asp-acf' ); ?></span>
				</div>
			</th>
			<td>
				<textarea name="asp_acf_field_description[%_field_id_%]" rows="3">%_field_descr_%</textarea>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e( 'Required', 'asp-acf' ); ?>
				<div class="asp_acf_help"><i class="dashicons dashicons-editor-help"></i><span
						class="asp_acf_help_text"><?php _e( 'Check this if you want to make the field a required field.', 'asp-acf' ); ?></span>
				</div>
			</th>
			<td>
				<span></span>
				<input class="asp_acf_field_required" data-wpspsc-cci-checked="%_field_required_%"
					name="asp_acf_field_required[%_field_id_%]" type="checkbox" value="1">
			</td>
		</tr>
	</table>
</div>

<table class="asp_acf_content_tpl_cb asp_acf_tpl">
	<tr valign="top">
		<th scope="row"><?php _e( 'Checked By Default', 'asp-acf' ); ?>
			<div class="asp_acf_help"><i class="dashicons dashicons-editor-help"></i>
				<div class="asp_acf_help_text">
					<?php _e( 'If enabled, checkbox will be checked by default when field is displayed.', 'asp-acf' ); ?>
				</div>
			</div>
		</th>
		<td>
			<input type="checkbox" name="asp_acf_field_opts[%_field_id_%][checked]" data-wpspsc-cci-checked="%_opt_checked_%" value="1">
		</td>
	</tr>
</table>

<table class="asp_acf_content_tpl_datepicker asp_acf_tpl">
	<tr valign="top">
		<th scope="row"><?php _e( 'Date Format', 'asp-acf' ); ?>
			<div class="asp_acf_help"><i class="dashicons dashicons-editor-help"></i>
				<div class="asp_acf_help_text">
					<?php _e( 'Date format. Supports following values:', 'asp-acf' ); ?>
					<ul>
						<li><?php _e( 'd - day of month (no leading zero)', 'asp-acf' ); ?></li>
						<li><?php _e( 'dd - day of month (two digit)', 'asp-acf' ); ?></li>
						<li><?php _e( 'm - month of year (no leading zero)', 'asp-acf' ); ?></li>
						<li><?php _e( 'mm - month of year (two digit)', 'asp-acf' ); ?></li>
						<li><?php _e( 'y - year (two digit)', 'asp-acf' ); ?></li>
						<li><?php _e( 'yy - year (four digit)', 'asp-acf' ); ?></li>
					</ul>
					<?php _e( 'You can use any other character or symbol as separator. Example: dd/mm/yy or d.m.y', 'asp-acf' ); ?>
				</div>
			</div>
		</th>
		<td>
			<input type="text" name="asp_acf_field_opts[%_field_id_%][date_format]" value="%_opt_date_format_%"
				required />
		</td>
	</tr>
	<tr valign="top">
		<th scope="row"><?php _e( 'Limit Date Range', 'asp-acf' ); ?><div class="asp_acf_help"><i
					class="dashicons dashicons-editor-help"></i>
				<div class="asp_acf_help_text">
					<?php _e( 'Select date range which would limit date selection. Leave it blank to not set any limitations.', 'asp-acf' ); ?>
				</div>
			</div>
		</th>
		<td>
			<label><?php _e( 'From', 'asp-acf' ); ?> <input type="text" class="asp-acf-input-short asp-acf-datepicker"
					name="asp_acf_field_opts[%_field_id_%][date_start]" value="%_opt_date_start_%" /></label>
			<label><?php _e( 'To', 'asp-acf' ); ?> <input type="text" class="asp-acf-input-short asp-acf-datepicker"
					name="asp_acf_field_opts[%_field_id_%][date_end]" value="%_opt_date_end_%" /></label>
		</td>
	</tr>
</table>

<table class="asp_acf_content_tpl_dropdown asp_acf_tpl">
	<tr valign="top">
		<th scope="row"><?php esc_html_e( 'Items', 'asp-acf' ); ?>
		</th>
		<td>
			<table class="asp-acf-dropdown-items-table">
			</table>
			<button type="button" data-field-id="%_field_id_%"
				class="button asp-acf-dropdown-new-item"><?php esc_html_e( 'Add Item', 'asp-acf' ); ?></button>
		</td>
	</tr>
</table>

<table class="asp_acf_dropdown_item_tpl asp_acf_tpl">
	<tr>
		<td style="position: relative;">
			<input type="text" name="asp_acf_field_opts[%_field_id_%][items][%_item_id_%]" value="%_item_value_%">
			<a href="#0" data-field-id="%_field_id_%" data-item-id="%_item_id_%"
				class="button asp-acf-inner-icon-button asp-acf-dropdown-delete-item"
				title="<?php esc_attr_e( 'Delete item', 'asp-acf' ); ?>">
				<i class="dashicons dashicons-trash"></i>
			</a>
		</td>
	</tr>
</table>

<div class="postbox asp_acf_input_tpl">
	<h3 class="hndle">
		<label for="title">%_title_%</label>
		<span class="asp_acf_input_control">
			<a class="button alignright asp_acf_del_input_btn" title="<?php _e( 'Delete Field', 'asp-acf' ); ?>"
				style="font-weight: normal;">
				<i class="dashicons dashicons-trash"></i>
			</a>
			<a class="button alignright asp_acf_down_input_btn" title="<?php _e( 'Move Down', 'asp-acf' ); ?>"
				style="font-weight: normal;">
				<i class="dashicons dashicons-arrow-down-alt"></i>
			</a>
			<a class="button alignright asp_acf_up_input_btn" title="<?php _e( 'Move Up', 'asp-acf' ); ?>"
				style="font-weight: normal;">
				<i class="dashicons dashicons-arrow-up-alt"></i>
			</a>
			<span class="asp_acf_field_id">ID: %_field_id_%</span>
		</span>
	</h3>
	<div class="inside">
		<input type="hidden" name="asp_acf_field_id[]" value="%_field_id_%">
		<input type="hidden" name="asp_acf_field_pos[%_field_id_%]" value="%_field_pos_%">
		<input type="hidden" name="asp_acf_field_type[%_field_id_%]" value="%_input_type_%">
		%_content_%
	</div>
</div>
<div class="asp_acf_single_item_radio asp_acf_tpl">
	<div>
		<input type="text" name="asp_acf_field_opts[]" value="" size="50">
		<a class="button asp_acf_remove_item_btn">
			<i class="dashicons dashicons-trash" style="vertical-align: bottom;"></i>
		</a>
	</div>
</div>
		<?php
	}

	function after_tabs() {
		$fields = new ASPACF_Fields();
		foreach ( $fields->fields as $key => $value ) {
			$fields->fields[ $key ]['id'] = $key;
		}
		usort(
			$fields->fields,
			function( $a, $b ) {
				return $a['pos'] - $b['pos'];
			}
		);
		wp_localize_script(
			'asp_acf_admin_script',
			'aspACFData',
			array(
				'fields'  => $fields->fields,
				'defOpts' => array(
					'datepicker' => array( 'date_format' => 'dd/mm/yy' ),
					'cb'         => array( 'checked' => false ),
				),
				'opts'    => array( 'firstDay' => get_option( 'start_of_week' ) ),
				'str'     => array(
					'confirmDelete'     => __( 'Are you sure want to delete this field?', 'asp-acf' ),
					'confirmItemDelete' => __( 'Are you sure want to delete this item?', 'asp-acf' ),
				),
			)
		);
		wp_enqueue_style( 'asp-acf-jquery-ui' );
		wp_enqueue_script( 'asp_acf_admin_script' );
		?>
<div class="wp-asp-tab-container asp-custom-msg-container acf-fields-container"
	data-tab-name="<?php echo $this->helper->addon->SETTINGS_TAB_NAME; ?>">
		<?php do_settings_sections( $this->plugin_slug . '-' . $this->helper->addon->SETTINGS_TAB_NAME ); ?>
	<div id="poststuff">
		<div id="post-body">
			<div id="asp_acf_inputs_container"></div>
			<select class="asp-acf-cf-select" name="asp-acf-cf-select">
				<option value="text"><?php esc_attr_e( 'Text', 'asp-acf' ); ?></option>
				<option value="cb"><?php esc_attr_e( 'Checkbox', 'asp-acf' ); ?></option>
				<option value="datepicker"><?php esc_attr_e( 'Datepicker', 'asp-acf' ); ?></option>
				<option value="dropdown"><?php esc_attr_e( 'Dropdown', 'asp-acf' ); ?></option>
			</select>
			<a class="button asp-acf-add-new-btn">
				<i class="dashicons dashicons-plus"></i> <?php _e( 'Add New', 'asp-acf' ); ?></a>
		</div>
	</div>

	<table class="asp_acf_tpl">
		<tbody class="asp_acf_content_tpl_radio">
			<tr valign="top">
				<th scope="row"><?php _e( 'Items', 'asp-acf' ); ?></th>
				<td>
					<div>
						<p class="description">Specify items.</p>
						<a class="button asp-acf-radio-add-btn"><i class="dashicons dashicons-plus"
								style="vertical-align: bottom;"></i> <?php _e( 'Add Items', 'asp-acf' ); ?></a>
						<p></p>
					</div>
				</td>
			</tr>
		</tbody>
	</table>
</div>
		<?php
	}

	public function email_tags_descr( $email_tags ) {
		$email_tags['Additional Custom Fields Addon tags'] = '';
		$email_tags['{custom_field_N}']                    = __( 'Displays custom field\'s name and value. N is ID of the field', 'asp-acf' );
		$email_tags['{custom_field_name_N}']               = __( 'Displays custom field\'s name only. N is ID of the field', 'asp-acf' );
		$email_tags['{custom_field_value_N}']              = __( 'Displays custom field\'s name value only. N is ID of the field', 'asp-acf' );
		return $email_tags;
	}

}
