<?php
declare(strict_types=1);

// Opened from the "Download your Itinerary" button in the approval email.
// The link looks like: https://yoursite.org/itinerary.php?t=<32 letters and numbers>

define('APP_MAIL', true);

require __DIR__ . '/lib.php';
require __DIR__ . '/itinerary-template.php';

function notFound(): void
{
    http_response_code(404);
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
       . '<title>Itinerary not found</title></head>'
       . '<body style="font-family:Arial,sans-serif;text-align:center;padding:60px 20px;">'
       . '<h2>Itinerary not found</h2><p>This link is not valid. Please use the button in your approval email, '
       . 'or contact the organizers.</p></body></html>';
    exit;
}

// Never let search engines or caches keep a personal page
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

$token = $_GET['t'] ?? '';
if (!is_string($token) || !preg_match('/^[a-f0-9]{32}$/', $token)) {
    notFound();
}

$env = loadEnv(envPath());
if (empty($env['SITE_URL'])) {
    error_log('itinerary.php: missing SITE_URL in .env');
    notFound();
}

try {
    $pdo  = getDb($env);
    $stmt = $pdo->prepare("SELECT first_name, last_name, country, location, event_date
                           FROM registrations
                           WHERE itinerary_token = ? AND status = 'approved'
                           LIMIT 1");
    $stmt->execute([$token]);
    $r = $stmt->fetch();
} catch (Throwable $e) {
    error_log('itinerary.php: ' . $e->getMessage());
    notFound();
}

if (!$r) {
    notFound();
}

$fullName = trim($r['first_name'] . ' ' . $r['last_name']);
$d    = emailData($env, $fullName, (string)$r['country'], (string)$r['location'], (string)$r['event_date']);
$html = buildItineraryHtml($d);

// If the Dompdf library is installed, send a real PDF.
// If not, show the itinerary as a normal page (the person can still print or save it as PDF).
$autoload = null;
foreach ([__DIR__ . '/vendor/autoload.php', dirname(__DIR__) . '/vendor/autoload.php'] as $candidate) {
    if (is_readable($candidate)) {
        $autoload = $candidate;
        break;
    }
}

if ($autoload !== null) {
    try {
        require $autoload;
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true); // lets the PDF load the logo from your site
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream('Conference-Itinerary.pdf', ['Attachment' => true]);
        exit;
    } catch (Throwable $e) {
        error_log('itinerary.php (pdf): ' . $e->getMessage());
        // fall through to the plain page below
    }
}

// Without Dompdf the itinerary is a normal web page, so add a "Download as PDF" button.
// It opens the browser's print window, where the person chooses "Save as PDF".
// The button hides itself when printing, so it never appears in the saved PDF.
$toolbar = '<style>'
    . '@media print{.bp-toolbar{display:none!important}}'
    . '.bp-toolbar{position:fixed;top:14px;right:14px;z-index:9999;font-family:Arial,Helvetica,sans-serif;text-align:center}'
    . '.bp-toolbar button{background:#F2B01E;color:#111111;border:0;border-radius:8px;padding:12px 22px;font-size:14px;font-weight:bold;cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,.35)}'
    . '.bp-toolbar small{display:block;margin-top:5px;font-size:11px;color:#ffffff;text-shadow:0 1px 3px rgba(0,0,0,.8)}'
    . '</style>'
    . '<div class="bp-toolbar"><button type="button" onclick="window.print()">Download as PDF</button>'
    . '<small>Choose &ldquo;Save as PDF&rdquo; in the window that opens</small></div>';
$html = str_replace('</body>', $toolbar . '</body>', $html);

header('Content-Type: text/html; charset=UTF-8');
echo $html;