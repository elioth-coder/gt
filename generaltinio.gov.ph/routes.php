<?php
use MiladRahimi\PhpRouter\Router;
use MiladRahimi\PhpRouter\Exceptions\RouteNotFoundException;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use App\Controller\WebsiteController;
use App\Controller\UsersController;
use App\Controller\AnnouncementsController;
use App\Controller\HeadlinesController;
use App\Controller\MessagesController;
use App\Controller\EventsController;
use App\Controller\FeaturedStoriesController;
use App\Controller\TouristSpotsController;
use App\Controller\DepartmentsController;
use App\Controller\PageSectionsController;
use App\Controller\BiddingsController;
use App\Controller\AwardsController;
use App\Controller\FullDisclosureController;
use App\Controller\OrdinancesResolutionsController;
use App\Controller\ExecutiveOrdersController;
use App\Controller\FileManagerController;
use App\Middleware\LoginMiddleware;
use App\Middleware\MaintenanceMiddleware;

if(!isset($_SESSION)) session_start();

$router = Router::create();

$router->get('/maintenance', [WebsiteController::class, 'maintenance']);
$router->get('/404', [WebsiteController::class, 'not_found']);
$router->get('/500', [WebsiteController::class, 'internal_error']);

$router->group(['middleware' => [MaintenanceMiddleware::class]], function(Router $router) {
  $router->post('/login', [WebsiteController::class, 'login']);
  $router->post('/logout', [WebsiteController::class, 'logout']);
  $router->get('/', [WebsiteController::class, 'home']);
  $router->get('/home', [WebsiteController::class, 'home']);
  $router->get('/about', [WebsiteController::class, 'about']);
  $router->get('/government', [WebsiteController::class, 'government']);
  $router->get('/tourism', [WebsiteController::class, 'tourism']);
  $router->get('/business', [WebsiteController::class, 'business']);
  $router->get('/faq', [WebsiteController::class, 'faq']);
  $router->get('/search', [WebsiteController::class, 'search']);
  $router->get('/list/{type}', [WebsiteController::class, 'list']);
  $router->get('/view/{type}/{id}', [WebsiteController::class, 'view']);
  $router->get('/department/{id}', [WebsiteController::class, 'department']);
  $router->get('/full_disclosure/{year}', [WebsiteController::class, 'full_disclosure']);

  $router->post('/system/messages', [MessagesController::class, 'store']);
});

