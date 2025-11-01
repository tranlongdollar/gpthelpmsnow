<?php
declare(strict_types=1);
/** /public_html/admin/logout.php */
require __DIR__ . '/../../app/auth.php';
auth_logout();
header('Location: /admin/login.php');
exit;
