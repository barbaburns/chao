<?php

namespace App;

class CreateUser
{
    protected $first_name;
    protected $last_name;
    protected $email;

    public function setFirstName($first_name)
    {
        $this->first_name = sanitize_text_field($first_name);
    }

    public function setLastName($last_name)
    {
        $this->last_name = sanitize_text_field($last_name);
    }

    public function setEmail($email)
    {
        $this->email = sanitize_email($email);
    }

    public function getFirstName()
    {
        return $this->first_name;
    }

    public function getLastName()
    {
        return $this->last_name;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getFullName()
    {
        return $this->getFirstName().' '.$this->getLastName();
    }

    /**
     * cria usuário, se não existir, e retorna ID
     *
     * @return int|false ID do usuário ou false quando não conseguir criar usuário
     */
    public function createUser()
    {
        $email_exists = email_exists($this->getEmail());
        if ($email_exists) {
            return false;
        }

        // verifica se o username já existe
        // se existir, inclui a data e hora do momento
        $username = sanitize_title($this->getFullName());
        if (username_exists($username)) {
            $username = $username.strtotime('now');
        }

        $password = wp_generate_password(12, false);

        // cria usuário
        $userdata = [
            'user_login'    => $username,
            'user_email'    => $this->getEmail(),
            'first_name'    => $this->getFirstName(),
            'last_name'     => $this->getlastName(),
            'role'          => 'customer',
            'user_pass'     => $password,
        ];

        $user_id = wp_insert_user($userdata);

        if (is_wp_error($user_id)) {
            return false;
        }

        return $user_id;
    }

    /**
     * Salvar os metadados
     *
     * @param int $user_id
     * @param string $field
     * @param string $value
     */
    public function saveMeta($user_id, $field, $value)
    {
        $value = sanitize_text_field($value);
        update_field($field, $value, 'user_'.$user_id);
    }
}
