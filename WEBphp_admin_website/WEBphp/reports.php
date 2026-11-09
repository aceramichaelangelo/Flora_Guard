<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/lib/analytics.php';
require_once __DIR__ . '/lib/barangay.php';

tg_require_login();

$records = tg_attach_barangays(tg_all_tree_records());
$counts = tg_conservation_counts($records);
$total = (int)$counts['total'];

$speciesTally = tg_tally($records, 'tg_record_species_label');

$locationTally = tg_tally($records, static function (array $r): string {
    $brgy = trim((string)($r['barangay'] ?? ''));
    if ($brgy === '') {
        return tg_record_lat_lng($r) === null ? 'No GPS' : 'Unresolved';
    }
    $town = trim((string)($r['municipality'] ?? ''));
    return $town !== '' ? $brgy . ', ' . $town : $brgy;
});

$review = ['approved' => 0, 'pending' => 0, 'reclassified' => 0, 'rejected' => 0];
foreach ($records as $r) {
    $review[tg_scan_review_status($r)]++;
}
$validated = $review['approved'] + $review['reclassified'];
$monitoring = (int)$counts['critical'] + (int)$counts['endangered'];

$monitoringRows = [
    ['label' => 'Validated', 'value' => $validated, 'tone' => 'validated', 'hint' => 'Approved or reclassified by a validator'],
    ['label' => 'Pending', 'value' => $review['pending'], 'tone' => 'pending', 'hint' => 'Awaiting validator review'],
    ['label' => 'Rejected', 'value' => $review['rejected'], 'tone' => 'rejected', 'hint' => 'Marked rejected by a validator'],
    ['label' => 'Monitoring', 'value' => $monitoring, 'tone' => 'monitoring', 'hint' => 'Critically endangered or endangered'],
];

/** Horizontal bar config for a label => count tally. */
$hbar = static fn (array $tally, string $color): array => [
    'labels' => array_map('strval', array_keys($tally)),
    'datasets' => [[
        'label' => 'Records',
        'data' => array_values($tally),
        'backgroundColor' => $color,
        'borderRadius' => 6,
        'maxBarThickness' => 26,
    ]],
];

$speciesChart = $hbar(array_slice($speciesTally, 0, 8, true), '#1b5e20');
$locationChart = $hbar(array_slice($locationTally, 0, 8, true), '#43a047');

$pieSlices = array_filter([
    'Vulnerable' => [(int)$counts['vulnerable'], '#ca8a04'],
    'Endangered' => [(int)$counts['endangered'], '#ea580c'],
    'Critically endangered' => [(int)$counts['critical'], '#dc2626'],
], static fn (array $s): bool => $s[0] > 0);
$pieChart = [
    'labels' => array_map(
        static fn (string $k, array $s): string => $k . ': ' . $s[0],
        array_keys($pieSlices),
        $pieSlices
    ),
    'datasets' => [[
        'data' => array_map(static fn (array $s): int => $s[0], array_values($pieSlices)),
        'backgroundColor' => array_map(static fn (array $s): string => $s[1], array_values($pieSlices)),
        'borderWidth' => 0,
        'hoverOffset' => 6,
    ]],
];

$chartJson = static fn (array $cfg): string => json_encode($cfg, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP);
$hbarOptions = '{indexAxis:"y",responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},'
    . 'scales:{x:{beginAtZero:true,ticks:{precision:0}},y:{grid:{display:false}}}}';

$pageTitle = 'Reports & Analytics';
$navActive = 'reports';
$extraScripts = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>'
    . '<script src="assets/js/chart_theme.js"></script>'
    . '<script>(function(){'
    . 'var S=' . $chartJson($speciesChart) . ';'
    . 'var L=' . $chartJson($locationChart) . ';'
    . 'var P=' . $chartJson($pieChart) . ';'
    . 'function draw(id,cfg){var el=document.getElementById(id);if(el&&window.Chart)new Chart(el,cfg);}'
    . 'function init(){'
    . 'draw("repSpecies",{type:"bar",data:S,options:' . $hbarOptions . '});'
    . 'draw("repLocation",{type:"bar",data:L,options:' . $hbarOptions . '});'
    . 'draw("repPie",{type:"pie",data:P,options:{responsive:true,maintainAspectRatio:false,'
    . 'plugins:{legend:{position:"bottom",labels:{usePointStyle:true,boxWidth:8,padding:14}}}}});'
    . '}'
    . 'if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",init);}else{init();}'
    . '})();</script>';

require __DIR__ . '/includes/layout_start.php';
?>

<div class="page-head">
    <div>
        <h1>Reports &amp; Analytics</h1>
        <p>Species detections, conservation status, and validation progress across tagged trees.</p>
    </div>
</div>

<div class="grid-4" style="margin-bottom:18px">
    <div class="card stat-card">
        <div class="lbl">Total Records</div>
        <div class="big"><?= $total ?></div>
    </div>
    <div class="card stat-card">
        <div class="lbl">Species</div>
        <div class="big"><?= count($speciesTally) ?></div>
    </div>
    <div class="card stat-card">
        <div class="lbl">Validated</div>
        <div class="big"><?= (int)$validated ?></div>
    </div>
    <div class="card stat-card critical">
        <div class="lbl">Monitoring</div>
        <div class="big"><?= (int)$monitoring ?></div>
    </div>
</div>

<div class="grid-2" style="margin-bottom:18px">
    <div class="card">
        <h2>Species Detection Overview</h2>
        <p class="sub">Tagged records per species</p>
        <?php if ($speciesTally === []) : ?>
            <p class="report-empty">No detections yet.</p>
        <?php else : ?>
            <div class="chart-wrap"><canvas id="repSpecies"></canvas></div>
        <?php endif; ?>
    </div>
    <div class="card">
        <h2>Conservation Status</h2>
        <p class="sub">Records by threat level</p>
        <?php if ($pieSlices === []) : ?>
            <p class="report-empty">No records yet.</p>
        <?php else : ?>
            <div class="chart-wrap"><canvas id="repPie"></canvas></div>
        <?php endif; ?>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <h2>Tree Records by Location</h2>
        <p class="sub">Tagged records per barangay</p>
        <?php if ($locationTally === []) : ?>
            <p class="report-empty">No records yet.</p>
        <?php else : ?>
            <div class="chart-wrap"><canvas id="repLocation"></canvas></div>
        <?php endif; ?>
    </div>
    <div class="card">
        <h2>Monitoring Status</h2>
        <p class="sub">Validation progress and trees needing monitoring</p>
        <ul class="report-status-list">
            <?php foreach ($monitoringRows as $row) : ?>
                <?php $pct = $total > 0 ? round($row['value'] / $total * 100) : 0; ?>
                <li class="report-status report-status--<?= h($row['tone']) ?>">
                    <div class="report-status__head">
                        <span class="report-status__label"><?= h($row['label']) ?></span>
                        <span class="report-status__value"><?= (int)$row['value'] ?></span>
                    </div>
                    <div class="report-status__bar" aria-hidden="true"><span style="width:<?= (int)$pct ?>%"></span></div>
                    <span class="report-status__hint"><?= h($row['hint']) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<?php
require __DIR__ . '/includes/layout_end.php';
