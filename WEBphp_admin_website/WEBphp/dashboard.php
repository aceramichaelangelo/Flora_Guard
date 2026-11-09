<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/lib/barangay.php';

tg_require_login();

$records = tg_attach_barangays(tg_all_tree_records());
$users = tg_all_users();
$counts = tg_conservation_counts($records);

$speciesKeys = [];
foreach ($records as $r) {
    $k = trim((string)($r['scientific'] ?? ''));
    if ($k === '' || $k === '-') {
        $k = (string)($r['title'] ?? 'Unknown');
    }
    $speciesKeys[$k] = true;
}
$speciesCount = count($speciesKeys);

$locKeys = [];
foreach ($records as $r) {
    $brgy = trim((string)($r['barangay'] ?? ''));
    if ($brgy !== '') {
        $locKeys[$brgy . '|' . trim((string)($r['municipality'] ?? ''))] = true;
    }
}
$locCount = count($locKeys);

$now = new DateTimeImmutable('now');
$bucket = [];
for ($i = 2; $i >= 0; $i--) {
    $d = $now->modify("-{$i} months");
    $bucket[$d->format('Y-m')] = ['label' => $d->format('M'), 'count' => 0];
}
foreach ($records as $r) {
    $ms = (int)($r['createdAt'] ?? 0);
    if ($ms <= 0) {
        continue;
    }
    $dt = (new DateTimeImmutable())->setTimestamp((int)floor($ms / 1000));
    $key = $dt->format('Y-m');
    if (isset($bucket[$key])) {
        $bucket[$key]['count']++;
    }
}
$barLabels = array_values(array_map(static fn ($x) => $x['label'], $bucket));
$barValues = array_values(array_map(static fn ($x) => (int)$x['count'], $bucket));

$recent = array_slice($records, 0, 5);

$mapPoints = [];
foreach ($records as $r) {
    $ll = tg_record_lat_lng($r);
    if ($ll === null) {
        continue;
    }
    $mapPoints[] = [
        'lat' => $ll[0],
        'lng' => $ll[1],
        'title' => (string)($r['title'] ?? 'Tree'),
        'scientific' => (string)($r['scientific'] ?? ''),
    ];
}
$mappedCount = count($mapPoints);

