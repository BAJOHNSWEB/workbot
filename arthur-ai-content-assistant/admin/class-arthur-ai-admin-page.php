<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Arthur_AI_Admin_Page {

    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
        add_action( 'admin_post_arthur_ai_save_settings', array( __CLASS__, 'handle_save_settings' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
    }

    public static function register_menu() {
        add_menu_page(
            __( 'Arthur AI', 'arthur-ai' ),
            __( 'Arthur AI', 'arthur-ai' ),
            'manage_options',
            'arthur-ai',
            array( __CLASS__, 'render_page' ),
            'dashicons-format-chat',
            65
        );
    }

    public static function enqueue_assets( $hook ) {
        if ( 'toplevel_page_arthur-ai' !== $hook ) {
            return;
        }

        wp_enqueue_style(
            'arthur-ai-admin',
            plugins_url( 'admin/css/arthur-ai-admin.css', dirname( __FILE__ ) ),
            array(),
            '3.2.0'
        );

        wp_enqueue_script(
            'arthur-ai-admin',
            plugins_url( 'admin/js/arthur-ai-admin.js', dirname( __FILE__ ) ),
            array( 'jquery' ),
            '3.2.0',
            true
        );

        wp_localize_script(
            'arthur-ai-admin',
            'arthurAiAdmin',
            array(
                'restUrl'       => esc_url_raw( rest_url( 'arthur-ai/v1/process-request' ) ),
                'restNonce'     => wp_create_nonce( 'wp_rest' ),
                'nonce'         => wp_create_nonce( 'wp_rest' ),
                'adminUrl'      => admin_url(),
                'moduleId'      => 'content',
                'editPostUrlBase' => admin_url( 'post.php?action=edit&post=' ),
                'i18nWorking'   => __( 'Working...', 'arthur-ai' ),
                'i18nSending'   => __( 'Sending request to Arthur...', 'arthur-ai' ),
                'i18nActionType'=> __( 'Action type', 'arthur-ai' ),
                'i18nSiteWide'  => __( 'Site-wide action', 'arthur-ai' ),
                'i18nEdit'      => __( 'Edit content', 'arthur-ai' ),
            )
        );
    }

    public static function handle_save_settings() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have permission to perform this action.', 'arthur-ai' ) );
        }
        check_admin_referer( 'arthur_ai_save_settings' );

        $api_key = isset( $_POST['arthur_ai_api_key'] ) ? wp_unslash( $_POST['arthur_ai_api_key'] ) : '';
        Arthur_AI_Settings::update_api_key( $api_key );

        wp_redirect( add_query_arg( array( 'page' => 'arthur-ai', 'updated' => '1' ), admin_url( 'admin.php' ) ) );
        exit;
    }

    
    public static function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $api_key = Arthur_AI_Settings::get_api_key();
        $log     = Arthur_AI_Settings::get_log();
        ?>
        <div class="wrap arthur-ai-wrap">
            <div class="arthur-ai-header">
                <div class="arthur-ai-header-main">
                    <h1><?php esc_html_e( 'Arthur AI Content Assistant', 'arthur-ai' ); ?></h1>
                    <p class="arthur-ai-tagline">
                        <?php esc_html_e( 'Describe what you want in natural language and let Arthur update your WordPress content for you.', 'arthur-ai' ); ?>
                    </p>
                </div>
                <div class="arthur-ai-status">
                    <?php if ( $api_key ) : ?>
                        <span class="arthur-ai-status-pill arthur-ai-status-ok">
                            <span class="arthur-ai-dot"></span>
                            <?php esc_html_e( 'Connected to AI service', 'arthur-ai' ); ?>
                        </span>
                    <?php else : ?>
                        <span class="arthur-ai-status-pill arthur-ai-status-warn">
                            <span class="arthur-ai-dot"></span>
                            <?php esc_html_e( 'API key not configured', 'arthur-ai' ); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="arthur-ai-tabs" role="tablist">
                <button type="button" class="arthur-ai-tab-button is-active" data-tab="assistant" role="tab" aria-selected="true">
                    <?php esc_html_e( 'Assistant', 'arthur-ai' ); ?>
                </button>
                <button type="button" class="arthur-ai-tab-button" data-tab="settings" role="tab" aria-selected="false">
                    <?php esc_html_e( 'Settings', 'arthur-ai' ); ?>
                </button>
                <button type="button" class="arthur-ai-tab-button" data-tab="history" role="tab" aria-selected="false">
                    <?php esc_html_e( 'History', 'arthur-ai' ); ?>
                </button>
            </div>

            <div class="arthur-ai-tab-panels">
                <!-- Assistant tab -->
                <div class="arthur-ai-tab-panel is-active" id="arthur-ai-tab-assistant" role="tabpanel">
                    <div class="arthur-ai-columns">
                        <div class="arthur-ai-column arthur-ai-column-main">
                            <h2><?php esc_html_e( 'Ask Arthur', 'arthur-ai' ); ?></h2>
                            <p class="arthur-ai-help-text">
                                <?php esc_html_e( 'Explain what you want Arthur to do. Mention specific pages, sections, or phrases (e.g. “About Us page”, “the mission section”, “the paragraph starting \"Our mission is\"”).', 'arthur-ai' ); ?>
                            </p>
                            <form id="arthur-ai-request-form">
                                <div class="arthur-ai-field">
                                    <label for="arthur-ai-user-request">
                                        <?php esc_html_e( 'Request', 'arthur-ai' ); ?>
                                    </label>
                                    <textarea id="arthur-ai-user-request" name="user_request" rows="6" placeholder="<?php esc_attr_e( 'E.g. Update the About Us page: rewrite the intro, add two new sections, and replace the image under the paragraph that starts \'Our mission is\'.', 'arthur-ai' ); ?>"></textarea>
                                </div>

                                <div class="arthur-ai-field arthur-ai-file-row">
                                    <div>
                                        <label for="arthur-ai-file">
                                            <?php esc_html_e( 'Optional image or file', 'arthur-ai' ); ?>
                                        </label>
                                        <input type="file" id="arthur-ai-file" name="attachment" accept="image/*" />
                                        <p class="description">
                                            <?php esc_html_e( 'Arthur can upload and use this image when updating content (e.g. hero images, section images).', 'arthur-ai' ); ?>
                                        </p>
                                    </div>
                                    <div class="arthur-ai-hints">
                                        <strong><?php esc_html_e( 'Tips', 'arthur-ai' ); ?></strong>
                                        <ul>
                                            <li><?php esc_html_e( 'Name the page or template you want to change (About, Contact, header, footer, etc.).', 'arthur-ai' ); ?></li>
                                            <li><?php esc_html_e( 'Quote the exact line or heading to target when inserting images or new blocks.', 'arthur-ai' ); ?></li>
                                            <li><?php esc_html_e( 'Say whether you want a draft or to publish changes.', 'arthur-ai' ); ?></li>
                                        </ul>
                                    </div>
                                </div>

                                <div class="arthur-ai-actions-row">
                                    <button type="submit" class="button button-primary arthur-ai-submit">
                                        <span class="arthur-ai-submit-label"><?php esc_html_e( 'Ask Arthur', 'arthur-ai' ); ?></span>
                                        <span class="arthur-ai-spinner"></span>
                                    </button>
                                    <p class="arthur-ai-inline-note">
                                        <?php esc_html_e( 'Arthur will propose and apply changes in one go. You can review details in the History tab.', 'arthur-ai' ); ?>
                                    </p>
                                </div>
                            </form>

                            <div id="arthur-ai-results" class="arthur-ai-results" aria-live="polite"></div>
                        </div>

                        <div class="arthur-ai-column arthur-ai-column-side">
                            <h2><?php esc_html_e( 'What Arthur can do today', 'arthur-ai' ); ?></h2>
                            <ul class="arthur-ai-capabilities">
                                <li><?php esc_html_e( 'Create or rewrite pages and posts (About, Contact, landing pages, blogs).', 'arthur-ai' ); ?></li>
                                <li><?php esc_html_e( 'Insert, replace, or remove images near specific paragraphs or headings.', 'arthur-ai' ); ?></li>
                                <li><?php esc_html_e( 'Update Elementor headings, body text, and image URLs inside templates.', 'arthur-ai' ); ?></li>
                                <li><?php esc_html_e( 'Adjust footer/header text in block themes (e.g. change “Designed with WordPress”).', 'arthur-ai' ); ?></li>
                                <li><?php esc_html_e( 'Add pages to navigation menus and publish drafts on request.', 'arthur-ai' ); ?></li>
                            </ul>
                            <p class="arthur-ai-note-small">
                                <?php esc_html_e( 'More modules are coming – WooCommerce, forms, and email ingestion can be layered on without changing this core UI.', 'arthur-ai' ); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Settings tab -->
                <div class="arthur-ai-tab-panel" id="arthur-ai-tab-settings" role="tabpanel">
                    <div class="arthur-ai-settings-card">
                        <h2><?php esc_html_e( 'API Settings', 'arthur-ai' ); ?></h2>
                        <p class="arthur-ai-help-text">
                            <?php esc_html_e( 'Paste your OpenAI-compatible API key. This key will be used for all Arthur operations across the site.', 'arthur-ai' ); ?>
                        </p>
                        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                            <?php wp_nonce_field( 'arthur_ai_save_settings', 'arthur_ai_settings_nonce' ); ?>
                            <input type="hidden" name="action" value="arthur_ai_save_settings" />
                            <table class="form-table" role="presentation">
                                <tr>
                                    <th scope="row">
                                        <label for="arthur-ai-api-key"><?php esc_html_e( 'API Key', 'arthur-ai' ); ?></label>
                                    </th>
                                    <td>
                                        <input type="password" id="arthur-ai-api-key" name="arthur_ai_api_key" class="regular-text" value="<?php echo esc_attr( $api_key ); ?>" autocomplete="off" />
                                        <p class="description">
                                            <?php esc_html_e( 'Stored securely in the WordPress options table. Only administrators can view or change this.', 'arthur-ai' ); ?>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            <p class="submit">
                                <button type="submit" class="button button-secondary">
                                    <?php esc_html_e( 'Save API Key', 'arthur-ai' ); ?>
                                </button>
                            </p>
                        </form>
                    </div>
                </div>

                <!-- History tab -->
                <div class="arthur-ai-tab-panel" id="arthur-ai-tab-history" role="tabpanel">
                    <div class="arthur-ai-history-card">
                        <h2><?php esc_html_e( 'Recent Actions', 'arthur-ai' ); ?></h2>
                        <p class="arthur-ai-help-text">
                            <?php esc_html_e( 'Arthur logs each action it takes, including the target, result, and any messages or errors.', 'arthur-ai' ); ?>
                        </p>

                        <div class="arthur-ai-log">
                            <?php if ( empty( $log ) ) : ?>
                                <p><?php esc_html_e( 'No actions logged yet.', 'arthur-ai' ); ?></p>
                            <?php else : ?>
                                <table class="widefat striped arthur-ai-log-table">
                                    <thead>
                                        <tr>
                                            <th><?php esc_html_e( 'Time', 'arthur-ai' ); ?></th>
                                            <th><?php esc_html_e( 'Action', 'arthur-ai' ); ?></th>
                                            <th><?php esc_html_e( 'Target', 'arthur-ai' ); ?></th>
                                            <th><?php esc_html_e( 'Status', 'arthur-ai' ); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ( $log as $entry ) : ?>
                                            <tr>
                                                <td><?php echo esc_html( $entry['time'] ); ?></td>
                                                <td><?php echo esc_html( $entry['type'] ); ?></td>
                                                <td>
                                                    <?php
                                                    if ( ! empty( $entry['post_id'] ) ) {
                                                        $edit_link = get_edit_post_link( $entry['post_id'] );
                                                        if ( $edit_link ) {
                                                            printf(
                                                                '<a href="%s">%s #%d</a>',
                                                                esc_url( $edit_link ),
                                                                esc_html( get_post_type_object( get_post_type( $entry['post_id'] ) )->labels->singular_name ?? 'Post' ),
                                                                intval( $entry['post_id'] )
                                                            );
                                                        } else {
                                                            echo intval( $entry['post_id'] );
                                                        }
                                                    } else {
                                                        esc_html_e( 'Site-wide / non-post action', 'arthur-ai' );
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php if ( ! empty( $entry['success'] ) ) : ?>
                                                        <span class="arthur-ai-pill arthur-ai-pill-success"><?php esc_html_e( 'Success', 'arthur-ai' ); ?></span>
                                                    <?php else : ?>
                                                        <span class="arthur-ai-pill arthur-ai-pill-error"><?php esc_html_e( 'Error', 'arthur-ai' ); ?></span>
                                                    <?php endif; ?>
                                                    <?php if ( ! empty( $entry['message'] ) ) : ?>
                                                        <div class="arthur-ai-log-message">
                                                            <?php echo esc_html( $entry['message'] ); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
