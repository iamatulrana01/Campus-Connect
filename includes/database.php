<?php
/**
 * Database connection
 */

// Database configuration
$db_config = [
    'host' => '127.0.0.1',
    'dbname' => 'collibration_app',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4'
];

// Connect to MySQL database
$db = new mysqli($db_config['host'], $db_config['username'], $db_config['password'], $db_config['dbname']);

// Check connection
if ($db->connect_error) {
    die("Database connection failed: " . $db->connect_error);
}

// Set charset
$db->set_charset($db_config['charset']);

// Include schema creation
require_once APP_DIR . '/includes/schema.php';

// Create tables if needed
create_database_tables($db);
