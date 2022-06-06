<?php
namespace App\Controller;

use Laminas\Diactoros\Response\JsonResponse;
use App\Utility\TwigTemplate;
use App\Utility\OpisDatabase;
use App\Validator\MessagesFormValidator;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

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

  function reply() {
    try {
      $mail = new PHPMailer(true);    

      $mail->setFrom("information@generaltinio.gov.ph", "General Tinio");
      $mail->addAddress($_POST['receiver'], $_POST['receiver_name']);

      $mail->SMTPDebug = false;                               
      $mail->isSMTP();            
      $mail->Host = "mail.generaltinio.gov.ph";
      $mail->SMTPAuth = true;                          
      $mail->Username = "information@generaltinio.gov.ph";                 
      $mail->Password = "information1234";                           
      $mail->SMTPSecure = "tls";                           
      $mail->Port = 587;                                   
      
      $mail->isHTML(true);
      $mail->Subject = "INFORMATION AT MUNICIPALITY OF GENERAL TINIO";
      $mail->Body = $_POST['question']. "\n\n" . $_POST['reply'];
      $mail->AltBody = $_POST['question']. "\n\n" . $_POST['reply'];
    
      $mail->send();
      $response = ['status' => 'success', 'mesage'=> "Replied to message successfully."];
    } catch (Exception $e) {
      $response = ['status' => 'error', 'mesage'=> "Error: " . $e->errorMessage()];
    }    

    return new JsonResponse($response);    
  }
}