<?php

namespace App\Controllers;

use Sober\Controller\Controller;

class FrontPage extends Controller
{
    /**
     * Retorna informações do banner de abertura da Home
     *
     * @return false|array
     */
    public function getHomeBanner()
    {
        $images = get_field('home_images', 'option');

        if (!$images) {
            return false;
        }

        $total = count($images);
        $select = rand(0, $total - 1);

        $title = $images[$select]['home_image_title'];
        $url_title = $images[$select]['home_image_link_title'];
        $id = $images[$select]['home_image_link'];
        $image = $images[$select]['home_image'];
        $image_mobile = $images[$select]['home_image_mobile'];

        $image_html = wp_get_attachment_image($image['ID'], 'product-featured', false, ['class' => 'image-desktop']);
        $image_mobile_html = $image_mobile ? wp_get_attachment_image($image_mobile['ID'], 'product-featured-mobile', false, ['class' => 'image-mobile']) : $image_html;

        $data = [
            'image'             => $image_html,
            'image_mobile'      => $image_mobile_html,
            'title'             => esc_html($title),
            'url_title'         => esc_html($url_title),
            'url'               => esc_url(get_permalink($id)),
        ];

        return $data;
    }

    /**
     * Retorna link featured books
     *
     * @return false|array
     */
    public function getFeaturedLink()
    {
        $link = get_field('header_our_books_link', 'option');

        return $link;
    }


    /**
     * Retorna os últimos posts do blog
     *
     * @return false|array
     */
    public function getBlogHighlights()
    {
        $show_blog = get_field('show_blog', 'option');

        if (!$show_blog) {
            return false;
        }

        $blog_posts = get_field('blog_posts', 'option');
        $blog_posts = absint($blog_posts);

        $args = [
            'posts_per_page'    => $blog_posts,
            'no_found_rows'     => true,
            'meta_query'        => [
                [
                    'key'       => 'hide_post',
                    'value'     => 1,
                    'compare'   => '!='
                ],
            ],
        ];

        $query = new \WP_Query($args);

        if (!$query->have_posts()) {
            return false;
        }

        $data = [];
        foreach ($query->posts as $post) {
            $image_html = get_the_post_thumbnail($post, 'thumb-square-big');
            $image_size = 'thumb-square-big';

            $class = get_post_class($image_size, $post);

            $data[] = [
                'title'           => esc_html($post->post_title),
                'date_raw'        => get_post_time('c', true, $post),
                'date'            => get_the_date('', $post),
                'image'           => $image_html,
                'class'           => implode(' ', $class),
                'url'             => esc_url(get_permalink($post)),
            ];
        }

        return $data;
    }

    /**
     * Retorna os livros de destaque
     *
     * @return false|array
     */
    public function getFeaturedBooks()
    {
        $show_featured_book = get_field('show_products_highlight', 'option');

        if (!$show_featured_book) {
            return false;
        }

        $featured_books = get_field('products_highlight', 'option');

        return $featured_books;
    }
}
