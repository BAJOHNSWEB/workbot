<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Arthur_AI_Rest {

    public static function init() {
        add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
    }

    public static function register_routes() {
        register_rest_route(
            'arthur-ai/v1',
            '/process-request',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( __CLASS__, 'process_request' ),
                'permission_callback' => function () {
                    return current_user_can( 'manage_options' );
                },
            )
        );
    }

    public static function process_request( WP_REST_Request $request ) {
        $nonce = $request->get_header( 'X-WP-Nonce' );
        if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
            return new WP_Error( 'rest_invalid_nonce', __( 'Invalid nonce.', 'arthur-ai' ), array( 'status' => 403 ) );
        }

        $user_request = sanitize_textarea_field( (string) $request->get_param( 'user_request' ) );
        if ( '' === $user_request ) {
            return new WP_Error( 'arthur_ai_empty_request', __( 'The request cannot be empty.', 'arthur-ai' ), array( 'status' => 400 ) );
        }

        $module_id = sanitize_text_field( (string) $request->get_param( 'module_id' ) );
        $module    = Arthur_AI_Modules::get_module( $module_id );
        if ( ! $module ) {
            $module = Arthur_AI_Modules::get_default_module();
        }
        if ( ! $module ) {
            return new WP_Error( 'arthur_ai_no_module', __( 'No Arthur AI modules are registered.', 'arthur-ai' ), array( 'status' => 500 ) );
        }

        // Build site map (recent posts/pages).
        $posts = get_posts(
            array(
                'post_type'      => array( 'post', 'page', 'elementor_library', 'wp_template', 'wp_template_part' ),
                'post_status'    => array( 'publish', 'draft' ),
                'posts_per_page' => 50,
                'orderby'        => 'date',
                'order'          => 'DESC',
            )
        );
        $site_map = array();
        foreach ( $posts as $p ) {
            $site_map[] = array(
                'ID'        => (int) $p->ID,
                'title'     => $p->post_title,
                'type'      => $p->post_type,
                'permalink' => get_permalink( $p->ID ),
            );
        }

        $uploaded_image_context = null;

        // Handle file upload.
        $file_params = $request->get_file_params();
        if ( ! empty( $file_params ) ) {
            $file = null;
            foreach ( $file_params as $value ) {
                if ( isset( $value['name'] ) && $value['name'] ) {
                    $file = $value;
                    break;
                }
            }
            if ( $file ) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
                $overrides     = array( 'test_form' => false );
                $upload_result = wp_handle_upload( $file, $overrides );
                if ( isset( $upload_result['error'] ) ) {
                    return new WP_Error( 'arthur_ai_upload_error', $upload_result['error'], array( 'status' => 400 ) );
                }
                $filetype = wp_check_filetype( $upload_result['file'] );
                if ( ! preg_match( '/^image\//', $filetype['type'] ) ) {
                    return new WP_Error( 'arthur_ai_invalid_file', __( 'Only image uploads are allowed.', 'arthur-ai' ), array( 'status' => 400 ) );
                }
                $attachment = array(
                    'post_mime_type' => $filetype['type'],
                    'post_title'     => sanitize_file_name( pathinfo( $upload_result['file'], PATHINFO_FILENAME ) ),
                    'post_content'   => '',
                    'post_status'    => 'inherit',
                );
                $attach_id = wp_insert_attachment( $attachment, $upload_result['file'] );
                if ( ! is_wp_error( $attach_id ) ) {
                    require_once ABSPATH . 'wp-admin/includes/image.php';
                    $attach_data = wp_generate_attachment_metadata( $attach_id, $upload_result['file'] );
                    wp_update_attachment_metadata( $attach_id, $attach_data );
                    $uploaded_image_context = array(
                        'attachment_id' => $attach_id,
                        'url'           => $upload_result['url'],
                        'title'         => get_the_title( $attach_id ),
                        'alt'           => get_post_meta( $attach_id, '_wp_attachment_image_alt', true ),
                    );
                }
            }
        }

        $service = new Arthur_AI_Service();
        $action  = $service->generate_action( $user_request, $site_map, $uploaded_image_context, $module );

        if ( isset( $action['error'] ) ) {
            return new WP_Error( 'arthur_ai_service_error', $action['error'], array( 'status' => 500 ) );
        }

        $action_type = $action['action_type'];
        $fields      = $action['fields'];
        // Pass user request into fields so actions can make decisions (e.g. page vs post).
        $fields['_user_request'] = $user_request;

        $post_id     = isset( $action['target_post_id'] ) ? $action['target_post_id'] : null;

        $action_handler = Arthur_AI_Actions_Registry::get_action( $action_type );
        if ( ! $action_handler ) {
            return new WP_Error( 'arthur_ai_no_action', __( 'No handler is registered for the requested action.', 'arthur-ai' ), array( 'status' => 500 ) );
        }

        if ( $uploaded_image_context ) {
            $fields['_uploaded_image'] = $uploaded_image_context;
        }
        if ( null !== $post_id ) {
            $fields['_target_post_id'] = $post_id;
        }

        $result = $action_handler->execute( $fields );

        Arthur_AI_Settings::log_action(
            $action_type,
            isset( $result['post_id'] ) ? $result['post_id'] : $post_id,
            ! empty( $result['success'] ),
            isset( $result['message'] ) ? $result['message'] : ''
        );

        return rest_ensure_response(
            array(
                'success' => true,
                'data'    => array(
                    'action' => $action,
                    'result' => $result,
                ),
            )
        );
    }
}
