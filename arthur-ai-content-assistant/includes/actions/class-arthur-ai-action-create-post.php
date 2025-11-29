<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Arthur_AI_Action_Create_Post implements Arthur_AI_Action_Interface {

    public function get_type() {
        return 'create_post';
    }

    public function get_label() {
        return __( 'Create Post/Page', 'arthur-ai' );
    }

    public function execute( array $payload ) {
        $title   = isset( $payload['post_title'] ) ? wp_strip_all_tags( $payload['post_title'] ) : '';
        $content = isset( $payload['post_content'] ) ? $payload['post_content'] : '';
        $type    = isset( $payload['post_type'] ) ? strtolower( (string) $payload['post_type'] ) : 'post';

        if ( '' === $title ) {
            $title = __( 'AI Generated Content', 'arthur-ai' );
        }

        
        $user_request = isset( $payload['_user_request'] ) ? strtolower( (string) $payload['_user_request'] ) : '';

        // If AI picked 'post' but the request clearly talks about a page (e.g. About Us page), coerce to 'page'.
        if ( 'page' !== $type && $user_request ) {
            $page_keywords = array(
                'about us page',
                'about page',
                'contact page',
                'services page',
                'service page',
                'team page',
                'our team page',
                'privacy page',
                'terms page',
                'landing page'
            );
            foreach ( $page_keywords as $kw ) {
                if ( false !== strpos( $user_request, $kw ) ) {
                    $type = 'page';
                    break;
                }
            }
            if ( 'page' !== $type && ( false !== strpos( $user_request, 'create a page' ) || false !== strpos( $user_request, 'new page' ) ) ) {
                $type = 'page';
            }
        }

// Only allow post or page for now.
        if ( ! in_array( $type, array( 'post', 'page' ), true ) ) {
            $type = 'post';
        }

        $post_id = wp_insert_post(
            array(
                'post_title'   => $title,
                'post_content' => wp_kses_post( $content ),
                'post_status'  => 'draft',
                'post_type'    => $type,
            ),
            true
        );

        if ( is_wp_error( $post_id ) ) {
            return array(
                'success' => false,
                'message' => __( 'Failed to create content.', 'arthur-ai' ),
            );
        }

        $label = ( 'page' === $type ) ? __( 'New draft page created.', 'arthur-ai' ) : __( 'New draft post created.', 'arthur-ai' );

        return array(
            'success' => true,
            'post_id' => $post_id,
            'message' => $label,
        );
    }
}
