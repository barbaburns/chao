<?php

namespace App;

/**
 * Set acf-json folder path
 */
add_filter('acf/settings/save_json', function ($path) {
    $path = get_stylesheet_directory() . '/acf-json';
    return $path;
});

add_filter('acf/settings/load_json', function ($paths) {
    unset($paths[0]);
    $paths[] = get_stylesheet_directory() . '/acf-json';
    return $paths;
});

/**
 * ACF option page
 */
if (function_exists('acf_add_options_page')) {
    acf_add_options_page([
        'page_title'        => 'Customizações',
        'menu_title'        => 'Customizações',
        'menu_slug'         => 'customizacoes',
        'position'          => 77,
        'redirect'          => true,
    ]);

    acf_add_options_sub_page([
        'page_title'        => 'Home',
        'menu_title'        => 'Home',
        'menu_slug'         => 'home',
        'update_button'     => 'Atualizar',
        'updated_message'   => 'Informações atualizadas',
        'parent_slug'       => 'customizacoes',
    ]);

    acf_add_options_sub_page([
        'page_title'        => 'Blog',
        'menu_title'        => 'Blog',
        'menu_slug'         => 'blog',
        'update_button'     => 'Atualizar',
        'updated_message'   => 'Informações atualizadas',
        'parent_slug'       => 'customizacoes',
    ]);

    acf_add_options_sub_page([
        'page_title'        => 'Contato',
        'menu_title'        => 'Contato',
        'menu_slug'         => 'contato',
        'update_button'     => 'Atualizar',
        'updated_message'   => 'Informações atualizadas',
        'parent_slug'       => 'customizacoes',
    ]);

    acf_add_options_sub_page([
        'page_title'        => 'Cabeçalhos de páginas',
        'menu_title'        => 'Cabeçalho',
        'menu_slug'         => 'cabecalho',
        'update_button'     => 'Atualizar',
        'updated_message'   => 'Informações atualizadas',
        'parent_slug'       => 'customizacoes',
    ]);

    acf_add_options_sub_page([
        'page_title'        => 'Chamadas',
        'menu_title'        => 'Chamadas',
        'menu_slug'         => 'chamadas',
        'update_button'     => 'Atualizar',
        'updated_message'   => 'Informações atualizadas',
        'parent_slug'       => 'customizacoes',
    ]);

    acf_add_options_sub_page([
        'page_title'        => 'Livrarias',
        'menu_title'        => 'Livrarias',
        'menu_slug'         => 'livrarias',
        'update_button'     => 'Atualizar',
        'updated_message'   => 'Informações atualizadas',
        'parent_slug'       => 'customizacoes',
    ]);

    acf_add_options_sub_page([
        'page_title'        => 'Desconto',
        'menu_title'        => 'Desconto',
        'menu_slug'         => 'desconto',
        'update_button'     => 'Atualizar',
        'updated_message'   => 'Informações atualizadas',
        'parent_slug'       => 'customizacoes',
    ]);
}

/**
 * Incluir lista de livrarias no select da página do produto
 */
add_filter('acf/load_field/name=buy_link_bookstore', function($field) {
    $choices = get_field('bookstores', 'option', false);
    $choices = explode("\n", $choices);
    $choices = array_map('trim', $choices);

    if (is_array($choices)) {
        foreach($choices as $choice) {
            $field['choices'][$choice] = $choice;
        }
    }

    return $field;
});

/**
 * Incluir lista de roles no select da página Desconto
 */
add_filter('acf/load_field/name=discount_user_roles', function($field) {
    $roles = wp_roles()->roles;
    foreach ($roles as $slug => $role) {
		$field['choices'][$slug] = $role['name'];
	}

    return $field;
});

if (function_exists('acf_add_local_field_group')) {
    acf_add_local_field_group([
        'key' => 'group_manuscript_local_pilot',
        'title' => 'Importacao de manuscrito',
        'fields' => [
            [
                'key' => 'field_manuscript_zip_file',
                'label' => 'Pacote ZIP do manuscrito',
                'name' => 'manuscript_zip_file',
                'type' => 'file',
                'return_format' => 'id',
                'mime_types' => 'zip',
                'required' => 0,
                'instructions' => 'Envie um pacote ZIP contendo as imagens e os arquivos ALTO XML pareados pelo mesmo nome-base.',
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'manuscrito',
                ],
            ],
        ],
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
    ]);
}
