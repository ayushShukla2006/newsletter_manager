<?php
// api/authors.php - list of writers (admins) with counts.
// If someone is logged in, each writer also says whether they subscribe.
require __DIR__ . '/../lib/auth.php';

$st = $pdo->query(
    "SELECT u.id, u.name, u.bio,
            (SELECT COUNT(*) FROM articles WHERE author_id = u.id)      AS articles,
            (SELECT COUNT(*) FROM subscriptions WHERE admin_id = u.id)  AS subscribers
     FROM users u WHERE u.role = 'admin' ORDER BY u.name"
);
$authors = $st->fetchAll();

$me = current_user();
$mine = [];
if ($me) {
    $st = $pdo->prepare('SELECT admin_id FROM subscriptions WHERE user_id = ?');
    $st->execute([$me['id']]);
    $mine = $st->fetchAll(PDO::FETCH_COLUMN);
}
foreach ($authors as &$a) {
    $a['subscribed'] = in_array($a['id'], $mine);
}
json_out(['success' => true, 'authors' => $authors]);
