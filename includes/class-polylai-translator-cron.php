<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
class polylai_Cron {
    const LOCK_TRANSIENT_KEY = "polylai_running";

    const ACTIVITY_TRANSIENT_KEY = "polylai_heartbeat";

    private $cli = false;

    private $ai_engine;

    public function __construct() {
        $ai_engine = polylai_Utils::get_ai_engine();
        // free version
        $this->ai_engine = new polylai_ChatGPT();
        if ( isset( $_SERVER['argv'] ) && is_array( $_SERVER['argv'] ) && count( $_SERVER['argv'] ) ) {
            polylai_Utils::db_log( 'debug', 'cron', "cron cli request {$ai_engine}" );
            foreach ( $_SERVER['argv'] as $arg ) {
                $e = explode( '=', sanitize_text_field( $arg ) );
                if ( $e[0] === 'polylai_cron' ) {
                    $this->cli = true;
                    add_action( 'init', [$this, 'execCron'], 1 );
                }
                if ( $e[0] === 'polylai_reset' ) {
                    delete_transient( self::LOCK_TRANSIENT_KEY );
                    delete_transient( self::ACTIVITY_TRANSIENT_KEY );
                }
            }
        }
        // Request from crontab, cannot use wp_nonce
        if ( isset( $_GET['action'] ) && sanitize_text_field( $_GET['action'] ) == 'polylai_run_cron' ) {
            polylai_Utils::db_log( 'info', 'cron', "cron http request {$ai_engine}" );
            add_action( 'init', [$this, 'execCron'], 1 );
        }
    }

    public function translate_categories() {
        $italian_language = 'it';
        // Assicurati che questo sia il codice lingua corretto per l'italiano nel tuo setup
        // Ottiene le categorie con il filtro della lingua italiana
        $args = array(
            'taxonomy'   => 'category',
            'lang'       => $italian_language,
            'hide_empty' => false,
        );
        $categories = get_terms( $args );
        // Ottiene le categorie
        // Controlla se ci sono categorie e le stampa
        print_r( $categories );
    }

    public function execCron() {
        polylai_Utils::db_log( 'debug', 'cron', 'exec cron' );
        if ( $this->cli ) {
            set_transient( self::ACTIVITY_TRANSIENT_KEY, gmdate( 'Y-m-d H:i:s' ), 70 );
        }
        $this->fetch_posts();
        die;
    }

    public function fetch_posts() {
        polylai_Utils::db_log( 'debug', 'cron', 'fetch posts' );
        global $wpdb;
        $running = get_transient( self::LOCK_TRANSIENT_KEY );
        if ( $running ) {
            polylai_Utils::db_log( 'debug', 'cron', 'lock' );
            set_transient( self::LOCK_TRANSIENT_KEY, '1', 60 * 60 );
            return;
        }
        set_transient( self::LOCK_TRANSIENT_KEY, '1', 60 * 60 );
        $query = $wpdb->prepare( "SELECT * FROM %i WHERE meta_key=%s LIMIT 1", $wpdb->postmeta, POLYLAI_NEED_TR_KEY );
        $results = $wpdb->get_results( $query );
        if ( count( $results ) == 0 ) {
            delete_transient( self::LOCK_TRANSIENT_KEY );
            return;
        }
        $allowed = polylai_Utils::allowed_langs();
        polylai_Utils::db_log( 'debug', 'cron', count( $results ) . " results" );
        foreach ( $results as $key => $row ) {
            $id = $row->post_id;
            //delete_post_meta($id, 'polylai_need_translation');
            $meta = get_post_meta( $id, POLYLAI_NEED_TR_KEY, true );
            $locales = explode( ',', $meta['locales'] );
            $locales_names = explode( ',', $meta['locales_names'] );
            // lock
            update_post_meta( $id, POLYLAI_PROCESSING_KEY, true );
            foreach ( $locales as $i => $locale ) {
                if ( !in_array( $locale, $allowed ) ) {
                    continue;
                }
                polylai_Utils::db_log(
                    'info',
                    'translate',
                    "processing {$locale}",
                    $id
                );
                $current = get_post_meta( $id, 'polylai_processing' . $locale );
                if ( $current ) {
                    continue;
                }
                update_post_meta( $id, 'polylai_current_' . $locale, 'true' );
                $this->translate( $id, $locale, $locales_names[$i] );
            }
            foreach ( $locales as $i => $locale ) {
                delete_post_meta( $id, 'polylai_current_' . $locale );
            }
            delete_post_meta( $id, POLYLAI_NEED_TR_KEY );
            delete_post_meta( $id, POLYLAI_TRANSLATING_KEY );
            delete_post_meta( $id, POLYLAI_PROCESSING_KEY );
        }
        polylai_Utils::db_log( 'info', 'translate', 'complete' );
        delete_transient( self::LOCK_TRANSIENT_KEY );
    }

