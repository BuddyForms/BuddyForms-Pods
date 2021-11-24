<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class bfPodsAPI extends PodsAPI {
	/**
	 * Singleton-ish handling for a basic pods_api() request
	 *
	 * @param string $pod (optional) The pod name
	 * @param string $format (deprecated) Format for import/export, "php" or "csv"
	 *
	 * @return \PodsAPI
	 *
	 * @since 2.3.5
	 */
	public static function init( $pod = null, $format = null ) {

		if ( null !== $pod || null !== $format ) {
			if ( ! isset( self::$instances[ $pod ] ) ) {
				// Cache API singleton per Pod
				self::$instances[ $pod ] = new bfPodsAPI( $pod, $format );
			}

			return self::$instances[ $pod ];
		} elseif ( ! is_object( self::$instance ) ) {
			self::$instance = new bfPodsAPI();
		}

		return self::$instance;
	}

	public static function destroy_instance( $pod ) {
		if ( isset( self::$instances[ $pod ] ) ) {
			unset( self::$instances[ $pod ] );
		}
	}

	/**
	 * Handle filters / actions for the class
	 *
	 * @since 2.0.0
	 */
	private function do_hook() {

		$args = func_get_args();
		if ( empty( $args ) ) {
			return false;
		}
		$name = array_shift( $args );

		return pods_do_hook( "api", $name, $args, $this );
	}

	public function handle_field_validation( &$value, $field, $object_fields, $fields, $pod, $params = array() ) {
		$tableless_field_types = PodsForm::tableless_field_types();

		if ( ! is_array( $fields ) ){
			$fields = array();
		}

		if ( ! is_array( $object_fields ) ){
			$object_fields = array();
		}

		$fields = array_merge( $fields, $object_fields );

		$options = $fields[ $field ];

		$id = ( is_object( $params ) ? $params->id : ( is_object( $pod ) ? $pod->id() : 0 ) );

		$form_slug = $params['form_slug'];

		if ( is_object( $pod ) ) {
			$pod = $pod->pod_data;
		}

		$type  = $options['type'];
		$label = $options['label'];
		$label = empty( $label ) ? $field : $label;

		$global_error    = ErrorHandler::get_instance();

		// Verify required fields
		if ( 1 == pods_var( 'required', $options['options'], 0 ) && 'slug' !== $type ) {
			if ( '' === $value || null === $value || array() === $value ) {
				$global_error->add_error( new BuddyForms_Error( 'buddyforms_form_' . $form_slug, sprintf( __( '%s is empty', 'pods' ), $label ), $field ) );
				return false;
			}

			if ( 'multi' === pods_var( 'pick_format_type', $options['options'] ) && 'autocomplete' !== pods_var( 'pick_format_multi', $options['options'] ) ) {
				$has_value = false;

				$check_value = (array) $value;

				foreach ( $check_value as $val ) {
					if ( '' !== $val && null !== $val && 0 !== $val && '0' !== $val ) {
						$has_value = true;

						continue;
					}
				}

				if ( ! $has_value ) {
					$global_error->add_error( new BuddyForms_Error( 'buddyforms_form_' . $form_slug, sprintf( __( '%s is required', 'pods' ), $label ), $field ) );
					return false;
				}
			}

		}

		// @todo move this to after pre-save preparations
		// Verify unique fields
		if ( 1 == pods_var( 'unique', $options['options'], 0 ) && '' !== $value && null !== $value && array() !== $value ) {
			if ( empty( $pod ) ) {
				return false;
			}

			if ( ! in_array( $type, $tableless_field_types ) ) {
				$exclude = '';

				if ( ! empty( $id ) ) {
					$exclude = "AND `id` != {$id}";
				}

				$check = false;

				$check_value = pods_sanitize( $value );

				// @todo handle meta-based fields
				// Trigger an error if not unique
				if ( 'table' === $pod['storage'] ) {
					$check = pods_query( "SELECT `id` FROM `@wp_pods_" . $pod['name'] . "` WHERE `{$field}` = '{$check_value}' {$exclude} LIMIT 1", $this );
				}

				if ( ! empty( $check ) ) {
					$global_error->add_error( new BuddyForms_Error( 'buddyforms_form_' . $form_slug, sprintf( __( '%s needs to be unique', 'pods' ), $label ), $field ) );
					return false;
				}
			} else {
				// @todo handle tableless check
			}
		}
		
		$options_as_array = json_decode( json_encode( $options ), true );
		$validate = PodsForm::validate( $options['type'], $value, $field, array_merge( $options_as_array, pods_var( 'options', $options, array() ) ), $fields, $pod, $id, $params );

		$validate = $this->do_hook( 'field_validation', $validate, $value, $field, $object_fields, $fields, $pod, $params );

		return $validate;
	}

}
