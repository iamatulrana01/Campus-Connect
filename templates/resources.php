<?php
// Check if viewing a specific resource
$resource_id = isset($_GET['id']) ? $_GET['id'] : null;

if ($resource_id) {
    // View single resource
    global $db;
    $stmt = $db->prepare("SELECT r.*, u.name as author_name, u.username as author_username FROM resources r JOIN users u ON r.user_id = u.id WHERE r.id = ?");
    $stmt->bind_param("i", $resource_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $resource = $result->fetch_assoc();
    
    // Update view count
    if ($resource) {
        $stmt = $db->prepare("UPDATE resources SET views = views + 1 WHERE id = ?");
        $stmt->bind_param("i", $resource_id);
        $stmt->execute();
        
        // Track view in analytics
        if (isset($_SESSION['user_id'])) {
            $stmt = $db->prepare("INSERT INTO resource_analytics (resource_id, user_id, action, ip_address, user_agent) VALUES (?, ?, 'view', ?, ?)");
            $ip = $_SERVER['REMOTE_ADDR'];
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
            $stmt->bind_param("iiss", $resource_id, $_SESSION['user_id'], $ip, $user_agent);
            $stmt->execute();
        }
    }
    
    // Get average rating
    $stmt = $db->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as rating_count FROM resource_ratings WHERE resource_id = ?");
    $stmt->bind_param("i", $resource_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $rating_data = $result->fetch_assoc();
    $avg_rating = round($rating_data['avg_rating'], 1);
    $rating_count = $rating_data['rating_count'];
    
    // Get user's rating if logged in
    $user_rating = 0;
    if (isset($_SESSION['user_id'])) {
        $stmt = $db->prepare("SELECT rating FROM resource_ratings WHERE resource_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $resource_id, $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $user_rating = $result->fetch_assoc()['rating'];
        }
    }
    
    // Handle rating submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_rating']) && isset($_SESSION['user_id'])) {
        $rating = (int)$_POST['rating'];
        $comment = isset($_POST['rating_comment']) ? sanitize($_POST['rating_comment']) : '';
        
        if ($rating < 1 || $rating > 5) {
            $error = "Rating must be between 1 and 5.";
        } else {
            // Check if user already rated this resource
            $stmt = $db->prepare("SELECT id FROM resource_ratings WHERE resource_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $resource_id, $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Update existing rating
                $stmt = $db->prepare("UPDATE resource_ratings SET rating = ?, comment = ? WHERE resource_id = ? AND user_id = ?");
                $stmt->bind_param("isii", $rating, $comment, $resource_id, $_SESSION['user_id']);
            } else {
                // Insert new rating
                $stmt = $db->prepare("INSERT INTO resource_ratings (resource_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiis", $resource_id, $_SESSION['user_id'], $rating, $comment);
            }
            
            if ($stmt->execute()) {
                $success = "Your rating has been submitted!";
                
                // Track rating in analytics
                $stmt = $db->prepare("INSERT INTO resource_analytics (resource_id, user_id, action) VALUES (?, ?, 'rate')");
                $stmt->bind_param("ii", $resource_id, $_SESSION['user_id']);
                $stmt->execute();
                
                // Refresh rating data
                $stmt = $db->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as rating_count FROM resource_ratings WHERE resource_id = ?");
                $stmt->bind_param("i", $resource_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $rating_data = $result->fetch_assoc();
                $avg_rating = round($rating_data['avg_rating'], 1);
                $rating_count = $rating_data['rating_count'];
                
                // Update user's rating
                $user_rating = $rating;
            } else {
                $error = "Failed to submit rating. Please try again.";
            }
        }
    }
    
    if (!$resource) {
        echo '<div class="alert alert-danger">Resource not found.</div>';
    } else {
        ?>
        <div class="container mt-4">
            <div class="mb-4">
                <a href="<?php echo APP_URL; ?>?route=resources" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-arrow-left"></i> Back to Resources
                </a>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h5 class="m-0 font-weight-bold text-primary"><?php echo htmlspecialchars($resource['title']); ?></h5>
                    <span class="badge bg-primary"><?php echo htmlspecialchars($resource['category']); ?></span>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted">
                            <i class="fas fa-user me-1"></i> Posted by <?php echo htmlspecialchars($resource['author_name']); ?> · 
                            <i class="fas fa-calendar-alt me-1"></i> <?php echo date('M d, Y', strtotime($resource['created_at'])); ?>
                            <?php if (!empty($resource['course_code'])): ?>
                                · <i class="fas fa-book me-1"></i> Course: <?php echo htmlspecialchars($resource['course_code']); ?>
                            <?php endif; ?>
                        </small>
                    </div>
                    
                    <!-- Rating display -->
                    <div class="resource-rating mb-4">
                        <div class="d-flex align-items-center">
                            <div class="stars me-2">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star <?php echo ($i <= $avg_rating) ? 'text-warning' : 'text-muted'; ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <div>
                                <strong><?php echo $avg_rating; ?></strong> 
                                <span class="text-muted">(<?php echo $rating_count; ?> rating<?php echo $rating_count !== 1 ? 's' : ''; ?>)</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="resource-description mb-4">
                        <p><?php echo nl2br(htmlspecialchars($resource['description'])); ?></p>
                    </div>
                    
                    <?php if (!empty($resource['file_path'])): ?>
                    <div class="text-center">
                        <a href="<?php echo APP_URL; ?>/uploads/<?php echo $resource['file_path']; ?>" class="btn btn-primary" download>
                            <i class="fas fa-download me-2"></i> Download Resource
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- Rate this resource -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0">Rate this Resource</h6>
                        </div>
                        <div class="card-body">
                            <form method="post" action="?route=resources&id=<?php echo $resource_id; ?>">
                                <div class="mb-3">
                                    <label class="form-label">Your Rating</label>
                                    <div class="rating-input">
                                        <?php for ($i = 5; $i >= 1; $i--): ?>
                                            <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" <?php echo ($user_rating == $i) ? 'checked' : ''; ?> />
                                            <label for="star<?php echo $i; ?>"><i class="fas fa-star"></i></label>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="rating_comment" class="form-label">Comment (optional)</label>
                                    <textarea class="form-control" id="rating_comment" name="rating_comment" rows="2"></textarea>
                                </div>
                                <button type="submit" name="submit_rating" class="btn btn-primary">Submit Rating</button>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Show resource ratings -->
                    <div class="resource-reviews">
                        <h6 class="mb-3">Reviews</h6>
                        
                        <?php
                        $stmt = $db->prepare("SELECT r.*, u.username, u.name FROM resource_ratings r JOIN users u ON r.user_id = u.id WHERE r.resource_id = ? ORDER BY r.created_at DESC");
                        $stmt->bind_param("i", $resource_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($result->num_rows === 0): ?>
                            <p class="text-muted">No reviews yet. Be the first to rate this resource!</p>
                        <?php else: ?>
                            <div class="list-group">
                                <?php while ($review = $result->fetch_assoc()): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong><?php echo htmlspecialchars($review['name'] ? $review['name'] : $review['username']); ?></strong>
                                                <small class="text-muted ms-2"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></small>
                                            </div>
                                            <div class="stars">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star <?php echo ($i <= $review['rating']) ? 'text-warning' : 'text-muted'; ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <?php if (!empty($review['comment'])): ?>
                                            <p class="mb-0 mt-2"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-footer">
                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $resource['user_id']): ?>
                        <div class="btn-group">
                            <a href="<?php echo APP_URL; ?>?route=resources&action=edit&id=<?php echo $resource['id']; ?>" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="#" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteResourceModal">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php if (is_logged_in() && $_SESSION['user_id'] == $resource['user_id']): ?>
        <!-- Delete Resource Modal -->
        <div class="modal fade" id="deleteResourceModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Delete Resource</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete this resource? This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" id="confirmDelete" data-id="<?php echo $resource['id']; ?>">Delete</button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php
    }
} else {
    // List resources
    $category_filter = isset($_GET['category']) ? $_GET['category'] : '';
    $search_filter = isset($_GET['search']) ? $_GET['search'] : '';
    ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Resource Library</h1>
        <?php if (is_logged_in()): ?>
        <a href="<?php echo APP_URL; ?>?route=resources&action=new" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Upload Resource
        </a>
        <?php endif; ?>
    </div>
    
    <div class="card shadow mb-4" id="resource-filters">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold">Filter Resources</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="category-filter" class="form-label">Category</label>
                    <select id="category-filter" class="form-select">
                        <option value="">All Categories</option>
                        <option value="Notes" <?php echo $category_filter == 'Notes' ? 'selected' : ''; ?>>Notes</option>
                        <option value="Study Guides" <?php echo $category_filter == 'Study Guides' ? 'selected' : ''; ?>>Study Guides</option>
                        <option value="Assignments" <?php echo $category_filter == 'Assignments' ? 'selected' : ''; ?>>Assignments</option>
                        <option value="Practice Tests" <?php echo $category_filter == 'Practice Tests' ? 'selected' : ''; ?>>Practice Tests</option>
                        <option value="Reference Materials" <?php echo $category_filter == 'Reference Materials' ? 'selected' : ''; ?>>Reference Materials</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="search-filter" class="form-label">Search</label>
                    <input type="text" id="search-filter" class="form-control" placeholder="Search by title or description" value="<?php echo htmlspecialchars($search_filter); ?>">
                </div>
                <div class="col-md-2 mb-3 d-flex align-items-end">
                    <button id="apply-filters" class="btn btn-primary w-100">Apply Filters</button>
                </div>
            </div>
        </div>
    </div>
    
    <?php
    // Fetch resources with filters
    global $db;
    
    $query = "SELECT r.*, u.name as author_name FROM resources r JOIN users u ON r.user_id = u.id WHERE 1=1";
    $params = [];
    
    if (!empty($category_filter)) {
        $query .= " AND r.category = ?";
        $params[] = $category_filter;
    }
    
    if (!empty($search_filter)) {
        $query .= " AND (r.title LIKE ? OR r.description LIKE ?)";
        $params[] = "%$search_filter%";
        $params[] = "%$search_filter%";
    }
    
    $query .= " ORDER BY r.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $resources = $stmt->fetchAll();
    
    if (empty($resources)) {
        echo '<div class="alert alert-info">No resources found matching your criteria. Try adjusting your filters or upload a new resource.</div>';
    } else {
        ?>
        <div class="row">
            <?php foreach ($resources as $resource): ?>
            <div class="col-md-6 mb-4">
                <div class="card h-100 shadow-sm resource-item">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <h5 class="card-title">
                                <a href="<?php echo APP_URL; ?>?route=resources&id=<?php echo $resource['id']; ?>"><?php echo htmlspecialchars($resource['title']); ?></a>
                            </h5>
                            <span class="badge bg-primary"><?php echo htmlspecialchars($resource['category']); ?></span>
                        </div>
                        <p class="card-text">
                            <?php echo htmlspecialchars(substr($resource['description'], 0, 150)) . (strlen($resource['description']) > 150 ? '...' : ''); ?>
                        </p>
                    </div>
                    <div class="card-footer text-muted">
                        <small>
                            <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($resource['author_name']); ?> · 
                            <i class="fas fa-calendar-alt me-1"></i> <?php echo date('M d, Y', strtotime($resource['created_at'])); ?>
                            <?php if (!empty($resource['course_code'])): ?>
                                · <i class="fas fa-book me-1"></i> <?php echo htmlspecialchars($resource['course_code']); ?>
                            <?php endif; ?>
                        </small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
}
?>
