<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Arthur_AI_Action_Change_Login_Logo implements Arthur_AI_Action_Interface {

    public function get_type() {
        return 'change_login_logo';
    }

    public function get_label() {
        return __( 'Change Login Page Logo', 'arthur-ai' );
    }

    public function execute( array $payload ) {
        $attachment_id = 0;
        $logo_url      = '';

        if ( isset( $payload['logo_attachment_id'] ) ) {
            $attachment_id = (int) $payload['logo_attachment_id'];
        }

        if ( isset( $payload['logo_url'] ) ) {
            $logo_url = (string) $payload['logo_url'];
        }

        if ( $attachment_id <= 0 && '' === trim( $logo_url ) ) {
            return array(
                'success' => false,
                'message' => 'No logo_attachment_id or logo_url provided.',
            );
        }

        if ( $attachment_id > 0 ) {
            update_option( 'arthur_ai_login_logo_attachment_id', $attachment_id );
        }

        if ( '' !== trim( $logo_url ) ) {
            $logo_url = esc_url_raw( $logo_url );
            update_option( 'arthur_ai_login_logo_url', $logo_url );
        } else {
            $logo_url = '';
        }

        return array(
            'success' => true,
            'message' => 'Login logo settings updated.',
            'data'    => array(
                'logo_attachment_id' => (int) $attachment_id,
                'logo_url'           => $logo_url,
            ),
        );
    }
}
