<?php
namespace App\Controllers;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig\Environment;

class TicketController
{
    private $twig;
    private $session;
    private $request;
    private $ticketsFile;

    public function __construct(Environment $twig, Session $session, Request $request)
    {
        $this->twig = $twig;
        $this->session = $session;
        $this->request = $request;
        $this->ticketsFile = __DIR__ . '/../../storage/tickets.json';
        
        if (!file_exists($this->ticketsFile)) {
            file_put_contents($this->ticketsFile, json_encode([]));
        }
    }

    private function getTickets()
    {
        return json_decode(file_get_contents($this->ticketsFile), true);
    }

    private function saveTickets($tickets)
    {
        file_put_contents($this->ticketsFile, json_encode($tickets));
    }

    public function index()
    {
        $tickets = $this->getTickets();
        $message = $this->session->getFlashBag()->get('message');

        return $this->twig->render('tickets/index.twig', [
            'tickets' => $tickets,
            'message' => $message ? $message[0] : null
        ]);
    }

    public function create()
    {
        $errors = [];
        
        if ($this->request->getMethod() === 'POST') {
            $title = $this->request->request->get('title');
            $description = $this->request->request->get('description');
            $status = $this->request->request->get('status');
            $priority = $this->request->request->get('priority', 'medium');

            // Validation
            if (empty(trim($title))) {
                $errors['title'] = 'Title is required';
            }
            
            if (!in_array($status, ['open', 'in_progress', 'closed'])) {
                $errors['status'] = 'Status must be open, in_progress, or closed';
            }

            if (empty($errors)) {
                $tickets = $this->getTickets();
                
                $tickets[] = [
                    'id' => time(),
                    'title' => $title,
                    'description' => $description,
                    'status' => $status,
                    'priority' => $priority
                ];
                
                $this->saveTickets($tickets);
                
                $this->session->getFlashBag()->add('message', 'Ticket created successfully');
                header('Location: /tickets');
                exit;
            }
        }

        return $this->twig->render('tickets/create.twig', [
            'errors' => $errors
        ]);
    }

    public function edit($id)
    {
        $tickets = $this->getTickets();
        $ticket = null;
        
        foreach ($tickets as $t) {
            if ($t['id'] == $id) {
                $ticket = $t;
                break;
            }
        }

        if (!$ticket) {
            header('Location: /tickets');
            exit;
        }

        $errors = [];
        
        if ($this->request->getMethod() === 'POST') {
            $title = $this->request->request->get('title');
            $description = $this->request->request->get('description');
            $status = $this->request->request->get('status');
            $priority = $this->request->request->get('priority', 'medium');

            // Validation
            if (empty(trim($title))) {
                $errors['title'] = 'Title is required';
            }
            
            if (!in_array($status, ['open', 'in_progress', 'closed'])) {
                $errors['status'] = 'Status must be open, in_progress, or closed';
            }

            if (empty($errors)) {
                foreach ($tickets as $key => $t) {
                    if ($t['id'] == $id) {
                        $tickets[$key] = [
                            'id' => $id,
                            'title' => $title,
                            'description' => $description,
                            'status' => $status,
                            'priority' => $priority
                        ];
                        break;
                    }
                }
                
                $this->saveTickets($tickets);
                
                $this->session->getFlashBag()->add('message', 'Ticket updated successfully');
                header('Location: /tickets');
                exit;
            }
        }

        return $this->twig->render('tickets/edit.twig', [
            'ticket' => $ticket,
            'errors' => $errors
        ]);
    }

    public function delete($id)
    {
        $tickets = $this->getTickets();
        $tickets = array_filter($tickets, fn($t) => $t['id'] != $id);
        $this->saveTickets(array_values($tickets));
        
        $this->session->getFlashBag()->add('message', 'Ticket deleted successfully');
        header('Location: /tickets');
        exit;
    }
}