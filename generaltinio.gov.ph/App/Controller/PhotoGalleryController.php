<?php
namespace App\Controller;

use Laminas\Diactoros\Response\JsonResponse;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToCreateDirectory;
use Intervention\Image\ImageManagerStatic as Image;
use App\Utility\FileTypeDetector;
use App\Utility\BytesFormatter;
use App\Utility\TwigTemplate;
use App\Utility\FileSystem;
use App\Utility\AccessConfiguration;
use App\Utility\PhotoGalleryHelper;

class PhotoGalleryController {
  function index() {
    $template = TwigTemplate::load('@pages/System/photo_gallery.html.twig');  
    $directories = PhotoGalleryHelper::getDirectories();

    $user = unserialize($_SESSION['user']);

    return $template->render([
      'user'         => $user,
      'defaults'     => AccessConfiguration::getDefaultFeatures(),
      'assigneds'    => AccessConfiguration::extractAssignedFeatures($user),
      'current_page' => 'photo_gallery', 
      'directories'  => $directories,
    ]);
  }

  function store() {
    $path = '/photo_gallery/';
    $fs = FileSystem::getInstance();

    try {
      if(!FileSystem::isDirectoryExists($path)) {
        $fs->createDirectory($path);
      }
    } catch (FilesystemException | UnableToCreateDirectory $e) {
      throw $e;
    }

    try {
      if(!FileSystem::isDirectoryExists($path . $_POST['name'])) {
        $fs->createDirectory($path . $_POST['name']);
        $response = ['status' => 'success', 'message' => 'Successfully created folder ' . $_POST['name'] . '.'];
      } else {
        $response = ['status' => 'error', 'message' => 'Folder already exists.'];
      }
    } catch (FilesystemException | UnableToCreateDirectory $e) {
      $response = ['status' => 'error', 'message' => $e->getMessage()];
    }

    return new JsonResponse($response);
  }

  function destroy($directory) {   
    $directory = urldecode($directory);
    $path = '/photo_gallery/';
    $fs = FileSystem::getInstance();

    try {
      $fs->deleteDirectory($path . $directory);
    } catch (FilesystemException | UnableToDeleteFile $e) {
      $response = ['status' => 'error', 'message' => $e->getMessage()];
    } 
    
    $response = ['status' => 'success', 'message' => 'Successfully deleted the folder ' . $directory . '.'];

    return new JsonResponse($response);
  }

  function update($directory) {
    $path = '/photo_gallery/';
    $directory = urldecode($directory);
    $fs = FileSystem::getInstance();

    try {
      if(!FileSystem::isDirectoryExists($path . $_POST['name'])) {
        FileSystem::renameDirectory($path . $directory, $path . $_POST['name']);
        $response = ['status' => 'success', 'message' => 'Successfully renamed folder to ' . $_POST['name'] . '.'];
      } else {
        $response = ['status' => 'error', 'message' => 'Folder already exists.'];
      }
    } catch (FilesystemException | UnableToCreateDirectory $e) {
      $response = ['status' => 'error', 'message' => $e->getMessage()];
    }

    return new JsonResponse($response);
  }

  function upload() {
    $path = '/photo_gallery/' . $_POST['directory'] . "/";

    try {
      $fs = FileSystem::getInstance();
      if(!FileSystem::isDirectoryExists($path)) {
        $fs->createDirectory($path);
      }
    } catch (FilesystemException | UnableToCreateDirectory $e) {
      return new JsonResponse(['status' => 'error', 'message' => $e->getMessage()]);
    }

    $imageName = "img-" . microtime(true) . "-" . rand(10000, 99999) . '.png'; 
    $image = Image::make($_POST["image"]);
    $image->save(FileSystem::getBasePath() . $path . $imageName);

    $response = [
      'status' => 'success', 
      'message' => 'Successfully uploaded the photo.',
      'image' => ['name' => $imageName, 'directory' => $_POST['directory']],
    ];

    return new JsonResponse($response);
  }

  function fetch($directory) {
    $directory = urldecode($directory);
    $images = [];

    try {
      $fs = FileSystem::getInstance();
      $listing = $fs->listContents('photo_gallery/' . $directory . '/');

      foreach ($listing as $item) {
        $imageReader = Image::make(FileSystem::getBasePath() . "/" . $item['path']);
        $image = [
          'name'      => array_reverse(explode('/', $item['path']))[0],
          'size'      => BytesFormatter::format($item['file_size']),
          'width'     => $imageReader->width(),
          'height'    => $imageReader->height(),
          'type'      => strtoupper(FileTypeDetector::detect($item['path'])),
          'directory' => $directory,
        ];

        $images[] = $image;
      }
    } catch (FilesystemException $e) {
      return new JsonResponse([
        'status' => 'error',
        'message' => $e->getMessage()
      ]);      
    }

    return new JsonResponse([
      'status' => 'success',
      'images' => $images
    ]);
  }

  function delete() {   
    try {
      $fs = FileSystem::getInstance();
      $path = 'photo_gallery/' . $_POST['directory'] . '/' . $_POST['image'];

      $fs->delete($path);
    } catch (FilesystemException | UnableToDeleteFile $e) {
      $response = ['status' => 'error', 'message' => $e->getMessage()];
    } 
    
    $response = ['status' => 'success', 'message' => 'Successfully deleted the image ' . $_POST['image'] . '.'];

    return new JsonResponse($response);
  }

}