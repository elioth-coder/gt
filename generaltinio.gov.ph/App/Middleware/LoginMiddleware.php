<?php
namespace App\Middleware;

use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ServerRequestInterface;

class LoginMiddleware {
  
  public function handle(ServerRequestInterface $request, $next) {

    if(!empty($_SESSION['user'])) {
      return $next($request);
    }

    return new RedirectResponse('/');
  }
}