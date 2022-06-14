<?php
namespace App\Controller;

use Laminas\Diactoros\Response\JsonResponse;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToDeleteFile;
use App\Utility\TwigTemplate;
use App\Utility\FileSystem;
use App\Utility\FileTypeDetector;
use App\Utility\BytesFormatter;
use App\Utility\AccessConfiguration;

class FileManagerController {
  function index() {
    $template = TwigTemplate::load('@pages/System/file_manager.html.twig');  
    $files = [];
    $user = unserialize($_SESSION['user']);

    try {
      $fs = FileSystem::getInstance();
      $listing = $fs->listContents('files/user_' . $user->id . '/');

      foreach ($listing as $item) {
        $file = [
          'name'   => array_reverse(explode('/', $item['path']))[0],
          'size'   => BytesFormatter::format($item['file_size']),
          'type'   => strtoupper(FileTypeDetector::detect($item['path'])),
          'author' => $user,
        ];

        $files[] = $file;
      }
    } catch (FilesystemException $exception) { }

    $user = unserialize($_SESSION['user']);

    return $template->render([
      'user'         => $user,
      'defaults'     => AccessConfiguration::getDefaultFeatures(),
      'assigneds'    => AccessConfiguration::extractAssignedFeatures($user),
      'current_page' => 'file_manager', 
      'files'        => $files,
    ]);
  }

  function store() {
    if($_FILES['file']['tmp_name']) {
      $file_name = $_POST['file_name'] . '.' . strtolower(array_reverse(explode('.', $_FILES['file']['name']))[0]);
      $fs = FileSystem::getInstance();
      $user = unserialize($_SESSION['user']);
      $path = 'files/user_' . $user->id . '/';

      if($fs->fileExists($path . $file_name)) {
        return new JsonResponse(['status' => 'error', 'message' => 'File already exists!']);
      }

      $stream = fopen($_FILES['file']['tmp_name'], 'r+');
      $fs->writeStream($path . $file_name, $stream);

      if (is_resource($stream)) {
        fclose($stream);
      }

      $response = ['status' => 'success', 'message' => 'Successfully saved the file ' . $_POST['file_name'] . '!'];
    } else {
      $response = ['status' => 'error', 'message' => 'No file uploaded!'];
    }

    return new JsonResponse($response);
  }

  function destroy($file_name) {   
    $file_name = urldecode($file_name);

    try {
      $fs = FileSystem::getInstance();
      $user = unserialize($_SESSION['user']);
      $path = 'files/user_' . $user->id . '/';

      $fs->delete($path . $file_name);
    } catch (FilesystemException | UnableToDeleteFile $e) {
      $response = ['status' => 'error', 'message' => $e->getMessage()];
    } 
    
    $response = ['status' => 'success', 'message' => 'Successfully deleted the file ' . $file_name . '.'];

    return new JsonResponse($response);
  }

  function update($file_name) {
    $file_name = $_POST['file_name'] . '.' . strtolower(array_reverse(explode('.', $_POST['old_name']))[0]);
    $fs = FileSystem::getInstance();
    $user = unserialize($_SESSION['user']);
    $path = 'files/user_' . $user->id . '/';

    if($fs->fileExists($path . $file_name)) {
      return new JsonResponse(['status' => 'error', 'message' => 'File already exists!']);
    }

    try {
      $fs = FileSystem::getInstance();
      $fs->move($path . $_POST['old_name'], $path . $file_name,);
    } catch (FilesystemException | UnableToDeleteFile $e) {
      $response = ['status' => 'error', 'message' => $e->getMessage()];
    } 

    $response = ['status' => 'success', 'message' => 'Successfully renamed the file to ' . $file_name . '!'];

    return new JsonResponse($response);
  }
}