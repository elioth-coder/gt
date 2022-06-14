<?php
namespace App\Utility;
use App\Model\User;


$dotenv = \Dotenv\Dotenv::createImmutable($_SERVER['DOCUMENT_ROOT']);
$dotenv->load();

class DatabaseConfiguration {
  public static function getHostName() {
    return $_ENV['DB_HOST'];
  }

  public static function getUserName() {
    return $_ENV['DB_USER'];
  }

  public static function getPassword() {
    return $_ENV['DB_PASS'];
  }

  public static function getDatabaseName() {
    return $_ENV['DB_NAME'];
  }
}