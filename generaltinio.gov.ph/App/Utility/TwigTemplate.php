<?php
namespace App\Utility;

use Twig\Loader\FilesystemLoader;

class TwigTemplate {
  private static $loader;

  private static function initializeLoader() {
    TwigTemplate::$loader = new FilesystemLoader($_SERVER['DOCUMENT_ROOT'] . '/App/Views');
    TwigTemplate::$loader->addPath($_SERVER['DOCUMENT_ROOT'] . '/App/Views/Components', 'components');
    TwigTemplate::$loader->addPath($_SERVER['DOCUMENT_ROOT'] . '/App/Views/Pages', 'pages');
  }

  public static function load(String $path) {
    if(!TwigTemplate::$loader) {
      TwigTemplate::initializeLoader();
    }

    $twig = new \Twig\Environment(TwigTemplate::$loader, [
        'debug' => true,
        'cache' => false,
        // 'cache' => $_SERVER['DOCUMENT_ROOT'] . '/cache',
    ]); 

    return $twig->load($path);
  }
}