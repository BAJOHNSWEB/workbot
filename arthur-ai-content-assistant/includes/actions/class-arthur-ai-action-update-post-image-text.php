<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Arthur_AI_Action_Update_Post_Image_Text implements Arthur_AI_Action_Interface {

    public function get_type() {
        return 'update_post_image_and_text';
    }

    public function get_label() {
        return __( 'Update Post/Page Image and Text', 'arthur-ai' );
    }

    public function execute( array $payload ) {
        $post_id = isset( $payload['_target_post_id'] ) ? intval( $payload['_target_post_id'] ) : 0;
        if ( $post_id <= 0 || ! get_post( $post_id ) ) {
            return array(
                'success' => false,
                'message' => __( 'Invalid target post ID.', 'arthur-ai' ),
            );
        }

        $content = get_post_field( 'post_content', $post_id );
        if ( ! is_string( $content ) ) {
            $content = '';
        }

        // Original user request for contextual targeting (e.g. quoted phrases).
        $user_request = isset( $payload['_user_request'] ) ? (string) $payload['_user_request'] : '';

        // Handle image replace / insert.
        if ( isset( $payload['_uploaded_image'] ) && is_array( $payload['_uploaded_image'] ) && ! empty( $payload['_uploaded_image']['url'] ) ) {
            $img  = $payload['_uploaded_image'];
            $alt  = isset( $img['alt'] ) ? $img['alt'] : '';

            // Inline styles ensure the image is not full-width massive on the front-end,
            // even if the theme does not know our CSS class.
            $html = '<figure class="arthur-ai-inline-image" style="max-width:320px;margin:1.5em auto;text-align:center;"><img src="' . esc_url( $img['url'] ) . '" alt="' . esc_attr( $alt ) . '" style="width:100%;height:auto;display:inline-block;" /></figure>';

            // Primary targeting hint from the model.
            $snippet = isset( $payload['snippet_text'] ) ? trim( (string) $payload['snippet_text'] ) : '';

            // If no explicit snippet was provided, try to infer a target paragraph from quoted text in the user request,
            // e.g. "under the paragraph that starts "Our mission"".
            if ( '' === $snippet && $user_request ) {
                if ( preg_match_all( '/"([^"\\]+)"/', $user_request, $m ) && ! empty( $m[1] ) ) {
                    $plain = wp_strip_all_tags( $content );
                    foreach ( $m[1] as $phrase ) {
                        $phrase = trim( $phrase );
                        if ( '' === $phrase ) {
                            continue;
                        }
                        if ( false !== stripos( $plain, $phrase ) ) {
                            $snippet = $phrase;
                            break;
                        }
                    }
                }
            }

            if ( '' !== $snippet ) {
                // Try to locate the snippet in the raw HTML content.
                $pos_raw = stripos( $content, $snippet );

                if ( false === $pos_raw ) {
                    // If not found in raw HTML, approximate by searching a trimmed portion.
                    $snippet_trim = trim( $snippet );
                    if ( strlen( $snippet_trim ) > 40 ) {
                        $snippet_trim = substr( $snippet_trim, 0, 40 );
                    }
                    $pos_raw = stripos( $content, $snippet_trim );
                }

                if ( false !== $pos_raw ) {
                    // Find the end of the paragraph/block containing the snippet.
                    $after_snippet = substr( $content, $pos_raw );
                    $end_pos       = null;

                    // Prefer closing paragraph tag if present.
                    $end_pos_local = stripos( $after_snippet, '</p>' );
                    if ( false !== $end_pos_local ) {
                        $end_pos = $pos_raw + $end_pos_local + 4; // include </p>
                    } else {
                        // Fallback: look for double newline or <br> as soft block boundaries.
                        $double_nl_pos = strpos( $after_snippet, "\n\n" );
                        $br_pos        = stripos( $after_snippet, '<br' );
                        $candidates    = array();

                        if ( false !== $double_nl_pos ) {
                            $candidates[] = $double_nl_pos;
                        }
                        if ( false !== $br_pos ) {
                            $candidates[] = $br_pos;
                        }

                        if ( ! empty( $candidates ) ) {
                            $closest = min( $candidates );
                            $end_pos = $pos_raw + $closest;
                        } else {
                            // If nothing else, use end of content.
                            $end_pos = strlen( $content );
                        }
                    }

                    // From after this block, check if there is an <img> nearby to replace.
                    $after_block = substr( $content, $end_pos );
                    if ( preg_match( '/<img[^>]*>/i', $after_block, $match, PREG_OFFSET_CAPTURE ) ) {
                        $img_pos_in_after = $match[0][1];
                        // Only consider it "under" the paragraph if it's reasonably close.
                        if ( $img_pos_in_after < 600 ) {
                            $before_block      = substr( $content, 0, $end_pos );
                            $between           = substr( $after_block, 0, $img_pos_in_after );
                            $after_img         = substr( $after_block, $img_pos_in_after + strlen( $match[0][0] ) );
                            $content           = $before_block . $between . $html . $after_img;
                        } else {
                            // Too far away; insert immediately after the paragraph instead.
                            $content = substr( $content, 0, $end_pos ) . "\n\n" . $html . substr( $content, $end_pos );
                        }
                    } else {
                        // No image after this paragraph; insert directly below it.
                        $content = substr( $content, 0, $end_pos ) . "\n\n" . $html . substr( $content, $end_pos );
                    }
                } else {
                    // Snippet not found at all: fallback to first-image replacement or prepend.
                    if ( preg_match( '/<img[^>]*>/i', $content ) ) {
                        $content = preg_replace( '/<img[^>]*>/i', $html, $content, 1 );
                    } else {
                        $content = $html . "\n\n" . $content;
                    }
                }
            } else {
                // No snippet hint; default to first image behaviour, or prepend if none.
                if ( preg_match( '/<img[^>]*>/i', $content ) ) {
                    $content = preg_replace( '/<img[^>]*>/i', $html, $content, 1 );
                } else {
                    $content = $html . "\n\n" . $content;
                }
            }
        }

        // Optional note is now stored as an HTML comment so it does not appear on the live page.
        if ( isset( $payload['note'] ) && '' !== trim( (string) $payload['note'] ) ) {
            $content .= "\n\n<!-- Arthur AI note: " . esc_html( $payload['note'] ) . " -->";
        }

        $res = wp_update_post(
            array(
                'ID'           => $post_id,
                'post_content' => $content,
            ),
            true
        );

        if ( is_wp_error( $res ) ) {
            return array(
                'success' => false,
                'message' => __( 'Failed to update content.', 'arthur-ai' ),
            );
        }

        return array(
            'success' => true,
            'post_id' => $post_id,
            'message' => __( 'Content updated successfully.', 'arthur-ai' ),
        );
    }
}

