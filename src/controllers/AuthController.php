<?php
namespace App\Controllers;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig\Environment;

class AuthController
{
    private $twig;
    private $session;
    private $usersFile;

    public function __construct(Environment $twig, Session $session)
    {
        $this->twig = $twig;
        $this->session = $session;
        $this->usersFile = __DIR__ . '/../../storage/users.json';
        
        // Create storage directory if not exists
        if (!file_exists(dirname($this->usersFile))) {
            mkdir(dirname($this->usersFile), 0777, true);
        }
        
        // Create users file if not exists
        if (!file_exists($this->usersFile)) {
            file_put_contents($this->usersFile, json_encode([]));
        }
    }

    public function login(Request $request)
    {
        $errors = [];
        $message = $this->session->getFlashBag()->get('message');

        if ($request->getMethod() === 'POST') {
            $email = $request->request->get('email');
            $password = $request->request->get('password');

            if (empty($email)) {
                $errors['email'] = 'Email is required';
            }
            if (empty($password)) {
                $errors['password'] = 'Password is required';
            }

            if (empty($errors)) {
                $users = json_decode(file_get_contents($this->usersFile), true);
                
                foreach ($users as $user) {
                    if ($user['email'] === $email && password_verify($password, $user['password'])) {
                        $this->session->set('authenticated', true);
                        $this->session->set('user_email', $email);
                        $this->session->set('ticketapp_session', [
                            'token' => 'mock-token-' . time(),
                            'email' => $email
                        ]);
                        header('Location: /dashboard');
                        exit;
                    }
                }
                
                $errors['general'] = 'Invalid credentials';
            }
        }

        return $this->twig->render('auth/login.twig', [
            'errors' => $errors,
            'message' => $message ? $message[0] : null
        ]);
    }

    public function signup(Request $request)
    {
        $errors = [];

        if ($request->getMethod() === 'POST') {
            $name = $request->request->get('name');
            $email = $request->request->get('email');
            $password = $request->request->get('password');

            if (empty($name)) {
                $errors['name'] = 'Name is required';
            }
            if (empty($email)) {
                $errors['email'] = 'Email is required';
            }
            if (empty($password)) {
                $errors['password'] = 'Password is required';
            }

            if (empty($errors)) {
                $users = json_decode(file_get_contents($this->usersFile), true);
                
                // Check if email already exists
                foreach ($users as $user) {
                    if ($user['email'] === $email) {
                        $errors['email'] = 'Email already exists';
                        break;
                    }
                }

                if (empty($errors)) {
                    $users[] = [
                        'name' => $name,
                        'email' => $email,
                        'password' => password_hash($password, PASSWORD_DEFAULT)
                    ];
                    
                    file_put_contents($this->usersFile, json_encode($users));
                    
                    $this->session->getFlashBag()->add('message', 'Account created! Please login.');
                    header('Location: /auth/login');
                    exit;
                }
            }
        }

        return $this->twig->render('auth/signup.twig', [
            'errors' => $errors
        ]);
    }

    public function logout()
    {
        $this->session->clear();
        header('Location: /');
        exit;
    }
}