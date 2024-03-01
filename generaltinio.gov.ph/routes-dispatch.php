<?php
use MiladRahimi\PhpRouter\Exceptions\RouteNotFoundException;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;

try {
    $router->dispatch();
  } catch (RouteNotFoundException $e) {
    // It's 404!
    $router->getPublisher()->publish(new RedirectResponse('/404'));
  } catch (Throwable $e) {
    // Log and report...
    $router->getPublisher()->publish(new HtmlResponse('Internal error.' . $e->getMessage(), 500));
  }