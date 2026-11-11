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
    // Sender name people see in their inbox. Override with MAIL_FROM_NAME in .env.
    $m->setFrom($env['SMTP_USER'], $env['MAIL_FROM_NAME'] ?? 'Best Politician Assembly');
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

// A random private code for one person's itinerary link (32 letters/numbers, impossible to guess)
function makeItineraryToken(): string
{
    return bin2hex(random_bytes(16));
}

// Data both email templates need.
// $itineraryToken is optional: when given, the approval email shows the "Download your Itinerary" button.
function emailData(array $env, string $name, string $country, string $location, string $date, ?string $itineraryToken = null): array
{
    $site = rtrim($env['SITE_URL'], '/');
    return [
        'itinerary_url' => $itineraryToken ? $site . '/itinerary.php?t=' . rawurlencode($itineraryToken) : '',
        'brand'         => $env['BRAND_NAME'] ?? 'Best Politician Assembly',
        'name'          => $name,
        'country'       => $country,
        'location'      => $location,
        'date'          => $date,
        // Deep-blue logo. Override with LOGO_URL in .env if you move or replace it.
        'logo_url'      => $env['LOGO_URL'] ?? ($site . '/assets/images/logo2.jpeg'),
        'site_url'      => $site,
        // "Do you need a visa" page, used by the "click here" link in the terms. Override with VISA_URL in .env.
        'visa_url'      => $env['VISA_URL'] ?? ($site . '/get-visa.html'),
        'contact_email' => $env['CONTACT_EMAIL'] ?? $env['SMTP_USER'],
        // Social links: set FACEBOOK_URL, INSTAGRAM_URL, TIKTOK_URL in .env to change them
        // without touching code. The values below are only used when .env has no entry.
        'facebook'      => $env['FACEBOOK_URL'] ?? 'https://www.facebook.com/bestpoliticianassembly.search',
        'instagram'     => $env['INSTAGRAM_URL'] ?? 'https://www.instagram.com/bestpolitician666?cplk=NnhhaXl2bDhzMzVi',
        'tiktok'        => $env['TIKTOK_URL'] ?? 'https://www.tiktok.com/@user8540388832663',
    ];
}