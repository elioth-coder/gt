<?php
namespace App\Controller;

use App\Utility\TwigTemplate;
use App\Utility\PageSectionHelper;
use App\Utility\DataFetcher;
use App\Utility\OpisDatabase;
use App\Model\User;
use App\Utility\PhotoGalleryHelper;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Kaoken\MarkdownIt\MarkdownIt;
use Jawira\CaseConverter\Convert;

class WebsiteController {
  function login() {
    $db = OpisDatabase::getInstance();
    $result = $db->from('user')
      ->where('username')->is($_POST['username'])
      ->andWhere('password')->is(sha1($_POST['password']))
      ->select()
      ->first();

    if($result) {
      $user = new User();
      $user->id       = $result->id;
      $user->username = $result->username;
      $user->image    = $result->image;
      $user->type     = $result->type;
      $access = json_decode($result->access);
      $user->access   = ($access) ? $access->features : [];

      $_SESSION['user'] = serialize($user);
      $response = ['status' => 'success'];
    } else {
      $response = ['status' => 'error'];
    }

    return new JsonResponse($response);
  }

  function logout() {
    unset($_SESSION['user']);

    return new JsonResponse(['status' => 'success']);
  }

  function home() {
    $featured_stories = DataFetcher::getFeaturedStories(['page' => 'HOME']);
    $headlines        = DataFetcher::getHeadlines(['limit'=>3]);
    $announcement     = DataFetcher::getLatestAnnouncement();
    $sections         = DataFetcher::getPageSections(['page'=>'HOME']);
    $page_sections    = PageSectionHelper::extractSectionsFrom($sections);
    $events           = DataFetcher::getEvents();
    $tourist_spots    = DataFetcher::getTouristSpots();
    $departments      = DataFetcher::getDepartments();

    $template = TwigTemplate::load('@pages/Website/index.html.twig');    
    $page_settings = [
      'current_page' => 'Home',
      'color'        => 'green',
      'sublinks'     => [
        ['url' => '#', 'title'=> '. . .']
      ],
    ];

    $article = null;
    $news = null;
    $event = null;

    if($announcement) {
      $md = new MarkdownIt(['html'=> true]);
      $type = $announcement->type;
      $id   = $announcement->data_id;
  
      if($type == "featured_story") {
        $article = DataFetcher::getFeaturedStory(['id'=>$id]);
        $article->content = $md->render($article->content);    
      }
  
      if($type == "headline") {
        $news = DataFetcher::getHeadline(['id'=>$id]);
        $news->content = $md->render($news->content);    
      }
  
      if($type == "event") {
        $event = DataFetcher::getEvent(['id'=>$id]);
        $event->details = $md->render($event->details);    
      }  
    }

    return $template->render([
      'page_settings'    => $page_settings, 
      'page_sections'    => $page_sections,
      'articles'         => $featured_stories,
      'featured_stories' => $featured_stories,
      'headlines'        => $headlines,
      'event'            => $event,
      'news'             => $news,
      'article'          => $article,
      'events'           => $events,
      'tourist_spots'    => $tourist_spots,
      'departments'      => $departments,
      'user'             => (empty($_SESSION['user'])) ? false : unserialize($_SESSION['user']),
    ]);
  }

  function general_info() {
    $featured_stories = DataFetcher::getFeaturedStories(['page'=>'GENERAL INFO']);
    $headlines        = DataFetcher::getHeadlines(['limit'=>3]);
    $sections         = DataFetcher::getPageSections(['page'=>'GENERAL INFO']);
    $sublinks         = PageSectionHelper::extractLinksFrom($sections);
    $page_sections    = PageSectionHelper::extractSectionsFrom($sections);
    $departments      = DataFetcher::getDepartments();
    $template = TwigTemplate::load('@pages/Website/general_info.html.twig');    
  
    $page_settings = [
      'current_page' => 'General Info',
      'color'        => 'orange',
      'sublinks'     => $sublinks,
    ];

    return $template->render([
      'page_settings'    => $page_settings, 
      'page_sections'    => $page_sections,
      'headlines'        => $headlines,
      'articles'         => $featured_stories,
      'featured_stories' => $featured_stories,
      'departments'      => $departments,
      'user'             => (empty($_SESSION['user'])) ? false : unserialize($_SESSION['user']),
    ]);
  }

