<?php

namespace Controllers;

use Middleware\AuthMiddleware;
use Models\User;
use Models\Book;

class BookController
{
  private $user;
  private $book;

  public function __construct()
  {
    $this->user = new User();
    $this->book = new Book();
  }

  public function createNewListing()
  {
    try {
      $user = AuthMiddleware::authenticate();
      $data = json_decode(file_get_contents('php://input'), true);

      // Debug incoming data
      error_log("Received data: " . json_encode($data));

      // Extract all required fields
      $requiredFields = [
        'isbn',
        'cover_img',
        'title',
        'author',
        'published_date',
        'publisher',
        'category',
        'book_condition',
        'notes',
      ];

      $missingFields = [];
      foreach ($requiredFields as $field) {
        if (!isset($data[$field])) {
          $missingFields[] = $field;
        }
      }

      if (!empty($missingFields)) {
        http_response_code(400);
        echo json_encode([
          'error' => 'Missing required fields',
          'details' => $missingFields,
          'validation_errors' => $missingFields
        ]);
        return;
      }

      // Check if user exists
      $userData = $this->user->findById($user['id']);
      if (!$userData) {
        http_response_code(404);
        echo json_encode([
          'error' => 'User not found',
          'details' => 'Invalid user ID',
          'validation_errors' => []
        ]);
        return;
      }

      // Create new book with user's location
      $bookData = array_merge($data, [
        'user_id' => $user['id'],
        'book_latitude' => $userData['latitude'] ?? null,
        'book_longitude' => $userData['longitude'] ?? null
      ]);

      // Debug book data before creation
      error_log("Attempting to create book with data: " . json_encode($bookData));

      $newBook = $this->book->create($bookData);
      if (!$newBook) {
        throw new \Exception('Database error while creating book');
      }

      http_response_code(201);
      echo json_encode([
        'success' => true,
        'message' => 'Book listed successfully!',
        'data' => $newBook
      ]);
    } catch (\Exception $e) {
      error_log("Error in createNewListing: " . $e->getMessage());
      http_response_code(400);
      echo json_encode([
        'error' => 'Error creating book listing',
        'details' => $e->getMessage(),
        'validation_errors' => []
      ]);
    }
  }

  public function deleteListing($bookId)
  {
    try {
      $user = AuthMiddleware::authenticate();

      $book = $this->book->findById($bookId);
      if (!$book) {
        http_response_code(404);
        echo json_encode(['error' => 'Book not found']);
        return;
      }

      // Optional: Check if the book belongs to the authenticated user
      if ($book->user_id !== $user['id']) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized to delete this listing']);
        return;
      }

      $this->book->delete($bookId);
      http_response_code(204);
      exit;
    } catch (\Exception $e) {
      error_log("Error in deleteListing: " . $e->getMessage());
      http_response_code(500);
      echo json_encode(['error' => $e->getMessage()]);
    }
  }

  public function getListedBooks()
  {
    try {
      $user = AuthMiddleware::authenticate();

      $books = $this->book->findAllWithUser([
        'where' => ['userId' => $user['id']],
        'include' => [
          'user' => [
            'attributes' => [
              'id',
              'email',
              'username',
              'postcode',
              'latitude',
              'longitude'
            ]
          ]
        ]
      ]);

      http_response_code(200);
      echo json_encode($books);
    } catch (\Exception $e) {
      error_log("Error in getListedBooks: " . $e->getMessage());
      http_response_code(500);
      echo json_encode(['error' => $e->getMessage()]);
    }
  }

  public function getRecommendations()
  {
    try {
      $user = AuthMiddleware::authenticate();

      // Get preferences from query params
      if (!isset($_GET['preferences'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Preferences parameter is required']);
        return;
      }

      $preferences = json_decode($_GET['preferences'], true);

      if (!is_array($preferences)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid preferences format']);
        return;
      }

      // Pass user ID and preferences to model
      $books = $this->book->findRecommendations($user['id'], $preferences);

      http_response_code(200);
      echo json_encode($books);
    } catch (\Exception $e) {
      error_log("Error fetching recommendations: " . $e->getMessage());
      http_response_code(500);
      echo json_encode(['error' => 'Internal server error']);
    }
  }

  public function getAllBooks()
  {
    try {
      $books = $this->book->findAll();

      http_response_code(200);
      echo json_encode($books);
    } catch (\Exception $e) {
      error_log("Error fetching all books: " . $e->getMessage());
      http_response_code(500);
      echo json_encode(['error' => 'Internal server error']);
    }
  }

  public function searchBooks()
  {
    try {
      $user = AuthMiddleware::authenticate();
      $query = $_GET['query'] ?? null;

      if (!$query) {
        http_response_code(400);
        echo json_encode(['error' => 'Query parameter is required']);
        return;
      }

      $books = $this->book->search($query, $user['id']);

      http_response_code(200);
      echo json_encode($books);
    } catch (\Exception $e) {
      error_log("Error searching books: " . $e->getMessage());
      http_response_code(500);
      echo json_encode(['error' => 'Internal server error']);
    }
  }
}
