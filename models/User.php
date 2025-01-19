<?php
namespace Models;

class User {
    private $db;
    
    public function __construct() {
        $this->db = \Config\Database::getInstance()->getConnection();
    }
    
    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
    
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO users (email, username, postcode, password, latitude, longitude, preferences, liked_books)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $data['email'],
            $data['username'],
            $data['postcode'],
            $data['password'],
            $data['latitude'],
            $data['longitude'],
            json_encode($data['preferences'] ?? []),
            json_encode($data['liked_books'] ?? [])
        ]);
    }
    
    public function findById($id) {
        $stmt = $this->db->prepare("
            SELECT id, email, username, postcode, latitude, longitude, preferences, liked_books 
            FROM users WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}