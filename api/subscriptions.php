<?php
// api/subscriptions.php
//   GET  -> (admin) people subscribed to me
//   POST {admin_id} -> toggle: subscribe if not subscribed, otherwise unsubscribe
require __DIR__ . '/../lib/auth.php';

$me = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($me['role'] !== 'admin') json_out(['success' => false, 'message' => 'Not allowed.'], 403);
    $st = $pdo->prepare(
        'SELECT u.name, u.email, s.created_at
         FROM subscriptions s JOIN users u ON u.id = s.user_id
         WHERE s.admin_id = ? ORDER BY s.created_at DESC'
    );
    $st->execute([$me['id']]);
    json_out(['success' => true, 'subscribers' => $st->fetchAll()]);
}

$adminId = (int)(input()['admin_id'] ?? 0);

$st = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'admin'");
$st->execute([$adminId]);
if (!$st->fetch() || $adminId === (int)$me['id']) {
    json_out(['success' => false, 'message' => 'You can not subscribe to that writer.'], 400);
}

$st = $pdo->prepare('DELETE FROM subscriptions WHERE user_id = ? AND admin_id = ?');
$st->execute([$me['id'], $adminId]);
if ($st->rowCount() > 0) json_out(['success' => true, 'subscribed' => false]);

$st = $pdo->prepare('INSERT INTO subscriptions (user_id, admin_id) VALUES (?, ?)');
$st->execute([$me['id'], $adminId]);
json_out(['success' => true, 'subscribed' => true]);
