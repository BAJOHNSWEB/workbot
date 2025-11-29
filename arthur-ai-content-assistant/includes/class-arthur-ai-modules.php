<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Arthur_AI_Modules {

    /**
     * @var Arthur_AI_Module_Interface[]
     */
    protected static $modules = array();

    public static function register_module( Arthur_AI_Module_Interface $module ) {
        self::$modules[ $module->get_id() ] = $module;
    }

    public static function get_module( $id ) {
        return isset( self::$modules[ $id ] ) ? self::$modules[ $id ] : null;
    }

    public static function get_default_module() {
        if ( empty( self::$modules ) ) {
            return null;
        }
        $modules = array_values( self::$modules );
        return $modules[0];
    }

    public static function get_modules() {
        return self::$modules;
    }
}
