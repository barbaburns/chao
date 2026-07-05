<?php

namespace App\Manuscripts;

class Frontend
{
    public static function register()
    {
        add_filter('sage/template/single-manuscrito/data', [static::class, 'withSingleTemplateData'], 10, 2);
    }

    public static function withSingleTemplateData($data, $template = null)
    {
        $postId = get_the_ID();

        if (!$postId) {
            return $data;
        }

        $snapshot = get_post_meta($postId, 'chao_manuscript_import_last_snapshot', true);
        $query = isset($_GET['busca']) ? static::wpUnslash($_GET['busca']) : '';

        $data['manuscript_search'] = Search::buildViewModel(
            is_array($snapshot) ? $snapshot : ['pages' => []],
            is_string($query) ? $query : ''
        );
        $data['related_books'] = static::getRelatedBooks($postId);

        return $data;
    }

    protected static function getRelatedBooks($postId)
    {
        if (!function_exists('get_field')) {
            return [];
        }

        $books = get_field('related_books', $postId);

        if ($books === null || $books === '' || $books === false) {
            return [];
        }

        if (!is_array($books)) {
            $books = [$books];
        }

        return array_values(array_filter(array_map(function ($book) {
            if (is_object($book) && isset($book->ID)) {
                return $book;
            }

            if (function_exists('get_post')) {
                $post = get_post($book);

                if ($post && isset($post->ID)) {
                    return $post;
                }
            }

            return null;
        }, $books)));
    }

    protected static function wpUnslash($value)
    {
        if (function_exists('wp_unslash')) {
            return wp_unslash($value);
        }

        return stripslashes((string) $value);
    }
}
