<?php
/**
 * Core functions for the application
 */

/**
 * Get the current page URL
 */
function get_current_url() {
    return APP_URL . '/' . (isset($_GET['route']) ? '?route=' . $_GET['route'] : '');
}

/**
 * Redirect to a specific page
 */
function redirect($route = '') {
    $url = APP_URL;
    if (!empty($route)) {
        $url .= '?route=' . $route;
    }
    header('Location: ' . $url);
    exit;
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user data
 */
function get_current_app_user() {
    if (!is_logged_in()) {
        return false;
    }
    
    global $db;
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Display error message
 */
function display_error($message) {
    echo '<div class="alert alert-danger">' . htmlspecialchars($message) . '</div>';
}

/**
 * Display success message
 */
function display_success($message) {
    echo '<div class="alert alert-success">' . htmlspecialchars($message) . '</div>';
}

/**
 * Generate CSRF token
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Check CSRF token
 */
function check_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
