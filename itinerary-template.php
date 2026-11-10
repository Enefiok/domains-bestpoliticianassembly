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
 * Each day has a title, a date, and rows of [time, activity].
 * TODO: replace the sample rows with the real programme from your boss.
 */
function defaultItineraryProgram(): array
{
    return [
        [
            'title' => 'DAY 1',
            'date'  => 'DATE TO BE CONFIRMED',
            'rows'  => [
                ['09:00', 'Registration'],
                ['10:00', 'Opening session'],
                ['12:00', 'Break'],
                ['13:00', 'Session to be confirmed'],
            ],
        ],
        [
            'title' => 'DAY 2',
            'date'  => 'DATE TO BE CONFIRMED',
            'rows'  => [
                ['09:00', 'Session to be confirmed'],
                ['12:00', 'Break'],
                ['13:00', 'Session to be confirmed'],
                ['17:00', 'Closing'],
            ],
        ],
    ];
}

/*
 * Builds the itinerary page as HTML, with the company letterhead.
 * $d needs: brand, name, location, date, logo_url, site_url
 * $program is optional; it defaults to the list above.
 * Uses simple tables and fixed bands so it also converts cleanly to PDF
 * (for example with Dompdf).
 */
function buildItineraryHtml(array $d, ?array $program = null): string
{
    $program = $program ?? defaultItineraryProgram();
    $navy    = BP_NAVY;

    $brand    = h($d['brand']);
    $name     = h($d['name']);
    $location = h($d['location']);
    $date     = h($d['date']);
    $logo     = h($d['logo_url']);
    $site     = h($d['site_url']);

    ob_start();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Conference Itinerary</title>
<style>
  @page { margin: 0; }
  body { margin: 0; font-family: Arial, Helvetica, sans-serif; color: #111111; font-size: 13px; }
  .band-top { background: <?= $navy ?>; color: #ffffff; text-align: center; padding: 22px 0 18px; }
  .band-top img { height: 90px; }
  .band-top .title { font-size: 24px; font-weight: bold; letter-spacing: 2px; margin-top: 10px; }
  .band-top .rule { width: 340px; height: 2px; background: #ffffff; margin: 10px auto 0; }
  .band-bottom { background: <?= $navy ?>; color: #ffffff; text-align: center; padding: 18px 0; font-size: 12px; }
  .content { padding: 28px 48px 40px; }
  h2 { color: <?= $navy ?>; font-size: 18px; margin: 0 0 4px; text-align: center; }
  .sub { text-align: center; margin: 0 0 18px; }
  .who { margin: 0 0 22px; line-height: 1.7; }
  .day { margin: 0 0 6px; font-weight: bold; color: <?= $navy ?>; font-size: 14px; }
  table.prog { width: 100%; border-collapse: collapse; margin: 0 0 22px; }
  table.prog td { border: 1px solid <?= $navy ?>; padding: 8px 10px; vertical-align: top; }
  table.prog td.time { width: 90px; font-weight: bold; }
  table.prog tr.head td { background: <?= $navy ?>; color: #ffffff; font-weight: bold; }
</style>
</head>
<body>

  <div class="band-top">
    <img src="<?= $logo ?>" alt="<?= $brand ?>">
    <div class="title"><?= strtoupper($brand) ?></div>
    <div class="rule"></div>
  </div>

  <div class="content">
    <h2>CONFERENCE ITINERARY</h2>
    <p class="sub"><?= $brand ?> Conference &middot; <?= $location ?> &middot; <?= $date ?></p>

    <p class="who">
      <strong>PREPARED FOR:</strong> <?= $name ?><br>
      <strong>EVENT:</strong> <?= $brand ?> Conference<br>
      <strong>LOCATION:</strong> <?= $location ?><br>
      <strong>DATES:</strong> <?= $date ?>
    </p>

    <?php foreach ($program as $day): ?>
      <p class="day"><?= h($day['title']) ?> &mdash; <?= h($day['date']) ?></p>
      <table class="prog">
        <tr class="head"><td class="time">TIME</td><td>ACTIVITY</td></tr>
        <?php foreach ($day['rows'] as $row): ?>
        <tr>
          <td class="time"><?= h($row[0]) ?></td>
          <td><?= h($row[1]) ?></td>
        </tr>
        <?php endforeach; ?>
      </table>
    <?php endforeach; ?>
  </div>

  <div class="band-bottom">
    <?= $site ?>
  </div>

</body>
</html>
    <?php
    return (string)ob_get_clean();
}