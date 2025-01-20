<?php

use Controllers\UserController;

$controller = new UserController();

switch ($method) {
  case 'GET':
    switch ($action) {
      case 'current':
        $controller->getUserDetails();
        break;

      case 'search':
        $controller->searchUsers();
        break;

      case 'liked':
        $controller->getLikedBooks();
        break;

      case ':id':
        $controller->getUserById($pathParts[4]);
        break;

      default:
        http_response_code(404);
        echo json_encode(['error' => 'Route not found']);
        break;
    }
    break;

  case 'PUT':
    switch ($action) {
      case 'update':
        $controller->updateUserDetails();
        break;

      case 'like':
        $controller->likeBook();
        break;

      case 'unlike':
        $controller->unlikeBook();
        break;

      case 'preferences':
        $controller->updatePreferences();
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