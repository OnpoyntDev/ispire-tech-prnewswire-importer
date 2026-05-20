<?php

if (!defined('ABSPATH')) {
    exit;
}

class PRN_Cron
{
    /**
     * Plugin activation
     */
    public static function activate()
    {
        /**
         * Add custom 15-minute schedule
         */
        add_filter(
            'cron_schedules',
            [__CLASS__, 'add_cron_intervals']
        );

        /**
         * Schedule cron if not already scheduled
         */
        if (!wp_next_scheduled('prn_import_cron')) {

            wp_schedule_event(
                time(),
                'hourly',
                'prn_import_cron'
            );
        }

        PRN_Logger::log(
            'Cron activated'
        );
    }

    /**
     * Plugin deactivation
     */
    public static function deactivate()
    {
        wp_clear_scheduled_hook(
            'prn_import_cron'
        );

        PRN_Logger::log(
            'Cron deactivated'
        );
    }

    /**
     * Add custom cron intervals
     */
    public static function add_cron_intervals($schedules)
    {
        $schedules['hourly'] = [
            'interval' => 900,
            'display'  => __(
                'Every 15 Minutes',
                'prnewswire-importer'
            ),
        ];

        return $schedules;
    }
}

/**
 * Register custom intervals globally
 */
add_filter(
    'cron_schedules',
    ['PRN_Cron', 'add_cron_intervals']
);