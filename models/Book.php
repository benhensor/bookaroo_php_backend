<?php
namespace Models;

use PDO;
use PDOException;

class Book {
    private $db;
    
    public function __construct() {
        $this->db = $this->db = \Config\Database::getInstance()->getConnection();
    }

    public function create($data) {
        try {
            $fields = [
                'isbn', 'coverImg', 'title', 'author', 'publishedDate',
                'publisher', 'category', 'condition', 'notes', 'userId',
                'bookLatitude', 'bookLongitude'
            ];
            
            $placeholders = array_fill(0, count($fields), '?');
            $sql = "INSERT INTO books (" . implode(', ', $fields) . ") 
                    VALUES (" . implode(', ', $placeholders) . ")";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array_map(fn($field) => $data[$field], $fields));
            
            return $this->findById($this->db->lastInsertId());
        } catch (PDOException $e) {
            error_log("Database error in create: " . $e->getMessage());
            throw new \Exception('Error creating book listing');
        }
    }

    public function findById($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM books WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Database error in findById: " . $e->getMessage());
            throw new \Exception('Database error occurred');
        }
    }

    public function delete($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM books WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Database error in delete: " . $e->getMessage());
            throw new \Exception('Error deleting book');
        }
    }

    public function findAllWithUser($options = []) {
        try {
            $sql = "
                SELECT b.*, 
                       u.id as user_id, 
                       u.email, 
                       u.username, 
                       u.postcode, 
                       u.latitude, 
                       u.longitude
                FROM books b
                JOIN users u ON b.userId = u.id
                WHERE b.userId = ?
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$options['where']['userId']]);
            
            $books = $stmt->fetchAll(PDO::FETCH_OBJ);
            
            // Transform results to match Sequelize's nested format
            return array_map(function($book) {
                $userData = [
                    'id' => $book->user_id,
                    'email' => $book->email,
                    'username' => $book->username,
                    'postcode' => $book->postcode,
                    'latitude' => $book->latitude,
                    'longitude' => $book->longitude
                ];
                
                unset($book->user_id, $book->email, $book->username, 
                      $book->postcode, $book->latitude, $book->longitude);
                
                $book->user = $userData;
                return $book;
            }, $books);
        } catch (PDOException $e) {
            error_log("Database error in findAllWithUser: " . $e->getMessage());
            throw new \Exception('Database error occurred');
        }
    }

    public function findRecommendations($userId, $preferences) {
        try {
            // Convert preferences array to a format that works with your database
            $preferencesJson = json_encode($preferences);
            
            $sql = "
                SELECT * FROM books 
                WHERE userId != :userId 
                AND category ?| :preferences
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':userId' => $userId,
                ':preferences' => $preferencesJson
            ]);
            
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Database error in findRecommendations: " . $e->getMessage());
            throw new \Exception('Database error occurred');
        }
    }

    public function findAll() {
        try {
            $stmt = $this->db->prepare("SELECT * FROM books");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Database error in findAll: " . $e->getMessage());
            throw new \Exception('Database error occurred');
        }
    }

    public function search($query, $currentUserId) {
        try {
            $sql = "
                SELECT * FROM books 
                WHERE userId != ? 
                AND (
                    LOWER(title) LIKE LOWER(?) 
                    OR LOWER(author) LIKE LOWER(?) 
                    OR EXISTS (
                        SELECT 1 
                        FROM json_array_elements_text(category::json) AS cat 
                        WHERE LOWER(cat) LIKE LOWER(?)
                    )
                )
            ";
            
            $queryParam = "%{$query}%";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $currentUserId,
                $queryParam,
                $queryParam,
                $queryParam
            ]);
            
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Database error in search: " . $e->getMessage());
            throw new \Exception('Database error occurred');
        }
    }
}