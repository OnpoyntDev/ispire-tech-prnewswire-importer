<?php

if (!defined('ABSPATH')) {
    exit;
}

class PRN_Admin
{
    /**
     * Init hooks
     */
    public static function init()
    {
        add_action(
            'admin_menu',
            [__CLASS__, 'add_menu']
        );

        add_action(
            'admin_post_prn_manual_import',
            [__CLASS__, 'handle_manual_import']
        );
    }

    /**
     * Add admin page
     */
    public static function add_menu()
    {
        add_menu_page(
            'PR Newswire Importer',
            'PR Newswire',
            'manage_options',
            'prn-importer',
            [__CLASS__, 'render_page'],
            'dashicons-rss',
            25
        );
    }

    /**
     * Render admin UI
     */
    public static function render_page()
    {
        ?>

        <div class="wrap">

            <h1>
                PR Newswire Importer
            </h1>

            <p>
                Import PR Newswire RSS releases into WordPress posts.
            </p>

            <hr>

            <h2>
                Manual Import
            </h2>

            <p>
                Click the button below to run the importer immediately.
            </p>

            <form
                method="POST"
                action="<?php echo esc_url(
                    admin_url(
                        'admin-post.php'
                    )
                ); ?>"
            >

                <input
                    type="hidden"
                    name="action"
                    value="prn_manual_import"
                >

                <?php wp_nonce_field(
                    'prn_manual_import'
                ); ?>

                <?php submit_button(
                    'Run Import Now',
                    'primary large'
                ); ?>

            </form>

            <hr>

            <h2>
                Cron Information
            </h2>

            <table class="widefat striped">

                <tbody>

                    <tr>

                        <td>
                            Cron Hook
                        </td>

                        <td>
                            <code>
                                prn_import_cron
                            </code>
                        </td>

                    </tr>

                    <tr>

                        <td>
                            Next Scheduled Run
                        </td>

                        <td>

                            <?php

                            $timestamp =
                                wp_next_scheduled(
                                    'prn_import_cron'
                                );

                            if ($timestamp) {

                                echo esc_html(
                                    wp_date(
                                        'Y-m-d H:i:s',
                                        $timestamp
                                    )
                                );

                            } else {

                                echo 'Not scheduled';
                            }

                            ?>

                        </td>

                    </tr>

                </tbody>

            </table>

        </div>

        <?php
    }

    /**
     * Handle manual import
     */
    public static function handle_manual_import()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        check_admin_referer(
            'prn_manual_import'
        );

        PRN_Logger::log(
            'Manual import triggered'
        );

        PRN_Importer::run();

        wp_safe_redirect(
            admin_url(
                'admin.php?page=prn-importer&success=1'
            )
        );

        exit;
    }
}

/**
 * Boot admin UI
 */
PRN_Admin::init();