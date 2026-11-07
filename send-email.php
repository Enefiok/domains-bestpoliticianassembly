<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

define('APP_MAIL', true);

require __DIR__ . '/phpmailer/Exception.php';
require __DIR__ . '/phpmailer/PHPMailer.php';
require __DIR__ . '/phpmailer/SMTP.php';
require __DIR__ . '/email-template.php';

/* ---------- helpers ---------- */

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

function field(string $key): string
{
    $v = $_POST[$key] ?? '';
    return is_string($v) ? mb_substr(trim($v), 0, 3000) : '';
}

function fail(string $message): void
{
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
       . '<title>Something went wrong</title></head>'
       . '<body style="font-family:Arial,sans-serif;text-align:center;padding:60px 20px;">'
       . '<h2>Sorry, something went wrong</h2><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>'
       . '<p><a href="register.html">Go back to registration</a></p></body></html>';
    exit;
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

/* ---------- only accept form submissions ---------- */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.html');
    exit;
}

// Honeypot: real people never fill this hidden field, bots do
if (field('website') !== '') {
    header('Location: thank-you.html');
    exit;
}

/* ---------- load secrets from .env (kept OUTSIDE public_html) ---------- */

$env = loadEnv(dirname(__DIR__) . '/.env');
foreach (['SMTP_HOST', 'SMTP_USER', 'SMTP_PASS', 'ADMIN_EMAIL', 'SITE_URL'] as $required) {
    if (empty($env[$required])) {
        error_log("send-email.php: missing $required in .env");
        fail('The registration system is not configured yet. Please contact the organizers.');
    }
}

/* ---------- read and check the form ---------- */

$firstName = field('First_Name');
$lastName  = field('Last_Name');
$email     = field('Email');

if ($firstName === '' || $lastName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fail('Please go back and check your name and email address.');
}

// "KUALA LUMPUR (24th-27 March, 2027)"  ->  location + date
$destination = field('Selected_Destination');
$location    = $destination;
$eventDate   = '';
if (preg_match('/^(.*)\s\((.*)\)$/', $destination, $m)) {
    $location  = trim($m[1]);
    $eventDate = trim($m[2]);
}

/* ---------- 1) copy of the full registration to the organizers ---------- */

$rows = '';
foreach ($_POST as $key => $value) {
    if (!is_string($value) || in_array($key, ['website', 'access_key', 'subject'], true)) {
        continue;
    }
    $rows .= '<tr><td style="padding:6px 10px;border:1px solid #ddd;background:#f5f8fc;font-weight:bold;">'
           . h(str_replace('_', ' ', $key)) . '</td><td style="padding:6px 10px;border:1px solid #ddd;">'
           . nl2br(h($value)) . '</td></tr>';
}

try {
    $admin = makeMailer($env);
    $admin->addAddress($env['ADMIN_EMAIL']);
    $admin->addReplyTo($email, "$firstName $lastName");
    $admin->Subject = "New Registration: $firstName $lastName - $location";
    $admin->Body    = '<h2 style="font-family:Arial,sans-serif;">New registration received</h2>'
                    . '<table style="border-collapse:collapse;font-family:Arial,sans-serif;font-size:14px;">' . $rows . '</table>';
    $admin->AltBody = "New registration from $firstName $lastName ($email) for $destination. Open this email in HTML to see all details.";
    $admin->send();
} catch (Exception $e) {
    error_log('send-email.php (admin mail): ' . $e->getMessage());
    fail('We could not submit your registration right now. Please try again in a few minutes.');
}

/* ---------- 2) confirmation email to the applicant (the template) ---------- */

try {
    $site = rtrim($env['SITE_URL'], '/');

    $html = buildApplicantEmail([
        'brand'         => $env['BRAND_NAME'] ?? 'Best Politicians',
        'name'          => "$firstName $lastName",
        'country'       => field('Country'),
        'location'      => $location,
        'date'          => $eventDate,
        'logo_url'      => $site . '/assets/images/best%20logo.png',
        'site_url'      => $site,
        'twibbon_url'   => $env['TWIBBON_URL'] ?? '#',
        'contact_email' => $env['CONTACT_EMAIL'] ?? $env['SMTP_USER'],
        'facebook'      => $env['FACEBOOK_URL'] ?? 'https://www.facebook.com/bestpoliticians',
        'instagram'     => $env['INSTAGRAM_URL'] ?? 'https://www.instagram.com/bestpoliticians',
    ]);

    $user = makeMailer($env);
    $user->addAddress($email, "$firstName $lastName");
    $user->addReplyTo($env['CONTACT_EMAIL'] ?? $env['SMTP_USER']);
    $user->Subject = 'Your Registration has been Received';
    $user->Body    = $html;
    $user->AltBody = "Dear $firstName $lastName, your registration for the conference in $location ($eventDate) has been received and is under review. "
                   . 'Shortlisted applicants will be contacted by email within 48 hours.';
    $user->send();
} catch (Exception $e) {
    // The organizers already have the registration, so don't block the applicant.
    error_log('send-email.php (applicant mail): ' . $e->getMessage());
}

header('Location: thank-you.html');
exit;