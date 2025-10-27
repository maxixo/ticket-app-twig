<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

session_start();

// Create Request object
$request = Request::createFromGlobals();
$path = $request->getPathInfo();

// Initialize Twig
$loader = new FilesystemLoader(__DIR__ . '/../templates');
$twig = new Environment($loader);

// Route handling
switch ($path) {
    // Landing Page
    case '/':
        $html = $twig->render('landing.twig');
        break;

    // Login
    case '/auth/login':
        if ($request->getMethod() === 'POST') {
            $email = $request->request->get('email', '');
            $password = $request->request->get('password', '');

            if (!empty($email) && !empty($password)) {
                // Simulate successful login
                $_SESSION['user'] = $email;
                $response = new Response('', 302, ['Location' => '/dashboard']);
                $response->send();
                exit;
            } else {
                $html = $twig->render('login.twig', ['error' => 'Invalid credentials']);
                break;
            }
        } else {
            $html = $twig->render('login.twig');
            break;
        }

    // Signup
    case '/auth/signup':
        $html = $twig->render('signup.twig');
        break;

    // Dashboard
    case '/dashboard':
        if (empty($_SESSION['user'])) {
            $response = new Response('', 302, ['Location' => '/auth/login']);
            $response->send();
            exit;
        }
        $html = $twig->render('dashboard.twig', ['user' => $_SESSION['user']]);
        break;

    // Tickets
    case '/ticketmanagement':
        $html = $twig->render('ticketmanagement.twig');
        break;

    // Logout
    case '/auth/logout':
        session_destroy();
        $response = new Response('', 302, ['Location' => '/']);
        $response->send();
        exit;

    // 404 - Not Found
    default:
        http_response_code(404);
        $html = $twig->render('404.twig');
        break;
}

// Send final response
$response = new Response($html);
$response->send();
