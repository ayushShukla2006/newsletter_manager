<?php
// subscribe.php
// Called by AngularJS via $http.post('subscribe.php', {...}).
// AngularJS's $http sends the request body as a JSON string (not as
// standard form-urlencoded data), so PHP's normal $_POST superglobal
// will be EMPTY here. We have to read the raw request body ourselves
// with php://input and decode it manually.

header('Content-Type: application/json');
require 'db.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Only POST requests are allowed."]);
    exit;
}

// Read raw JSON body sent by AngularJS's $http.post()
$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid or missing JSON payload."]);
    exit;
}

// --- Server-side validation ---
// Never trust the client. AngularJS validates on the frontend for UX,
// but PHP must re-validate because the frontend check can be bypassed
// (e.g. someone calling this endpoint directly with curl/Postman).
$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$topics = $data['topics'] ?? []; // expected: array of strings, e.g. ["Tech", "Sports"]

$errors = [];

if ($name === '') {
    $errors[] = "Name is required.";
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "A valid email is required.";
}
if (!is_array($topics)) {
    $topics = [];
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(["success" => false, "message" => implode(" ", $errors)]);
    exit;
}

$topicsString = implode(",", array_map('trim', $topics));

try {
    // Prepared statement — protects against SQL injection.
    $stmt = $pdo->prepare(
        "INSERT INTO subscribers (name, email, topics, status) VALUES (:name, :email, :topics, 'active')"
    );
    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':topics' => $topicsString,
    ]);

    echo json_encode([
        "success" => true,
        "message" => "Subscribed successfully.",
        "id" => $pdo->lastInsertId(),
    ]);

} catch (PDOException $e) {
    // Error code 23000 = integrity constraint violation (e.g. duplicate email,
    // since we set email as UNIQUE in schema.sql)
    if ($e->getCode() == 23000) {
        http_response_code(409);
        echo json_encode(["success" => false, "message" => "This email is already subscribed."]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Server error: " . $e->getMessage()]);
    }
}
