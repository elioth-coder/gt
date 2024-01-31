<?php
namespace App\Controller;

use Laminas\Diactoros\Response\JsonResponse;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToDeleteFile;
use App\Utility\TwigTemplate;
use App\Utility\OpisDatabase;
use App\Utility\FileSystem;
use App\Utility\AccessConfiguration;

class NoticeToProceedController {
  function index() {
    $db = OpisDatabase::getInstance();
    $notices = $db->from('notice_to_proceed')
      ->orderBy('type', 'desc')
      ->orderBy('date_issued', 'desc')
      ->select()
      ->all();

    $awarded_bids = $db->from('bid')
      ->orderBy('created_date', 'asc')
      ->where('status')->is('Awarded')
      ->select()
      ->all();

    $template = TwigTemplate::load('@pages/System/notice_to_proceed.html.twig');    

    $user  = unserialize($_SESSION['user']);

    return $template->render([
      'user'         => $user,
      'defaults'     => AccessConfiguration::getDefaultFeatures(),
      'assigneds'    => AccessConfiguration::extractAssignedFeatures($user),
      'current_page' => 'notice_to_proceed', 
      'notices'      => $notices,
      'awarded_bids' => $awarded_bids,
    ]);
  }

  function store() {
    $fileName = "";

    if($_FILES["file"]["tmp_name"]) {
      $fs = FileSystem::getInstance();
      $extension = explode(".", $_FILES["file"]["name"]);
      $fileName = "notice-to-proceed-" . microtime(true) . "-" . rand(10000, 99999).".".end($extension);
      $stream = fopen($_FILES["file"]['tmp_name'], 'r+');
      $fs->writeStream('/documents/notice-to-proceed/'.$fileName,$stream);

      if (is_resource($stream)) {
        fclose($stream);
      }
    }

    $db = OpisDatabase::getInstance();
    $result = $db->insert([
      'date_issued' => $_POST['date_issued'],
      'title'       => $_POST['title'],
      'details'     => $_POST['details'],
      'category'    => $_POST['category'],
      'type'        => $_POST['type'],
      'file'        => $fileName ?? "",
    ])
    ->into('notice_to_proceed');

    $response = ($result) ? ['status' => 'success'] : ['status' => 'error'];

    return new JsonResponse($response);
  }

  function destroy($id) {
    $db = OpisDatabase::getInstance();
    $file = $db->from('notice_to_proceed')
      ->where('id')->is($id)
      ->column('file');
    
    if($file) {
      try {
        $fs = FileSystem::getInstance();
        $fs->delete('documents/notice-to-proceed/' . $file);
      } catch (FilesystemException | UnableToDeleteFile $e) { } 
    }
    
    $result = $db->from('notice_to_proceed')
      ->where('id')->is($id)
      ->delete();

    $response = ($result) 
      ? ['status' => 'success', 'file' => $file, 'message' => 'Successfully deleted the notice.'] 
      : ['status' => 'error', 'message' => 'Failed to delete the notice.'];

    return new JsonResponse($response);
  }

  function update($id) {
    $fileName = $_POST['old_file'];

    if($_FILES["file"]["tmp_name"]) {
      $extension = explode(".", $_FILES["file"]["name"]);
      $fileName = "notice-to-proceed-" . microtime(true) . "-" . rand(10000, 99999).".".end($extension);
      $stream = fopen($_FILES["file"]['tmp_name'], 'r+');
      $fs = FileSystem::getInstance();
      $fs->writeStream('/documents/notice-to-proceed/'.$fileName,$stream); 

      if($_POST['old_file']) {
        try {
          $fs->delete('/documents/notice-to-proceed/' . $_POST['old_file']);
        } catch (FilesystemException | UnableToDeleteFile $e) { } 
      }
      if (is_resource($stream)) {
        fclose($stream);
      }
    }

    $db = OpisDatabase::getInstance();
    $result = $db->update('notice_to_proceed')
      ->where('id')->is($id)
      ->set([
        'date_issued' => $_POST['date_issued'],
        'title'       => $_POST['title'],
        'details'     => $_POST['details'],
        'category'    => $_POST['category'],
        'type'        => $_POST['type'],
        'file'        => $fileName ?? "",
      ]);

    $response = ($result) 
      ? ['status' => 'success', 'message' => 'Successfully updated the notice.'] 
      : ['status' => 'error', 'message' => 'Failed to update the notice.'];

    return new JsonResponse($response);
  }
}