<?php
namespace App\Controller;

use Laminas\Diactoros\Response\JsonResponse;
use App\Utility\TwigTemplate;
use App\Utility\OpisDatabase;
use App\Utility\DataFetcher;
use App\Utility\AccessConfiguration;

class EmailAccountsController {
  function index() {
    $departments = DataFetcher::getDepartments();
    $db = OpisDatabase::getInstance();
    $result = $db->from('email_account')
      ->select()
      ->all();

    $template = TwigTemplate::load('@pages/System/email_accounts.html.twig');    

    $user = unserialize($_SESSION['user']);

    return $template->render([
      'user'           => $user,
      'defaults'       => AccessConfiguration::getDefaultFeatures(),
      'assigneds'      => AccessConfiguration::extractAssignedFeatures($user),
      'current_page'   => 'email_accounts', 
      'email_accounts' => $result,
      'departments'    => $departments,
    ]);
  }

  function store() {
    $db = OpisDatabase::getInstance();

    $result = $db->insert([
      'email' => $_POST['email'] . '@generaltinio.gov.ph',
      'name'  => $_POST['name'],
    ])
    ->into('email_account');

    $response = ($result) ? ['status' => 'success'] : ['status' => 'error'];

    return new JsonResponse($response);
  }

  function destroy($id) {
    $db = OpisDatabase::getInstance();
        
    $result = $db->from('email_account')
      ->where('id')->is($id)
      ->delete();

    $response = ($result) 
      ? ['status' => 'success', 'message' => 'Successfully deleted the email account.'] 
      : ['status' => 'error', 'message' => 'Failed to delete the email account.'];

    return new JsonResponse($response);
  }

  function update($id) {
    $db = OpisDatabase::getInstance();
    $result = $db->update('email_account')
      ->where('id')->is($id)
      ->set([
        'email' => $_POST['email'] . '@generaltinio.gov.ph',
        'name'  => $_POST['name'],
      ]);

    $response = ($result) 
      ? ['status' => 'success', 'message' => 'Successfully updated the email account.'] 
      : ['status' => 'error', 'message' => 'Failed to update the email account.'];

    return new JsonResponse($response);
  }

}