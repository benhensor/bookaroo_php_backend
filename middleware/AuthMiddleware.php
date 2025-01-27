<?php
namespace Middleware;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthMiddleware {
    public static function authenticate() {
        error_log('Cookies received: ' . json_encode($_COOKIE));
        error_log('Headers received: ' . json_encode(getallheaders()));
        
        try {
            if (!isset($_COOKIE['authToken'])) {
                throw new \Exception('No authentication token');
            }
            
            $token = $_COOKIE['authToken'];
            error_log('Token before decode: ' . $token);
            
            // Verify token using JWT_SECRET
            $decoded = JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));
            error_log('Token decoded successfully');
            
            return [
                'id' => $decoded->id,
                'email' => $decoded->email
            ];
            
        } catch (\Exception $e) {
            error_log('Authentication error: ' . $e->getMessage());
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
    }
}