<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Arthur_AI_Action_Change_Login_Bg_Image implements Arthur_AI_Action_Interface {

    public function get_type() {
        return 'change_login_bg_image';
    }

    public function get_label() {
        return __( 'Change Login Background Image', 'arthur-ai' );
    }

    public function execute( array $payload ) {
        $attachment_id  = isset( $payload['background_attachment_id'] ) ? (int) $payload['background_attachment_id'] : 0;
        $background_url = ! empty( $payload['background_url'] ) ? esc_url_raw( $payload['background_url'] ) : '';

        $has_attachment = $attachment_id > 0;
        $has_url        = '' !== $background_url;

        if ( ! $has_attachment && ! $has_url ) {
            return array(
                'success' => false,
                'message' => 'No background_attachment_id or background_url provided.',
            );
        }

        if ( $has_attachment ) {
            update_option( 'arthur_ai_login_background_attachment_id', $attachment_id );
        }

        if ( $has_url ) {
            update_option( 'arthur_ai_login_background_url', $background_url );
        }

        return array(
            'success' => true,
            'message' => 'Login background image settings saved.',
            'data'    => array(
                'background_attachment_id' => $has_attachment ? $attachment_id : 0,
                'background_url'           => $has_url ? $background_url : '',
            ),
        );
    }
}
