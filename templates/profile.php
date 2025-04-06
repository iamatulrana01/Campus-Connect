<?php
/**
 * User Profile Page
 * Allows users to view and edit their profile information
 */

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    redirect('login');
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get user data
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $academic_year = sanitize($_POST['academic_year']);
    $major = sanitize($_POST['major']);
    $bio = sanitize($_POST['bio']);
    
    // Check if email exists for another user
    $sql = "SELECT id FROM users WHERE email = ? AND id != ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $error = "Email is already in use by another account.";
    } else {
        // Update user profile
        $sql = "UPDATE users SET name = ?, email = ?, academic_year = ?, major = ?, bio = ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("ssssi", $name, $email, $academic_year, $major, $bio, $user_id);
        
        if ($stmt->execute()) {
            // Update session values
            $_SESSION['name'] = $name;
            $success = "Profile updated successfully!";
        } else {
            $error = "Failed to update profile. Please try again.";
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify current password
    $sql = "SELECT password FROM users WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();
    
    if (!password_verify($current_password, $user_data['password'])) {
        $error = "Current password is incorrect.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } elseif (strlen($new_password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("si", $hashed_password, $user_id);
        
        if ($stmt->execute()) {
            $success = "Password changed successfully!";
        } else {
            $error = "Failed to change password. Please try again.";
        }
    }
}

// Get user's activity stats
$sql = "SELECT 
            (SELECT COUNT(*) FROM resources WHERE user_id = ?) as resource_count,
            (SELECT COUNT(*) FROM discussions WHERE user_id = ?) as discussion_count,
            (SELECT COUNT(*) FROM group_members WHERE user_id = ?) as group_count";
$stmt = $db->prepare($sql);
$stmt->bind_param("iii", $user_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$stats = $result->fetch_assoc();

// Get user's recently uploaded resources
$sql = "SELECT id, title, file_type, created_at FROM resources 
        WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
$stmt = $db->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_resources = $stmt->get_result();

// Get user's study groups
$sql = "SELECT g.id, g.name, g.subject 
        FROM study_groups g 
        JOIN group_members gm ON g.id = gm.group_id 
        WHERE gm.user_id = ? 
        ORDER BY gm.joined_at DESC";
$stmt = $db->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_groups = $stmt->get_result();

// Get user's recent discussions
$sql = "SELECT id, title, created_at FROM discussions 
        WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
$stmt = $db->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_discussions = $stmt->get_result();

// Academic year options
$academic_years = ['Freshman', 'Sophomore', 'Junior', 'Senior', 'Graduate', 'PhD', 'Other'];
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-3">
            <!-- Profile sidebar -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Profile</h5>
                </div>
                <div class="card-body text-center">
                    <div class="profile-avatar mb-3">
                        <span class="avatar-circle">
                            <?php echo substr($user['name'] ?? $user['username'], 0, 1); ?>
                        </span>
                    </div>
                    <h5><?php echo htmlspecialchars($user['name']); ?></h5>
                    <p class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></p>
                    <?php if (!empty($user['major'])): ?>
                        <p class="badge bg-primary"><?php echo htmlspecialchars($user['major']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($user['academic_year'])): ?>
                        <p class="badge bg-secondary"><?php echo htmlspecialchars($user['academic_year']); ?></p>
                    <?php endif; ?>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Resources
                        <span class="badge bg-primary rounded-pill"><?php echo $stats['resource_count']; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Discussions
                        <span class="badge bg-primary rounded-pill"><?php echo $stats['discussion_count']; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Study Groups
                        <span class="badge bg-primary rounded-pill"><?php echo $stats['group_count']; ?></span>
                    </li>
                </ul>
                <div class="card-footer text-center">
                    <small class="text-muted">Joined: <?php echo date('F j, Y', strtotime($user['created_at'])); ?></small>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <!-- Content tabs -->
            <ul class="nav nav-tabs" id="profileTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="dashboard-tab" data-bs-toggle="tab" data-bs-target="#dashboard" type="button" role="tab" aria-controls="dashboard" aria-selected="true">Dashboard</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="edit-profile-tab" data-bs-toggle="tab" data-bs-target="#edit-profile" type="button" role="tab" aria-controls="edit-profile" aria-selected="false">Edit Profile</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password" type="button" role="tab" aria-controls="password" aria-selected="false">Change Password</button>
                </li>
            </ul>
            
            <?php if ($error): ?>
                <div class="alert alert-danger mt-3"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success mt-3"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="tab-content" id="profileTabsContent">
                <!-- Dashboard Tab -->
                <div class="tab-pane fade show active" id="dashboard" role="tabpanel" aria-labelledby="dashboard-tab">
                    <div class="card mt-3">
                        <div class="card-body">
                            <h5 class="card-title">Your Dashboard</h5>
                            
                            <!-- Study Groups -->
                            <h6 class="mt-4">Your Study Groups</h6>
                            <?php if ($user_groups->num_rows > 0): ?>
                                <div class="list-group mb-4">
                                    <?php while ($group = $user_groups->fetch_assoc()): ?>
                                        <a href="?route=study-groups&action=view&id=<?php echo $group['id']; ?>" class="list-group-item list-group-item-action">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($group['name']); ?></h6>
                                                <small class="text-muted"><?php echo htmlspecialchars($group['subject']); ?></small>
                                            </div>
                                        </a>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">You haven't joined any study groups yet.</p>
                                <a href="?route=study-groups" class="btn btn-sm btn-outline-primary">Find Study Groups</a>
                            <?php endif; ?>
                            
                            <div class="row">
                                <!-- Recent Resources -->
                                <div class="col-md-6">
                                    <h6>Your Recent Resources</h6>
                                    <?php if ($recent_resources->num_rows > 0): ?>
                                        <div class="list-group">
                                            <?php while ($resource = $recent_resources->fetch_assoc()): ?>
                                                <a href="?route=resources&action=view&id=<?php echo $resource['id']; ?>" class="list-group-item list-group-item-action">
                                                    <div class="d-flex w-100 justify-content-between">
                                                        <p class="mb-1"><?php echo htmlspecialchars($resource['title']); ?></p>
                                                        <small class="text-muted">
                                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($resource['file_type']); ?></span>
                                                        </small>
                                                    </div>
                                                    <small class="text-muted"><?php echo date('M j, Y', strtotime($resource['created_at'])); ?></small>
                                                </a>
                                            <?php endwhile; ?>
                                        </div>
                                        <a href="?route=resources&filter=my" class="btn btn-sm btn-link mt-2">View All Resources</a>
                                    <?php else: ?>
                                        <p class="text-muted">You haven't uploaded any resources yet.</p>
                                        <a href="?route=resources&action=upload" class="btn btn-sm btn-outline-primary">Upload Resource</a>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Recent Discussions -->
                                <div class="col-md-6">
                                    <h6>Your Recent Discussions</h6>
                                    <?php if ($recent_discussions->num_rows > 0): ?>
                                        <div class="list-group">
                                            <?php while ($discussion = $recent_discussions->fetch_assoc()): ?>
                                                <a href="?route=discussions&action=view&id=<?php echo $discussion['id']; ?>" class="list-group-item list-group-item-action">
                                                    <div class="d-flex w-100 justify-content-between">
                                                        <p class="mb-1"><?php echo htmlspecialchars($discussion['title']); ?></p>
                                                    </div>
                                                    <small class="text-muted"><?php echo date('M j, Y', strtotime($discussion['created_at'])); ?></small>
                                                </a>
                                            <?php endwhile; ?>
                                        </div>
                                        <a href="?route=discussions&filter=my" class="btn btn-sm btn-link mt-2">View All Discussions</a>
                                    <?php else: ?>
                                        <p class="text-muted">You haven't started any discussions yet.</p>
                                        <a href="?route=discussions&action=create" class="btn btn-sm btn-outline-primary">Start Discussion</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Edit Profile Tab -->
                <div class="tab-pane fade" id="edit-profile" role="tabpanel" aria-labelledby="edit-profile-tab">
                    <div class="card mt-3">
                        <div class="card-body">
                            <h5 class="card-title">Edit Profile</h5>
                            <form method="post" action="?route=profile">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                                    <small class="text-muted">Username cannot be changed</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="academic_year" class="form-label">Academic Year</label>
                                        <select class="form-select" id="academic_year" name="academic_year">
                                            <option value="">Select Academic Year</option>
                                            <?php foreach ($academic_years as $year): ?>
                                                <option value="<?php echo $year; ?>" <?php echo ($user['academic_year'] === $year) ? 'selected' : ''; ?>>
                                                    <?php echo $year; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="major" class="form-label">Major/Subject</label>
                                        <input type="text" class="form-control" id="major" name="major" value="<?php echo htmlspecialchars($user['major'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="bio" class="form-label">Bio</label>
                                    <textarea class="form-control" id="bio" name="bio" rows="3"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                                </div>
                                
                                <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Change Password Tab -->
                <div class="tab-pane fade" id="password" role="tabpanel" aria-labelledby="password-tab">
                    <div class="card mt-3">
                        <div class="card-body">
                            <h5 class="card-title">Change Password</h5>
                            <form method="post" action="?route=profile">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    <small class="text-muted">Password must be at least 8 characters long</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                
                                <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-circle {
    width: 80px;
    height: 80px;
    background-color: #007bff;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    font-weight: bold;
    margin: 0 auto;
}
</style>
