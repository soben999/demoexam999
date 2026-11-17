<?php
define('APP', true);
require_once 'config.php';

admin_logout();
flash('success', 'Вы вышли из системы.');
header('Location: index.php');
exit;
