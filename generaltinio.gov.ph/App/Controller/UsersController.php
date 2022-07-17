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
use App\Utility\DataFetcher;

class UsersController {
  function index() {
    $db = OpisDatabase::getInstance();
    $result = $db->from('user')
      ->select()
      ->all();
    $designations = DataFetcher::getDesignations();

    $template = TwigTemplate::load('@pages/System/users.html.twig');    
    $user = unserialize($_SESSION['user']);

    return $template->render([
      'user'         => $user,
      'designations' => $designations,
      'features'     => AccessConfiguration::getFeatures(),
      'defaults'     => AccessConfiguration::getDefaultFeatures(),
      'assigneds'    => AccessConfiguration::extractAssignedFeatures($user),
      'current_page' => 'users', 
      'users'        => $result,
    ]);
  }

  function store() {
    $imageName = "";

    if($_FILES["file"]["tmp_name"]) {
      $imageName = $this->saveImage($_FILES["file"]["tmp_name"]);
    }

    $user_access = ["features" => []];
    foreach($_POST['feature'] as $feature) {
      $user_access['features'][] = json_decode($feature);
    }

    $db = OpisDatabase::getInstance();
    $result = $db->insert([
      'username'     => $_POST['username'],
      'password'     => sha1($_POST['password']),
      'image'        => $imageName,
      'type'         => $_POST['type'],
      'designation'  => $_POST['designation'],
      'access'       => json_encode($user_access),
      'first_name'   => $_POST['first_name'],
      'middle_name'  => $_POST['middle_name'],
      'last_name'    => $_POST['last_name'],
      'gender'       => $_POST['gender'],
      'birthday'     => $_POST['birthday'],
      'contact_info' => $_POST['contact_info'],
    ])
    ->into('user');

    $response = ($result) ? ['status' => 'success'] : ['status' => 'error'];

    return new JsonResponse($response);
  }

  function destroy($id) {
    $db = OpisDatabase::getInstance();
    $image = $db->from('user')
      ->where('id')->is($id)
      ->column('image');
    
    if($image) {
      try {
        $fs = FileSystem::getInstance();
        $fs->delete('users/' . $image);
      } catch (FilesystemException | UnableToDeleteFile $e) { } 
    }
    
    $result = $db->from('user')
      ->where('id')->is($id)
      ->delete();

    $response = ($result) 
      ? ['status' => 'success', 'image' => $image, 'message' => 'Successfully deleted the user.'] 
      : ['status' => 'error', 'message' => 'Failed to delete the user.'];

    return new JsonResponse($response);
  }

  function update($id) {
    $imageName = $_POST['old_image'];

    if($_FILES["file"]["tmp_name"]) {
      $imageName = $this->saveImage($_FILES["file"]["tmp_name"]);

      if($_POST['old_image']) {
        try {
          $fs = FileSystem::getInstance();
          $fs->delete('users/' . $_POST['old_image']);
        } catch (FilesystemException | UnableToDeleteFile $e) { } 
      }
    }

    $user_access = ["features" => []];
    foreach($_POST['feature'] as $feature) {
      $user_access['features'][] = json_decode($feature);
    }

    $db = OpisDatabase::getInstance();
    $setParemeters = [
      'username'     => $_POST['username'],
      'image'        => $imageName,
      'type'         => $_POST['type'],
      'designation'  => $_POST['designation'],
      'access'       => json_encode($user_access),
      'first_name'   => $_POST['first_name'],
      'middle_name'  => $_POST['middle_name'],
      'last_name'    => $_POST['last_name'],
      'gender'       => $_POST['gender'],
      'birthday'     => $_POST['birthday'],
      'contact_info' => $_POST['contact_info'],
    ];
    if(!empty($_POST['password'])) {
      $setParemeters['password'] = sha1($_POST['password']);
    }

    $result = $db->update('user')
      ->where('id')->is($id)
      ->set($setParemeters);

    $response = ($result) 
      ? ['status' => 'success', 'message' => 'Successfully updated the user.'] 
      : ['status' => 'error', 'message' => 'Failed to update the user.'];

    return new JsonResponse($response);
  }

  function saveImage($tmp) {
    $path = '/users/';

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