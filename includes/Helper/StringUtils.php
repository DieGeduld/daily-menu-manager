<?php
namespace DailyMenuManager\Helper;

class StringUtils {
    /**
     * Sanitizes text by removing special characters and formatting it for use as identifiers
     *
     * @param string $text The text to sanitize
     * @return string Sanitized text
     */
    public static function hard_sanitize($text) {
        $text = sanitize_text_field($text);
        if (function_exists('transliterator_transliterate')) {
            $text = transliterator_transliterate('Any-Latin; Latin-ASCII', $text);
        } else {
            $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        }
        $text = strtolower($text);
        $text = str_replace(' ', '_', $text);
        $text = preg_replace('/[^a-z_]/', '', $text);
        $text = preg_replace('/_+/', '_', $text);
        $text = trim($text, '_');
        return $text;
    }
}