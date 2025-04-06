<?php
/**
 * Admin Reports Management
 * Manages reported content
 */

// Get list of reports
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$type_filter = isset($_GET['type']) ? sanitize($_GET['type']) : '';

// Build query with filters
$query = "SELECT r.*, u.username as reporter_name 
          FROM reports r
          JOIN users u ON r.reporter_id = u.id 
          WHERE 1=1";
$params = [];
$types = "";

if (!empty($status_filter)) {
    $query .= " AND r.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($type_filter)) {
    $query .= " AND r.content_type = ?";
    $params[] = $type_filter;
    $types .= "s";
}

$query .= " ORDER BY FIELD(r.status, 'pending', 'reviewed', 'resolved', 'dismissed'), r.created_at DESC";

$stmt = $db->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$reports = $stmt->get_result();
?>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h5 class="m-0 font-weight-bold text-primary">Content Reports</h5>
    </div>
    <div class="card-body">
        <!-- Filters -->
        <div class="mb-4">
            <form method="get" action="" class="row g-3 align-items-end">
                <input type="hidden" name="route" value="admin">
                <input type="hidden" name="tab" value="reports">
                
                <div class="col-md-4">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="reviewed" <?php echo $status_filter === 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                        <option value="resolved" <?php echo $status_filter === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                        <option value="dismissed" <?php echo $status_filter === 'dismissed' ? 'selected' : ''; ?>>Dismissed</option>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label for="type" class="form-label">Content Type</label>
                    <select class="form-select" id="type" name="type">
                        <option value="">All Types</option>
                        <option value="resource" <?php echo $type_filter === 'resource' ? 'selected' : ''; ?>>Resource</option>
                        <option value="discussion" <?php echo $type_filter === 'discussion' ? 'selected' : ''; ?>>Discussion</option>
                        <option value="comment" <?php echo $type_filter === 'comment' ? 'selected' : ''; ?>>Comment</option>
                        <option value="user" <?php echo $type_filter === 'user' ? 'selected' : ''; ?>>User</option>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="?route=admin&tab=reports" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
        
        <!-- Reports Table -->
        <div class="table-responsive">
            <table class="table table-bordered" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Type</th>
                        <th>Reason</th>
                        <th>Reported By</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($reports->num_rows === 0): ?>
                        <tr>
                            <td colspan="7" class="text-center">No reports found</td>
                        </tr>
                    <?php else: ?>
                        <?php while ($report = $reports->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $report['id']; ?></td>
                                <td><?php echo ucfirst(htmlspecialchars($report['content_type'])); ?></td>
                                <td><?php echo htmlspecialchars($report['reason']); ?></td>
                                <td><?php echo htmlspecialchars($report['reporter_name']); ?></td>
                                <td><?php echo date('M j, Y g:i a', strtotime($report['created_at'])); ?></td>
                                <td>
                                    <?php if ($report['status'] === 'pending'): ?>
                                        <span class="badge bg-warning">Pending</span>
                                    <?php elseif ($report['status'] === 'resolved'): ?>
                                        <span class="badge bg-success">Resolved</span>
                                    <?php elseif ($report['status'] === 'dismissed'): ?>
                                        <span class="badge bg-secondary">Dismissed</span>
                                    <?php else: ?>
                                        <span class="badge bg-info">Reviewed</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#viewReportModal<?php echo $report['id']; ?>">
                                        View
                                    </button>
                                </td>
                            </tr>
                            
                            <!-- View Report Modal -->
                            <div class="modal fade" id="viewReportModal<?php echo $report['id']; ?>" tabindex="-1" aria-labelledby="viewReportModalLabel<?php echo $report['id']; ?>" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="viewReportModalLabel<?php echo $report['id']; ?>">Report Details</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <p><strong>Report ID:</strong> <?php echo $report['id']; ?></p>
                                                    <p><strong>Content Type:</strong> <?php echo ucfirst(htmlspecialchars($report['content_type'])); ?></p>
                                                    <p><strong>Content ID:</strong> <?php echo $report['content_id']; ?></p>
                                                    <p><strong>Reported By:</strong> <?php echo htmlspecialchars($report['reporter_name']); ?></p>
                                                    <p><strong>Date Reported:</strong> <?php echo date('F j, Y g:i a', strtotime($report['created_at'])); ?></p>
                                                    <p><strong>Status:</strong> 
                                                        <?php if ($report['status'] === 'pending'): ?>
                                                            <span class="badge bg-warning">Pending</span>
                                                        <?php elseif ($report['status'] === 'resolved'): ?>
                                                            <span class="badge bg-success">Resolved</span>
                                                        <?php elseif ($report['status'] === 'dismissed'): ?>
                                                            <span class="badge bg-secondary">Dismissed</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-info">Reviewed</span>
                                                        <?php endif; ?>
                                                    </p>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="card">
                                                        <div class="card-header">
                                                            <h6 class="mb-0">Reason for Report</h6>
                                                        </div>
                                                        <div class="card-body">
                                                            <p><?php echo htmlspecialchars($report['reason']); ?></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <hr>
                                            
                                            <div class="content-preview mb-3">
                                                <h6>Content Preview</h6>
                                                <?php
                                                // Get content details based on type
                                                $content_details = [];
                                                
                                                if ($report['content_type'] === 'resource') {
                                                    $stmt = $db->prepare("SELECT r.*, u.username FROM resources r JOIN users u ON r.user_id = u.id WHERE r.id = ?");
                                                    $stmt->bind_param("i", $report['content_id']);
                                                    $stmt->execute();
                                                    $result = $stmt->get_result();
                                                    if ($result->num_rows > 0) {
                                                        $content = $result->fetch_assoc();
                                                        $content_details = [
                                                            'title' => $content['title'],
                                                            'user' => $content['username'],
                                                            'content' => $content['description'],
                                                            'url' => '?route=resources&id=' . $content['id']
                                                        ];
                                                    }
                                                } elseif ($report['content_type'] === 'discussion') {
                                                    $stmt = $db->prepare("SELECT d.*, u.username FROM discussions d JOIN users u ON d.user_id = u.id WHERE d.id = ?");
                                                    $stmt->bind_param("i", $report['content_id']);
                                                    $stmt->execute();
                                                    $result = $stmt->get_result();
                                                    if ($result->num_rows > 0) {
                                                        $content = $result->fetch_assoc();
                                                        $content_details = [
                                                            'title' => $content['title'],
                                                            'user' => $content['username'],
                                                            'content' => $content['content'],
                                                            'url' => '?route=discussions&id=' . $content['id']
                                                        ];
                                                    }
                                                } elseif ($report['content_type'] === 'comment') {
                                                    $stmt = $db->prepare("SELECT c.*, u.username, d.title as discussion_title, d.id as discussion_id 
                                                                        FROM comments c 
                                                                        JOIN users u ON c.user_id = u.id 
                                                                        JOIN discussions d ON c.discussion_id = d.id 
                                                                        WHERE c.id = ?");
                                                    $stmt->bind_param("i", $report['content_id']);
                                                    $stmt->execute();
                                                    $result = $stmt->get_result();
                                                    if ($result->num_rows > 0) {
                                                        $content = $result->fetch_assoc();
                                                        $content_details = [
                                                            'title' => 'Comment on "' . $content['discussion_title'] . '"',
                                                            'user' => $content['username'],
                                                            'content' => $content['content'],
                                                            'url' => '?route=discussions&id=' . $content['discussion_id']
                                                        ];
                                                    }
                                                } elseif ($report['content_type'] === 'user') {
                                                    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                                                    $stmt->bind_param("i", $report['content_id']);
                                                    $stmt->execute();
                                                    $result = $stmt->get_result();
                                                    if ($result->num_rows > 0) {
                                                        $content = $result->fetch_assoc();
                                                        $content_details = [
                                                            'title' => $content['username'] . '\'s Profile',
                                                            'user' => $content['username'],
                                                            'content' => $content['bio'] ?? 'No bio provided',
                                                            'url' => '?route=profile&user=' . $content['id']
                                                        ];
                                                    }
                                                }
                                                ?>
                                                
                                                <?php if (!empty($content_details)): ?>
                                                    <div class="card">
                                                        <div class="card-header">
                                                            <h6><?php echo htmlspecialchars($content_details['title']); ?></h6>
                                                            <small>By: <?php echo htmlspecialchars($content_details['user']); ?></small>
                                                        </div>
                                                        <div class="card-body">
                                                            <p><?php echo nl2br(htmlspecialchars($content_details['content'])); ?></p>
                                                            <a href="<?php echo $content_details['url']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">View Full Content</a>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="alert alert-warning">
                                                        Content not found. It may have been deleted.
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <?php if ($report['status'] === 'pending'): ?>
                                                <hr>
                                                <div class="action-form">
                                                    <h6>Take Action</h6>
                                                    <form method="post" action="?route=admin&tab=reports">
                                                        <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                                        <div class="mb-3">
                                                            <label for="status<?php echo $report['id']; ?>" class="form-label">Update Status</label>
                                                            <select class="form-select" id="status<?php echo $report['id']; ?>" name="status" required>
                                                                <option value="">Select Status</option>
                                                                <option value="reviewed">Mark as Reviewed</option>
                                                                <option value="resolved">Mark as Resolved</option>
                                                                <option value="dismissed">Dismiss Report</option>
                                                            </select>
                                                        </div>
                                                        <button type="submit" name="resolve_report" class="btn btn-primary">Update Status</button>
                                                    </form>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
