<?php
// get_subscribers.php
// Called by AngularJS via $http.get('get_subscribers.php').
// AngularJS expects a JSON array/object back; $http automatically
// parses a JSON response body into a JS object, so we just need to
// echo valid JSON with the right Content-Type header.

header('Content-Type: application/json');
require 'db.php';

try {
    $stmt = $pdo->prepare(
        "SELECT id, name, email, topics, status, created_at
         FROM subscribers
         WHERE status = 'active'
         ORDER BY created_at DESC"
    );
    $stmt->execute();
    $subscribers = $stmt->fetchAll();

    // Convert the stored comma-separated topics string back into an array
    // so the AngularJS frontend can loop over it easily with ng-repeat.
    foreach ($subscribers as &$s) {
        $s['topics'] = $s['topics'] === '' ? [] : explode(',', $s['topics']);
    }

    echo json_encode([
        "success" => true,
        "data" => $subscribers,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Server error: " . $e->getMessage()]);
}
