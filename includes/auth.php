<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!current_user()) {
        header('Location: /Grading_System/login.php');
        exit;
    }
}

function require_role(string $role): void
{
    require_login();
    if (!isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== $role) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}

function login_user(array $user): void
{
    $_SESSION['user'] = [
        'user_id' => (int)$user['user_id'],
        'username' => $user['username'],
        'role' => $user['role'],
    ];
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'], $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}
