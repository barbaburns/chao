<?php

namespace App;

use App\Controllers\App;
use App\Controllers\PageMinhaConta;

/**
 * Disable WooCommerce stylesheets
 */
add_filter('woocommerce_enqueue_styles', '__return_empty_array');

remove_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20);
remove_action('woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10);
remove_action('woocommerce_before_shop_loop', 'woocommerce_result_count', 20);
remove_action('woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30);
remove_action('woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10);
remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20);
remove_action('woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15);
remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10);
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10);
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40);
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 10);
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20);
remove_action('woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10);
remove_action('woocommerce_after_shop_loop', 'woocommerce_pagination', 10);
remove_action('woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10);
remove_action('woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10);

add_action('woocommerce_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 5);
add_action('woocommerce_single_product_summary', 'woocommerce_show_product_sale_flash', 4);

add_action('woocommerce_after_shop_loop_item_title', function () {
    if (
        !is_singular(['post', 'product']) &&
        !wp_is_mobile() &&
        $description = App::getProductShortDescription()
    ) {
        echo '<p class="product-desktop-description">'.$description.'</p>';
    }
}, 13);

add_action('woocommerce_after_shop_loop_item_title', function () {
    if (!is_singular(['post', 'product'])) {
        echo template('partials.btn-learn-more');
    }
}, 15);

add_action('woocommerce_single_product_summary', function () {
    echo template('partials.product-authors');
}, 6);

add_action('woocommerce_single_product_summary', function () {
    echo template('partials.product-attachments');
}, 7);

add_action('woocommerce_single_product_summary', function () {
    echo '<div class="entry-summary-left">';
}, 3);

add_action('woocommerce_single_product_summary', function () {
    echo template('partials.share-buttons');
    echo '</div>'; // fecha a div entry-summary-left
}, 8);

add_action('woocommerce_after_edit_account_form', function () {
    echo template('partials.remove-account-button');
});

add_action('woocommerce_single_product_summary', function () {
    echo '<div class="entry-summary-right">';
    echo template('partials.product-specifications');
}, 9);

add_action('woocommerce_after_add_to_cart_button', function () {
    echo template('partials.product-buy-links');
});

add_action('woocommerce_after_add_to_cart_form', function () {
    echo '</div>'; // fecha a div entry-summary-right
});

add_filter('woocommerce_product_loop_start', function () {
    return '<div class="products grid-content">';
});

add_filter('woocommerce_product_loop_end', function () {
    return '</div>';
});

add_filter('woocommerce_output_related_products_args', function ($args) {
    $args['posts_per_page'] = 4;
    return $args;
}, 20);

add_filter('woocommerce_product_single_add_to_cart_text', function ($text) {
    global $product;

    if ($product && get_field('product_pre_sale', $product->get_id())) {
        return 'Pré-venda';
    }

    return 'Comprar';
});

add_filter('woocommerce_product_related_products_heading', function ($text) {
    return 'Obras relacionadas';
});

add_filter('woocommerce_checkout_login_message', function ($text) {
    return false;
});

add_filter('woocommerce_correios_email_tracking_code_url', function ($html, $trackingCode) {
    return $trackingCode;
}, 10, 3);

add_action('woocommerce_shop_loop_item_title', __NAMESPACE__ . '\\addProductBadges', 6);
add_action('woocommerce_single_product_summary', __NAMESPACE__ . '\\addProductBadges', 4);

function addProductBadges()
{
    global $product;
    $product_id = $product->get_id();

    $soon = get_field('em_breve', $product_id);
    if ($soon) {
        echo template('components.badge', [
            'class'     => 'soon',
            'text'      => 'Em breve'
        ]);
    }

    $pre_sale = get_field('product_pre_sale', $product_id);
    if ($pre_sale) {
        echo template('components.badge', [
            'class'     => 'pre-sale',
            'text'      => 'Pré-venda'
        ]);
    }

    $release = get_field('product_release', $product_id);
    if ($release) {
        echo template('components.badge', [
            'class'     => 'release',
            'text'      => 'Lançamento'
        ]);
    }

    if (!$product->is_in_stock() && !is_product()) {
        echo template('components.badge', [
            'class'     => 'out-of-stock',
            'text'      => 'Fora de estoque'
        ]);
    }
}

/**
 * Mostrar ícone do carrinho no lugar da palavra "Comprar"
 */
add_filter('woocommerce_product_add_to_cart_text', function ($text) {
    return template('partials.button-add-to-cart');
}, 20, 1);

/**
 * Altera tamanho dos thumbnails
 */
add_filter('woocommerce_get_image_size_gallery_thumbnail', function ($size) {
    return [
        'width' => 190,
        'height' => 190,
        'crop' => 1,
    ];
});

add_filter('woocommerce_checkout_fields', function ($fields) {
    $billing_names = [
        'billing_first_name',
        'billing_last_name',
        'billing_cpf',
        'billing_company',
        'billing_cnpj',
        'billing_postcode',
        'billing_address_1',
        'billing_number',
        'billing_address_2',
        'billing_neighborhood',
        'billing_city',
        'billing_phone',
        'billing_cellphone',
        'billing_email',
    ];

    $shipping_names = [
        'shipping_first_name',
        'shipping_last_name',
        //'shipping_company',
        'shipping_postcode',
        'shipping_address_1',
        'shipping_number',
        'shipping_address_2',
        'shipping_neighborhood',
        'shipping_city',
    ];

    foreach ($billing_names as $name) {
        if ( isset( $fields['billing'][$name])) {
            $fields['billing'][$name]['placeholder'] = $fields['billing'][$name]['label'] ?? '';
        }
    }

    foreach ($shipping_names as $name) {
        if ( isset( $fields['shipping'][$name])) {
            $fields['shipping'][$name]['placeholder'] = $fields['shipping'][$name]['label'] ?? '';
        }
    }

    $fields['billing']['billing_company']['class'][0] = 'form-row-first';

    $fields['billing']['billing_cnpj']['class'][0] = 'form-row-last';

    return $fields;
});

add_filter('woocommerce_address_to_edit', function ($fields, $load_address) {
    if ($load_address) {
        if ('shipping' == $load_address) {
            $names = [
                'shipping_first_name',
                'shipping_last_name',
                //'shipping_company',
                'shipping_postcode',
                'shipping_address_1',
                'shipping_number',
                'shipping_address_2',
                'shipping_neighborhood',
                'shipping_city',
            ];
        } elseif ('billing' == $load_address) {
            $names = [
                'billing_first_name',
                'billing_last_name',
                'billing_cpf',
                'billing_company',
                'billing_cnpj',
                'billing_postcode',
                'billing_address_1',
                'billing_number',
                'billing_address_2',
                'billing_neighborhood',
                'billing_city',
                'billing_phone',
                'billing_cellphone',
                'billing_email',
            ];
        }

        foreach ($names as $name) {
            $fields[$name]['placeholder'] = $fields[$name]['label'];
        }
    }

    return $fields;
}, 2, 10);

/*
 * Aplica o desconto para os tipos de usuários selecionados
 */
add_action('woocommerce_cart_calculate_fees', function ($cart) {
    if (is_admin() && ! defined('DOING_AJAX')) {
        return;
    }

    if (!is_user_logged_in()) {
        return;
    }

    $discount = get_field('discount', 'option');
    if ($discount == 0) {
        return;
    }

    $selected_roles = get_field('discount_user_roles', 'option');
    if (!$selected_roles) {
        return;
    }

    $user = wp_get_current_user();
    $user_roles = $user->roles;

    if (count(array_intersect($user_roles, $selected_roles)) < 1) {
        return;
    }

    $percentage = absint($discount);
    $discount = $cart->get_subtotal() * $percentage / 100;

    $cart->add_fee('Desconto ('.$percentage.'%)', -$discount, true);
}, 20, 1);


add_action('woocommerce_save_account_details', function ($user_id) {
    $fields = [
        'user_school',
        'user_subject',
        'user_area',
        'user_bookstore',
        'user_interest',
    ];

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            $value = sanitize_text_field($_POST[$field]);
            update_field($field, $value, 'user_'.$user_id);
        }
    }

    $remove_user_email = isset($_POST['remove_user_email']) ? 1 : 0;
    if ($remove_user_email) {
        PageMinhaConta::unsubscribeEmail($_POST['account_email']);
    }
});
