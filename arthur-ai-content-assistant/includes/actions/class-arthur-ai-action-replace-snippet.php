<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Arthur_AI_Action_Replace_Snippet implements Arthur_AI_Action_Interface {

    public function get_type() {
        return 'replace_snippet_in_post';
    }

    public function get_label() {
        return __( 'Replace Snippet in Post/Page', 'arthur-ai' );
    }

    public function execute( array $payload ) {
        $post_id = isset( $payload['_target_post_id'] ) ? intval( $payload['_target_post_id'] ) : 0;
        if ( $post_id <= 0 || ! get_post( $post_id ) ) {
            return array(
                'success' => false,
                'message' => __( 'Invalid target post ID.', 'arthur-ai' ),
            );
        }

        $find    = isset( $payload['find'] ) ? (string) $payload['find'] : '';
        $replace = isset( $payload['replace_with'] ) ? (string) $payload['replace_with'] : '';

        if ( '' === trim( $find ) ) {
            return array(
                'success' => false,
                'message' => __( 'No text specified to find.', 'arthur-ai' ),
            );
        }

        $content = get_post_field( 'post_content', $post_id );
        if ( ! is_string( $content ) ) {
            $content = '';
        }

        // Exact match first.
        $pos = strpos( $content, $find );

        // Fallback: case-insensitive match.
        if ( false === $pos ) {
            $pos = stripos( $content, $find );
        }

        if ( false === $pos ) {
            return array(
                'success' => false,
                'message' => __( 'The specified snippet could not be found in the content. This can happen if the visible text includes special characters or extra formatting.', 'arthur-ai' ),
            );
        }

        // Before doing a raw substring replace, check if this looks like it lives inside an <img> tag.
        $img_start = strrpos( substr( $content, 0, $pos ), '<img' );
        if ( false !== $img_start ) {
            $img_end = strpos( $content, '>', $pos );
            if ( false !== $img_end && $img_start < $pos && $img_end >= $pos ) {
                // We have an <img ...> tag that encloses the snippet. Replace/remove the whole tag.
                $before = substr( $content, 0, $img_start );
                $after  = substr( $content, $img_end + 1 );

                if ( '' !== $replace ) {
                    // If replace is HTML, drop it in place of the image.
                    $content = $before . $replace . $after;
                } else {
                    // No replacement: delete the image entirely.
                    $content = $before . $after;
                }
            } else {
                // Not confidently inside an image tag; fall back to substring replacement.
                $content = substr( $content, 0, $pos ) . $replace . substr( $content, $pos + strlen( $find ) );
            }
        } else {
            // Not inside an image; standard substring replacement.
            $content = substr( $content, 0, $pos ) . $replace . substr( $content, $pos + strlen( $find ) );
        }

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
                'message' => __( 'Failed to replace snippet.', 'arthur-ai' ),
            );
        }

        return array(
            'success' => true,
            'post_id' => $post_id,
            'message' => __( 'Snippet replaced successfully.', 'arthur-ai' ),
        );
    }
}
