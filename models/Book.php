<?php
namespace Models;

class Book {
    private $db;
    
    public function __construct() {
        $this->db = \Config\Database::getInstance()->getConnection();
    }

    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO books (
                isbn, cover_img, title, author, published_date, publisher,
                category, condition, notes, user_id, book_latitude, book_longitude
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
            RETURNING id
        ");
        
        $stmt->execute([
            $data['isbn'],
            $data['coverImg'] ?? null,
            $data['title'],
            $data['author'],
            $data['publishedDate'] ?? null,
            $data['publisher'] ?? null,
            $data['category'] ? json_encode($data['category']) : null,
            $data['condition'] ?? null,
            $data['notes'] ?? null,
            $data['userId'],
            $data['bookLatitude'] ?? null,
            $data['bookLongitude'] ?? null
        ]);
        
        return $stmt->fetch(\PDO::FETCH_ASSOC)['id'];
    }

    public function findById($id) {
        $stmt = $this->db->prepare("
            SELECT 
                id,
                isbn,
                cover_img AS coverImg,
                title,
                author,
                published_date AS publishedDate,
                publisher,
                category,
                condition,
                notes,
                user_id AS userId,
                book_latitude AS bookLatitude,
                book_longitude AS bookLongitude
            FROM books 
            WHERE id = ?
        ");
        
        $stmt->execute([$id]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($result) {
            // Convert category from JSON string to array
            $result['category'] = $result['category'] ? json_decode($result['category'], true) : [];
            // Convert date string to proper format
            $result['publishedDate'] = $result['publishedDate'] ? date('Y-m-d', strtotime($result['publishedDate'])) : null;
        }
        
        return $result;
    }

    public function update($id, $data) {
        $updateFields = [];
        $values = [];
        
        // Build dynamic update query based on provided fields
        foreach ($data as $key => $value) {
            $dbKey = $this->toSnakeCase($key);
            if ($key === 'category') {
                $updateFields[] = "$dbKey = ?";
                $values[] = json_encode($value);
            } else {
                $updateFields[] = "$dbKey = ?";
                $values[] = $value;
            }
        }
        
        $values[] = $id;
        
        $stmt = $this->db->prepare("
            UPDATE books 
            SET " . implode(', ', $updateFields) . "
            WHERE id = ?
            RETURNING *
        ");
        
        $stmt->execute($values);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    private function toSnakeCase($input) {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
    }
}