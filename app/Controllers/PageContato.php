<?php

namespace App\Controllers;

use Sober\Controller\Controller;

class PageContato extends Controller
{
    public function getPhone()
    {
        $phone = get_field('contact_phone', 'option');

        return esc_html($phone);
    }

    public function getAddress()
    {
        $address = get_field('contact_address', 'option');

        return esc_html($address);
    }

    public function getCnpj()
    {
        $cnpj = get_field('contact_cnpj', 'option');

        return esc_html($cnpj);
    }
}
