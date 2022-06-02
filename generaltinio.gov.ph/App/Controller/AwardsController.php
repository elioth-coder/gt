<?php
namespace App\Controller;

use Laminas\Diactoros\Response\JsonResponse;
use App\Utility\TwigTemplate;
use App\Utility\OpisDatabase;
use App\Utility\FileSystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToDeleteFile;
use App\Validator\AwardsFormValidator;

class AwardsController {

  function index() {
    $db = OpisDatabase::getInstance();
    $award = $this->awardsTable();
    $bid = $db->from('bid')
            ->select()
            ->all();

    $template = TwigTemplate::load('@pages/System/awards.html.twig');    

    return $template->render([
      'current_page' => 'awards',
      'award' => $award,
      'bid' => $bid
    ]);
  }

  private function awardsTable(){
    $db = OpisDatabase::getInstance();
    $award = $db->from('award')
             ->join('bid', function($join){
                $join->on('award.bid_id', 'bid.id');
             })
             ->select(['award.id','bid.id' => 'bid_id', 'bid.title', 'award.details', 'bid.category', 'award.status', 'award.date_closed', 'award.file', ])
             ->all();
    return $award;
  }

  function store() {
    if($errors = AwardsFormValidator::validate(['dataset' => $_POST, 'files' => $_FILES ])) {
      return new JsonResponse(['status' => 'error', 'errors' => $errors]);
    }

    if($_FILES["file"]["tmp_name"]) {
      $fs = FileSystem::getInstance();
      $extension = explode(".", $_FILES["file"]["name"]);
      $fileName = "File-Awards-" . microtime(true) . "-" . rand(10000, 99999).".".end($extension);
      $stream = fopen($_FILES["file"]['tmp_name'], 'r+');
      $fs->writeStream('/documents/Awards/'.$fileName,$stream);

      if (is_resource($stream)) {
        fclose($stream);
      }
    }
    
    $user = unserialize($_SESSION['user']);
    $db = OpisDatabase::getInstance();
    $success = $db->insert([
      'date_closed' => $_POST['date_closed'],
      'bid_id'      => $_POST['bids_id'],
      'details'     => $_POST['details'],
      'status'      => $_POST['status'],
      'user_id'     => $user->id,
      'file'        => $fileName
    ])->into('award');

    $result = $db->update('bid')
      ->where('id')->is($_POST['bids_id'])
      ->set([
        'status' => 'Awarded'
      ]);

    $response = ($success && $result) ? ['status' => 'success'] : ['status' => 'error'];

    return new JsonResponse($response);
  }

  function destroy($id) {

    $db = OpisDatabase::getInstance();
    $file = $db->from('award')
      ->where('id')->is($id)
      ->column('file');
    
    if($file) {
      try {
        $fs = FileSystem::getInstance();
        $fs->delete('/documents/Awards/' . $file);
      } catch (FilesystemException | UnableToDeleteFile $e) { } 
    }

    $result = $this->updateHelper(['award' => $id, 'bid' => '']);
    
    $result = $db->from('award')
      ->where('id')->is($id)
      ->delete();

    $response = ($result) 
      ? ['status' => 'success', 'file' => $file, 'message' => 'Successfully deleted the Award.'] 
      : ['status' => 'error', 'message' => 'Failed to delete the Award.'];

    return new JsonResponse($response);
  }

  function update($id) {
    if($errors = AwardsFormValidator::validate(['dataset' => $_POST, 'files' => $_FILES, $id])) {
      return new JsonResponse(['status' => 'error', 'errors' => $errors]);
    }

    $fileName = $_POST['old_file'];

    if($_FILES["file"]["tmp_name"]) {
      $extension = explode(".", $_FILES["file"]["name"]);
      $fileName = "File-Awards-" . microtime(true) . "-" . rand(10000, 99999).".".end($extension);
      $stream = fopen($_FILES["file"]['tmp_name'], 'r+');
      $fs = FileSystem::getInstance();
      $fs->writeStream('/documents/Awards/'.$fileName,$stream); 

      if($_POST['old_file']) {
        try {
          $fs->delete('/documents/Awards/' . $_POST['old_file']);
        } catch (FilesystemException | UnableToDeleteFile $e) { } 
      }
      if (is_resource($stream)) {
        fclose($stream);
      }
    }

    $result1 = $this->updateHelper(['award' => $id, 'bid' => $_POST['bids_id']]);

    $user = unserialize($_SESSION['user']);
    $db = OpisDatabase::getInstance();
    $result2 = $db->update('award')
      ->where('id')->is($id)
      ->set([
        'date_closed' => $_POST['date_closed'],
        'bid_id'      => $_POST['bids_id'],
        'details'     => $_POST['details'],
        'status'      => $_POST['status'],
        'user_id'     => $user->id,
        'file'        => $fileName
      ]);

    $response = ($result1 && $result2) 
      ? ['status' => 'success', 'message' => 'Successfully updated the Award.'] 
      : ['status' => 'error', 'message' => 'Failed to update the Award.'];

    return new JsonResponse($response);
  }

  private function updateHelper($IDs = ['award' => '', 'bid' => ''])
  {
    $award_list = $this->awardsTable();
    $result = false;
    foreach ($award_list as $key => $value) {
      if($IDs['award'] == $value->id){
        $result = $this->updater(['before' => $value->bid_id, 'after' => $IDs['bid']]);
        break;
      }
    }
    return $result;
  }

  private function updater($IDs = ['before' => '', 'after' => ''])
  {
    $db = OpisDatabase::getInstance();
    $result = true;
    if ($IDs['before'] != '') {
      $result = $db->update('bid')
        ->where('id')->is($IDs['before'])
        ->set([
          'status' => 'Unpublish'
      ]);
    }
    if ($IDs['after'] != '') {
      $result = $db->update('bid')
        ->where('id')->is($IDs['after'])
        ->set([
          'status' => 'Awarded'
      ]);
    }
    return $result;
  }
}