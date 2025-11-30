<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AiAssistant_Login_Branding {

    const OPTION_KEY = 'aiassistant_login_branding';

    /**
     * Initialise hooks for applying login branding.
     */
    public static function init() {
        add_action( 'login_enqueue_scripts', array( __CLASS__, 'render_login_styles' ) );
        add_filter( 'login_headerurl', array( __CLASS__, 'filter_login_headerurl' ) );
        add_filter( 'login_headertext', array( __CLASS__, 'filter_login_headertext' ) );
    }

    /**
     * Customise the login branding.
     *
     * @param array $params Branding parameters.
     *
     * @return array Summary of the applied settings.
     */
    public static function customise_login_branding( array $params ) {
        $branding = array();

        if ( isset( $params['logo_url'] ) ) {
            $logo_url = esc_url_raw( $params['logo_url'] );
            if ( ! empty( $logo_url ) ) {
                $branding['logo_url'] = $logo_url;
            }
        }

        if ( isset( $params['logo_height'] ) ) {
            $logo_height = absint( $params['logo_height'] );
            if ( $logo_height > 0 ) {
                $branding['logo_height'] = $logo_height;
            }
        }

        if ( isset( $params['logo_width'] ) ) {
            $logo_width = absint( $params['logo_width'] );
            if ( $logo_width > 0 ) {
                $branding['logo_width'] = $logo_width;
            }
        }

        if ( isset( $params['login_url'] ) ) {
            $login_url = esc_url_raw( $params['login_url'] );
            if ( ! empty( $login_url ) ) {
                $branding['login_url'] = $login_url;
            }
        }

        if ( isset( $params['login_title'] ) ) {
            $login_title = sanitize_text_field( $params['login_title'] );
            if ( '' !== $login_title ) {
                $branding['login_title'] = $login_title;
            }
        }

        update_option( self::OPTION_KEY, $branding );

        return array(
            'success'  => true,
            'settings' => $branding,
        );
    }

    /**
     * Output custom login styles.
     */
    public static function render_login_styles() {
        $branding = get_option( self::OPTION_KEY, array() );

        if ( empty( $branding['logo_url'] ) ) {
            return;
        }

        $height = ! empty( $branding['logo_height'] ) ? absint( $branding['logo_height'] ) : 84;
        $width  = ! empty( $branding['logo_width'] ) ? absint( $branding['logo_width'] ) : 84;
        ?>
        <style type="text/css">
            .login h1 a {
                background-image: url('<?php echo esc_url( $branding['logo_url'] ); ?>');
                height: <?php echo (int) $height; ?>px;
                width: <?php echo (int) $width; ?>px;
                background-size: contain;
                background-repeat: no-repeat;
                padding-bottom: 0;
            }
        </style>
        <?php
    }

    /**
     * Filter the login header URL.
     *
     * @param string $url Default login header URL.
     *
     * @return string
     */
    public static function filter_login_headerurl( $url ) {
        $branding = get_option( self::OPTION_KEY, array() );

        if ( ! empty( $branding['login_url'] ) ) {
            return esc_url( $branding['login_url'] );
        }

        return $url;
    }

    /**
     * Filter the login header text.
     *
     * @param string $text Default login header text.
     *
     * @return string
     */
    public static function filter_login_headertext( $text ) {
        $branding = get_option( self::OPTION_KEY, array() );

        if ( ! empty( $branding['login_title'] ) ) {
            return esc_html( $branding['login_title'] );
        }

        return $text;
    }
}

AiAssistant_Login_Branding::init();
