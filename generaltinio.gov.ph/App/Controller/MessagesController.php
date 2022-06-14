<?php
namespace App\Controller;

use App\Utility\DataFetcher;
use Laminas\Diactoros\Response\JsonResponse;
use App\Utility\TwigTemplate;
use App\Utility\OpisDatabase;
use App\Utility\AccessConfiguration;
use App\Validator\MessagesFormValidator;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MessagesController {
  function index() {
    $email_accounts = DataFetcher::getEmailAccounts();
    $db = OpisDatabase::getInstance();
    $result = $db->from('message')
      ->select()
      ->all();

    $template = TwigTemplate::load('@pages/System/messages.html.twig');    

    $user = unserialize($_SESSION['user']);

    return $template->render([
      'user'           => $user,
      'defaults'       => AccessConfiguration::getDefaultFeatures(),
      'assigneds'      => AccessConfiguration::extractAssignedFeatures($user),
      'current_page'   => 'messages', 
      'messages'       => $result,
      'email_accounts' => $email_accounts,
    ]);
  }

  function store() {
    if($errors = MessagesFormValidator::validate(['dataset' => $_POST, 'files' => $_FILES])) {
      return new JsonResponse(['status' => 'error', 'errors' => $errors]);
    }

    $db = OpisDatabase::getInstance();
    $result = $db->insert([
      'sender'      => htmlspecialchars($_POST['sender'], ENT_NOQUOTES),
      'email'       => htmlspecialchars($_POST['email'], ENT_NOQUOTES),
      'message'     => htmlspecialchars($_POST['message'], ENT_NOQUOTES),
    ])
    ->into('message');

    $response = ($result) ? ['status' => 'success'] : ['status' => 'error'];

    return new JsonResponse($response);
  }

  function forward($id) {
    $dotenv = \Dotenv\Dotenv::createImmutable($_SERVER['DOCUMENT_ROOT']);
    $dotenv->load();

    try {
      $mail = new PHPMailer(true);    

      $mail->setFrom($_ENV['MAIL_USERNAME']);
      $mail->AddCC($_POST['sender_email']);
      $mail->addAddress($_POST['email']);

      $mail->SMTPDebug = false;                               
      $mail->isSMTP();            
      $mail->Host = $_ENV['MAIL_HOST'];
      $mail->SMTPAuth = true;                          
      $mail->Username = $_ENV['MAIL_USERNAME'];                 
      $mail->Password = $_ENV['MAIL_PASSWORD'];                           
      // $mail->SMTPSecure = "tls";                           
      // $mail->Port = 587;                                   
      $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'];                         
      $mail->Port = $_ENV['MAIL_PORT'];                                  
      
      $mail->isHTML(true);
      $mail->Subject = "MESSAGE FORWARDED TO " . $_POST['name'] . "<" . $_POST['email'] . ">";
      $mail->Body = 
        "<hr>" . 
        "Sender: " . $_POST['sender'] . "&lt;" . $_POST['sender_email'] . "&gt;<br>" . 
        "Message: " . $_POST['message'] . 
        "<hr><br>" . 
        "FORWARDING THIS MESSAGE TO " . $_POST['name'] . "&lt;" . $_POST['email'] . "&gt;" .
        "<br><br>" . 
        "Remarks: " . $_POST['remarks'];
      $mail->AltBody = $_POST['sender']. "<br>" . $_POST['message'];
    
      $mail->send();
      $response = ['status' => 'success', 'mesage'=> "Forwarded the message successfully."];
    } catch (Exception $e) {
      $response = ['status' => 'error', 'mesage'=> "Error: " . $e->errorMessage()];
    }    

    $db = OpisDatabase::getInstance();
    $result = $db->update('message')
      ->where('id')->is($id)
      ->set([
        'forwarded_to' => $_POST['forward_to'],        
      ]);

    $response = ($result) 
      ? $response 
      : ['status' => 'error', 'message' => 'Failed to forward the message.'];

    return new JsonResponse($response);    
  }
}