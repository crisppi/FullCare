<?php

if (!function_exists('password_policy_errors')) {
    function password_policy_errors(?string $password): array
    {
        $password = (string)$password;
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = 'A senha deve ter pelo menos 8 caracteres.';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'A senha deve conter pelo menos 1 letra maiúscula.';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'A senha deve conter pelo menos 1 letra minúscula.';
        }
        if (!preg_match('/\d/', $password)) {
            $errors[] = 'A senha deve conter pelo menos 1 número.';
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'A senha deve conter pelo menos 1 caractere especial.';
        }

        return $errors;
    }
}

if (!function_exists('password_policy_message')) {
    function password_policy_message(): string
    {
        return 'A senha deve ter no mínimo 8 caracteres, com letra maiúscula, minúscula, número e caractere especial.';
    }
}

