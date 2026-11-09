<?php

declare(strict_types=1);



require_once __DIR__ . '/includes/init.php';



tg_require_login();

tg_session_start();



$page = max(1, (int)($_GET['page'] ?? 1));

$flashMsg = (string)($_GET['msg'] ?? '');



if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = (string)($_POST['tree_action'] ?? '');

    $ruid = (string)($_POST['record_uid'] ?? '');

    $rid = (string)($_POST['record_id'] ?? '');

    $flash = '';



    if ($ruid !== '' && $rid !== '') {

        $path = 'tree_records/' . $ruid . '/' . $rid;

        if ($action === 'delete') {

            $flash = firebase_db_delete($path) ? 'deleted' : 'error';

        }

    }



    $qs = 'page=' . $page;

    if ($flash !== '') {

        $qs .= '&msg=' . rawurlencode($flash);

    }

    header('Location: trees.php?' . $qs, true, 302);

    exit;

}



$allRecords = tg_all_tree_records();

$users = tg_all_users();

$filtered = $allRecords;

$counts = tg_conservation_counts($filtered);



$perPage = 10;

$totalRows = count($filtered);

$totalPages = max(1, (int)ceil($totalRows / $perPage));

if ($page > $totalPages) {

    $page = $totalPages;

}

$offset = ($page - 1) * $perPage;

$records = array_slice($filtered, $offset, $perPage);



$pageTitle = 'Tree Management';

$navActive = 'trees';



require __DIR__ . '/includes/layout_start.php';

?>



<div class="page-head">

    <div>

        <h1>Tree Management</h1>

        <p>Inventory of tagged trees and conservation status. To <strong>approve</strong>, <strong>reclassify</strong>, or <strong>view on map</strong>, use <a href="scans.php">Scan Records</a>.</p>

    </div>

</div>



<?php if ($flashMsg === 'deleted') : ?>

    <div class="alert alert-success scan-flash" role="status">Tree record deleted.</div>

<?php elseif ($flashMsg === 'error') : ?>

    <div class="alert alert-error scan-flash" role="alert">Action failed. Try again.</div>

<?php endif; ?>



<div class="grid-4" style="margin-bottom:18px">

    <div class="card stat-card">

        <div class="lbl">Total Trees</div>

        <div class="big"><?= (int)$counts['total'] ?></div>

    </div>

    <div class="card stat-card critical">

        <div class="lbl">Critically Endangered</div>

        <div class="big"><?= (int)$counts['critical'] ?></div>

    </div>

    <div class="card stat-card endangered">

        <div class="lbl">Endangered</div>

        <div class="big"><?= (int)$counts['endangered'] ?></div>

    </div>

    <div class="card stat-card vulnerable">

        <div class="lbl">Vulnerable</div>

        <div class="big"><?= (int)$counts['vulnerable'] ?></div>

    </div>

</div>



