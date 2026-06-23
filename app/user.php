<?php

namespace App;

use App\CreateUser;

/**
 * Gera usuário com informações do CF7
 */
add_action('wpcf7_mail_sent', function($form){
    $form_slug = $form->name();
    $forms = ['formulario-professores', 'formulario-livreiros'];

    if (in_array($form_slug, $forms)) {
        $submission = \WPCF7_Submission::get_instance();
        $data = $submission->get_posted_data();

        $user = new CreateUser();
        $user->setFirstName($data['user-name']);
        $user->setLastName($data['user-last-name']);
        $user->setEmail($data['user-email']);
        $user_id = $user->createUser();

        if ($user_id) {
            if ('formulario-professores' == $form_slug) {
                $user->saveMeta($user_id, 'user_school', $data['user-school']);
                $user->saveMeta($user_id, 'user_subject', $data['user-subject']);
                $user->saveMeta($user_id, 'user_area', $data['user-area']);
            } elseif ('formulario-livreiros' == $form_slug) {
                $user->saveMeta($user_id, 'user_bookstore', $data['user-store']);
                $user->saveMeta($user_id, 'user_interest', $data['user-interest']);
                $user->saveMeta($user_id, 'billing_country', 'BR');
                $user->saveMeta($user_id, 'billing_state', $data['user-state']);
                $user->saveMeta($user_id, 'billing_city', $data['user-city']);
                $user->saveMeta($user_id, 'shipping_country', 'BR');
                $user->saveMeta($user_id, 'shipping_state', $data['user-state']);
                $user->saveMeta($user_id, 'shipping_city', $data['user-city']);
            }
        }
    }
});

/**
 * Cria novos tipos de cliente
 */
//add_role('teacher', 'Professor', array('read' => true));
//add_role('bookseller', 'Livreiro', array('read' => true));

/**
 * Permite que usuário shop_manager mude role de outros usuários
 */
//$role = get_role('shop_manager');
//$role->add_cap('promote_users');

/*
 * Permite que usuários do tipo shop_manager
 * possam editar usuários do tipo teacher_client
 */
add_filter('woocommerce_shop_manager_editable_roles', function($roles){
    $new_roles = array_merge($roles, ['teacher', 'bookseller']);
    return $new_roles;
});
