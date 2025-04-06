<?php
/**
 * Authentication functions
 */

/**
 * Register a new user
 * 
 * @param string $username
 * @param string $email
 * @param string $password
 * @param string $name
 * @return array Response with status and message
 */
function register_user($username, $email, $password, $name) {
    global $db;
    
    // Validate input
    if (empty($username) || empty($email) || empty($password) || empty($name)) {
        return ['status' => 'error', 'message' => 'All fields are required'];
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['status' => 'error', 'message' => 'Invalid email format'];
    }
    
    if (strlen($password) < 8) {
        return ['status' => 'error', 'message' => 'Password must be at least 8 characters long'];
    }
    
    // Check if username or email already exists
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return ['status' => 'error', 'message' => 'Username or email already exists'];
    }
    
    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert the new user
    try {
        $stmt = $db->prepare("INSERT INTO users (username, email, password, name, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssss", $username, $email, $hashed_password, $name);
        $success = $stmt->execute();
        
        if ($success) {
            return ['status' => 'success', 'message' => 'Registration successful! You can now log in.'];
        } else {
            return ['status' => 'error', 'message' => 'Registration failed: ' . $db->error];
        }
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => 'Registration failed: ' . $e->getMessage()];
    }
}

/**
 * Login a user
 * 
 * @param string $username Username or email
 * @param string $password 
 * @return array Response with status and message
 */
function login_user($username, $password) {
    global $db;
    
    // Validate input
    if (empty($username) || empty($password)) {
        return ['status' => 'error', 'message' => 'Username and password are required'];
    }
    
    // Check if username exists
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['status' => 'error', 'message' => 'Invalid username or password'];
    }
    
    $user = $result->fetch_assoc();
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        return ['status' => 'error', 'message' => 'Invalid username or password'];
    }
    
    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['role'] = $user['role'] ?? 'user';
    
    return ['status' => 'success', 'message' => 'Login successful!', 'user' => $user];
}

/**
 * Logout the current user
 */
function logout_user() {
    // Unset all session variables
    $_SESSION = [];
    
    // Destroy the session
    session_destroy();
    
    return ['status' => 'success', 'message' => 'Logged out successfully'];
}

/**
 * Check if user is admin
 * 
 * @return bool
 */
function is_admin() {
    return is_logged_in() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}
