<?php
// unsubscribe.php
// Called by AngularJS via $http.post('unsubscribe.php', {id: ...}).
// Rather than deleting the row outright, we soft-delete by flipping
// `status` to 'unsubscribed'. This keeps a history and is generally
// better practice than a hard DELETE — but a hard delete version is
// included below (commented) if your assignment specifically asks for one.

header('Content-Type: application/json');
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Only POST requests are allowed."]);
    exit;
}

$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true);

$id = $data['id'] ?? null;
$email = $data['email'] ?? null;

if (!$id && !$email) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "An id or email is required."]);
    exit;
}

try {
    if ($id) {
        $stmt = $pdo->prepare("UPDATE subscribers SET status = 'unsubscribed' WHERE id = :id");
        $stmt->execute([':id' => $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE subscribers SET status = 'unsubscribed' WHERE email = :email");
        $stmt->execute([':email' => $email]);
    }

    // --- Hard-delete alternative (uncomment to use instead) ---
    // $stmt = $pdo->prepare("DELETE FROM subscribers WHERE id = :id");
    // $stmt->execute([':id' => $id]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Subscriber not found."]);
        exit;
    }

    echo json_encode(["success" => true, "message" => "Unsubscribed successfully."]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Server error: " . $e->getMessage()]);
}
