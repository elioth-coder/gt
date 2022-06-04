<?php
namespace App\Controller;

use Laminas\Diactoros\Response\JsonResponse;
use App\Utility\TwigTemplate;
use App\Utility\OpisDatabase;
use App\Validator\MessagesFormValidator;

class MessagesController {
  function index() {
    $db = OpisDatabase::getInstance();
    $result = $db->from('message')
      ->select()
      ->all();

    $template = TwigTemplate::load('@pages/System/messages.html.twig');    

    return $template->render([
      'current_page' => 'messages', 
      'messages' => $result
    ]);
  }

  function store() {
    if($errors = MessagesFormValidator::validate(['dataset' => $_POST, 'files' => $_FILES])) {
      return new JsonResponse(['status' => 'error', 'errors' => $errors]);
    }

    $db = OpisDatabase::getInstance();
    $result = $db->insert([
      'name'    => htmlspecialchars($_POST['name'], ENT_NOQUOTES),
      'email'   => htmlspecialchars($_POST['email'], ENT_NOQUOTES),
      'message' => htmlspecialchars($_POST['message'], ENT_NOQUOTES),
    ])
    ->into('message');

    $response = ($result) ? ['status' => 'success'] : ['status' => 'error'];

    return new JsonResponse($response);
  }
}