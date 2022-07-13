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

class FeaturedStoriesController {
  function index() {
    $db = OpisDatabase::getInstance();
    $result = $db->from('featured_story')
      ->select()
      ->all();

    $template = TwigTemplate::load('@pages/System/featured_stories.html.twig');    

    $user = unserialize($_SESSION['user']);

    return $template->render([
      'user'             => $user,
      'defaults'         => AccessConfiguration::getDefaultFeatures(),
      'assigneds'        => AccessConfiguration::extractAssignedFeatures($user),
      'current_page'     => 'featured_stories', 
      'featured_stories' => $result
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
      'category'  => $_POST['category'],
      'title'     => $_POST['title'],
      'image'     => $imageName,
      'subtitle'  => $_POST['subtitle'],
      'date'      => $_POST['date'],
      'visible'   => $_POST['visible'],
      'content'   => $_POST['content'],
      'user_id'   => $user->id,
    ])
    ->into('featured_story');

    $response = ($result) ? ['status' => 'success'] : ['status' => 'error'];

    return new JsonResponse($response);
  }

  function destroy($id) {
    $db = OpisDatabase::getInstance();
    $image = $db->from('featured_story')
      ->where('id')->is($id)
      ->column('image');
    
    if($image) {
      try {
        $fs = FileSystem::getInstance();
        $fs->delete('featured_stories/' . $image);
      } catch (FilesystemException | UnableToDeleteFile $e) { } 
    }
    
    $result = $db->from('featured_story')
      ->where('id')->is($id)
      ->delete();
    
    if($result) {
      $result = $db->from('announcement')
      ->where('data_id')->is($id)
      ->andWhere('type')->is('featured_story')
      ->delete();
    }

    $response = ($result) 
      ? ['status' => 'success', 'image' => $image, 'message' => 'Successfully deleted the featured story.'] 
      : ['status' => 'error', 'message' => 'Failed to delete the featured story.'];

    return new JsonResponse($response);
  }

  function update($id) {
    $imageName = $_POST['old_image'];

    if($_FILES["file"]["tmp_name"]) {
      $imageName = $this->saveImage($_FILES["file"]["tmp_name"]);

      if($_POST['old_image']) {
        try {
          $fs = FileSystem::getInstance();
          $fs->delete('featured_stories/' . $_POST['old_image']);
        } catch (FilesystemException | UnableToDeleteFile $e) { } 
      }
    }

    $db = OpisDatabase::getInstance();
    $result = $db->update('featured_story')
      ->where('id')->is($id)
      ->set([
        'category'  => $_POST['category'],
        'title'     => $_POST['title'],
        'image'     => $imageName,
        'subtitle'  => $_POST['subtitle'],
        'date'      => $_POST['date'],
        'visible'   => $_POST['visible'],
        'content'   => $_POST['content'],
      ]);

    $response = ($result) 
      ? ['status' => 'success', 'message' => 'Successfully updated the featured story.'] 
      : ['status' => 'error', 'message' => 'Failed to update the featured story.'];

    return new JsonResponse($response);
  }

  function saveImage($tmp) {
    $path = '/featured_stories/';

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