    private function create_translation(
        $post_id,
        $title,
        $text,
        $excerpt,
        $meta,
        $locale_from,
        $locale
    ) {
        $oldpost = get_post( $post_id );
        $post = [
            'post_author'    => $oldpost->post_author,
            'post_date'      => $oldpost->post_date,
            'post_date_gmt'  => $oldpost->post_date_gmt,
            'post_content'   => $text,
            'post_title'     => $title,
            'post_excerpt'   => $excerpt,
            'post_status'    => $oldpost->post_status,
            'comment_status' => $oldpost->comment_status,
            'ping_status'    => $oldpost->ping_status,
            'post_password'  => $oldpost->post_password,
            'post_name'      => sanitize_title( $title ),
            'to_ping'        => $oldpost->to_ping,
            'post_type'      => $oldpost->post_type,
        ];
        polylai_Utils::db_log(
            'info',
            "create_translation",
            null,
            $post_id,
            $title
        );
        $new_post_id = wp_insert_post( $post );
        $all_meta = get_post_meta( $post_id );
        foreach ( $all_meta as $key => $item ) {
            if ( substr( $key, 0, strlen( '_ez-toc' ) ) == '_ez-toc' || substr( $key, 0, strlen( '_yoast' ) ) == '_yoast' ) {
                foreach ( $item as $value ) {
                    update_post_meta( $new_post_id, $key, maybe_unserialize( $value ) );
                }
            }
        }
        foreach ( $meta as $key => $value ) {
            update_post_meta( $new_post_id, $key, $value );
        }
        pll_set_post_language( $new_post_id, $locale );
        $translations = pll_get_post_translations( $post_id );
        $translations[$locale] = $new_post_id;
        pll_save_post_translations( $translations );
        $taxonomies = get_post_taxonomies( $post_id );
        if ( $taxonomies ) {
            foreach ( $taxonomies as $taxonomy ) {
                if ( $taxonomy == 'language' ) {
                    continue;
                }
                if ( $taxonomy == 'post_translations' ) {
                    continue;
                }
                $terms = wp_get_object_terms( $post_id, $taxonomy, [
                    'fields' => 'ids',
                ] );
                if ( count( $terms ) == 0 ) {
                    continue;
                }
                foreach ( $terms as $k => $term_id ) {
                    $new_tx_id = pll_get_term( $terms[$k], $locale );
                    //print_r("Translated term $term_id: $new_tx_id\n");
                    if ( $new_tx_id == $term_id || $new_tx_id == 0 ) {
                        unset($terms[$k]);
                        continue;
                    }
                    $terms[$k] = $new_tx_id;
                }
                $result = wp_set_object_terms( $new_post_id, $terms, $taxonomy );
            }
        }
        $thumb_id = get_post_thumbnail_id( $post_id );
        if ( $thumb_id ) {
            set_post_thumbnail( $new_post_id, $thumb_id );
        }
        return $new_post_id;
    }

    private function translate_block(
        $id,
        $block,
        $locale_from,
        $locale_to,
        $tot_blocks,
        $locale
    ) {
        foreach ( $block['innerBlocks'] as $i => $innerBlock ) {
            $block['innerBlocks'][$i] = $this->translate_block(
                $id,
                $block['innerBlocks'][$i],
                $locale_from,
                $locale_to,
                $tot_blocks,
                $locale
            );
        }
        $html = ( $block['innerHTML'] == null ? '' : $block['innerHTML'] );
        $cleanText = trim( strip_tags( $html ) );
        // cannot use wp_strip_all_tags here
        if ( strlen( $cleanText ) > 0 ) {
            foreach ( $block['innerContent'] as $i => $c ) {
                $splitted_text = polylai_Utils::split_text( $block['innerContent'][$i], 500 );
                $block['innerContent'][$i] = '';
                foreach ( $splitted_text as $ii => $s ) {
                    $block['innerContent'][$i] .= $this->ai_engine->translate(
                        $s,
                        $locale_from,
                        $locale_to,
                        null
                    ) . " ";
                    if ( $tot_blocks == 1 ) {
                        update_post_meta( $id, POLYLAI_KEY, [
                            "locale" => $locale,
                            "perc"   => ceil( $ii / count( $splitted_text ) * 100 ),
                        ] );
                    }
                }
                // $block['innerContent'][$i] = $this->ai_engine->translate($block['innerContent'][$i], $locale_from, $locale_to, null);
            }
        }
        return $block;
    }

