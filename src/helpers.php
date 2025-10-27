<?php
/**
 * TicketFlow Helper Functions
 * ----------------------------
 * Common utilities shared across controllers, templates, and middleware.
 */

use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Simple function to read JSON data from a file safely.
 */
function read_json(string $path): array
{
    if (!file_exists($path)) {
        return [];
    }

    $content = file_get_contents($path);
    $decoded = json_decode($content, true);

    return is_array($decoded) ? $decoded : [];
}

/**
 * Writes data (array/object) to a JSON file in a human-readable format.
 */
function write_json(string $path, $data): bool
{
    $json = json_encode($data, JSON_PRETTY_PRINT);
    return file_put_contents($path, $json) !== false;
}

/**
 * Check if a user is authenticated (simplified helper).
 */
function is_authenticated(Session $session): bool
{
    return (bool) $session->get('authenticated');
}

/**
 * Redirect to a different route.
 */
function redirect(string $path): void
{
    header("Location: $path");
    exit;
}

/**
 * Flash message helper – easier access to Symfony’s FlashBag.
 */
function flash(Session $session, string $type, string $message): void
{
    $session->getFlashBag()->add($type, $message);
}

/**
 * Retrieve a single flash message of a given type.
 */
function get_flash(Session $session, string $type): ?string
{
    $messages = $session->getFlashBag()->get($type);
    return $messages[0] ?? null;
}

/**
 * Sanitize user input (e.g., before storing or rendering).
 */
function sanitize_input(string $value): string
{
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}
