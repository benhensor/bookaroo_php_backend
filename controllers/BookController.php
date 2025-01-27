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

  public function getAllBooks() {
    try {
      $user = AuthMiddleware::authenticate();
      if (!$user) {
        throw new \Exception('User not authenticated');
      }
      
      $books = $this->book->findAll();
      if (!is_array($books)) {
        throw new \Exception('Invalid books data format');
      }
      
      // Debug
      error_log("Total books found: " . count($books));
      
      // Clear any previous output
      ob_clean();
      
      // Set headers
      header('Content-Type: application/json; charset=utf-8');
      
      // Encode with error checking
      $json = json_encode($books, JSON_THROW_ON_ERROR);
      if ($json === false) {
        throw new \Exception('JSON encoding failed: ' . json_last_error_msg());
      }
      
      echo $json;
        
    } catch (\Exception $e) {
      error_log("Error in getAllBooks: " . $e->getMessage());
      http_response_code(500);
      echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage()
      ]);
    }
  }
}
