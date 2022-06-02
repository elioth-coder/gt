<?php
namespace App\Controller;

use Laminas\Diactoros\Response\JsonResponse;
use App\Utility\TwigTemplate;
use App\Utility\OpisDatabase;

class PageSectionsController {
  function index() {
    $db = OpisDatabase::getInstance();
    $result = $db->from('section')
      ->orderBy('page', 'asc')
      ->orderBy('sequence', 'asc')
      ->select()
      ->all();

    $template = TwigTemplate::load('@pages/System/page_sections.html.twig');    

    return $template->render([
      'current_page'   => 'page_sections', 
      'page_sections' => $result
    ]);
  }

  function store() {
    $db = OpisDatabase::getInstance();
    $result = $db->insert([
      'title'    => $_POST['title'],
      'content'  => $_POST['content'],
      'sequence' => $_POST['sequence'],
      'page'     => $_POST['page'],
    ])
    ->into('section');

    $response = ($result) ? ['status' => 'success'] : ['status' => 'error'];

    return new JsonResponse($response);
  }

  function destroy($id) {
    $db = OpisDatabase::getInstance();
    $result = $db->from('section')
      ->where('id')->is($id)
      ->delete();

    $response = ($result) 
      ? ['status' => 'success', 'message' => 'Successfully deleted the page section.'] 
      : ['status' => 'error', 'message' => 'Failed to delete the page section.'];

    return new JsonResponse($response);
  }

  function update($id) {
    $db = OpisDatabase::getInstance();
    $result = $db->update('section')
      ->where('id')->is($id)
      ->set([
        'title'    => $_POST['title'],
        'content'  => $_POST['content'],
        'sequence' => $_POST['sequence'],        
        'page'     => $_POST['page'],        
      ]);

    $response = ($result) 
      ? ['status' => 'success', 'message' => 'Successfully updated the page section.'] 
      : ['status' => 'error', 'message' => 'Failed to update the page section.'];

    return new JsonResponse($response);
  }
}