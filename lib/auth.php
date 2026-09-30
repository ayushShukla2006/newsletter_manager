<?php
// lib/auth.php - helpers shared by every API file.
// Vercel runs PHP as serverless functions, so PHP's normal file-based
// sessions don't survive between requests. Instead we put a signed
// cookie on the browser: "user_id|expiry" + an HMAC signature. Nobody
// can edit the id without breaking the signature.
require __DIR__ . '/db.php';

function secret() { return getenv('APP_SECRET') ?: 'dev-secret-change-me'; }

function json_out($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function input() { return json_decode(file_get_contents('php://input'), true) ?: []; }

function login_user($id) {
    $expires = time() + 7 * 86400;
    $payload = $id . '|' . $expires;
    $sig = hash_hmac('sha256', $payload, secret());
    setcookie('auth', base64_encode($payload) . '.' . $sig, [
        'expires' => $expires, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax',
    ]);
}

function logout_user() { setcookie('auth', '', ['expires' => 1, 'path' => '/']); }

// Returns the logged-in user row, or null.
function current_user() {
    global $pdo;
    if (empty($_COOKIE['auth'])) return null;
    $parts = explode('.', $_COOKIE['auth']);
    if (count($parts) !== 2) return null;
    $payload = base64_decode($parts[0]);
    if (!hash_equals(hash_hmac('sha256', $payload, secret()), $parts[1])) return null;
    [$id, $expires] = explode('|', $payload);
    if ($expires < time()) return null;
    $st = $pdo->prepare('SELECT id, name, email, role, bio FROM users WHERE id = ?');
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

// Stops the request with 401/403 unless the right kind of user is logged in.
function require_login($role = null) {
    $u = current_user();
    if (!$u) json_out(['success' => false, 'message' => 'Please log in.'], 401);
    if ($role && $u['role'] !== $role) json_out(['success' => false, 'message' => 'Not allowed.'], 403);
    return $u;
}
