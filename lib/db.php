<?php
// lib/db.php - database connection (same idea as before).
// Local XAMPP: no env vars -> root / no password / localhost.
// Vercel: set DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD (and DB_SSL_CA if your host needs TLS).
$host     = getenv('DB_HOST') ?: 'localhost';
$port     = getenv('DB_PORT') ?: '3306';
$dbname   = getenv('DB_NAME') ?: 'newsletter_db';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
   $sslCa = getenv('DB_SSL_CA') ? __DIR__ . '/../' . getenv('DB_SSL_CA') : null;

try {
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    if ($sslCa) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = $sslCa;
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
    }
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password, $options);
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(["success" => false, "message" => "Database connection failed: " . $e->getMessage() . " | CA: " . ($sslCa ?: 'none') . " exists=" . (($sslCa && file_exists($sslCa)) ? 'yes' : 'no')]);
    exit;
}
