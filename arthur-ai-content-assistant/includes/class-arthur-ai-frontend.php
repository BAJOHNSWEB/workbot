<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Arthur_AI_Frontend {

    public static function init() {
        if ( ! is_admin() ) {
            add_action( 'template_redirect', array( __CLASS__, 'start_buffer' ), 0 );
        }
    }

    public static function start_buffer() {
        ob_start( array( __CLASS__, 'filter_output' ) );
    }

    public static function filter_output( $html ) {
        $replacements = get_option( 'arthur_ai_frontend_replacements', array() );
        if ( ! is_array( $replacements ) || empty( $replacements ) ) {
            return $html;
        }

        foreach ( $replacements as $pair ) {
            if ( empty( $pair['find'] ) ) {
                continue;
            }
            $find    = (string) $pair['find'];
            $replace = isset( $pair['replace'] ) ? (string) $pair['replace'] : '';
            // Simple string replacement across full HTML output.
            $html = str_replace( $find, $replace, $html );
        }

        return $html;
    }
}
