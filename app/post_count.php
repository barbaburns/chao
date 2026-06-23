<?php

namespace App;

add_action('wp_head', function($post_id) {
    if (!is_singular('post')) {
        return;
    }

    if (empty($post_id)) {
        global $post;
        $post_id = $post->ID;
    }

    $count_key = 'chao_post_views_count';
    $count = get_post_meta($post_id, $count_key, true);

    if ($count == '') {
        $count = 0;
        delete_post_meta($post_id, $count_key);
        add_post_meta($post_id, $count_key, '0');
    } else {
        $count++;
        update_post_meta($post_id, $count_key, $count);
    }
});

remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);
