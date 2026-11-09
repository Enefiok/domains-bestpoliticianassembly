<?php
// Shared helpers used by send-email.php and send_approvals.php.
// Blocks anyone from opening this file directly in a browser.
if (!defined('APP_MAIL')) {
    http_response_code(403);
    exit;
}

use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/phpmailer/Exception.php';
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';

// The .env file lives one folder ABOVE public_html (not reachable from the web)
function envPath(): string
{
    return dirname(__DIR__) . '/.env';
}

// Reads the .env file (KEY=value per line, # for comments)
function loadEnv(string $path): array
{
    $vars = [];
    if (!is_readable($path)) {
        return $vars;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $vars[trim($parts[0])] = trim(trim($parts[1]), "\"'");
        }
    }
    return $vars;
}

function makeMailer(array $env): PHPMailer
{
    $m = new PHPMailer(true);
    $m->isSMTP();
    $m->Host       = $env['SMTP_HOST'];
    $m->SMTPAuth   = true;
    $m->Username   = $env['SMTP_USER'];
    $m->Password   = $env['SMTP_PASS'];
    $port          = (int)($env['SMTP_PORT'] ?? 465);
    $m->SMTPSecure = $port === 587 ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
    $m->Port       = $port;
    $m->CharSet    = 'UTF-8';
    $m->isHTML(true);
    $m->setFrom($env['SMTP_USER'], $env['MAIL_FROM_NAME'] ?? 'Best Politicians');
    return $m;
}

function getDb(array $env): PDO
{
    foreach (['DB_NAME', 'DB_USER', 'DB_PASS'] as $k) {
        if (empty($env[$k])) {
            throw new RuntimeException("Missing $k in .env");
        }
    }
    $dsn = 'mysql:host=' . ($env['DB_HOST'] ?? 'localhost') . ';dbname=' . $env['DB_NAME'] . ';charset=utf8mb4';
    return new PDO($dsn, $env['DB_USER'], $env['DB_PASS'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
}

// Data both email templates need
function emailData(array $env, string $name, string $country, string $location, string $date): array
{
    $site = rtrim($env['SITE_URL'], '/');
    return [
        'brand'         => $env['BRAND_NAME'] ?? 'Best Politicians',
        'name'          => $name,
        'country'       => $country,
        'location'      => $location,
        'date'          => $date,
        'logo_url'      => $site . '/assets/images/best%20logo.png',
        'site_url'      => $site,
        'contact_email' => $env['CONTACT_EMAIL'] ?? $env['SMTP_USER'],
        'facebook'      => $env['FACEBOOK_URL'] ?? 'https://www.facebook.com/bestpoliticians',
        'instagram'     => $env['INSTAGRAM_URL'] ?? 'https://www.instagram.com/bestpoliticians',
    ];
}