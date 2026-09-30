<?php
// api/articles.php
//   GET                -> latest articles
//   GET ?id=5          -> one article
//   GET ?author=2      -> one writer's articles
//   GET ?feed=1        -> articles from writers I subscribe to (login needed)
//   GET ?mine=1        -> my own articles (admin)
//   POST               -> create (or edit if "id" is sent) - admin only
//   DELETE ?id=5       -> delete my article - admin only
require __DIR__ . '/../lib/auth.php';

$cols = 'a.id, a.title, a.body, a.created_at, u.id AS author_id, u.name AS author';
$from = 'FROM articles a JOIN users u ON u.id = a.author_id';
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (isset($_GET['id'])) {
        $st = $pdo->prepare("SELECT $cols $from WHERE a.id = ?");
        $st->execute([$_GET['id']]);
        $a = $st->fetch();
        if (!$a) json_out(['success' => false, 'message' => 'Article not found.'], 404);
        json_out(['success' => true, 'article' => $a]);
    }

    $where = '1';
    $params = [];
    if (isset($_GET['feed'])) {
        $me = require_login();
        $from .= ' JOIN subscriptions s ON s.admin_id = a.author_id';
        $where = 's.user_id = ?';
        $params = [$me['id']];
    } elseif (isset($_GET['mine'])) {
        $me = require_login('admin');
        $where = 'a.author_id = ?';
        $params = [$me['id']];
    } elseif (isset($_GET['author'])) {
        $where = 'a.author_id = ?';
        $params = [$_GET['author']];
    }
    $st = $pdo->prepare("SELECT $cols $from WHERE $where ORDER BY a.created_at DESC LIMIT 50");
    $st->execute($params);
    json_out(['success' => true, 'articles' => $st->fetchAll()]);
}

$me = require_login('admin');

if ($method === 'POST') {
    $d = input();
    $title = trim($d['title'] ?? '');
    $body  = trim($d['body'] ?? '');
    if ($title === '' || $body === '') json_out(['success' => false, 'message' => 'Title and body are required.'], 422);

    if (!empty($d['id'])) {
        // author_id in the WHERE means you can only edit your own articles
        $st = $pdo->prepare('UPDATE articles SET title = ?, body = ? WHERE id = ? AND author_id = ?');
        $st->execute([$title, $body, $d['id'], $me['id']]);
        json_out(['success' => true, 'id' => $d['id']]);
    }

    $st = $pdo->prepare('INSERT INTO articles (author_id, title, body) VALUES (?, ?, ?)');
    $st->execute([$me['id'], $title, $body]);
    $newId = $pdo->lastInsertId();

    $st = $pdo->prepare('SELECT COUNT(*) FROM subscriptions WHERE admin_id = ?');
    $st->execute([$me['id']]);
    // Email sending is simulated: we only report how many people would get it.
    json_out(['success' => true, 'id' => $newId, 'notified' => (int)$st->fetchColumn()]);
}

if ($method === 'DELETE') {
    $st = $pdo->prepare('DELETE FROM articles WHERE id = ? AND author_id = ?');
    $st->execute([$_GET['id'] ?? 0, $me['id']]);
    json_out(['success' => $st->rowCount() > 0]);
}

json_out(['success' => false, 'message' => 'Method not allowed.'], 405);
