<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Arthur_AI_Action_Insert_At_Bottom implements Arthur_AI_Action_Interface {

    public function get_type() {
        return 'insert_at_bottom_of_post';
    }

    public function get_label() {
        return __( 'Insert at Bottom of Post/Page', 'arthur-ai' );
    }

    public function execute( array $payload ) {
        $post_id = isset( $payload['_target_post_id'] ) ? intval( $payload['_target_post_id'] ) : 0;
        if ( $post_id <= 0 || ! get_post( $post_id ) ) {
            return array(
                'success' => false,
                'message' => __( 'Invalid target post ID.', 'arthur-ai' ),
            );
        }

        $insert_html = isset( $payload['insert_html'] ) ? (string) $payload['insert_html'] : '';
        if ( '' === $insert_html ) {
            return array(
                'success' => false,
                'message' => __( 'No content provided to insert.', 'arthur-ai' ),
            );
        }

        $content = get_post_field( 'post_content', $post_id );
        if ( ! is_string( $content ) ) {
            $content = '';
        }

        $content .= "\n\n" . $insert_html;

        $res = wp_update_post(
            array(
                'ID'           => $post_id,
                'post_content' => $content,
            ),
            true
        );

        if ( is_wp_error( $res ) ) {
            return array(
                'success' => false,
                'message' => __( 'Failed to insert content.', 'arthur-ai' ),
            );
        }

        return array(
            'success' => true,
            'post_id' => $post_id,
            'message' => __( 'Content inserted at bottom of content.', 'arthur-ai' ),
        );
    }
}
