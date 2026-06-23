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

        $job = get_post_meta($postId, 'chao_manuscript_worker_last_job', true);
        $lastResponse = get_post_meta($postId, 'chao_manuscript_worker_last_response', true);
        $review = get_post_meta($postId, 'chao_manuscript_page_review', true);
        $query = isset($_GET['busca']) ? static::wpUnslash($_GET['busca']) : '';
        $resolvedJob = Search::mergeJobWithWorkerResponse(
            is_array($job) ? $job : [],
            is_array($lastResponse) ? $lastResponse : []
        );

        $data['manuscript_search'] = Search::buildViewModel(
            $resolvedJob,
            is_array($review) ? $review : [],
            is_string($query) ? $query : ''
        );

        return $data;
    }

    protected static function wpUnslash($value)
    {
        if (function_exists('wp_unslash')) {
            return wp_unslash($value);
        }

        return stripslashes((string) $value);
    }
}
