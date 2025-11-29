<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Replace text inside block theme templates (wp_template, wp_template_part),
 * e.g. footer or header, by operating on post_content.
 */
class Arthur_AI_Action_Replace_In_Block_Template implements Arthur_AI_Action_Interface {

    public function get_type() {
        return 'replace_in_block_template';
    }

    public function get_label() {
        return __( 'Replace in Block Template (Header/Footer)', 'arthur-ai' );
    }

    public function execute( array $payload ) {
        if ( ! current_user_can( 'edit_theme_options' ) ) {
            return array(
                'success' => false,
                'message' => __( 'You do not have permission to edit templates.', 'arthur-ai' ),
            );
        }

        $post_id = isset( $payload['_target_post_id'] ) ? intval( $payload['_target_post_id'] ) : 0;

        if ( $post_id <= 0 ) {
            return array(
                'success' => false,
                'message' => __( 'No target template ID provided.', 'arthur-ai' ),
            );
        }

        $post = get_post( $post_id );
        if ( ! $post || ! in_array( $post->post_type, array( 'wp_template', 'wp_template_part' ), true ) ) {
            return array(
                'success' => false,
                'post_id' => $post_id,
                'message' => __( 'Target is not a block template/header/footer.', 'arthur-ai' ),
            );
        }

        $find    = isset( $payload['find'] ) ? (string) $payload['find'] : '';
        $replace = isset( $payload['replace_with'] ) ? (string) $payload['replace_with'] : '';

        if ( '' === trim( $find ) ) {
            return array(
                'success' => false,
                'post_id' => $post_id,
                'message' => __( 'No text specified to replace in template.', 'arthur-ai' ),
            );
        }

        $content = (string) $post->post_content;

        if ( false === strpos( $content, $find ) ) {
            return array(
                'success' => false,
                'post_id' => $post_id,
                'message' => __( 'The specified text was not found in the template content.', 'arthur-ai' ),
            );
        }

        $new_content = str_replace( $find, $replace, $content );

        if ( $new_content === $content ) {
            return array(
                'success' => false,
                'post_id' => $post_id,
                'message' => __( 'Template content was not changed.', 'arthur-ai' ),
            );
        }

        $res = wp_update_post(
            array(
                'ID'           => $post_id,
                'post_content' => $new_content,
            ),
            true
        );

        if ( is_wp_error( $res ) ) {
            return array(
                'success' => false,
                'post_id' => $post_id,
                'message' => __( 'Failed to update the template.', 'arthur-ai' ),
            );
        }

        return array(
            'success' => true,
            'post_id' => $post_id,
            'message' => __( 'Block template updated successfully.', 'arthur-ai' ),
        );
    }
}
