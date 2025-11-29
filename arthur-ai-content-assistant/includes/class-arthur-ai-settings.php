<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Arthur_AI_Settings {

    const OPTION_API_KEY = 'arthur_ai_api_key';
    const OPTION_LOG     = 'arthur_ai_action_log';

    public static function init() {
        // Reserved for future settings.
    }

    public static function get_api_key() {
        $key = get_option( self::OPTION_API_KEY, '' );
        return is_string( $key ) ? trim( $key ) : '';
    }

    public static function update_api_key( $key ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return false;
        }
        $key = sanitize_text_field( (string) $key );
        update_option( self::OPTION_API_KEY, $key );
        return true;
    }

    public static function log_action( $type, $post_id, $success, $message ) {
        $log = get_option( self::OPTION_LOG, array() );
        if ( ! is_array( $log ) ) {
            $log = array();
        }

        $log[] = array(
            'time'    => current_time( 'mysql' ),
            'user'    => get_current_user_id(),
            'type'    => $type,
            'post_id' => $post_id,
            'success' => $success ? 1 : 0,
            'message' => $message,
        );

        if ( count( $log ) > 100 ) {
            $log = array_slice( $log, -100 );
        }

        update_option( self::OPTION_LOG, $log );
    }

    public static function get_log() {
        $log = get_option( self::OPTION_LOG, array() );
        return is_array( $log ) ? array_reverse( $log ) : array();
    }
}
