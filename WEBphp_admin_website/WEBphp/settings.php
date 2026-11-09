<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

tg_require_login();

$cu = tg_current_user();
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    $fn = trim((string)($_POST['first_name'] ?? ''));
    $ln = trim((string)($_POST['last_name'] ?? ''));
    $pos = trim((string)($_POST['position'] ?? ''));
    $dept = trim((string)($_POST['department'] ?? ''));
    if ($fn !== '' || $ln !== '') {
        $full = trim($fn . ' ' . $ln);
        if (($cu['uid'] ?? '') !== '' && ($cu['uid'] ?? '') !== 'dev-admin') {
            firebase_db_patch('users/' . $cu['uid'], [
                'fullName' => $full,
                'position' => $pos,
                'department' => $dept,
            ]);
        }
        if (isset($_SESSION['tg_user']) && is_array($_SESSION['tg_user'])) {
            $_SESSION['tg_user']['fullName'] = $full !== '' ? $full : ($_SESSION['tg_user']['fullName'] ?? '');
        }
        $msg = 'Profile saved (demo: dev-admin skips Firebase write).';
    }
}

$parts = preg_split('/\s+/', trim((string)($cu['fullName'] ?? 'Admin User')));
$first = is_array($parts) && count($parts) > 0 ? $parts[0] : 'Admin';
$last = is_array($parts) && count($parts) > 1 ? $parts[count($parts) - 1] : 'User';

$pageTitle = 'Admin Profile';
$navActive = '';

require __DIR__ . '/includes/layout_start.php';
?>

<div class="page-head">
    <div>
        <h1>Admin Profile</h1>
        <p>Update your account details and view system information.</p>
    </div>
</div>

<?php if ($msg !== '') : ?>
    <div class="alert alert-info" style="margin-bottom:16px"><?= h($msg) ?></div>
<?php endif; ?>

<div class="settings-grid">
    <div>
        <form method="post" action="settings.php" class="card" style="margin-bottom:18px">
            <h2 style="margin:0 0 14px;font-size:1.05rem">Profile Information</h2>
            <div class="form-grid">
                <div class="form-field">
                    <label for="first_name">First Name</label>
                    <input id="first_name" name="first_name" value="<?= h($first) ?>">
                </div>
                <div class="form-field">
                    <label for="last_name">Last Name</label>
                    <input id="last_name" name="last_name" value="<?= h($last) ?>">
                </div>
                <div class="form-field" style="grid-column:1/-1">
                    <label for="email">Email Address</label>
                    <input id="email" type="email" value="<?= h($cu['email'] ?? 'admin@davao.gov.ph') ?>" disabled>
                </div>
                <div class="form-field">
                    <label for="position">Position</label>
                    <input id="position" name="position" value="Environmental Officer">
                </div>
                <div class="form-field">
                    <label for="department">Office / Department</label>
                    <input id="department" name="department" value="<?= h(APP_REGION) ?>">
                </div>
            </div>
            <button type="submit" name="save_profile" value="1" class="btn btn-primary" style="margin-top:16px">&#128190; Save Changes</button>
        </form>

        <div class="card">
            <h2 style="margin:0 0 14px;font-size:1.05rem">Security</h2>
            <div class="form-field">
                <label for="curpw">Current Password</label>
                <input id="curpw" type="password" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" disabled>
            </div>
            <p style="font-size:0.85rem;color:var(--muted);margin:0">Password changes use Firebase Authentication in production.</p>
        </div>
    </div>
    <div>
        <div class="card" style="margin-bottom:18px">
            <h2 style="margin:0 0 12px;font-size:1.05rem">System Information</h2>
            <p style="margin:6px 0;font-size:0.9rem"><strong>System Version</strong><br><span class="muted">v1.0.2</span></p>
            <p style="margin:6px 0;font-size:0.9rem"><strong>Database Status</strong><br><span style="color:var(--green-600);font-weight:600">Connected (Firebase RTDB)</span></p>
            <p style="margin:6px 0;font-size:0.9rem"><strong>Last Backup</strong><br>Apr 10, 2026 <span style="color:var(--muted);font-size:0.8rem">(demo)</span></p>
            <p style="margin:6px 0;font-size:0.9rem"><strong>Total Storage Used</strong><br>2.4 GB / 50 GB <span style="color:var(--muted);font-size:0.8rem">(demo)</span></p>
        </div>
        <div class="card">
            <h2 style="margin:0 0 12px;font-size:1.05rem">Account Details</h2>
            <p style="margin:6px 0;font-size:0.9rem"><strong>Account Type</strong><br>Administrator</p>
            <p style="margin:6px 0;font-size:0.9rem"><strong>Account Created</strong><br>Jan 1, 2025 <span style="color:var(--muted);font-size:0.8rem">(demo)</span></p>
            <p style="margin:6px 0;font-size:0.9rem"><strong>Last Login</strong><br>Today</p>
            <p style="margin:6px 0;font-size:0.9rem"><strong>Region</strong><br><?= h(APP_REGION) ?></p>
        </div>
    </div>
</div>

<?php
require __DIR__ . '/includes/layout_end.php';
