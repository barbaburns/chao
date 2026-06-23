<?php

namespace App;

function chao_comment_cookies_field()
{
    $commenter = wp_get_current_commenter();
    $consent = empty($commenter['comment_author_email']) ? '' : ' checked="checked"';

    return '<p class="comment-form-cookies-consent"><input id="wp-comment-cookies-consent" name="wp-comment-cookies-consent" type="checkbox" value="yes"' . $consent . ' /><label for="wp-comment-cookies-consent">Salvar meus dados neste navegador</label></p>';
}

/**
 * Remover campo de website do
 * formulário de comentários
 */
add_filter('comment_form_default_fields', function ($fields) {
    if (isset($fields['url'])) {
        unset($fields['url']);
    }

    $fields['cookies'] = chao_comment_cookies_field();

    return $fields;
});

add_filter('comment_form_defaults', function ($defaults) {
    $defaults['comment_notes_before'] = "";

    $defaults['title_reply'] = esc_html__( 'Leave a Reply', 'chao' );

    $defaults['fields']['email'] = '<div class="comment-form-email form-group"><label for="email" class="sr-only">E-mail</label> <input id="email" class="form-control" name="email" type="email" value="" maxlength="100" aria-describedby="email-notes" required="required" placeholder="E-MAIL" /></div>';

    $defaults['fields']['author'] = '<div class="comment-form-author form-group"><label for="author" class="sr-only">Nome </label> <input id="author" class="form-control" name="author" type="text" value="" maxlength="245" required="required" placeholder="NOME" /></div>';

    $defaults['comment_field'] = '<div class="comment-form-comment form-group"><label for="comment" class="sr-only">Comentário</label> <textarea id="comment" class="form-control" name="comment" maxlength="65525" required="required" placeholder="COMENTÁRIO"></textarea></div>';

    return $defaults;
});

add_filter('woocommerce_product_review_comment_form_args', function ($args) {
    if (!isset($args['fields']) || !is_array($args['fields'])) {
        $args['fields'] = [];
    }

    $args['fields']['cookies'] = chao_comment_cookies_field();

    return $args;
});
