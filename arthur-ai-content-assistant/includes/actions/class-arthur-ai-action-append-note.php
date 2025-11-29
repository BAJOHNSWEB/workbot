<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Arthur_AI_Action_Append_Note implements Arthur_AI_Action_Interface {

    public function get_type() {
        return 'append_note_to_post';
    }

    public function get_label() {
        return __( 'Append Note to Post/Page', 'arthur-ai' );
    }

    public function execute( array $payload ) {
        $post_id = isset( $payload['_target_post_id'] ) ? intval( $payload['_target_post_id'] ) : 0;
        if ( $post_id <= 0 || ! get_post( $post_id ) ) {
            return array(
                'success' => false,
                'message' => __( 'Invalid target post ID.', 'arthur-ai' ),
            );
        }

        if ( empty( $payload['note'] ) ) {
            return array(
                'success' => false,
                'message' => __( 'No note provided.', 'arthur-ai' ),
            );
        }

        $content = get_post_field( 'post_content', $post_id );
        if ( ! is_string( $content ) ) {
            $content = '';
        }

        $content .= "\n\n<p><em>Update note:</em> " . esc_html( $payload['note'] ) . '</p>';

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
                'message' => __( 'Failed to append note.', 'arthur-ai' ),
            );
        }

        return array(
            'success' => true,
            'post_id' => $post_id,
            'message' => __( 'Note appended to content.', 'arthur-ai' ),
        );
    }
}
