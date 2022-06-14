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

class TouristSpotsController {
  function index() {
    $db = OpisDatabase::getInstance();
    $result = $db->from('tourist_spot')
      ->select()
      ->all();

    $template = TwigTemplate::load('@pages/System/tourist_spots.html.twig');    

    $user = unserialize($_SESSION['user']);

    return $template->render([
      'user'          => $user,
      'defaults'      => AccessConfiguration::getDefaultFeatures(),
      'assigneds'     => AccessConfiguration::extractAssignedFeatures($user),
      'current_page'  => 'tourist_spots', 
      'tourist_spots' => $result
    ]);
  }

  function store() {
    $imageName = "";

    if($_FILES["file"]["tmp_name"]) {
      $imageName = $this->saveImage($_FILES["file"]["tmp_name"]);
    }

    $db = OpisDatabase::getInstance();
    $result = $db->insert([
      'image'   => $imageName,
      'name'    => $_POST['name'],
      'details' => $_POST['details'],
    ])
    ->into('tourist_spot');

    $response = ($result) 
      ? ['status' => 'success', 'message' => 'Successfully saved the tourist spot.'] 
      : ['status' => 'error', 'message' => 'Failed to save the tourist spot.'];

    return new JsonResponse($response);
  }

  function destroy($id) {
    $db = OpisDatabase::getInstance();
    $image = $db->from('tourist_spot')
      ->where('id')->is($id)
      ->column('image');
    
    if($image) {
      try {
        $fs = FileSystem::getInstance();
        $fs->delete('tourist_spots/' . $image);
      } catch (FilesystemException | UnableToDeleteFile $e) { } 
    }
    
    $result = $db->from('tourist_spot')
      ->where('id')->is($id)
      ->delete();

    $response = ($result) 
      ? ['status' => 'success', 'message' => 'Successfully deleted the tourist spot.'] 
      : ['status' => 'error', 'message' => 'Failed to delete the tourist spot.'];

    return new JsonResponse($response);
  }

  function update($id) {
    $imageName = $_POST['old_image'];

    if($_FILES["file"]["tmp_name"]) {
      $imageName = $this->saveImage($_FILES["file"]["tmp_name"]);

      if($_POST['old_image']) {
        try {
          $fs = FileSystem::getInstance();
          $fs->delete('tourist_spots/' . $_POST['old_image']);
        } catch (FilesystemException | UnableToDeleteFile $e) { } 
      }
    }

    $db = OpisDatabase::getInstance();
    $result = $db->update('tourist_spot')
      ->where('id')->is($id)
      ->set([
        'image'   => $imageName,
        'name'    => $_POST['name'],
        'details' => $_POST['details'],
      ]);

    $response = ($result) 
      ? ['status' => 'success', 'message' => 'Successfully updated the tourist spot.'] 
      : ['status' => 'error', 'message' => 'Failed to update the tourist spot.'];

    return new JsonResponse($response);
  }


  function saveImage($tmp) {
    $path = '/tourist_spots/';

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
    $image->save(FileSystem::getBasePath() . $path . $imageName);

    return $imageName;
  }
}