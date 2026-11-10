<?php
// Blocks anyone from opening this file directly in a browser
if (!defined('APP_MAIL')) {
    http_response_code(403);
    exit;
}

// ===== COMPANY COLOR (change it here and both emails update) =====
// Deep blue taken from the Best Politician Assembly letterhead.
if (!defined('BP_NAVY')) {
    define('BP_NAVY', '#00005F');
}
// =================================================================

function h($s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// Picks the payment page for the registrant's location.
// Override: set $d['payment_url'] to a full URL and it is used as is.
function bpPaymentUrl(array $d): string
{
    if (!empty($d['payment_url'])) {
        return (string)$d['payment_url'];
    }

    $pages = [
        'KUALA'     => 'payment-kuala.html',
        'BUCHAREST' => 'payment-bucharest.html',
        'BRUSSELS'  => 'payment-brussels.html',
    ];
    $page = 'payment.html'; // fallback
    $loc  = (string)($d['location'] ?? '');
    foreach ($pages as $needle => $file) {
        if (stripos($loc, $needle) !== false) {
            $page = $file;
            break;
        }
    }
    return rtrim((string)($d['site_url'] ?? ''), '/') . '/' . $page;
}

// Email-safe "bulletproof" button (table + filled cell + padded link).
// Outlook ignores border-radius and shows square corners, which is fine.
function bpButtonHtml(string $url, string $label, string $color): string
{
    ob_start();
    ?>
        <table role="presentation" cellpadding="0" cellspacing="0" style="border-collapse:separate;margin:0 auto;">
          <tr>
            <td align="center" bgcolor="<?= $color ?>" style="background:<?= $color ?>;border-radius:8px;">
              <a href="<?= h($url) ?>" style="display:inline-block;padding:11px 30px;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:bold;color:#ffffff;text-decoration:none;border-radius:8px;"><?= h($label) ?></a>
            </td>
          </tr>
        </table>
    <?php
    return (string)ob_get_clean();
}

// Payment heading + both tables. Used by BOTH emails.
function paymentSectionHtml(string $table): string
{
    // TODO: ask your boss for the correct amounts and deadlines for each event.
    // These are the London 2026 figures copied from the old email and are OUT OF DATE.
    $fullPayment = [
        ['FULL PAYMENT DEADLINE<br>05TH OCT, 2026', '$699', '$1199', '$1899'],
    ];
    $installments = [
        ['FIRST PAYMENT DEADLINE<br>05TH OCT, 2026', '-', '$699', '$999'],
        ['SECOND PAYMENT DEADLINE<br>20TH OCT, 2026', '-', '$500', '$900'],
    ];

    $th = "border:1px solid $table;background:$table;color:#ffffff;font-size:11px;font-weight:bold;padding:10px 6px;text-align:center;";
    $tdLabel = "border:1px solid $table;font-size:10px;font-weight:bold;padding:8px 6px;text-align:left;width:28%;";
    $td = "border:1px solid $table;font-size:14px;font-weight:bold;padding:12px 6px;text-align:center;";

    ob_start();
    ?>
      <tr><td align="center" style="padding:20px 20px 10px;">
        <p style="margin:0 0 14px;font-family:'Times New Roman',serif;font-size:17px;font-weight:bold;color:<?= $table ?>;">PLEASE TAKE A LOOK AT THE INFORMATION BELOW:</p>
        <p style="margin:0 0 18px;font-family:'Times New Roman',serif;font-size:17px;font-weight:bold;color:<?= $table ?>;">TYPE OF PAYMENT</p>
        <p style="margin:0;font-size:18px;font-weight:bold;color:<?= $table ?>;">EARLY BIRD</p>
        <p style="margin:0 0 10px;font-size:15px;font-weight:bold;color:<?= $table ?>;">FULL PAYMENT METHOD</p>

        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
          <tr>
            <td style="<?= $th ?>">PAYMENT PERIOD</td>
            <td style="<?= $th ?>">BASIC</td>
            <td style="<?= $th ?>">NON-ACCOMMODATION</td>
            <td style="<?= $th ?>">ACCOMMODATION</td>
          </tr>
          <?php foreach ($fullPayment as $row): ?>
          <tr>
            <td style="<?= $tdLabel ?>"><?= $row[0] ?></td>
            <td style="<?= $td ?>"><?= h($row[1]) ?></td>
            <td style="<?= $td ?>"><?= h($row[2]) ?></td>
            <td style="<?= $td ?>"><?= h($row[3]) ?></td>
          </tr>
          <?php endforeach; ?>
        </table>

        <p style="margin:22px 0 10px;font-size:15px;font-weight:bold;color:<?= $table ?>;">INSTALLMENT METHOD</p>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
          <?php foreach ($installments as $row): ?>
          <tr>
            <td style="<?= $tdLabel ?>"><?= $row[0] ?></td>
            <td style="<?= $td ?>"><?= h($row[1]) ?></td>
            <td style="<?= $td ?>"><?= h($row[2]) ?></td>
            <td style="<?= $td ?>"><?= h($row[3]) ?></td>
          </tr>
          <?php endforeach; ?>
        </table>
      </td></tr>
    <?php
    return (string)ob_get_clean();
}

/* =====================================================================
   EMAIL 1: "Application Under Review" (sent right after registering)
   Same as before, with the Twibbon block removed.
   ===================================================================== */
function buildApplicantEmail(array $d): string
{
    // ===== EASY SETTINGS =====
    $accent = BP_NAVY;   // header / footer
    $table  = BP_NAVY;   // payment tables, headings
    $link   = BP_NAVY;   // links
    // =========================

    $brand    = h($d['brand']);
    $name     = h($d['name']);
    $location = h($d['location']);
    $date     = h($d['date']);
    $year     = date('Y');

    ob_start();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Your Registration has been Received</title>
</head>
<body style="margin:0;padding:0;background:#dbe7f0;font-family:Arial,Helvetica,sans-serif;color:#111111;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#dbe7f0;">
<tr><td align="center" style="padding:30px 10px;">

  <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#eef4fa;border-radius:16px;">
  <tr><td style="padding:16px;">

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-radius:12px;overflow:hidden;background:#ffffff;">

      <!-- Header -->
      <tr><td align="center" bgcolor="<?= $accent ?>" style="background:<?= $accent ?>;padding:30px 20px;">
        <img src="<?= h($d['logo_url']) ?>" alt="<?= $brand ?>" width="240" style="display:block;max-width:240px;height:auto;border:0;margin:0 auto 14px;">
        <h1 style="margin:0;font-size:26px;color:#ffffff;font-weight:bold;">Application Under Review!</h1>
      </td></tr>

      <!-- Body -->
      <tr><td style="padding:24px 20px 10px;font-size:14px;line-height:1.6;">
        <p style="margin:0 0 14px;">Dear <?= $name ?></p>
        <p style="margin:0 0 18px;">You have successfully submitted your registration form for the <strong><?= $brand ?> Conference</strong> to be held in <strong><?= $location ?></strong> from <strong><?= $date ?></strong>. However, your application is currently under review. The shortlisted applicants will be contacted shortly through email within the next 48 Hours.</p>
      </td></tr>

      <!-- Payment information (delete this line if you want it only in the approval email) -->
      <?= paymentSectionHtml($table) ?>

      <!-- Closing -->
      <tr><td align="center" style="padding:20px 20px 28px;font-size:14px;line-height:1.7;">
        <p style="margin:0 0 16px;font-weight:bold;">Please wait for further information through email or Social media handles of <?= $brand ?>.</p>
        <p style="margin:0 0 12px;">More Details : <a href="<?= h($d['site_url']) ?>" style="color:<?= $link ?>;font-weight:bold;"><?= h($d['site_url']) ?></a></p>
        <p style="margin:0 0 12px;">Facebook : <a href="<?= h($d['facebook']) ?>" style="color:<?= $link ?>;font-weight:bold;">BestPoliticians</a></p>
        <p style="margin:0 0 12px;">Instagram : <a href="<?= h($d['instagram']) ?>" style="color:<?= $link ?>;font-weight:bold;">@BestPoliticians</a></p>
        <?php if (!empty($d['tiktok'])): ?>
        <p style="margin:0 0 16px;">TikTok : <a href="<?= h($d['tiktok']) ?>" style="color:<?= $link ?>;font-weight:bold;">@BestPoliticians</a></p>
        <?php endif; ?>
        <p style="margin:0 0 16px;">Should you have any questions, feel free to reach us out at<br><a href="mailto:<?= h($d['contact_email']) ?>" style="color:<?= $link ?>;font-weight:bold;"><?= h($d['contact_email']) ?></a> We will be happy to assist you.</p>
        <p style="margin:0 0 16px;font-weight:bold;">We look forward to meeting you in <?= $location ?>!</p>
        <p style="margin:0;">Once again, thank you for registering yourself as a part of this powerful Diplomatic Conference!</p>
      </td></tr>

      <!-- Footer -->
      <tr><td align="center" bgcolor="<?= $accent ?>" style="background:<?= $accent ?>;padding:20px;color:#ffffff;">
        <p style="margin:0 0 8px;font-size:11px;font-weight:bold;">© <?= $year ?> <?= $brand ?>. All rights reserved.</p>
        <p style="margin:0;font-size:11px;font-weight:bold;font-style:italic;">“Master Diplomacy, Leadership and Negotiations”</p>
      </td></tr>

    </table>
  </td></tr>
  </table>

</td></tr>
</table>
</body>
</html>
    <?php
    return (string)ob_get_clean();
}

/* =====================================================================
   EMAIL 2: "Registration Approval" (auto after 24h, or when admin approves)
   Terms text lives in terms-content.php (edit it there, not here).
   ===================================================================== */
function buildApprovalEmail(array $d): string
{
    // ===== EASY SETTINGS =====
    $accent = BP_NAVY;
    $table  = BP_NAVY;
    $link   = BP_NAVY;

    // Terms introduction (two short paragraphs shown above section 1).
    // TODO: replace with the real wording from your boss.
    $termsIntro = [
        'These Terms and Conditions explain the rules for taking part in the conference. By registering, making a payment or attending, you confirm that you have read and accepted them, together with our Privacy Policy. TODO: add the full legal name of the organising company.',
        'We may update these terms from time to time. The latest version is always the one published on our website.',
    ];
    // =========================

    $brand    = h($d['brand']);
    $name     = h($d['name']);
    $location = h($d['location']);
    $date     = h($d['date']);
    $year     = date('Y');

    $siteUrl    = rtrim((string)($d['site_url'] ?? ''), '/');
    $paymentUrl = bpPaymentUrl($d);

    // Terms sections come from terms-content.php
    $termsFile     = __DIR__ . '/terms-content.php';
    $termsSections = is_file($termsFile) ? require $termsFile : [];
    if (!is_array($termsSections)) {
        $termsSections = [];
    }

    // "{click here}" inside any clause becomes a link to the visa page
    $visaUrl  = !empty($d['visa_url']) ? (string)$d['visa_url'] : ($siteUrl . '/get-visa.html');
    $visaLink = '<a href="' . h($visaUrl) . '" style="color:' . $link . ';font-weight:bold;">click here</a>';
    $termsText = function ($t) use ($visaLink): string {
        return str_replace('{click here}', $visaLink, h($t));
    };

    ob_start();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Registration Approved</title>
</head>
<body style="margin:0;padding:0;background:#dbe7f0;font-family:Arial,Helvetica,sans-serif;color:#111111;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#dbe7f0;">
<tr><td align="center" style="padding:30px 10px;">

  <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#eef4fa;border-radius:16px;">
  <tr><td style="padding:16px;">

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-radius:12px;overflow:hidden;background:#ffffff;">

      <!-- Header: logo, CONGRATULATIONS, name -->
      <tr><td align="center" bgcolor="<?= $accent ?>" style="background:<?= $accent ?>;padding:30px 20px;">
        <img src="<?= h($d['logo_url']) ?>" alt="<?= $brand ?>" width="240" style="display:block;max-width:240px;height:auto;border:0;margin:0 auto 14px;">
        <h1 style="margin:0 0 8px;font-size:24px;color:#ffffff;font-weight:bold;">CONGRATULATIONS!</h1>
        <p style="margin:0;font-size:22px;color:#ffffff;font-weight:bold;"><?= $name ?></p>
      </td></tr>

      <!-- First step completed -->
      <tr><td align="center" style="padding:22px 20px 14px;font-size:13px;line-height:1.6;">
        <p style="margin:0;">You have successfully completed the first step of Delegate Registration for the <strong><?= $brand ?> Conference</strong> to be held in <strong><?= $location ?></strong> from <strong><?= $date ?></strong>.</p>
      </td></tr>

      <?php if (!empty($d['itinerary_url'])): ?>
      <!-- Itinerary button (only when an itinerary URL exists) -->
      <tr><td align="center" style="padding:0 20px 18px;">
        <?= bpButtonHtml((string)$d['itinerary_url'], 'DOWNLOAD ITINERARY', $accent) ?>
      </td></tr>
      <?php endif; ?>

      <!-- Next step: payment -->
      <tr><td style="padding:6px 20px 14px;font-size:13px;line-height:1.6;">
        <p style="margin:0 0 10px;font-size:17px;font-weight:bold;text-align:center;color:<?= $table ?>;">Next Step: Payment</p>
        <p style="margin:0;text-align:justify;">We are pleased to inform you that your registration has been approved and your seat is reserved temporarily for the Conference. Please complete your payment before the stated deadlines to confirm your seat.</p>
      </td></tr>

      <!-- Complete payment button -->
      <tr><td align="center" style="padding:4px 20px 14px;">
        <?= bpButtonHtml($paymentUrl, 'COMPLETE PAYMENT', $accent) ?>
      </td></tr>

      <?php
      /* TODO: add the Visa Invitation Letter line here ONLY once the company confirms it really issues one.
         Layout: a small (12px) paragraph in its own row, directly under the COMPLETE PAYMENT button. */
      ?>

      <!-- Payment tables -->
      <?= paymentSectionHtml($table) ?>

      <!-- Terms and conditions -->
      <tr><td style="padding:24px 20px 10px;font-size:12px;line-height:1.6;color:#111111;text-align:justify;">
        <p style="margin:0 0 12px;font-size:20px;font-weight:bold;text-align:center;color:<?= $table ?>;">TERMS AND CONDITIONS</p>
        <?php foreach ($termsIntro as $para): ?>
        <p style="margin:0 0 12px;font-size:12px;text-align:justify;"><?= h($para) ?></p>
        <?php endforeach; ?>

        <?php foreach ($termsSections as $section): ?>
        <p style="margin:16px 0 6px;font-size:13px;font-weight:bold;text-align:left;color:<?= $table ?>;"><?= h($section['title'] ?? '') ?></p>
          <?php foreach (($section['clauses'] ?? []) as $ci => $clause): ?>
            <?php
              $clauseTitle = (string)($clause[0] ?? '');
              $clauseText  = (string)($clause[1] ?? '');
              $bullets     = (isset($clause[2]) && is_array($clause[2])) ? $clause[2] : [];
              // Clause number = section number (from its title) + position, e.g. "1.1"
              $secNo = (int)($section['title'] ?? 0);
              $label = ($secNo > 0 ? $secNo . '.' . ($ci + 1) . ' ' : '') . $clauseTitle;
            ?>
        <p style="margin:0 0 6px;font-size:12px;text-align:justify;"><strong><?= h($label) ?></strong> <?= $termsText($clauseText) ?></p>
            <?php if ($bullets): ?>
        <ul style="margin:0 0 8px;padding-left:20px;font-size:12px;text-align:justify;">
              <?php foreach ($bullets as $b): ?>
          <li style="margin:0 0 4px;"><?= $termsText($b) ?></li>
              <?php endforeach; ?>
        </ul>
            <?php endif; ?>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </td></tr>

      <!-- Closing -->
      <tr><td align="center" style="padding:14px 20px 28px;font-size:13px;line-height:1.7;">
        <p style="margin:0 0 12px;">Full terms: <a href="<?= h($siteUrl) ?>/terms-and-conditions.html" style="color:<?= $link ?>;font-weight:bold;"><?= h($siteUrl) ?>/terms-and-conditions.html</a></p>
        <p style="margin:0 0 12px;">More Details : <a href="<?= h($d['site_url']) ?>" style="color:<?= $link ?>;font-weight:bold;"><?= h($d['site_url']) ?></a></p>
        <p style="margin:0 0 12px;">Facebook : <a href="<?= h($d['facebook']) ?>" style="color:<?= $link ?>;font-weight:bold;">BestPoliticians</a></p>
        <p style="margin:0 0 12px;">Instagram : <a href="<?= h($d['instagram']) ?>" style="color:<?= $link ?>;font-weight:bold;">@BestPoliticians</a></p>
        <?php if (!empty($d['tiktok'])): ?>
        <p style="margin:0 0 16px;">TikTok : <a href="<?= h($d['tiktok']) ?>" style="color:<?= $link ?>;font-weight:bold;">@BestPoliticians</a></p>
        <?php endif; ?>
        <p style="margin:0 0 14px;">Should you have any questions, feel free to reach us out at<br><a href="mailto:<?= h($d['contact_email']) ?>" style="color:<?= $link ?>;font-weight:bold;"><?= h($d['contact_email']) ?></a> We will be happy to assist you.</p>
        <p style="margin:0 0 14px;font-weight:bold;">We look forward to meeting you in <?= $location ?>!</p>
        <p style="margin:0;font-weight:bold;">Once again, Congratulations on becoming a vital part of this powerful Diplomatic Conference!</p>
      </td></tr>

      <!-- Footer -->
      <tr><td align="center" bgcolor="<?= $accent ?>" style="background:<?= $accent ?>;padding:20px;color:#ffffff;">
        <p style="margin:0 0 8px;font-size:11px;font-weight:bold;">© <?= $year ?> <?= $brand ?>. All rights reserved.</p>
        <table role="presentation" width="120" cellpadding="0" cellspacing="0" style="margin:0 auto 8px;"><tr><td height="1" style="height:1px;line-height:1px;font-size:1px;background:#ffffff;">&nbsp;</td></tr></table>
        <p style="margin:0;font-size:11px;font-weight:bold;font-style:italic;">“Master Diplomacy, Leadership and Negotiations”</p>
      </td></tr>

    </table>
  </td></tr>
  </table>

</td></tr>
</table>
</body>
</html>
    <?php
    return (string)ob_get_clean();
}