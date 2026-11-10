<?php
declare(strict_types=1);

// Runs the approval emails when you open this page in a browser.
// URL: https://yoursite.org/run-approvals.php?key=YOUR_SECRET
// Needs APPROVALS_KEY=<at least 16 random letters/numbers> in your .env file.
// Same steps as send_approvals.php, but errors are shown on the page.

define('APP_MAIL', true);

header('Content-Type: text/plain; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

require __DIR__ . '/lib.php';

$env   = loadEnv(envPath());
$key   = (string)($env['APPROVALS_KEY'] ?? '');
$given = $_GET['key'] ?? '';

if (strlen($key) < 16 || !is_string($given) || !hash_equals($key, $given)) {
    http_response_code(403);
    exit('Forbidden');
}

// Only after the key is correct: show every PHP error on this page
ini_set('display_errors', '1');
error_reporting(E_ALL);
set_time_limit(120);

require __DIR__ . '/email-template.php';

foreach (['SMTP_HOST', 'SMTP_USER', 'SMTP_PASS', 'SITE_URL', 'DB_NAME', 'DB_USER', 'DB_PASS'] as $required) {
    if (empty($env[$required])) {
        exit("Missing $required in .env\n");
    }
}

$delay = max(1, (int)($env['APPROVAL_DELAY_MINUTES'] ?? 1440));
echo date('c') . " - starting (delay {$delay} min)\n";

try {
    $pdo = getDb($env);

    $due = $pdo->query(
        "SELECT * FROM registrations
         WHERE status = 'pending' AND created_at <= (NOW() - INTERVAL {$delay} MINUTE)
         ORDER BY created_at
         LIMIT 50"
    )->fetchAll();

    $claim  = $pdo->prepare("UPDATE registrations SET status = 'approved', approved_by = 'auto', approved_at = NOW()
                             WHERE id = ? AND status = 'pending'");
    $revert = $pdo->prepare("UPDATE registrations SET status = 'pending', approved_by = NULL, approved_at = NULL
                             WHERE id = ?");
    $saveToken = $pdo->prepare("UPDATE registrations SET itinerary_token = COALESCE(itinerary_token, ?) WHERE id = ?");
    $getToken  = $pdo->prepare("SELECT itinerary_token FROM registrations WHERE id = ?");

    echo 'due: ' . count($due) . "\n";

    $sent = 0;
    foreach ($due as $r) {
        $claim->execute([$r['id']]);
        if ($claim->rowCount() !== 1) {
            continue;
        }

        try {
            $fullName = trim($r['first_name'] . ' ' . $r['last_name']);

            $token = null;
            try {
                $saveToken->execute([makeItineraryToken(), $r['id']]);
                $getToken->execute([$r['id']]);
                $token = $getToken->fetchColumn() ?: null;
            } catch (Throwable $e) {
                echo 'itinerary token problem (id ' . $r['id'] . '): ' . $e->getMessage() . "\n";
            }

            $html = buildApprovalEmail(
                emailData($env, $fullName, (string)$r['country'], (string)$r['location'], (string)$r['event_date'], $token)
            );

            $m = makeMailer($env);
            $m->addAddress($r['email'], $fullName);
            $m->addReplyTo($env['CONTACT_EMAIL'] ?? $env['SMTP_USER']);
            $m->Subject = 'CONGRATULATIONS! Your Registration has been Approved';
            $m->Body    = $html;
            $m->AltBody = "Congratulations $fullName! Your application for the conference in {$r['location']} ({$r['event_date']}) has been approved. "
                        . 'Please open this email in HTML to see the payment options and terms.';
            $m->send();
            $sent++;
            echo 'sent id ' . $r['id'] . "\n";
        } catch (Throwable $e) {
            $revert->execute([$r['id']]);
            echo 'FAILED id ' . $r['id'] . ': ' . $e->getMessage() . "\n";
        }
    }

    echo date('c') . " - approval emails sent: $sent\n";
} catch (Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
}