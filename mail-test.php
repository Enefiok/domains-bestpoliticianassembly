<?php
// TEMPORARY TEST FILE - delete it from the server after use.
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/phpmailer/Exception.php';
require __DIR__ . '/phpmailer/PHPMailer.php';
require __DIR__ . '/phpmailer/SMTP.php';

header('Content-Type: text/plain; charset=utf-8');

$path = dirname(__DIR__) . '/.env';
echo "Looking for .env at: $path\n";
echo "File readable: " . (is_readable($path) ? 'yes' : 'NO - file missing or wrong place') . "\n\n";

$env = [];
if (is_readable($path)) {
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        $p = explode('=', $line, 2);
        if (count($p) === 2) $env[trim($p[0])] = trim(trim($p[1]), "\"'");
    }
}

foreach (['SMTP_HOST', 'SMTP_PORT', 'SMTP_USER', 'SMTP_PASS', 'ADMIN_EMAIL', 'SITE_URL'] as $k) {
    echo str_pad($k, 12) . ': ' . (empty($env[$k]) ? 'MISSING' : 'found') . "\n";
}
echo "\nSMTP_HOST value: " . ($env['SMTP_HOST'] ?? '-') . "\n";
echo "SMTP_USER value: " . ($env['SMTP_USER'] ?? '-') . "\n";
echo "Password length: " . strlen($env['SMTP_PASS'] ?? '') . " characters\n";
if (strpos($env['SMTP_PASS'] ?? '', 'PUT-THE') !== false) {
    echo ">>> PROBLEM: the password is still the placeholder text.\n";
}
echo "\n";

if (empty($env['SMTP_HOST']) || empty($env['SMTP_USER']) || empty($env['SMTP_PASS'])) {
    exit("Stopping: .env is missing required values.\n");
}

// Try both common Hostinger settings
$tests = [
    ['port' => 465, 'secure' => PHPMailer::ENCRYPTION_SMTPS,    'label' => 'Port 465 (SSL)'],
    ['port' => 587, 'secure' => PHPMailer::ENCRYPTION_STARTTLS, 'label' => 'Port 587 (STARTTLS)'],
];

foreach ($tests as $t) {
    $m = new PHPMailer(true);
    $m->isSMTP();
    $m->Host       = $env['SMTP_HOST'];
    $m->SMTPAuth   = true;
    $m->Username   = $env['SMTP_USER'];
    $m->Password   = $env['SMTP_PASS'];
    $m->SMTPSecure = $t['secure'];
    $m->Port       = $t['port'];
    $m->Timeout    = 15;

    echo "Testing " . $t['label'] . " ... ";
    try {
        if ($m->smtpConnect()) {
            echo "SUCCESS (connected and logged in)\n";
            $m->smtpClose();
        } else {
            echo "FAILED: " . $m->ErrorInfo . "\n";
        }
    } catch (Exception $e) {
        echo "FAILED: " . $e->getMessage() . "\n";
    }
}

echo "\nDone. Delete mail-test.php from the server now.\n";
