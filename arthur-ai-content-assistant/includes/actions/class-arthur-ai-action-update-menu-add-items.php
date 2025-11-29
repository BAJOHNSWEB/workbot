<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Arthur_AI_Action_Update_Menu_Add_Items implements Arthur_AI_Action_Interface {

    public function get_type() {
        return 'update_menu_add_items';
    }

    public function get_label() {
        return __( 'Add Items to Menu', 'arthur-ai' );
    }

    public function execute( array $payload ) {
        if ( ! current_user_can( 'edit_theme_options' ) ) {
            return array(
                'success' => false,
                'message' => __( 'You do not have permission to edit menus.', 'arthur-ai' ),
            );
        }

        $items        = isset( $payload['items'] ) && is_array( $payload['items'] ) ? $payload['items'] : array();
        $menu_name    = isset( $payload['menu_name'] ) ? (string) $payload['menu_name'] : '';
        $menu_location = isset( $payload['menu_location'] ) ? (string) $payload['menu_location'] : '';

        if ( empty( $items ) ) {
            return array(
                'success' => false,
                'message' => __( 'No menu items were provided to add.', 'arthur-ai' ),
            );
        }

        // Determine target menu: by location, then by name, then fall back to first menu.
        $menu_id = 0;

        $menus = wp_get_nav_menus();
        if ( ! empty( $menu_location ) ) {
            $locations = get_nav_menu_locations();
            if ( isset( $locations[ $menu_location ] ) ) {
                $menu_id = (int) $locations[ $menu_location ];
            }
        }

        if ( ! $menu_id && $menu_name ) {
            foreach ( $menus as $m ) {
                if ( strtolower( $m->name ) === strtolower( $menu_name ) ) {
                    $menu_id = (int) $m->term_id;
                    break;
                }
            }
        }

        if ( ! $menu_id && ! empty( $menus ) ) {
            $menu_id = (int) $menus[0]->term_id;
        }

        if ( ! $menu_id ) {
            return array(
                'success' => false,
                'message' => __( 'No navigation menu is available to update.', 'arthur-ai' ),
            );
        }

        $created = array();

        foreach ( $items as $item ) {
            $title   = isset( $item['title'] ) ? wp_strip_all_tags( $item['title'] ) : '';
            $url     = isset( $item['url'] ) ? esc_url_raw( $item['url'] ) : '';
            $post_id = isset( $item['post_id'] ) ? intval( $item['post_id'] ) : 0;

            if ( ! $title ) {
                if ( $post_id ) {
                    $title = get_the_title( $post_id );
                } elseif ( $url ) {
                    $title = $url;
                } else {
                    continue;
                }
            }

            $args = array(
                'menu-item-title'  => $title,
                'menu-item-status' => 'publish',
            );

            if ( $post_id ) {
                $args['menu-item-object-id'] = $post_id;
                $args['menu-item-object']    = get_post_type( $post_id ) ?: 'page';
                $args['menu-item-type']      = 'post_type';
            } elseif ( $url ) {
                $args['menu-item-url']  = $url;
                $args['menu-item-type'] = 'custom';
            } else {
                continue;
            }

            $menu_item_id = wp_update_nav_menu_item( $menu_id, 0, $args );
            if ( ! is_wp_error( $menu_item_id ) && $menu_item_id ) {
                $created[] = (int) $menu_item_id;
            }
        }

        if ( empty( $created ) ) {
            return array(
                'success' => false,
                'message' => __( 'No menu items were added.', 'arthur-ai' ),
            );
        }

        return array(
            'success'    => true,
            'post_id'    => 0,
            'created_ids'=> $created,
            'message'    => sprintf(
                _n( 'Added %d item to the navigation menu.', 'Added %d items to the navigation menu.', count( $created ), 'arthur-ai' ),
                count( $created )
            ),
        );
    }
}
