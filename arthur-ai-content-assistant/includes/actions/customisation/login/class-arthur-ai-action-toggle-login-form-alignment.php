<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Arthur_AI_Action_Toggle_Login_Form_Alignment implements Arthur_AI_Action_Interface {

    public function get_type() {
        return 'toggle_login_form_alignment';
    }

    public function get_label() {
        return __( 'Toggle Login Form Alignment', 'arthur-ai' );
    }

    public function execute( array $payload ) {
        if ( ! array_key_exists( 'centre', $payload ) && ! array_key_exists( 'center', $payload ) ) {
            return array(
                'success' => false,
                'message' => 'No alignment value provided.',
            );
        }

        $value  = array_key_exists( 'centre', $payload ) ? $payload['centre'] : $payload['center'];
        $centre = false;

        if ( true === $value ) {
            $centre = true;
        } elseif ( is_string( $value ) || is_numeric( $value ) ) {
            $centre = in_array( strtolower( (string) $value ), array( '1', 'true', 'yes' ), true );
        }

        $centre = (bool) $centre;

        update_option( 'arthur_ai_login_form_centre', $centre );

        return array(
            'success' => true,
            'message' => $centre ? 'Login form alignment set to centre.' : 'Login form alignment set to default.',
            'data'    => array(
                'centre' => $centre,
            ),
        );
    }
}
