<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Replace text or URLs inside Elementor JSON data for a given template/page.
 *
 * This is intentionally simple: it operates on the raw _elementor_data string,
 * using a string replace. It is suitable for replacing widget text, headings,
 * or image URLs, assuming the AI chooses a fragment that exists in the JSON.
 */
class Arthur_AI_Action_Replace_In_Elementor_Data implements Arthur_AI_Action_Interface {

    public function get_type() {
        return 'replace_in_elementor_data';
    }

    public function get_label() {
        return __( 'Replace in Elementor Data', 'arthur-ai' );
    }

    public function execute( array $payload ) {
        $post_id = isset( $payload['_target_post_id'] ) ? intval( $payload['_target_post_id'] ) : 0;

        if ( $post_id <= 0 || ! get_post( $post_id ) ) {
            return array(
                'success' => false,
                'message' => __( 'Invalid target post/template ID for Elementor update.', 'arthur-ai' ),
            );
        }

        $find    = isset( $payload['find'] ) ? (string) $payload['find'] : '';
        $replace = isset( $payload['replace_with'] ) ? (string) $payload['replace_with'] : '';

        if ( '' === trim( $find ) ) {
            return array(
                'success' => false,
                'post_id' => $post_id,
                'message' => __( 'No snippet specified to replace in Elementor data.', 'arthur-ai' ),
            );
        }

        $data = get_post_meta( $post_id, '_elementor_data', true );

        if ( '' === $data ) {
            return array(
                'success' => false,
                'post_id' => $post_id,
                'message' => __( 'No Elementor data found for this post/template.', 'arthur-ai' ),
            );
        }

        if ( false === strpos( $data, $find ) ) {
            return array(
                'success' => false,
                'post_id' => $post_id,
                'message' => __( 'The specified snippet was not found in Elementor data.', 'arthur-ai' ),
            );
        }

        // Perform a simple string replacement on the JSON text.
        $new_data = str_replace( $find, $replace, $data );

        if ( $new_data === $data ) {
            return array(
                'success' => false,
                'post_id' => $post_id,
                'message' => __( 'Elementor data was not changed.', 'arthur-ai' ),
            );
        }

        update_post_meta( $post_id, '_elementor_data', $new_data );

        return array(
            'success' => true,
            'post_id' => $post_id,
            'message' => __( 'Elementor content updated successfully.', 'arthur-ai' ),
        );
    }
}
