<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

tg_require_login();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim((string)($_POST['full_name'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    $password2 = (string)($_POST['password_confirm'] ?? '');
    $roleRaw = strtolower(trim((string)($_POST['role'] ?? 'user')));

    $role = in_array($roleRaw, ['admin', 'user', 'viewer'], true) ? $roleRaw : 'user';

    if ($fullName === '' || $email === '' || $password === '') {
        $error = 'Please fill in full name, email, and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $password2) {
        $error = 'Passwords do not match.';
    } else {
        $auth = firebase_sign_up($email, $password);
        if (empty($auth['ok'])) {
            $error = (string)($auth['message'] ?? 'Could not create user.');
        } else {
            $uid = (string)$auth['localId'];
            $createdMs = (int) round(microtime(true) * 1000);
            $ok = firebase_db_patch('users/' . $uid, [
                'uid' => $uid,
                'fullName' => $fullName,
                'email' => $email,
                'role' => $role,
                'isGuest' => false,
                'createdAt' => $createdMs,
            ]);
            if (!$ok) {
                $error = 'Auth account was created but saving the profile to Realtime Database failed. Check DB rules and network.';
            } else {
                header('Location: users.php?added=1', true, 302);
                exit;
            }
        }
    }
}

$pageTitle = 'Add User';
$navActive = 'users';

require __DIR__ . '/includes/layout_start.php';
?>

<div class="page-head">
    <div>
        <h1>Add User</h1>
        <p>Create a Firebase Auth account and user profile in Realtime Database.</p>
    </div>
    <a class="btn btn-outline btn-sm" href="users.php">Back to list</a>
</div>

<?php if ($error !== '') : ?>
    <div class="alert alert-error" style="max-width:560px"><?= h($error) ?></div>
<?php endif; ?>

<div class="card" style="max-width:560px">
    <form method="post" action="user_add.php" autocomplete="off">
        <div class="form-field" style="margin-bottom:14px">
            <label for="full_name">Full name</label>
            <input id="full_name" name="full_name" required value="<?= h($_POST['full_name'] ?? '') ?>">
        </div>
        <div class="form-field" style="margin-bottom:14px">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" required value="<?= h($_POST['email'] ?? '') ?>">
        </div>
        <div class="form-field" style="margin-bottom:14px">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" required minlength="6" autocomplete="new-password">
        </div>
        <div class="form-field" style="margin-bottom:14px">
            <label for="password_confirm">Confirm password</label>
            <input id="password_confirm" name="password_confirm" type="password" required minlength="6" autocomplete="new-password">
        </div>
        <div class="form-field" style="margin-bottom:18px">
            <label for="role">Role</label>
            <select id="role" name="role" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:0.9rem">
                <?php
                $sel = $_POST['role'] ?? 'user';
                $opts = [
                    'user' => 'Field Officer (mobile app user)',
                    'admin' => 'Administrator',
                    'viewer' => 'Viewer (read-only)',
                ];
                foreach ($opts as $val => $label) :
                    ?>
                    <option value="<?= h($val) ?>"<?= (string)$sel === $val ? ' selected' : '' ?>><?= h($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Create user</button>
    </form>
</div>

<?php
require __DIR__ . '/includes/layout_end.php';
