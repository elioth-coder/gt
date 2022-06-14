<?php
namespace App\Controller;

use Laminas\Diactoros\Response\JsonResponse;
use App\Utility\TwigTemplate;
use App\Utility\OpisDatabase;
use App\Utility\FileSystem;
use App\Utility\AccessConfiguration;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToDeleteFile;
use App\Validator\BiddingsFormValidator;

class BiddingsController {
  function index() {
	  $db = OpisDatabase::getInstance();
    $result = $db->from('bid')
      ->select()
      ->all();

    $template = TwigTemplate::load('@pages/System/biddings.html.twig');    

    $user = unserialize($_SESSION['user']);

    return $template->render([
      'user'         => $user,
      'defaults'     => AccessConfiguration::getDefaultFeatures(),
      'assigneds'    => AccessConfiguration::extractAssignedFeatures($user),
      'current_page' => 'biddings',
      'biddings'     => $result
    ]);
  }

  function store() {
    if($errors = BiddingsFormValidator::validate(['dataset' => $_POST, 'files' => $_FILES])) {
      return new JsonResponse(['status' => 'error', 'errors' => $errors]);
    }

    if($_FILES["file"]["tmp_name"]) {
      $fs = FileSystem::getInstance();
      $extension = explode(".", $_FILES["file"]["name"]);
      $fileName = "File-Biddings-" . microtime(true) . "-" . rand(10000, 99999).".".end($extension);
      $stream = fopen($_FILES["file"]['tmp_name'], 'r+');
      $fs->writeStream('/documents/Biddings/'.$fileName,$stream);

      if (is_resource($stream)) {
        fclose($stream);
      }
    }
    
    $user = unserialize($_SESSION['user']);
    $db = OpisDatabase::getInstance();
    $success = $db->insert([
      'closing_date' => $_POST['closing_date'],
      'title'        => $_POST['title'],
      'details'      => $_POST['details'],
      'category'     => $_POST['category'],
      'status'       => $_POST['status'],
      'user_id'      => $user->id,
      'file'        => $fileName
    ])->into('bid');
    
    $response = ($success) ? ['status' => 'success'] : ['status' => 'error'];

    return new JsonResponse($response);
  }

  function destroy($id) {

    $db = OpisDatabase::getInstance();
    $file = $db->from('bid')
      ->where('id')->is($id)
      ->column('file');
    
    if($file) {
      try {
        $fs = FileSystem::getInstance();
        $fs->delete('/documents/Biddings/' . $file);
      } catch (FilesystemException | UnableToDeleteFile $e) { } 
    }
    
    $result = $db->from('bid')
      ->where('id')->is($id)
      ->delete();

    $response = ($result) 
      ? ['status' => 'success', 'file' => $file, 'message' => 'Successfully deleted the Bidding.'] 
      : ['status' => 'error', 'message' => 'Failed to delete the Bidding.'];

    return new JsonResponse($response);
  }

  function update($id) {
    if($errors = BiddingsFormValidator::validate(['dataset' => $_POST, 'files' => $_FILES, $id])) {
      return new JsonResponse(['status' => 'error', 'errors' => $errors]);
    }

    $fileName = $_POST['old_file'];

    if($_FILES["file"]["tmp_name"]) {
      $extension = explode(".", $_FILES["file"]["name"]);
      $fileName = "File-Biddings-" . microtime(true) . "-" . rand(10000, 99999).".".end($extension);
      $stream = fopen($_FILES["file"]['tmp_name'], 'r+');
      $fs = FileSystem::getInstance();
      $fs->writeStream('/documents/Biddings/'.$fileName,$stream); 

      if($_POST['old_file']) {
        try {
          $fs->delete('/documents/Biddings/' . $_POST['old_file']);
        } catch (FilesystemException | UnableToDeleteFile $e) { } 
      }
      if (is_resource($stream)) {
        fclose($stream);
      }
    }

    $user = unserialize($_SESSION['user']);
    $db = OpisDatabase::getInstance();
    $result = $db->update('bid')
      ->where('id')->is($id)
      ->set([
        'closing_date' => $_POST['closing_date'],
        'title'        => $_POST['title'],
        'details'      => $_POST['details'],
        'category'     => $_POST['category'],
        'status'       => $_POST['status'],
        'user_id'      => $user->id,
        'file'        => $fileName
      ]);

    $response = ($result) 
      ? ['status' => 'success', 'message' => 'Successfully updated the Bidding.'] 
      : ['status' => 'error', 'message' => 'Failed to update the Bidding.'];

    return new JsonResponse($response);
  }
}