<?php

/*
 * Add PODS form elementrs in the form elements select box
 */

add_filter( 'buddyforms_add_form_element_select_option', 'buddyforms_pods_elements_to_select', 1, 2 );
function buddyforms_pods_elements_to_select( $elements_select_options ) {
	global $post;


	if ( $post->post_type != 'buddyforms' ) {
		return $elements_select_options;
	}

	if ( ! defined( 'PODS_VERSION' ) ) {
		return $elements_select_options;
	}

	$elements_select_options['pods']['label']                = 'PODS';
	$elements_select_options['pods']['class']                = 'bf_show_if_f_type_all';
	$elements_select_options['pods']['fields']['pods-field'] = array(
		'label' => __( 'PODS Field', 'buddyforms' ),
	);

	$elements_select_options['pods']['fields']['pods-group'] = array(
		'label' => __( 'PODS Fields', 'buddyforms' ),
	);

	return $elements_select_options;
}

/*
 * Create the new PODS Form Builder Form Elements
 *
 */
add_filter( 'buddyforms_form_element_add_field', 'buddyforms_pods_form_builder_form_elements', 1, 5 );
function buddyforms_pods_form_builder_form_elements( $form_fields, $form_slug, $field_type, $field_id ) {
	global $field_position, $buddyforms;

	if ( ! defined( 'PODS_VERSION' ) ) {
		return $form_fields;
	}

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
			reset($pods_list);
			$pods_group = key($pods_list);//get the first element by default;
			if ( isset( $buddyforms[ $form_slug ]['form_fields'][ $field_id ]['pods_group'] ) ) {
				$pods_group = $buddyforms[ $form_slug ]['form_fields'][ $field_id ]['pods_group'];
			}


			$form_fields['general']['pods_group'] = new Element_Select( '', "buddyforms_options[form_fields][" . $field_id . "][pods_group]", $pods_list, array(
				'value'         => $pods_group,
				'class'         => 'bf_pods_field_group_select',
				'data-field_id' => $field_id,
				'onchange'		=>"populate_pod_fields(this,'".$form_slug."','".$field_id."')"
			) );

			$pods_field = false;
			$field_select                         = $pod_form_fields[ $pods_group ];
			if ( isset( $buddyforms[ $form_slug ]['form_fields'][ $field_id ]['pods_field'] ) ) {
				$pods_field = $buddyforms[ $form_slug ]['form_fields'][ $field_id ]['pods_field'];
			}
			else{
				reset($field_select);
				$pods_field = key($field_select);//get the first element by default;
			}

			$form_fields['general']['pods_field'] = new Element_Select( '', "buddyforms_options[form_fields][" . $field_id . "][pods_field]", $field_select, array(
				'value' => $pods_field,
				'class' => 'bf_pods_fields_select bf_pods_' . $field_id,
				'data-pods-field-id' => $field_id,
				'onchange'		=>"change_slug_and_name(this,'".$form_slug."','".$field_id."')"
			) );

			$name = 'PODS-Field';
			if ( $pods_field != 'not-set' ) {
				$name = 'PODS Field: ' . $pods_field;
			}
			$form_fields['general']['name'] = new Element_Hidden( "buddyforms_options[form_fields][" . $field_id . "][name]", $name ,array(
				'data'     => $field_id,
				'value'    => $name,
				'data-pods-field-name' => $field_id,
			));
			$form_fields['general']['slug'] = new Element_Hidden(  "buddyforms_options[form_fields][" . $field_id . "][slug]",$pods_field, array(

				'data'     => $field_id,
				'value'    => $pods_field,
				'data-pods-field-slug' => $field_id,
			) );

			//$form_fields['general']['slug']  = new Element_Hidden( "buddyforms_options[form_fields][" . $field_id . "][slug]", $pods_field );
			$form_fields['general']['type']  = new Element_Hidden( "buddyforms_options[form_fields][" . $field_id . "][type]", $field_type );
			$form_fields['general']['order'] = new Element_Hidden( "buddyforms_options[form_fields][" . $field_id . "][order]", $field_position, array( 'id' => 'buddyforms/' . $form_slug . '/form_fields/' . $field_id . '/order' ) );
			break;
		case 'pods-group':

			unset( $form_fields );


			$pods_group = 'not-set';
			if ( isset( $buddyforms[ $form_slug ]['form_fields'][ $field_id ]['pods_group'] ) ) {
				$pods_group = $buddyforms[ $form_slug ]['form_fields'][ $field_id ]['pods_group'];
			}
			$form_fields['general']['pods_group'] = new Element_Select( '', "buddyforms_options[form_fields][" . $field_id . "][pods_group]", $pods_list, array( 'value' => $pods_group ) );

			$name = 'PODS-Group';
			if ( $pods_group != 'not-set' ) {
				$name = ' PODS Group: ' . $pods_group;
			}
			$form_fields['general']['name'] = new Element_Hidden( "buddyforms_options[form_fields][" . $field_id . "][name]", $name );

			$form_fields['general']['slug']  = new Element_Hidden( "buddyforms_options[form_fields][" . $field_id . "][slug]", $pods_group );
			$form_fields['general']['type']  = new Element_Hidden( "buddyforms_options[form_fields][" . $field_id . "][type]", $field_type );
			$form_fields['general']['order'] = new Element_Hidden( "buddyforms_options[form_fields][" . $field_id . "][order]", $field_position, array( 'id' => 'buddyforms/' . $form_slug . '/form_fields/' . $field_id . '/order' ) );
			break;

	}

	return $form_fields;
}

