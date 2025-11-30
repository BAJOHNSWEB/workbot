<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Applies Arthur AI login customisation options on the wp-login.php screen.
 */
class Arthur_AI_Login_Customiser {

    public static function init() {
        // Styles and CSS tweaks on the login page.
        add_action( 'login_enqueue_scripts', array( __CLASS__, 'output_login_styles' ) );

        // Welcome message above the form.
        add_filter( 'login_message', array( __CLASS__, 'filter_login_message' ) );

        // Footer text below the form.
        add_action( 'login_footer', array( __CLASS__, 'output_login_footer' ) );

        // Logo link behaviour.
        add_filter( 'login_headerurl', array( __CLASS__, 'filter_login_header_url' ) );
        add_filter( 'login_headertext', array( __CLASS__, 'filter_login_header_text' ) );
    }

    /**
     * Output CSS for logo, background, form, buttons, alignment, link visibility and custom CSS.
     */
    public static function output_login_styles() {
        // Options written by the actions.
        $logo_attachment_id = (int) get_option( 'arthur_ai_login_logo_attachment_id', 0 );
        $logo_url           = trim( (string) get_option( 'arthur_ai_login_logo_url', '' ) );

        $bg_colour          = trim( (string) get_option( 'arthur_ai_login_background_color', '' ) );
        $bg_attachment_id   = (int) get_option( 'arthur_ai_login_background_attachment_id', 0 );
        $bg_image_url       = trim( (string) get_option( 'arthur_ai_login_background_url', '' ) );

        $button_colors      = get_option( 'arthur_ai_login_button_colors', array() );
        $form_style         = get_option( 'arthur_ai_login_form_style', array() );
        $centre_form        = (bool) get_option( 'arthur_ai_login_form_centre', false );
        $links_visibility   = get_option( 'arthur_ai_login_links_visibility', array() );
        $custom_css         = (string) get_option( 'arthur_ai_login_custom_css', '' );

        // Resolve attachment IDs to URLs if needed.
        if ( $logo_attachment_id > 0 && empty( $logo_url ) ) {
            $url = wp_get_attachment_image_url( $logo_attachment_id, 'full' );
            if ( $url ) {
                $logo_url = $url;
            }
        }

        if ( $bg_attachment_id > 0 && empty( $bg_image_url ) ) {
            $url = wp_get_attachment_image_url( $bg_attachment_id, 'full' );
            if ( $url ) {
                $bg_image_url = $url;
            }
        }

        $logo_url_css = $logo_url ? esc_url( $logo_url ) : '';
        $bg_image_css = $bg_image_url ? esc_url( $bg_image_url ) : '';

        // Be lenient on background colour input.
        $bg_colour_css = '';
        if ( $bg_colour !== '' ) {
            if ( preg_match( '/^#?[0-9a-fA-F]{3,6}$/', $bg_colour ) ) {
                $bg_colour_css = '#' . ltrim( $bg_colour, '#' );
            } else {
                $bg_colour_css = sanitize_text_field( $bg_colour );
            }
        }

        $button_bg         = isset( $button_colors['button_background'] ) ? sanitize_text_field( $button_colors['button_background'] ) : '';
        $button_text       = isset( $button_colors['button_text'] ) ? sanitize_text_field( $button_colors['button_text'] ) : '';
        $button_hover_bg   = isset( $button_colors['button_hover_background'] ) ? sanitize_text_field( $button_colors['button_hover_background'] ) : '';
        $button_hover_text = isset( $button_colors['button_hover_text'] ) ? sanitize_text_field( $button_colors['button_hover_text'] ) : '';

        $form_bg        = isset( $form_style['background_color'] ) ? sanitize_text_field( $form_style['background_color'] ) : '';
        $form_radius    = isset( $form_style['border_radius'] ) ? sanitize_text_field( $form_style['border_radius'] ) : '';
        $form_shadow    = isset( $form_style['box_shadow'] ) ? sanitize_text_field( $form_style['box_shadow'] ) : '';
        $form_padding   = isset( $form_style['padding'] ) ? sanitize_text_field( $form_style['padding'] ) : '';
        $form_max_width = isset( $form_style['max_width'] ) ? sanitize_text_field( $form_style['max_width'] ) : '';
        $form_opacity   = isset( $form_style['opacity'] ) ? (float) $form_style['opacity'] : 0.0;

        $show_lost_password = isset( $links_visibility['show_lost_password'] ) ? (bool) $links_visibility['show_lost_password'] : null;
        $show_back_to_site  = isset( $links_visibility['show_back_to_site'] ) ? (bool) $links_visibility['show_back_to_site'] : null;

        ?>
        <style type="text/css">
            <?php if ( $logo_url_css ) : ?>
            body.login div#login h1 a {
                background-image: url('<?php echo $logo_url_css; ?>');
                background-size: contain;
                background-position: centre centre;
                width: 100%;
                max-width: 320px;
            }
            <?php endif; ?>

