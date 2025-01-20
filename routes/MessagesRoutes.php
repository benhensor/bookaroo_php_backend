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
      case 'read/:id':
        $controller->markAsRead($pathparts[4]);
        break;
      
      case 'unread/:id':
        $controller->markAsUnread($pathparts[4]);
        break;

      default:
        http_response_code(404);
        echo json_encode(['error' => 'Route not found']);
        break;
    }
    break;

  case 'DELETE':
    switch ($action) {
      case 'delete/:id':
        $controller->deleteMessage($pathparts[4]);
        break;

      default:
        http_response_code(404);
        echo json_encode(['error' => 'Route not found']);
        break;
    }
    break;

  default:
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    break;
}