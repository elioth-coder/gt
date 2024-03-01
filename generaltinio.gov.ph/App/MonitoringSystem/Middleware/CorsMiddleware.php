<?php
namespace App\MonitoringSystem\Middleware;
use Psr\Http\Message\ServerRequestInterface;

class CorsMiddleware
{
    public function handle(ServerRequestInterface $request, $next)
    {
        if(isset($_SERVER['HTTP_ORIGIN'])) {
            $origin = $_SERVER['HTTP_ORIGIN'];
        } else if(isset($_SERVER['HTTP_REFERER'])) {
            $origin = $_SERVER['HTTP_REFERER'];
        } else if(isset($_SERVER['PHP_SELF'])) {
            $origin = $_SERVER['PHP_SELF'];
        } else {
            $origin = "";
        }

        header("Access-Control-Allow-Origin: {$origin}");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        return $next($request);
    }
}
