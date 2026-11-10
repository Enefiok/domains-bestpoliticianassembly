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

/*
 * Builds the itinerary as HTML, one page per day, with the company letterhead.
 * $d needs: brand, name, location, date, logo_url, site_url
 * $program is optional; it defaults to the list above.
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
    $location = h($d['location']);
    $date     = h($d['date']);
    $logo     = h($d['logo_url']);
    $site     = h($d['site_url']);

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
<title>Detailed Itinerary</title>
<style>
  @page { margin: 0; }
  body { margin: 0; font-family: Arial, Helvetica, sans-serif; color: #111111; font-size: 13px; }
  .day { page-break-after: always; }
  .day.last { page-break-after: auto; }
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
</style>
</head>
<body>

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