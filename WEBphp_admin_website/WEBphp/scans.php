<?php

declare(strict_types=1);



require_once __DIR__ . '/includes/init.php';

require_once __DIR__ . '/lib/flora_species.php';



tg_require_login();

tg_session_start();



if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = (string)($_POST['scan_action'] ?? '');

    $ruid = (string)($_POST['record_uid'] ?? '');

    $rid = (string)($_POST['record_id'] ?? '');

    $f = (string)($_GET['filter'] ?? 'all');

    $flash = '';



    if ($ruid !== '' && $rid !== '') {

        if ($action === 'approve') {

            if (firebase_db_patch('tree_records/' . $ruid . '/' . $rid, ['scanStatus' => 'approved'])) {

                $flash = 'approved';

            } else {

                $flash = 'error';

            }

        } elseif ($action === 'reclassify') {

            $speciesId = (string)($_POST['species_id'] ?? '');

            $profile = tg_flora_species_by_id($speciesId);

            if ($profile !== null && in_array($speciesId, ['anahaw', 'narra'], true)) {

                $ok = firebase_db_patch('tree_records/' . $ruid . '/' . $rid, [

                    'scanStatus' => 'reclassified',

                    'speciesId' => $speciesId,

                    'title' => $profile['common'],

                    'scientific' => $profile['scientific'],

                ]);

                $flash = $ok ? 'reclassified' : 'error';

            } else {

                $flash = 'invalid_species';

            }

        }

    }



    $qs = 'filter=' . rawurlencode($f);

    if ($flash !== '') {

        $qs .= '&msg=' . rawurlencode($flash);

    }

    header('Location: scans.php?' . $qs, true, 302);

    exit;

}



$records = tg_all_tree_records();

$users = tg_all_users();

$scanCounts = tg_scan_counts($records);

$detectableSpecies = array_values(array_filter(

    tg_flora_species_profiles(),

    static fn (array $p): bool => in_array($p['id'], ['anahaw', 'narra'], true)

));



$filter = strtolower((string)($_GET['filter'] ?? 'all'));

if (!in_array($filter, ['all', 'pending', 'approved', 'reclassified'], true)) {

    $filter = 'all';

}



$filtered = $records;

if ($filter !== 'all') {

    $filtered = array_values(array_filter($records, static function ($r) use ($filter) {

        $st = tg_scan_review_status($r);

        if ($filter === 'reclassified') {

            return $st === 'reclassified' || $st === 'rejected';

        }

        return $st === $filter;

    }));

}



$flashMsg = (string)($_GET['msg'] ?? '');



$pageTitle = 'Scan Records';

$navActive = 'scans';

