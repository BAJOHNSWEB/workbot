<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Arthur_AI_Actions_Registry {

    /**
     * @var Arthur_AI_Action_Interface[]
     */
    protected static $actions = array();

    public static function register_action( Arthur_AI_Action_Interface $action ) {
        self::$actions[ $action->get_type() ] = $action;
    }

    public static function get_action( $type ) {
        return isset( self::$actions[ $type ] ) ? self::$actions[ $type ] : null;
    }

    public static function get_action_types() {
        return array_keys( self::$actions );
    }
}
