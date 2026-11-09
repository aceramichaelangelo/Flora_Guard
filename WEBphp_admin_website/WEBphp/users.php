<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

tg_require_login();
tg_session_start();

$cu = tg_current_user();
$flashMsg = (string)($_GET['msg'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['user_action'] ?? '');
    $uid = (string)($_POST['user_uid'] ?? '');
    $flash = '';

    if ($uid !== '') {
        if ($action === 'update') {
            $fullName = trim((string)($_POST['full_name'] ?? ''));
            $roleRaw = strtolower(trim((string)($_POST['role'] ?? 'user')));
            $role = in_array($roleRaw, ['admin', 'user', 'viewer'], true) ? $roleRaw : 'user';
            if ($fullName === '') {
                $flash = 'invalid';
            } else {
                $flash = firebase_db_patch('users/' . $uid, [
                    'fullName' => $fullName,
                    'role' => $role,
                ]) ? 'updated' : 'error';
            }
        } elseif ($action === 'delete') {
            if ($uid === (string)($cu['uid'] ?? '')) {
                $flash = 'self';
            } else {
                $flash = firebase_db_delete('users/' . $uid) ? 'deleted' : 'error';
            }
        }
    }

    $qs = [];
    if ($flash !== '') {
        $qs['msg'] = $flash;
    }
    $q = trim((string)($_GET['q'] ?? $_POST['return_q'] ?? ''));
    if ($q !== '') {
        $qs['q'] = $q;
    }
    $roleF = trim((string)($_GET['role'] ?? $_POST['return_role'] ?? ''));
    if ($roleF !== '' && $roleF !== 'all') {
        $qs['role'] = $roleF;
    }
    header('Location: users.php' . ($qs !== [] ? '?' . http_build_query($qs) : ''), true, 302);
    exit;
}

$users = tg_all_users();
$uq = strtolower(trim((string)($_GET['q'] ?? '')));
$roleFilter = strtolower(trim((string)($_GET['role'] ?? 'all')));
$added = isset($_GET['added']);

$rows = [];
foreach ($users as $uid => $u) {
    if (!is_array($u)) {
        continue;
    }
    $rows[] = array_merge($u, ['_uid' => (string)$uid]);
}

if ($uq !== '') {
    $rows = array_values(array_filter($rows, static function ($u) use ($uq) {
        $role = tg_role_label((string)($u['role'] ?? ''));
        $hay = strtolower(
            ($u['fullName'] ?? '') . ' ' . ($u['email'] ?? '') . ' ' . $role
        );
        return str_contains($hay, $uq);
    }));
}

if ($roleFilter !== '' && $roleFilter !== 'all') {
    $rows = array_values(array_filter($rows, static function ($u) use ($roleFilter) {
        $r = strtolower((string)($u['role'] ?? 'user'));
        if ($roleFilter === 'admin') {
            return $r === 'admin';
        }
        if ($roleFilter === 'viewer') {
            return $r === 'viewer';
        }
        return in_array($r, ['user', 'field', 'field_officer', 'officer'], true);
    }));
}

$total = count($rows);
$admins = count(array_filter($rows, static fn ($u) => strtolower((string)($u['role'] ?? '')) === 'admin'));
$field = count(array_filter($rows, static function ($u) {
    $r = strtolower((string)($u['role'] ?? ''));
    return in_array($r, ['user', 'field', 'field_officer', 'officer'], true);
}));
$active = $total;

$pageTitle = 'User Management';
$navActive = 'users';

require __DIR__ . '/includes/layout_start.php';
?>

<?php if ($added) : ?>
    <div class="alert alert-info" style="margin-bottom:16px">User created successfully. They can sign in on the mobile app with this email and password.</div>
<?php endif; ?>
<?php if ($flashMsg === 'updated') : ?>
    <div class="alert alert-success" style="margin-bottom:16px">User profile updated.</div>
<?php elseif ($flashMsg === 'deleted') : ?>
    <div class="alert alert-success" style="margin-bottom:16px">User removed from the database. Firebase Auth account may still exist in Console.</div>
<?php elseif ($flashMsg === 'self') : ?>
    <div class="alert alert-error" style="margin-bottom:16px">You cannot delete your own account while logged in.</div>
<?php elseif ($flashMsg === 'error') : ?>
    <div class="alert alert-error" style="margin-bottom:16px">Action failed. Check Firebase connection and database rules.</div>
<?php elseif ($flashMsg === 'invalid') : ?>
    <div class="alert alert-error" style="margin-bottom:16px">Invalid data. Full name is required.</div>
