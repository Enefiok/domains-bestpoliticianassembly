<?php
// Blocks anyone from opening this file directly in a browser
if (!defined('APP_MAIL')) {
    http_response_code(403);
    exit;
}

function h($s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function buildApplicantEmail(array $d): string
{
    // ===== EASY SETTINGS =====
    $accent = '#418DDF';   // header / footer blue
    $table  = '#5B8BD6';   // payment table blue

    // TODO: ask your boss for the correct amounts and deadlines for each event.
    // These are the London 2026 figures copied from the old email and are OUT OF DATE.
    $fullPayment = [
        ['FULL PAYMENT DEADLINE<br>05TH OCT, 2026', '$699', '$1199', '$1899'],
    ];
    $installments = [
        ['FIRST PAYMENT DEADLINE<br>05TH OCT, 2026', '-', '$699', '$999'],
        ['SECOND PAYMENT DEADLINE<br>20TH OCT, 2026', '-', '$500', '$900'],
    ];
    // =========================

    $brand    = h($d['brand']);
    $name     = h($d['name']);
    $location = h($d['location']);
    $date     = h($d['date']);
    $country  = h($d['country']);
    $year     = date('Y');

    $th = "border:1px solid $table;background:$table;color:#ffffff;font-size:11px;font-weight:bold;padding:10px 6px;text-align:center;";
    $tdLabel = "border:1px solid $table;font-size:10px;font-weight:bold;padding:8px 6px;text-align:left;width:28%;";
    $td = "border:1px solid $table;font-size:14px;font-weight:bold;padding:12px 6px;text-align:center;";

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
      <tr><td align="center" style="background:<?= $accent ?>;padding:30px 20px;">
        <img src="<?= h($d['logo_url']) ?>" alt="<?= $brand ?>" width="240" style="display:block;max-width:240px;height:auto;border:0;margin:0 auto 14px;">
        <h1 style="margin:0;font-size:26px;color:#ffffff;font-weight:bold;">Application Under Review!</h1>
      </td></tr>

      <!-- Body -->
      <tr><td style="padding:24px 20px 10px;font-size:14px;line-height:1.6;">
        <p style="margin:0 0 14px;">Dear <?= $name ?></p>
        <p style="margin:0 0 18px;">You have successfully submitted your registration form for the <strong><?= $brand ?> Conference</strong> to be held in <strong><?= $location ?></strong> from <strong><?= $date ?></strong>. However, your application is currently under review. The shortlisted applicants will be contacted shortly through email within the next 48 Hours.</p>

        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
          <tr><td style="border-left:4px solid <?= $accent ?>;background:#f4f9fc;padding:14px 16px;font-size:12px;line-height:1.6;">
            <p style="margin:0 0 10px;">In the meantime, we encourage you to personalize your photo and post on your social media accounts using our official Twibbon.</p>
            <p style="margin:0 0 10px;"><strong>Step 1</strong>: Download the Twibbon from the following link: <a href="<?= h($d['twibbon_url']) ?>" style="color:<?= $accent ?>;font-weight:bold;">Click here</a></p>
            <p style="margin:0 0 10px;"><strong>Step 2</strong>: Insert your photo in the downloaded twibbon and post it with the following caption:</p>
            <p style="margin:0 0 10px;font-style:italic;color:#444444;">“This is <?= $name ?> a potential diplomat of <?= $country ?> at <?= $brand ?> to be held in <?= $location ?> from <?= $date ?>. Join me and future diplomats from over 70 Nations across the globe in our journey to Master Diplomacy, Leadership and Negotiations. #bestpoliticians #impactdirectors”</p>
            <p style="margin:0;"><strong>Step 3</strong>: Tag our Instagram <strong>@bestpoliticians</strong> or Facebook <strong>@bestpoliticians</strong> and <strong>5 of your friends</strong> inviting them to join this powerful movement.</p>
          </td></tr>
        </table>
      </td></tr>

      <!-- Payment information -->
      <tr><td align="center" style="padding:20px 20px 10px;">
        <p style="margin:0 0 14px;font-family:'Times New Roman',serif;font-size:17px;font-weight:bold;color:#0b5bd3;">PLEASE TAKE A LOOK AT THE INFORMATION BELOW:</p>
        <p style="margin:0 0 18px;font-family:'Times New Roman',serif;font-size:17px;font-weight:bold;color:#0b5bd3;">TYPE OF PAYMENT</p>
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

      <!-- Closing -->
      <tr><td align="center" style="padding:20px 20px 28px;font-size:14px;line-height:1.7;">
        <p style="margin:0 0 16px;font-weight:bold;">Please wait for further information through email or Social media handles of <?= $brand ?>.</p>
        <p style="margin:0 0 12px;">More Details : <a href="<?= h($d['site_url']) ?>" style="color:#0b5bd3;font-weight:bold;"><?= h($d['site_url']) ?></a></p>
        <p style="margin:0 0 12px;">Facebook : <a href="<?= h($d['facebook']) ?>" style="color:#0b5bd3;font-weight:bold;">BestPoliticians</a></p>
        <p style="margin:0 0 16px;">Instagram : <a href="<?= h($d['instagram']) ?>" style="color:#0b5bd3;font-weight:bold;">@BestPoliticians</a></p>
        <p style="margin:0 0 16px;">Should you have any questions, feel free to reach us out at<br><a href="mailto:<?= h($d['contact_email']) ?>" style="color:#0b5bd3;font-weight:bold;"><?= h($d['contact_email']) ?></a> We will be happy to assist you.</p>
        <p style="margin:0 0 16px;font-weight:bold;">We look forward to meeting you in <?= $location ?>!</p>
        <p style="margin:0;">Once again, thank you for registering yourself as a part of this powerful Diplomatic Conference!</p>
      </td></tr>

      <!-- Footer -->
      <tr><td align="center" style="background:<?= $accent ?>;padding:20px;color:#ffffff;">
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