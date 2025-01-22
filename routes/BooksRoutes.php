<?php

use Controllers\BookController;

$controller = new BookController();

switch ($method) {
  case 'GET':
    switch ($action) {
      case 'user':
        $controller->getListedBooks();
        break;

      case 'recommendations':
        $controller->getRecommendations();
        break;

      case 'allbooks':
        $controller->getAllBooks();
        break;

      case 'search':
        $controller->searchBooks();
        break;

      default:
        http_response_code(404);
        echo json_encode(['error' => 'Route not found']);
        break;
    }
    break;

  case 'POST':
    switch ($action) {
      case 'newlisting':
        $controller->createNewListing();
        break;

      default:
        http_response_code(404);
        echo json_encode(['error' => 'Route not found']);
        break;
    }
    break;

  case 'DELETE':
    $bookId = $pathParts[3]; 
    $controller->deleteListing($bookId);
    break;

  default:
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    break;
}