  function government() {
    $bids                   = DataFetcher::getBids();
    $awards                 = DataFetcher::getAwards();
    $ordinances_resolutions = DataFetcher::getOrdinancesAndResolutions();
    $featured_stories       = DataFetcher::getFeaturedStories(['page'=>'GOVERNMENT']);
    $headlines              = DataFetcher::getHeadlines(['limit'=>3]);
    $sections               = DataFetcher::getPageSections(['page'=>'GOVERNMENT']);
    $sublinks               = PageSectionHelper::extractLinksFrom($sections);
    $page_sections          = PageSectionHelper::extractSectionsFrom($sections);
    $departments            = DataFetcher::getDepartments();

    $template = TwigTemplate::load('@pages/Website/government.html.twig');    
  
    $sublinks[] = ['url' => '#offices', 'title'=> 'Offices']; 
    $sublinks[] = ['url' => '#full_disclosures', 'title'=> 'Full Disclosure']; 
    $sublinks[] = ['url' => '#bids', 'title'=> 'Bids']; 
    $sublinks[] = ['url' => '#awards', 'title'=> 'Awards']; 
    $sublinks[] = ['url' => '#ordinances_resolutions', 'title'=> 'Ordinances & Resolutions']; 
    $page_settings = [
      'current_page' => 'Government',
      'color'        => 'blue',
      'sublinks'     => $sublinks,
    ];

    return $template->render([
      'bids'                   => $bids,
      'awards'                 => $awards,
      'ordinances_resolutions' => $ordinances_resolutions,
      'current_year'           => date('Y'),
      'page_settings'          => $page_settings, 
      'page_sections'          => $page_sections,
      'headlines'              => $headlines,
      'articles'               => $featured_stories,
      'featured_stories'       => $featured_stories,
      'departments'            => $departments,
      'user'                   => (empty($_SESSION['user'])) ? false : unserialize($_SESSION['user']),
    ]);
  }

  function department($id) {
    $department       = DataFetcher::getDepartment(['id'=>$id]);
    $department_head  = DataFetcher::getUser(['id'=>$department->department_head]);
    $featured_stories = DataFetcher::getFeaturedStories(['page'=>'GOVERNMENT']);
    $headlines        = DataFetcher::getHeadlines(['limit'=>3]);
    $departments      = DataFetcher::getDepartments();

    $template = TwigTemplate::load('@pages/Website/department.html.twig');  
      
    $md = new MarkdownIt(['html'=> true]);
    $department->details = $md->render($department->details);

    $sublinks   = array();
    $sublinks[] = ['url' => '/government', 'title'=> 'Go Back']; 
    $sublinks[] = ['url' => '#departments', 'title'=> 'Departments']; 
    $sublinks[] = ['url' => '#articles', 'title'=> 'Articles']; 
    $sublinks[] = ['url' => '#community-news', 'title'=> 'Community News']; 
    $page_settings = [
      'current_page' => 'Government',
      'color'        => 'blue',
      'sublinks'     => $sublinks,
    ];

    return $template->render([
      'page_settings'    => $page_settings, 
      'department'       => $department,
      'department_head'  => $department_head,
      'headlines'        => $headlines,
      'articles'         => $featured_stories,
      'featured_stories' => $featured_stories,
      'departments'      => $departments,
      'user'             => (empty($_SESSION['user'])) ? false : unserialize($_SESSION['user']),
    ]);
  }

