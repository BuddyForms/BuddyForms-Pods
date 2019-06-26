<?php

/*
 * Add PODS form elementrs in the form elements select box
 */
function buddyforms_pods_elements_to_select( $elements_select_options ) {
	global $post;

	if ( $post->post_type != 'buddyforms' ) {
		return;
	}
	$elements_select_options['pods']['label']                = 'PODS';
	$elements_select_options['pods']['class']                = 'bf_show_if_f_type_post';
	$elements_select_options['pods']['fields']['pods-field'] = array(
		'label' => __( 'PODS Field', 'buddyforms' ),
	);

	$elements_select_options['pods']['fields']['pods-group'] = array(
		'label' => __( 'PODS Fields', 'buddyforms' ),
	);

	return $elements_select_options;
}

add_filter( 'buddyforms_add_form_element_select_option', 'buddyforms_pods_elements_to_select', 1, 2 );


/*
 * Create the new PODS Form Builder Form Elements
 *
 */
function buddyforms_pods_form_builder_form_elements( $form_fields, $form_slug, $field_type, $field_id ) {
	global $field_position, $buddyforms;


	$pods            = pods_api()->load_pods( array( 'fields' => false ) );
	$pod_form_fields = array();
	$pods_list       = array();
	foreach ( $pods as $pod_key => $pod ) {
		$pods_list[ $pod['name'] ] = $pod['label'];
		foreach ( $pod['fields'] as $pod_fields_key => $field ) {
			$pod_form_fields[ $pod['name'] ][ $field['name'] ] = $field['label'];
		}
	}

	switch ( $field_type ) {
		case 'pods-field':

			unset( $form_fields );

			$pods_group = 'false';
			if ( isset( $buddyforms[ $form_slug ]['form_fields'][ $field_id ]['pods_group'] ) ) {
				$pods_group = $buddyforms[ $form_slug ]['form_fields'][ $field_id ]['pods_group'];
			}

			$form_fields['general']['pods_group'] = new Element_Select( '', "buddyforms_options[form_fields][" . $field_id . "][pods_group]", $pods_list, array(
				'value'         => $pods_group,
				'class'         => 'bf_pods_field_group_select',
				'data-field_id' => $field_id
			) );

			$pods_field = 'false';
			if ( isset( $buddyforms[ $form_slug ]['form_fields'][ $field_id ]['pods_field'] ) ) {
				$pods_field = $buddyforms[ $form_slug ]['form_fields'][ $field_id ]['pods_field'];
			}
			$field_select                         = $pod_form_fields['devs'];
			$form_fields['general']['pods_field'] = new Element_Select( '', "buddyforms_options[form_fields][" . $field_id . "][pods_field]", $field_select, array(
				'value' => $pods_field,
				'class' => 'bf_pods_fields_select bf_pods_' . $field_id
			) );

			$name = 'PODS-Field';
			if ( $pods_field && $pods_field != 'false' ) {
				$name = 'PODS Field: ' . $pods_field;
			}
			$form_fields['general']['name'] = new Element_Hidden( "buddyforms_options[form_fields][" . $field_id . "][name]", $name );

			$form_fields['general']['slug']  = new Element_Hidden( "buddyforms_options[form_fields][" . $field_id . "][slug]", 'pods_field_key' );
			$form_fields['general']['type']  = new Element_Hidden( "buddyforms_options[form_fields][" . $field_id . "][type]", $field_type );
			$form_fields['general']['order'] = new Element_Hidden( "buddyforms_options[form_fields][" . $field_id . "][order]", $field_position, array( 'id' => 'buddyforms/' . $form_slug . '/form_fields/' . $field_id . '/order' ) );
			break;
		case 'pods-group':

			unset( $form_fields );


			$pods_group = 'false';
			if ( isset( $buddyforms[ $form_slug ]['form_fields'][ $field_id ]['pods_group'] ) ) {
				$pods_group = $buddyforms[ $form_slug ]['form_fields'][ $field_id ]['pods_group'];
			}
			$form_fields['general']['pods_group'] = new Element_Select( '', "buddyforms_options[form_fields][" . $field_id . "][pods_group]", $pods_list, array( 'value' => $pods_group ) );

			$name = 'PODS-Group';
			if ( $pods_group != 'false' ) {
				$name = ' PODS Group: ' . $pods_group;
			}
			$form_fields['general']['name'] = new Element_Hidden( "buddyforms_options[form_fields][" . $field_id . "][name]", $name );

			$form_fields['general']['slug']  = new Element_Hidden( "buddyforms_options[form_fields][" . $field_id . "][slug]", 'pods-fields-group' );
			$form_fields['general']['type']  = new Element_Hidden( "buddyforms_options[form_fields][" . $field_id . "][type]", $field_type );
			$form_fields['general']['order'] = new Element_Hidden( "buddyforms_options[form_fields][" . $field_id . "][order]", $field_position, array( 'id' => 'buddyforms/' . $form_slug . '/form_fields/' . $field_id . '/order' ) );
			break;

	}

	return $form_fields;
}

add_filter( 'buddyforms_form_element_add_field', 'buddyforms_pods_form_builder_form_elements', 1, 5 );

/*
 * Display the new PODS Fields in the frontend form
 *
 */
