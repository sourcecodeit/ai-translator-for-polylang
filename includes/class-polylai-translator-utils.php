<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
class polylai_Utils {
    public static function is_debug() {
        return defined( 'POLYAI_DEBUG' ) && POLYAI_DEBUG;
    }

    public static function db_log(
        $type,
        $operation,
        $message = null,
        $post_id = null,
        $post_title = null,
        $text = null
    ) {
        global $wpdb;
        if ( $type == 'debug' && !polylai_Utils::is_debug() ) {
            return;
        }
        $table_name = $wpdb->prefix . 'polylai_log';
        $wpdb->insert( $table_name, array(
            'type'       => $type,
            'operation'  => $operation,
            'post_id'    => $post_id,
            'post_title' => $post_title,
            'message'    => $message,
            'text'       => $text,
        ) );
    }

    public static function get_ai_engine() {
        $options = get_option( 'polylai_translator_options' );
        if ( !isset( $options['ai_engine'] ) || !polylai_fs()->can_use_premium_code() ) {
            return 'openai';
        }
        return $options['ai_engine'];
    }

    public static function allowed_langs() {
        $max = 1;
        $allowed = [];
        if ( function_exists( 'pll_the_languages' ) ) {
            $default_lang = pll_default_language( 'slug' );
            $allowed[] = $default_lang;
            $index = 0;
            $langs = pll_the_languages( [
                'raw'           => 1,
                'hide_if_empty' => 0,
            ] );
            foreach ( $langs as $lang ) {
                if ( $lang['slug'] == $default_lang ) {
                    continue;
                }
                if ( $index < $max ) {
                    $allowed[] = $lang["slug"];
                }
                $index++;
            }
        }
        return $allowed;
    }

    public static function sanitize_options( $options ) {
        $old_options = get_option( 'polylai_translator_options' );
        if ( $options['ai_engine'] == 'claude' && trim( $options['claude_key'] ) == '' ) {
            add_settings_error(
                'polylai_translator_options',
                'polylai_translator_options',
                __( 'Claude API key is invalid', 'ai-translator-for-polylang' ),
                'error'
            );
            $error = true;
        }
        if ( $options['ai_engine'] == 'openai' && empty( trim( $options['openai_key'] ) ) ) {
            add_settings_error(
                'polylai_translator_options',
                'polylai_translator_options',
                __( 'OpenAI API key is invalid', 'ai-translator-for-polylang' ),
                'error'
            );
            $error = true;
        }
        return ( $error ? $old_options : $options );
    }

    public static function split_text( $text, $chunk_size ) {
        $words = explode( ' ', $text );
        if ( count( $words ) <= $chunk_size ) {
            return [implode( ' ', $words )];
        }
        $chunks = [];
        $current_chunk = [];
        foreach ( $words as $i => $word ) {
            $current_chunk[] = $word;
            if ( count( $current_chunk ) + 1 >= $chunk_size ) {
                $chunks[] = implode( ' ', $current_chunk );
                $current_chunk = [];
            }
        }
        if ( count( $current_chunk ) > 0 ) {
            $chunks[] = implode( ' ', $current_chunk );
        }
        return $chunks;
    }

}
