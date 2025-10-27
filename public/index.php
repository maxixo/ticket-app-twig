<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;

// Initialize Twig
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../templates');
$twig = new \Twig\Environment($loader);

// Create request object
$request = Request::createFromGlobals();
$path = $request->getPathInfo();

// Ensure data directory exists
$dataDir = __DIR__ . '/../data';
if (!is_dir($dataDir)) mkdir($dataDir, 0777, true);

// Define tickets file
$ticketsFile = $dataDir . '/tickets.json';
if (!file_exists($ticketsFile)) file_put_contents($ticketsFile, json_encode([], JSON_PRETTY_PRINT));

// Utility functions
function readTickets($file) {
    if (!file_exists($file)) return [];
    $data = file_get_contents($file);
    return json_decode($data, true) ?? [];
}

function saveTickets($file, $tickets) {
    file_put_contents($file, json_encode($tickets, JSON_PRETTY_PRINT));
}

/* ===========================================================
   ROUTES
=========================================================== */

// ✅ DASHBOARD
if ($path === '/' || $path === '/dashboard') {
    $tickets = readTickets($ticketsFile);
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
        'total' => $total,
        'open' => $open,
        'inProgress' => $inProgress,
        'closed' => $closed,
        'completionRate' => $completionRate,
        'priorityStats' => $priorityStats,
        'tickets' => $tickets
    ]);
    exit;
}

// ✅ TICKET LIST
if ($path === '/ticket_list' || $path === '/tickets') {
    $tickets = readTickets($ticketsFile);
    echo $twig->render('ticket_list.twig', ['tickets' => $tickets]);
    exit;
}

// ✅ CREATE TICKET (GET)
if ($path === '/tickets/create' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    echo $twig->render('create_ticket.twig');
    exit;
}

// ✅ CREATE TICKET (POST)
if ($path === '/tickets/create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $tickets = readTickets($ticketsFile);

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = trim($_POST['status'] ?? 'open');
    $priority = trim($_POST['priority'] ?? 'medium');

    // Validation
    if (strlen($title) < 3 || strlen($description) < 3) {
        echo $twig->render('create_ticket.twig', [
            'error' => 'Title and description must be at least 3 characters long.'
        ]);
        exit;
    }

    // Create new ticket
    $newTicket = [
        'id' => uniqid(),
        'title' => $title,
        'description' => $description,
        'status' => $status,
        'priority' => $priority,
        'created_at' => date('Y-m-d H:i:s')
    ];

    $tickets[] = $newTicket;
    saveTickets($ticketsFile, $tickets);

    header('Location: /ticket_list');
    exit;
}
// ✅ EDIT TICKET (GET)
// ✅ EDIT TICKET (GET)
if (preg_match('#^/tickets/([a-zA-Z0-9]+)/edit$#', $path, $matches)) {
    $id = $matches[1]; // Extract ticket ID from URL
    $tickets = readTickets($ticketsFile);

    // Find ticket by ID
    $ticket = null;
    foreach ($tickets as $t) {
        if ($t['id'] === $id) {
            $ticket = $t;
            break;
        }
    }

    if (!$ticket) {
        echo $twig->render('404.twig');
        exit;
    }

    echo $twig->render('edit_ticket.twig', ['ticket' => $ticket]);
    exit;
}


// ✅ UPDATE TICKET (POST)
// ✅ UPDATE TICKET (POST)
if ($path === '/tickets/update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $tickets = readTickets($ticketsFile);
    foreach ($tickets as &$ticket) {
        if ($ticket['id'] === $_POST['id']) {
            $ticket['title'] = trim($_POST['title']);
            $ticket['description'] = trim($_POST['description']);
            $ticket['status'] = $_POST['status'];
            $ticket['priority'] = $_POST['priority'];
        }
    }
    saveTickets($ticketsFile, $tickets);
    header('Location: /ticket_list');
    exit;
}

// ✅ DELETE TICKET
if (preg_match('#^/tickets/delete/([a-zA-Z0-9]+)$#', $path, $matches)) {
    $id = $matches[1];
    $tickets = readTickets($ticketsFile);
    $tickets = array_filter($tickets, fn($t) => $t['id'] !== $id);
    saveTickets($ticketsFile, array_values($tickets));
    header('Location: /ticket_list');
    exit;
}

// ✅ 404 PAGE
echo $twig->render('404.twig');
exit;
?>
