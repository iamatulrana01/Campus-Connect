<?php
/**
 * Academic Resource Sharing Platform
 * Main entry point for the application
 */

// Define app constants
define('APP_DIR', __DIR__);
define('APP_URL', 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']));
define('APP_VERSION', '1.0.0');

// Start session
session_start();

// Include required files
require_once APP_DIR . '/includes/functions.php';
require_once APP_DIR . '/includes/auth.php';
require_once APP_DIR . '/includes/database.php';

// Route the request
$route = isset($_GET['route']) ? $_GET['route'] : 'home';

// Handle API requests
if (strpos($route, 'api/') === 0) {
    header('Content-Type: application/json');
    $api_route = substr($route, 4);
    
    require_once APP_DIR . '/includes/api/api-handler.php';
    $api = new ApiHandler();
    $api->handleRequest($api_route);
    exit;
}

// Include header
include APP_DIR . '/templates/header.php';

// Load the requested page
switch ($route) {
    case 'login':
        include APP_DIR . '/templates/login.php';
        break;
    case 'register':
        include APP_DIR . '/templates/register.php';
        break;
    case 'resources':
        include APP_DIR . '/templates/resources.php';
        break;
    case 'discussions':
        include APP_DIR . '/templates/discussions.php';
        break;
    case 'study-groups':
        include APP_DIR . '/templates/study-groups.php';
        break;
    case 'profile':
        include APP_DIR . '/templates/profile.php';
        break;
    case 'messages':
        include APP_DIR . '/templates/messages.php';
        break;
    case 'admin':
        // Check if user is admin
        if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            include APP_DIR . '/templates/admin.php';
        } else {
            // Redirect non-admin users to home
            redirect('home');
        }
        break;
    case 'report':
        // Handle report submission
        if (isset($_SESSION['user_id'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_report'])) {
                $content_type = sanitize($_POST['content_type']);
                $content_id = (int)$_POST['content_id'];
                $reason = sanitize($_POST['report_reason']);
                $details = sanitize($_POST['report_details']);
                $user_id = $_SESSION['user_id'];
                
                $valid_types = ['resource', 'discussion', 'comment', 'user'];
                if (!in_array($content_type, $valid_types)) {
                    $_SESSION['error'] = "Invalid content type.";
                } else {
                    $full_reason = $reason . ': ' . $details;
                    
                    $stmt = $db->prepare("INSERT INTO reports (content_type, content_id, reporter_id, reason, status, created_at) 
                                         VALUES (?, ?, ?, ?, 'pending', NOW())");
                    $stmt->bind_param("siis", $content_type, $content_id, $user_id, $full_reason);
                    
                    if ($stmt->execute()) {
                        $_SESSION['success'] = "Your report has been submitted and will be reviewed by moderators.";
                    } else {
                        $_SESSION['error'] = "Failed to submit report. Please try again.";
                    }
                }
                
                // Redirect back to the content
                switch ($content_type) {
                    case 'resource':
                        redirect('resources?id=' . $content_id);
                        break;
                    case 'discussion':
                    case 'comment':
                        redirect('discussions?id=' . $content_id);
                        break;
                    case 'user':
                        redirect('profile?user=' . $content_id);
                        break;
                    default:
                        redirect('home');
                        break;
                }
            } else {
                redirect('home');
            }
        } else {
            redirect('login');
        }
        break;
    case 'home':
    default:
        include APP_DIR . '/templates/home.php';
        break;
}

// Include footer
include APP_DIR . '/templates/footer.php';
?>
