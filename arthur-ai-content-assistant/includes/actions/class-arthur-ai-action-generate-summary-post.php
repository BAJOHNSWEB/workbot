<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Arthur_AI_Action_Generate_Summary_Post implements Arthur_AI_Action_Interface {

    public function get_type() {
        return 'generate_summary_post';
    }

    public function get_label() {
        return __( 'Generate Summary Post', 'arthur-ai' );
    }

    public function execute( array $payload ) {
        $ids = isset( $payload['source_post_ids'] ) && is_array( $payload['source_post_ids'] ) ? $payload['source_post_ids'] : array();
        if ( empty( $ids ) ) {
            return array(
                'success' => false,
                'message' => __( 'No source posts provided for summary.', 'arthur-ai' ),
            );
        }

        $title   = isset( $payload['post_title'] ) ? wp_strip_all_tags( $payload['post_title'] ) : __( 'Summary Post', 'arthur-ai' );
        $content = isset( $payload['post_content'] ) ? $payload['post_content'] : '';

        $post_id = wp_insert_post(
            array(
                'post_title'   => $title,
                'post_content' => wp_kses_post( $content ),
                'post_status'  => 'draft',
                'post_type'    => 'post',
            ),
            true
        );

        if ( is_wp_error( $post_id ) ) {
            return array(
                'success' => false,
                'message' => __( 'Failed to create summary post.', 'arthur-ai' ),
            );
        }

        return array(
            'success' => true,
            'post_id' => $post_id,
            'message' => __( 'Summary post created as draft.', 'arthur-ai' ),
        );
    }
}
