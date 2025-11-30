<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Arthur_AI_Action_Change_Login_Bg_Color implements Arthur_AI_Action_Interface {

    public function get_type() {
        return 'change_login_bg_color';
    }

    public function get_label() {
        return __( 'Change Login Background Colour', 'arthur-ai' );
    }

    public function execute( array $payload ) {
        if ( empty( trim( $payload['background_color'] ?? '' ) ) ) {
            return array(
                'success' => false,
                'message' => 'No background_color value provided.',
            );
        }

        $colour = sanitize_text_field( $payload['background_color'] );
        update_option( 'arthur_ai_login_background_color', $colour );

        return array(
            'success'  => true,
            'message'  => 'Login background colour saved.',
            'data'     => array(
                'background_color' => $colour,
            ),
        );
    }
}