/*
 * Display the new PODS Fields in the frontend form
 *
 */
add_filter( 'buddyforms_create_edit_form_display_element', 'buddyforms_pods_frontend_form_elements', 1, 2 );
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

	if ( ! defined( 'PODS_VERSION' ) ) {
		return $form_fields;
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

	$pods_initial_script = "<script type=\"text/javascript\">if ('undefined' === typeof pods_form_init) {var pods_form_init = true;  document.addEventListener('DOMContentLoaded', function () { if ('undefined' !== typeof jQuery(document).Pods) { if ('undefined' === typeof ajaxurl) {  window.ajaxurl = '" . pods_slash( admin_url( 'admin-ajax.php' ) ) . "';  jQuery(document).Pods('dependency', true); } } }, false); }</script>";

	//$form->addElement( new Element_HTML( $pods_initial_script ) );

	switch ( $customfield['type'] ) {
		case 'pods-field':
			$pod_target = $customfield['pods_group'];
			if ( empty( $pod_target ) ) {
				return $form;
			}
			if(is_user_logged_in()){
				$user_id = get_current_user_id();
				$mypod = pods( $pod_target )->find(array("where" =>"t.ID = ".$user_id));

			}else{
				$mypod = pods( $pod_target, $post_id );
			}

			if ( ! count( $mypod->pod_data['fields'] ) > 0 ) {
				break;
			}

			$params = array( 'fields_only' => true, 'fields' => $customfield['pods_field'] );

			$output_field = $mypod->form( $params,"" );
			$output_field = str_replace(array( "\r", "\n", "\t"), '', $output_field);
			$labels_layout = isset( $buddyforms[ $form_slug ]['layout']['labels_layout'] ) ? $buddyforms[ $form_slug ]['layout']['labels_layout'] : 'inline';
			if ( $labels_layout == 'inline' ) {
				$lbl =$mypod->fields[$customfield['pods_field']]['label'];
				$field_slug = str_replace('_','-',$customfield['pods_field']);
				$search       = sprintf( '<label class="pods-form-ui-label pods-form-ui-label-%s" for="pods-form-ui-%s">%s</label>', $field_slug,$field_slug,$lbl );
				//Remove Label
				$output_field = str_replace( $search, '', $output_field );
				//add placeholder
				$search =sprintf( '<input name="%s"', $customfield['pods_field'] );
				$placeholder = sprintf( '<input name="%s" placeholder="%s"', $customfield['pods_field'] ,$lbl);
				//$output_field = str_replace( $search, $placeholder, $output_field );
			}
			//Add wrapper class
			$output_field = str_replace( '<ul class="pods-form-fields', '<ul class="pods-form-fields bf_field_group ', $output_field );
			$search       = sprintf( 'name="%s"', $field['name'] );
			//Add attribute data-form
			$output_field = str_replace( $search, $search . sprintf( ' data-form="%s" data-pod-target="%s"', $form_slug, $pod_target ), $output_field );
			$form->addElement( new Element_HTML( $output_field ) );
			break;
		case 'pods-group':
			$pod_target = $customfield['pods_group'];
			if ( empty( $pod_target ) ) {
				return $form;
			}
			$mypod = pods( $pod_target, $post_id );
			if ( ! count( $mypod->pod_data['fields'] ) > 0 ) {
				break;
			}

			foreach ( $mypod->fields as $field_id => $field ) {
				$params       = array( 'fields_only' => true, 'fields' => array( $field_id => $field ) );
				$output_field = $mypod->form( $params );
				//Add wrapper class
				$output_field = str_replace( '<ul class="pods-form-fields', '<ul class="pods-form-fields bf_field_group ', $output_field );
				$search       = sprintf( 'name="%s"', $field['name'] );
				//Add attribute data-form
				$output_field = str_replace( $search, $search . sprintf( ' data-form="%s" data-pod-target="%s"', $form_slug, $pod_target ), $output_field );
				$form->addElement( new Element_HTML( $output_field ) );
			}
			break;
	}

	return $form;
}

