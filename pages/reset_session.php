<?php
session_start();

// Verify CSRF token
$headers = getallheaders();
if (!isset($headers['X-CSRF-Token']) || !isset($_SESSION['csrf_token']) || $headers['X-CSRF-Token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    exit('CSRF token validation failed');
}

// Check if request is AJAX
if (!isset($headers['X-Requested-With']) || $headers['X-Requested-With'] !== 'XMLHttpRequest') {
    http_response_code(403);
    exit('Invalid request method');
}

// Check if user is logged in
if (!isset($_SESSION['admin']) || !isset($_SESSION['admin']['authenticated']) || $_SESSION['admin']['authenticated'] !== true) {
    http_response_code(401);
    exit('Not authenticated');
}

// Update session activity timestamp
$_SESSION['last_activity'] = time();

// Return success response
http_response_code(200);
echo json_encode(['status' => 'success', 'message' => 'Session refreshed successfully']);
?>