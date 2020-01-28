<?php

/**
 * Include assets after buddyforms
 */
function buddyforms_pods_include_assets() {
	wp_enqueue_style( 'buddyforms_pods_css', BUDDYFORMS_PODS_ASSETS_URL . "css/style.css", array(), BuddyFormsPODS::getVersion() );
	wp_enqueue_script( 'buddyforms_pods_js', BUDDYFORMS_PODS_ASSETS_URL . 'js/script.js', array( 'jquery', 'buddyforms-js' ), BuddyFormsPODS::getVersion() );
}

add_action( 'buddyforms_front_js_css_after_enqueue', 'buddyforms_pods_include_assets' );

/**
 * Include the pods field into JS options
 *
 * @param $buddyforms_global_js_data
 * @param $form_slug
 *
 * @return array()
 */
function add_pods_field_to_global( $buddyforms_global_js_data, $form_slug ) {
	if ( ! empty( $form_slug ) && ! empty( $buddyforms_global_js_data[ $form_slug ] ) && ! empty( $buddyforms_global_js_data[ $form_slug ]['form_fields'] ) ) {
		$new_fields = array();
		foreach ( $buddyforms_global_js_data[ $form_slug ]['form_fields'] as $field_id => $field ) {
			if ( $field['type'] === 'pods-group' ) {
				if ( ! empty( $field['pods_group'] ) ) {
					$pods = pods( $field['pods_group'] );
					if ( ! empty( $pods->fields ) ) {
						foreach ( $pods->fields as $pod_field_id => $pod_field ) {
							$pod_field['slug']           = $pod_field['name'];
							$pod_field['name']           = $pod_field['label'];
							$new_fields[ $pod_field_id ] = $pod_field;
						}
					}
				} else {
					$new_fields[ $field_id ] = $field;
				}
			} else {
				$new_fields[ $field_id ] = $field;
			}
		}
		if ( ! empty( $new_fields ) ) {
			$buddyforms_global_js_data[ $form_slug ]['form_fields'] = $new_fields;
		}

	}

	return $buddyforms_global_js_data;
}

add_filter( 'buddyforms_global_localize_scripts', 'add_pods_field_to_global', 10, 2 );

/**
 * Function to validate the pods fields this function is called from JS
 *
 * @return string
 */
function buddyforms_pods_ajax_validate() {
	try {
		if ( ! ( is_array( $_POST ) && defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			die();
		}
		if ( ! isset( $_POST['action'] ) || ! isset( $_POST['nonce'] ) || empty( $_POST['form_slug'] ) || empty( $_POST['field_data'] ) || empty( $_POST['pod_target'] ) || empty( $_POST['field_name'] ) ) {
			die();
		}
		if ( ! wp_verify_nonce( $_POST['nonce'], 'fac_drop' ) ) {
			die();
		}

		$form_slug  = buddyforms_sanitize_slug( $_POST['form_slug'] );
		$field_data = sanitize_text_field( $_POST['field_data'] );
		$field_name = sanitize_text_field( $_POST['field_name'] );
		$pod_target = sanitize_text_field( $_POST['pod_target'] );

		$result = false;
		$pod    = pods( $pod_target );

		if ( ! empty( $pod ) ) {
			$pod_fields = $pod->fields;
			if ( isset( $pod_fields[ $field_name ] ) ) {
				$pod_field      = $pod_fields[ $field_name ];
				$pod_field_type = $pod_field['type'];
				PodsForm::field_loader( $pod_field_type );
				$result = PodsForm::$loaded[ $pod_field_type ]->validate( $field_data, $field_name, $pod_field['options'], $pod_fields, $pod, null, array() );
			}
		}

		wp_send_json( $result, 200 );
	} catch ( Exception $ex ) {
		error_log( 'BuddyFormsPods::' . $ex->getMessage() );
	}
	die();
}

add_action( 'wp_ajax_buddyforms_pods_validate', 'buddyforms_pods_ajax_validate' );


function buddyforms_pods_server_validation( $valid, $form_slug ) {
	global $buddyforms;

	$form = $buddyforms[ $form_slug ];

	if ( isset( $form['form_fields'] ) ) {
		$internal_result = array();
		$global_error    = ErrorHandler::get_instance();
		foreach ( $form['form_fields'] as $key => $form_field ) {
			if ( $form_field['type'] === 'pods-group' || $form_field['type'] === 'pods-field' ) {
				if ( ! empty( $form_field['pods_group'] ) ) {
					$pod = pods( $form_field['pods_group'] );
					bfPodsAPI::destroy_instance( $form_field['pods_group'] );
					$pods_api = bfPodsAPI::init( $form_field['pods_group'] );
					if ( ! empty( $pod ) ) {
						$pod_fields = $pod->fields;
						foreach ( $pod->fields as $field_id => $pod_field ) {
							$field_name        = $pod_field['name'];
							$field_label       = $pod_field['label'];
							$field_data        = isset( $_POST[ $field_name ] ) ? $_POST[ $field_name ] : '';
							$pod_field         = $pod_fields[ $field_name ];
							$is_valid          = $pods_api->handle_field_validation( $field_data, $field_name, $pod_field, $pod_fields, $pod, array( 'form_slug' => $form_slug ) );
							$internal_result[] = $is_valid;
							if ( empty( $is_valid ) ) {
//								$global_error->add_error( new BuddyForms_Error( 'buddyforms_form_' . $form_slug, sprintf( __( '%s has an error', 'pods' ), $field_label ), $field_name ) );
							}
						}
					}
				}
			}
		}
		if ( ! empty( $internal_result ) ) {
			$internal_result = array_unique( $internal_result );
			$valid           = array_key_exists( 'true', array_keys( $internal_result ) );
		}
	}

	return $valid;
}

add_filter( 'buddyforms_form_custom_validation', 'buddyforms_pods_server_validation', 2, 2 );
