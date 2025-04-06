<?php
// Check if viewing a specific discussion
$discussion_id = isset($_GET['id']) ? $_GET['id'] : null;

if ($discussion_id) {
    // View single discussion
    global $db;
    $stmt = $db->prepare("SELECT d.*, u.name as author_name FROM discussions d JOIN users u ON d.user_id = u.id WHERE d.id = ?");
    $stmt->execute([$discussion_id]);
    $discussion = $stmt->fetch();
    
    if (!$discussion) {
        echo '<div class="alert alert-danger">Discussion not found.</div>';
    } else {
        // Fetch comments for this discussion
        $stmt = $db->prepare("
            SELECT c.*, u.name as author_name 
            FROM comments c 
            JOIN users u ON c.user_id = u.id 
            WHERE c.discussion_id = ? 
            ORDER BY c.created_at ASC
        ");
        $stmt->execute([$discussion_id]);
        $comments = $stmt->fetchAll();
        ?>
        <div class="mb-4">
            <a href="<?php echo APP_URL; ?>?route=discussions" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-arrow-left"></i> Back to Discussions
            </a>
        </div>
        
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h5 class="m-0 font-weight-bold text-primary"><?php echo htmlspecialchars($discussion['title']); ?></h5>
                <span class="badge bg-primary"><?php echo htmlspecialchars($discussion['category']); ?></span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <small class="text-muted">
                        <i class="fas fa-user me-1"></i> Posted by <?php echo htmlspecialchars($discussion['author_name']); ?> · 
                        <i class="fas fa-calendar-alt me-1"></i> <?php echo date('M d, Y', strtotime($discussion['created_at'])); ?>
                    </small>
                </div>
                
                <div class="discussion-content mb-4">
                    <p><?php echo nl2br(htmlspecialchars($discussion['content'])); ?></p>
                </div>
            </div>
            <div class="card-footer">
                <?php if (is_logged_in() && $_SESSION['user_id'] == $discussion['user_id']): ?>
                <div class="btn-group">
                    <a href="<?php echo APP_URL; ?>?route=discussions&action=edit&id=<?php echo $discussion['id']; ?>" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <a href="#" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteDiscussionModal">
                        <i class="fas fa-trash"></i> Delete
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Comments Section -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold">Comments (<?php echo count($comments); ?>)</h6>
            </div>
            <div class="card-body">
                <?php if (empty($comments)): ?>
                    <p class="text-muted">No comments yet. Be the first to comment!</p>
                <?php else: ?>
                    <div class="comments-list">
                        <?php foreach ($comments as $comment): ?>
                            <div class="comment-item mb-3 pb-3 border-bottom">
                                <div class="d-flex justify-content-between">
                                    <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($comment['author_name']); ?></h6>
                                    <small class="text-muted"><?php echo date('M d, Y h:i A', strtotime($comment['created_at'])); ?></small>
                                </div>
                                <div class="comment-content">
                                    <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                                </div>
                                <?php if (is_logged_in() && $_SESSION['user_id'] == $comment['user_id']): ?>
                                    <div class="mt-2">
                                        <a href="#" class="text-danger delete-comment" data-id="<?php echo $comment['id']; ?>">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (is_logged_in()): ?>
                    <div class="add-comment-form mt-4">
                        <h6 class="mb-3">Add a Comment</h6>
                        <form id="commentForm" action="<?php echo APP_URL; ?>/includes/api/comments.php" method="post">
                            <input type="hidden" name="discussion_id" value="<?php echo $discussion_id; ?>">
                            <input type="hidden" name="action" value="add_comment">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <div class="mb-3">
                                <textarea class="form-control" name="content" rows="3" placeholder="Write your comment here..." required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Post Comment</button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mt-4">
                        <a href="<?php echo APP_URL; ?>?route=login">Login</a> or <a href="<?php echo APP_URL; ?>?route=register">Register</a> to join the conversation.
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (is_logged_in() && $_SESSION['user_id'] == $discussion['user_id']): ?>
        <!-- Delete Discussion Modal -->
        <div class="modal fade" id="deleteDiscussionModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Delete Discussion</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete this discussion? All comments will also be deleted. This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" id="confirmDelete" data-id="<?php echo $discussion['id']; ?>">Delete</button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php
    }
} else {
    // List discussions
    $category_filter = isset($_GET['category']) ? $_GET['category'] : '';
    $search_filter = isset($_GET['search']) ? $_GET['search'] : '';
    ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Discussion Forum</h1>
        <?php if (is_logged_in()): ?>
        <a href="<?php echo APP_URL; ?>?route=discussions&action=new" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Start New Discussion
        </a>
        <?php endif; ?>
    </div>
    
    <div class="card shadow mb-4" id="discussion-filters">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold">Filter Discussions</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="category-filter" class="form-label">Category</label>
                    <select id="category-filter" class="form-select">
                        <option value="">All Categories</option>
                        <option value="General" <?php echo $category_filter == 'General' ? 'selected' : ''; ?>>General</option>
                        <option value="Academic" <?php echo $category_filter == 'Academic' ? 'selected' : ''; ?>>Academic</option>
                        <option value="Homework Help" <?php echo $category_filter == 'Homework Help' ? 'selected' : ''; ?>>Homework Help</option>
                        <option value="Study Tips" <?php echo $category_filter == 'Study Tips' ? 'selected' : ''; ?>>Study Tips</option>
                        <option value="Campus Life" <?php echo $category_filter == 'Campus Life' ? 'selected' : ''; ?>>Campus Life</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="search-filter" class="form-label">Search</label>
                    <input type="text" id="search-filter" class="form-control" placeholder="Search by title or content" value="<?php echo htmlspecialchars($search_filter); ?>">
                </div>
                <div class="col-md-2 mb-3 d-flex align-items-end">
                    <button id="apply-filters" class="btn btn-primary w-100">Apply Filters</button>
                </div>
            </div>
        </div>
    </div>
    
    <?php
    // Fetch discussions with filters
    global $db;
    
    $query = "SELECT d.*, u.name as author_name, COUNT(c.id) as comment_count 
              FROM discussions d 
              LEFT JOIN comments c ON d.id = c.discussion_id
              JOIN users u ON d.user_id = u.id 
              WHERE 1=1";
    $params = [];
    
    if (!empty($category_filter)) {
        $query .= " AND d.category = ?";
        $params[] = $category_filter;
    }
    
    if (!empty($search_filter)) {
        $query .= " AND (d.title LIKE ? OR d.content LIKE ?)";
        $params[] = "%$search_filter%";
        $params[] = "%$search_filter%";
    }
    
    $query .= " GROUP BY d.id ORDER BY d.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $discussions = $stmt->fetchAll();
    
    if (empty($discussions)) {
        echo '<div class="alert alert-info">No discussions found matching your criteria. Start a new conversation!</div>';
    } else {
        ?>
        <div class="list-group discussion-list">
            <?php foreach ($discussions as $discussion): ?>
            <a href="<?php echo APP_URL; ?>?route=discussions&id=<?php echo $discussion['id']; ?>" class="list-group-item list-group-item-action">
                <div class="d-flex w-100 justify-content-between align-items-start">
                    <h5 class="mb-1"><?php echo htmlspecialchars($discussion['title']); ?></h5>
                    <span class="badge bg-primary"><?php echo htmlspecialchars($discussion['category']); ?></span>
                </div>
                <p class="mb-1">
                    <?php echo htmlspecialchars(substr($discussion['content'], 0, 150)) . (strlen($discussion['content']) > 150 ? '...' : ''); ?>
                </p>
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($discussion['author_name']); ?> · 
                        <i class="fas fa-calendar-alt me-1"></i> <?php echo date('M d, Y', strtotime($discussion['created_at'])); ?>
                    </small>
                    <span class="badge bg-secondary rounded-pill">
                        <i class="fas fa-comments me-1"></i> <?php echo $discussion['comment_count']; ?>
                    </span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php
    }
}
?>

<script>
$(document).ready(function() {
    // Apply filters
    $('#apply-filters').click(function() {
        var category = $('#category-filter').val();
        var search = $('#search-filter').val();
        var url = '<?php echo APP_URL; ?>?route=discussions';
        
        if (category) {
            url += '&category=' + encodeURIComponent(category);
        }
        
        if (search) {
            url += '&search=' + encodeURIComponent(search);
        }
        
        window.location.href = url;
    });
    
    // Handle comment submission
    $('#commentForm').submit(function(e) {
        e.preventDefault();
        
        $.ajax({
            type: 'POST',
            url: $(this).attr('action'),
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('An error occurred while processing your request.');
            }
        });
    });
    
    // Delete comment
    $('.delete-comment').click(function(e) {
        e.preventDefault();
        
        if (confirm('Are you sure you want to delete this comment?')) {
            var commentId = $(this).data('id');
            
            $.ajax({
                type: 'POST',
                url: '<?php echo APP_URL; ?>/includes/api/comments.php',
                data: {
                    action: 'delete_comment',
                    comment_id: commentId,
                    csrf_token: '<?php echo generate_csrf_token(); ?>'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('An error occurred while processing your request.');
                }
            });
        }
    });
    
    // Delete discussion
    $('#confirmDelete').click(function() {
        var discussionId = $(this).data('id');
        
        $.ajax({
            type: 'POST',
            url: '<?php echo APP_URL; ?>/includes/api/discussions.php',
            data: {
                action: 'delete_discussion',
                discussion_id: discussionId,
                csrf_token: '<?php echo generate_csrf_token(); ?>'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    window.location.href = '<?php echo APP_URL; ?>?route=discussions';
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('An error occurred while processing your request.');
            }
        });
    });
});
</script>
