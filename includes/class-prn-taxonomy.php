<?php

if (!defined('ABSPATH')) {
    exit;
}

class PRN_Taxonomy
{
    /**
     * Get or create category
     */
    public static function get_or_create_category($name)
    {
        $name = self::clean_term_name($name);

        if (!$name) {
            return false;
        }

        /**
         * Check existing term
         */
        $existing = term_exists(
            $name,
            'category'
        );

        if (
            $existing &&
            !is_wp_error($existing)
        ) {
            return intval(
                is_array($existing)
                    ? $existing['term_id']
                    : $existing
            );
        }

        /**
         * Create term
         */
        $created = wp_insert_term(
            $name,
            'category'
        );

        if (is_wp_error($created)) {

            PRN_Logger::error(
                'Failed creating category',
                [
                    'name' => $name,
                    'error' => $created->get_error_message(),
                ]
            );

            return false;
        }

        PRN_Logger::log(
            'Created category',
            [
                'name' => $name,
                'term_id' => $created['term_id'],
            ]
        );

        return intval($created['term_id']);
    }

    /**
     * Get or create tag
     */
    public static function get_or_create_tag($name)
    {
        $name = self::clean_term_name($name);

        if (!$name) {
            return false;
        }

        /**
         * Check existing tag
         */
        $existing = term_exists(
            $name,
            'post_tag'
        );

        if (
            $existing &&
            !is_wp_error($existing)
        ) {
            return intval(
                is_array($existing)
                    ? $existing['term_id']
                    : $existing
            );
        }

        /**
         * Create tag
         */
        $created = wp_insert_term(
            $name,
            'post_tag'
        );

        if (is_wp_error($created)) {

            PRN_Logger::error(
                'Failed creating tag',
                [
                    'name' => $name,
                    'error' => $created->get_error_message(),
                ]
            );

            return false;
        }

        PRN_Logger::log(
            'Created tag',
            [
                'name' => $name,
                'term_id' => $created['term_id'],
            ]
        );

        return intval($created['term_id']);
    }

    /**
     * Clean RSS taxonomy values
     */
    private static function clean_term_name($name)
    {
        /**
         * Handle arrays from malformed RSS
         */
        if (is_array($name)) {

            if (isset($name['data'])) {
                $name = $name['data'];
            } else {
                $name = implode(
                    ' ',
                    array_map(
                        'strval',
                        $name
                    )
                );
            }
        }

        /**
         * Convert objects safely
         */
        if (is_object($name)) {
            $name = wp_json_encode($name);
        }

        /**
         * Final cleanup
         */
        $name = wp_strip_all_tags(
            html_entity_decode(
                (string) $name,
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            )
        );

        $name = trim(
            preg_replace(
                '/\\s+/',
                ' ',
                $name
            )
        );

        return $name;
    }
}