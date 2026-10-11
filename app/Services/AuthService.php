<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\User;

class AuthService
{
    public static function register(array $in): array
    {
        $errors = [];
        $name = trim($in['name'] ?? '');
        $username = trim($in['username'] ?? '');
        $email = trim(strtolower($in['email'] ?? ''));
        $phone = trim($in['phone'] ?? '');
        $pass = (string)($in['password'] ?? '');
        $pass2 = (string)($in['password_confirm'] ?? $in['password_confirmation'] ?? '');

        if (mb_strlen($name) < 3) $errors['name'] = 'Nama minimal 3 karakter.';
        if (!\App\Core\Validator::username($username)) $errors['username'] = 'Username 3-24 karakter (huruf, angka, _ .).';
        if (!\App\Core\Validator::email($email)) $errors['email'] = 'Email tidak valid.';
        if (!\App\Core\Validator::phone($phone)) $errors['phone'] = 'Nomor telepon tidak valid.';
        if (strlen($pass) < 8) $errors['password'] = 'Password minimal 8 karakter.';
        if ($pass !== $pass2) $errors['password_confirm'] = 'Konfirmasi password tidak sama.';

        if (!$errors) {
            if (User::findByLogin($email) || User::findByLogin($username)) {
                // Bedakan tanpa bocor? Pesan umum tapi field spesifik untuk UX
                $errors['email'] = 'Email atau username sudah terdaftar.';
            }
        }
        if ($errors) return ['ok' => false, 'errors' => $errors];

        $id = User::create([
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'phone' => $phone ?: null,
            'password_hash' => password_hash($pass, PASSWORD_DEFAULT),
            'role' => 'user',
        ]);
        return ['ok' => true, 'user_id' => $id];
    }
}
