<?php
namespace App\Controller;

use Laminas\Diactoros\Response\JsonResponse;
use App\Utility\TwigTemplate;
use App\Utility\OpisDatabase;
use App\Utility\AccessConfiguration;

class AnnouncementsController {
  function index() {
    $db = OpisDatabase::getInstance();
    $result = $db->from('announcement')
      ->select()
      ->all();

    $template = TwigTemplate::load('@pages/System/announcements.html.twig');    

    $user = unserialize($_SESSION['user']);

    return $template->render([
      'user'          => $user,
      'defaults'      => AccessConfiguration::getDefaultFeatures(),
      'assigneds'     => AccessConfiguration::extractAssignedFeatures($user),
      'current_page'  => 'announcements', 
      'announcements' => $result
    ]);
  }

  function store() {
    $db   = OpisDatabase::getInstance();
    $user = unserialize($_SESSION['user']);

    $result = $db->insert([
      'type'    => $_POST['type'],
      'title'   => $_POST['title'],
      'data_id' => $_POST['data_id'],
      'user_id' => $user->id,
    ])
    ->into('announcement');

    $response = ($result) ? ['status' => 'success'] : ['status' => 'error'];

    return new JsonResponse($response);
  }

  function destroy($id) {
    $db = OpisDatabase::getInstance();
    
    $result = $db->from('announcement')
      ->where('id')->is($id)
      ->delete();

    $response = ($result) 
      ? ['status' => 'success', 'message' => 'Successfully deleted the announcement.'] 
      : ['status' => 'error', 'message' => 'Failed to delete the announcement.'];

    return new JsonResponse($response);
  }
}