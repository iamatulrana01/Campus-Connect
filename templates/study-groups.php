<?php
/**
 * Study Groups Page
 * Allows users to view, create, join, and manage study groups
 */

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    redirect('login');
}

$user_id = $_SESSION['user_id'];
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$group_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$success = '';

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create a new study group
    if (isset($_POST['create_group'])) {
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        $subject = sanitize($_POST['subject']);
        $max_members = (int)$_POST['max_members'];
        
        if (empty($name) || empty($description) || empty($subject)) {
            $error = "All fields are required.";
        } else {
            $sql = "INSERT INTO study_groups (name, description, subject, max_members, created_by, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("sssii", $name, $description, $subject, $max_members, $user_id);
            
            if ($stmt->execute()) {
                $new_group_id = $db->insert_id;
                
                // Add creator as a member automatically
                $sql = "INSERT INTO group_members (group_id, user_id, role, joined_at) 
                        VALUES (?, ?, 'admin', NOW())";
                $stmt = $db->prepare($sql);
                $stmt->bind_param("ii", $new_group_id, $user_id);
                $stmt->execute();
                
                $success = "Study group created successfully!";
                redirect('study-groups?action=view&id=' . $new_group_id);
            } else {
                $error = "Failed to create study group. Please try again.";
            }
        }
    }
    
    // Join a study group
    if (isset($_POST['join_group'])) {
        $group_id = (int)$_POST['group_id'];
        
        // Check if already a member
        $sql = "SELECT * FROM group_members WHERE group_id = ? AND user_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("ii", $group_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "You are already a member of this group.";
        } else {
            // Check if group is full
            $sql = "SELECT g.max_members, COUNT(gm.id) as current_members 
                    FROM study_groups g 
                    LEFT JOIN group_members gm ON g.id = gm.group_id 
                    WHERE g.id = ?
                    GROUP BY g.id";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("i", $group_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $group_info = $result->fetch_assoc();
            
            if ($group_info['current_members'] >= $group_info['max_members'] && $group_info['max_members'] > 0) {
                $error = "This group is already full.";
            } else {
                $sql = "INSERT INTO group_members (group_id, user_id, role, joined_at) 
                        VALUES (?, ?, 'member', NOW())";
                $stmt = $db->prepare($sql);
                $stmt->bind_param("ii", $group_id, $user_id);
                
                if ($stmt->execute()) {
                    $success = "You have successfully joined the group!";
                    redirect('study-groups?action=view&id=' . $group_id);
                } else {
                    $error = "Failed to join the group. Please try again.";
                }
            }
        }
    }
    
    // Leave a study group
    if (isset($_POST['leave_group'])) {
        $group_id = (int)$_POST['group_id'];
        
        // Check if user is the admin and if they're the only admin
        $sql = "SELECT COUNT(*) as admin_count FROM group_members WHERE group_id = ? AND role = 'admin'";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $group_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin_count = $result->fetch_assoc()['admin_count'];
        
        $sql = "SELECT role FROM group_members WHERE group_id = ? AND user_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("ii", $group_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_role = $result->fetch_assoc()['role'];
        
        if ($user_role === 'admin' && $admin_count <= 1) {
            $error = "You cannot leave the group as you are the only admin. Please assign another admin first.";
        } else {
            $sql = "DELETE FROM group_members WHERE group_id = ? AND user_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("ii", $group_id, $user_id);
            
            if ($stmt->execute()) {
                $success = "You have left the group.";
                redirect('study-groups');
            } else {
                $error = "Failed to leave the group. Please try again.";
            }
        }
    }
}

