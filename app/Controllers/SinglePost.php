<?php

namespace App\Controllers;

use Sober\Controller\Controller;

class SinglePost extends Controller
{
    /**
     * Retorna lista de posts relacionados
     *
     * @return false|array
     */
    public function getRelatedPosts()
    {
        $posts = get_field('related_posts');

        if (!$posts) {
            return false;
        }

        $data = [];
        foreach ($posts as $post_id) {
            $title = get_the_title($post_id);
            $url = get_permalink($post_id);

            $data[] = [
                'title'     => esc_html($title),
                'url'       => esc_url($url)
            ];
        }

        return $data;
    }

    /**
     * Retorna os livros relacionados ao post
     *
     * @return false|array
     */
    public function getRelatedBooks()
    {
        $books = get_field('related_books');

        if (!$books) {
            return false;
        }

        return $books;
    }
}