  function full_disclosure($year) {
    $disclosures      = DataFetcher::getFullDisclosures(['year'=>$year]);
    $featured_stories = DataFetcher::getFeaturedStories(['page'=>'GOVERNMENT']);
    $headlines        = DataFetcher::getHeadlines(['limit'=>3]);
    $departments      = DataFetcher::getDepartments();

    $template = TwigTemplate::load('@pages/Website/full_disclosure.html.twig');  
      
    $sublinks   = array();
    $sublinks[] = ['url' => '/government', 'title'=> 'Go Back']; 
    $sublinks[] = ['url' => '#full_disclosures', 'title'=> 'Full Disclosure']; 
    $sublinks[] = ['url' => '#articles', 'title'=> 'Articles']; 
    $sublinks[] = ['url' => '#community-news', 'title'=> 'Community News']; 
    $page_settings = [
      'current_page' => 'Government',
      'color'        => 'blue',
      'sublinks'     => $sublinks,
    ];

    $quarters = [];
    for($i=1; $i<=4; $i++) {
      $docs = [];

      foreach($disclosures as $disclosure) {
        if($disclosure->quarter == $i) {
          $docs[] = $disclosure;
        }
      }

      $quarters[] = array('number' => $i, 'documents' => $docs);
    }

    return $template->render([
      'year'             => $year,
      'current_year'     => date('Y'),
      'page_settings'    => $page_settings, 
      'quarters'         => $quarters,
      'headlines'        => $headlines,
      'articles'         => $featured_stories,
      'featured_stories' => $featured_stories,
      'departments'      => $departments,
      'user'             => (empty($_SESSION['user'])) ? false : unserialize($_SESSION['user']),
    ]);
  }

  function tourism() {
    $tourist_spots    = DataFetcher::getTouristSpots(['limit'=>5]);
    $featured_stories = DataFetcher::getFeaturedStories(['page'=>'TOURISM']);
    $headlines        = DataFetcher::getHeadlines(['limit'=>3]);
    $sections         = DataFetcher::getPageSections(['page'=>'TOURISM']);
    $sublinks         = PageSectionHelper::extractLinksFrom($sections);
    $page_sections    = PageSectionHelper::extractSectionsFrom($sections);
    $departments      = DataFetcher::getDepartments();

    $directories = PhotoGalleryHelper::getDirectories(); 
    $template = TwigTemplate::load('@pages/Website/tourism.html.twig');    
    $sublinks[] = ['url' => '#photo_gallery', 'title'=> 'Photo Gallery']; 
    

    $page_settings = [
      'current_page' => 'Tourism',
      'color'        => 'yellow',
      'sublinks'     => $sublinks,
    ];

    return $template->render([
      'page_settings'    => $page_settings, 
      'page_sections'    => $page_sections,
      'tourist_spots'    => $tourist_spots,
      'headlines'        => $headlines,
      'articles'         => $featured_stories,
      'featured_stories' => $featured_stories,
      'departments'      => $departments,
      'directories'      => $directories,
      'user'             => (empty($_SESSION['user'])) ? false : unserialize($_SESSION['user']),
    ]);
  }

  function business() {
    $featured_stories = DataFetcher::getFeaturedStories(['page'=>'BUSINESS']);
    $headlines        = DataFetcher::getHeadlines(['limit'=>3]);
    $sections         = DataFetcher::getPageSections(['page'=>'BUSINESS']);
    $sublinks         = PageSectionHelper::extractLinksFrom($sections);
    $page_sections    = PageSectionHelper::extractSectionsFrom($sections);
    $departments      = DataFetcher::getDepartments();

    $template = TwigTemplate::load('@pages/Website/business.html.twig');    
  
    $page_settings = [
      'current_page' => 'Business',
      'color'        => 'pink',
      'sublinks'     => $sublinks,
    ];

    return $template->render([
      'page_settings'    => $page_settings, 
      'page_sections'    => $page_sections,
      'headlines'        => $headlines,
      'articles'         => $featured_stories,
      'featured_stories' => $featured_stories,
      'departments'      => $departments,
      'user'             => (empty($_SESSION['user'])) ? false : unserialize($_SESSION['user']),
    ]);
  }

  function health() {
    $featured_stories = DataFetcher::getFeaturedStories(['page'=>'HEALTH']);
    $headlines        = DataFetcher::getHeadlines(['limit'=>3]);
    $sections         = DataFetcher::getPageSections(['page'=>'HEALTH']);
    $sublinks         = PageSectionHelper::extractLinksFrom($sections);
    $page_sections    = PageSectionHelper::extractSectionsFrom($sections);
    $departments      = DataFetcher::getDepartments();

    $template = TwigTemplate::load('@pages/Website/health.html.twig');    
  
    $page_settings = [
      'current_page' => 'Health',
      'color'        => 'indigo',
      'sublinks'     => $sublinks,
    ];

    return $template->render([
      'page_settings'    => $page_settings, 
      'page_sections'    => $page_sections,
      'headlines'        => $headlines,
      'articles'         => $featured_stories,
      'featured_stories' => $featured_stories,
      'departments'      => $departments,
      'user'             => (empty($_SESSION['user'])) ? false : unserialize($_SESSION['user']),
    ]);
  }

