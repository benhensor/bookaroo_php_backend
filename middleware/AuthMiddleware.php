<?php
namespace Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthMiddleware {
    public static function authenticate() {
        try {
            // Check for token in cookies, matching Node.js implementation
            $token = $_COOKIE['authToken'] ?? null;
            
            if (!$token) {
                error_log('Token missing');
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized']);
                exit;
            }

            try {
                // Verify token using JWT_SECRET
                $verified = JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));
                
                // Set user data for the request
                return $verified;
                
            } catch (\Exception $e) {
                error_log('Token verification failed: ' . $e->getMessage());
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized']);
                exit;
            }
        } catch (\Exception $e) {
            error_log('Authentication error: ' . $e->getMessage());
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
    }
}