<?php
namespace Controllers;

use Middleware\AuthMiddleware;
use Models\User;
use Models\Book;
use GuzzleHttp\Client;

class UserController {
    private $user;
    private $httpClient;

    public function __construct() {
        $this->user = new User();
        $this->httpClient = new Client();
    }

    public function getUserDetails() {
        try {
            $user = AuthMiddleware::authenticate();
            if (!$user || !$user['id']) {
                http_response_code(400);
                echo json_encode(['error' => 'User not authenticated']);
                return;
            }

            $data = $this->user->findById($user['id']);
            if (!$data) {
                http_response_code(404);
                echo json_encode(['error' => 'User not found']);
                return;
            }

            // Exclude password from response
            unset($data->password);
            
            http_response_code(200);
            echo json_encode($data);

        } catch (\Exception $e) {
            error_log("Error in getUserDetails: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
    }

    public function getUserById($user_id) {
        try {
            if (!$user_id) {
                http_response_code(400);
                echo json_encode(['error' => 'User ID is required']);
                return;
            }

            $data = $this->user->findById($user_id);
            if (!$data) {
                http_response_code(404);
                echo json_encode(['error' => 'User not found']);
                return;
            }

            // Exclude password from response
            unset($data->password);

            http_response_code(200);
            echo json_encode($data);

        } catch (\Exception $e) {
            error_log("Error fetching user by ID: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
    }

    public function searchUsers() {
        try {
            $userId = $_GET['userId'] ?? null;
            
            if (!$userId) {
                http_response_code(400);
                echo json_encode(['error' => 'User ID is required']);
                return;
            }

            $data = $this->user->findAll([
                'where' => ['id' => $userId],
                'select' => ['id', 'email', 'username', 'postcode', 'latitude', 'longitude']
            ]);

            http_response_code(200);
            echo json_encode($data);

        } catch (\Exception $e) {
            error_log("Error searching for users: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
    }

    public function updateUserDetails() {
        try {
            $user = AuthMiddleware::authenticate();
            $data = json_decode(file_get_contents('php://input'), true);
            
            $username = $data['username'] ?? null;
            $email = $data['email'] ?? null;
            $postcode = $data['postcode'] ?? null;

            if (!$username || !$email || !$postcode) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields']);
                return;
            }

            // Geocoding integration
            $response = $this->httpClient->get(
                "https://api.geocodify.com/v2/geocode",
                [
                    'query' => [
                        'api_key' => getenv('GEOCODIFY_API_KEY'),
                        'q' => $postcode
                    ]
                ]
            );

            $geocodeData = json_decode($response->getBody(), true);
            
            if (empty($geocodeData['response']['features'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid address, geocoding failed']);
                return;
            }

            $location = $geocodeData['response']['features'][0]['geometry']['coordinates'];
            
            $updateData = [
                'username' => $username,
                'email' => $email,
                'postcode' => $postcode,
                'latitude' => $location[1],
                'longitude' => $location[0]
            ];

            $updated = $this->user->update($user['id'], $updateData);
            
            if (!$updated) {
                http_response_code(404);
                echo json_encode(['message' => 'User not found']);
                return;
            }

            $updatedUser = $this->user->findById($user['id']);
            unset($updatedUser->password);

            http_response_code(200);
            echo json_encode($updatedUser);

        } catch (\Exception $e) {
            error_log("Error updating user details: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
    }

    public function likeBook() {
        try {
            $user = AuthMiddleware::authenticate();
            $data = json_decode(file_get_contents('php://input'), true);
            $bookId = $data['bookId'] ?? null;

            if (!$bookId) {
                http_response_code(400);
                echo json_encode(['error' => 'Book ID is required']);
                return;
            }

            $userData = $this->user->findById($user['id']);
            $likedBooks = json_decode($userData->liked_books ?? '[]');

            if (in_array($bookId, $likedBooks)) {
                http_response_code(400);
                echo json_encode(['message' => 'Book is already liked']);
                return;
            }

            $likedBooks[] = $bookId;
            $this->user->update($user['id'], ['liked_books' => json_encode($likedBooks)]);

            http_response_code(200);
            echo json_encode([
                'message' => 'Book liked successfully',
                'likedBooks' => $likedBooks
            ]);

        } catch (\Exception $e) {
            error_log("Error liking book: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
    }

    public function unlikeBook() {
        try {
            $user = AuthMiddleware::authenticate();
            $data = json_decode(file_get_contents('php://input'), true);
            $bookId = $data['bookId'] ?? null;

            if (!$bookId) {
                http_response_code(400);
                echo json_encode(['error' => 'Book ID is required']);
                return;
            }

            $userData = $this->user->findById($user['id']);
            $likedBooks = json_decode($userData->liked_books ?? '[]');

            if (!in_array($bookId, $likedBooks)) {
                http_response_code(400);
                echo json_encode(['message' => 'Book is not liked']);
                return;
            }

            $likedBooks = array_values(array_filter($likedBooks, fn($id) => $id !== $bookId));
            $this->user->update($user['id'], ['liked_books' => json_encode($likedBooks)]);

            http_response_code(200);
            echo json_encode([
                'message' => 'Book unliked successfully',
                'likedBooks' => $likedBooks
            ]);

        } catch (\Exception $e) {
            error_log("Error unliking book: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
    }

    public function getLikedBooks() {
        try {
            $user = AuthMiddleware::authenticate();
            
            $userData = $this->user->findById($user['id']);
            if (!$userData) {
                http_response_code(404);
                echo json_encode(['message' => 'User not found']);
                return;
            }

            $likedBooks = json_decode($userData->liked_books ?? '[]');
            if (empty($likedBooks)) {
                http_response_code(200);
                echo json_encode([]);
                return;
            }

            $books = (new Book())->findAll([
                'where' => ['id' => ['in' => $likedBooks]]
            ]);

            http_response_code(200);
            echo json_encode($books);

        } catch (\Exception $e) {
            error_log("Error fetching liked books: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
    }

    public function updatePreferences() {
        try {
            $user = AuthMiddleware::authenticate();
            $data = json_decode(file_get_contents('php://input'), true);
            $preferences = $data['preferences'] ?? null;

            if (!$preferences) {
                http_response_code(400);
                echo json_encode(['error' => 'Preferences are required']);
                return;
            }

            $updated = $this->user->update($user['id'], ['preferences' => json_encode($preferences)]);
            
            if (!$updated) {
                http_response_code(404);
                echo json_encode(['message' => 'User not found']);
                return;
            }

            http_response_code(200);
            echo json_encode([
                'message' => 'Preferences updated successfully',
                'preferences' => $preferences
            ]);

        } catch (\Exception $e) {
            error_log("Error updating preferences: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
    }
}