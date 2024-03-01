<?php
use MiladRahimi\PhpRouter\Router;
use MiladRahimi\PhpRouter\View\View;
use App\MonitoringSystem\Controllers\UserController;
use App\MonitoringSystem\Controllers\TeacherController;
use App\MonitoringSystem\Controllers\ScheduleController;
use App\MonitoringSystem\Controllers\SettingController;
use App\MonitoringSystem\Controllers\AttendanceController;
use App\MonitoringSystem\Middleware\AuthenticatedMiddleware;
use App\MonitoringSystem\Middleware\CorsMiddleware;

$router->setupView(__DIR__ . '/monitoring-system/pages/');

$router->group(['prefix' => '/monitoring/api', 'middleware' => [CorsMiddleware::class]], function (Router $router) {
    $router->post('/login', [UserController::class, 'login']);
    $router->get('/logout', [UserController::class, 'logout']);

    $router->group(['middleware' => [AuthenticatedMiddleware::class]], function (Router $router) {
        $router->get('/me', [UserController::class, 'me']);
        $router->post('/attendance/create', [AttendanceController::class, 'create']);
        $router->get('/attendance/months', [AttendanceController::class, 'months']);
        $router->get('/attendance/monthly_report/{month}', [AttendanceController::class, 'monthly_report']);
        $router->get('/attendance/buildings', [AttendanceController::class, 'buildings']);
        $router->get('/attendance/to_check/{date}', [AttendanceController::class, 'to_check']);
        $router->get('/attendance/checked/{date}', [AttendanceController::class, 'checked']);
        $router->get('/attendance/delete/{date}', [AttendanceController::class, 'delete']);

        $router->post('/user/update', [UserController::class, 'update']);
        $router->post('/user/create', [UserController::class, 'create']);
        $router->get('/user/read', [UserController::class, 'read']);
        $router->get('/user/delete/{id}', [UserController::class, 'delete']);
        $router->get('/user/get/{id}', [UserController::class, 'get']);
        $router->get('/user/count', [UserController::class, 'count']);

        $router->post('/teacher/update', [TeacherController::class, 'update']);
        $router->post('/teacher/create', [TeacherController::class, 'create']);
        $router->get('/teacher/read', [TeacherController::class, 'read']);
        $router->get('/teacher/get/{id}', [TeacherController::class, 'get']);
        $router->get('/teacher/delete/{id}', [TeacherController::class, 'delete']);
        $router->get('/teacher/count', [TeacherController::class, 'count']);

        $router->post('/schedule/update', [ScheduleController::class, 'update']);
        $router->post('/schedule/create', [ScheduleController::class, 'create']);
        $router->get('/schedule/count', [ScheduleController::class, 'count']);
        $router->get('/schedule/read', [ScheduleController::class, 'read']);
        $router->get('/schedule/get/{id}', [ScheduleController::class, 'get']);
        $router->get('/schedule/delete/{id}', [ScheduleController::class, 'delete']);

        $router->post('/setting/update', [SettingController::class, 'update']);
        $router->post('/setting/create', [SettingController::class, 'create']);
        $router->get('/setting/count', [SettingController::class, 'count']);
        $router->get('/setting/read', [SettingController::class, 'read']);
        $router->get('/setting/get/{id}', [SettingController::class, 'get']);
        $router->get('/setting/delete/{id}', [SettingController::class, 'delete']);
    });
});

$router->get('/monitoring', function (View $view) {
    return $view->make('index');
});

$router->get('/monitoring/attendance', function (View $view) {
    return $view->make('attendance');
});
