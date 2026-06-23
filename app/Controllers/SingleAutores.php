<?php

namespace App\Controllers;

use Sober\Controller\Controller;

class SingleAutores extends Controller
{
    /**
     * Retorna lista de autores
     * em ordem alfabética
     *
     * @return array
     */
    public function getAllAuthors()
    {
        $args = [
            'post_type'         => 'autores',
            'posts_per_page'    => 99,
            'no_found_rows'     => true,
            'fields'            => 'ids',
            'orderby'           => 'title',
            'order'             => 'ASC',
        ];

        $query = new \WP_Query($args);

        if ($query->have_posts()) {
            foreach ($query->posts as $post_id) {
                $data[] = [
                    'title'     => esc_html(get_the_title($post_id)),
                    'url'       => esc_url(get_permalink($post_id)),
                    'class'     => $post_id == get_the_ID() ? 'active' : '',
                ];
            }
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
