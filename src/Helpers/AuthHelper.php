<?php

namespace App\Helpers;

class AuthHelper
{
    public static function generateApiKey(): string
    {
        $result = sha1(self::generateRandomString());

        return $result;
    }

    public static function generateRandomString($length = 10): string
    {
        $result = substr(
            str_shuffle(
                str_repeat(
                    $x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
                    ceil($length / strlen($x)
                    )
                )
            ), 1, $length
        );

        return $result;
    }
}
