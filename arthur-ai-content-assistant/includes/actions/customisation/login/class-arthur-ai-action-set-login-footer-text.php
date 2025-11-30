<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Arthur_AI_Action_Set_Login_Footer_Text implements Arthur_AI_Action_Interface {

    public function get_type() {
        return 'set_login_footer_text';
    }

    public function get_label() {
        return __( 'Set Login Footer Text', 'arthur-ai' );
    }

    public function execute( array $payload ) {
        $footer_text = isset( $payload['footer_text'] ) ? $payload['footer_text'] : '';

        if ( '' === trim( (string) $footer_text ) ) {
            return array(
                'success' => false,
                'message' => 'No login footer text provided.',
            );
        }

        $sanitised = wp_kses_post( (string) $footer_text );

        update_option( 'arthur_ai_login_footer_text', $sanitised );

        return array(
            'success' => true,
            'message' => 'Login footer text saved.',
            'data'    => array(
                'footer_text' => $sanitised,
            ),
        );
    }
}
