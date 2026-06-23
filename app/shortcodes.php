<?php

namespace App;

/**
 * Shortcode
 * [read-more]text[/read-more]
 */
add_shortcode('read-more', function ($atts, $content = null) {
    $args = shortcode_atts([
        'mobile'        => false,
    ], $atts);

    $text = $content ? apply_filters('the_content', $content) : 'sem texto';
    $class = $args['mobile'] ? 'mobile-only' : '';

    $html = '<div class="read-more '. $class .'">'.do_shortcode($text).'</div><button class="btn-read-more">leia mais</button>';

    return $html;
});
