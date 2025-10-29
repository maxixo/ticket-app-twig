<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

session_start();

// ========== TWIG SETUP ==========
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../templates');
$twig = new \Twig\Environment($loader);

// ========== REQUEST ==========
$request = Request::createFromGlobals();
$path = $request->getPathInfo();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// ========== DATA DIRECTORY ==========
$dataDir = __DIR__ . '/../data';
if (!is_dir($dataDir)) mkdir($dataDir, 0777, true);

$ticketsFile = $dataDir . '/tickets.json';
$usersFile = $dataDir . '/users.json';
if (!file_exists($ticketsFile)) file_put_contents($ticketsFile, json_encode([], JSON_PRETTY_PRINT));
if (!file_exists($usersFile)) file_put_contents($usersFile, json_encode([], JSON_PRETTY_PRINT));

// ========== HELPERS ==========
function readJsonFile(string $file): array {
    if (!file_exists($file)) return [];
    $data = file_get_contents($file);
    return json_decode($data, true) ?? [];
}

function saveJsonFile(string $file, array $data): void {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

function findTicketIndexById(array $tickets, string $id): ?int {
    foreach ($tickets as $i => $t) {
        if ((string)$t['id'] === (string)$id) return $i;
    }
    return null;
}

function hasAtLeastWords(string $text, int $n): bool {
    return str_word_count(trim($text)) >= $n;
}

function isLoggedIn(): bool {
    return !empty($_SESSION['user']);
}

function validateEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function sanitizeInput(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// ========== ROUTES ==========

// ✅ LANDING PAGE (Default route)
if ($path === '/' || $path === '' || $path === '/landing') {
    if (isLoggedIn()) {
        header('Location: /dashboard');
        exit;
    }
    echo $twig->render('landing.twig');
    exit;
}

// ✅ SIGNUP (GET/POST)
if ($path === '/auth/signup') {
    if ($method === 'GET') {
        $success = isset($_GET['success']) ? 'Account created successfully! Please login.' : null;
        echo $twig->render('signup.twig', ['success' => $success]);
        exit;
    }

    // POST - Handle signup
    $users = readJsonFile($usersFile);
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $errors = [];
    
    // Name validation
    if (empty($name)) {
        $errors['name'] = 'Name is required.';
    } elseif (strlen($name) < 3) {
        $errors['name'] = 'Name must be at least 3 characters.';
    } elseif (strlen($name) > 50) {
        $errors['name'] = 'Name must not exceed 50 characters.';
    }

    // Email validation
    if (empty($email)) {
        $errors['email'] = 'Email is required.';
    } elseif (!validateEmail($email)) {
        $errors['email'] = 'Please enter a valid email address.';
    } elseif (isset($users[$email])) {
        $errors['email'] = 'This email is already registered.';
    }

    // Password validation
    if (empty($password)) {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Password must be at least 6 characters.';
    } elseif (strlen($password) > 100) {
        $errors['password'] = 'Password is too long.';
    }

    // Confirm password validation
    if (empty($confirm)) {
        $errors['confirm_password'] = 'Please confirm your password.';
    } elseif ($password !== $confirm) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    if (!empty($errors)) {
        echo $twig->render('signup.twig', [
            'errors' => $errors, 
            'old' => ['name' => $name, 'email' => $email]
        ]);
        exit;
    }

    // Save user
    $users[$email] = [
        'name' => $name,
        'email' => $email,
        'password' => password_hash($password, PASSWORD_BCRYPT),
        'created_at' => date('Y-m-d H:i:s'),
    ];

    saveJsonFile($usersFile, $users);
    
    // Redirect to login with success message
    header('Location: /auth/login?registered=1');
    exit;
}

// ✅ LOGIN (GET/POST)
if ($path === '/auth/login') {
    if ($method === 'GET') {
        $success = isset($_GET['registered']) ? 'Registration successful! Please login with your credentials.' : null;
        echo $twig->render('login.twig', ['success' => $success]);
        exit;
    }

    // POST - Handle login
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $users = readJsonFile($usersFile);

    $errors = [];
    
    // Email validation
    if (empty($email)) {
        $errors['email'] = 'Email is required.';
    } elseif (!validateEmail($email)) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    // Password validation
    if (empty($password)) {
        $errors['password'] = 'Password is required.';
    }

    // Check credentials
    if (empty($errors)) {
        if (!isset($users[$email])) {
            $errors['general'] = 'Invalid email or password.';
        } elseif (!password_verify($password, $users[$email]['password'])) {
            $errors['general'] = 'Invalid email or password.';
        }
    }

    if (!empty($errors)) {
        echo $twig->render('login.twig', [
            'errors' => $errors, 
            'old' => ['email' => $email]
        ]);
        exit;
    }

    // Login successful
    $_SESSION['user'] = [
        'email' => $email,
        'name' => $users[$email]['name'],
    ];

    header('Location: /dashboard');
    exit;
}

// ✅ LOGOUT (Redirect to landing page)
if ($path === '/auth/logout') {
    session_unset();
    session_destroy();
    header('Location: /');
    exit;
}

// ✅ REQUIRE LOGIN FOR PRIVATE ROUTES
$publicPaths = ['/auth/login', '/auth/signup', '/', '/landing'];
if (!isLoggedIn() && !in_array($path, $publicPaths)) {
    header('Location: /auth/login');
    exit;
}

// ✅ DASHBOARD
if ($path === '/dashboard') {
    $tickets = readJsonFile($ticketsFile);
    $total = count($tickets);
    $open = count(array_filter($tickets, fn($t) => $t['status'] === 'open'));
    $inProgress = count(array_filter($tickets, fn($t) => $t['status'] === 'in_progress'));
    $closed = count(array_filter($tickets, fn($t) => $t['status'] === 'closed'));
    $completionRate = $total > 0 ? round(($closed / $total) * 100, 1) : 0;

    $priorityStats = [
        'low' => count(array_filter($tickets, fn($t) => $t['priority'] === 'low')),
        'medium' => count(array_filter($tickets, fn($t) => $t['priority'] === 'medium')),
        'high' => count(array_filter($tickets, fn($t) => $t['priority'] === 'high')),
    ];

    echo $twig->render('dashboard.twig', [
        'user' => $_SESSION['user'],
        'tickets' => $tickets,
        'total' => $total,
        'open' => $open,
        'inProgress' => $inProgress,
        'closed' => $closed,
        'completionRate' => $completionRate,
        'priorityStats' => $priorityStats,
    ]);
    exit;
}

// ✅ TICKET LIST
if ($path === '/tickets' || $path === '/ticket_list') {
    $tickets = readJsonFile($ticketsFile);
    echo $twig->render('ticket_list.twig', ['tickets' => $tickets]);
    exit;
}

// ✅ CREATE TICKET (GET/POST)
if ($path === '/tickets/create') {
    if ($method === 'GET') {
        echo $twig->render('create_ticket.twig');
        exit;
    }

    $title = sanitizeInput($_POST['title'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $status = sanitizeInput($_POST['status'] ?? 'open');
    $priority = sanitizeInput($_POST['priority'] ?? 'medium');

    $errors = [];
    
    // Title validation
    if (empty($title)) {
        $errors['title'] = 'Title is required.';
    } elseif (!hasAtLeastWords($title, 3)) {
        $errors['title'] = 'Title must contain at least 3 words.';
    } elseif (strlen($title) > 200) {
        $errors['title'] = 'Title must not exceed 200 characters.';
    }

    // Description validation
    if (empty($description)) {
        $errors['description'] = 'Description is required.';
    } elseif (!hasAtLeastWords($description, 3)) {
        $errors['description'] = 'Description must contain at least 3 words.';
    } elseif (strlen($description) > 1000) {
        $errors['description'] = 'Description must not exceed 1000 characters.';
    }

    // Status validation
    $validStatuses = ['open', 'in_progress', 'closed'];
    if (empty($status)) {
        $errors['status'] = 'Status is required.';
    } elseif (!in_array($status, $validStatuses)) {
        $errors['status'] = 'Invalid status selected.';
    }

    // Priority validation
    $validPriorities = ['low', 'medium', 'high'];
    if (empty($priority)) {
        $errors['priority'] = 'Priority is required.';
    } elseif (!in_array($priority, $validPriorities)) {
        $errors['priority'] = 'Invalid priority selected.';
    }

    if (!empty($errors)) {
        echo $twig->render('create_ticket.twig', [
            'errors' => $errors, 
            'old' => $_POST
        ]);
        exit;
    }

    $tickets = readJsonFile($ticketsFile);
    $tickets[] = [
        'id' => uniqid(),
        'title' => $title,
        'description' => $description,
        'status' => $status,
        'priority' => $priority,
        'created_at' => date('Y-m-d H:i:s'),
        'created_by' => $_SESSION['user']['email'],
    ];

    saveJsonFile($ticketsFile, $tickets);
    header('Location: /dashboard');
    exit;
}

// ✅ EDIT TICKET (GET)
if (preg_match('#^/tickets/([a-zA-Z0-9]+)/edit$#', $path, $matches)) {
    $id = $matches[1];
    $tickets = readJsonFile($ticketsFile);
    $ticket = array_values(array_filter($tickets, fn($t) => $t['id'] === $id))[0] ?? null;

    if (!$ticket) {
        echo $twig->render('404.twig');
        exit;
    }

    echo $twig->render('edit_ticket.twig', ['ticket' => $ticket]);
    exit;
}

// ✅ UPDATE TICKET (POST)
if ($path === '/tickets/update' && $method === 'POST') {
    $tickets = readJsonFile($ticketsFile);
    $id = $_POST['id'] ?? '';
    $title = sanitizeInput($_POST['title'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $status = sanitizeInput($_POST['status'] ?? '');
    $priority = sanitizeInput($_POST['priority'] ?? '');

    $errors = [];
    
    // Title validation
    if (empty($title)) {
        $errors['title'] = 'Title is required.';
    } elseif (!hasAtLeastWords($title, 3)) {
        $errors['title'] = 'Title must contain at least 3 words.';
    } elseif (strlen($title) > 200) {
        $errors['title'] = 'Title must not exceed 200 characters.';
    }

    // Description validation
    if (empty($description)) {
        $errors['description'] = 'Description is required.';
    } elseif (!hasAtLeastWords($description, 3)) {
        $errors['description'] = 'Description must contain at least 3 words.';
    } elseif (strlen($description) > 1000) {
        $errors['description'] = 'Description must not exceed 1000 characters.';
    }

    // Status validation
    $validStatuses = ['open', 'in_progress', 'closed'];
    if (!in_array($status, $validStatuses)) {
        $errors['status'] = 'Invalid status selected.';
    }

    // Priority validation
    $validPriorities = ['low', 'medium', 'high'];
    if (!in_array($priority, $validPriorities)) {
        $errors['priority'] = 'Invalid priority selected.';
    }

    $index = findTicketIndexById($tickets, $id);
    if ($index === null) {
        echo $twig->render('404.twig');
        exit;
    }

    if (!empty($errors)) {
        echo $twig->render('edit_ticket.twig', [
            'ticket' => $tickets[$index],
            'errors' => $errors,
        ]);
        exit;
    }

    $tickets[$index]['title'] = $title;
    $tickets[$index]['description'] = $description;
    $tickets[$index]['status'] = $status;
    $tickets[$index]['priority'] = $priority;
    $tickets[$index]['updated_at'] = date('Y-m-d H:i:s');

    saveJsonFile($ticketsFile, $tickets);
    header('Location: /dashboard');
    exit;
}

// ✅ DELETE TICKET (Fixed route pattern)
if (preg_match('#^/tickets/delete/([a-zA-Z0-9]+)$#', $path, $matches)) {
    $id = $matches[1];
    $tickets = readJsonFile($ticketsFile);
    $tickets = array_filter($tickets, fn($t) => $t['id'] !== $id);
    saveJsonFile($ticketsFile, array_values($tickets));
    header('Location: /tickets');
    exit;
}

// ✅ 404
http_response_code(404);
echo $twig->render('404.twig');
exit;