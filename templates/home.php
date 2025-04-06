<div class="row mb-5">
    <div class="col-md-8 offset-md-2 text-center">
        <h1 class="display-4 mb-4">Welcome to Collibration</h1>
        <p class="lead">A platform for students to share academic resources, collaborate in study groups, and engage in course discussions.</p>
        <?php if (!is_logged_in()): ?>
            <div class="mt-4">
                <a href="<?php echo APP_URL; ?>?route=register" class="btn btn-primary btn-lg me-2">Sign Up</a>
                <a href="<?php echo APP_URL; ?>?route=login" class="btn btn-outline-primary btn-lg">Log In</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="row mb-5">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-book-open fa-3x text-primary mb-3"></i>
                <h3 class="card-title">Resource Library</h3>
                <p class="card-text">Share and discover study materials, lecture notes, practice exams, and more to help you succeed in your courses.</p>
                <a href="<?php echo APP_URL; ?>?route=resources" class="btn btn-primary">Browse Resources</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-users fa-3x text-primary mb-3"></i>
                <h3 class="card-title">Study Groups</h3>
                <p class="card-text">Join or create study groups to collaborate with classmates, schedule study sessions, and prepare for exams together.</p>
                <a href="<?php echo APP_URL; ?>?route=study-groups" class="btn btn-primary">Find Study Groups</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-comments fa-3x text-primary mb-3"></i>
                <h3 class="card-title">Course Discussions</h3>
                <p class="card-text">Ask questions, share insights, and engage in discussions about course content with fellow students.</p>
                <a href="<?php echo APP_URL; ?>?route=discussions" class="btn btn-primary">Join Discussions</a>
            </div>
        </div>
    </div>
</div>

<?php if (is_logged_in()): ?>
<hr class="my-5">

<div class="row">
    <div class="col-md-6">
        <h3>Recent Resources</h3>
        <div class="list-group mb-4">
            <?php
            global $db;
            $stmt = $db->prepare("SELECT r.*, u.name as author_name 
                                FROM resources r 
                                JOIN users u ON r.user_id = u.id 
                                ORDER BY r.created_at DESC 
                                LIMIT 5");
            $stmt->execute();
            $resources = $stmt->fetchAll();
            
            if ($resources): 
                foreach ($resources as $resource): ?>
                    <a href="<?php echo APP_URL; ?>?route=resources&id=<?php echo $resource['id']; ?>" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1"><?php echo htmlspecialchars($resource['title']); ?></h5>
                            <small><?php echo date('M d, Y', strtotime($resource['created_at'])); ?></small>
                        </div>
                        <p class="mb-1"><?php echo htmlspecialchars(substr($resource['description'], 0, 100)) . '...'; ?></p>
                        <small>By <?php echo htmlspecialchars($resource['author_name']); ?> • Category: <?php echo htmlspecialchars($resource['category']); ?></small>
                    </a>
                <?php endforeach;
            else: ?>
                <p class="text-muted">No resources available yet.</p>
            <?php endif; ?>
        </div>
        <a href="<?php echo APP_URL; ?>?route=resources" class="btn btn-outline-primary">View All Resources</a>
    </div>
    
    <div class="col-md-6">
        <h3>Active Discussions</h3>
        <div class="list-group mb-4">
            <?php
            $stmt = $db->prepare("SELECT d.*, u.name as author_name, COUNT(c.id) as comment_count 
                                FROM discussions d 
                                JOIN users u ON d.user_id = u.id 
                                LEFT JOIN comments c ON d.id = c.discussion_id 
                                GROUP BY d.id
                                ORDER BY d.created_at DESC 
                                LIMIT 5");
            $stmt->execute();
            $discussions = $stmt->fetchAll();
            
            if ($discussions): 
                foreach ($discussions as $discussion): ?>
                    <a href="<?php echo APP_URL; ?>?route=discussions&id=<?php echo $discussion['id']; ?>" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1"><?php echo htmlspecialchars($discussion['title']); ?></h5>
                            <small><?php echo date('M d, Y', strtotime($discussion['created_at'])); ?></small>
                        </div>
                        <p class="mb-1"><?php echo htmlspecialchars(substr($discussion['content'], 0, 100)) . '...'; ?></p>
                        <small>By <?php echo htmlspecialchars($discussion['author_name']); ?> • <?php echo $discussion['comment_count']; ?> comments</small>
                    </a>
                <?php endforeach;
            else: ?>
                <p class="text-muted">No discussions available yet.</p>
            <?php endif; ?>
        </div>
        <a href="<?php echo APP_URL; ?>?route=discussions" class="btn btn-outline-primary">View All Discussions</a>
    </div>
</div>
<?php endif; ?>
