<?php

/**
 * Do not edit anything in this file unless you know what you're doing
 */

use Roots\Sage\Config;
use Roots\Sage\Container;

/**
 * Helper function for prettying up errors
 * @param string $message
 * @param string $subtitle
 * @param string $title
 */
$sage_error = function ($message, $subtitle = '', $title = '') {
    $title = $title ?: __('Sage &rsaquo; Error', 'chao');
    $footer = '<a href="https://roots.io/sage/docs/">roots.io/sage/docs/</a>';
    $message = "<h1>{$title}<br><small>{$subtitle}</small></h1><p>{$message}</p><p>{$footer}</p>";
    wp_die($message, $title);
};

/**
 * Ensure compatible version of PHP is used
 */
if (version_compare('7.1', phpversion(), '>=')) {
    $sage_error(__('You must be using PHP 7.1 or greater.', 'chao'), __('Invalid PHP version', 'chao'));
}

/**
 * Ensure compatible version of WordPress is used
 */
if (version_compare('4.7.0', get_bloginfo('version'), '>=')) {
    $sage_error(__('You must be using WordPress 4.7.0 or greater.', 'chao'), __('Invalid WordPress version', 'chao'));
}

/**
 * Ensure dependencies are loaded
 */
if (!class_exists('Roots\\Sage\\Container')) {
    if (!file_exists($composer = __DIR__.'/../vendor/autoload.php')) {
        $sage_error(
            __('You must run <code>composer install</code> from the Sage directory.', 'chao'),
            __('Autoloader not found.', 'chao')
        );
    }
    require_once $composer;
}

/**
 * Sage required files
 *
 * The mapped array determines the code library included in your theme.
 * Add or remove files to the array as needed. Supports child theme overrides.
 */
array_map(function ($file) use ($sage_error) {
    $file = "app/{$file}.php";
    if (!locate_template($file, true, true)) {
        $sage_error(sprintf(__('Error locating <code>%s</code> for inclusion.', 'chao'), $file), 'File not found');
    }
}, ['helpers', 'setup', 'filters', 'acf', 'post_count', 'woocommerce', 'shortcodes', 'comments', 'user', 'admin', 'manuscripts']);

/**
 * Here's what's happening with these hooks:
 * 1. WordPress initially detects theme in themes/sage/resources
 * 2. Upon activation, we tell WordPress that the theme is actually in themes/sage/resources/views
 * 3. When we call get_template_directory() or get_template_directory_uri(), we point it back to themes/sage/resources
 *
 * We do this so that the Template Hierarchy will look in themes/sage/resources/views for core WordPress themes
 * But functions.php, style.css, and index.php are all still located in themes/sage/resources
 *
 * This is not compatible with the WordPress Customizer theme preview prior to theme activation
 *
 * get_template_directory()   -> /srv/www/example.com/current/web/app/themes/sage/resources
 * get_stylesheet_directory() -> /srv/www/example.com/current/web/app/themes/sage/resources
 * locate_template()
 * ├── STYLESHEETPATH         -> /srv/www/example.com/current/web/app/themes/sage/resources/views
 * └── TEMPLATEPATH           -> /srv/www/example.com/current/web/app/themes/sage/resources
 */
Container::getInstance()
    ->bindIf('config', function () {
        return new Config([
            'assets' => require dirname(__DIR__).'/config/assets.php',
            'theme' => require dirname(__DIR__).'/config/theme.php',
            'view' => require dirname(__DIR__).'/config/view.php',
        ]);
    }, true);

/**
 * Work around Pagar.me registering a private method as a WordPress callback.
 *
 * PHP 8 rejects `[$object, 'privateMethod']` when WordPress executes the hook,
 * so we replace that callback with a reflected wrapper at the beginning of `init`.
 */
