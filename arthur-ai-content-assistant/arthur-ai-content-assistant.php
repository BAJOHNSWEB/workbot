<?php
/**
 * Plugin Name: Arthur AI Content Assistant
 * Description: AI assistant to create and edit WordPress content.
 * Author: Arthur
 * Version: 3.2.1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'ARTHUR_AI_PLUGIN_DIR' ) ) {
    define( 'ARTHUR_AI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

require_once ARTHUR_AI_PLUGIN_DIR . 'includes/class-arthur-ai-settings.php';
require_once ARTHUR_AI_PLUGIN_DIR . 'includes/class-arthur-ai-action-interface.php';
require_once ARTHUR_AI_PLUGIN_DIR . 'includes/class-arthur-ai-actions-registry.php';
require_once ARTHUR_AI_PLUGIN_DIR . 'includes/class-arthur-ai-module-interface.php';
require_once ARTHUR_AI_PLUGIN_DIR . 'includes/class-arthur-ai-modules.php';
require_once ARTHUR_AI_PLUGIN_DIR . 'includes/class-arthur-ai-service.php';
require_once ARTHUR_AI_PLUGIN_DIR . 'includes/class-arthur-ai-rest.php';
require_once ARTHUR_AI_PLUGIN_DIR . 'includes/class-arthur-ai-frontend.php';

// Actions
require_once ARTHUR_AI_PLUGIN_DIR . 'includes/actions/class-arthur-ai-action-create-post.php';
require_once ARTHUR_AI_PLUGIN_DIR . 'includes/actions/class-arthur-ai-action-update-post-image-text.php';
require_once ARTHUR_AI_PLUGIN_DIR . 'includes/actions/class-arthur-ai-action-rewrite-post-body.php';
require_once ARTHUR_AI_PLUGIN_DIR . 'includes/actions/class-arthur-ai-action-append-note.php';
require_once ARTHUR_AI_PLUGIN_DIR . 'includes/actions/class-arthur-ai-action-replace-snippet.php';
require_once ARTHUR_AI_PLUGIN_DIR . 'includes/actions/class-arthur-ai-action-insert-after-heading.php';
require_once ARTHUR_AI_PLUGIN_DIR . 'includes/actions/class-arthur-ai-action-insert-at-bottom.php';
require_once ARTHUR_AI_PLUGIN_DIR . 'includes/actions/class-arthur-ai-action-bulk-create-posts.php';
require_once ARTHUR_AI_PLUGIN_DIR . 'includes/actions/class-arthur-ai-action-generate-summary-post.php';
require_once ARTHUR_AI_PLUGIN_DIR . 'includes/actions/class-arthur-ai-action-publish-post.php';
require_once ARTHUR_AI_PLUGIN_DIR . 'includes/actions/class-arthur-ai-action-update-menu-add-items.php';
require_once ARTHUR_AI_PLUGIN_DIR . 'includes/actions/class-arthur-ai-action-replace-frontend-snippet.php';
require_once ARTHUR_AI_PLUGIN_DIR . 'includes/actions/class-arthur-ai-action-replace-in-elementor-data.php';
require_once ARTHUR_AI_PLUGIN_DIR . 'includes/actions/class-arthur-ai-action-replace-in-block-template.php';

// Login customisation actions
require_once ARTHUR_AI_PLUGIN_DIR . 'includes/actions/customisation/login/class-arthur-ai-action-change-login-logo.php';
require_once ARTHUR_AI_PLUGIN_DIR . 'includes/actions/customisation/login/class-arthur-ai-action-change-login-bg-color.php';
require_once ARTHUR_AI_PLUGIN_DIR . 'includes/actions/customisation/login/class-arthur-ai-action-change-login-bg-image.php';
require_once ARTHUR_AI_PLUGIN_DIR . 'includes/actions/customisation/login/class-arthur-ai-action-change-login-button-colors.php';
require_once ARTHUR_AI_PLUGIN_DIR . 'includes/actions/customisation/login/class-arthur-ai-action-set-login-message.php';
require_once ARTHUR_AI_PLUGIN_DIR . 'includes/actions/customisation/login/class-arthur-ai-action-toggle-login-links.php';
require_once ARTHUR_AI_PLUGIN_DIR . 'includes/actions/customisation/login/class-arthur-ai-action-change-login-form-style.php';
require_once ARTHUR_AI_PLUGIN_DIR . 'includes/actions/customisation/login/class-arthur-ai-action-toggle-login-form-alignment.php';
require_once ARTHUR_AI_PLUGIN_DIR . 'includes/actions/customisation/login/class-arthur-ai-action-set-login-footer-text.php';
require_once ARTHUR_AI_PLUGIN_DIR . 'includes/actions/customisation/login/class-arthur-ai-action-set-login-custom-css.php';
require_once ARTHUR_AI_PLUGIN_DIR . 'includes/actions/customisation/login/class-arthur-ai-action-set-login-logo-link.php';

// Modules
require_once ARTHUR_AI_PLUGIN_DIR . 'includes/modules/class-arthur-ai-module-content.php';

// Admin
require_once ARTHUR_AI_PLUGIN_DIR . 'admin/class-arthur-ai-admin-page.php';

/**
 * Initialise the Arthur AI plugin components.
 */
