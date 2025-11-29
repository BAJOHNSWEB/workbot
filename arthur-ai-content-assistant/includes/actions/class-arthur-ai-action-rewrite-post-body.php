<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Arthur_AI_Action_Rewrite_Post_Body implements Arthur_AI_Action_Interface {

    public function get_type() {
        return 'rewrite_post_body';
    }

    public function get_label() {
        return __( 'Rewrite Post/Page Body', 'arthur-ai' );
    }

    public function execute( array $payload ) {
        $post_id = isset( $payload['_target_post_id'] ) ? intval( $payload['_target_post_id'] ) : 0;
        if ( $post_id <= 0 || ! get_post( $post_id ) ) {
            return array(
                'success' => false,
                'message' => __( 'Invalid target post ID.', 'arthur-ai' ),
            );
        }

        if ( empty( $payload['post_content'] ) ) {
            return array(
                'success' => false,
                'message' => __( 'No replacement content provided.', 'arthur-ai' ),
            );
        }

        $content = wp_kses_post( $payload['post_content'] );
        $res     = wp_update_post(
            array(
                'ID'           => $post_id,
                'post_content' => $content,
            ),
            true
        );

        if ( is_wp_error( $res ) ) {
            return array(
                'success' => false,
                'message' => __( 'Failed to rewrite content.', 'arthur-ai' ),
            );
        }

        return array(
            'success' => true,
            'post_id' => $post_id,
            'message' => __( 'Content body rewritten.', 'arthur-ai' ),
        );
    }
}
