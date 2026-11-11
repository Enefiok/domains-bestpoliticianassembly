<?php
declare(strict_types=1);

// Opened from the "Download itinerary" button in the approval email.
// The link looks like: https://yoursite.org/itinerary.php?t=<32 letters and numbers>
//   itinerary.php?t=...          shows the Letter of Invitation + itinerary page (with the passport box)
//   itinerary.php?t=...&pdf=1    downloads the PDF (needs the Dompdf library, see below)

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

/* ---------- find the person (and their saved passport number, if any) ---------- */

$passportSupported = true;   // false if the passport_number column has not been added yet
$r = false;
try {
    $pdo = getDb($env);
    try {
        $stmt = $pdo->prepare("SELECT first_name, last_name, country, location, event_date, passport_number
                               FROM registrations
                               WHERE itinerary_token = ? AND status = 'approved'
                               LIMIT 1");
        $stmt->execute([$token]);
        $r = $stmt->fetch();
    } catch (Throwable $e) {
        // The passport_number column is missing: carry on without the passport box.
        $passportSupported = false;
        $stmt = $pdo->prepare("SELECT first_name, last_name, country, location, event_date
                               FROM registrations
                               WHERE itinerary_token = ? AND status = 'approved'
                               LIMIT 1");
        $stmt->execute([$token]);
        $r = $stmt->fetch();
    }
} catch (Throwable $e) {
    error_log('itinerary.php: ' . $e->getMessage());
    notFound();
}

if (!$r) {
    notFound();
}

/* ---------- save or remove the passport number (form on the page) ---------- */

$formError = '';
if ($passportSupported && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['remove'])) {
            $pdo->prepare("UPDATE registrations SET passport_number = NULL WHERE itinerary_token = ? AND status = 'approved'")
                ->execute([$token]);
            header('Location: itinerary.php?t=' . $token);
            exit;
        }

        $p = strtoupper(preg_replace('/\s+/', '', (string)($_POST['passport_number'] ?? '')));
        if (!preg_match('/^[A-Z0-9]{5,20}$/', $p)) {
            $formError = 'Please enter 5 to 20 letters or numbers, exactly as in your passport.';
        } else {
            $pdo->prepare("UPDATE registrations SET passport_number = ? WHERE itinerary_token = ? AND status = 'approved'")
                ->execute([$p, $token]);
            header('Location: itinerary.php?t=' . $token . '&saved=1'); // reload so a refresh does not resubmit
            exit;
        }
    } catch (Throwable $e) {
        error_log('itinerary.php (passport): ' . $e->getMessage());
        $formError = 'Sorry, we could not save it right now. Please try again later.';
    }
    $r['passport_number'] = null; // do not echo back an invalid entry
}

/* ---------- build the itinerary ---------- */

$fullName = trim($r['first_name'] . ' ' . $r['last_name']);
$d = emailData($env, $fullName, (string)$r['country'], (string)$r['location'], (string)$r['event_date']);
$d['passport_number'] = (string)($r['passport_number'] ?? '');

// Is the Dompdf library installed? (Composer "vendor" folder, or the plain "dompdf" folder uploaded from GitHub)
$autoload = null;
foreach ([
    __DIR__ . '/vendor/autoload.php',
    dirname(__DIR__) . '/vendor/autoload.php',
    __DIR__ . '/dompdf/autoload.inc.php',
    dirname(__DIR__) . '/dompdf/autoload.inc.php',
] as $candidate) {
    if (is_readable($candidate)) {
        $autoload = $candidate;
        break;
    }
}

/* ---------- PDF download (?pdf=1) ---------- */

if ($autoload !== null && isset($_GET['pdf'])) {
    try {
        $html = buildItineraryHtml($d);   // no passport box in the PDF
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
        // fall through to the normal page below
    }
}

/* ---------- the web page: letter + itinerary + passport box + download button ---------- */

$button = ($autoload !== null)
    ? '<a href="itinerary.php?t=' . $token . '&amp;pdf=1" class="bp-btn">Download PDF</a>'
    : '<button type="button" class="bp-btn" onclick="window.print()">Download as PDF</button>'
      . '<small>Choose &ldquo;Save as PDF&rdquo; in the window that opens</small>';

$d['show_form']    = $passportSupported;
$d['form_action']  = 'itinerary.php?t=' . $token;
$d['form_error']   = $formError;
$d['form_saved']   = isset($_GET['saved']) && $formError === '';
$d['toolbar_html'] = '<div class="bp-toolbar-inline">' . $button . '</div>';

$html = buildItineraryHtml($d);

// If there is no passport box (column not added yet), float the download button in the corner instead.
if (!$passportSupported) {
    $html = str_replace('</body>', '<div class="bp-toolbar-fixed">' . $button . '</div></body>', $html);
}

$css = '<style>'
    . '.bp-btn{display:inline-block;background:#F2B01E;color:#111111;border:0;border-radius:8px;padding:12px 22px;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:bold;cursor:pointer;text-decoration:none;box-shadow:0 2px 8px rgba(0,0,0,.25)}'
    . '.bp-toolbar-inline{text-align:center;font-family:Arial,Helvetica,sans-serif}'
    . '.bp-toolbar-inline small{display:block;margin-top:5px;font-size:11px;color:#333333}'
    . '.bp-toolbar-fixed{position:fixed;top:14px;right:14px;z-index:9999;text-align:center;font-family:Arial,Helvetica,sans-serif}'
    . '.bp-toolbar-fixed small{display:block;margin-top:5px;font-size:11px;color:#ffffff;text-shadow:0 1px 3px rgba(0,0,0,.8)}'
    . '@media print{.bp-toolbar-fixed,.bp-toolbar-inline{display:none!important}}'
    . '</style>';
$html = str_replace('</head>', $css . '</head>', $html);

header('Content-Type: text/html; charset=UTF-8');
echo $html;