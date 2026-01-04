<?php
require __DIR__ . '/_init.php';
session_destroy();
header('Location: login');
exit;
