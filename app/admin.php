<?php

namespace App;

/**
 * Theme customizer
 */
add_action('customize_register', function (\WP_Customize_Manager $wp_customize) {
    // Add postMessage support
    $wp_customize->get_setting('blogname')->transport = 'postMessage';
    $wp_customize->selective_refresh->add_partial('blogname', [
        'selector' => '.brand',
        'render_callback' => function () {
            bloginfo('name');
        }
    ]);
});

/**
 * Customizer JS
 */
add_action('customize_preview_init', function () {
    wp_enqueue_script('sage/customizer.js', asset_path('scripts/customizer.js'), ['customize-preview'], null, true);
});

/**
 * Adiciona coluna "visualizações" no CPT post
 */
add_action('manage_post_posts_columns', function ($columns) {
    $end = array_splice($columns, 5);
    $columns = $columns + ['count' => 'Visualizações'] + $end;

    return $columns;
}, 10, 2);

add_action('manage_posts_custom_column', function ($column, $post_id) {
    if ($column == 'count') {
        $count = get_post_meta($post_id, 'chao_post_views_count', true);

        if ($count == '') {
            echo 0;
        } else {
            echo $count;
        }
    }
}, 10, 2);
