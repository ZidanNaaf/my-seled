<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Response;
use App\Core\Validator;
use App\Middleware\RateLimit;
use App\Models\User;
use App\Services\AuthService;

class AuthController extends Controller
{
    public function showLogin(array $p = []): void
    {
        $this->view('auth/login', ['title' => 'Masuk — My Seled'], 'guest');
    }

    public function showRegister(array $p = []): void
    {
        $this->view('auth/register', ['title' => 'Daftar — My Seled'], 'guest');
    }

    public function login(array $p = []): void
    {
        Csrf::requireValid();
        $login = (string)$this->input('login', '');
        $password = (string)($this->input('password', ''));
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        RateLimit::check('login_' . $ip . '_' . substr(md5($login), 0, 12), 8, 600);

        $errors = [];
        if ($login === '' || $password === '') {
            $errors['login'] = 'Email/username dan password wajib diisi.';
        }
        if ($errors) {
            $this->fail($errors, 'Login gagal. Periksa kembali data kamu.');
            return;
        }
        $user = User::findByLogin($login);
        // Cek lock
        if ($user && !empty($user['locked_until']) && strtotime($user['locked_until'] . ' UTC') > time()) {
            $this->fail([], 'Akun dikunci sementara. Coba lagi nanti.');
            return;
        }
        if (!$user || (int)$user['is_active'] !== 1 || !password_verify($password, $user['password_hash'])) {
            // Tambah failed
            if ($user) {
                $n = (int)$user['failed_logins'] + 1;
                $lock = $n >= 8 ? gmdate('Y-m-d H:i:s', time() + 600) : null;
                $st = Database::connection()->prepare('UPDATE users SET failed_logins = ?, locked_until = ? WHERE id = ?');
                $st->execute([$n, $lock, $user['id']]);
            }
            $this->fail([], 'Email/username atau password salah.');
            return;
        }
        Database::connection()->prepare('UPDATE users SET failed_logins = 0, locked_until = NULL WHERE id = ?')->execute([$user['id']]);
        RateLimit::reset('login_' . $ip . '_' . substr(md5($login), 0, 12));
        Auth::login($user);
        $isApi = str_starts_with(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/api/');
        if ($isApi) {
            $this->json(['success' => true, 'message' => 'Login berhasil', 'data' => ['role' => $user['role']]]);
        }
        $to = $_SESSION['intended'] ?? '/home';
        unset($_SESSION['intended']);
        Response::redirect($to);
    }

    public function register(array $p = []): void
    {
        Csrf::requireValid();
        $res = AuthService::register($_POST);
        $isApi = str_starts_with(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/api/');
        if (!$res['ok']) {
            if ($isApi) {
                $this->json(['success' => false, 'message' => 'Registrasi gagal', 'errors' => $res['errors']], 422);
            }
            $this->view('auth/register', ['title' => 'Daftar — My Seled', 'errors' => $res['errors'], 'old' => $_POST], 'guest');
            return;
        }
        $user = User::findById($res['user_id']);
        Auth::login($user);
        if ($isApi) {
            $this->json(['success' => true, 'message' => 'Registrasi berhasil', 'data' => ['role' => 'user']], 201);
        }
        Response::redirect('/home');
    }

    public function logout(array $p = []): void
    {
        Auth::logout();
        $isApi = str_starts_with(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/api/');
        if ($isApi) {
            $this->json(['success' => true, 'message' => 'Logout berhasil']);
        }
        Response::redirect('/');
    }

    private function fail(array $errors, string $msg): void
    {
        $isApi = str_starts_with(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/api/');
        if ($isApi) {
            $this->json(['success' => false, 'message' => $msg, 'errors' => $errors], 401);
        }
        $this->view('auth/login', ['title' => 'Masuk — My Seled', 'errors' => $errors, 'message' => $msg, 'old' => $_POST], 'guest');
    }
}