$mapboxToken = tg_mapbox_access_token();
$hasMapbox = strncmp(trim($mapboxToken), 'pk.', 3) === 0;
$mapTokenJson = json_encode($mapboxToken, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$scanMapJsV = (string)@filemtime(__DIR__ . '/assets/js/scan_map.js');
$extraScripts = '';
if ($hasMapbox) {
    $extraScripts =
        '<link href="assets/vendor/mapbox-gl/mapbox-gl.css" rel="stylesheet">'
        . '<script src="assets/vendor/mapbox-gl/mapbox-gl.js"></script>'
        . '<script>window.TG_MAPBOX_TOKEN=' . $mapTokenJson . ';</script>'
        . '<script src="assets/js/scan_map.js?v=' . h($scanMapJsV) . '"></script>';
}

require __DIR__ . '/includes/layout_start.php';

?>



<div class="page-head">

    <div>

        <h1>Scan Records</h1>

        <p>Review captured flora images, coordinates, and validator corrections.</p>

    </div>

</div>



<?php if ($flashMsg === 'approved') : ?>

    <div class="alert alert-success scan-flash" role="status">Scan approved.</div>

<?php elseif ($flashMsg === 'reclassified') : ?>

    <div class="alert alert-success scan-flash" role="status">Species reclassified successfully.</div>

<?php elseif ($flashMsg === 'invalid_species') : ?>

    <div class="alert alert-error scan-flash" role="alert">Choose a valid species (Anahaw or Narra).</div>

<?php elseif ($flashMsg === 'error') : ?>

    <div class="alert alert-error scan-flash" role="alert">Could not update this record. Try again.</div>

<?php endif; ?>



<div class="stat-row">

    <div class="stat-tile">

        <div class="lbl" style="color:var(--muted);font-size:0.8rem">Total Scans</div>

        <div class="big" style="font-size:1.5rem;font-weight:800"><?= count($records) ?></div>

    </div>

    <div class="stat-tile pending">

        <div class="lbl" style="font-size:0.8rem">Pending</div>

        <div class="big" style="font-size:1.5rem;font-weight:800"><?= (int)$scanCounts['pending'] ?></div>

    </div>

    <div class="stat-tile approved">

        <div class="lbl" style="font-size:0.8rem">Approved</div>

        <div class="big" style="font-size:1.5rem;font-weight:800"><?= (int)$scanCounts['approved'] ?></div>

    </div>

    <div class="stat-tile reclassified">

        <div class="lbl" style="font-size:0.8rem">Reclassified</div>

        <div class="big" style="font-size:1.5rem;font-weight:800"><?= (int)$scanCounts['reclassified'] ?></div>

    </div>

</div>



<p style="font-size:0.9rem;margin:0 0 8px"><strong>Filter by status:</strong></p>

<div class="filter-pills">

    <?php

    $pills = [

        'all' => 'All',

        'pending' => 'Pending',

        'approved' => 'Approved',

        'reclassified' => 'Reclassified',

    ];

    foreach ($pills as $key => $label) :

        ?>

        <a href="scans.php?filter=<?= h($key) ?>" class="<?= $filter === $key ? 'is-active' : '' ?>"><?= h($label) ?></a>

    <?php endforeach; ?>

</div>



<p class="scan-page-hint">Review each scan below — tap the photo to enlarge or use <strong>View on map</strong> for location and full details.</p>

<div class="scan-list">
<?php foreach ($filtered as $r) : ?>

    <?php

    $uid = (string)($r['uid'] ?? '');

    $rid = (string)($r['id'] ?? '');

    $st = tg_scan_review_status($r);

    $badgeClass = match ($st) {

        'pending' => 'badge-pending',

        'reclassified', 'rejected' => 'badge-reclassified',

        default => 'badge-approved',

    };

    $badgeLabel = tg_scan_review_label($st);

    $canApprove = tg_scan_can_approve($st);

    $canReclassify = tg_scan_can_reclassify($st);

    $officer = tg_user_display_name($uid, $users);

    $ts = (int)($r['createdAt'] ?? 0);

    $when = $ts > 0 ? date('M j, Y, h:i A', (int)floor($ts / 1000)) : '—';

    $conf = (float)($r['confidence'] ?? 0);

    $imageUrl = trim((string)($r['imageUrl'] ?? ''));

    $ll = tg_record_lat_lng($r);

    if ($ll !== null) {

        $coords = sprintf('%.6f, %.6f', $ll[0], $ll[1]);

        $hasCoords = true;

        $mapLat = $ll[0];

        $mapLng = $ll[1];

    } else {

        $coords = (string)($r['coordinates'] ?? '—');

        $hasCoords = false;

        $mapLat = 0.0;

        $mapLng = 0.0;

    }

    $speciesId = (string)($r['speciesId'] ?? '');

    $scanTitle = (string)($r['title'] ?? 'Scan');

    $conservationBucket = tg_status_bucket((string)($r['status'] ?? ''));

    $floraProfile = $speciesId !== '' ? tg_flora_species_by_id($speciesId) : null;

    $scanMapPayload = [
        'title' => $scanTitle,
        'scientific' => (string)($r['scientific'] ?? '—'),
        'speciesId' => $speciesId !== '' ? $speciesId : '—',
        'conservationStatus' => tg_status_label($conservationBucket),
        'scanReviewStatus' => $badgeLabel,
        'location' => (string)($r['locationName'] ?? '—'),
        'coordinates' => $coords,
        'latitude' => $hasCoords ? (string)$mapLat : '—',
        'longitude' => $hasCoords ? (string)$mapLng : '—',
        'scannedBy' => $officer,
        'scannedAt' => $when,
        'confidence' => (string)round($conf) . '%',
        'recordId' => $rid,
        'userId' => $uid,
        'hasImage' => $imageUrl !== '' ? 'Yes' : 'No',
    ];

    if (is_array($floraProfile)) {
        $scanMapPayload['origin'] = (string)($floraProfile['origin'] ?? '—');
        $scanMapPayload['habitat'] = (string)($floraProfile['distribution_habitat']['habitat'] ?? '—');
        $scanMapPayload['distribution'] = (string)($floraProfile['distribution_habitat']['distribution'] ?? '—');
        $scanMapPayload['climate'] = (string)($floraProfile['distribution_habitat']['climate'] ?? '—');
        $scanMapPayload['floraConservation'] = (string)($floraProfile['conservation'] ?? '—');
        $desc = (string)($floraProfile['description'] ?? '');
        if ($desc !== '') {
            $scanMapPayload['description'] = $desc;
        }
    }

    $scanMapJson = htmlspecialchars(
        json_encode($scanMapPayload, JSON_THROW_ON_ERROR),
        ENT_QUOTES,
        'UTF-8'
    );

    ?>

    <article class="scan-card scan-card--<?= h($st) ?>">
        <header class="scan-card__header">
            <div class="scan-card__title-block">
                <h2 class="scan-card__title"><?= h($scanTitle) ?></h2>
                <p class="scan-card__sci"><em><?= h((string)($r['scientific'] ?? '—')) ?></em></p>
            </div>
            <div class="scan-card__header-badges">
                <span class="badge <?= $badgeClass ?>"><?= h($badgeLabel) ?></span>
                <span class="scan-confidence"><?= h((string)round($conf)) ?>% match</span>
            </div>
        </header>

        <div class="scan-card__main">
            <div class="scan-card__media">
                <?php if ($imageUrl !== '') : ?>
                    <button
                        type="button"
                        class="scan-thumb-btn"
                        data-scan-image="<?= h($imageUrl) ?>"
                        data-scan-title="<?= h($scanTitle) ?>"
                        aria-label="Enlarge captured scan for <?= h($scanTitle) ?>"
                    >
                        <img
                            src="<?= h($imageUrl) ?>"
                            alt="Captured scan — <?= h($scanTitle) ?>"
                            class="scan-thumb-img"
                            loading="lazy"
                            data-scan-thumb
                        >
                    </button>
                    <span class="scan-thumb-hint">Tap to enlarge</span>
                <?php else : ?>
                    <div class="scan-thumb-placeholder" aria-hidden="true">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2.5"/><circle cx="9" cy="11" r="2"/><path d="m21 16-4.2-4.2a2 2 0 0 0-2.8 0L7 19"/></svg>
                        <span>No photo</span>
                    </div>
                <?php endif; ?>
            </div>

            <dl class="scan-facts">
                <div class="scan-facts__item">
                    <dt>Scanned</dt>
                    <dd><?= h($when) ?></dd>
                </div>
                <div class="scan-facts__item">
                    <dt>Officer</dt>
                    <dd><?= h($officer) ?></dd>
                </div>
                <div class="scan-facts__item">
                    <dt>Location</dt>
                    <dd><?= h((string)($r['locationName'] ?? '—')) ?></dd>
                </div>
                <div class="scan-facts__item">
                    <dt>Coordinates</dt>
                    <dd><?= h($coords) ?></dd>
                </div>
                <?php if ($speciesId !== '') : ?>
                    <div class="scan-facts__item">
                        <dt>Species ID</dt>
                        <dd><code class="scan-facts__code"><?= h($speciesId) ?></code></dd>
                    </div>
                <?php endif; ?>
                <div class="scan-facts__item">
                    <dt>Conservation</dt>
                    <dd><?= h(tg_status_label($conservationBucket)) ?></dd>
                </div>
            </dl>
        </div>

        <footer class="scan-card__footer">
            <div class="scan-card__toolbar">
                <?php if ($hasCoords && $hasMapbox) : ?>
                    <button
                        type="button"
                        class="btn btn-outline btn-sm scan-map-btn"
                        data-lat="<?= h((string)$mapLat) ?>"
                        data-lng="<?= h((string)$mapLng) ?>"
                        data-scan-detail="<?= $scanMapJson ?>"
                    >
                        <span aria-hidden="true">📍</span> View on map
                    </button>
                <?php endif; ?>
                <?php if ($canApprove) : ?>
                    <form method="post" action="scans.php?filter=<?= h($filter) ?>" class="scan-toolbar-form">
                        <input type="hidden" name="record_uid" value="<?= h($uid) ?>">
                        <input type="hidden" name="record_id" value="<?= h($rid) ?>">
                        <button type="submit" name="scan_action" value="approve" class="btn btn-primary btn-sm">
                            Approve
                        </button>
                    </form>
                <?php endif; ?>
            </div>
            <?php if ($canReclassify) : ?>
                <form method="post" action="scans.php?filter=<?= h($filter) ?>" class="scan-reclassify-bar">
                    <input type="hidden" name="record_uid" value="<?= h($uid) ?>">
                    <input type="hidden" name="record_id" value="<?= h($rid) ?>">
                    <label class="scan-reclassify-bar__label" for="species-<?= h($rid) ?>">Reclassify</label>
                    <select id="species-<?= h($rid) ?>" name="species_id" class="scan-species-select" required>
                        <?php foreach ($detectableSpecies as $sp) : ?>
                            <option value="<?= h($sp['id']) ?>"<?= $speciesId === $sp['id'] ? ' selected' : '' ?>>
                                <?= h($sp['common']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="scan_action" value="reclassify" class="btn btn-outline btn-sm">
                        Apply
                    </button>
                </form>
            <?php endif; ?>
        </footer>
    </article>

<?php endforeach; ?>
</div>

<?php if (count($filtered) === 0) : ?>

    <div class="card"><p style="margin:0;color:var(--muted)">No scan records for this filter.</p></div>

<?php endif; ?>

<?php if ($hasMapbox) : ?>
<div id="scanMapModal" class="modal-overlay scan-map-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="scanMapModalTitle">
    <div class="scan-map-modal__panel" role="document">
        <button type="button" class="scan-map-modal__close" id="scanMapModalClose" aria-label="Close map">&times;</button>
        <div class="scan-map-modal__head">
            <h3 id="scanMapModalTitle" class="scan-map-modal__title">Tagged location</h3>
            <p id="scanMapModalMeta" class="scan-map-modal__meta"></p>
        </div>
        <div id="scanMapCanvas" class="scan-map-modal__map" aria-label="Map showing this scan only"></div>
        <section class="scan-map-modal__details" aria-labelledby="scanMapDetailsHeading">
            <h4 id="scanMapDetailsHeading" class="scan-map-modal__details-title">Scan details</h4>
            <div id="scanMapDetails" class="scan-map-details-grid"></div>
        </section>
        <div class="scan-map-modal__actions">
            <button type="button" class="btn btn-primary btn-sm" id="scanMapModalDone">Close</button>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
require __DIR__ . '/includes/layout_end.php';



