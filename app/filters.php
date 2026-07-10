<?php

namespace App;

/**
 * Add <body> classes
 */
add_filter('body_class', function (array $classes) {
    /** Add page slug if it doesn't exist */
    if (is_single() || is_page() && !is_front_page()) {
        if (!in_array(basename(get_permalink()), $classes)) {
            $classes[] = basename(get_permalink());
        }
    }

    /** Clean up class names for custom templates */
    $classes = array_map(function ($class) {
        return preg_replace(['/-blade(-php)?$/', '/^page-template-views/'], '', $class);
    }, $classes);

    return array_filter($classes);
});

/**
 * Add "… Continued" to the excerpt
 */
add_filter('excerpt_more', function () {
    return ' &hellip;';
});

add_filter('excerpt_length', function ($length){
    return 25;
}, 999);

/**
 * Template Hierarchy should search for .blade.php files
 */
collect([
    'index', '404', 'archive', 'author', 'category', 'tag', 'taxonomy', 'date', 'home',
    'frontpage', 'page', 'paged', 'search', 'single', 'singular', 'attachment', 'embed'
])->map(function ($type) {
    add_filter("{$type}_template_hierarchy", __NAMESPACE__.'\\filter_templates');
});

/**
 * Render page using Blade
 */
add_filter('template_include', function ($template) {
    collect(['get_header', 'wp_head'])->each(function ($tag) {
        ob_start();
        do_action($tag);
        $output = ob_get_clean();
        remove_all_actions($tag);
        add_action($tag, function () use ($output) {
            echo $output;
        });
    });
    $data = collect(get_body_class())->reduce(function ($data, $class) use ($template) {
        return apply_filters("sage/template/{$class}/data", $data, $template);
    }, []);
    if ($template) {
        echo template($template, $data);
        return get_stylesheet_directory().'/index.php';
    }
    return $template;
}, PHP_INT_MAX);

/**
 * Render comments.blade.php
 */
add_filter('comments_template', function ($comments_template) {
    $comments_template = str_replace(
        [get_stylesheet_directory(), get_template_directory()],
        '',
        $comments_template
    );

    $data = collect(get_body_class())->reduce(function ($data, $class) use ($comments_template) {
        return apply_filters("sage/template/{$class}/data", $data, $comments_template);
    }, []);

    $theme_template = locate_template(["views/{$comments_template}", $comments_template]);

    if ($theme_template) {
        echo template($theme_template, $data);
        return get_stylesheet_directory().'/index.php';
    }

    return $comments_template;
}, 100);

/**
 * Ordena a lista de autores
 */
add_action('pre_get_posts', [ArchiveQuery::class, 'filterMainQuery']);

/**
 * Carregando formulário de busca customizado
 */
add_filter('get_search_form', function () {
    $form = '';
    echo template('partials.search-form');
    return $form;
});

/**
 * Adiciona tag ao formulário do Mailchimp no rodapé
 */
add_filter('mc4wp_form_subscriber_data', function($subscriber) {
    $subscriber->tags[] = 'cliente';
    return $subscriber;
});

/**
 * Adiciona tag do Mailchimp aos formulários de professor e livreiro
 */
add_filter('mc4wp_integration_contact-form-7_subscriber_data', function($subscriber, $related_object_id){
    if (238 == $related_object_id) {
        $tag = 'professor';
    } elseif (239 == $related_object_id) {
        $tag = 'livreiro';
    } else {
        $tag = '';
    }

   $subscriber->tags[] = $tag;
   return $subscriber;
}, 10, 2);

/**
 * Adiciona tag do Mailchimp ao formulário de checkout
 */
add_filter('mc4wp_integration_woocommerce_subscriber_data', function($subscriber, $related_object_id){
    $subscriber->tags[] = 'cliente';
    return $subscriber;
}, 10, 2);

/**
 * Rewrite category Instagram URL
 */
add_action('init', function(){
	add_rewrite_rule(
        '^instagram/page/([0-9]{1,})/?',
        'index.php?taxonomy=category&term=instagram&paged=$matches[1]',
        'top'
    );

    add_rewrite_rule(
        '^instagram/?',
        'index.php?taxonomy=category&term=instagram',
        'top'
    );
});
