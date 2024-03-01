<?php
namespace App\MonitoringSystem\Middleware;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;

class AuthenticatedMiddleware
{
    public function handle(ServerRequestInterface $request, $next)
    {
        if(empty($_SESSION['user'])) {
            return new JsonResponse([
                'status'  => 'error',
                'message' => 'You are not logged in',
            ]);
        }
        
        return $next($request);
    }
}