// Display based on action
switch ($action) {
    case 'create':
        // Form to create a new study group
        ?>
        <div class="container mt-4">
            <h1>Create a Study Group</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="post" action="?route=study-groups&action=create">
                <div class="mb-3">
                    <label for="name" class="form-label">Group Name</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                
                <div class="mb-3">
                    <label for="subject" class="form-label">Subject</label>
                    <input type="text" class="form-control" id="subject" name="subject" required>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="max_members" class="form-label">Maximum Members (0 for unlimited)</label>
                    <input type="number" class="form-control" id="max_members" name="max_members" min="0" value="10">
                </div>
                
                <button type="submit" name="create_group" class="btn btn-primary">Create Group</button>
                <a href="?route=study-groups" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
        <?php
        break;
        
    case 'view':
        // View a specific study group
        if (!$group_id) {
            redirect('study-groups');
        }
        
        $sql = "SELECT g.*, u.username as creator_name
                FROM study_groups g
                JOIN users u ON g.created_by = u.id
                WHERE g.id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $group_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo '<div class="container mt-4"><div class="alert alert-danger">Study group not found.</div></div>';
            echo '<div class="container"><a href="?route=study-groups" class="btn btn-primary">Back to Study Groups</a></div>';
            break;
        }
        
        $group = $result->fetch_assoc();
        
        // Check if user is a member
        $sql = "SELECT * FROM group_members WHERE group_id = ? AND user_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("ii", $group_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $is_member = $result->num_rows > 0;
        $member_info = $is_member ? $result->fetch_assoc() : null;
        $is_admin = $is_member && $member_info['role'] === 'admin';
        
        // Get all members
        $sql = "SELECT gm.*, u.username, u.email 
                FROM group_members gm
                JOIN users u ON gm.user_id = u.id
                WHERE gm.group_id = ?
                ORDER BY gm.role = 'admin' DESC, gm.joined_at ASC";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $group_id);
        $stmt->execute();
        $members_result = $stmt->get_result();
        
        // Count current members
        $sql = "SELECT COUNT(*) as count FROM group_members WHERE group_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $group_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $current_members = $result->fetch_assoc()['count'];
        ?>
        
        <div class="container mt-4">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2><?php echo htmlspecialchars($group['name']); ?></h2>
                    <span class="badge bg-primary"><?php echo htmlspecialchars($group['subject']); ?></span>
                </div>
                <div class="card-body">
                    <p class="card-text"><?php echo nl2br(htmlspecialchars($group['description'])); ?></p>
                    <ul class="list-group list-group-flush mb-3">
                        <li class="list-group-item">Created by: <?php echo htmlspecialchars($group['creator_name']); ?></li>
                        <li class="list-group-item">Created on: <?php echo date('F j, Y', strtotime($group['created_at'])); ?></li>
                        <li class="list-group-item">Members: <?php echo $current_members; ?><?php echo $group['max_members'] > 0 ? '/' . $group['max_members'] : ''; ?></li>
                    </ul>
                    
                    <?php if (!$is_member && ($group['max_members'] == 0 || $current_members < $group['max_members'])): ?>
                        <form method="post" action="?route=study-groups&action=view&id=<?php echo $group_id; ?>">
                            <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
                            <button type="submit" name="join_group" class="btn btn-success">Join Group</button>
                        </form>
                    <?php elseif ($is_member): ?>
                        <form method="post" action="?route=study-groups&action=view&id=<?php echo $group_id; ?>">
                            <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
                            <button type="submit" name="leave_group" class="btn btn-danger">Leave Group</button>
                        </form>
                    <?php elseif ($group['max_members'] > 0 && $current_members >= $group['max_members']): ?>
                        <div class="alert alert-warning">This group is full.</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <h3>Members</h3>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Joined</th>
                            <?php if ($is_admin): ?><th>Actions</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($member = $members_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($member['username']); ?></td>
                                <td>
                                    <span class="badge <?php echo $member['role'] === 'admin' ? 'bg-danger' : 'bg-secondary'; ?>">
                                        <?php echo ucfirst(htmlspecialchars($member['role'])); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($member['joined_at'])); ?></td>
                                <?php if ($is_admin && $member['user_id'] != $user_id): ?>
                                <td>
                                    <!-- Admin actions would go here -->
                                    <button class="btn btn-sm btn-outline-secondary">Change Role</button>
                                    <button class="btn btn-sm btn-outline-danger">Remove</button>
                                </td>
                                <?php elseif ($is_admin): ?>
                                <td>
                                    <span class="text-muted">You</span>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-3">
                <a href="?route=study-groups" class="btn btn-primary">Back to Study Groups</a>
            </div>
        </div>
        <?php
        break;
        
    case 'list':
    default:
        // List all study groups
        $search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
        $subject_filter = isset($_GET['subject']) ? sanitize($_GET['subject']) : '';
        
        // Get list of subjects for filter
        $sql = "SELECT DISTINCT subject FROM study_groups ORDER BY subject";
        $subjects_result = $db->query($sql);
        
        // Build query with filters
        $query = "SELECT g.*, u.username as creator_name, COUNT(gm.id) as member_count
                FROM study_groups g
                JOIN users u ON g.created_by = u.id
                LEFT JOIN group_members gm ON g.id = gm.group_id
                WHERE 1=1";
        $params = [];
        $types = "";
        
        if (!empty($search)) {
            $query .= " AND (g.name LIKE ? OR g.description LIKE ?)";
            $search_param = "%$search%";
            $params[] = $search_param;
            $params[] = $search_param;
            $types .= "ss";
        }
        
        if (!empty($subject_filter)) {
            $query .= " AND g.subject = ?";
            $params[] = $subject_filter;
            $types .= "s";
        }
        
        $query .= " GROUP BY g.id ORDER BY g.created_at DESC";
        
        $stmt = $db->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $groups_result = $stmt->get_result();
        
        // Get user's groups
        $sql = "SELECT g.id
                FROM study_groups g
                JOIN group_members gm ON g.id = gm.group_id
                WHERE gm.user_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user_groups_result = $stmt->get_result();
        $user_groups = [];
        while ($row = $user_groups_result->fetch_assoc()) {
            $user_groups[] = $row['id'];
        }
        ?>
        
        <div class="container mt-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Study Groups</h1>
                <a href="?route=study-groups&action=create" class="btn btn-primary">Create New Group</a>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <!-- Search and Filter -->
            <form method="get" class="mb-4">
                <input type="hidden" name="route" value="study-groups">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" placeholder="Search by name or description" value="<?php echo htmlspecialchars($search); ?>">
                            <button class="btn btn-outline-secondary" type="submit">Search</button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" name="subject" onchange="this.form.submit()">
                            <option value="">All Subjects</option>
                            <?php while ($subject = $subjects_result->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($subject['subject']); ?>" <?php echo $subject_filter === $subject['subject'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($subject['subject']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <a href="?route=study-groups" class="btn btn-outline-secondary w-100">Clear</a>
                    </div>
                </div>
            </form>
            
            <?php if ($groups_result->num_rows === 0): ?>
                <div class="alert alert-info">No study groups found.</div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    <?php while ($group = $groups_result->fetch_assoc()): ?>
                        <div class="col">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h5 class="card-title mb-0"><?php echo htmlspecialchars($group['name']); ?></h5>
                                </div>
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($group['subject']); ?></h6>
                                    <p class="card-text">
                                        <?php echo nl2br(htmlspecialchars(substr($group['description'], 0, 100) . (strlen($group['description']) > 100 ? '...' : ''))); ?>
                                    </p>
                                    <div class="d-flex justify-content-between">
                                        <small class="text-muted">Created: <?php echo date('M j, Y', strtotime($group['created_at'])); ?></small>
                                        <small class="text-muted">Members: 
                                            <?php echo $group['member_count']; ?>
                                            <?php echo $group['max_members'] > 0 ? '/' . $group['max_members'] : ''; ?>
                                        </small>
                                    </div>
                                </div>
                                <div class="card-footer d-flex justify-content-between align-items-center">
                                    <a href="?route=study-groups&action=view&id=<?php echo $group['id']; ?>" class="btn btn-primary btn-sm">View Details</a>
                                    <?php if (in_array($group['id'], $user_groups)): ?>
                                        <span class="badge bg-success">Member</span>
                                    <?php elseif ($group['max_members'] > 0 && $group['member_count'] >= $group['max_members']): ?>
                                        <span class="badge bg-secondary">Full</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        break;
}
?>
