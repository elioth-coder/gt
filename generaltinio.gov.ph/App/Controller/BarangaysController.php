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

class BarangaysController {
  function index() {
    $db = OpisDatabase::getInstance();
    $result = $db->from('barangay')
      ->orderBy('sequence', 'asc')      
      ->select()
      ->all();

    $template = TwigTemplate::load('@pages/System/barangays.html.twig');    

    $user  = unserialize($_SESSION['user']);

    return $template->render([
      'user'         => $user,
      'defaults'     => AccessConfiguration::getDefaultFeatures(),
      'assigneds'    => AccessConfiguration::extractAssignedFeatures($user),
      'current_page' => 'barangays', 
      'barangays'  => $result
    ]);
  }

  function store() {
    $imageName = "";
    $photoName = "";

    if($_FILES["file"]["tmp_name"]) {
      $imageName = $this->saveImage($_FILES["file"]);
    }

    if($_FILES["photo"]["tmp_name"]) {
      $photoName = $this->saveImage($_FILES["photo"]);
    }

    $db = OpisDatabase::getInstance();
    $result = $db->insert([
      'logo'     => $imageName,
      'name'     => $_POST['name'],
      'details'  => $_POST['details'],
      'sequence' => $_POST['sequence'],
      'chairman' => json_encode([
        'name' => $_POST['chairman'],
        'designation' => 'Brgy. Chairman',
        'photo' => $photoName,
      ])
    ])
    ->into('barangay');

    $response = ($result) ? ['status' => 'success'] : ['status' => 'error'];

    return new JsonResponse($response);
  }

  function destroy($id) {
    $db = OpisDatabase::getInstance();
    $image = $db->from('barangay')
      ->where('id')->is($id)
      ->column('logo');
    
    if($image) {
      try {
        $fs = FileSystem::getInstance();
        $fs->delete('barangays/' . $image);
      } catch (FilesystemException | UnableToDeleteFile $e) { } 
    }
    
    $result = $db->from('barangay')
      ->where('id')->is($id)
      ->delete();

    $response = ($result) 
      ? ['status' => 'success', 'image' => $image, 'message' => 'Successfully deleted the barangay.'] 
      : ['status' => 'error', 'message' => 'Failed to delete the barangay.'];

    return new JsonResponse($response);
  }

  function update($id) {
    $imageName = $_POST['old_image'];
    $photoName = $_POST['old_photo'];

    if($_FILES["file"]["tmp_name"]) {
      $imageName = $this->saveImage($_FILES["file"]);

      if($_POST['old_image']) {
        try {
          $fs = FileSystem::getInstance();
          $fs->delete('barangays/' . $_POST['old_image']);
        } catch (FilesystemException | UnableToDeleteFile $e) { } 
      }
    }

    if($_FILES["photo"]["tmp_name"]) {
      $photoName = $this->saveImage($_FILES["photo"]);

      if($_POST['old_photo']) {
        try {
          $fs = FileSystem::getInstance();
          $fs->delete('barangays/' . $_POST['old_photo']);
        } catch (FilesystemException | UnableToDeleteFile $e) { } 
      }
    }

    $db = OpisDatabase::getInstance();
    $result = $db->update('barangay')
      ->where('id')->is($id)
      ->set([
        'logo'     => $imageName,
        'name'     => $_POST['name'],
        'details'  => $_POST['details'],
        'sequence' => $_POST['sequence'],
        'chairman' => json_encode([
          'name' => $_POST['chairman'],
          'designation' => 'Brgy. Chairman',
          'photo' => $photoName,
        ])
      ]);

    $response = ($result) 
      ? ['status' => 'success', 'message' => 'Successfully updated the barangay.'] 
      : ['status' => 'error', 'message' => 'Failed to update the barangay.'];

    return new JsonResponse($response);
  }

  function saveImage($file) {
    $path = '/barangays/';

    try {
      $fs = FileSystem::getInstance();
      if(!FileSystem::isDirectoryExists($path)) {
        $fs->createDirectory($path);
      }
    } catch (FilesystemException | UnableToCreateDirectory $e) {
      throw $e;
    }

    $imageName = "img-" . microtime(true) . "-" . rand(10000, 99999) . '.png'; 
    $image = Image::make($file["tmp_name"]);
    $image->orientate();
    $image->save(FileSystem::getBasePath() . $path . $imageName);

    return $imageName;
  }
}