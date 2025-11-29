<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

interface Arthur_AI_Action_Interface {

    public function get_type();

    public function get_label();

    /**
     * Execute the action.
     *
     * @param array $payload
     * @return array
     */
    public function execute( array $payload );
}
