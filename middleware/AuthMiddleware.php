<?php
namespace Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthMiddleware {
    public static function authenticate() {
        try {
            if (!isset($_COOKIE['authToken'])) {
                throw new \Exception('No authentication token');
            }

            try {

                // Check for token in cookies
                $token = $_COOKIE['authToken'];

                // Verify token using JWT_SECRET
                $decoded = JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));
                
                return [
                    'id' => $decoded->id,
                    'email' => $decoded->email
                ];
                
            } catch (\Exception $e) {
                error_log('Token verification failed: ' . $e->getMessage());
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized - Invalid token']);
                exit;
            }
        } catch (\Exception $e) {
            error_log('Authentication error: ' . $e->getMessage());
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized - Authentication failed']);
            exit;
        }
    }
}