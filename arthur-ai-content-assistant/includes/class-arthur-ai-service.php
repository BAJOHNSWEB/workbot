<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Arthur_AI_Service {

    const ENDPOINT = 'https://api.openai.com/v1/chat/completions';
    const MODEL    = 'gpt-4.1-mini';

        /**
     * Mark certain actions as higher-risk because they apply custom code (CSS/JS).
     *
     * @param string $action_type
     * @return bool
     */
    public static function is_risky_action_type( $action_type ) {
        $risky_actions = array(
            'set_login_custom_css',
            'set_login_custom_js', // if/when you add this
        );

        return in_array( $action_type, $risky_actions, true );
    }


    /**
     * Call the AI and get an action + fields.
     *
     * @param string                     $user_request
     * @param array                      $site_map
     * @param array|null                 $uploaded_image_context
     * @param Arthur_AI_Module_Interface $module
     *
     * @return array
     */
    public function generate_action( $user_request, array $site_map, ?array $uploaded_image_context, Arthur_AI_Module_Interface $module ) {
        $api_key = Arthur_AI_Settings::get_api_key();
        if ( empty( $api_key ) ) {
            return array( 'error' => 'No API key configured. Please enter your API key in the Arthur AI settings.' );
        }

        $user_request = trim( (string) $user_request );
        if ( '' === $user_request ) {
            return array( 'error' => 'Request cannot be empty.' );
        }

        $site_map_json = wp_json_encode( $site_map );
        $image_info    = '';
        if ( $uploaded_image_context && is_array( $uploaded_image_context ) ) {
            $image_info = ' An image has been uploaded: ' . wp_json_encode( $uploaded_image_context ) . '.';
        }

        $allowed_actions      = $module->get_allowed_actions();
        $allowed_actions_json = wp_json_encode( $allowed_actions );
        $module_context       = $module->get_prompt_context();

        
        $system_message  = "You are Arthur, an AI WordPress assistant.\n";
        $system_message .= "You operate in modular domains. You are currently in the Content module.\n\n";
        $system_message .= $module_context . "\n\n";
        $system_message .= "You are given:\n";
        $system_message .= "- A site map (array of posts/pages with ID, title, type, permalink).\n";
        $system_message .= "- A user request.\n";
        $system_message .= "- Optionally, an uploaded image.\n\n";
        $system_message .= "You must decide on ONE action_type from the following list:\n";
        $system_message .= $allowed_actions_json . "\n\n";
        $system_message .= "Prefer updating existing content when the user refers to an existing page or post by name (e.g. 'About Us page'). Only create new items when clearly requested or when no suitable existing content is available.\n";
        $system_message .= "If the user mentions an 'About', 'Contact', 'Services', 'Team', 'Privacy', or similar site page, treat that as a PAGE (post_type = 'page').\n\n";
        $system_message .= "When the user provides structured content like:\n";
        $system_message .= "- Main heading\n- Intro paragraph\n- Sections (each with heading + paragraph)\n- Image tasks (add/replace under a specific paragraph)\n";
        $system_message .= "you should choose a rewrite-style or create-style action (e.g. 'rewrite_post_body' or 'create_post') and build a fully structured layout, not a single appended block. For example:\n";
        $system_message .= "- Use <h1> for the main page heading (for pages) or top-level heading.\n";
        $system_message .= "- Use <h2> (or <h3>) for each section heading.\n";
        $system_message .= "- Use <p> for each paragraph.\n";
        $system_message .= "- You may group related blocks into <section> wrappers with descriptive classes (e.g. <section class=\"arthur-ai-section\">).\n";
        $system_message .= "- For images, insert <figure class=\"arthur-ai-inline-image\"><img ... /></figure> near the referenced paragraph.\n";
        $system_message .= "Do NOT just insert one monolithic block at the bottom when the user clearly describes a full-page layout.\n\n";
        $system_message .= "Return a JSON object ONLY, with these keys:\n";
        $system_message .= "{\n";
        $system_message .= "  \"action_type\": string,\n";
        $system_message .= "  \"target_post_id\": number|null,\n";
        $system_message .= "  \"fields\": object\n";
        $system_message .= "}\n\n";
        $system_message .= "Where:\n";
        $system_message .= "- action_type is one of the allowed action types above.\n";
        $system_message .= "- target_post_id is null for actions that create new content, otherwise an existing ID from the site map.\n";
        $system_message .= "- fields is an object containing any additional parameters required by the chosen action.\n\n";
        $system_message .= "Field expectations per action_type:\n";
        $system_message .= "1) create_post:\n";
        $system_message .= "   fields = { \"post_title\": string, \"post_content\": string, \"post_type\": \"post\" or \"page\" }\n";
        $system_message .= "   - Use post_type = \"page\" for things like About, Contact, Services, Team, etc., especially when the user explicitly says 'page'.\n";
        $system_message .= "   - post_content must be well-structured HTML (headings, sections, paragraphs, optional images).\n";
        $system_message .= "2) update_post_image_and_text:\n";
        $system_message .= "   fields = { \"note\": string|null, \"snippet_text\": string|null }\n";
        $system_message .= "   - Always fill snippet_text with the exact visible paragraph text you are targeting when the user references a specific line or paragraph (e.g. 'under the paragraph titled \"Hello\"').\n";
        $system_message .= "3) rewrite_post_body:\n";
        $system_message .= "   fields = { \"post_content\": string }\n";
        $system_message .= "   - Use this when the user provides a full new layout for an existing page (e.g. a complete About Us page breakdown).\n";
        $system_message .= "   - post_content must be well-structured HTML with multiple sections/headings as described by the user, not a single block.\n";
        $system_message .= "4) append_note_to_post:\n";
        $system_message .= "   fields = { \"note\": string }\n";
        $system_message .= "5) replace_snippet_in_post:\n";
        $system_message .= "   fields = { \"find\": string, \"replace_with\": string }\n";
        $system_message .= "   - Always set find to the exact visible text or attribute fragment you expect to be in the HTML. When replacing images, \"find\" may be part of the image URL or tag, and the system will remove the whole <img> tag.\n";
        $system_message .= "6) insert_after_heading_in_post:\n";
        $system_message .= "   fields = { \"heading_text\": string, \"insert_html\": string }\n";
        $system_message .= "   - insert_html can be any valid HTML block such as quotes (<blockquote>), callouts, bullet lists, CTAs, etc.\n";
        $system_message .= "7) insert_at_bottom_of_post:\n";
        $system_message .= "   fields = { \"insert_html\": string }\n";
        $system_message .= "   - Use this only for small additions, not for full page rewrites.\n";
        $system_message .= "8) bulk_create_posts:\n";
        $system_message .= "   fields = { \"posts\": [ { \"post_title\": string, \"post_content\": string } ] }\n";
        $system_message .= "9) generate_summary_post:\n";
        $system_message .= "   fields = { \"source_post_ids\": [number], \"post_title\": string, \"post_content\": string }\n";
        $system_message .= "10) publish_post:\n";
        $system_message .= "   fields = { }\n";
        $system_message .= "   - Use when the user explicitly asks you to publish a specific draft page/post.\n";
        $system_message .= "11) update_menu_add_items:\n";
        $system_message .= "   fields = { \"menu_location\": string|null, \"menu_name\": string|null, \"items\": [ { \"title\": string|null, \"url\": string|null, \"post_id\": number|null } ] }\n";
        $system_message .= "   - Use when the user wants to add existing pages or posts (or custom links) to a navigation menu. Prefer using post_id when adding existing pages (e.g. About, Contact).\n";
        $system_message .= "12) replace_frontend_snippet:\n";
        $system_message .= "   fields = { \"find\": string, \"replace_with\": string }\n";
        $system_message .= "   - Use for site-wide footer/header text changes when you cannot reliably identify a specific template. This works by replacing the visible text in the rendered HTML on output.\n";
        $system_message .= "13) replace_in_elementor_data:\n";
        $system_message .= "   fields = { \"find\": string, \"replace_with\": string }\n";
        $system_message .= "   - Use when the content is clearly inside an Elementor header/footer/template or Elementor-built page (e.g. hero heading, Elementor image URL). Choose a \"find\" snippet that actually appears in the Elementor JSON, such as the current heading text or image URL.\n";
        $system_message .= "14) replace_in_block_template:\n";
        $system_message .= "   fields = { \"find\": string, \"replace_with\": string }\n";
        $system_message .= "   - Use when editing block theme headers/footers (wp_template/wp_template_part). Prefer this for phrases like \"Designed with WordPress\" when using a block theme such as Twenty Twenty-Five.\n\n";
        $system_message .= "Do NOT include markdown, comments or extra keys. Respond with JSON only.";

           



        $user_message  = "Site map: " . $site_map_json . "\n\n";
        $user_message .= "User request: " . $user_request . "\n";
        $user_message .= $image_info;

        $payload = array(
            'model'    => self::MODEL,
            'messages' => array(
                array(
                    'role'    => 'system',
                    'content' => $system_message,
                ),
                array(
                    'role'    => 'user',
                    'content' => $user_message,
                ),
            ),
            'temperature' => 0,
            'max_tokens'  => 1000,
        );

        $args = array(
            'headers'     => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ),
            'body'        => wp_json_encode( $payload ),
            'timeout'     => 30,
            'httpversion' => '1.1',
        );

        $response = wp_remote_post( self::ENDPOINT, $args );

        if ( is_wp_error( $response ) ) {
            return array( 'error' => 'HTTP request error: ' . $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );

        if ( 200 !== $code ) {
            $msg = 'AI service returned HTTP ' . $code;
            if ( ! empty( $body ) ) {
                $decoded = json_decode( $body, true );
                if ( is_array( $decoded ) && isset( $decoded['error']['message'] ) ) {
                    $msg .= ': ' . $decoded['error']['message'];
                } else {
                    $snippet = substr( trim( $body ), 0, 600 );
                    $msg    .= ' – Raw response: ' . $snippet;
                }
            }
            if ( function_exists( 'error_log' ) ) {
                error_log( '[Arthur AI] ' . $msg );
            }
            return array( 'error' => $msg );
        }

        if ( '' === $body ) {
            return array( 'error' => 'AI service returned an empty response body.' );
        }

        $decoded = json_decode( $body, true );
        if ( ! is_array( $decoded ) || ! isset( $decoded['choices'][0]['message']['content'] ) ) {
            return array( 'error' => 'Unexpected response structure from AI service: ' . substr( $body, 0, 600 ) );
        }

        $content = trim( $decoded['choices'][0]['message']['content'] );
        $action  = json_decode( $content, true );

        if ( null === $action || JSON_ERROR_NONE !== json_last_error() ) {
            return array( 'error' => 'AI response could not be decoded as JSON. Raw content: ' . substr( $content, 0, 600 ) );
        }

        $defaults = array(
            'action_type'    => null,
            'target_post_id' => null,
            'fields'         => array(),
        );
        $action = wp_parse_args( $action, $defaults );

        if ( ! in_array( $action['action_type'], $allowed_actions, true ) ) {
            return array( 'error' => 'AI selected an invalid action_type: ' . $action['action_type'] );
        }

        if ( ! is_null( $action['target_post_id'] ) && ! is_numeric( $action['target_post_id'] ) ) {
            return array( 'error' => 'target_post_id must be numeric or null.' );
        }

        if ( ! is_array( $action['fields'] ) ) {
            return array( 'error' => 'fields must be an object.' );
        }

        if ( ! is_null( $action['target_post_id'] ) ) {
            $action['target_post_id'] = intval( $action['target_post_id'] );
        }

        return $action;
    }
}
