<?php

namespace App\Controllers\Partials;

trait Team
{
    /**
     * Retorna os posts do CPT equipe
     *
     * @return false|array
     */
    public function getTeam()
    {
        $data = [];

        $args = [
            'post_type'         => 'equipe',
            'posts_per_page'    => 99,
            'no_found_rows'     => true,
            'fields'            => 'ids',
            'orderby'           => 'menu_order',
            'order'             => 'ASC',
        ];

        $query = new \WP_Query($args);

        if ($query->have_posts()) {
            $data['Equipe'] = self::populateList($query->posts);
        }

        $args['post_type'] = 'colaborador';

        $query = new \WP_Query($args);

        if ($query->have_posts()) {
            $data['Colaboradores'] = self::populateList($query->posts);
        }

        return $data;
    }

    /**
     * Roda o loop pelos posts passados
     *
     * @param object $posts
     * @return array
     */
    public static function populateList($posts)
    {
        $data = [];
        foreach ($posts as $post_id) {
            $data[] = [
                'title'     => esc_html(get_the_title($post_id)),
                'url'       => esc_url(get_permalink($post_id)),
                'image'     => get_the_post_thumbnail($post_id, 'thumb-square'),
            ];
        }

        return $data;
    }
}
