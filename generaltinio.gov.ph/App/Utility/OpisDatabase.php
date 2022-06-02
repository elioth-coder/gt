<?php
namespace App\Utility;

use Opis\Database\Database;
use Opis\Database\Connection;

class OpisDatabase {
  private static $instance;

  private static function createInstance() {
    $dotenv = \Dotenv\Dotenv::createImmutable($_SERVER['DOCUMENT_ROOT']);
    $dotenv->load();

    $connection = new Connection(
      'mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_NAME'],
      $_ENV['DB_USER'],
      $_ENV['DB_PASS']
    );
  
    OpisDatabase::$instance = new Database($connection);
  }

  public static function getInstance() {
    if(!OpisDatabase::$instance) {
      OpisDatabase::createInstance();
    }

    return OpisDatabase::$instance;
  }

}