    private function translate_meta( $id, $current_locale_name, $locale_name ) {
        // Yoast
        $yoast = [
            '_yoast_wpseo_bctitle',
            '_yoast_wpseo_metadesc',
            '_yoast_wpseo_focuskw',
            '_yoast_wpseo_title'
        ];
        $meta = [];
        foreach ( $yoast as $key ) {
            $item = get_post_meta( $id, $key, true );
            if ( $item ) {
                $meta[$key] = $this->ai_engine->translate(
                    $item,
                    $current_locale_name,
                    $locale_name,
                    $id
                );
            }
        }
        return $meta;
    }

    private function get_category_id_by_url( $category_url, $locale ) {
        //print_r(pll_home_url($locale) . "\n");
        $path = str_replace( pll_home_url( $locale ), '', $category_url );
        $path = trim( $path, '/' );
        $slug = array_filter( explode( '/', $path ) );
        $category_slug = end( $slug );
        $category = get_term_by( 'slug', $category_slug, 'category' );
        if ( $category ) {
            return $category->term_id;
        } else {
            return 0;
        }
    }

    private function translate_links( $html, $locale, $current_locale ) {
        $pattern = '/href="([^"]*)"/';
        if ( preg_match_all( $pattern, $html, $matches ) ) {
            foreach ( $matches[1] as $href ) {
                $postID = url_to_postid( $href );
                //print_r("POST ID $href $postID\n");
                if ( $postID > 0 ) {
                    $translatedPost = pll_get_post( $postID, $locale );
                    if ( $translatedPost ) {
                        $link = get_permalink( $translatedPost );
                        if ( $link ) {
                            $html = str_replace( $href, $link, $html );
                        }
                    }
                } else {
                    $cat_id = $this->get_category_id_by_url( $href, $current_locale );
                    if ( $cat_id > 0 ) {
                        $term_id = pll_get_term( $cat_id, $locale );
                        //print_r("CAT ID $href $term_id $link\n");
                        if ( $term_id > 0 ) {
                            $link = get_category_link( $term_id );
                            $html = str_replace( $href, $link, $html );
                        }
                    }
                }
            }
        }
        return $html;
    }

    private function translate( $id, $locale, $locale_name ) {
        $post = get_post( $id );
        $current_locale = pll_get_post_language( $id, 'slug' );
        $current_locale_name = pll_get_post_language( $id, 'name' );
        $blocks = parse_blocks( $post->post_content );
        $tot_blocks = count( $blocks );
        polylai_Utils::db_log(
            'info',
            "translate",
            count( $blocks ) . " blocks {$locale}",
            $id
        );
        $tr_blocks = [];
        update_post_meta( $id, POLYLAI_KEY, [
            "locale" => $locale,
            "perc"   => 0,
        ] );
        foreach ( $blocks as $i => $block ) {
            $tr_blocks[] = $this->translate_block(
                $id,
                $block,
                $current_locale_name,
                $locale_name,
                $tot_blocks,
                $locale
            );
            if ( count( $blocks ) > 1 ) {
                update_post_meta( $id, POLYLAI_KEY, [
                    "locale" => $locale,
                    "perc"   => ceil( $i / $tot_blocks * 100 ),
                ] );
            }
        }
        $translated_text = serialize_blocks( $tr_blocks );
        $translated_text = $this->translate_links( $translated_text, $locale, $current_locale );
        try {
            $title = $this->ai_engine->translate(
                $post->post_title,
                $current_locale_name,
                $locale_name,
                $id
            );
            if ( str_ends_with( $title, "." ) ) {
                $title = substr( $title, 0, strlen( $title ) - 1 );
            }
            $meta = $this->translate_meta( $id, $current_locale_name, $locale_name );
            $excerpt = get_the_excerpt( $id );
            if ( trim( $excerpt ) ) {
                $excerpt = $this->ai_engine->translate(
                    $excerpt,
                    $current_locale_name,
                    $locale_name,
                    $id
                );
            }
            $this->create_translation(
                $id,
                $title,
                $translated_text,
                $excerpt,
                $meta,
                $current_locale,
                $locale
            );
            delete_post_meta( $id, POLYLAI_KEY );
        } catch ( Exception $e ) {
            polylai_Utils::db_log( 'error', 'translate', $e->getMessage() );
        }
    }

}
