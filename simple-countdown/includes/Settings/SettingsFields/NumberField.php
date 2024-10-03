<?php
namespace GPLSCore\GPLS_PLUGIN_WPSCTR\Settings\SettingsFields;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Number Field Class.
 * 
 */
class NumberField extends TextField {

	/**
	 * Sanitize Field.
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	public function sanitize_field( $value ) {
		return is_numeric( $value ) ? ( sanitize_text_field( $value ) + 0 ) : '';
	}
}