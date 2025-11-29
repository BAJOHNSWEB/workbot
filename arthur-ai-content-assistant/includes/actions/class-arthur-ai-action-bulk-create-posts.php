<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Arthur_AI_Action_Bulk_Create_Posts implements Arthur_AI_Action_Interface {

    public function get_type() {
        return 'bulk_create_posts';
    }

    public function get_label() {
        return __( 'Bulk Create Posts', 'arthur-ai' );
    }

    public function execute( array $payload ) {
        $posts = isset( $payload['posts'] ) && is_array( $payload['posts'] ) ? $payload['posts'] : array();
        if ( empty( $posts ) ) {
            return array(
                'success' => false,
                'message' => __( 'No posts provided for bulk creation.', 'arthur-ai' ),
            );
        }

        $created_ids = array();
        foreach ( $posts as $post ) {
            $title   = isset( $post['post_title'] ) ? wp_strip_all_tags( $post['post_title'] ) : '';
            $content = isset( $post['post_content'] ) ? $post['post_content'] : '';

            if ( '' === $title && '' === $content ) {
                continue;
            }

            if ( '' === $title ) {
                $title = __( 'AI Generated Post', 'arthur-ai' );
            }

            $post_id = wp_insert_post(
                array(
                    'post_title'   => $title,
                    'post_content' => wp_kses_post( $content ),
                    'post_status'  => 'draft',
                    'post_type'    => 'post',
                ),
                true
            );

            if ( ! is_wp_error( $post_id ) ) {
                $created_ids[] = $post_id;
            }
        }

        if ( empty( $created_ids ) ) {
            return array(
                'success' => false,
                'message' => __( 'No posts were created.', 'arthur-ai' ),
            );
        }

        return array(
            'success'     => true,
            'post_id'     => $created_ids[0],
            'created_ids' => $created_ids,
            'message'     => sprintf(
                _n( 'Created %d draft post.', 'Created %d draft posts.', count( $created_ids ), 'arthur-ai' ),
                count( $created_ids )
            ),
        );
    }
}
