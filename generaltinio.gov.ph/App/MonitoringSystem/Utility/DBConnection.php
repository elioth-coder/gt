<?php
namespace App\MonitoringSystem\Utility;
use \PDO;
use \PDOException;

class DBConnection
{
  public static function create()
  {
    $server = "localhost";
    $username = "root";
    $password = "";
    $db = "ms";

    try {
      $conn = new PDO("mysql:host=$server;dbname=$db", $username, $password);
      $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

      return $conn;
    } catch (PDOException $e) {
      throw $e;
    }
  }
}
