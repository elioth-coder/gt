<?php
namespace App\Controller;

use App\Utility\TwigTemplate;
use App\Utility\AccessConfiguration;

class DashboardController {
  function index() {
    $template = TwigTemplate::load('@pages/System/dashboard.html.twig');    
    $user = unserialize($_SESSION['user']);

    return $template->render([
      'user'          => $user,
      'defaults'      => AccessConfiguration::getDefaultFeatures(),
      'assigneds'     => AccessConfiguration::extractAssignedFeatures($user),
      'current_page'  => 'dashboard', 
    ]);
  }

}