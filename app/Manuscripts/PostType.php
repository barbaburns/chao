<?php

namespace App\Manuscripts;

class PostType
{
    public static function register()
    {
        register_post_type('manuscrito', [
            'labels' => [
                'name' => __('Manuscritos', 'chao'),
                'singular_name' => __('Manuscrito', 'chao'),
                'add_new_item' => __('Adicionar manuscrito', 'chao'),
                'edit_item' => __('Editar manuscrito', 'chao'),
            ],
            'public' => true,
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-media-document',
            'supports' => ['title', 'editor', 'thumbnail'],
            'rewrite' => ['slug' => 'manuscritos'],
            'has_archive' => true,
        ]);
    }
}
