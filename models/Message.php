<?php
namespace Models;

class Message {
  private $db;

  public function __construct() {
    $this->db = \Config\Database::getInstance()->getConnection();
  }

  public function create($data) {
    $stmt = $this->db->prepare("
      INSERT INTO messages (sender_id, recipient_id, book_id, message, is_read)
      VALUES (?, ?, ?, ?, ?)
    ");

    return $stmt->execute([
      $data['sender_id'],
      $data['recipient_id'], 
      $data['book_id'],
      $data['message'],
      $data['is_read'] ?? false
    ]);
  }

  public function getUserMessages($userId) {
    $stmt = $this->db->prepare("
      SELECT m.*, 
        s.username as sender_username, s.email as sender_email,
        r.username as recipient_username, r.email as recipient_email,
        b.id as book_id, b.isbn, b.cover_img, b.title, b.author, 
        b.publisher, b.published_date, b.category, b.book_condition, b.notes
      FROM messages m
      JOIN users s ON m.sender_id = s.id
      JOIN users r ON m.recipient_id = r.id
      JOIN books b ON m.book_id = b.id
      WHERE m.recipient_id = ?
      ORDER BY m.created_at DESC
    ");
    
    $stmt->execute([$userId]);
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }

  public function getAllMessages() {
    $stmt = $this->db->prepare("
      SELECT m.*, 
        s.username as sender_username, s.email as sender_email,
        r.username as recipient_username, r.email as recipient_email,
        b.id as book_id, b.isbn, b.cover_img, b.title, b.author, 
        b.publisher, b.published_date, b.category, b.book_condition, b.notes
      FROM messages m
      JOIN users s ON m.sender_id = s.id
      JOIN users r ON m.recipient_id = r.id
      JOIN books b ON m.book_id = b.id
      ORDER BY m.created_at DESC
    ");
    
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }

  public function findById($id) {
    $stmt = $this->db->prepare("
      SELECT * FROM messages WHERE id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch(\PDO::FETCH_ASSOC);
  }

  public function markAsRead($id) {
    $stmt = $this->db->prepare("
      UPDATE messages SET is_read = true WHERE id = ?
    ");
    return $stmt->execute([$id]);
  }

  public function markAsUnread($id) {
    $stmt = $this->db->prepare("
      UPDATE messages SET is_read = false WHERE id = ?
    ");
    return $stmt->execute([$id]);
  }

  public function delete($id) {
    $stmt = $this->db->prepare("
      DELETE FROM messages WHERE id = ?
    ");
    return $stmt->execute([$id]);
  }
}