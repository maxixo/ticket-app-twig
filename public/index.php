<?php

require_once '../vendor/autoload.php';

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

$loader = new FilesystemLoader(__DIR__ . '/../templates');
$twig = new Environment($loader);

session_start();

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

switch ($uri) {
  // Landing Page
  case '/':
    echo $twig->render('landing.twig');
    break;

  // Login
  case '/auth/login':
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $email = $_POST['email'] ?? '';
      $password = $_POST['password'] ?? '';

      if (!empty($email) && !empty($password)) {
        // Simulate successful login
        $_SESSION['user'] = $email;
        header('Location: /dashboard');
        exit;
      } else {
        echo $twig->render('login.twig', ['error' => 'Invalid credentials']);
      }
    } else {
      echo $twig->render('login.twig');
    }
    break;

  // Signup
  case '/auth/signup':
    echo $twig->render('signup.twig');
    break;

  // Dashboard
  case '/dashboard':
    if (empty($_SESSION['user'])) {
      header('Location: /auth/login');
      exit;
    }
    echo $twig->render('dashboard.twig', ['user' => $_SESSION['user']]);
    break;

  // Tickets
case '/ticketmanagement':
  echo $twig->render('ticketmanagement.twig');
  break;


  // ✅ Logout Route
  case '/auth/logout':
    session_destroy(); // destroy session
    header('Location: /'); // redirect to landing page
    exit;

  // 404 - Not Found
  default:
    http_response_code(404);
    echo $twig->render('404.twig');
    break;
}
