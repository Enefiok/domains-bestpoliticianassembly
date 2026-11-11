<?php
// Blocks anyone from opening this file directly in a browser
if (!defined('APP_MAIL')) {
    http_response_code(403);
    exit;
}

if (!defined('BP_NAVY')) {
    define('BP_NAVY', '#00005F');
}

if (!function_exists('h')) {
    function h($s): string
    {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}

/* =====================================================================
   THINGS YOU EDIT
   ===================================================================== */

// Venue for each city, shown in the Letter of Invitation.
// Leave a venue as '' to show the city name instead. Later, type the real hotel or hall,
// for example 'KUALA' => 'Hotel name, Kuala Lumpur'.
// The key is a word that appears in the registration location (any capitals).
function bpVenues(): array
{
    return [
        'KUALA'     => '',
        'BUCHAREST' => '',
        'BRUSSELS'  => '',
    ];
}

/*
 * The conference programme. Edit this list for each event.
 *
 * One entry per day:
 *   'title' => 'DAY 1'
 *   'date'  => 'Thursday 16 June'      (optional, leave '' to hide)
 *   'rows'  => list of rows (keep each day to about 10 rows so it fits one page)
 *
 * One row:
 *   'time'  => '09:00'
 *   'title' => 'Activity name'
 *   'desc'  => 'Optional small text under the name (use "\n" for a new line)'
 *   'basic' => true | false | null     true = tick, false = cross, null = blank
 *   'non'   => true | false | null     (Non-Accommodation package)
 *   'acc'   => true | false | null     (Accommodation package)
 *
 * TODO: replace the sample rows with the real programme from your boss.
 */
function defaultItineraryProgram(): array
{
    return [
        [
            'title' => 'DAY 1',
            'date'  => '',
            'rows'  => [
                ['time' => '11:00', 'title' => 'Arrival and Airport Assistance', 'desc' => 'TODO: replace with the real programme.', 'basic' => false, 'non' => false, 'acc' => true],
                ['time' => '15:00', 'title' => 'Hotel Check-In',                 'desc' => 'TODO: sample row.',                       'basic' => false, 'non' => false, 'acc' => true],
                ['time' => '18:00', 'title' => 'Opening Session',                'desc' => 'TODO: sample row.',                       'basic' => true,  'non' => true,  'acc' => true],
                ['time' => '20:00', 'title' => 'Welcome Dinner',                 'desc' => 'TODO: sample row.',                       'basic' => true,  'non' => true,  'acc' => true],
                ['time' => '22:00', 'title' => 'Closing of Day 1',               'desc' => '',                                        'basic' => null,  'non' => null,  'acc' => null],
            ],
        ],
        [
            'title' => 'DAY 2',
            'date'  => '',
            'rows'  => [
                ['time' => '07:00', 'title' => 'Breakfast',                      'desc' => '',                                        'basic' => false, 'non' => false, 'acc' => true],
                ['time' => '09:00', 'title' => 'Session One',                    'desc' => "TODO: sample row.\nSecond line of detail.", 'basic' => true,  'non' => true,  'acc' => true],
                ['time' => '12:30', 'title' => 'Lunch',                          'desc' => '',                                        'basic' => false, 'non' => true,  'acc' => true],
                ['time' => '14:00', 'title' => 'Session Two',                    'desc' => 'TODO: sample row.',                       'basic' => true,  'non' => true,  'acc' => true],
                ['time' => '18:00', 'title' => 'Closing of Day 2',               'desc' => '',                                        'basic' => null,  'non' => null,  'acc' => null],
            ],
        ],
        [
            'title' => 'DAY 3',
            'date'  => '',
            'rows'  => [
                ['time' => '09:00', 'title' => 'Closing Ceremony',               'desc' => 'TODO: sample row.',                       'basic' => true,  'non' => true,  'acc' => true],
                ['time' => '12:00', 'title' => 'Farewell Lunch',                 'desc' => '',                                        'basic' => false, 'non' => true,  'acc' => true],
                ['time' => '13:00', 'title' => 'Check-Out and Departure Transfer', 'desc' => 'TODO: sample row.',                     'basic' => false, 'non' => false, 'acc' => true],
            ],
        ],
    ];
}

/* =====================================================================
   HELPERS FOR THE LETTER (no need to edit)
   ===================================================================== */

// "BUCHAREST , ROMANIA" -> "Bucharest, Romania"; "16TH-19TH JUNE , 2027" -> "16th-19th June, 2027"
function bpTidy(string $s): string
{
    $s = strtolower(trim(preg_replace('/\s+/', ' ', $s)));
    $s = preg_replace('/\s+,/', ',', $s);
    return preg_replace_callback('/\b([a-z]{3,})\b/', function ($m) {
        return ucfirst($m[1]);
    }, $s);
}

// "24th-27 March, 2027" -> 4. Returns null if the dates cannot be read.
function bpEventDays(string $date): ?int
{
    if (preg_match('/(\d{1,2})\s*(?:st|nd|rd|th)?\s*[-\x{2013}]\s*(\d{1,2})\s*(?:st|nd|rd|th)?\b/iu', $date, $m)) {
        $days = (int)$m[2] - (int)$m[1] + 1;
        if ($days >= 1 && $days <= 31) {
            return $days;
        }
    }
    return null;
}

function bpVenueFor(string $location): string
{
    foreach (bpVenues() as $needle => $venue) {
        if (stripos($location, $needle) !== false && $venue !== '') {
            return $venue;
        }
    }
    return bpTidy($location); // no hotel/hall set yet, so show the city
}

/*
 * Builds the itinerary as HTML: first the Letter of Invitation, then one page per day,
 * all on the company letterhead.
 * $d needs: brand, name, location, date, logo_url, site_url
 * Optional: $d['manager_email'] (defaults to conferencemanager.logistics@bestpoliticianassembly.org)
 * $program is optional; it defaults to the list above.
 * The passport number is a blank line for the delegate to write in by hand.
 * Uses only tables and simple CSS so it also converts cleanly to PDF (Dompdf).
 */
function buildItineraryHtml(array $d, ?array $program = null): string
{
    $program = $program ?? defaultItineraryProgram();

    // ===== COLOURS (change here) =====
    $navy  = BP_NAVY;
    $gold  = '#F2B01E';   // TIME and Non-Accommodation headers
    $tick  = '#2F6FD1';   // tick colour
    $cross = '#C62828';   // cross colour
    // =================================

    $brand    = h($d['brand']);
    $brandCap = h(strtoupper((string)$d['brand']));
    $name     = h($d['name']);
    $logo     = h($d['logo_url']);
    $site     = h($d['site_url']);

    $rawLocation = (string)$d['location'];
    $rawDate     = (string)$d['date'];
    $location    = h(bpTidy($rawLocation));
    $date        = h(bpTidy($rawDate));
    $venue       = h(bpVenueFor($rawLocation));
    $days        = bpEventDays($rawDate);
    $duration    = $date . ($days !== null ? ' (' . $days . ' day' . ($days === 1 ? '' : 's') . ')' : '');

    $managerEmail = h($d['manager_email'] ?? 'conferencemanager.logistics@bestpoliticianassembly.org');

    // Passport number: shown if the delegate saved one, otherwise a blank line to write in by hand.
    $passport     = trim((string)($d['passport_number'] ?? ''));
    $passportCell = $passport !== '' ? h($passport) : '......................................................';

    // The passport box is only built for the web page (never for the PDF).
    $showForm    = !empty($d['show_form']);
    $formAction  = h($d['form_action'] ?? '');
    $formError   = h($d['form_error'] ?? '');
    $formSaved   = !empty($d['form_saved']);
    $toolbarHtml = (string)($d['toolbar_html'] ?? '');   // built by itinerary.php, trusted HTML

    // One mark cell: tick, cross or blank. $onDark = white tick on the navy column.
    $mark = function ($v, bool $onDark = false) use ($tick, $cross): string {
        if ($v === null) {
            return '&nbsp;';
        }
        if ($v) {
            $c = $onDark ? '#ffffff' : $tick;
            return '<span style="font-family:\'DejaVu Sans\',Arial,sans-serif;font-size:17px;font-weight:bold;color:' . $c . ';">&#10003;</span>';
        }
        $c = $onDark ? '#FF8A80' : $cross;
        return '<span style="font-family:\'DejaVu Sans\',Arial,sans-serif;font-size:17px;font-weight:bold;color:' . $c . ';">&#10007;</span>';
    };

    $lastIndex = count($program) - 1;

    ob_start();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Letter of Invitation and Itinerary</title>
<style>
  @page { margin: 0; }
  body { margin: 0; font-family: Arial, Helvetica, sans-serif; color: #111111; font-size: 13px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  .day { page-break-after: always; }
  .day.last { page-break-after: auto; }
  .letter { page-break-after: always; }
  .band-top { background: <?= $navy ?>; color: #ffffff; text-align: center; padding: 22px 0 22px; border-radius: 0 0 40px 40px; }
  .band-top img { height: 80px; }
  .band-top .brand { font-size: 22px; font-weight: bold; letter-spacing: 2px; margin-top: 8px; }
  .band-top .rule { width: 300px; height: 2px; background: #ffffff; margin: 8px auto 14px; }
  .band-top .title { font-size: 26px; font-weight: bold; letter-spacing: 1px; }
  .band-top .daytitle { font-size: 18px; margin-top: 2px; }
  .band-bottom { position: fixed; bottom: 0; left: 0; right: 0; background: <?= $navy ?>; color: #ffffff; text-align: center; padding: 14px 0; font-size: 11px; }
  .content { padding: 20px 40px 70px; }
  .who { text-align: center; font-size: 11px; line-height: 1.6; margin: 0 0 16px; color: #333333; }
  table.prog { width: 100%; border-collapse: collapse; }
  table.prog th { padding: 9px 6px; font-size: 11px; color: #ffffff; text-align: center; }
  table.prog td { padding: 9px 8px; vertical-align: middle; }
  td.time { width: 11%; text-align: center; font-weight: bold; font-size: 13px; }
  td.act  { width: 41%; border-right: 2px solid #111111; }
  td.mk   { width: 16%; text-align: center; }
  td.accom { width: 16%; text-align: center; background: <?= $navy ?>; }
  .atitle { font-size: 13px; font-weight: bold; color: <?= $navy ?>; }
  .adesc  { font-size: 10px; color: #333333; margin-top: 2px; line-height: 1.4; }
  .note { text-align: center; font-size: 12px; margin: 26px 20px 0; line-height: 1.5; }

  /* Letter of Invitation */
  .lbody { font-size: 12px; line-height: 1.5; }
  .lbody p { margin: 0 0 9px; text-align: justify; }
  .lnotice { text-align: center !important; font-weight: bold; font-size: 11px; margin: 0 0 14px !important; }
  table.details { border-collapse: collapse; margin: 0 0 11px; }
  table.details td { padding: 2px 14px 2px 0; vertical-align: top; font-size: 12px; }
  table.details td.label { font-weight: bold; white-space: nowrap; }
  ul.docs { margin: 0 0 9px; padding-left: 22px; }
  @media print { .bp-noprint { display: none !important; } }
</style>
</head>
<body>

<?php if ($showForm): ?>
  <!-- ===================== PASSPORT BOX (web page only, hidden when printing) ===================== -->
  <div class="bp-noprint" style="background:#fff8e1;border-bottom:1px solid #e0c97a;padding:14px 40px;font-size:12px;line-height:1.5;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
      <td style="vertical-align:top;">
        <strong style="font-size:13px;">Passport number (optional)</strong><br>
        Enter it if you want it printed on your letter. It is only used on your letter, and you can remove it at any time.
        <?php if ($formError !== ''): ?>
        <div style="color:#b00020;font-weight:bold;margin-top:5px;"><?= $formError ?></div>
        <?php endif; ?>
        <?php if ($formSaved): ?>
        <div style="color:#1b6e2d;font-weight:bold;margin-top:5px;">Saved. It now appears on your letter below.</div>
        <?php endif; ?>
        <form method="post" action="<?= $formAction ?>" style="margin:8px 0 0;">
          <input type="text" name="passport_number" value="<?= h($passport) ?>" maxlength="20" autocomplete="off" placeholder="e.g. A12345678"
                 style="padding:9px 10px;font-size:13px;border:1px solid #999999;border-radius:6px;width:200px;">
          <button type="submit" style="background:<?= $navy ?>;color:#ffffff;border:0;border-radius:6px;padding:10px 18px;font-size:13px;font-weight:bold;cursor:pointer;">Save</button>
          <?php if ($passport !== ''): ?>
          <button type="submit" name="remove" value="1" style="background:#ffffff;color:#b00020;border:1px solid #b00020;border-radius:6px;padding:9px 16px;font-size:13px;cursor:pointer;">Remove</button>
          <?php endif; ?>
        </form>
      </td>
      <td align="right" style="vertical-align:top;white-space:nowrap;padding-left:20px;"><?= $toolbarHtml ?></td>
    </tr></table>
  </div>
<?php endif; ?>

  <!-- ===================== LETTER OF INVITATION ===================== -->
  <div class="letter">
    <div class="band-top">
      <img src="<?= $logo ?>" alt="<?= $brand ?>">
      <div class="brand"><?= $brandCap ?></div>
      <div class="rule"></div>
      <div class="title">LETTER OF INVITATION</div>
    </div>

    <div class="content">
      <div class="lbody">
        <p class="lnotice">(NOT TO BE USED AS VISA SUPPORT OR INVITATION LETTER)</p>

        <p>Dear <?= $name ?>,</p>

        <p>We are grateful to inform you that you have been shortlisted to join the <?= $brand ?>, to be held from <strong><?= $date ?></strong> in <strong><?= $location ?></strong>.</p>

        <p>The <?= $brand ?> is an educational assembly of the real-world political organization which engages youths from across the globe, helping them to learn more about the principles of nations, their political beliefs, tactics and democracy, although it is not directly affiliated with any political party.</p>

        <p>The <?= $brand ?> provides participants with a real-world political and democratic setting of international crisis management, where they must navigate complex scenarios that go beyond textbook strategic thinking as future leaders.</p>

        <table class="details">
          <tr><td class="label">Name:</td><td><?= $name ?></td></tr>
          <tr><td class="label">Passport number:</td><td><?= $passportCell ?></td></tr>
          <tr><td class="label">Duration of stay:</td><td><?= $duration ?></td></tr>
          <tr><td class="label">Venue:</td><td><?= $venue ?></td></tr>
          <tr><td class="label">Destination:</td><td><?= $location ?></td></tr>
        </table>

        <p>Upon confirmation of this email, you are requested to proceed with your payment to enable your participation.</p>

        <p>If the <?= $brand ?> team will be responsible for your trip, a signed letter will be attached.</p>

        <p>The committee will provide accommodation, including meals, local transport from the airports, T-shirts and a tour guide.</p>

        <p>Before departure, please ensure you carry the following mandatory documents with you:</p>
        <ul class="docs">
          <li>Valid Passport</li>
          <li>Police Character Certificate</li>
          <li>Valid Visa</li>
          <li>Air tickets</li>
        </ul>

        <p>This letter does not guarantee a visa or entry into the host country.</p>

        <p>We would love to thank you for your kind cooperation and attention. We look forward to seeing you in <?= $location ?>.</p>

        <p style="margin-bottom:3px;">Regards,</p>
        <p style="margin-bottom:2px;"><strong>Conference Manager</strong></p>
        <p style="margin-bottom:2px;"><?= $managerEmail ?></p>
        <p style="margin-bottom:0;"><?= $brand ?></p>
      </div>
    </div>
  </div>

  <!-- ===================== ITINERARY, ONE PAGE PER DAY ===================== -->
<?php foreach ($program as $i => $day): ?>
  <div class="day<?= $i === $lastIndex ? ' last' : '' ?>">

    <div class="band-top">
      <img src="<?= $logo ?>" alt="<?= $brand ?>">
      <div class="brand"><?= $brandCap ?></div>
      <div class="rule"></div>
      <div class="title">DETAILED ITINERARY</div>
      <div class="daytitle"><?= h($day['title'] ?? '') ?><?= !empty($day['date']) ? ' &middot; ' . h($day['date']) : '' ?></div>
    </div>

    <div class="content">
      <p class="who">
        <strong>PREPARED FOR:</strong> <?= $name ?> &nbsp;&middot;&nbsp;
        <strong>EVENT:</strong> <?= $brand ?> Conference<br>
        <strong>LOCATION:</strong> <?= $location ?> &nbsp;&middot;&nbsp;
        <strong>DATES:</strong> <?= $date ?>
      </p>

      <table class="prog">
        <tr>
          <th style="background:<?= $gold ?>;color:#111111;">TIME</th>
          <th style="background:<?= $navy ?>;">ACTIVITY</th>
          <th style="background:<?= $navy ?>;">Basic</th>
          <th style="background:<?= $gold ?>;color:#111111;">Non-<br>Accommodation</th>
          <th style="background:<?= $navy ?>;">Accommodation</th>
        </tr>
        <?php foreach (($day['rows'] ?? []) as $n => $row): ?>
        <?php $bg = ($n % 2 === 0) ? '#f2f2f2' : '#e2e2e2'; ?>
        <tr style="background:<?= $bg ?>;">
          <td class="time"><?= h($row['time'] ?? '') ?></td>
          <td class="act">
            <div class="atitle"><?= h($row['title'] ?? '') ?></div>
            <?php if (!empty($row['desc'])): ?>
            <div class="adesc"><?= nl2br(h($row['desc'])) ?></div>
            <?php endif; ?>
          </td>
          <td class="mk"><?= $mark($row['basic'] ?? null) ?></td>
          <td class="mk"><?= $mark($row['non'] ?? null) ?></td>
          <td class="accom"><?= $mark($row['acc'] ?? null, true) ?></td>
        </tr>
        <?php endforeach; ?>
      </table>

      <?php if ($i === $lastIndex): ?>
      <p class="note"><strong>Note:</strong> This itinerary is provisional. The organisers may update it where needed, and any changes will be sent to participants by email.</p>
      <?php endif; ?>
    </div>

  </div>
<?php endforeach; ?>

  <div class="band-bottom">
    <?= $site ?> &nbsp;&middot;&nbsp; &ldquo;Master Diplomacy, Leadership and Negotiations&rdquo;
  </div>

</body>
</html>
    <?php
    return (string)ob_get_clean();
}