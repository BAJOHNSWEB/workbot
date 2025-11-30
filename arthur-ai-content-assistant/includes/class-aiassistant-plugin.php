<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AiAssistant_Plugin {

    /**
     * Register available AI actions.
     */
    public static function register_actions() {
        require_once __DIR__ . '/login/class-aiassistant-login-branding.php';

        if ( class_exists( 'AiAssistant_Actions_Registry' ) ) {
            AiAssistant_Actions_Registry::register_action(
                'customise_login_branding',
                array( 'AiAssistant_Login_Branding', 'customise_login_branding' )
            );
        }
    }
}