add_action('init', function () {
    if (!class_exists('Woocommerce\\Pagarme\\Core')) {
        return;
    }

    $initHook = $GLOBALS['wp_filter']['init'] ?? null;
    if (!$initHook instanceof WP_Hook || empty($initHook->callbacks[20])) {
        return;
    }

    foreach ($initHook->callbacks[20] as $callbackId => $callbackData) {
        $callback = $callbackData['function'] ?? null;

        if (
            !is_array($callback) ||
            !isset($callback[0], $callback[1]) ||
            !is_object($callback[0]) ||
            !$callback[0] instanceof Woocommerce\Pagarme\Core ||
            $callback[1] !== 'addActionsRunners'
        ) {
            continue;
        }

        unset($initHook->callbacks[20][$callbackId]);

        add_action('init', function () use ($callback) {
            $method = new ReflectionMethod($callback[0], 'addActionsRunners');
            $method->setAccessible(true);
            $method->invoke($callback[0]);
        }, 20);

        break;
    }
}, 1);


//New formats to the WordPress editor
function add_style_select_buttons($buttons)
{
    array_unshift($buttons, 'styleselect');
    return $buttons;
}
add_filter('mce_buttons_2', 'add_style_select_buttons');

//add custom styles to the WordPress editor
function my_custom_styles($init_array)
{
    $style_formats = array(
        // These are the custom styles
        array(
            'title' => 'Small Caps',
            'inline' => 'span',
            'classes' => 'sc',
            'wrapper' => true,
        ),
    );
    // Insert the array, JSON ENCODED, into 'style_formats'
    $init_array['style_formats'] = json_encode($style_formats);

    return $init_array;
}
// Attach callback to 'tiny_mce_before_init'
add_filter('tiny_mce_before_init', 'my_custom_styles');

//woocommerce custom field name
add_filter('woocommerce_default_address_fields', 'custom_override_default_checkout_fields', 10, 1);

function custom_override_default_checkout_fields($address_fields)
{
    $address_fields['address_1']['placeholder'] = __('Nome da rua', 'woocommerce');
    $address_fields['address_2']['placeholder'] = __('Complemento', 'woocommerce');
    $address_fields['phone']['placeholder'] = __('TELEFONE (COM DDD)', 'woocommerce');
    $address_fields['cellphone']['placeholder'] = __('CELULAR (COM DDD)', 'woocommerce');

    return $address_fields;
}

add_filter('woocommerce_checkout_fields', 'custom_override_checkout_fields', 10, 1);

function custom_override_checkout_fields($fields)
{
    $fields['billing']['billing_phone']['placeholder'] = 'TELEFONE (COM DDD)';
    $fields['billing']['billing_cellphone']['placeholder'] = 'CELULAR (COM DDD)';
    $fields['billing']['billing_email']['placeholder'] = 'e-mail';
    $fields['billing']['billing_address_1']['maxlength'] = 40;
    $fields['billing']['billing_address_2']['maxlength'] = 20;
    $fields['billing']['billing_number']['maxlength'] = 10;
    $fields['shipping']['shipping_address_1']['maxlength'] = 40;
    $fields['shipping']['shipping_address_2']['maxlength'] = 20;
    $fields['shipping']['shipping_number']['maxlength'] = 10;

    return $fields;
}

// address read only and remove shipping company
//add_filter('woocommerce_billing_fields', 'my_woocommerce_billing_fields');
function my_woocommerce_billing_fields($fields)
{
    $fields['billing_address_1']['custom_attributes'] = array('readonly'=>'readonly');

    return $fields;
}

add_filter('woocommerce_shipping_fields', 'my_woocommerce_shipping_fields');
function my_woocommerce_shipping_fields($fields)
{
    //$fields['shipping_address_1']['custom_attributes'] = array('readonly'=>'readonly');
    unset($fields['shipping_company']);

    return $fields;
}

