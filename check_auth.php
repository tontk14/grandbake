<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

$isLoggedIn = isset($_SESSION['user_id']);

echo json_encode([
    'isLoggedIn' => $isLoggedIn,
    'userName'   => $_SESSION['first_name'] ?? '',
    'fullName'   => trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '')),
    'email'      => $_SESSION['email'] ?? ''
], JSON_UNESCAPED_UNICODE);
