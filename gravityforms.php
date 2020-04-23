<?php
/**
 * Gravity Forms Bootstrap Hooks
 *
 * Actions & filters for using Gravityforms in your Bootstrap enabled theme.
 *
 * @package     WordPress
 * @subpackage  GravityForms
 * @link        https://github.com/MoshCat/gravityforms-bootstrap-hooks
 */

namespace App;

if ( class_exists( 'GFCommon' ) ) {

	/**
	 * GF: Iban validation
	 */
	add_filter( 'gform_field_validation', function( $result, $value, $form, $field ) {
		if ( $field->cssClass === 'verify-iban' ) { // phpcs:ignore
			if ( ! preg_match( '/[a-zA-Z]{2}[0-9]{2}[a-zA-Z0-9]{4}[0-9]{7}([a-zA-Z0-9]?){0,16}/', $value ) ) {
				$result['is_valid'] = false;
				$result['message']  = 'Invalid IBAN';
			}
		}
		return $result;
	}, 10, 4 );

	/**
	 * Disable Gravity Forms CSS.
	 */
	add_filter( 'pre_option_rg_gforms_disable_css', '__return_true' );

	/**
	 * Enable HTML5.
	 */
	add_filter( 'pre_option_rg_gforms_enable_html5', '__return_true' );

	/**
	 * Enable the shortcode preview
	 */
	add_filter( 'gform_shortcode_preview_disabled', '__return_false' );

	/**
	 * Disable Gravity Forms CSS in Admin and allow custom styles in shortcode preview.
	 */
	remove_filter( 'tiny_mce_before_init', array( 'GFForms', 'modify_tiny_mce_4' ), 20 );

	/**
	 * Style Gravity Forms preview pages.
	 */
	add_filter(
		'gform_preview_styles', function ( $styles, $form ) {
			wp_register_style( 'gf_styles', get_stylesheet_directory_uri() . '/dist/css/style.min.css', array(), '1.0' );
			$styles = array( 'gf_styles' );
			return $styles;
		}, 10, 2
	);

	/**
	 * Grant Editors access to Gravityforms.
	 */
	add_action(
		'admin_init', function () {
			$role = get_role( 'editor' );
			$role->add_cap( 'gform_full_access' ); // To disable use: $role->remove_cap.
		}
	);

	/**
	 * Place Gravityforms jQuery In Footer.
	 */
	add_filter(
		'gform_cdata_open', function ( $content = '' ) {
			$content = 'document.addEventListener("DOMContentLoaded", function() { ';
			return $content;
		}
	);
	add_filter(
		'gform_cdata_close', function ( $content = '' ) {
			$content = ' }, false);';
			return $content;
		}
	);
	add_filter( 'gform_init_scripts_footer', '__return_true' );

	/**
	 * Add .form-group to .gfield.
	 */
	add_filter(
		'gform_field_css_class', function ( $classes, $field, $form ) {
			$classes .= ' form-group';
			return $classes;
		}, 10, 3
	);

	/**
	 * Modify the fields classes to Bootstrap classes.
	 */
	add_filter(
		'gform_field_content', function ( $content, $field, $value, $lead_id, $form_id ) {
			// Add .form-control to most inputs.
			$exclude_formcontrol = array(
				'hidden',
				'post_image',
				'email',
				'fileupload',
				'list',
				'multiselect',
				'select',
				'html',
				'address',
				'post_category',
			);
			if ( ! in_array( $field['type'], $exclude_formcontrol, true ) ) {
				$content = str_replace( 'class=\'small', 'class=\'form-control form-control-sm', $content );
				$content = str_replace( 'class=\'medium', 'class=\'form-control', $content );
				$content = str_replace( 'class=\'large', 'class=\'form-control form-control-lg', $content );
			}
			// Select.
			if ( 'select' === $field['type'] || 'multiselect' === $field['type'] || 'post_category' === $field['type'] ) {
				$content = str_replace( 'class=\'small', 'class=\'custom-select custom-select-sm', $content );
				$content = str_replace( 'class=\'medium', 'class=\'custom-select', $content );
				$content = str_replace( 'class=\'large', 'class=\'custom-select custom-select-lg', $content );
			}
			// Textarea.
			if ( 'textarea' === $field['type'] || 'post_content' === $field['type'] || 'post_excerpt' === $field['type'] ) {
				$content = str_replace( 'class=\'textarea small', 'class=\'form-control form-control-sm textarea', $content );
				$content = str_replace( 'class=\'textarea medium', 'class=\'form-control textarea', $content );
				$content = str_replace( 'class=\'textarea large', 'class=\'form-control form-control-lg textarea', $content );
			}
			// Checkbox.
			if ( 'checkbox' === $field['type'] ) {
				$content = str_replace( 'li class=\'', 'li class=\'custom-control custom-checkbox ', $content );
				$content = str_replace( '<input ', '<input class=\'custom-control-input\' ', $content );
				$content = str_replace( '<label for', '<label class=\'custom-control-label\' for', $content );
			}
			// Radio.
			if ( 'radio' === $field['type'] ) {
				$content = str_replace( 'li class=\'', 'li class=\'custom-control custom-radio ', $content );
				$content = str_replace( '<input name', '<input class=\'custom-control-input\' name', $content );
				$content = str_replace( '<input id', '<input class=\'form-control form-control-sm\' id', $content );
				$content = str_replace( '<label for', '<label class=\'custom-control-label\' for', $content );
			}
			// Fileupload. Add class 'preview' to the field to enable the image preview
			if ( 'fileupload' === $field['type'] || 'post_image' === $field['type'] ) {
				if ( ! is_admin() && false === $field["multipleFiles"] ) {
					$required    = ( $field['isRequired'] ) ? '<span class="gfield_required">*</span>' : '';
					$max_upload  = ( $field['maxFileSize'] ) ? ( $field['maxFileSize'] * 1048576 ) : 67108864;
					$preview     = ( $field['cssClass'] === 'preview' ) ? '<img id="output_' . $form_id . '_' . $field['id'] . '">' : '';
					$content = '<label class="gfield_label">' . $field['label'] . $required . '</label>';
					$content .= '<div class="ginput_container ginput_container_fileupload">';
					$content .= '<div class="custom-file">';
					$content .= '<input type="hidden" name="MAX_FILE_SIZE" value="' . $max_upload . '">';
					$content .= '<input name="input_' . $field['id'] . '" id="input_' . $form_id . '_' . $field['id'] . '" type="file" class="custom-file-input" aria-describedby="validation_message_' . $form_id . '_' . $field['id'] . ' live_validation_message_' . $form_id . '_' . $field['id'] . ' extensions_message_' . $form_id . '_' . $field['id'] . '" onchange="javascript:gformValidateFileSize( this, ' . $max_upload . ' );">';
					$content .= '<label class="custom-file-label" for="input_' . $form_id . '_' . $field['id'] . '">' . $field['label'] . '</label>';
					$content .= '<span id="extensions_message_' . $form_id . '_' . $field['id'] . '" class="screen-reader-text"></span>';
					$content .= '<div class="validation_message" id="live_validation_message_' . $form_id . '_' . $field['id'] . '"></div>';
					$content .= '</div>';
					$content .= $preview;
					$content .= '</div>';
					$content .= '<script>
					document.getElementById(\'input_' . $form_id . '_' . $field['id'] . '\').addEventListener(\'change\', function (e) {
						// Show filename after upload
						var fileName = e.target.files[0].name;
						var nextSibling = e.target.nextElementSibling;
						nextSibling.innerText = fileName;
						// Create preview
						var input = e.target;
						var reader = new FileReader();
						reader.onload = function () {
							var dataURL = reader.result;
							var output = document.getElementById(\'output_' . $form_id . '_' . $field['id'] . '\');
							output.src = dataURL;
							output.className = \'preview_img\';
						};
						reader.readAsDataURL(input.files[0]);
					})
					</script>';
				} else {
					$content = str_replace( 'class=\'button', 'class=\'btn btn-primary btn-sm', $content );
				}
			}
			// Date & Time.
			if ( 'date' === $field['type'] || 'time' === $field['type'] ) {
				$content = str_replace( '<select', '<select class=\'custom-select\'', $content );
				$content = str_replace( 'type=\'number\'', 'type=\'number\' class=\'form-control\'', $content );
				$content = str_replace( 'class=\'datepicker medium', 'class=\'form-control datepicker', $content );
			}
			// Complex.
			if ( 'name' === $field['type'] || 'address' === $field['type'] || 'email' === $field['type'] || 'password' === $field['type'] ) {
				$content = str_replace( 'class=\'ginput_complex', 'class=\'ginput_complex form-row', $content );
				$content = str_replace( 'class=\'ginput_left', 'class=\'ginput_left col-6', $content );
				$content = str_replace( 'class=\'ginput_right', 'class=\'ginput_right col-6', $content );
				$content = str_replace( 'class=\'ginput_full', 'class=\'ginput_full col-12', $content );
				// Password.
				if ( 'password' === $field['type'] ) {
					$content = str_replace( 'type=\'password\'', 'type="password" class=\'form-control\' ', $content );
				}
				// Email
				if ( 'email' === $field['type'] ) {
					$content = str_replace( '<input class=\'', '<input class=\'form-control\' ', $content );
					$content = str_replace( 'class=\'small', 'class=\'small form-control form-control-sm', $content );
					$content = str_replace( 'class=\'medium', 'class=\'medium form-control', $content );
					$content = str_replace( 'class=\'large', 'class=\'large form-control form-control-lg', $content );
				}
				// Name & Address.
				if ( 'name' === $field['type'] || 'address' === $field['type'] ) {
					$content = str_replace( '<input ', '<input class=\'form-control\' ', $content );
					$content = str_replace( '<select ', '<select class=\'custom-select\' ', $content );
				}
			}
			// Consent.
			if ( 'consent' === $field['type'] ) {
				$content = str_replace( 'ginput_container_consent', 'ginput_container_consent custom-control custom-checkbox', $content );
				$content = str_replace( 'gfield_consent_label', 'gfield_consent_label custom-control-label', $content );
				$content = str_replace( 'type=\'checkbox\'', 'type=\'checkbox\' class=\'custom-control-input\' ', $content );
			}
			// List.
			if ( 'list' === $field['type'] ) {
				$content = str_replace( 'type=\'text\'', 'type=\'text\' class=\'form-control\' ', $content );
			}
			return $content;
		}, 10, 5
	);

	/**
	 * Change the main validation message.
	 */
	add_filter(
		'gform_validation_message', function ( $message, $form ) {
			return "<div class='validation_error alert alert-danger'>" . esc_html__( 'There was a problem with your submission.', 'gravityforms' ) . ' ' . esc_html__( 'Errors have been highlighted below.', 'gravityforms' ) . '</div>'; // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
		}, 10, 2
	);

	/**
	 * Change classes on Submit button.
	 */
	add_filter(
		'gform_submit_button',
		function ( $button, $form ) {
			$button = str_replace( 'class=\'gform_button', 'class=\'gform_button btn btn-outline-primary btn-block', $button );
			return $button;
		}, 10, 2
	);

	/**
	 * Change classes on Next button.
	 */
	add_filter(
		'gform_next_button', function ( $button, $form ) {
			$button = str_replace( 'class=\'gform_next_button', 'class=\'gform_next_button btn btn-secondary', $button );
			return $button;
		},
		10,
		2
	);

	/**
	 * Change classes on Previous button.
	 */
	add_filter(
		'gform_previous_button', function ( $button, $form ) {
			$button = str_replace( 'class=\'gform_previous_button', 'class=\'gform_previous_button btn btn-outline-secondary', $button );
			return $button;
		}, 10, 2
	);

	/**
	 * Change classes on progressbars
	 */
	add_filter(
		'gform_progress_bar', function ( $progress_bar, $form, $confirmation_message ) {
			$progress_bar = str_replace( 'progress_wrapper', 'progress_wrapper form-group', $progress_bar );
			$progress_bar = str_replace( 'gf_progressbar', 'gf_progressbar progress', $progress_bar );
			$progress_bar = str_replace( 'progress_percentage', 'progress_percentage progress-bar progress-bar-striped progress-bar-animated', $progress_bar );
			$progress_bar = str_replace( 'percentbar_blue', 'percentbar_blue bg-primary', $progress_bar );
			$progress_bar = str_replace( 'percentbar_gray', 'percentbar_gray bg-secondary', $progress_bar );
			$progress_bar = str_replace( 'percentbar_green', 'percentbar_green bg-success', $progress_bar );
			$progress_bar = str_replace( 'percentbar_orange', 'percentbar_orange bg-warning', $progress_bar );
			$progress_bar = str_replace( 'percentbar_red', 'percentbar_red bg-danger', $progress_bar );
			return $progress_bar;
		}, 10, 3
	);

	/**
	 * Hide Gravityforms Spinner.
	 */
	add_filter(
		'gform_ajax_spinner_url', function () {
			return 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
		}
	);
}
