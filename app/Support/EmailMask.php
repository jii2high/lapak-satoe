<?php

namespace App\Support;

class EmailMask
{
    public static function mask(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');

        if ($domain === '') {
            return str_repeat('*', max(3, mb_strlen($email)));
        }

        return mb_substr($name, 0, 1).str_repeat('*', max(3, mb_strlen($name) - 1)).'@'.$domain;
    }
}
