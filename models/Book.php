<?php

namespace Models;

use PDO;
use PDOException;

class Book
{
  private $db;

  public function __construct() {
    $this->db = $this->db = \Config\Database::getInstance()->getConnection();
  }

  public function create($data) {
    try {
      // Format the category data - convert array to JSON string
      $formattedData = $data;
      if (isset($formattedData['category']) && is_array($formattedData['category'])) {
        $formattedData['category'] = json_encode($formattedData['category']);
      }

      // Also ensure we're using book_condition not condition
      if (isset($formattedData['condition'])) {
        $formattedData['book_condition'] = $formattedData['condition'];
        unset($formattedData['condition']);
      }

      $fields = [
        'isbn',
        'cover_img',
        'title',
        'author',
        'published_date',
        'publisher',
        'category',
        'book_condition',
        'notes',
        'user_id',
        'book_latitude',
        'book_longitude'
      ];

      $placeholders = array_fill(0, count($fields), '?');
      $sql = "INSERT INTO books (" . implode(', ', $fields) . ") 
                    VALUES (" . implode(', ', $placeholders) . ")";

      $stmt = $this->db->prepare($sql);
      $stmt->execute(array_map(fn($field) => $formattedData[$field], $fields));

      return $this->findById($this->db->lastInsertId());
    } catch (PDOException $e) {
      error_log("Database error in create: " . $e->getMessage());
      throw new \Exception('Error creating book listing: ' . $e->getMessage());
    }
  }

  public function findById($id) {
    try {
      $stmt = $this->db->prepare("SELECT id, isbn, cover_img, title, author, published_date, publisher, category, book_condition, notes, user_id, book_latitude, book_longitude FROM books WHERE id = ?");
      $stmt->execute([$id]);
      return $stmt->fetch(PDO::FETCH_OBJ);
    } catch (PDOException $e) {
      error_log("Database error in findById: " . $e->getMessage());
      throw new \Exception('Database error occurred');
    }
  }

  public function delete($id)
  {
    try {
      $stmt = $this->db->prepare("DELETE FROM books WHERE id = ?");
      return $stmt->execute([$id]);
    } catch (PDOException $e) {
      error_log("Database error in delete: " . $e->getMessage());
      throw new \Exception('Error deleting book');
    }
  }

  public function findAll() {
      try {
          $sql = "SELECT id, isbn, cover_img, title, author, published_date, publisher, 
                        category, book_condition, notes, user_id, book_latitude, book_longitude 
                  FROM books";
          
          $stmt = $this->db->prepare($sql);
          $stmt->execute();
          error_log("Found " . $stmt->rowCount() . " books");
          return $stmt->fetchAll(PDO::FETCH_OBJ);
      } catch (PDOException $e) {
          error_log("Database error in findAll: " . $e->getMessage());
          throw new \Exception('Database error occurred');
      }
  }
}
