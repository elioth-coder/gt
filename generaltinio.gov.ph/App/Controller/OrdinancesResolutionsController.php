<?php
namespace App\Controller;

use Laminas\Diactoros\Response\JsonResponse;
use App\Utility\TwigTemplate;
use App\Utility\OpisDatabase;
use App\Utility\FileSystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToDeleteFile;
use App\Validator\OrdinancesResolutionsValidator;

class OrdinancesResolutionsController {

  function index() {
    $db = OpisDatabase::getInstance();
    $result = $db->from('ordinances_resolutions')
            ->select()
            ->all();

    $template = TwigTemplate::load('@pages/System/ordinances_resolutions.html.twig');    
    
    return $template->render([
      'current_page' => 'ordinances_resolutions',
      'OR' => $result
    ]);
  }

  function store() {
    if($errors = OrdinancesResolutionsValidator::validate(['dataset' => $_POST, 'files' => $_FILES])) {
      return new JsonResponse(['status' => 'error', 'errors' => $errors]);
    }

    if($_FILES["file"]["tmp_name"]) {
      $fs = FileSystem::getInstance();
      $extension = explode(".", $_FILES["file"]["name"]);
      $fileName = "File-OrdiReso-" . microtime(true) . "-" . rand(10000, 99999).".".end($extension);
      $stream = fopen($_FILES["file"]['tmp_name'], 'r+');
      $fs->writeStream('/documents/OrdiReso/'.$fileName,$stream);

      if (is_resource($stream)) {
        fclose($stream);
      }
    }
    
    $user = unserialize($_SESSION['user']);
    $db = OpisDatabase::getInstance();
    $success = $db->insert([
      'category'      => $_POST['category'],
      'ORno'          => $_POST['ORno'],
      'title'         => $_POST['title'],
      'author'        => $_POST['author'],
      'com_incharged' => $_POST['com_incharged'],
      'date_approved' => $_POST['date_approved'],
      'status'        => $_POST['status'],
      'user_id'       => $user->id,
      'file'          => $fileName
    ])->into('ordinances_resolutions');
    
    $response = ($success) ? ['status' => 'success'] : ['status' => 'error'];

    return new JsonResponse($response);
  }

  function destroy($id) {
    $db = OpisDatabase::getInstance();
    $file = $db->from('ordinances_resolutions')
          ->where('id')->is($id)
          ->select(['file' => 'f', 'category' => 'c'])
          ->all();

    if($file) {
      try {
        $fs = FileSystem::getInstance();
        $fs->delete('/documents/OrdiReso/' . $file[0]->f);
      } catch (FilesystemException | UnableToDeleteFile $e) { } 
    }
    
    $result = $db->from('ordinances_resolutions')
      ->where('id')->is($id)
      ->delete();

    $response = ($result) 
      ? ['status' => 'success', 'file' => $file, 'message' => 'Successfully deleted the ' . $file[0]->c . '.'] 
      : ['status' => 'error', 'message' => 'Failed to delete the ' . $file[0]->c . '.'];

    return new JsonResponse($response);
  }

  function update($id) {
    if($errors = OrdinancesResolutionsValidator::validate(['dataset' => $_POST, 'files' => $_FILES, $id])) {
      return new JsonResponse(['status' => 'error', 'errors' => $errors]);
    }

    $fileName = $_POST['old_file'];

    if($_FILES["file"]["tmp_name"]) {
      $extension = explode(".", $_FILES["file"]["name"]);
      $fileName = "File-OrdiReso-" . microtime(true) . "-" . rand(10000, 99999).".".end($extension);
      $stream = fopen($_FILES["file"]['tmp_name'], 'r+');
      $fs = FileSystem::getInstance();
      $fs->writeStream('/documents/OrdiReso/'.$fileName,$stream); 

      if($_POST['old_file']) {
        try {
          $fs->delete('/documents/OrdiReso/' . $_POST['old_file']);
        } catch (FilesystemException | UnableToDeleteFile $e) { } 
      }
      if (is_resource($stream)) {
        fclose($stream);
      }
    }

    $user = unserialize($_SESSION['user']);
    $db = OpisDatabase::getInstance();
    $result = $db->update('ordinances_resolutions')
      ->where('id')->is($id)
      ->set([
        'category'      => $_POST['category'],
        'ORno'          => $_POST['ORno'],
        'title'         => $_POST['title'],
        'author'        => $_POST['author'],
        'com_incharged' => $_POST['com_incharged'],
        'date_approved' => $_POST['date_approved'],
        'status'        => $_POST['status'],
        'user_id'       => $user->id,
        'file'          => $fileName
      ]);

    $response = ($result) 
      ? ['status' => 'success', 'message' => 'Successfully updated the ' . $_POST['category'] . '.'] 
      : ['status' => 'error', 'message' => 'Failed to update the ' . $_POST['category'] . '.'];

    return new JsonResponse($response);
  }
}