<div class="table-wrap">

    <table class="data-table">

        <thead>

            <tr>

                <th>Image</th>

                <th>Common Name</th>

                <th>Scientific Name</th>

                <th>Conservation</th>

                <th>Scan review</th>

                <th>Location</th>

                <th>Scanned By</th>

                <th>Date</th>

                <th>Actions</th>

            </tr>

        </thead>

        <tbody>

            <?php if (count($records) === 0) : ?>

                <tr><td colspan="9" style="padding:28px;text-align:center;color:var(--muted)">No trees in inventory yet.</td></tr>

            <?php else : ?>

                <?php foreach ($records as $r) : ?>

                    <?php

                    $b = tg_status_bucket((string)($r['status'] ?? ''));

                    $badgeClass = match ($b) {

                        'critical' => 'badge-critical',

                        'endangered' => 'badge-endangered',

                        default => 'badge-vulnerable',

                    };

                    $uid = (string)($r['uid'] ?? '');

                    $rid = (string)($r['id'] ?? '');

                    $officer = tg_user_display_name($uid, $users);

                    $ts = (int)($r['createdAt'] ?? 0);

                    $when = $ts > 0 ? date('M j, Y', (int)floor($ts / 1000)) : '—';

                    $imageUrl = trim((string)($r['imageUrl'] ?? ''));

                    $title = (string)($r['title'] ?? 'Unknown');

                    $scientific = (string)($r['scientific'] ?? '—');

                    $location = (string)($r['locationName'] ?? '—');

                    $statusRaw = (string)($r['status'] ?? 'vulnerable');

                    $lat = $r['latitude'] ?? null;

                    $lng = $r['longitude'] ?? null;

                    if (is_numeric($lat) && is_numeric($lng)) {

                        $coords = sprintf('%.6f, %.6f', (float)$lat, (float)$lng);

                    } else {

                        $coords = (string)($r['coordinates'] ?? '—');

                    }

                    $scanSt = tg_scan_review_status($r);
                    $scanBadgeClass = match ($scanSt) {
                        'pending' => 'badge-pending',
                        'reclassified', 'rejected' => 'badge-reclassified',
                        default => 'badge-approved',
                    };
                    $scanBadgeLabel = tg_scan_review_label($scanSt);

                    $conf = (float)($r['confidence'] ?? 0);

                    $rowPayload = [

                        'uid' => $uid,

                        'id' => $rid,

                        'title' => $title,

                        'scientific' => $scientific,

                        'status' => $statusRaw,

                        'statusLabel' => tg_status_label($b),

                        'location' => $location,

                        'coords' => $coords,

                        'officer' => $officer,

                        'when' => $when,

                        'imageUrl' => $imageUrl,

                        'scanStatus' => $scanSt,
                        'scanStatusLabel' => $scanBadgeLabel,

                        'confidence' => $conf,

                    ];

                    $rowJson = htmlspecialchars(

                        json_encode($rowPayload, JSON_THROW_ON_ERROR),

                        ENT_QUOTES,

                        'UTF-8'

                    );

                    ?>

                    <tr data-tree-row="<?= $rowJson ?>">

                        <td>

                            <?php if ($imageUrl !== '') : ?>

                                <button

                                    type="button"

                                    class="tree-thumb-btn"

                                    data-scan-image="<?= h($imageUrl) ?>"

                                    data-scan-title="<?= h($title) ?>"

                                    aria-label="Enlarge photo of <?= h($title) ?>"

                                >

                                    <img

                                        src="<?= h($imageUrl) ?>"

                                        alt="<?= h($title) ?>"

                                        class="tree-thumb-img"

                                        loading="lazy"

                                        data-scan-thumb

                                    >

                                </button>

                            <?php else : ?>

                                <div class="thumb-sm thumb-sm--empty" title="No image">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2.5"/><circle cx="9" cy="11" r="2"/><path d="m21 16-4.2-4.2a2 2 0 0 0-2.8 0L7 19"/></svg>
                                </div>

                            <?php endif; ?>

                        </td>

                        <td><?= h($title) ?></td>

                        <td><i><?= h($scientific) ?></i></td>

                        <td><span class="badge <?= $badgeClass ?>"><?= h(tg_status_label($b)) ?></span></td>

                        <td><span class="badge <?= h($scanBadgeClass) ?>"><?= h($scanBadgeLabel) ?></span></td>

                        <td><?= h($location) ?></td>

                        <td><?= h($officer) ?></td>

                        <td><?= h($when) ?></td>

                        <td>

                            <div class="row-actions">

                                <button

                                    type="button"

                                    class="icon-link tree-btn-view"

                                    title="View details"

                                    aria-label="View <?= h($title) ?>"

                                >&#128065;</button>

                                <button

                                    type="button"

                                    class="icon-link icon-link--danger tree-btn-delete"

                                    title="Delete record"

                                    aria-label="Delete <?= h($title) ?>"

                                >&#128465;</button>

                            </div>

                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php endif; ?>

        </tbody>

    </table>