function arthur_ai_content_assistant_init() {
    Arthur_AI_Settings::init();
    Arthur_AI_Frontend::init();

    // Register actions
    Arthur_AI_Actions_Registry::register_action( new Arthur_AI_Action_Create_Post() );
    Arthur_AI_Actions_Registry::register_action( new Arthur_AI_Action_Update_Post_Image_Text() );
    Arthur_AI_Actions_Registry::register_action( new Arthur_AI_Action_Rewrite_Post_Body() );
    Arthur_AI_Actions_Registry::register_action( new Arthur_AI_Action_Append_Note() );
    Arthur_AI_Actions_Registry::register_action( new Arthur_AI_Action_Replace_Snippet() );
    Arthur_AI_Actions_Registry::register_action( new Arthur_AI_Action_Insert_After_Heading() );
    Arthur_AI_Actions_Registry::register_action( new Arthur_AI_Action_Insert_At_Bottom() );
    Arthur_AI_Actions_Registry::register_action( new Arthur_AI_Action_Bulk_Create_Posts() );
    Arthur_AI_Actions_Registry::register_action( new Arthur_AI_Action_Generate_Summary_Post() );
    Arthur_AI_Actions_Registry::register_action( new Arthur_AI_Action_Publish_Post() );
    Arthur_AI_Actions_Registry::register_action( new Arthur_AI_Action_Update_Menu_Add_Items() );
    Arthur_AI_Actions_Registry::register_action( new Arthur_AI_Action_Replace_Frontend_Snippet() );
    Arthur_AI_Actions_Registry::register_action( new Arthur_AI_Action_Replace_In_Elementor_Data() );
    Arthur_AI_Actions_Registry::register_action( new Arthur_AI_Action_Replace_In_Block_Template() );

    // Login customisation actions
    Arthur_AI_Actions_Registry::register_action( new Arthur_AI_Action_Change_Login_Logo() );
    Arthur_AI_Actions_Registry::register_action( new Arthur_AI_Action_Change_Login_Bg_Color() );
    Arthur_AI_Actions_Registry::register_action( new Arthur_AI_Action_Change_Login_Bg_Image() );
    Arthur_AI_Actions_Registry::register_action( new Arthur_AI_Action_Change_Login_Button_Colors() );
    Arthur_AI_Actions_Registry::register_action( new Arthur_AI_Action_Set_Login_Message() );
    Arthur_AI_Actions_Registry::register_action( new Arthur_AI_Action_Toggle_Login_Links() );
    Arthur_AI_Actions_Registry::register_action( new Arthur_AI_Action_Change_Login_Form_Style() );
    Arthur_AI_Actions_Registry::register_action( new Arthur_AI_Action_Toggle_Login_Form_Alignment() );
    Arthur_AI_Actions_Registry::register_action( new Arthur_AI_Action_Set_Login_Footer_Text() );
    Arthur_AI_Actions_Registry::register_action( new Arthur_AI_Action_Set_Login_Custom_CSS() );
    Arthur_AI_Actions_Registry::register_action( new Arthur_AI_Action_Set_Login_Logo_Link() );

    // Register modules
    Arthur_AI_Modules::register_module( new Arthur_AI_Module_Content() );

    Arthur_AI_Rest::init();
    Arthur_AI_Admin_Page::init();
}
add_action( 'init', 'arthur_ai_content_assistant_init' );
