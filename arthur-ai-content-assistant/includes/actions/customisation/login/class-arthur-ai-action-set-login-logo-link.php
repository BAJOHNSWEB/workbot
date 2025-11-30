<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Arthur_AI_Action_Set_Login_Logo_Link implements Arthur_AI_Action_Interface {

    public function get_type() {
        return 'set_login_logo_link';
    }

    public function get_label() {
        return __( 'Set Login Logo Link', 'arthur-ai' );
    }

    public function execute( array $payload ) {
        $url    = isset( $payload['url'] ) ? $payload['url'] : '';
        $target = isset( $payload['target'] ) ? $payload['target'] : '';

        if ( empty( $url ) && empty( $target ) ) {
            return array(
                'success' => false,
                'message' => 'No logo link URL or target provided.',
            );
        }

        $settings = array();

        if ( ! empty( $url ) ) {
            $settings['url'] = esc_url_raw( $url );
        }

        if ( ! empty( $target ) ) {
            $target = (string) $target;
            if ( '_blank' !== $target && '_self' !== $target ) {
                $target = '_self';
            }

            $settings['target'] = $target;
        }

        update_option( 'arthur_ai_login_logo_link', $settings );

        return array(
            'success' => true,
            'message' => 'Login logo link settings saved.',
            'data'    => $settings,
        );
    }
}