// Nossos livors - browser tab title
function wc_custom_shop_archive_title($title)
{
    if (is_shop() && isset($title['title'])) {
        $title['title'] = 'Nossos livros';
    }

    return $title;
}
add_filter('document_title_parts', 'wc_custom_shop_archive_title');

/**
 * Change number of products that are displayed per page (shop page)
 */
add_filter('loop_shop_per_page', 'new_loop_shop_per_page', 20);

function new_loop_shop_per_page($cols)
{
    // $cols contains the current number of products per page based on the value stored on Options –> Reading
    // Return the number of products you wanna show per page.
    $cols = -1;
    return $cols;
}


/*
 * @desc    Filtro da mensagem de erro do admin do WP
 */
function rs_custom_login_error()
{
    $msg1 = "O e-mail ou a senha que você informou estão errados.";
    $msg2 =  "<a href="."https://chaoeditora.com.br/minha-conta/senha-perdida/".">Esqueceu a senha?</a>" ;
    return $error = $msg1 .' '. $msg2;
}
add_filter('login_errors', 'rs_custom_login_error');



//custom cart checkout
add_action('woocommerce_review_order_before_payment', 'chao_cart_free_shipping_text');

function chao_cart_free_shipping_text()
{
    echo
    '<div class="woocommerce-moip-info">
      <div class="woocommerce-billing-fields">
        <h3>Pagamento</h3>

      </div>
    </div>';
}

/**
 * Force WooCommerce terms and conditions link to open in a new page when clicked on the checkout page
 *
 * @author   Golden Oak Web Design <info@goldenoakwebdesign.com>
 * @license  https://www.gnu.org/licenses/gpl-2.0.html GPLv2+
 */
function chao_woocommerce_checkout_terms_and_conditions()
{
    remove_action('woocommerce_checkout_terms_and_conditions', 'wc_terms_and_conditions_page_content', 30);
}
add_action('wp', 'chao_woocommerce_checkout_terms_and_conditions');

// Add page-slug to body class
function add_slug_body_class( $classes ) {
    global $post;
    if ( isset( $post ) ) {
    $classes[] = $post->post_type . '-' . $post->post_name;
    }
    return $classes;
    }
add_filter( 'body_class', 'add_slug_body_class' );

// Adiciona colunas de largura de imagem na biblioteca de midias

// === CONFIG ===
define('IMG_WIDTH_LIMIT', 2000); // limite de largura
define('IMG_HEIGHT_LIMIT', 1300); // limite de altura

// Adiciona colunas
add_filter('manage_upload_columns', function($columns) {
    $columns['image_width']  = 'Width';
    $columns['image_height'] = 'Height';
    return $columns;
});

// Preenche colunas
add_action('manage_media_custom_column', function($column_name, $post_id) {
    if ($column_name === 'image_width' || $column_name === 'image_height') {

        $meta = wp_get_attachment_metadata($post_id);

        if (!empty($meta['width']) && !empty($meta['height'])) {

            $width  = $meta['width'];
            $height = $meta['height'];

            $is_large = ($width > IMG_WIDTH_LIMIT || $height > IMG_HEIGHT_LIMIT);

            $style = $is_large ? 'color:red;font-weight:bold;' : '';

            if ($column_name === 'image_width') {
                echo '<span style="' . esc_attr($style) . '">' . esc_html($width) . ' px</span>';
            }

            if ($column_name === 'image_height') {
                echo '<span style="' . esc_attr($style) . '">' . esc_html($height) . ' px</span>';
            }

        } else {
            echo '—';
        }
    }
}, 10, 2);

// Torna colunas ordenáveis
add_filter('manage_upload_sortable_columns', function($columns) {
    $columns['image_width'] = 'image_width';
    return $columns;
});

add_action('pre_get_posts', function($query) {
    if (!is_admin() || !$query->is_main_query()) return;

    if ($query->get('orderby') === 'image_width') {
        $query->set('meta_key', '_wp_attachment_metadata');
        $query->set('orderby', 'meta_value');
    }
});
