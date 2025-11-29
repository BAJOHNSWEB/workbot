<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Arthur_AI_Action_Duplicate_Post_With_Updates implements Arthur_AI_Action_Interface {

    public function get_type() {
        return 'duplicate_post_with_updates';
    }

    public function get_label() {
        return __( 'Duplicate Post/Page With Updates', 'arthur-ai' );
    }

    public function execute( array $payload ) {

        $source_id = isset( $payload['source_post_id'] ) ? intval( $payload['source_post_id'] ) : 0;
        if ( ! $source_id ) {
            return array('success'=>false,'message'=>'No source_post_id provided.');
        }
        $source = get_post( $source_id );
        if ( ! $source ) {
            return array('success'=>false,'message'=>'Source not found.');
        }
        if ( ! current_user_can( 'edit_post', $source_id ) ) {
            return array('success'=>false,'message'=>'No permission.');
        }

        $new_title = isset($payload['new_post_title']) && trim($payload['new_post_title'])!=='' 
            ? wp_strip_all_tags($payload['new_post_title']) 
            : $source->post_title;

        $new_content = isset($payload['new_post_content']) && trim($payload['new_post_content'])!==''
            ? $payload['new_post_content']
            : $source->post_content;

        $post_status = isset($payload['post_status']) ? sanitize_key($payload['post_status']) : 'draft';

        $new_post_id = wp_insert_post(array(
            'post_title'   => $new_title,
            'post_content' => wp_kses_post($new_content),
            'post_excerpt' => $source->post_excerpt,
            'post_status'  => $post_status,
            'post_type'    => $source->post_type,
            'post_author'  => get_current_user_id(),
        ), true);

        if ( is_wp_error($new_post_id) ) {
            return array('success'=>false,'message'=>$new_post_id->get_error_message());
        }

        return array(
            'success'=>true,
            'post_id'=>$new_post_id,
            'message'=>'Post duplicated and updated.'
        );
    }
}
