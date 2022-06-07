<?php
namespace App\Controller;

use Laminas\Diactoros\Response\JsonResponse;
use App\Utility\TwigTemplate;
use App\Utility\OpisDatabase;
use App\Utility\FileSystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToDeleteFile;
use App\Validator\ExecutiveOrdersFormValidator;

class ExecutiveOrdersController {

  function index() {
    $db = OpisDatabase::getInstance();
    $result = $db->from('executive_order')
            ->select()
            ->all();
    $template = TwigTemplate::load('@pages/System/executive_orders.html.twig');    

    return $template->render([
      'current_page' => 'executive_orders',
      'executive_order' => $result,
    ]);
  }

  function store() {
    if($errors = ExecutiveOrdersFormValidator::validate(['dataset' => $_POST, 'files' => $_FILES])) {
      return new JsonResponse(['status' => 'error', 'errors' => $errors]);
    }
    
    if($_FILES["file"]["tmp_name"]) {
      $fs = FileSystem::getInstance();
      $extension = explode(".", $_FILES["file"]["name"]);
      $fileName = "File-ExecutiveOrder-" . microtime(true) . "-" . rand(10000, 99999).".".end($extension);
      $stream = fopen($_FILES["file"]['tmp_name'], 'r+');
      $fs->writeStream('/documents/ExecutiveOrder/'.$fileName,$stream);

      if (is_resource($stream)) {
        fclose($stream);
      }
    }
    
    $user = unserialize($_SESSION['user']);
    $db = OpisDatabase::getInstance();
    $success = $db->insert([
      'details' => $_POST['details'],
      'date_issued'    => $_POST['date_issued'],
      'status'  => $_POST['status'],
      'user_id' => $user->id,
      'file'    => $fileName
    ])->into('executive_order');

    $response = ($success) ? ['status' => 'success'] : ['status' => 'error'];

    return new JsonResponse($response);
  }

  function destroy($id) {

    $db = OpisDatabase::getInstance();
    $file = $db->from('executive_order')
      ->where('id')->is($id)
      ->column('file');
    
    if($file) {
      try {
        $fs = FileSystem::getInstance();
        $fs->delete('/documents/ExecutiveOrder/' . $file);
      } catch (FilesystemException | UnableToDeleteFile $e) { } 
    }
    
    $result = $db->from('executive_order')
      ->where('id')->is($id)
      ->delete();

    $response = ($result) 
      ? ['status' => 'success', 'file' => $file, 'message' => 'Successfully deleted the Executive Order.'] 
      : ['status' => 'error', 'message' => 'Failed to delete the Executive Order.'];

    return new JsonResponse($response);
  }

  function update($id) {
    if($errors = ExecutiveOrdersFormValidator::validate(['dataset' => $_POST, 'files' => $_FILES, $id])) {
      return new JsonResponse(['status' => 'error', 'errors' => $errors]);
    }

    $fileName = $_POST['old_file'];

    if($_FILES["file"]["tmp_name"]) {
      $fs = FileSystem::getInstance();
      $extension = explode(".", $_FILES["file"]["name"]);
      $fileName = "File-ExecutiveOrder-" . microtime(true) . "-" . rand(10000, 99999).".".end($extension);
      $stream = fopen($_FILES["file"]['tmp_name'], 'r+');
      $fs->writeStream('/documents/ExecutiveOrder/'.$fileName,$stream);

      if($_POST['old_file']) {
        try {
          $fs->delete('/documents/ExecutiveOrder/' . $_POST['old_file']);
        } catch (FilesystemException | UnableToDeleteFile $e) { } 
      }
      if (is_resource($stream)) {
        fclose($stream);
      }
    }

    $user = unserialize($_SESSION['user']);
    $db = OpisDatabase::getInstance();
    $result = $db->update('executive_order')
      ->where('id')->is($id)
      ->set([
        'details' => $_POST['details'],
        'date_issued'    => $_POST['date_issued'],
        'status'  => $_POST['status'],
        'user_id' => $user->id,
        'file'    => $fileName
      ]);

    $response = ($result) 
      ? ['status' => 'success', 'message' => 'Successfully updated the Executive Order.'] 
      : ['status' => 'error', 'message' => 'Failed to update the Executive Order.'];

    return new JsonResponse($response);
  }
}