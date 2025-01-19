<?php

use Controllers\AuthController;

$controller = new AuthController();

switch ($method) {
  case 'POST':
    switch ($action) {
      case 'register':
        $controller->register();
        break;

      case 'login':
        $controller->login();
        break;

      case 'logout':
        $controller->logout();
        break;

      default:
        http_response_code(404);
        echo json_encode(['error' => 'Route not found']);
        break;
    }
    break;

  case 'GET':
    switch ($action) {
      case 'current':
        $controller->getCurrentUser();
        break;
      
        case 'test':
        $controller->test();
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
