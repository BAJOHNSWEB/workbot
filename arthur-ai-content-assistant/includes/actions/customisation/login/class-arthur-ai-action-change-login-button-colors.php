<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Arthur_AI_Action_Change_Login_Button_Colors implements Arthur_AI_Action_Interface {

    public function get_type() {
        return 'change_login_button_colors';
    }

    public function get_label() {
        return __( 'Change Login Button Colours', 'arthur-ai' );
    }

    public function execute( array $payload ) {
        $button_background       = $payload['button_background']       ?? '';
        $button_text             = $payload['button_text']             ?? '';
        $button_hover_background = $payload['button_hover_background'] ?? '';
        $button_hover_text       = $payload['button_hover_text']       ?? '';

        if ( empty( $button_background ) && ! empty( $payload['button_color'] ) ) {
            $button_background = $payload['button_color'];
        }

        if ( empty( $button_background ) && empty( $button_text ) && empty( $button_hover_background ) && empty( $button_hover_text ) ) {
            return array(
                'success' => false,
                'message' => 'No button colour fields provided.',
            );
        }

        $settings = array();

        if ( ! empty( $button_background ) ) {
            $settings['button_background'] = sanitize_text_field( $button_background );
        }
        if ( ! empty( $button_text ) ) {
            $settings['button_text'] = sanitize_text_field( $button_text );
        }
        if ( ! empty( $button_hover_background ) ) {
            $settings['button_hover_background'] = sanitize_text_field( $button_hover_background );
        }
        if ( ! empty( $button_hover_text ) ) {
            $settings['button_hover_text'] = sanitize_text_field( $button_hover_text );
        }

        update_option( 'arthur_ai_login_button_colors', $settings );

        return array(
            'success' => true,
            'message' => 'Login button colour settings saved.',
            'data'    => $settings,
        );
    }
}
