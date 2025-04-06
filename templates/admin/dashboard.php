<?php
/**
 * Admin Dashboard Overview
 * Shows summary statistics and recent activity
 */

// Get counts for dashboard
$counts = [];

// User count
$result = $db->query("SELECT COUNT(*) as count FROM users");
$counts['users'] = $result->fetch_assoc()['count'];

// Resource count
$result = $db->query("SELECT COUNT(*) as count FROM resources");
$counts['resources'] = $result->fetch_assoc()['count'];

// Discussion count
$result = $db->query("SELECT COUNT(*) as count FROM discussions");
$counts['discussions'] = $result->fetch_assoc()['count'];

// Study group count
$result = $db->query("SELECT COUNT(*) as count FROM study_groups");
$counts['groups'] = $result->fetch_assoc()['count'];

// Pending reports count
$result = $db->query("SELECT COUNT(*) as count FROM reports WHERE status = 'pending'");
$counts['pending_reports'] = $result->fetch_assoc()['count'];

// Recent users
$result = $db->query("SELECT id, username, name, email, created_at FROM users ORDER BY created_at DESC LIMIT 5");
$recent_users = $result;

// Recent resources
$result = $db->query("SELECT r.id, r.title, r.views, r.downloads, r.created_at, u.username 
                     FROM resources r 
                     JOIN users u ON r.user_id = u.id 
                     ORDER BY r.created_at DESC LIMIT 5");
$recent_resources = $result;

// Top resources by views/downloads
$result = $db->query("SELECT r.id, r.title, r.views, r.downloads, u.username 
                     FROM resources r 
                     JOIN users u ON r.user_id = u.id 
                     ORDER BY (r.views + r.downloads) DESC LIMIT 5");
$top_resources = $result;

// Recent reports
$result = $db->query("SELECT r.id, r.content_type, r.reason, r.status, r.created_at, u.username as reporter
                     FROM reports r
                     JOIN users u ON r.reporter_id = u.id
                     ORDER BY r.created_at DESC LIMIT 5");
$recent_reports = $result;
?>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h5 class="m-0 font-weight-bold text-primary">Dashboard Overview</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <!-- Users -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Users</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $counts['users']; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resources -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Resources</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $counts['resources']; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-file-alt fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Discussions -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Discussions</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $counts['discussions']; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-comments fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Study Groups -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Study Groups</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $counts['groups']; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users-cog fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Resources -->
            <div class="col-xl-6 col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Recent Resources</h6>
                        <a href="?route=admin&tab=resources" class="btn btn-sm btn-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>By</th>
                                        <th>Views</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($recent_resources->num_rows === 0): ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No resources found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php while ($resource = $recent_resources->fetch_assoc()): ?>
                                            <tr>
                                                <td><a href="?route=resources&id=<?php echo $resource['id']; ?>"><?php echo htmlspecialchars($resource['title']); ?></a></td>
                                                <td><?php echo htmlspecialchars($resource['username']); ?></td>
                                                <td><?php echo $resource['views']; ?></td>
                                                <td><?php echo date('M j, Y', strtotime($resource['created_at'])); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Users -->
            <div class="col-xl-6 col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Recent Users</h6>
                        <a href="?route=admin&tab=users" class="btn btn-sm btn-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Joined</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($recent_users->num_rows === 0): ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No users found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php while ($user = $recent_users->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Top Resources -->
            <div class="col-xl-6 col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Top Resources</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>By</th>
                                        <th>Views</th>
                                        <th>Downloads</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($top_resources->num_rows === 0): ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No resources found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php while ($resource = $top_resources->fetch_assoc()): ?>
                                            <tr>
                                                <td><a href="?route=resources&id=<?php echo $resource['id']; ?>"><?php echo htmlspecialchars($resource['title']); ?></a></td>
                                                <td><?php echo htmlspecialchars($resource['username']); ?></td>
                                                <td><?php echo $resource['views']; ?></td>
                                                <td><?php echo $resource['downloads']; ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Reports -->
            <div class="col-xl-6 col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Recent Reports</h6>
                        <?php if ($counts['pending_reports'] > 0): ?>
                            <span class="badge bg-danger"><?php echo $counts['pending_reports']; ?> pending</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Reason</th>
                                        <th>Reported By</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($recent_reports->num_rows === 0): ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No reports found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php while ($report = $recent_reports->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo ucfirst(htmlspecialchars($report['content_type'])); ?></td>
                                                <td><?php echo htmlspecialchars($report['reason']); ?></td>
                                                <td><?php echo htmlspecialchars($report['reporter']); ?></td>
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
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            <a href="?route=admin&tab=reports" class="btn btn-sm btn-primary">Manage Reports</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
