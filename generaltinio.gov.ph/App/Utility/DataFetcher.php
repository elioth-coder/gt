<?php
namespace App\Utility;

class DataFetcher {

  static function db() {
    return OpisDatabase::getInstance();
  }

  static function getLatestAnnouncement() {
    $result = DataFetcher::db()->from('announcement')
      ->orderBy('id', 'desc')
      ->select()
      ->first();
      
    return $result;
  }

  static function getTouristSpot($config) {
    $result = DataFetcher::db()->from('tourist_spot')
      ->where('id')->is($config['id'])
      ->select()
      ->first();
      
    return $result;
  }

  static function getDepartment($config) {
    $result = DataFetcher::db()->from('department')
      ->where('id')->is($config['id'])
      ->select()
      ->first();
      
    return $result;
  }

  static function getEvent($config) {
    $result = DataFetcher::db()->from('event')
      ->where('id')->is($config['id'])
      ->select()
      ->first();
      
    return $result;
  }

  static function getHeadline($config) {
    $result = DataFetcher::db()->from('headline')
      ->join('user', function($join){
        $join->on('headline.user_id', 'user.id');
      })
      ->where('headline.id')->is($config['id'])
      ->select(['headline.*', 'user.first_name', 'user.last_name'])
      ->first();
      
    return $result;
  }

  static function getFeaturedStory($config) {
    $result = DataFetcher::db()->from('featured_story')
      ->join('user', function($join){
        $join->on('featured_story.user_id', 'user.id');
      })
      ->where('featured_story.id')->is($config['id'])
      ->select(['featured_story.*', 'user.first_name', 'user.last_name'])
      ->first();
      
    return $result;
  }

  static function getHeadlines($config=['limit'=>5]) {
    $result = DataFetcher::db()->from('headline')
      ->orderBy('date', 'desc')
      ->limit(!empty($config['limit']) ? $config['limit'] : 5)
      ->select()
      ->all();
      
    return $result;
  }

  static function getEvents($config=['limit'=>5]) {
    $result = DataFetcher::db()->from('event')
      ->orderBy('date_time', 'asc')
      ->where('date_time')->gte(date("Y-m-d h:i:s"))
      ->limit($config['limit'])
      ->select()
      ->all();   
      
    return $result;
  }

  static function getTouristSpots($config=['limit'=>3]) {
    $result = DataFetcher::db()->from('tourist_spot')
      ->orderBy('id', 'desc')
      ->limit(!empty($config['limit']) ? $config['limit'] : 3)
      ->select()
      ->all();
      
    return $result;
  }

  static function getDepartments($config=['limit'=>5]) {
    $result = DataFetcher::db()->from('department')
      ->orderBy('sequence', 'asc')
      ->limit(!empty($config['limit']) ? $config['limit'] : 5)
      ->select()
      ->all();
      
    return $result;
  }

  static function getFullDisclosures($config=['limit'=>5]) {
    $result = DataFetcher::db()->from('full_disclosure')
      ->where('year')->is($config['year'])
      ->select()
      ->all();
      
    return $result;
  }
  
  static function getBidsAndAwards($config=['limit'=>5]) {
    $result = DataFetcher::db()->from('bid')
      ->select()
      ->all();
      
    return $result;
  }
  
  static function getOrdinancesAndResolutions($config=['limit'=>5]) {
    $result = DataFetcher::db()->from('ordinances_resolutions')
      ->select()
      ->all();
      
    return $result;
  }
  
  static function getPageSections($config) {
    $result = DataFetcher::db()->from('section')
      ->where('page')->is($config['page'])
      ->orderBy('sequence', 'asc')
      ->select()
      ->all();

    return $result;
  }

  static function getFeaturedStories($config) {
    $result = DataFetcher::db()->from('featured_story')
      ->where('category')->is($config['page'])
      ->andWhere('visible')->is('YES')
      ->orderBy('id', 'desc')
      ->limit(!empty($config['limit']) ? $config['limit'] : 5)
      ->select()
      ->all();
      
    return $result;
  }

  static function getSearchResults($config) {
    $dotenv = \Dotenv\Dotenv::createImmutable($_SERVER['DOCUMENT_ROOT']);
    $dotenv->load();

    define("HOST_NAME", $_ENV['DB_HOST']);
    define("USERNAME", $_ENV['DB_USER']);
    define("PASSWORD", $_ENV['DB_PASS']);
    define("DATABASENAME", $_ENV['DB_NAME']);

    try {
      $pdo = new \PDO("mysql:host=".HOST_NAME.";dbname=".DATABASENAME, USERNAME, PASSWORD);
      $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    } catch(\PDOException $e) {
      throw $e;
    }

    $sql = "
      SELECT * FROM (
        SELECT id, title, content, image, 'NEWS'         AS type FROM headline
        UNION
        SELECT id, name,  details, logo,  'DEPARTMENT'   AS type FROM department
        UNION
        SELECT id, title, details, image, 'EVENT'        AS type FROM event
        UNION
        SELECT id, title, content, image, 'ARTICLE'      AS type FROM featured_story
        UNION
        SELECT id, title, content, page,  'PAGE SECTION' AS type FROM section
        UNION
        SELECT id, name,  details, image, 'TOURIST SPOT' AS type FROM tourist_spot
      ) AS search_results 
      WHERE title LIKE :q
    ";     
    
    if(!empty($config['type'])) {
      $sql .= "AND type='" . $config['type'] . "'";
    }

    $title = "%". $config['q']. "%";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':q', $title);
    $stmt->execute();
    $results = array();

    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
      $results[] = $row;
    }

    return $results;
  }

}