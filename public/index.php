<?php
/**
 * IntraBox — Front Controller
 *
 * All HTTP requests are routed through this single entry point.
 */

declare(strict_types=1);

use App\Core\Router;
use App\Core\Session;
use App\Controllers\AuthController;
use App\Controllers\InboxController;
use App\Controllers\ComposeController;
use App\Controllers\GroupController;
use App\Controllers\RulesController;
use App\Controllers\AdminController;

require_once __DIR__ . '/../src/bootstrap.php';

Session::start();

$router = new Router();

// Public
$router->get('/login',                 [AuthController::class, 'showLogin']);
$router->post('/login',                [AuthController::class, 'login']);
$router->post('/logout',               [AuthController::class, 'logout']);

// Authenticated
$router->get('/',                      [InboxController::class, 'index']);
$router->get('/inbox',                 [InboxController::class, 'index']);
$router->get('/sent',                  [InboxController::class, 'sent']);
$router->get('/messages/{id}',         [InboxController::class, 'read']);

$router->get('/compose',               [ComposeController::class, 'form']);
$router->post('/compose',              [ComposeController::class, 'send']);

$router->get('/groups',                [GroupController::class, 'index']);
$router->get('/groups/new',            [GroupController::class, 'createForm']);
$router->post('/groups',               [GroupController::class, 'create']);
$router->get('/groups/{id}',           [GroupController::class, 'edit']);
$router->post('/groups/{id}/members',  [GroupController::class, 'addMember']);
$router->post('/groups/{id}/remove',   [GroupController::class, 'removeMember']);

$router->get('/rules',                 [RulesController::class, 'index']);

// Admin only
$router->get('/admin',                 [AdminController::class, 'dashboard']);
$router->get('/admin/users',           [AdminController::class, 'users']);
$router->post('/admin/users',          [AdminController::class, 'createUser']);
$router->post('/admin/users/{id}/toggle', [AdminController::class, 'toggleUser']);
$router->get('/admin/abuse',           [AdminController::class, 'abuse']);
$router->post('/admin/abuse/{id}/review', [AdminController::class, 'markAbuseReviewed']);
$router->get('/admin/rules',           [RulesController::class, 'adminIndex']);
$router->post('/admin/rules',          [RulesController::class, 'create']);
$router->post('/admin/rules/{id}/delete', [RulesController::class, 'delete']);
$router->get('/api/stats',             [AdminController::class, 'statsJson']);

$router->dispatch();
