<?php
namespace App\Middleware;

use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ServerRequestInterface;

class MaintenanceMiddleware {
  
  public function handle(ServerRequestInterface $request, $next) {
    $dotenv = \Dotenv\Dotenv::createImmutable($_SERVER['DOCUMENT_ROOT']);
    $dotenv->load();

    define('MAINTENANCE', boolval($_ENV['MAINTENANCE']));

    if(!MAINTENANCE) {
      unset($_SESSION['m_token']);

      return $next($request);
    }

    if(!empty($_GET['m_token'])) {
      if($_GET['m_token'] != sha1($_ENV['M_TOKEN'])) {
        unset($_SESSION['m_token']);
      }
    }

    if(!empty($_SESSION['m_token'])) {
      if($_SESSION['m_token'] == sha1($_ENV['M_TOKEN'])) {
        return $next($request);
      }
    }

    if(!empty($_GET['m_token'])) {
      if($_GET['m_token'] == sha1($_ENV['M_TOKEN'])) {
        $_SESSION['m_token'] = $_GET['m_token'];
        return $next($request);
      }
    }

    return new RedirectResponse('/maintenance');
  }
}