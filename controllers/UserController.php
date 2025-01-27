<?php

namespace Controllers;

use Middleware\AuthMiddleware;
use Models\User;
use Models\Book;
use GuzzleHttp\Client;

class UserController
{
  private $user;
  private $httpClient;

  public function __construct()
  {
    $this->user = new User();
    $this->httpClient = new Client();
  }

  public function getUserDetails()
  {
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

      $preferences = json_decode($data->preferences);
      $data->preferences = is_array($preferences) ? $preferences : [];

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

  public function getUserById($user_id)
  {
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

  public function searchUsers()
  {
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

  public function updateUserDetails()
  {
    error_log('Update user details endpoint hit');
    error_log('Request body: ' . file_get_contents('php://input'));
    try {
      $user = AuthMiddleware::authenticate();
      $data = json_decode(file_get_contents('php://input'), true);

      error_log('Decoded data: ' . print_r($data, true));
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
            'api_key' => $_ENV['GEOCODIFY_API_KEY'],
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

  public function likeBook()
  {
    try {
      $user = AuthMiddleware::authenticate();
      $data = json_decode(file_get_contents('php://input'), true);
      $bookId = $data['book_id'] ?? null;

      if (!$bookId) {
        http_response_code(400);
        echo json_encode(['error' => 'Book ID is required']);
        return;
      }

      $userData = $this->user->findById($user['id']);
      $likedBooks = json_decode($userData['liked_books'] ?? '[]');

      if (in_array((string)$bookId, $likedBooks)) {
        http_response_code(400);
        echo json_encode(['message' => 'Book is already liked']);
        return;
      }

      $likedBooks[] = (string)$bookId;
      $this->user->update($user['id'], ['liked_books' => json_encode($likedBooks)]);

      http_response_code(200);
      echo json_encode([
        'message' => 'Book liked successfully',
        'likedBooks' => $likedBooks,
        'book_id' => $bookId  // Add the book_id to response
      ]);
    } catch (\Exception $e) {
      error_log("Error liking book: " . $e->getMessage());
      http_response_code(500);
      echo json_encode(['error' => 'Internal server error']);
    }
  }

  public function unlikeBook()
  {
    try {
      $user = AuthMiddleware::authenticate();
      $data = json_decode(file_get_contents('php://input'), true);
      $bookId = $data['book_id'] ?? null;

      if (!$bookId) {
        http_response_code(400);
        echo json_encode(['error' => 'Book ID is required']);
        return;
      }

      $userData = $this->user->findById($user['id']);
      $likedBooks = json_decode($userData['liked_books'] ?? '[]');

      if (!in_array((string)$bookId, $likedBooks)) {
        http_response_code(400);
        echo json_encode(['message' => 'Book is not liked']);
        return;
      }

      $likedBooks = array_values(array_filter($likedBooks, fn($id) => $id !== (string)$bookId));
      $this->user->update($user['id'], ['liked_books' => json_encode($likedBooks)]);

      http_response_code(200);
      echo json_encode([
        'message' => 'Book unliked successfully',
        'likedBooks' => $likedBooks,
        'book_id' => $bookId  // Add the book_id to response
      ]);
    } catch (\Exception $e) {
      error_log("Error unliking book: " . $e->getMessage());
      http_response_code(500);
      echo json_encode(['error' => 'Internal server error']);
    }
  }

  public function getLikedBooks() {
    try {
        // Make sure there's no output before headers
        ob_clean(); // Clear output buffer
        
        $user = AuthMiddleware::authenticate();
        
        $userData = $this->user->findById($user['id']);
        if (!$userData) {
            http_response_code(404);
            echo json_encode(['message' => 'User not found']);
            return;
        }

        $likedBookIds = json_decode($userData['liked_books'] ?? '[]');
        
        if (empty($likedBookIds)) {
            http_response_code(200);
            echo json_encode([]);
            return;
        }

        $allBooks = (new Book())->findAll();
        $likedBooks = [];
        
        foreach ($allBooks as $book) {
            if (in_array((string)$book['id'], array_map('strval', $likedBookIds))) {
                $likedBooks[] = $book;
            }
        }

        // Debug log before sending response
        error_log("Liked books array: " . print_r($likedBooks, true));
        
        http_response_code(200);
        echo json_encode(array_values($likedBooks));
        
    } catch (\Exception $e) {
        error_log("Error fetching liked books: " . $e->getMessage());
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['error' => 'Internal server error']);
    }
}

  public function updatePreferences()
  {
    try {
      $user = AuthMiddleware::authenticate();
      $data = json_decode(file_get_contents('php://input'), true);
      $preferences = $data['preferences'] ?? [];

      // Ensure preferences is an array
      if (!is_array($preferences)) {
        $preferences = [];
      }

      // Convert preferences to JSON string for storage
      $preferencesJson = json_encode($preferences);

      $updated = $this->user->update($user['id'], ['preferences' => $preferencesJson]);

      if (!$updated) {
        http_response_code(404);
        echo json_encode(['message' => 'User not found']);
        return;
      }

      // Return decoded preferences in response
      http_response_code(200);
      echo json_encode([
        'message' => 'Preferences updated successfully',
        'preferences' => $preferences  // Send back as array
      ]);
    } catch (\Exception $e) {
      error_log("Error updating preferences: " . $e->getMessage());
      http_response_code(500);
      echo json_encode(['error' => 'Internal server error']);
    }
  }
}
