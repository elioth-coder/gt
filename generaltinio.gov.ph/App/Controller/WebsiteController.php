<?php
namespace App\Controller;

use App\Utility\TwigTemplate;
use App\Utility\PageSectionHelper;
use App\Utility\DataFetcher;
use App\Utility\OpisDatabase;
use App\Model\User;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Kaoken\MarkdownIt\MarkdownIt;

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
    $departments      = DataFetcher::getDepartments(['limit'=>5]);
    $events           = DataFetcher::getEvents();
    $tourist_spots    = DataFetcher::getTouristSpots();
    $announcement     = DataFetcher::getLatestAnnouncement();
    $sections         = DataFetcher::getPageSections(['page'=>'HOME']);
    $sublinks         = PageSectionHelper::extractLinksFrom($sections);
    $page_sections    = PageSectionHelper::extractSectionsFrom($sections);

    $template = TwigTemplate::load('@pages/Website/index.html.twig');    
    $sublinks[] = ['url' => '#departments',  'title' => 'Departments' ];
    $sublinks[] = ['url' => '#upcoming-events',  'title' => 'Upcoming Events' ];
    $sublinks[] = ['url' => '#explore-the-town', 'title' => 'Explore The Town' ];
    $sublinks[] = ['url' => '#articles',         'title'=> 'Articles']; 
    $sublinks[] = ['url' => '#community-news',   'title'=> 'Community News']; 
    $sublinks[] = ['url' => '#our-location',     'title' => 'Our Location'];
    $page_settings = [
      'current_page' => 'Home',
      'color'        => 'green',
      'sublinks'     => $sublinks,
    ];

    $md = new MarkdownIt(['html'=> true]);
    $type = $announcement->type;
    $id   = $announcement->data_id;

    $article = null;
    if($type == "featured_story") {
      $article = DataFetcher::getFeaturedStory(['id'=>$id]);
      $article->content = $md->render($article->content);    
      array_unshift($sublinks, ['url' => '#content', 'title'=> $article->title]);
      $title = $article->title;
    }

    $news = null;
    if($type == "headline") {
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

    return $template->render([
      'page_settings'    => $page_settings, 
      'page_sections'    => $page_sections,
      'articles'         => $featured_stories,
      'featured_stories' => $featured_stories,
      'headlines'        => $headlines,
      'events'           => $events,
      'departments'      => $departments,
      'tourist_spots'    => $tourist_spots,
      'event'            => $event,
      'news'             => $news,
      'article'          => $article,
      'user'             => (empty($_SESSION['user'])) ? false : unserialize($_SESSION['user']),
    ]);
  }

  function about() {
    $featured_stories = DataFetcher::getFeaturedStories(['page'=>'ABOUT']);
    $headlines        = DataFetcher::getHeadlines(['limit'=>3]);
    $sections         = DataFetcher::getPageSections(['page'=>'ABOUT']);
    $sublinks         = PageSectionHelper::extractLinksFrom($sections);
    $page_sections    = PageSectionHelper::extractSectionsFrom($sections);

    $template = TwigTemplate::load('@pages/Website/about.html.twig');    
  
    $sublinks[] = ['url' => '#articles', 'title'=> 'Articles']; 
    $sublinks[] = ['url' => '#community-news', 'title'=> 'Community News']; 
    $page_settings = [
      'current_page' => 'About',
      'color'        => 'orange',
      'sublinks'     => $sublinks,
    ];

    return $template->render([
      'page_settings'    => $page_settings, 
      'page_sections'    => $page_sections,
      'headlines'        => $headlines,
      'articles'         => $featured_stories,
      'featured_stories' => $featured_stories,
      'user'             => (empty($_SESSION['user'])) ? false : unserialize($_SESSION['user']),
    ]);
  }

  function business() {
    $featured_stories = DataFetcher::getFeaturedStories(['page'=>'BUSINESS']);
    $headlines        = DataFetcher::getHeadlines(['limit'=>3]);
    $sections         = DataFetcher::getPageSections(['page'=>'BUSINESS']);
    $sublinks         = PageSectionHelper::extractLinksFrom($sections);
    $page_sections    = PageSectionHelper::extractSectionsFrom($sections);

    $template = TwigTemplate::load('@pages/Website/business.html.twig');    
  
    $sublinks[] = ['url' => '#articles', 'title'=> 'Articles']; 
    $sublinks[] = ['url' => '#community-news', 'title'=> 'Community News']; 
    $page_settings = [
      'current_page' => 'Business',
      'color'        => 'red',
      'sublinks'     => $sublinks,
    ];

    return $template->render([
      'page_settings'    => $page_settings, 
      'page_sections'    => $page_sections,
      'headlines'        => $headlines,
      'articles'         => $featured_stories,
      'featured_stories' => $featured_stories,
      'user'             => (empty($_SESSION['user'])) ? false : unserialize($_SESSION['user']),
    ]);
  }

  function faq() {
    $featured_stories = DataFetcher::getFeaturedStories(['page'=>'FAQ']);
    $headlines        = DataFetcher::getHeadlines(['limit'=>3]);
    $sections         = DataFetcher::getPageSections(['page'=>'FAQ']);
    $sublinks         = PageSectionHelper::extractLinksFrom($sections);
    $page_sections    = PageSectionHelper::extractSectionsFrom($sections);

    $template = TwigTemplate::load('@pages/Website/faq.html.twig');    
  
    $sublinks[] = ['url' => '#articles', 'title'=> 'Articles']; 
    $sublinks[] = ['url' => '#community-news', 'title'=> 'Community News']; 
    $page_settings = [
      'current_page' => 'FAQ',
      'color'        => 'purple',
      'sublinks'     => $sublinks,
    ];

    return $template->render([
      'page_settings'    => $page_settings, 
      'page_sections'    => $page_sections,
      'headlines'        => $headlines,
      'articles'         => $featured_stories,
      'featured_stories' => $featured_stories,
      'user'             => (empty($_SESSION['user'])) ? false : unserialize($_SESSION['user']),
    ]);
  }

  function search() { 
    $type             = !empty($_GET['type']) ? $_GET['type'] : null; 
    $results          = DataFetcher::getSearchResults(['q'=> $_GET['q'], 'type'=> $type ]);
    $featured_stories = DataFetcher::getFeaturedStories(['page'=>'FAQ']);
    $headlines        = DataFetcher::getHeadlines(['limit'=>3]);

    $template = TwigTemplate::load('@pages/Website/search.html.twig');    
    
    $sublinks   = array();
    $sublinks[] = ['url' => '#search-results', 'title'=> 'Search Results']; 
    $sublinks[] = ['url' => '#articles', 'title'=> 'Articles']; 
    $sublinks[] = ['url' => '#community-news', 'title'=> 'Community News']; 
    $page_settings = [
      'title'        => 'Search results for: "' . $_GET['q'] . '"',
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
      'user'             => (empty($_SESSION['user'])) ? false : unserialize($_SESSION['user']),
    ]);
  }


  function view($type, $id) {
    $featured_stories = DataFetcher::getFeaturedStories(['page'=>'FAQ']);
    $headlines        = DataFetcher::getHeadlines(['limit'=>3]);

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
      'user'             => (empty($_SESSION['user'])) ? false : unserialize($_SESSION['user']),
    ]);
  }

  function department($id) {
    $department       = DataFetcher::getDepartment(['id'=>$id]);
    $departments      = DataFetcher::getDepartments(['limit'=>100]);
    $featured_stories = DataFetcher::getFeaturedStories(['page'=>'GOVERNMENT']);
    $headlines        = DataFetcher::getHeadlines(['limit'=>3]);

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
      'departments'      => $departments,
      'headlines'        => $headlines,
      'articles'         => $featured_stories,
      'featured_stories' => $featured_stories,
      'user'             => (empty($_SESSION['user'])) ? false : unserialize($_SESSION['user']),
    ]);
  }

  function full_disclosure($year) {
    $disclosures      = DataFetcher::getFullDisclosures(['year'=>$year]);
    $featured_stories = DataFetcher::getFeaturedStories(['page'=>'GOVERNMENT']);
    $headlines        = DataFetcher::getHeadlines(['limit'=>3]);

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
      'user'             => (empty($_SESSION['user'])) ? false : unserialize($_SESSION['user']),
    ]);
  }

  function government() {
    $bids                   = DataFetcher::getBidsAndAwards();
    $ordinances_resolutions = DataFetcher::getOrdinancesAndResolutions();
    $departments            = DataFetcher::getDepartments(['limit'=>5]);
    $featured_stories       = DataFetcher::getFeaturedStories(['page'=>'GOVERNMENT']);
    $headlines              = DataFetcher::getHeadlines(['limit'=>3]);
    $sections               = DataFetcher::getPageSections(['page'=>'GOVERNMENT']);
    $sublinks               = PageSectionHelper::extractLinksFrom($sections);
    $page_sections          = PageSectionHelper::extractSectionsFrom($sections);

    $template = TwigTemplate::load('@pages/Website/government.html.twig');    
  
    $sublinks[] = ['url' => '#full_disclosures', 'title'=> 'Full Disclosure']; 
    $sublinks[] = ['url' => '#bids', 'title'=> 'Bids & Awards']; 
    $sublinks[] = ['url' => '#ordinances_resolutions', 'title'=> 'Ordinances & Resolutions']; 
    $sublinks[] = ['url' => '#departments', 'title'=> 'Departments']; 
    $sublinks[] = ['url' => '#articles', 'title'=> 'Articles']; 
    $sublinks[] = ['url' => '#community-news', 'title'=> 'Community News']; 
    $page_settings = [
      'current_page' => 'Government',
      'color'        => 'blue',
      'sublinks'     => $sublinks,
    ];

    return $template->render([
      'bids'                   => $bids,
      'ordinances_resolutions' => $ordinances_resolutions,
      'current_year'           => date('Y'),
      'page_settings'          => $page_settings, 
      'page_sections'          => $page_sections,
      'departments'            => $departments,
      'headlines'              => $headlines,
      'articles'               => $featured_stories,
      'featured_stories'       => $featured_stories,
      'user'                   => (empty($_SESSION['user'])) ? false : unserialize($_SESSION['user']),
    ]);
  }

  function tourism() {
    $tourist_spots    = DataFetcher::getTouristSpots(['limit'=>5]);
    $featured_stories = DataFetcher::getFeaturedStories(['page'=>'TOURISM']);
    $headlines        = DataFetcher::getHeadlines(['limit'=>3]);
    $sections         = DataFetcher::getPageSections(['page'=>'TOURISM']);
    $sublinks         = PageSectionHelper::extractLinksFrom($sections);
    $page_sections    = PageSectionHelper::extractSectionsFrom($sections);
 
    $template = TwigTemplate::load('@pages/Website/tourism.html.twig');    
    
    $sublinks[] = ['url' => '#tourist-spots', 'title'=> 'Tourist Spots']; 
    $sublinks[] = ['url' => '#articles', 'title'=> 'Articles']; 
    $sublinks[] = ['url' => '#community-news', 'title'=> 'Community News']; 
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