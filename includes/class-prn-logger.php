<?php

if (!defined('ABSPATH')) {
    exit;
}

class PRN_Logger
{
    /**
     * Standard log
     */
    public static function log($message, $context = [])
    {
        self::write(
            'INFO',
            $message,
            $context
        );
    }

    /**
     * Error log
     */
    public static function error($message, $context = [])
    {
        self::write(
            'ERROR',
            $message,
            $context
        );
    }

    /**
     * Warning log
     */
    public static function warning($message, $context = [])
    {
        self::write(
            'WARNING',
            $message,
            $context
        );
    }

    /**
     * Debug log
     */
    public static function debug($message, $context = [])
    {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        self::write(
            'DEBUG',
            $message,
            $context
        );
    }

    /**
     * Internal logger
     */
    private static function write(
        $level,
        $message,
        $context = []
    ) {
        $timestamp = current_time('mysql');

        $log = sprintf(
            '[PRN IMPORTER] [%s] [%s] %s',
            $timestamp,
            $level,
            $message
        );

        if (!empty($context)) {
            $log .= ' ' . wp_json_encode(
                $context,
                JSON_UNESCAPED_SLASHES |
                JSON_UNESCAPED_UNICODE
            );
        }

        error_log($log);
    }
}