<?php endif; ?>

<div class="page-head">
    <div>
        <h1>User Management</h1>
        <p>Manage system users and permissions.</p>
    </div>
</div>

<div class="grid-4" style="margin-bottom:18px">
    <div class="card stat-card">
        <div class="lbl">Total Users</div>
        <div class="big"><?= (int)$total ?></div>
    </div>
    <div class="card stat-card">
        <div class="lbl" style="color:var(--purple)">Administrators</div>
        <div class="big" style="color:var(--purple)"><?= (int)$admins ?></div>
    </div>
    <div class="card stat-card">
        <div class="lbl" style="color:var(--blue)">Field Officers</div>
        <div class="big" style="color:var(--blue)"><?= (int)$field ?></div>
    </div>
    <div class="card stat-card">
        <div class="lbl" style="color:var(--green-700)">Active Users</div>
        <div class="big" style="color:var(--green-700)"><?= (int)$active ?></div>
    </div>
</div>

<div class="table-toolbar">
    <form method="get" action="users.php" style="flex:1;max-width:420px">
        <div class="input-wrap" style="background:#fff">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="search" name="q" placeholder="Search by name, email, or role..." value="<?= h($_GET['q'] ?? '') ?>" style="border:0;background:transparent;flex:1;padding:10px 0">
        </div>
        <?php if ($roleFilter !== '' && $roleFilter !== 'all') : ?>
            <input type="hidden" name="role" value="<?= h($roleFilter) ?>">
        <?php endif; ?>
    </form>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
        <form method="get" action="users.php" style="display:flex;gap:8px;align-items:center">
            <?php if ($uq !== '') : ?>
                <input type="hidden" name="q" value="<?= h($_GET['q'] ?? '') ?>">
            <?php endif; ?>
            <select name="role" onchange="this.form.submit()" style="padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:0.88rem">
                <option value="all"<?= $roleFilter === 'all' ? ' selected' : '' ?>>All roles</option>
                <option value="admin"<?= $roleFilter === 'admin' ? ' selected' : '' ?>>Administrators</option>
                <option value="user"<?= $roleFilter === 'user' ? ' selected' : '' ?>>Field officers</option>
                <option value="viewer"<?= $roleFilter === 'viewer' ? ' selected' : '' ?>>Viewers</option>
            </select>
        </form>
        <a class="toolbar-btn toolbar-btn--primary" href="user_add.php">+ Add User</a>
    </div>
</div>

<div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>User</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Joined Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($rows) === 0) : ?>
                <tr><td colspan="6" style="padding:28px;text-align:center;color:var(--muted)">No users found.</td></tr>
            <?php else : ?>
                <?php foreach ($rows as $u) : ?>
                    <?php
                    $uid = (string)($u['_uid'] ?? '');
                    $name = (string)($u['fullName'] ?? 'Unknown');
                    $initials = 'U';
                    $parts = preg_split('/\s+/', trim($name));
                    if (is_array($parts) && count($parts) >= 2) {
                        $initials = strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts) - 1], 0, 1));
                    } elseif ($name !== '') {
                        $initials = strtoupper(substr($name, 0, 2));
                    }
                    $role = (string)($u['role'] ?? 'user');
                    $badge = tg_role_badge_class($role);
                    $joined = (int)($u['createdAt'] ?? 0);
                    $jd = $joined > 0 ? date('M j, Y', (int)floor($joined / 1000)) : '—';
                    $rowPayload = [
                        'uid' => $uid,
                        'fullName' => $name,
                        'email' => (string)($u['email'] ?? '—'),
                        'role' => $role,
                        'roleLabel' => tg_role_label($role),
                        'joined' => $jd,
                        'isSelf' => $uid === (string)($cu['uid'] ?? ''),
                    ];
                    $rowJson = htmlspecialchars(
                        json_encode($rowPayload, JSON_THROW_ON_ERROR),
                        ENT_QUOTES,
                        'UTF-8'
                    );
                    ?>
                    <tr data-user-row="<?= $rowJson ?>">
                        <td>
                            <div style="display:flex;align-items:center;gap:10px">
                                <div class="user-avatar" style="width:36px;height:36px;font-size:0.75rem"><?= h($initials) ?></div>
                                <?= h($name) ?>
                            </div>
                        </td>
                        <td><?= h((string)($u['email'] ?? '—')) ?></td>
                        <td><span class="badge <?= h($badge) ?>"><?= h(tg_role_label($role)) ?></span></td>
                        <td><span class="badge badge-green">Active</span></td>
                        <td>&#128198; <?= h($jd) ?></td>
                        <td>
                            <div class="row-actions">
                                <button type="button" class="icon-link user-btn-view" title="View user" aria-label="View <?= h($name) ?>">&#128065;</button>
                                <button type="button" class="icon-link user-btn-edit" title="Edit user" aria-label="Edit <?= h($name) ?>">&#9998;</button>
                                <button type="button" class="icon-link icon-link--danger user-btn-delete" title="Delete user" aria-label="Delete <?= h($name) ?>"<?= $uid === (string)($cu['uid'] ?? '') ? ' disabled style="opacity:0.4;cursor:not-allowed"' : '' ?>>&#128465;</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div id="userViewModal" class="modal-overlay" aria-hidden="true">
    <div class="modal modal-modern tree-dialog" role="dialog" aria-modal="true" aria-labelledby="userViewTitle">
        <div class="modal-hero">
            <div class="modal-icon-wrap">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
            <div class="modal-hero-copy">
                <span class="modal-kicker">User profile</span>
                <h3 id="userViewTitle">User</h3>
                <p class="modal-desc" id="userViewEmail">—</p>
            </div>
        </div>
        <div class="modal-summary" id="userViewSummary"></div>
        <div class="modal-actions tree-dialog__actions">
            <button type="button" class="btn btn-outline" id="userViewClose">Close</button>
        </div>
    </div>