function buddyforms_pods_frontend_form_elements( $form, $form_args ) {
	global $buddyforms, $nonce;

	extract( $form_args );

	$post_type = $buddyforms[ $form_slug ]['post_type'];

	if ( ! $post_type ) {
		return $form;
	}

	if ( ! isset( $customfield['type'] ) ) {
		return $form;
	}


	$pods = pods_api()->load_pods( array( 'fields' => false ) );

	$pod_form_fields = array();
	$pods_list       = array();
	foreach ( $pods as $pod_key => $pod ) {
		$pods_list[ $pod['id'] ] = $pod['name'];
		foreach ( $pod['fields'] as $pod_fields_key => $field ) {
			$pod_form_fields[ $pod['name'] ][ $pod_fields_key ] = $field['name'];
		}
	}

	switch ( $customfield['type'] ) {
		case 'pods-field':
			$post_id = $post_id == 0 ? 'new_post' : $post_id;

			$tmp = '<div id="poststuff">';

			if ( ! $nonce ) {
				$tmp .= '<input type="hidden" name="_podsnonce" value="' . wp_create_nonce( 'input' ) . '" />';
			}

			if ( ! isset( $customfield['pods_field'] ) ) {
				return $form;
			}

			$field['name'] = 'fields[' . $field['key'] . ']';
			$field_type    = isset( $field['type'] ) ? $field['type'] : 'text';

			// Create the BuddyForms Form Element Structure
			if ( post_type_exists( 'pods-field-group' ) ) {
				// Create the BuddyForms Form Element Structure
				$tmp .= '<div id="pods-' . $field['name'] . '" class="bf_field pods-field pods-field-' . str_replace( "_", "-", $field_type ) . ' pods-' . str_replace( "_", "-", $field['key'] ) . ' ' . $required_class . '" data-name="' . $field['name'] . '" data-key="' . $field['key'] . '" data-type="' . $field['type'] . '"><label for="' . $field['name'] . '">' . $field['label'] . '</label>';
			} else {
				// Create the BuddyForms Form Element Structure
				$tmp .= '<div id="pods-' . $field['name'] . '" class="bf_field_group field field_type-' . $field_type . ' field_key-' . $field['key'] . $required_class . '" data-field_name="' . $field['name'] . '" data-field_key="' . $field['key'] . '" data-field_type="' . $field_type . '"><label for="' . $field['name'] . '"><label for="' . $field['name'] . '">' . $field['label'] . '</label>';
			}

			if ( $field['required'] ) {
				$tmp             .= '<span class="required" aria-required="true">* </span>';
				$pods_form_field = str_replace( 'type=', 'required type=', $pods_form_field );
			}
			$pods_form_field = str_replace( 'pods-input-wrap', '', $pods_form_field );

			if ( $field['instructions'] ) {
				$tmp .= '<span class="help-inline">' . $field['instructions'] . '</span>';
			}

			$tmp .= '<div class="bf_inputs"> ' . $pods_form_field . '</div> ';
			$tmp .= '</div></div>';


			$mypod  = pods( 'devs' );
			$params = array( 'fields_only' => true, 'fields' => array( 'categories' ) );

			$form->addElement( new Element_HTML( $mypod->form( $params ) ) );

			break;
		case 'pods-group':


			$mypod = pods( $customfield['pods_group'] );
			if ( ! count( $mypod->pod_data['fields'] ) > 0 ) {
				break;
			}

			$params = array( 'fields_only' => true, 'fields' => $pod_form_fields[ $customfield['pods_group'] ] );
			$form->addElement( new Element_HTML( $mypod->form( $params ) ) );
			break;
	}

	return $form;
}

add_filter( 'buddyforms_create_edit_form_display_element', 'buddyforms_pods_frontend_form_elements', 1, 2 );

/*
 * Save PODS Fields
 *
 */
function buddyforms_pods_update_post_meta( $customfield, $post_id ) {
	if ( $customfield['type'] == 'pods-group' ) {

		$group_ID = $customfield['pods_group'];

		$fields = array();

		// load fields
		if ( post_type_exists( 'pods-field-group' ) ) {
			$fields = pods_get_fields( $group_ID );

			if ( $fields ) {
				foreach ( $fields as $field ) {
					if ( isset( $_POST['pods'][ $field['key'] ] ) ) {
						update_field( $field['key'], $_POST['pods'][ $field['key'] ], $post_id );
					}
				}
			}
		} else {
			$fields = apply_filters( 'pods/field_group/get_fields', $fields, $group_ID );
			if ( $fields ) {
				foreach ( $fields as $field ) {

					if ( isset( $_POST[ $field['name'] ] ) ) {
						update_field( $field['key'], $_POST[ $field['name'] ], $post_id );
					}

				}
			}
		}

	}
	if ( $customfield['type'] == 'pods-field' ) {
		if ( post_type_exists( 'pods-field-group' ) ) {
			if ( isset( $_POST['pods'][ $customfield['pods_field'] ] ) ) {
				update_field( $customfield['pods_field'], $_POST['pods'][ $customfield['pods_field'] ], $post_id );
			}
		} else {
			if ( isset( $_POST['fields'][ $customfield['pods_field'] ] ) ) {
				update_field( $customfield['pods_field'], $_POST['fields'][ $customfield['pods_field'] ], $post_id );
			}
		}
	}
}

add_action( 'buddyforms_update_post_meta', 'buddyforms_pods_update_post_meta', 10, 2 );

function buddyforms_pods_get_fields() {

	// load fields
	if ( post_type_exists( 'pods-field-group' ) ) {
		if ( $_POST['fields_group_id'] ) {
			$fields = pods_get_fields( $_POST['fields_group_id'] );
		}
	} else {
		if ( $_POST['fields_group_id'] ) {
			$fields = apply_filters( 'pods/field_group/get_fields', array(), $_POST['fields_group_id'] );
		}
	}

	$field_select = Array();
	foreach ( $fields as $field ) {
		if ( $field['name'] ) {
			$field_select[ $field['key'] ] = $field['label'];
		}
	}

	echo json_encode( $field_select );
	die();
}

add_action( 'wp_ajax_buddyforms_pods_get_fields', 'buddyforms_pods_get_fields' );
