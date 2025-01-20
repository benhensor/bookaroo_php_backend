<?php
namespace Controllers;

use Middleware\AuthMiddleware;
use Models\Message;
use Models\User;
use Models\Book;

class MessageController {
  private $message;
  private $user;
  private $book;

  public function __construct() {
    $this->message = new Message();
    $this->user = new User();
    $this->book = new Book();
  }

  public function getUsersMessages() {
    try {
      $user = AuthMiddleware::authenticate();
      $messages = $this->message->getUserMessages($user['id']);
      
      http_response_code(200);
      echo json_encode($messages);
    } catch (\Exception $e) {
      error_log('Error fetching all messages: ' . $e->getMessage());
      http_response_code(500);
      echo json_encode(['error' => 'Internal server error']);
    }
  }

  public function getAllMessages() {
    try {
      $messages = $this->message->getAllMessages();
      
      http_response_code(200);
      echo json_encode($messages);
    } catch (\Exception $e) {
      error_log('Error fetching all messages: ' . $e->getMessage());
      http_response_code(500);
      echo json_encode(['error' => 'Internal server error']);
    }
  }

  public function sendMessage() {
    try {
      $data = json_decode(file_get_contents('php://input'), true);
      $sender = $this->user->findById($data['sender_id']);
      $recipient = $this->user->findById($data['recipient_id']);
      $book = $this->book->findById($data['book_id']);

      if (!$sender || !$recipient || !$book) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid sender, recipient, or book']);
        return;
      }

      $newMessage = $this->message->create([
        'sender_id' => $data['sender_id'],
        'recipient_id' => $data['recipient_id'],
        'book_id' => $data['book_id'],
        'message' => $data['message'],
        'is_read' => false
      ]);

      http_response_code(201);
      echo json_encode($newMessage);
    } catch (\Exception $e) {
      error_log('Error sending message: ' . $e->getMessage());
      http_response_code(500);
      echo json_encode(['error' => 'Internal server error']);
    }
  }

  public function markAsRead($id) {
    try {
      $user = AuthMiddleware::authenticate();
      $message = $this->message->findById($id);

      if (!$message) {
        http_response_code(404);
        echo json_encode(['error' => 'Message not found']);
        return;
      }

      if ($message['recipient_id'] !== $user['id']) {
        http_response_code(403);
        echo json_encode(['error' => 'Not authorized to mark this message as read']);
        return;
      }

      $this->message->markAsRead($id);
      $updatedMessage = $this->message->findById($id);
      
      http_response_code(200);
      echo json_encode($updatedMessage);
    } catch (\Exception $e) {
      error_log('Error marking message as read: ' . $e->getMessage());
      http_response_code(500);
      echo json_encode(['error' => 'Internal server error']);
    }
  }

  public function markAsUnread($id) {
    try {
      $user = AuthMiddleware::authenticate();
      $message = $this->message->findById($id);

      if (!$message) {
        http_response_code(404);
        echo json_encode(['error' => 'Message not found']);
        return;
      }

      if ($message['recipient_id'] !== $user['id']) {
        http_response_code(403);
        echo json_encode(['error' => 'Not authorized to mark this message as unread']);
        return;
      }

      $this->message->markAsUnread($id);
      $updatedMessage = $this->message->findById($id);
      
      http_response_code(200);
      echo json_encode($updatedMessage);
    } catch (\Exception $e) {
      error_log('Error marking message as unread: ' . $e->getMessage());
      http_response_code(500);
      echo json_encode(['error' => 'Internal server error']);
    }
  }

  public function deleteMessage($id) {
    try {
      $user = AuthMiddleware::authenticate();
      $message = $this->message->findById($id);

      if (!$message) {
        http_response_code(404);
        echo json_encode(['error' => 'Message not found']);
        return;
      }

      if ($message['sender_id'] !== $user['id'] && $message['recipient_id'] !== $user['id']) {
        http_response_code(403);
        echo json_encode(['error' => 'Not authorized to delete this message']);
        return;
      }

      $this->message->delete($id);
      
      http_response_code(200);
      echo json_encode(['message' => 'Message deleted successfully']);
    } catch (\Exception $e) {
      error_log('Error deleting message: ' . $e->getMessage());
      http_response_code(500);
      echo json_encode(['error' => 'Internal server error']);
    }
  }
}