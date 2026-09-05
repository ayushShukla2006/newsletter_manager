<?php
// db.php
// Central database connection file, included by every endpoint.
//
// Reads connection details from environment variables so the SAME file
// works in two places without editing:
//   - Locally on XAMPP/WAMP: no env vars set, so it falls back to the
//     classic root/no-password/localhost defaults.
//   - On Vercel: DB_HOST/DB_NAME/DB_USER/DB_PASSWORD/DB_PORT are set in
//     Project Settings > Environment Variables, pointing at your hosted
//     MySQL (e.g. Aiven).
//
// Aiven's MySQL (and most managed MySQL hosts) require TLS. If DB_SSL_CA
// is set to a path inside the container, PDO uses it to verify the
// server certificate. Locally this is left empty, so XAMPP's plain
// unencrypted connection still works unchanged.

$host     = getenv('DB_HOST') ?: 'localhost';
$port     = getenv('DB_PORT') ?: '3306';
$dbname   = getenv('DB_NAME') ?: 'newsletter_db';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$sslCa    = getenv('DB_SSL_CA') ?: null; // e.g. /app/certs/aiven-ca.pem

try {
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,       // throw exceptions on SQL errors
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,  // return rows as assoc arrays
    ];

    if ($sslCa) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = $sslCa;
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
    }

    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        $options
    );
} catch (PDOException $e) {
    // If the DB connection itself fails, every endpoint that includes this
    // file should stop and return a JSON error rather than a raw PHP error page.
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(["success" => false, "message" => "Database connection failed: " . $e->getMessage()]);
    exit;
}
