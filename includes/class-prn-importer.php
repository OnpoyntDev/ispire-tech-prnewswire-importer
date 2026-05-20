<?php

if (!defined('ABSPATH')) {
    exit;
}

class PRN_Importer
{
    /**
     * RSS feed URL
     */
    const RSS_URL =
        'https://www.prnewswire.com/rss/news-releases-list.rss?company=ispire-technology-inc';

    /**
     * Maximum imports per run
     */
    const MAX_IMPORTS = 10;

    /**
     * Main cron runner
     */
    public static function run()
    {
        PRN_Logger::log(
            'Starting import'
        );

        /**
         * Ensure feed parser exists
         */
        require_once ABSPATH .
            WPINC .
            '/feed.php';

        /**
         * Fetch RSS
         */
        $feed = fetch_feed(
            self::RSS_URL
        );

        if (is_wp_error($feed)) {

            PRN_Logger::error(
                'RSS fetch failed',
                [
                    'error' => $feed->get_error_message(),
                ]
            );

            return;
        }

        /**
         * Get feed items
         */
        $items = $feed->get_items(
            0,
            self::MAX_IMPORTS
        );

        if (empty($items)) {

            PRN_Logger::warning(
                'No RSS items found'
            );

            return;
        }

        PRN_Logger::log(
            'RSS items loaded',
            [
                'count' => count($items),
            ]
        );

        /**
         * Import each item
         */
        foreach ($items as $item) {

            try {

                self::import_item($item);

            } catch (Exception $e) {

                PRN_Logger::error(
                    'Item import failed',
                    [
                        'message' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]
                );
            }
        }

        PRN_Logger::log(
            'Import completed'
        );
    }

    /**
     * Import single RSS item
     */
    private static function import_item($item)
    {
        $guid = trim(
            (string) $item->get_id()
        );

        if (!$guid) {

            PRN_Logger::warning(
                'Skipping item without GUID'
            );

            return;
        }

        /**
         * Prevent duplicates
         */
        $existing = get_posts([
            'post_type' => 'post',
            'posts_per_page' => 1,
            'meta_key' => 'rss_guid',
            'meta_value' => $guid,
            'fields' => 'ids',
        ]);

        if (!empty($existing)) {

            PRN_Logger::log(
                'Skipping existing post',
                [
                    'guid' => $guid,
                ]
            );

            return;
        }

        /**
         * Core data
         */
        $title = wp_strip_all_tags(
            $item->get_title()
        );

        $source_url = esc_url_raw(
            $item->get_link()
        );

        $description = $item->get_description();

        $content = wp_kses_post(
            $description
        );

        $excerpt = wp_trim_words(
            wp_strip_all_tags($description),
            40
        );

        $date = $item->get_date('Y-m-d H:i:s');

        PRN_Logger::log(
            'Creating post',
            [
                'title' => $title,
            ]
        );

        /**
         * Create post
         */
        $post_id = wp_insert_post([
            'post_type' => 'post',
            'post_status' => 'publish',
            'post_title' => $title,
            'post_content' => self::build_content(
                $content,
                $source_url
            ),
            'post_excerpt' => $excerpt,
            'post_date' => $date,
        ]);

        if (
            is_wp_error($post_id) ||
            !$post_id
        ) {

            throw new Exception(
                is_wp_error($post_id)
                    ? $post_id->get_error_message()
                    : 'Unknown post creation error'
            );
        }

        /**
         * Store metadata
         */
        update_post_meta(
            $post_id,
            'rss_guid',
            $guid
        );

        update_post_meta(
            $post_id,
            'source_url',
            $source_url
        );

        /**
         * Import taxonomies
         */
        self::import_taxonomies(
            $post_id,
            $item
        );

        /**
         * Import featured image
         */
        self::import_featured_image(
            $post_id,
            $item,
            $title
        );

        PRN_Logger::log(
            'Post imported',
            [
                'post_id' => $post_id,
                'title' => $title,
            ]
        );
    }

    /**
     * Build article HTML
     */
    private static function build_content(
        $content,
        $source_url
    ) {

        ob_start();

        ?>

        <article class="press-release">

            <div class="press-release__content">

                <?php echo wp_kses_post($content); ?>

            </div>

            <p>

                <a
                    href="<?php echo esc_url($source_url); ?>"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    Read original release

                </a>

            </p>

        </article>

        <?php

        return ob_get_clean();
    }

    /**
     * Import categories + tags
     */
    private static function import_taxonomies(
        $post_id,
        $item
    ) {

        $categories = [];
        $tags = [];

        /**
         * Raw feed data
         */
        $data = $item->data;

        /**
         * PRN industries => categories
         */
        if (
            !empty(
                $data['child']['']['prn:industry']
            )
        ) {

            foreach (
                $data['child']['']['prn:industry']
                as $industry
            ) {

                $name = trim(
                    strip_tags(
                        $industry['data'] ?? ''
                    )
                );

                if (!$name) {
                    continue;
                }

                $term_id =
                    PRN_Taxonomy::get_or_create_category(
                        $name
                    );

                if ($term_id) {
                    $categories[] = $term_id;
                }
            }
        }

        /**
         * PRN subjects => tags
         */
        if (
            !empty(
                $data['child']['']['prn:subject']
            )
        ) {

            foreach (
                $data['child']['']['prn:subject']
                as $subject
            ) {

                $name = trim(
                    strip_tags(
                        $subject['data'] ?? ''
                    )
                );

                if (!$name) {
                    continue;
                }

                $term_id =
                    PRN_Taxonomy::get_or_create_tag(
                        $name
                    );

                if ($term_id) {
                    $tags[] = $term_id;
                }
            }
        }

        /**
         * Assign categories
         */
        if (!empty($categories)) {

            wp_set_post_terms(
                $post_id,
                array_unique($categories),
                'category'
            );
        }

        /**
         * Assign tags
         */
        if (!empty($tags)) {

            wp_set_post_terms(
                $post_id,
                array_unique($tags),
                'post_tag'
            );
        }
    }

    /**
     * Import featured image
     */
    private static function import_featured_image(
        $post_id,
        $item,
        $title
    ) {

        $image_url = null;

        /**
         * Try enclosure first
         */
        $enclosure = $item->get_enclosure();

        if ($enclosure) {
            $image_url = $enclosure->get_link();
        }

        /**
         * Fallback to media content
         */
        if (
            !$image_url &&
            !empty(
                $item->data['child']['']['media:content'][0]['attribs']['']['url']
            )
        ) {

            $image_url =
                $item->data['child']['']['media:content'][0]['attribs']['']['url'];
        }

        if (!$image_url) {

            PRN_Logger::warning(
                'No image found',
                [
                    'post_id' => $post_id,
                ]
            );

            return;
        }

        /**
         * Import image
         */
        $attachment_id =
            PRN_Media::sideload_image(
                $image_url,
                $post_id,
                $title
            );

        if (!$attachment_id) {
            return;
        }

        /**
         * Set featured image
         */
        set_post_thumbnail(
            $post_id,
            $attachment_id
        );

        PRN_Logger::log(
            'Featured image attached',
            [
                'post_id' => $post_id,
                'attachment_id' => $attachment_id,
            ]
        );
    }
}