  function education() {
    $featured_stories = DataFetcher::getFeaturedStories(['page'=>'EDUCATION']);
    $headlines        = DataFetcher::getHeadlines(['limit'=>3]);
    $sections         = DataFetcher::getPageSections(['page'=>'EDUCATION']);
    $sublinks         = PageSectionHelper::extractLinksFrom($sections);
    $page_sections    = PageSectionHelper::extractSectionsFrom($sections);
    $departments      = DataFetcher::getDepartments();

    $template = TwigTemplate::load('@pages/Website/education.html.twig');    
  
    $page_settings = [
      'current_page' => 'Education',
      'color'        => 'purple',
      'sublinks'     => $sublinks,
    ];

    return $template->render([
      'page_settings'    => $page_settings, 
      'page_sections'    => $page_sections,
      'headlines'        => $headlines,
      'articles'         => $featured_stories,
      'featured_stories' => $featured_stories,
      'departments'      => $departments,
      'user'             => (empty($_SESSION['user'])) ? false : unserialize($_SESSION['user']),
    ]);
  }

  function barangays() {
    $featured_stories = DataFetcher::getFeaturedStories(['page'=>'BARANGAYS']);
    $headlines        = DataFetcher::getHeadlines(['limit'=>3]);
    $sections         = DataFetcher::getPageSections(['page'=>'BARANGAYS']);
    $sublinks         = PageSectionHelper::extractLinksFrom($sections);
    $page_sections    = PageSectionHelper::extractSectionsFrom($sections);
    $departments      = DataFetcher::getDepartments();

    $template = TwigTemplate::load('@pages/Website/barangays.html.twig');    
  
    $page_settings = [
      'current_page' => 'Barangays',
      'color'        => 'red',
      'sublinks'     => $sublinks,
    ];

    return $template->render([
      'page_settings'    => $page_settings, 
      'page_sections'    => $page_sections,
      'headlines'        => $headlines,
      'articles'         => $featured_stories,
      'featured_stories' => $featured_stories,
      'departments'      => $departments,
      'user'             => (empty($_SESSION['user'])) ? false : unserialize($_SESSION['user']),
    ]);
  }

  function search() { 
    $results          = DataFetcher::getSearchResults(['q'=> $_GET['q']]);
    $featured_stories = DataFetcher::getFeaturedStories(['page'=>'HOME']);
    $headlines        = DataFetcher::getHeadlines(['limit'=>3]);
    $departments      = DataFetcher::getDepartments();

    $template = TwigTemplate::load('@pages/Website/search.html.twig');    
    
    $sublinks   = array();
    $sublinks[] = ['url' => '#search-results', 'title'=> 'Search Results']; 
    $sublinks[] = ['url' => '#articles', 'title'=> 'Articles']; 
    $sublinks[] = ['url' => '#community-news', 'title'=> 'Community News']; 
    $page_settings = [
      'title'        => 'Search results for: "' . htmlspecialchars($_GET['q']) . '"',
      'current_page' => 'Search',
      'color'        => 'green',
      'sublinks'     => $sublinks,
    ];

    return $template->render([
      'q'                => $_GET['q'],
      'page_settings'    => $page_settings, 
      'results'          => $results,
      'headlines'        => $headlines,
      'articles'         => $featured_stories,
      'featured_stories' => $featured_stories,
      'departments'      => $departments,
      'user'             => (empty($_SESSION['user'])) ? false : unserialize($_SESSION['user']),
    ]);
  }

