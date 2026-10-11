<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Response;
use App\Core\Validator;

class ProfileController extends Controller
{
    public function show(array $p = []): void
    {
        $user = Auth::user();
        $pdo = Database::connection();
        $total = (int)$pdo->query('SELECT COUNT(*) FROM orders WHERE user_id=' . (int)$user['id'])->fetchColumn();
        $this->view('profile/index', ['title' => 'Profil', 'user' => $user, 'total' => $total], 'app');
    }

    public function update(array $p = []): void
    {
        Csrf::requireValid();
        $user = Auth::user();
        $name = trim((string)$this->input('name', ''));
        $phone = trim((string)$this->input('phone', ''));
        $email = trim(strtolower((string)$this->input('email', '')));
        $errors = [];
        if (mb_strlen($name) < 3) $errors['name'] = 'Nama minimal 3 karakter.';
        if (!Validator::email($email)) $errors['email'] = 'Email tidak valid.';
        if (!Validator::phone($phone)) $errors['phone'] = 'Telepon tidak valid.';
        // Cek duplikat milik orang lain
        $pdo = Database::connection();
        $st = $pdo->prepare('SELECT id FROM users WHERE (email = ? OR username = ?) AND id != ? LIMIT 1');
        // username tidak diubah di sini
        $st->execute([$email, $user['username'], $user['id']]);
        // email duplikat?
        $st2 = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
        $st2->execute([$email, $user['id']]);
        if ($st2->fetch()) $errors['email'] = 'Email sudah dipakai akun lain.';
        if ($errors) {
            $this->view('profile/index', ['title' => 'Profil', 'user' => array_merge($user, ['name' => $name, 'phone' => $phone, 'email' => $email]), 'total' => 0, 'errors' => $errors], 'app');
            return;
        }
        $pdo->prepare('UPDATE users SET name=?, phone=?, email=?, updated_at=? WHERE id=?')
            ->execute([$name, $phone ?: null, $email, gmdate('Y-m-d H:i:s'), $user['id']]);
        $_SESSION['flash'][] = ['type' => 'success', 'message' => 'Profil diperbarui.'];
        Response::redirect('/profile');
    }

    public function password(array $p = []): void
    {
        Csrf::requireValid();
        $user = Auth::user();
        $old = (string)$this->input('old_password', '');
        $new = (string)$this->input('new_password', '');
        $conf = (string)$this->input('new_password_confirm', '');
        $errors = [];
        if (!password_verify($old, $user['password_hash'])) $errors['old_password'] = 'Password lama salah.';
        if (strlen($new) < 8) $errors['new_password'] = 'Password baru minimal 8 karakter.';
        if ($new !== $conf) $errors['new_password_confirm'] = 'Konfirmasi tidak sama.';
        if ($errors) {
            $this->view('profile/index', ['title' => 'Profil', 'user' => $user, 'total' => 0, 'errors' => $errors], 'app');
            return;
        }
        Database::connection()->prepare('UPDATE users SET password_hash=?, updated_at=? WHERE id=?')
            ->execute([password_hash($new, PASSWORD_DEFAULT), gmdate('Y-m-d H:i:s'), $user['id']]);
        $_SESSION['flash'][] = ['type' => 'success', 'message' => 'Password diganti.'];
        Response::redirect('/profile');
    }
}
