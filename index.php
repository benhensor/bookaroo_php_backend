<?php
require_once __DIR__ . '/vendor/autoload.php';

use Config\Database;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Set error reporting in development
if ($_ENV['APP_ENV'] === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// CORS headers
$allowedOrigin = $_ENV['FRONTEND_URL'] ?? 'https://bookaroo-frontend.vercel.app';

header("Access-Control-Allow-Origin: $allowedOrigin");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit();
}

// Parse JSON body
$rawInput = file_get_contents('php://input');
if (!empty($rawInput)) {
    $_POST = json_decode($rawInput, true) ?? [];
}

// Test database connection
try {
    $db = Database::getInstance()->getConnection();
    error_log("Database connected successfully.");
} catch (Exception $e) {
    error_log("Unable to connect to the database: " . $e->getMessage());
    error_log("Full error details: " . json_encode($e->getMessage(), JSON_PRETTY_PRINT));
}

// Route handling
$requestUri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Parse the route
$path = parse_url($requestUri, PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));
$baseRoute = $pathParts[1] ?? ''; // 'api'
$resource = $pathParts[2] ?? ''; // 'auth', 'users', 'books', 'messages'
$action = $pathParts[3] ?? ''; // specific endpoint

// Set content type for all responses
header('Content-Type: application/json');

// Router
try {
    switch ("$baseRoute/$resource") {
        case 'api/test':
            echo json_encode(['message' => 'Server is running']);
            break;

        case 'api/auth':
            require_once __DIR__ . '/routes/AuthRoutes.php';
            break;

        case 'api/users':
            require_once __DIR__ . '/routes/UsersRoutes.php';
            break;

        case 'api/books':
            require_once __DIR__ . '/routes/BooksRoutes.php';
            break;

        case 'api/messages':
            require_once __DIR__ . '/routes/MessagesRoutes.php';
            break;

        default:
            http_response_code(404);
            echo json_encode(['error' => 'Route not found']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}