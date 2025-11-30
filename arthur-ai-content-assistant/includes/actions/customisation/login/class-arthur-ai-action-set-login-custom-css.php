<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Arthur_AI_Action_Set_Login_Custom_CSS implements Arthur_AI_Action_Interface {

    public function get_type() {
        return 'set_login_custom_css';
    }

    public function get_label() {
        return __( 'Set Login Custom CSS', 'arthur-ai' );
    }

    public function execute( array $payload ) {
        $css = '';

        if ( isset( $payload['css'] ) ) {
            $css = (string) $payload['css'];
            $css = wp_unslash( $css );
            $css = trim( $css );
        }

        if ( '' === $css ) {
            return array(
                'success' => false,
                'message' => 'No CSS provided.',
            );
        }

        update_option( 'arthur_ai_login_custom_css', $css );

        return array(
            'success' => true,
            'message' => 'Login custom CSS saved.',
            'data'    => array(
                'length' => strlen( $css ),
            ),
        );
    }
}