$mapboxToken = tg_mapbox_access_token();
$hasMapbox = strncmp(trim($mapboxToken), 'pk.', 3) === 0;
$mapPointsJson = json_encode($mapPoints, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$mapTokenJson = json_encode($mapboxToken, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

$pageTitle = 'Dashboard';
$navActive = 'dashboard';

$barCfg = [
    'labels' => $barLabels,
    'datasets' => [[
        'label' => 'Trees',
        'data' => $barValues,
        'backgroundColor' => '#1b7a3b',
        'hoverBackgroundColor' => '#166534',
        'borderRadius' => 8,
        'maxBarThickness' => 36,
    ]],
];
$pieCfg = [
    'labels' => ['Endangered', 'Critically endangered', 'Vulnerable'],
    'datasets' => [[
        'data' => [
            (int)$counts['endangered'],
            (int)$counts['critical'],
            (int)$counts['vulnerable'],
        ],
        'backgroundColor' => ['#ea580c', '#dc2626', '#ca8a04'],
        'borderWidth' => 0,
        'hoverOffset' => 6,
    ]],
];
$chartJson = static fn (array $cfg): string => json_encode($cfg, JSON_THROW_ON_ERROR);
$extraScripts = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>'
    . '<script src="assets/js/chart_theme.js"></script>'
    . '<script>(function(){var BAR=' . $chartJson($barCfg)
    . ';var PIE=' . $chartJson($pieCfg) . ';'
    . 'function initCharts(){'
    . 'var barEl=document.getElementById("chartBar");'
    . 'var pieEl=document.getElementById("chartPie");'
    . 'if(!barEl||!pieEl||typeof Chart==="undefined")return;'
    . 'new Chart(barEl,{type:"bar",data:BAR,options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{precision:0}}}}});'
    . 'new Chart(pieEl,{type:"pie",data:PIE,options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:"bottom",labels:{usePointStyle:true,boxWidth:8,padding:14}}}}});'
    . '}'
    . 'if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",initCharts);}'
    . 'else{initCharts();}'
    . '})();</script>';

if ($hasMapbox) {
    $extraScripts .= '<link href="assets/vendor/mapbox-gl/mapbox-gl.css" rel="stylesheet">'
        . '<script src="assets/vendor/mapbox-gl/mapbox-gl.js"></script>'
        . '<script>window.TG_DASHBOARD_MAP={token:' . $mapTokenJson . ',points:' . $mapPointsJson . '};</script>'
        . '<script src="assets/js/dashboard_map_preview.js"></script>';
}

require __DIR__ . '/includes/layout_start.php';
?>

<div class="page-head">
    <div>
        <h1>Dashboard</h1>
        <p>Overview of protected trees and field activity.</p>
    </div>
</div>

<div class="grid-4" style="margin-bottom:18px">
    <div class="card stat-card">
        <div class="lbl">Total Trees</div>
        <div class="big"><?= (int)$counts['total'] ?></div>
    </div>
    <div class="card stat-card">
        <div class="lbl">Unique Species</div>
        <div class="big"><?= (int)$speciesCount ?></div>
    </div>
    <div class="card stat-card">
        <div class="lbl">Barangays</div>
        <div class="big"><?= (int)$locCount ?></div>
    </div>
    <div class="card stat-card critical">
        <div class="lbl">Critical Status</div>
        <div class="big"><?= (int)$counts['critical'] + (int)$counts['endangered'] ?></div>
    </div>
</div>

<div class="grid-2" style="margin-bottom:18px">
    <div class="card">
        <h2>Monthly Tags</h2>
        <p class="sub">Trees tagged per month (last 3 months)</p>
        <div class="chart-wrap"><canvas id="chartBar"></canvas></div>
    </div>
    <div class="card">
        <h2>Conservation Status</h2>
        <p class="sub">Records by threat level</p>
        <div class="chart-wrap"><canvas id="chartPie"></canvas></div>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center">
            <div>
                <h2>Recent Activity</h2>
                <p class="sub">Latest scanned trees</p>
            </div>
            <a class="link-quiet" href="scans.php">View All</a>
        </div>
        <div class="activity-list">
            <?php if (count($recent) === 0) : ?>
                <p style="color:var(--muted);font-size:0.9rem">No records yet. Data syncs from the mobile app to Firebase.</p>
            <?php else : ?>
                <?php foreach ($recent as $r) : ?>
                    <?php
                    $b = tg_status_bucket((string)($r['status'] ?? ''));
                    $badgeClass = match ($b) {
                        'critical' => 'badge-critical',
                        'endangered' => 'badge-endangered',
                        default => 'badge-vulnerable',
                    };
                    $uid = (string)($r['uid'] ?? '');
                    $officer = tg_user_display_name($uid, $users);
                    $ts = (int)($r['createdAt'] ?? 0);
                    $when = $ts > 0 ? date('n/j/Y', (int)floor($ts / 1000)) : '—';
                    ?>
                    <div class="activity-row">
                        <div class="activity-thumb" aria-hidden="true"></div>
                        <div class="activity-main">
                            <div class="activity-title"><?= h((string)($r['title'] ?? 'Unknown')) ?></div>
                            <div class="activity-sub"><i><?= h((string)($r['scientific'] ?? '—')) ?></i> · <?= h($officer) ?> · <?= h($when) ?></div>
                        </div>
                        <span class="badge <?= $badgeClass ?>"><?= h(tg_status_label($b)) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center">
            <div>
                <h2>Location Overview</h2>
                <p class="sub">Tree distribution map</p>
            </div>
            <a class="link-quiet" href="map.php">Full Map</a>
        </div>
        <div class="map-preview<?= $hasMapbox ? ' map-preview--live' : '' ?>">
            <?php if ($hasMapbox) : ?>
                <div id="dashboardMapPreview" class="map-preview-canvas" role="img" aria-label="Tree locations map preview"></div>
            <?php else : ?>
                <div class="map-preview-fallback">
                    <p>Map preview needs a Mapbox token in <code>config.php</code>.</p>
                    <a class="link-quiet" href="map.php">Open Full Map</a>
                </div>
            <?php endif; ?>
            <div class="map-preview-overlay">
                <strong><?= (int)$mappedCount ?></strong> trees with GPS · <?= (int)$speciesCount ?> species · <?= (int)$locCount ?> locations
            </div>
        </div>
    </div>
</div>

<?php
require __DIR__ . '/includes/layout_end.php';
