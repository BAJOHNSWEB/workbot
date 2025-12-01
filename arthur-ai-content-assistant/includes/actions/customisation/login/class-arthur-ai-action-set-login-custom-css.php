<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Arthur_AI_Action_Set_Login_Custom_CSS implements Arthur_AI_Action_Interface {

    public function get_type() {
        return 'set_login_custom_css';
    }

    public function get_label() {
        return __( 'Set Login Custom CSS', 'arthur-ai' );
    }

    /**
     * Execute the action.
     *
     * Expected payload:
     *   fields = {
     *     "css": string
     *   }
     *
     * The CSS string is stored in the arthur_ai_login_custom_css option and
     * injected on wp-login.php by Arthur_AI_Login_Customiser.
     *
     * @param array $payload
     * @return array
     */
    public function execute( array $payload ) {
        $css = isset( $payload['css'] ) ? (string) $payload['css'] : '';

        if ( '' === trim( $css ) ) {
            return array(
                'success' => false,
                'message' => __( 'No CSS was provided to save.', 'arthur-ai' ),
            );
        }

        // No escaping here – this is trusted admin-side usage and is escaped at output time.
        update_option( 'arthur_ai_login_custom_css', $css );

        return array(
            'success' => true,
            'message' => __( 'Login custom CSS saved.', 'arthur-ai' ),
            'data'    => array(
                'css_length' => strlen( $css ),
            ),
        );
    }
}
