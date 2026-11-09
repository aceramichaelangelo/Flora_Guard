<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

tg_session_start();
if (!empty($_SESSION['tg_user']) && ($_SESSION['tg_user']['role'] ?? '') === 'admin') {
    header('Location: dashboard.php', true, 302);
    exit;
}
header('Location: login.php', true, 302);
exit;
