<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Arthur_AI_Action_Replace_Frontend_Snippet implements Arthur_AI_Action_Interface {

    public function get_type() {
        return 'replace_frontend_snippet';
    }

    public function get_label() {
        return __( 'Replace Frontend Snippet (Footer/Header Text)', 'arthur-ai' );
    }

    public function execute( array $payload ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return array(
                'success' => false,
                'message' => __( 'You do not have permission to change site-wide text.', 'arthur-ai' ),
            );
        }

        $find    = isset( $payload['find'] ) ? (string) $payload['find'] : '';
        $replace = isset( $payload['replace_with'] ) ? (string) $payload['replace_with'] : '';

        if ( '' === trim( $find ) ) {
            return array(
                'success' => false,
                'message' => __( 'No text specified to replace.', 'arthur-ai' ),
            );
        }

        $replacements = get_option( 'arthur_ai_frontend_replacements', array() );
        if ( ! is_array( $replacements ) ) {
            $replacements = array();
        }

        $updated = false;
        foreach ( $replacements as &$pair ) {
            if ( isset( $pair['find'] ) && $pair['find'] === $find ) {
                $pair['replace'] = $replace;
                $updated         = true;
                break;
            }
        }
        unset( $pair );

        if ( ! $updated ) {
            $replacements[] = array(
                'find'    => $find,
                'replace' => $replace,
            );
        }

        update_option( 'arthur_ai_frontend_replacements', $replacements );

        return array(
            'success' => true,
            'post_id' => 0,
            'message' => __( 'Frontend text replacement rule saved. Future page loads will show the updated text in the footer/header where it appears.', 'arthur-ai' ),
        );
    }
}
