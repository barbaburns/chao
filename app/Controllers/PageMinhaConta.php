<?php

namespace App\Controllers;

use Sober\Controller\Controller;

class PageMinhaConta extends Controller
{
    /**
     * Verifica se usuário está inscrito no Mailchimp
     *
     * @param [string] $email
     * @return boolean
     */
    public static function isMailchimpSbuscribed($user_email)
    {
        $mailchimp_api = new \MC4WP_MailChimp();
        $lists = $mailchimp_api->get_lists();
        $list_id = array_keys($lists)[0];
        $is_subscribed = $mailchimp_api->list_has_subscriber($list_id, $user_email);

        return $is_subscribed;
    }

    /**
     * Descadastrar email
     *
     * @param [string] $user_email
     * @return void
     */
    public static function unsubscribeEmail($user_email)
    {
        $mailchimp_api = new \MC4WP_MailChimp();
        $lists = $mailchimp_api->get_lists();
        $list_id = array_keys($lists)[0];
        $unsubscribe = $mailchimp_api->list_unsubscribe($list_id, $user_email);
        error_log(print_r($unsubscribe, true));
    }
}
