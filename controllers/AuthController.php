<?php
namespace Controllers;

use Middleware\AuthMiddleware;
use Firebase\JWT\JWT;
use Models\User;

class AuthController {
    private $user;
    
    public function __construct() {
        $this->user = new User();
    }

    public function test() {
        header('Content-Type: application/json');
        echo json_encode([
            'message' => 'Test route working!',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function register() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate input
            $this->validateRegistrationData($data);
            
            // Hash password
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
            
            // Get coordinates from postcode
            $geocodeResponse = file_get_contents(
                "https://api.geocodify.com/v2/geocode?api_key=" . 
                $_ENV['GEOCODIFY_API_KEY'] . "&q=" . urlencode($data['postcode'])
            );
            
            $geocodeData = json_decode($geocodeResponse, true);
            $coordinates = $geocodeData['response']['features'][0]['geometry']['coordinates'];
            
            $data['latitude'] = $coordinates[1];
            $data['longitude'] = $coordinates[0];
            
            // Create user
            $this->user->create($data);
            
            http_response_code(201);
            echo json_encode(['message' => 'User registered successfully']);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    public function login() {
        // Set content type header
        header('Content-Type: application/json');
        
        try {
            // Get and decode request body
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate input
            if (!isset($data['email']) || !isset($data['password'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Email and password are required']);
                return;
            }
            
            // Find user
            $user = $this->user->findByEmail($data['email']);
            if (!$user) {
                http_response_code(404);
                echo json_encode(['error' => 'User not found']);
                return;
            }
            
            // Verify password
            if (!password_verify($data['password'], $user['password'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid credentials']);
                return;
            }
            
            // Generate JWT 
            $token = JWT::encode([
                'id' => $user['id'],
                'email' => $user['email'],
                'exp' => time() + (24 * 60 * 60), // 24 hours
                'iat' => time() // issued at time
            ], $_ENV['JWT_SECRET'], 'HS256');
            
            // Set cookie 
            setcookie('authToken', $token, [
                'expires' => time() + (24 * 60 * 60),
                'path' => '/bookaroo/',
                'domain' => $_ENV['COOKIE_DOMAIN'] ?? 'benhensor.co.uk',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'None'
            ]);
            
            // Remove password from user object before sending
            unset($user['password']);
            
            // Send success response
            http_response_code(200);
            echo json_encode(['user' => $user]);
            
        } catch (\Exception $e) {
            error_log('Login error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
    }

    public function logout() {
        setcookie('authToken', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => $_ENV['COOKIE_DOMAIN'],
            'secure' => true,
            'httponly' => true,
            'samesite' => 'None'
        ]);
        
        echo json_encode(['message' => 'Logged out']);
    }

    public function getCurrentUser() {
        header('Content-Type: application/json');
        
        try {
            $authenticatedUser = AuthMiddleware::authenticate();
            if (!$authenticatedUser) {
                http_response_code(401);
                echo json_encode(['error' => 'Not authenticated']);
                return;
            }

            $user = $this->user->findById($authenticatedUser['id']);
            if (!$user) {
                http_response_code(404);
                echo json_encode(['error' => 'User not found']);
                return;
            }

            unset($user['password']);
            echo json_encode(['user' => $user]);
            
        } catch (\Exception $e) {
            error_log('Get current user error: ' . $e->getMessage());
            http_response_code(401);
            echo json_encode(['error' => 'Authentication failed']);
        }
    }
    
    private function validateRegistrationData($data) {
        $errors = [];
        
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }
        
        if (strlen($data['username']) < 3 || strlen($data['username']) > 20) {
            $errors[] = 'Username must be between 3 and 20 characters';
        }
        
        if (!preg_match('/^([A-Z]{1,2}\d[A-Z\d]? ?\d[A-Z]{2}|GIR ?0A{2})$/', $data['postcode'])) {
            $errors[] = 'Invalid postcode format';
        }
        
        if (!preg_match('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]).{6,20}$/', $data['password'])) {
            $errors[] = 'Password does not meet requirements';
        }
        
        if (!empty($errors)) {
            throw new \Exception(implode(', ', $errors));
        }
    }
    
    private function validateLoginData($data) {
        if (!isset($data['email']) || !isset($data['password'])) {
            throw new \Exception('Email and password are required');
        }
    }
}