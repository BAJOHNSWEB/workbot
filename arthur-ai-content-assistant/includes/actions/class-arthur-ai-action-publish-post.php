<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Arthur_AI_Action_Publish_Post implements Arthur_AI_Action_Interface {

    public function get_type() {
        return 'publish_post';
    }

    public function get_label() {
        return __( 'Publish Post/Page', 'arthur-ai' );
    }

    public function execute( array $payload ) {
        $post_id = isset( $payload['_target_post_id'] ) ? intval( $payload['_target_post_id'] ) : 0;
        if ( $post_id <= 0 || ! get_post( $post_id ) ) {
            return array(
                'success' => false,
                'message' => __( 'Invalid target post ID.', 'arthur-ai' ),
            );
        }

        $post_arr = array(
            'ID'          => $post_id,
            'post_status' => 'publish',
        );

        $res = wp_update_post( $post_arr, true );

        if ( is_wp_error( $res ) ) {
            return array(
                'success' => false,
                'post_id' => $post_id,
                'message' => __( 'Failed to publish content.', 'arthur-ai' ),
            );
        }

        return array(
            'success' => true,
            'post_id' => $post_id,
            'message' => __( 'Content published.', 'arthur-ai' ),
        );
    }
}
