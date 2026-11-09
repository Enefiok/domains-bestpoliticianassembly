<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\Exception;

define('APP_MAIL', true);

require __DIR__ . '/lib.php';
require __DIR__ . '/email-template.php';

/* ---------- helpers ---------- */

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

$env = loadEnv(envPath());
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

/* ---------- 0) save the registration (status = pending) ---------- */

// Every form field, so nothing is lost
$details = [];
foreach ($_POST as $key => $value) {
    if (!is_string($value) || in_array($key, ['website', 'access_key', 'subject'], true)) {
        continue;
    }
    $details[$key] = mb_substr(trim($value), 0, 3000);
}

$saved = false;
try {
    $pdo  = getDb($env);
    $json = json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    // Same email + same destination twice = one row (details refreshed, timer NOT restarted)
    $stmt = $pdo->prepare(
        'INSERT INTO registrations (first_name, last_name, email, country, location, event_date, details)
         VALUES (?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE details = ?'
    );
    $stmt->execute([$firstName, $lastName, $email, field('Country'), $location, $eventDate, $json, $json]);
    $saved = true;
} catch (Throwable $e) {
    // Don't lose the registration: the organizers still get the email below.
    error_log('send-email.php (database): ' . $e->getMessage());
}

/* ---------- 1) copy of the full registration to the organizers ---------- */

$rows = '';
foreach ($details as $key => $value) {
    $rows .= '<tr><td style="padding:6px 10px;border:1px solid #ddd;background:#f5f8fc;font-weight:bold;">'
           . h(str_replace('_', ' ', $key)) . '</td><td style="padding:6px 10px;border:1px solid #ddd;">'
           . nl2br(h($value)) . '</td></tr>';
}

try {
    $admin = makeMailer($env);
    $admin->addAddress($env['ADMIN_EMAIL']);
    $admin->addReplyTo($email, "$firstName $lastName");
    $admin->Subject = ($saved ? '' : '[NOT SAVED TO DATABASE] ') . "New Registration: $firstName $lastName - $location";
    $admin->Body    = '<h2 style="font-family:Arial,sans-serif;">New registration received</h2>'
                    . ($saved ? '' : '<p style="color:#b00020;font-family:Arial,sans-serif;"><strong>This registration could not be saved to the database, so it will NOT get the automatic approval email.</strong></p>')
                    . '<table style="border-collapse:collapse;font-family:Arial,sans-serif;font-size:14px;">' . $rows . '</table>';
    $admin->AltBody = "New registration from $firstName $lastName ($email) for $destination. Open this email in HTML to see all details.";
    $admin->send();
} catch (Exception $e) {
    error_log('send-email.php (admin mail): ' . $e->getMessage());
    fail('We could not submit your registration right now. Please try again in a few minutes.');
}

/* ---------- 2) "Application Under Review" email to the applicant ---------- */

try {
    $html = buildApplicantEmail(
        emailData($env, "$firstName $lastName", field('Country'), $location, $eventDate)
    );

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