</div>

<div id="userEditModal" class="modal-overlay" aria-hidden="true">
    <div class="modal modal-modern tree-dialog" role="dialog" aria-modal="true" aria-labelledby="userEditTitle">
        <div class="modal-hero">
            <div class="modal-hero-copy">
                <span class="modal-kicker">Edit user</span>
                <h3 id="userEditTitle">Edit user</h3>
                <p class="modal-desc" id="userEditSubtitle">—</p>
            </div>
        </div>
        <form method="post" action="users.php<?= $uq !== '' || $roleFilter !== 'all' ? '?' . http_build_query(array_filter(['q' => $_GET['q'] ?? '', 'role' => $roleFilter !== 'all' ? $roleFilter : ''])) : '' ?>">
            <input type="hidden" name="user_action" value="update">
            <input type="hidden" name="user_uid" id="userEditUid" value="">
            <input type="hidden" name="return_q" value="<?= h($_GET['q'] ?? '') ?>">
            <input type="hidden" name="return_role" value="<?= h($roleFilter) ?>">
            <div class="form-field" style="margin-bottom:12px">
                <label for="userEditName">Full name</label>
                <input id="userEditName" name="full_name" required>
            </div>
            <div class="form-field" style="margin-bottom:16px">
                <label for="userEditRole">Role</label>
                <select id="userEditRole" name="role" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm)">
                    <option value="user">Field Officer</option>
                    <option value="admin">Administrator</option>
                    <option value="viewer">Viewer</option>
                </select>
            </div>
            <div class="modal-actions tree-dialog__actions">
                <button type="button" class="btn btn-outline" id="userEditCancel">Cancel</button>
                <button type="submit" class="btn btn-primary">Save changes</button>
            </div>
        </form>
    </div>
</div>

<div id="userDeleteModal" class="modal-overlay" aria-hidden="true">
    <div class="modal modal-modern tree-dialog" role="dialog" aria-modal="true" aria-labelledby="userDeleteTitle">
        <div class="modal-hero">
            <div class="modal-icon-wrap" style="background:#fef2f2;color:#b91c1c">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
            </div>
            <div class="modal-hero-copy">
                <span class="modal-kicker">Delete user</span>
                <h3 id="userDeleteTitle">Remove user?</h3>
                <p class="modal-desc" id="userDeleteSubtitle">This cannot be undone.</p>
            </div>
        </div>
        <form method="post" action="users.php">
            <input type="hidden" name="user_action" value="delete">
            <input type="hidden" name="user_uid" id="userDeleteUid" value="">
            <input type="hidden" name="return_q" value="<?= h($_GET['q'] ?? '') ?>">
            <input type="hidden" name="return_role" value="<?= h($roleFilter) ?>">
            <div class="modal-actions tree-dialog__actions">
                <button type="button" class="btn btn-outline" id="userDeleteCancel">Cancel</button>
                <button type="submit" class="btn btn-danger">Delete user</button>
            </div>
        </form>
    </div>
</div>

<?php
$extraScripts = '<script src="assets/js/users.js?v=' . h((string)@filemtime(__DIR__ . '/assets/js/users.js')) . '"></script>';
require __DIR__ . '/includes/layout_end.php';
