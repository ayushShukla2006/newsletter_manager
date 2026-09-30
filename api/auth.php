<?php
// api/auth.php?action=register | login | logout | me
require __DIR__ . '/../lib/auth.php';

$action = $_GET['action'] ?? 'me';

if ($action === 'me')     json_out(['user' => current_user()]);
if ($action === 'logout') { logout_user(); json_out(['success' => true]); }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['success' => false, 'message' => 'POST only.'], 405);

$d = input();
$email = strtolower(trim($d['email'] ?? ''));
$pass  = $d['password'] ?? '';

if ($action === 'register') {
    $name = trim($d['name'] ?? '');
    $role = ($d['role'] ?? '') === 'admin' ? 'admin' : 'user';
    $bio  = substr(trim($d['bio'] ?? ''), 0, 255);

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 6) {
        json_out(['success' => false, 'message' => 'Enter a name, a valid email and a password of 6+ characters.'], 422);
    }
    try {
        $st = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, bio) VALUES (?, ?, ?, ?, ?)');
        $st->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT), $role, $bio]);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) json_out(['success' => false, 'message' => 'That email is already registered.'], 409);
        throw $e;
    }
    login_user($pdo->lastInsertId());
    json_out(['success' => true, 'role' => $role]);
}

if ($action === 'login') {
    $st = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $st->execute([$email]);
    $u = $st->fetch();
    if (!$u || !password_verify($pass, $u['password_hash'])) {
        json_out(['success' => false, 'message' => 'Wrong email or password.'], 401);
    }
    login_user($u['id']);
    json_out(['success' => true, 'role' => $u['role']]);
}

json_out(['success' => false, 'message' => 'Unknown action.'], 400);
