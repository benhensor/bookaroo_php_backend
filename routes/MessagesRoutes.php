<?php

use Controllers\MessageController;

$controller = new MessageController();

switch ($method) {
  case 'GET':
    switch ($action) {
      case 'inbox':
        $controller->getUsersMessages();
        break;

      case 'all':
        $controller->getAllMessages();
        break;
        
      default:
        http_response_code(404);
        echo json_encode(['error' => 'Route not found']);
        break;
    }
    break;
      
  case 'POST':
    switch ($action) {
      case 'send':
        $controller->sendMessage();
        break;

      default:
        http_response_code(404);
        echo json_encode(['error' => 'Route not found']);
        break;
    }
  break;

  case 'PUT':
    switch ($action) {
      case 'read':
        $controller->markAsRead($pathParts[4]);
        break;
      
      case 'unread':
        $controller->markAsUnread($pathParts[4]);
        break;

      default:
        http_response_code(404);
        echo json_encode(['error' => 'Route not found']);
        break;
    }
    break;

  case 'DELETE':
    $controller->deleteMessage($pathParts[3]);
    break;

  default:
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    break;
}