$router->group(['middleware' => [LoginMiddleware::class]], function(Router $router) {
  $router->get('/system/users', [UsersController::class, 'index']);
  $router->post('/system/users', [UsersController::class, 'store']);
  $router->delete('/system/users/{id}', [UsersController::class, 'destroy']);
  $router->post('/system/users/{id}/update', [UsersController::class, 'update']);

  $router->get('/system/announcements', [AnnouncementsController::class, 'index']);
  $router->post('/system/announcements', [AnnouncementsController::class, 'store']);
  $router->delete('/system/announcements/{id}', [AnnouncementsController::class, 'destroy']);
  
  $router->get('/system/messages', [MessagesController::class, 'index']);
  $router->post('/system/messages/reply', [MessagesController::class, 'reply']);

  $router->get('/system/headlines', [HeadlinesController::class, 'index']);
  $router->post('/system/headlines', [HeadlinesController::class, 'store']);
  $router->delete('/system/headlines/{id}', [HeadlinesController::class, 'destroy']);
  $router->post('/system/headlines/{id}/update', [HeadlinesController::class, 'update']);

  $router->get('/system/events', [EventsController::class, 'index']);
  $router->post('/system/events', [EventsController::class, 'store']);
  $router->delete('/system/events/{id}', [EventsController::class, 'destroy']);
  $router->post('/system/events/{id}/update', [EventsController::class, 'update']);

  $router->get('/system/featured_stories', [FeaturedStoriesController::class, 'index']);
  $router->post('/system/featured_stories', [FeaturedStoriesController::class, 'store']);
  $router->delete('/system/featured_stories/{id}', [FeaturedStoriesController::class, 'destroy']);
  $router->post('/system/featured_stories/{id}/update', [FeaturedStoriesController::class, 'update']);

  $router->get('/system/tourist_spots', [TouristSpotsController::class, 'index']);
  $router->post('/system/tourist_spots', [TouristSpotsController::class, 'store']);
  $router->delete('/system/tourist_spots/{id}', [TouristSpotsController::class, 'destroy']);
  $router->post('/system/tourist_spots/{id}/update', [TouristSpotsController::class, 'update']);

  $router->get('/system/departments', [DepartmentsController::class, 'index']);
  $router->post('/system/departments', [DepartmentsController::class, 'store']);
  $router->delete('/system/departments/{id}', [DepartmentsController::class, 'destroy']);
  $router->post('/system/departments/{id}/update', [DepartmentsController::class, 'update']);

  $router->get('/system/page_sections', [PageSectionsController::class, 'index']);
  $router->post('/system/page_sections', [PageSectionsController::class, 'store']);
  $router->delete('/system/page_sections/{id}', [PageSectionsController::class, 'destroy']);
  $router->post('/system/page_sections/{id}/update', [PageSectionsController::class, 'update']);

  $router->get('/system/biddings', [BiddingsController::class, 'index']);
  $router->post('/system/biddings', [BiddingsController::class, 'store']);
  $router->delete('/system/biddings/{id}', [BiddingsController::class, 'destroy']);
  $router->post('/system/biddings/{id}/update', [BiddingsController::class, 'update']);

  $router->get('/system/awards', [AwardsController::class, 'index']);
  $router->post('/system/awards', [AwardsController::class, 'store']);
  $router->delete('/system/awards/{id}', [AwardsController::class, 'destroy']);
  $router->post('/system/awards/{id}/update', [AwardsController::class, 'update']);

  $router->get('/system/full_disclosure', [FullDisclosureController::class, 'index']);
  $router->post('/system/full_disclosure', [FullDisclosureController::class, 'store']);
  $router->delete('/system/full_disclosure/{id}', [FullDisclosureController::class, 'destroy']);
  $router->post('/system/full_disclosure/{id}/update', [FullDisclosureController::class, 'update']);

  $router->get('/system/ordinances_resolutions', [OrdinancesResolutionsController::class, 'index']);
  $router->post('/system/ordinances_resolutions', [OrdinancesResolutionsController::class, 'store']);
  $router->delete('/system/ordinances_resolutions/{id}', [OrdinancesResolutionsController::class, 'destroy']);
  $router->post('/system/ordinances_resolutions/{id}/update', [OrdinancesResolutionsController::class, 'update']);

  $router->get('/system/executive_orders', [ExecutiveOrdersController::class, 'index']);
  $router->post('/system/executive_orders', [ExecutiveOrdersController::class, 'store']);
  $router->delete('/system/executive_orders/{id}', [ExecutiveOrdersController::class, 'destroy']);
  $router->post('/system/executive_orders/{id}/update', [ExecutiveOrdersController::class, 'update']);

  $router->get('/system/file_manager', [FileManagerController::class, 'index']);
  $router->post('/system/file_manager', [FileManagerController::class, 'store']);
  $router->delete('/system/file_manager/{file_name}', [FileManagerController::class, 'destroy']);
  $router->post('/system/file_manager/{file_name}/update', [FileManagerController::class, 'update']);
});

try {
  $router->dispatch();
} catch (RouteNotFoundException $e) {
  // It's 404!
  $router->getPublisher()->publish(new RedirectResponse('/404'));
} catch (Throwable $e) {
  // Log and report...
  $router->getPublisher()->publish(new HtmlResponse('Internal error.' . $e->getMessage(), 500));
}