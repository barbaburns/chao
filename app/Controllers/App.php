<?php

namespace App\Controllers;

use Sober\Controller\Controller;

class App extends Controller
{
    use Partials\ProductExtra;

    public function siteName()
    {
        return get_bloginfo('name');
    }

    public static function blogLink()
    {
        return esc_url(get_permalink(get_option('page_for_posts')));
    }

    public static function storeLink()
    {
        return wc_get_page_permalink('shop');
    }

    public function getEmail()
    {
        $email = get_field('contact_email', 'option');
        return sprintf('<a href="mailto:%1$s">%1$s</a>', antispambot($email));
    }

    /**
     * Retorna a lista de links das redes sociais
     * @return boolean|array
     */
    public function getSocialNetworks()
    {
        if (!have_rows('social_networks', 'option')) {
            return false;
        }

        $data = [];
        while (have_rows('social_networks', 'option')) {
            the_row();

            $icon = esc_attr(get_sub_field('network'));
            $url = esc_url(get_sub_field('link'));
            $brand = preg_replace('/fa-([a-zA-z0-9]*).*/', '$1', $icon);

            $data[] = [
                'icon'      => $icon,
                'brand'     => $brand,
                'url'       => $url,
            ];
        }

        return $data;
    }

    /**
     * Retorna lista de chamadas
     *
     * @return false|array
     */
    public function getShortTexts()
    {
        if (!have_rows('short_texts', 'option')) {
            return false;
        }

        $data = [];
        while (have_rows('short_texts', 'option')) {
            the_row();

            $text = get_sub_field('short_text');
            $link = get_sub_field('short_text_link');

            $data[] = [
                'text'  => esc_html($text),
                'url'   => esc_url($link),
            ];
        }

        return $data;
    }

    public static function title()
    {
        if (is_home()) {
            if ($home = get_option('page_for_posts', true)) {
                return get_the_title($home);
            }
            return __('Latest Posts', 'chao');
        }
        if (is_month()) {
            return sprintf('<span>Arquivo:</span> %s', get_the_date('F Y'));
        }
        if (is_category()) {
            if (is_category(['livreiros', 'professores'])) {
                return single_cat_title('', false);
            } else {
                return single_cat_title('', false);
            }
        }
        if (is_tag()) {
            return single_tag_title('<span>Tag:</span> ', false);
        }
        if (is_archive()) {
            return post_type_archive_title('', false);
        }
        if (is_search()) {
            return 'Busca';
        }
        if (is_404()) {
            return __('Not Found', 'chao');
        }
        return get_the_title();
    }

    /**
     * Retorna texto de abertura das páginas
     *
     * @param string $field campo do ACF
     * @return false|string
     */
    public static function getHeaderText($field)
    {
        if ('header_blog' == $field && is_category()) {
            $cat_id = get_query_var('cat');
            $description = category_description($cat_id);

            if ($description) {
                return strip_tags($description);
            }
        }

        $text = get_field($field, 'option');
        return esc_html($text);
    }

    /**
     * Gera HTML da paginação
     *
     * @return string
     */
    public static function getPagination()
    {
        $args = [
            'prev_text'     => '<',
            'next_text'     => '>',
            'type'          => 'list'
        ];

        $html = paginate_links($args);
        return $html;
    }

    /**
     * Retorna o título da página informda
     *
     * @param int $id ID da página
     * @return string
     */
    public static function getPageTitle($id)
    {
        $title = get_the_title($id);
        return esc_html($title);
    }

    /**
     * Retorna lista de categorias
     *
     * @return array
     */
    public static function getAllCategories()
    {
        $instagram_object = get_term_by('slug', 'instagram', 'category');

        $args = [
            'fields'    => 'id=>name'
        ];

        if ($instagram_object) {
            $args['exclude'] = $instagram_object->term_id;
        }

        $categories = get_categories($args);

        $data = [];
        foreach ($categories as $id => $category) {
            $data[] = [
                'title'     => esc_html($category),
                'url'       => esc_url(get_category_link($id)),
                'class'     => is_category($id) ? 'active' : '',
            ];
        }

        return $data;
    }

    /**
     * Retorna lista de tags
     *
     * @return array
     */
    public static function getAllTags()
    {
        $args = [
            'fields'    => 'id=>name'
        ];

        $tags = get_tags($args);

        $data = [];
        foreach ($tags as $id => $tag) {
            $data[] = [
                'title'     => esc_html($tag),
                'url'       => esc_url(get_tag_link($id)),
                'class'     => is_tag($id) ? 'active' : '',
            ];
        }

        return $data;
    }

    /**
     * Retorna lista de meses do blog
     *
     * @return array
     */
    public static function getAllMonths()
    {
        $args = [
            'echo'      => 0,
            'format'    => 'custom',
            'before'    => '',
            'after'     => '|',
        ];

        $months = wp_get_archives($args);

        $months_array = explode('|', $months);
        $ymonths_array = array_filter($months_array, function ($item) {
            // Remove empty whitespace item from array
            return trim($item) !== '';
        });

        $data = [];
        foreach ($months_array as $item) {
            $month = trim($item);
            preg_match("/href='?([^\']*)' ?([^>]*)>(.+)<\/a>/", $month, $month_vars);
            if (!empty($month_vars)) {
                $data[] = [
                    'title'     => $month_vars[3],
                    'url'       => $month_vars[1],
                    'class'     => $month_vars[2] == 'aria-current="page"' ? 'active' : '',
                ];
            }
        }

        return $data;
    }

    /**
     * Retorna lista de posts populares
     *
     * @return false|array
     */
    public static function getPopularPosts()
    {
        $show_popular = get_field('show_popular_posts', 'option');

        if (!$show_popular) {
            return false;
        }

        $posts_count = get_field('popular_posts', 'option');

        $args = [
            'posts_per_page'    => $posts_count,
            'meta_key'          => 'chao_post_views_count',
            'no_found_rows'     => true,
            'fields'            => 'ids',
            'orderby'           => 'meta_value_num',
            'order'             => 'DESC',
        ];

        $query = new \WP_Query($args);

        if (!$query->have_posts()) {
            return false;
        }

        $data = [];
        foreach ($query->posts as $post_id) {
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
     * Retorna a lista de livros que aparecerão no menu
     *
     * @return array
     */
    public function getBooksList()
    {
        $args = [
            'post_type'         => 'product',
            'posts_per_page'    => 99,
            'no_found_rows'     => true,
            'fields'            => 'ids',
            'meta_query'        => [
                [
                    'key'           => 'book_list_show',
                    'value'         => '1',
                    'type'          => 'NUMERIC',
                ]
            ],
        ];

        $query = new \WP_Query($args);

        if (!$query->have_posts()) {
            return false;
        }

        $data = [];
        foreach ($query->posts as $id) {
            $title = get_the_title($id);
            $url = get_permalink($id);
            $author = get_field('book_list_author', $id);
            $color = get_field('book_list_color', $id);

            $data[] = [
                'title'     => esc_html($title),
                'url'       => esc_url($url),
                'author'    => esc_html($author),
                'color'     => $color,
            ];
        }

        return $data;
    }

    public static function excludeAccountLink()
    {
        $url = add_query_arg('your-subject', 'Exclusão de conta', home_url('/contato/'));
        return $url;
    }

    public static function getPageLink($slug)
    {
        $url = get_permalink(get_page_by_path($slug));
        return esc_url($url);
    }
}