add_filter( 'buddyforms_formbuilder_fields_options', 'buddyforms_pods_formbuilder_fields_options', 999, 4 );
function buddyforms_pods_formbuilder_fields_options( $form_fields, $field_type, $field_id, $form_slug = '' ) {
	global $buddyforms;

	if ( $field_type == 'pods-group' || $field_type == 'pods-field' ) {
		return $form_fields;
	}

	if ( ! defined( 'PODS_VERSION' ) ) {
		return $form_fields;
	}

	if ( ! isset( $buddyforms[ $form_slug ] ) ) {
		return $form_fields;
	}

	$post_type = $buddyforms[ $form_slug ]['post_type'];

	$pods = pods_api()->load_pods( array( 'fields' => false ) );

	$pod_form_fields = array();
	$pods_list       = array();
	foreach ( $pods as $pod_key => $pod ) {
		$pods_list[ $pod['id'] ]                 = $pod['name'];
		$pod_form_fields[ $pod['name'] ]['none'] = 'Select a field';


		foreach ( $pod['fields'] as $pod_fields_key => $field ) {
			$pod_form_fields[ $pod['name'] ][ $pod_fields_key ] = $field['name'];
		}
	}

	if ( ! isset( $pod_form_fields[ $post_type ] ) ) {
		return $form_fields;
	}

	if ( isset( $pod_form_fields[ $post_type ] ) ) {
		$mapped_pods_field = isset( $buddyforms[ $form_slug ]['form_fields'][ $field_id ]['mapped_pods_field'] ) ? $buddyforms[ $form_slug ]['form_fields'][ $field_id ]['mapped_pods_field'] : '';
	}
	$form_fields['PODS']['mapped_pods_field'] = new Element_Select( '<b>' . __( 'Map with existing Pods Field', 'buddyforms' ) . '</b>', "buddyforms_options[form_fields][" . $field_id . "][mapped_pods_field]", $pod_form_fields[ $post_type ], array(
		'value'    => $mapped_pods_field,
		'class'    => 'bf_tax_select',
		'field_id' => $field_id,
		'id'       => 'buddyforms_pods_' . $field_id,
	) );


	return $form_fields;
}