</div>



<?php

$from = $totalRows === 0 ? 0 : $offset + 1;

$to = min($offset + $perPage, $totalRows);

$prevHref = 'trees.php?page=' . ($page - 1);

$nextHref = 'trees.php?page=' . ($page + 1);

$prevDisabled = $page <= 1;

$nextDisabled = $page >= $totalPages;

?>

<div class="table-foot table-foot--pagination">

    <span class="table-foot-meta">Showing <?= (int)$from ?>–<?= (int)$to ?> of <?= (int)$totalRows ?> trees<?= $totalPages > 1 ? ' · Page ' . (int)$page . ' / ' . (int)$totalPages : '' ?></span>

    <div class="table-foot-nav">

        <?php if ($prevDisabled) : ?>

            <span class="btn btn-outline btn-sm is-disabled" aria-disabled="true">Previous</span>

        <?php else : ?>

            <a class="btn btn-outline btn-sm" href="<?= h($prevHref) ?>">Previous</a>

        <?php endif; ?>

        <?php if ($nextDisabled) : ?>

            <span class="btn btn-outline btn-sm is-disabled" aria-disabled="true">Next</span>

        <?php else : ?>

            <a class="btn btn-outline btn-sm" href="<?= h($nextHref) ?>">Next</a>

        <?php endif; ?>

    </div>

</div>



<!-- View tree -->
<div id="treeViewModal" class="modal-overlay" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="treeViewTitle">
    <div class="modal modal-modern tree-dialog tree-dialog--view">
        <div class="tree-dialog__body tree-dialog__body--hero">
            <div class="tree-dialog__icon tree-dialog__icon--view" aria-hidden="true">🌳</div>
            <div class="tree-dialog__copy">
                <span class="tree-dialog__kicker">Tree record</span>
                <h3 id="treeViewTitle">—</h3>
                <p class="tree-dialog__text tree-dialog__text--sci" id="treeViewScientific"><em>—</em></p>
            </div>
        </div>
        <div id="treeViewImageWrap" class="tree-dialog__image-wrap" hidden>
            <button type="button" class="tree-dialog__image-btn" id="treeViewImageBtn">
                <img id="treeViewImage" src="" alt="" class="tree-dialog__image">
            </button>
            <span class="tree-dialog__image-hint">Click image to enlarge</span>
        </div>
        <div class="modal-summary tree-dialog__summary" id="treeViewSummary"></div>
        <div class="modal-actions">
            <a id="treeViewScansLink" class="btn btn-primary" href="scans.php">Review in Scan Records</a>
            <button type="button" class="btn btn-outline" id="treeViewClose">Close</button>
        </div>
    </div>
</div>

<!-- Delete confirm -->
<div id="treeDeleteModal" class="modal-overlay" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="treeDeleteTitle">
    <div class="modal modal-modern tree-dialog tree-dialog--danger">
        <div class="tree-dialog__body">
            <div class="tree-dialog__icon tree-dialog__icon--danger" aria-hidden="true">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
            </div>
            <div class="tree-dialog__copy">
                <span class="tree-dialog__kicker">Permanent action</span>
                <h3 id="treeDeleteTitle">Delete tree record?</h3>
                <p class="tree-dialog__text" id="treeDeleteSubtitle">This cannot be undone.</p>
            </div>
        </div>
        <form method="post" action="trees.php?page=<?= (int)$page ?>" id="treeDeleteForm">
            <input type="hidden" name="tree_action" value="delete">
            <input type="hidden" name="record_uid" id="treeDeleteUid" value="">
            <input type="hidden" name="record_id" id="treeDeleteRid" value="">
            <div class="modal-actions tree-dialog__actions">
                <button type="button" class="btn btn-outline" id="treeDeleteCancel">Cancel</button>
                <button type="submit" class="btn btn-danger">Delete record</button>
            </div>
        </form>
    </div>
</div>



<?php

require __DIR__ . '/includes/layout_end.php';

