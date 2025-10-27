<?php
namespace App\Controllers;

use Symfony\Component\HttpFoundation\Session\Session;
use Twig\Environment;

class DashboardController
{
    private $twig;
    private $session;
    private $ticketsFile;

    public function __construct(Environment $twig, Session $session)
    {
        $this->twig = $twig;
        $this->session = $session;
        $this->ticketsFile = __DIR__ . '/../../storage/tickets.json';
        
        if (!file_exists($this->ticketsFile)) {
            file_put_contents($this->ticketsFile, json_encode([]));
        }
    }

    public function index()
    {
        $tickets = json_decode(file_get_contents($this->ticketsFile), true);
        
        $stats = [
            'total' => count($tickets),
            'open' => count(array_filter($tickets, fn($t) => $t['status'] === 'open')),
            'in_progress' => count(array_filter($tickets, fn($t) => $t['status'] === 'in_progress')),
            'closed' => count(array_filter($tickets, fn($t) => $t['status'] === 'closed'))
        ];

        return $this->twig->render('dashboard.twig', [
            'stats' => $stats
        ]);
    }
}