  function list($type) { 
    $type             = urldecode($type);
    $results          = DataFetcher::getListResults(['type'=> $type ]);
    $featured_stories = DataFetcher::getFeaturedStories(['page'=>'HOME']);
    $headlines        = DataFetcher::getHeadlines(['limit'=>3]);
    $departments      = DataFetcher::getDepartments();

    $template = TwigTemplate::load('@pages/Website/list.html.twig');    
    
    $title = (new Convert('LIST OF ' . $type))->toTitle();
    $sublinks   = array();
    $sublinks[] = ['url' => '#list-results', 'title'=> $title]; 
    $sublinks[] = ['url' => '#articles', 'title'=> 'Articles']; 
    $sublinks[] = ['url' => '#community-news', 'title'=> 'Community News']; 
    $page_settings = [
      'title'        => $title,
      'current_page' => 'List',
      'color'        => 'green',
      'sublinks'     => $sublinks,
    ];

    return $template->render([
      'type'             => $type,
      'page_settings'    => $page_settings, 
      'results'          => $results,
      'headlines'        => $headlines,
      'articles'         => $featured_stories,
      'featured_stories' => $featured_stories,
      'departments'      => $departments,
      'user'             => (empty($_SESSION['user'])) ? false : unserialize($_SESSION['user']),
    ]);
  }

  function view($type, $id) {
    $featured_stories = DataFetcher::getFeaturedStories(['page'=>'HOME']);
    $headlines        = DataFetcher::getHeadlines(['limit'=>3]);
    $departments      = DataFetcher::getDepartments();

    $template = TwigTemplate::load('@pages/Website/view.html.twig');    
    
    $sublinks   = array();
    $sublinks[] = ['url' => '#articles', 'title'=> 'Articles']; 
    $sublinks[] = ['url' => '#community-news', 'title'=> 'Community News']; 
    $md = new MarkdownIt(['html'=> true]);
    $title = "PAPAYA Page";

    $article = null;
    if($type == "article") {
      $article = DataFetcher::getFeaturedStory(['id'=>$id]);
      $article->content = $md->render($article->content);    
      array_unshift($sublinks, ['url' => '#content', 'title'=> $article->title]);
      $title = $article->title;
    }

    $news = null;
    if($type == "news") {
      $news = DataFetcher::getHeadline(['id'=>$id]);
      $news->content = $md->render($news->content);    
      array_unshift($sublinks, ['url' => '#content', 'title'=> $news->title]);
      $title = $news->title;
    }

    $event = null;
    if($type == "event") {
      $event = DataFetcher::getEvent(['id'=>$id]);
      $event->details = $md->render($event->details);    
      array_unshift($sublinks, ['url' => '#content', 'title'=> $event->title]);
      $title = $event->title;
    }

    $tourist_spot = null;
    if($type == "tourist_spot") {
      $tourist_spot = DataFetcher::getTouristSpot(['id'=>$id]);
      $tourist_spot->details = $md->render($tourist_spot->details);    
      array_unshift($sublinks, ['url' => '#content', 'title'=> $tourist_spot->name]);
      $title = $tourist_spot->name;
    }

    $articles = $featured_stories;
    $page_settings = [
      'title'        => $title,
      'current_page' => 'View',
      'color'        => 'green',
      'sublinks'     => $sublinks,
    ];

    return $template->render([
      'page_settings'    => $page_settings, 
      'tourist_spot'     => $tourist_spot,
      'event'            => $event,
      'news'             => $news,
      'headlines'        => $headlines,
      'article'          => $article,
      'articles'         => $articles,
      'featured_stories' => $featured_stories,
      'departments'      => $departments,
      'user'             => (empty($_SESSION['user'])) ? false : unserialize($_SESSION['user']),
    ]);
  }

  function maintenance() {
    $dotenv = \Dotenv\Dotenv::createImmutable($_SERVER['DOCUMENT_ROOT']);
    $dotenv->load();

    define('MAINTENANCE', boolval($_ENV['MAINTENANCE']));

    if(!MAINTENANCE) {
      unset($_SESSION['m_token']);
      
      return new RedirectResponse('/');
    }    

    $template = TwigTemplate::load('@pages/Website/maintenance.html.twig');    
    return $template->render();
  }

  function not_found() {
    $template = TwigTemplate::load('@pages/Website/not_found.html.twig');    
    return $template->render();
  }

  function internal_error() {
    $template = TwigTemplate::load('@pages/Website/internal_error.html.twig');    
    return $template->render();
  }

}