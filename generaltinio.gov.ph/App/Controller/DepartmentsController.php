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

class DepartmentsController {
  function index() {
    $db = OpisDatabase::getInstance();
    $result = $db->from('department')
      ->orderBy('sequence', 'asc')      
      ->select()
      ->all();

    $template = TwigTemplate::load('@pages/System/departments.html.twig');    

    $user  = unserialize($_SESSION['user']);
    $users = DataFetcher::getUsers();

    return $template->render([
      'user'         => $user,
      'users'        => $users,
      'defaults'     => AccessConfiguration::getDefaultFeatures(),
      'assigneds'    => AccessConfiguration::extractAssignedFeatures($user),
      'current_page' => 'departments', 
      'departments'  => $result
    ]);
  }

  function store() {
    $imageName = "";

    if($_FILES["file"]["tmp_name"]) {
      $imageName = $this->saveImage($_FILES["file"]["tmp_name"]);
    }

    $db = OpisDatabase::getInstance();
    $result = $db->insert([
      'logo'     => $imageName,
      'name'     => $_POST['name'],
      'details'  => $_POST['details'],
      'sequence' => $_POST['sequence'],
      'department_head' => $_POST['department_head'],
    ])
    ->into('department');

    $response = ($result) ? ['status' => 'success'] : ['status' => 'error'];

    return new JsonResponse($response);
  }

  function destroy($id) {
    $db = OpisDatabase::getInstance();
    $image = $db->from('department')
      ->where('id')->is($id)
      ->column('logo');
    
    if($image) {
      try {
        $fs = FileSystem::getInstance();
        $fs->delete('departments/' . $image);
      } catch (FilesystemException | UnableToDeleteFile $e) { } 
    }
    
    $result = $db->from('department')
      ->where('id')->is($id)
      ->delete();

    $response = ($result) 
      ? ['status' => 'success', 'image' => $image, 'message' => 'Successfully deleted the department.'] 
      : ['status' => 'error', 'message' => 'Failed to delete the department.'];

    return new JsonResponse($response);
  }

  function update($id) {
    $imageName = $_POST['old_image'];

    if($_FILES["file"]["tmp_name"]) {
      $imageName = $this->saveImage($_FILES["file"]["tmp_name"]);

      if($_POST['old_image']) {
        try {
          $fs = FileSystem::getInstance();
          $fs->delete('departments/' . $_POST['old_image']);
        } catch (FilesystemException | UnableToDeleteFile $e) { } 
      }
    }

    $db = OpisDatabase::getInstance();
    $result = $db->update('department')
      ->where('id')->is($id)
      ->set([
        'logo'     => $imageName,
        'name'     => $_POST['name'],
        'details'  => $_POST['details'],
        'sequence' => $_POST['sequence'],
        'department_head' => $_POST['department_head'],
      ]);

    $response = ($result) 
      ? ['status' => 'success', 'message' => 'Successfully updated the department.'] 
      : ['status' => 'error', 'message' => 'Failed to update the department.'];

    return new JsonResponse($response);
  }

  function saveImage($tmp) {
    $path = '/departments/';

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