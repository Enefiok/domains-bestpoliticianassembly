<?php
declare(strict_types=1);

// Run by cron only. If someone opens this URL in a browser, refuse.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

define('APP_MAIL', true);

require __DIR__ . '/lib.php';
require __DIR__ . '/email-template.php';

$env = loadEnv(envPath());
foreach (['SMTP_HOST', 'SMTP_USER', 'SMTP_PASS', 'SITE_URL', 'DB_NAME', 'DB_USER', 'DB_PASS'] as $required) {
    if (empty($env[$required])) {
        fwrite(STDERR, "send_approvals.php: missing $required in .env\n");
        exit(1);
    }
}

// Minutes to wait before auto-approving. 1440 = 24 hours. Set 1 in .env to test.
$delay = max(1, (int)($env['APPROVAL_DELAY_MINUTES'] ?? 1440));

try {
    $pdo = getDb($env);

    // The database clock is used on both sides, so there is no timezone mix-up.
    $due = $pdo->query(
        "SELECT * FROM registrations
         WHERE status = 'pending' AND created_at <= (NOW() - INTERVAL {$delay} MINUTE)
         ORDER BY created_at
         LIMIT 50"
    )->fetchAll();

    // Claim a row BEFORE sending, so two overlapping runs (or an admin click)
    // can never send the same person two approval emails.
    $claim  = $pdo->prepare("UPDATE registrations SET status = 'approved', approved_by = 'auto', approved_at = NOW()
                             WHERE id = ? AND status = 'pending'");
    $revert = $pdo->prepare("UPDATE registrations SET status = 'pending', approved_by = NULL, approved_at = NULL
                             WHERE id = ?");

    // Private itinerary link code. Keeps an existing code, or saves a new one.
    $saveToken = $pdo->prepare("UPDATE registrations SET itinerary_token = COALESCE(itinerary_token, ?) WHERE id = ?");
    $getToken  = $pdo->prepare("SELECT itinerary_token FROM registrations WHERE id = ?");

    $sent = 0;
    foreach ($due as $r) {
        $claim->execute([$r['id']]);
        if ($claim->rowCount() !== 1) {
            continue; // someone else already handled it
        }

        try {
            $fullName = trim($r['first_name'] . ' ' . $r['last_name']);

            // Itinerary link. If this step fails (for example the database column
            // has not been added yet), the email is still sent, just without the button.
            $token = null;
            try {
                $saveToken->execute([makeItineraryToken(), $r['id']]);
                $getToken->execute([$r['id']]);
                $token = $getToken->fetchColumn() ?: null;
            } catch (Throwable $e) {
                error_log('send_approvals.php (itinerary token, id ' . $r['id'] . '): ' . $e->getMessage());
            }

            $html = buildApprovalEmail(
                emailData($env, $fullName, $r['country'], $r['location'], $r['event_date'], $token)
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
        } catch (Throwable $e) {
            // Put the person back in the queue so the next run tries again
            $revert->execute([$r['id']]);
            error_log('send_approvals.php (id ' . $r['id'] . '): ' . $e->getMessage());
            echo 'FAILED id ' . $r['id'] . ': ' . $e->getMessage() . "\n";
        }
    }

    echo date('c') . " - due: " . count($due) . ", approval emails sent: $sent\n";
} catch (Throwable $e) {
    error_log('send_approvals.php: ' . $e->getMessage());
    fwrite(STDERR, 'send_approvals.php error: ' . $e->getMessage() . "\n");
    exit(1);
}