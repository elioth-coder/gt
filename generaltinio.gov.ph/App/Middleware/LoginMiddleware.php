<?php
namespace App\Middleware;

use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ServerRequestInterface;
use App\Utility\AccessConfiguration;
use function _\findIndex;

class LoginMiddleware {
  
  public function handle(ServerRequestInterface $request, $next) {

    if(empty($_SESSION['user'])) {
      return new RedirectResponse('/');
    }

    $user = unserialize($_SESSION['user']);
    $accessible_features = AccessConfiguration::extractAccessibleFeatures($user);

    $index = findIndex($accessible_features, function($feature) { 
      return str_contains($_SERVER['REQUEST_URI'], $feature->url); 
    });

    if($index < 0) {
      return new HtmlResponse('401 Unauthorized access.', 401);
    }

    return $next($request);
  }
}