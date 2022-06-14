<?php
namespace App\Controller;

use Laminas\Diactoros\Response\JsonResponse;
use App\Utility\TwigTemplate;
use App\Utility\OpisDatabase;
use App\Utility\FileSystem;
use App\Utility\AccessConfiguration;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToDeleteFile;
use App\Validator\FullDisclosureValidator;

class FullDisclosureController {

  function index() {
    $db = OpisDatabase::getInstance();
    $result = $db->from('full_disclosure')
            ->select()
            ->all();
    $year = date("Y");
    $template = TwigTemplate::load('@pages/System/full_disclosure.html.twig');    

    $user = unserialize($_SESSION['user']);

    return $template->render([
      'user'            => $user,
      'defaults'        => AccessConfiguration::getDefaultFeatures(),
      'assigneds'       => AccessConfiguration::extractAssignedFeatures($user),
      'current_page'    => 'full_disclosure',
      'full_disclosure' => $result,
      'year'            => $year
    ]);
  }

  function store() {
    if($errors = FullDisclosureValidator::validate(['dataset' => $_POST, 'files' => $_FILES])) {
      return new JsonResponse(['status' => 'error', 'errors' => $errors]);
    }
    
    if($_FILES["file"]["tmp_name"]) {
      $fs = FileSystem::getInstance();
      $extension = explode(".", $_FILES["file"]["name"]);
      $fileName = "File-FullDisclosure-" . microtime(true) . "-" . rand(10000, 99999).".".end($extension);
      $stream = fopen($_FILES["file"]['tmp_name'], 'r+');
      $fs->writeStream('/documents/FullDisclosure/'.$fileName,$stream);

      if (is_resource($stream)) {
        fclose($stream);
      }
    }
    
    $user = unserialize($_SESSION['user']);
    $db = OpisDatabase::getInstance();
    $success = $db->insert([
      'details' => $_POST['details'],
      'year'    => $_POST['year'],
      'quarter' => $_POST['quarter'],
      'status'  => $_POST['status'],
      'user_id' => $user->id,
      'file'    => $fileName
    ])->into('full_disclosure');

    $response = ($success) ? ['status' => 'success'] : ['status' => 'error'];

    return new JsonResponse($response);
  }

  function destroy($id) {

    $db = OpisDatabase::getInstance();
    $file = $db->from('full_disclosure')
      ->where('id')->is($id)
      ->column('file');
    
    if($file) {
      try {
        $fs = FileSystem::getInstance();
        $fs->delete('/documents/FullDisclosure/' . $file);
      } catch (FilesystemException | UnableToDeleteFile $e) { } 
    }
    
    $result = $db->from('full_disclosure')
      ->where('id')->is($id)
      ->delete();

    $response = ($result) 
      ? ['status' => 'success', 'file' => $file, 'message' => 'Successfully deleted the Full Disclosure.'] 
      : ['status' => 'error', 'message' => 'Failed to delete the Full Disclosure.'];

    return new JsonResponse($response);
  }

  function update($id) {
    if($errors = FullDisclosureValidator::validate(['dataset' => $_POST, 'files' => $_FILES, $id])) {
      return new JsonResponse(['status' => 'error', 'errors' => $errors]);
    }

    $fileName = $_POST['old_file'];

    if($_FILES["file"]["tmp_name"]) {
      $extension = explode(".", $_FILES["file"]["name"]);
      $fileName = "File-FullDisclosure-" . microtime(true) . "-" . rand(10000, 99999).".".end($extension);
      $stream = fopen($_FILES["file"]['tmp_name'], 'r+');
      $fs = FileSystem::getInstance();
      $fs->writeStream('/documents/FullDisclosure/'.$fileName,$stream); 

      if($_POST['old_file']) {
        try {
          $fs->delete('/documents/FullDisclosure/' . $_POST['old_file']);
        } catch (FilesystemException | UnableToDeleteFile $e) { } 
      }
      if (is_resource($stream)) {
        fclose($stream);
      }
    }

    $user = unserialize($_SESSION['user']);
    $db = OpisDatabase::getInstance();
    $result = $db->update('full_disclosure')
      ->where('id')->is($id)
      ->set([
        'details' => $_POST['details'],
        'year'    => $_POST['year'],
        'quarter' => $_POST['quarter'],
        'status'  => $_POST['status'],
        'user_id' => $user->id,
        'file'    => $fileName
      ]);

    $response = ($result) 
      ? ['status' => 'success', 'message' => 'Successfully updated the Full Disclosure.'] 
      : ['status' => 'error', 'message' => 'Failed to update the Full Disclosure.'];

    return new JsonResponse($response);
  }
}