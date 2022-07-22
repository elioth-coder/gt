<?php
namespace App\Controller;

use Laminas\Diactoros\Response\JsonResponse;
use Intervention\Image\ImageManagerStatic as Image;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToCreateDirectory;
use App\Utility\TwigTemplate;
use App\Utility\OpisDatabase;
use App\Utility\FileSystem;
use App\Utility\AccessConfiguration;

class EventsController {
  function index() {
    $db = OpisDatabase::getInstance();
    $result = $db->from('event')
      ->select()
      ->all();

    $template = TwigTemplate::load('@pages/System/events.html.twig');    

    $user = unserialize($_SESSION['user']);

    return $template->render([
      'user'         => $user,
      'defaults'     => AccessConfiguration::getDefaultFeatures(),
      'assigneds'    => AccessConfiguration::extractAssignedFeatures($user),
      'current_page' => 'events', 
      'events'       => $result
    ]);
  }

  function store() {
    $imageName = "";

    if($_FILES["file"]["tmp_name"]) {
      $imageName = $this->saveImage($_FILES["file"]["tmp_name"]);
    }

    $db = OpisDatabase::getInstance();
    $user = unserialize($_SESSION['user']);

    $result = $db->insert([
      'image'     => $imageName,
      'title'     => $_POST['title'],
      'details'   => $_POST['details'],
      'venue'     => $_POST['venue'],
      'date_time' => $_POST['date_time'],
      'user_id'   => $user->id,
    ])
    ->into('event');

    $response = ($result) ? ['status' => 'success'] : ['status' => 'error'];

    return new JsonResponse($response);
  }

  function destroy($id) {
    $db = OpisDatabase::getInstance();
    $image = $db->from('event')
      ->where('id')->is($id)
      ->column('image');
    
    if($image) {
      try {
        $fs = FileSystem::getInstance();
        $fs->delete('events/' . $image);
      } catch (FilesystemException | UnableToDeleteFile $e) { } 
    }
    
    $result = $db->from('event')
      ->where('id')->is($id)
      ->delete();

    if($result) {
      $db->from('announcement')
        ->where('data_id')->is($id)
        ->andWhere('type')->is('event')
        ->delete();
    }

    $response = ($result) 
      ? ['status' => 'success', 'image' => $image, 'message' => 'Successfully deleted the event.'] 
      : ['status' => 'error', 'message' => 'Failed to delete the event.'];

    return new JsonResponse($response);
  }

  function update($id) {
    $imageName = $_POST['old_image'];

    if($_FILES["file"]["tmp_name"]) {
      $imageName = $this->saveImage($_FILES["file"]["tmp_name"]);

      if($_POST['old_image']) {
        try {
          $fs = FileSystem::getInstance();
          $fs->delete('events/' . $_POST['old_image']);
        } catch (FilesystemException | UnableToDeleteFile $e) { } 
      }
    }

    $db = OpisDatabase::getInstance();
    $result = $db->update('event')
      ->where('id')->is($id)
      ->set([
        'image'     => $imageName,
        'title'     => $_POST['title'],
        'details'   => $_POST['details'],
        'venue'     => $_POST['venue'],
        'date_time' => $_POST['date_time'],
      ]);

    $response = ($result) 
      ? ['status' => 'success', 'message' => 'Successfully updated the event.'] 
      : ['status' => 'error', 'message' => 'Failed to update the event.'];

    return new JsonResponse($response);
  }

  function saveImage($tmp) {
    $path = '/events/';

    try {
      $fs = FileSystem::getInstance();
      if(!FileSystem::isDirectoryExists($path)) {
        $fs->createDirectory($path);
      }
    } catch (FilesystemException | UnableToCreateDirectory $e) {
      throw $e;
    }

    $imageName = "img-" . microtime(true) . "-" . rand(10000, 99999) . '.png'; 
    $image = Image::make($_FILES["file"]["tmp_name"]);
    $image->orientate();
    $image->save(FileSystem::getBasePath() . $path . $imageName);

    return $imageName;
  }
}