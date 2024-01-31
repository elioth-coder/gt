<?php
namespace App\Utility;

class DataFetcher {

  static function db() {
    return OpisDatabase::getInstance();
  }

  static function getDesignations() {
    $db = OpisDatabase::getInstance();
    $result = $db->from('user')
      ->distinct()
      ->select(['designation'])
      ->all();
 
    return $result;
  }

  static function getUsers() {
    $db = OpisDatabase::getInstance();
    $result = $db->from('user')
      ->select()
      ->all();
 
    return $result;
  }

  static function getUser($config) {
    $db = OpisDatabase::getInstance();
    $result = $db->from('user')
      ->where('id')->is($config['id'])
      ->select()
      ->first();
 
    return $result;
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

  static function getBarangay($config) {
    $result = DataFetcher::db()->from('barangay')
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

  static function getEvents($config=['limit'=>4]) {
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

  static function getBarangays() {
    $result = DataFetcher::db()->from('barangay')
      ->orderBy('sequence', 'asc')
      ->select()
      ->all();
      
    return $result;
  }

  static function getDepartments($config=['limit'=>4]) {
    $result = DataFetcher::db()->from('department')
      ->orderBy('sequence', 'asc')
      ->limit($config['limit'])
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
  
  static function getBids($config=['limit'=>5]) {
    $result = DataFetcher::db()->from('bid')
      ->select()
      ->all();
      
    return $result;
  }
  
  static function getAwards($config=['limit'=>5]) {
    $result = DataFetcher::db()->from('award')
      ->join('bid', function($join){
        $join->on('award.bid_id', 'bid.id');
      })  
      ->select(['*', 'award.file'])         
      ->all();
      
    return $result;
  }
    
  static function getNotices($config=['limit'=>5]) {
    $result = DataFetcher::db()->from('notice_to_proceed')
      ->where('type')->is('Notice')
      ->andWhere('status')->is('Publish')
      ->select()         
      ->all();
      
    return $result;
  }  

  static function getContracts($config=['limit'=>5]) {
    $result = DataFetcher::db()->from('notice_to_proceed')
      ->where('type')->is('Contract')
      ->andWhere('status')->is('Publish')
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
    try {
      $pdo = new \PDO(
        "mysql:host=".
        DatabaseConfiguration::getHostName(). ";dbname=".
        DatabaseConfiguration::getDatabaseName(), 
        DatabaseConfiguration::getUserName(), 
        DatabaseConfiguration::getPassword()
      );
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
        SELECT id, name,  details, logo,  'BARANGAY'     AS type FROM barangay
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
    
    $stmt  = $pdo->prepare($sql);
    $title = "%". $config['q']. "%";
    $stmt->bindParam(':q', $title);
    $stmt->execute();
    $results = array();

    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
      $results[] = $row;
    }

    return $results;
  }

  static function getListResults($config) {
    try {
      $pdo = new \PDO(
        "mysql:host=".
        DatabaseConfiguration::getHostName(). ";dbname=".
        DatabaseConfiguration::getDatabaseName(), 
        DatabaseConfiguration::getUserName(), 
        DatabaseConfiguration::getPassword()
      );
      $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    } catch(\PDOException $e) {
      throw $e;
    }

    $sql = "
      SELECT * FROM (
        SELECT id, title, content, image, 
          'NEWS' AS type, 
          date AS sequence
          FROM headline
        UNION
        SELECT id, name,  details, logo,  
          'DEPARTMENT' AS type,
          (1000000 - sequence) AS sequence
          FROM department
        UNION
        SELECT id, title, details, image, 
          'EVENT' AS type,
          date_time AS sequence
          FROM event
        UNION
        SELECT id, title, content, image, 
          'ARTICLE' AS type,
          date AS sequence
          FROM featured_story
        UNION
        SELECT id, title, content, page,  
          'PAGE SECTION' AS type,
          sequence
          FROM section
        UNION
        SELECT id, name,  details, image, 
          'TOURIST SPOT' AS type,
          id AS sequence
          FROM tourist_spot
      ) AS search_results 
      WHERE type=:type 
      ORDER BY sequence DESC
    ";   

    $stmt = $pdo->prepare($sql);
    $type = $config['type'];
    $stmt->bindParam(':type', $type);
    $stmt->execute();
    $results = array();

    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
      $results[] = $row;
    }

    return $results;
  }

  static function getEmailAccounts() {
    $result = DataFetcher::db()->from('email_account')
      ->select()
      ->all();
      
    return $result;
  }

  static function getVisitorCount() {
    $count = DataFetcher::db()->from('visitor_counter')->sum('counter');

    return $count;
  }

}