            <?php if ( $bg_colour_css || $bg_image_css ) : ?>
            body.login {
                <?php if ( $bg_colour_css ) : ?>
                    background-color: <?php echo $bg_colour_css; ?> !important;
                <?php endif; ?>
                <?php if ( $bg_image_css ) : ?>
                    background-image: url('<?php echo $bg_image_css; ?>') !important;
                    background-size: cover;
                    background-position: centre centre;
                <?php endif; ?>
            }
            <?php endif; ?>

            <?php if ( $form_bg || $form_radius || $form_shadow || $form_padding || $form_max_width || $form_opacity > 0 ) : ?>
            body.login #loginform {
                <?php if ( $form_bg ) : ?>
                    background-color: <?php echo esc_attr( $form_bg ); ?> !important;
                <?php endif; ?>
                <?php if ( $form_radius ) : ?>
                    border-radius: <?php echo esc_attr( $form_radius ); ?> !important;
                <?php endif; ?>
                <?php if ( $form_shadow ) : ?>
                    box-shadow: <?php echo esc_attr( $form_shadow ); ?> !important;
                <?php endif; ?>
                <?php if ( $form_padding ) : ?>
                    padding: <?php echo esc_attr( $form_padding ); ?> !important;
                <?php endif; ?>
                <?php if ( $form_max_width ) : ?>
                    max-width: <?php echo esc_attr( $form_max_width ); ?> !important;
                <?php endif; ?>
                <?php if ( $form_opacity > 0 && $form_opacity <= 1 ) : ?>
                    opacity: <?php echo $form_opacity; ?> !important;
                <?php endif; ?>
            }
            <?php endif; ?>

            <?php if ( $centre_form ) : ?>
            body.login #login {
                margin: 0 auto;
            }
            body.login {
                display: flex;
                align-items: centre;
                justify-content: centre;
                min-height: 100vh;
            }
            <?php endif; ?>

            <?php if ( $button_bg || $button_text || $button_hover_bg || $button_hover_text ) : ?>
            body.login #wp-submit {
                <?php if ( $button_bg ) : ?>
                    background-color: <?php echo esc_attr( $button_bg ); ?> !important;
                    border-color: <?php echo esc_attr( $button_bg ); ?> !important;
                <?php endif; ?>
                <?php if ( $button_text ) : ?>
                    color: <?php echo esc_attr( $button_text ); ?> !important;
                <?php endif; ?>
            }
            <?php if ( $button_hover_bg || $button_hover_text ) : ?>
            body.login #wp-submit:hover,
            body.login #wp-submit:focus {
                <?php if ( $button_hover_bg ) : ?>
                    background-color: <?php echo esc_attr( $button_hover_bg ); ?> !important;
                    border-color: <?php echo esc_attr( $button_hover_bg ); ?> !important;
                <?php endif; ?>
                <?php if ( $button_hover_text ) : ?>
                    color: <?php echo esc_attr( $button_hover_text ); ?> !important;
                <?php endif; ?>
            }
            <?php endif; ?>
            <?php endif; ?>

            <?php if ( $show_lost_password === false ) : ?>
            body.login #nav a[href*="lostpassword"] {
                display: none !important;
            }
            <?php endif; ?>

            <?php if ( $show_back_to_site === false ) : ?>
            body.login #backtoblog {
                display: none !important;
            }
            <?php endif; ?>

            <?php if ( ! empty( $custom_css ) ) : ?>
            /* Custom CSS from Arthur AI */
            <?php echo $custom_css; // trusted admin input ?>
            <?php endif; ?>
        </style>
        <?php
    }

    /**
     * Add a welcome message above the login form, if set.
     */
    public static function filter_login_message( $message ) {
        $welcome = (string) get_option( 'arthur_ai_login_welcome_message', '' );
        if ( '' === trim( $welcome ) ) {
            return $message;
        }

        $welcome = wp_kses_post( $welcome );

        return '<p class="arthur-ai-login-message" style="text-align: centre; margin-bottom: 16px;">' . $welcome . '</p>' . $message;
    }

    /**
     * Output footer text below the login form, if set.
     */
    public static function output_login_footer() {
        $footer = (string) get_option( 'arthur_ai_login_footer_text', '' );
        if ( '' === trim( $footer ) ) {
            return;
        }

        echo '<p class="arthur-ai-login-footer" style="text-align: centre; margin-top: 24px;">' . wp_kses_post( $footer ) . '</p>';
    }

    /**
     * Override the logo link URL if configured.
     */
    public static function filter_login_header_url( $url ) {
        $settings = get_option( 'arthur_ai_login_logo_link', array() );
        if ( empty( $settings['url'] ) ) {
            return $url;
        }

        return esc_url( $settings['url'] );
    }

    /**
     * Override the logo title attribute.
     */
    public static function filter_login_header_text( $title ) {
        return get_bloginfo( 'name' );
    }
}
