<?php

namespace App\Controllers\Partials;

trait ProductExtra
{
    /**
     * Retorna as informações do ACF clipping
     *
     * @param int $id ID do produto
     * @return false|array
     */
    public static function getProductClipping($id)
    {
        $title = get_field('clipping_title', $id);

        if(!$title) {
            return false;
        }

        $source = get_field('clipping_source', $id);
        $url = get_field('clipping_link', $id);

        $data = [
            'title'     => esc_html($title),
            'source'    => esc_html($source),
            'url'       => esc_url($url),
        ];

        return $data;
    }

    /**
     * Retorna as informações do ACF áudio
     *
     * @param int $id ID do produto
     * @return false|array
     */
    public static function getProductAudio($id)
    {
        $audio = get_field('audio_file', $id);

        if(!$audio) {
            return false;
        }

        $html = do_shortcode('[audio mp3="'.$audio['url'].'"]');

        $data = [
            'player'        => $html,
            'title'         => esc_html($audio['title']),
            'description'   => esc_html($audio['description']),
        ];

        return $data;
    }

    /**
     * Retorna as informações do ACF excerto
     *
     * @param int $id ID do produto
     * @return false|array
     */
    public static function getProductExcerpt($id)
    {
        $text = wp_kses_post( get_field('excerpt_text', $id) );

        if(!$text) {
            return false;
        }

        $url = get_field('excerpt_link', $id);

        $data = [
            'text'      => esc_html($text),
            'url'       => esc_url($url),
        ];

        return $data;
    }

    /**
     * Retorna as informações do ACF manuscrito
     *
     * @param int $id ID do produto
     * @return false|array
     */
    public static function getProductManuscript($id)
    {
        $gallery = get_field('manuscript_gallery', $id);
        $manuscriptPost = get_field('manuscrito', $id);

        if(!$gallery || !is_array($gallery)) {
            return false;
        }

        $images = array_values(array_filter(array_map(function ($image) {
            if (empty($image['ID'])) {
                return null;
            }

            return [
                'image' => wp_get_attachment_image($image['ID'], 'large'),
                'caption' => esc_html($image['caption'] ?? ''),
            ];
        }, $gallery)));

        if(!$images) {
            return false;
        }

        $manuscriptUrl = '';

        if ($manuscriptPost) {
            $manuscriptPostId = is_object($manuscriptPost) ? ($manuscriptPost->ID ?? null) : $manuscriptPost;

            if ($manuscriptPostId) {
                $manuscriptUrl = esc_url(get_permalink($manuscriptPostId));
            }
        }

        return [
            'images' => $images,
            'url' => $manuscriptUrl,
        ];
    }

    /**
     * Retorna as informações do ACF vídeo
     *
     * @param int $id ID do produto
     * @return false|array
     */
    public static function getProductVideo($id)
    {
        $player = get_field('video_embed', $id);

        if(!$player) {
            return false;
        }

        $title = get_field('video_title', $id);
        $caption = get_field('video_caption', $id);

        $data = [
            'player'   => $player,
            'title'    => esc_html($title),
            'caption'  => esc_html($caption),
        ];

        return $data;
    }

    /**
     * Retorna a descrição curta do produto
     *
     * @return string
     */
    public static function getProductShortDescription()
    {
        global $product;
        $text = $product->get_short_description();

        return wp_kses_post($text);
    }
}
