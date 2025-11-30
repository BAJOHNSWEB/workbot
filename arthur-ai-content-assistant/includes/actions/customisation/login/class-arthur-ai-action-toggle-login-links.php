<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Arthur_AI_Action_Toggle_Login_Links implements Arthur_AI_Action_Interface {

    public function get_type() {
        return 'toggle_login_links';
    }

    public function get_label() {
        return __( 'Toggle Login Links (Lost Password / Back to Site)', 'arthur-ai' );
    }

    public function execute( array $payload ) {
        $has_lost_password = array_key_exists( 'show_lost_password', $payload );
        $has_back_to_site  = array_key_exists( 'show_back_to_site', $payload );

        if ( ! $has_lost_password && ! $has_back_to_site ) {
            return array(
                'success' => false,
                'message' => 'No login link visibility fields provided.',
            );
        }

        $settings = array(
            'show_lost_password' => $has_lost_password ? (bool) filter_var( $payload['show_lost_password'], FILTER_VALIDATE_BOOLEAN ) : false,
            'show_back_to_site'  => $has_back_to_site ? (bool) filter_var( $payload['show_back_to_site'], FILTER_VALIDATE_BOOLEAN ) : false,
        );

        update_option( 'arthur_ai_login_links_visibility', $settings );

        return array(
            'success' => true,
            'message' => 'Login links visibility saved.',
            'data'    => $settings,
        );
    }
}
