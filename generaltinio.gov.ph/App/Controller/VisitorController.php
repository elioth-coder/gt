<?php
namespace App\Controller;

use App\Utility\DataFetcher;
use App\Utility\DatabaseConfiguration;
use Laminas\Diactoros\Response\JsonResponse;

class VisitorController {
  function increment() {
    $sql = "
      UPDATE `visitor_counter` SET `counter` = `counter` + 1 WHERE DATE(`date`) = DATE(NOW());
      INSERT INTO `visitor_counter`(`counter`,`date`) 
        SELECT 1, DATE(NOW())
          WHERE NOT EXISTS (
              SELECT * FROM `visitor_counter` WHERE DATE(`date`) = DATE(NOW())
          );    
    ";

    try {
      $pdo = new \PDO(
        "mysql:host=".
        DatabaseConfiguration::getHostName(). ";dbname=".
        DatabaseConfiguration::getDatabaseName(), 
        DatabaseConfiguration::getUserName(), 
        DatabaseConfiguration::getPassword()
      );
      $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
      $stmt = $pdo->prepare($sql);
      $stmt->execute();
      $response = ['status' => 'success', 'message' => 'Successfully incremented the number of visitors.'];
    } catch(\PDOException $e) {
      throw $e;
      $response = ['status' => 'error', 'message' => $e->getMessage()];

    }

    return new JsonResponse($response);
  }

  function count() {
    $count = DataFetcher::getVisitorCount();

    return new JsonResponse([
      'count' => $count
    ]);
  }
}