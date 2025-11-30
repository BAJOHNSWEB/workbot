<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Arthur_AI_Action_Change_Login_Form_Style implements Arthur_AI_Action_Interface {

    public function get_type() {
        return 'change_login_form_style';
    }

    public function get_label() {
        return __( 'Change Login Form Style', 'arthur-ai' );
    }

    public function execute( array $payload ) {
        $settings = array();

        if ( isset( $payload['background_color'] ) && '' !== $payload['background_color'] ) {
            $settings['background_color'] = sanitize_text_field( $payload['background_color'] );
        }

        if ( isset( $payload['border_radius'] ) && '' !== $payload['border_radius'] ) {
            $settings['border_radius'] = sanitize_text_field( $payload['border_radius'] );
        }

        if ( isset( $payload['box_shadow'] ) && '' !== $payload['box_shadow'] ) {
            $settings['box_shadow'] = sanitize_text_field( $payload['box_shadow'] );
        }

        if ( isset( $payload['padding'] ) && '' !== $payload['padding'] ) {
            $settings['padding'] = sanitize_text_field( $payload['padding'] );
        }

        if ( isset( $payload['max_width'] ) && '' !== $payload['max_width'] ) {
            $settings['max_width'] = sanitize_text_field( $payload['max_width'] );
        }

        if ( isset( $payload['opacity'] ) && '' !== $payload['opacity'] ) {
            $opacity = floatval( $payload['opacity'] );
            $opacity = max( 0, min( 1, $opacity ) );

            $settings['opacity'] = $opacity;
        }

        if ( empty( $settings ) ) {
            return array(
                'success' => false,
                'message' => 'No login form style fields provided.',
            );
        }

        update_option( 'arthur_ai_login_form_style', $settings );

        return array(
            'success' => true,
            'message' => 'Login form style settings saved.',
            'data'    => $settings,
        );
    }
}
