<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Arthur_AI_Action_Insert_After_Heading implements Arthur_AI_Action_Interface {

    public function get_type() {
        return 'insert_after_heading_in_post';
    }

    public function get_label() {
        return __( 'Insert After Heading in Post/Page', 'arthur-ai' );
    }

    public function execute( array $payload ) {
        $post_id = isset( $payload['_target_post_id'] ) ? intval( $payload['_target_post_id'] ) : 0;
        if ( $post_id <= 0 || ! get_post( $post_id ) ) {
            return array(
                'success' => false,
                'message' => __( 'Invalid target post ID.', 'arthur-ai' ),
            );
        }

        $heading_text = isset( $payload['heading_text'] ) ? trim( (string) $payload['heading_text'] ) : '';
        $insert_html  = isset( $payload['insert_html'] ) ? (string) $payload['insert_html'] : '';

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

        $updated = false;

        if ( '' !== $heading_text ) {
            $pattern = '/(<h[1-6][^>]*>)(.*?)(<\/h[1-6]>)/i';
            if ( preg_match_all( $pattern, $content, $matches, PREG_OFFSET_CAPTURE ) ) {
                foreach ( $matches[0] as $index => $match ) {
                    $full_heading  = $match[0];
                    $pos           = $match[1];
                    $heading_inner = strip_tags( $matches[2][ $index ][0] );
                    if ( false !== stripos( $heading_inner, $heading_text ) ) {
                        $insert_pos = $pos + strlen( $full_heading );
                        $content    = substr( $content, 0, $insert_pos ) . "\n\n" . $insert_html . "\n\n" . substr( $content, $insert_pos );
                        $updated    = true;
                        break;
                    }
                }
            }
        }

        if ( ! $updated ) {
            // Append to bottom if no heading match.
            $content .= "\n\n" . $insert_html;
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
                'message' => __( 'Failed to insert content.', 'arthur-ai' ),
            );
        }

        return array(
            'success' => true,
            'post_id' => $post_id,
            'message' => __( 'Content inserted successfully.', 'arthur-ai' ),
        );
    }
}
