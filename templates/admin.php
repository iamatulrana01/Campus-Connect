<?php
/**
 * Admin Dashboard
 * Provides content moderation, analytics, and management tools
 */

// Redirect if not admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    redirect('home');
}

$user_id = $_SESSION['user_id'];
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';
$error = '';
$success = '';

// Handle report resolution
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resolve_report'])) {
    $report_id = (int)$_POST['report_id'];
    $status = sanitize($_POST['status']);
    $valid_statuses = ['reviewed', 'resolved', 'dismissed'];
    
    if (!in_array($status, $valid_statuses)) {
        $error = "Invalid status provided.";
    } else {
        $stmt = $db->prepare("UPDATE reports SET status = ?, resolved_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $status, $report_id);
        
        if ($stmt->execute()) {
            $success = "Report has been marked as " . $status . ".";
        } else {
            $error = "Failed to update report status.";
        }
    }
}

// Handle resource approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['moderate_resource'])) {
    $resource_id = (int)$_POST['resource_id'];
    $action = $_POST['action'];
    
    if ($action === 'approve') {
        $stmt = $db->prepare("UPDATE resources SET status = 'approved' WHERE id = ?");
        $stmt->bind_param("i", $resource_id);
        if ($stmt->execute()) {
            $success = "Resource has been approved.";
        } else {
            $error = "Failed to approve resource.";
        }
    } elseif ($action === 'reject') {
        $reason = sanitize($_POST['rejection_reason']);
        $stmt = $db->prepare("UPDATE resources SET status = 'rejected', rejection_reason = ? WHERE id = ?");
        $stmt->bind_param("si", $reason, $resource_id);
        if ($stmt->execute()) {
            $success = "Resource has been rejected.";
        } else {
            $error = "Failed to reject resource.";
        }
    } else {
        $error = "Invalid action provided.";
    }
}
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Admin Dashboard</h1>
        <a href="?route=home" class="btn btn-outline-primary">Back to Site</a>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Admin sidebar -->
        <div class="col-md-3">
            <div class="list-group mb-4">
                <a href="?route=admin&tab=dashboard" class="list-group-item list-group-item-action <?php echo $tab === 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
                <a href="?route=admin&tab=reports" class="list-group-item list-group-item-action <?php echo $tab === 'reports' ? 'active' : ''; ?>">
                    <i class="fas fa-flag me-2"></i> Reports
                </a>
                <a href="?route=admin&tab=resources" class="list-group-item list-group-item-action <?php echo $tab === 'resources' ? 'active' : ''; ?>">
                    <i class="fas fa-file-alt me-2"></i> Resource Moderation
                </a>
                <a href="?route=admin&tab=users" class="list-group-item list-group-item-action <?php echo $tab === 'users' ? 'active' : ''; ?>">
                    <i class="fas fa-users me-2"></i> User Management
                </a>
                <a href="?route=admin&tab=analytics" class="list-group-item list-group-item-action <?php echo $tab === 'analytics' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar me-2"></i> Analytics
                </a>
            </div>
        </div>
        
        <!-- Admin content -->
        <div class="col-md-9">
            <?php
            // Display different content based on selected tab
            switch ($tab) {
                case 'dashboard':
                    include APP_DIR . '/templates/admin/dashboard.php';
                    break;
                case 'reports':
                    include APP_DIR . '/templates/admin/reports.php';
                    break;
                case 'resources':
                    include APP_DIR . '/templates/admin/resources.php';
                    break;
                case 'users':
                    include APP_DIR . '/templates/admin/users.php';
                    break;
                case 'analytics':
                    include APP_DIR . '/templates/admin/analytics.php';
                    break;
                default:
                    include APP_DIR . '/templates/admin/dashboard.php';
                    break;
            }
            ?>
        </div>
    </div>
</div>
