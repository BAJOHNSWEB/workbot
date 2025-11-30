<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Arthur_AI_Action_Set_Login_Message implements Arthur_AI_Action_Interface {

    public function get_type() {
        return 'set_login_message';
    }

    public function get_label() {
        return __( 'Set Login Welcome Message', 'arthur-ai' );
    }

    public function execute( array $payload ) {
        $message = isset( $payload['message'] ) ? trim( (string) $payload['message'] ) : '';

        if ( '' === $message ) {
            return array(
                'success' => false,
                'message' => 'No login message provided.',
            );
        }

        $sanitised_message = wp_kses_post( $message );

        update_option( 'arthur_ai_login_welcome_message', $sanitised_message );

        return array(
            'success' => true,
            'message' => 'Login welcome message saved.',
            'data'    => array(
                'message' => $sanitised_message,
            ),
        );
    }
}
