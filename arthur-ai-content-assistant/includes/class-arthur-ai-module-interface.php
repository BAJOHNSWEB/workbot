<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

interface Arthur_AI_Module_Interface {

    public function get_id();

    public function get_title();

    public function get_prompt_context();

    /**
     * @return string[] allowed action types
     */
    public function get_allowed_actions();
}
