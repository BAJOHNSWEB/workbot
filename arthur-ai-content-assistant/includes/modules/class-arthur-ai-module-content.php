<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Arthur_AI_Module_Content implements Arthur_AI_Module_Interface {

    public function get_id() {
        return 'content';
    }

    public function get_title() {
        return __( 'Content Assistant', 'arthur-ai' );
    }

    public function get_prompt_context() {
        return 'You are operating in the Content module. Focus on WordPress posts and pages, and simple appearance changes such as the login page. You may create, rewrite, and structurally edit content. You can insert blocks such as quotes, callouts, bullet lists, CTAs and summary sections using valid HTML. You may also update login page settings such as the logo, background colour or image, button colours, welcome message, footer text and custom CSS. Prefer updating existing pages or posts in the site map when the user refers to them by name (e.g. About Us page, Contact page). Only create a new page or post if no suitable existing one exists.';
    }

    public function get_allowed_actions() {
        return array(
            // Content actions
            'create_post',
            'update_post_image_and_text',
            'rewrite_post_body',
            'append_note_to_post',
            'replace_snippet_in_post',
            'insert_after_heading_in_post',
            'insert_at_bottom_of_post',
            'bulk_create_posts',
            'generate_summary_post',
            'publish_post',
            'update_menu_add_items',
            'replace_frontend_snippet',
            'replace_in_elementor_data',
            'replace_in_block_template',

            // Login customisation actions
            'change_login_logo',
            'change_login_bg_color',
            'change_login_bg_image',
            'change_login_button_colors',
            'set_login_message',
            'toggle_login_links',
            'change_login_form_style',
            'toggle_login_form_alignment',
            'set_login_footer_text',
            'set_login_custom_css',
            'set_login_logo_link',
        );
    }
}
