<?php

namespace App\Controllers;

use Sober\Controller\Controller;

class SingleProduct extends Controller
{
    /**
     * Retorna o banner do produto
     *
     * @return false|string
     */
    public function getFeaturedImage()
    {
        $image = get_field('product_featured_image');
        $image_mobile = get_field('product_featured_mobile');

        if (!$image) {
            return false;
        }

        $image_html = wp_get_attachment_image($image, 'product-featured', false, ['class' => 'image-desktop']);
        $image_mobile_html = $image_mobile ? wp_get_attachment_image($image_mobile, 'product-featured-mobile', false, ['class' => 'image-mobile']) : $image_html;

        $data = [
            'image'         => $image_html,
            'image_mobile'  => $image_mobile_html,
        ];

        return $data;
    }

    /**
     * Retorna a lista de autores
     *
     * @return array
     */
    public static function getProductAuthors()
    {
        if (!have_rows('authors_list')) {
            return false;
        }

        $data = [];
        while (have_rows('authors_list')) {
            the_row();

            $role = get_sub_field('author_role');
            $authors = get_sub_field('authors');
            

            $authors_info = [];
            foreach ($authors as $author_id) {
                $url = get_field('author_link', $author_id);
                $url_text = get_field('author_link_text', $author_id);
                $resumo = get_field('resumo', $author_id);

                $authors_info[] = [
                    'title'             => esc_html(get_the_title($author_id)),
                    'excerpt'           => get_the_excerpt($author_id),
                    'url'               => esc_url(get_the_permalink($author_id)),
                    'extra_url'         => esc_url($url),
                    'resumo'            => $resumo,
                    'extra_url_text'    => esc_html($url_text)
                ];
            }

            $data[] = [
                'role'          => esc_html($role),
                'role_class'    => sanitize_title($role),
                'authors'       => $authors_info,
                
            ];
        }

        return $data;
    }

    /**
     * Retorna os links dos PDFs
     *
     * @return array
     */
    public static function getProductAttachments()
    {
        $sample = get_field('product_sample');
        $release = get_field('product_release_file');

        if (!$sample && !$release) {
            return false;
        }

        $data = [
            'sample'    => esc_url($sample),
            'release'   => esc_url($release),
        ];

        return $data;
    }

    /**
     * Retorna as especificações do produto
     *
     * @return false|array
     */
    public static function getProductSpecifications()
    {
        if (!have_rows('product_specification')) {
            return false;
        }

        $data = [];
        while (have_rows('product_specification')) {
            the_row();

            $title = esc_html(get_sub_field('title'));
            $value = esc_html(get_sub_field('value'));

            $data[$title] = $value;
        }

        return $data;
    }

    /**
     * Retorna os links externos do produto
     *
     * @return false|array
     */
    public static function getBuyLinks()
    {
        if (!have_rows('buy_links')) {
            return false;
        }

        $data = [];
        while (have_rows('buy_links')) {
            the_row();

            $title = esc_html(get_sub_field('buy_link_bookstore'));
            $url = esc_url(get_sub_field('buy_link'));

            $data[] = [
                'title'     => $title,
                'url'       => $url,
            ];
        }

        return $data;
    }
}
