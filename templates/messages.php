<?php
/**
 * Messaging System
 * Allows users to communicate with their study group members
 */

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    redirect('login');
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';
$conversation_id = isset($_GET['conversation']) ? (int)$_GET['conversation'] : 0;
$recipient_id = isset($_GET['recipient']) ? (int)$_GET['recipient'] : 0;

// Handle new message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $message_text = sanitize($_POST['message']);
    $recipient_id = (int)$_POST['recipient_id'];
    $conversation_id = (int)$_POST['conversation_id'];
    
    if (empty($message_text)) {
        $error = "Message cannot be empty.";
    } else {
        // Check if conversation exists or create new one
        if ($conversation_id === 0) {
            // Find if there's an existing conversation between these users
            $sql = "SELECT id FROM conversations 
                    WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)
                    LIMIT 1";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("iiii", $user_id, $recipient_id, $recipient_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $conversation_id = $result->fetch_assoc()['id'];
            } else {
                // Create new conversation
                $sql = "INSERT INTO conversations (user1_id, user2_id, created_at) VALUES (?, ?, NOW())";
                $stmt = $db->prepare($sql);
                $stmt->bind_param("ii", $user_id, $recipient_id);
                $stmt->execute();
                $conversation_id = $db->insert_id;
            }
        }
        
        // Send message
        $sql = "INSERT INTO messages (conversation_id, sender_id, recipient_id, message, created_at, is_read) 
                VALUES (?, ?, ?, ?, NOW(), 0)";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("iiis", $conversation_id, $user_id, $recipient_id, $message_text);
        
        if ($stmt->execute()) {
            $success = "Message sent successfully!";
            // Redirect to avoid form resubmission
            redirect("messages?conversation={$conversation_id}");
        } else {
            $error = "Failed to send message. Please try again.";
        }
    }
}

// Get user's conversations
$sql = "SELECT c.id, 
               IF(c.user1_id = ?, c.user2_id, c.user1_id) as other_user_id,
               u.username as other_username,
               u.name as other_name,
               MAX(m.created_at) as last_message_time,
               (SELECT message FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message,
               COUNT(CASE WHEN m.is_read = 0 AND m.recipient_id = ? THEN 1 END) as unread_count
        FROM conversations c
        JOIN users u ON u.id = IF(c.user1_id = ?, c.user2_id, c.user1_id)
        LEFT JOIN messages m ON m.conversation_id = c.id
        WHERE c.user1_id = ? OR c.user2_id = ?
        GROUP BY c.id
        ORDER BY last_message_time DESC";
$stmt = $db->prepare($sql);
$stmt->bind_param("iiiii", $user_id, $user_id, $user_id, $user_id, $user_id);
$stmt->execute();
$conversations = $stmt->get_result();

// Get messages for selected conversation
$messages = [];
$other_user = null;
if ($conversation_id > 0) {
    // Get other user in conversation
    $sql = "SELECT c.id, 
                   IF(c.user1_id = ?, c.user2_id, c.user1_id) as other_user_id,
                   u.username as other_username,
                   u.name as other_name
            FROM conversations c
            JOIN users u ON u.id = IF(c.user1_id = ?, c.user2_id, c.user1_id)
            WHERE c.id = ? AND (c.user1_id = ? OR c.user2_id = ?)
            LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("iiiii", $user_id, $user_id, $conversation_id, $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $other_user = $result->fetch_assoc();
        $recipient_id = $other_user['other_user_id'];
        
        // Get messages
        $sql = "SELECT m.*, u.username, u.name 
                FROM messages m
                JOIN users u ON m.sender_id = u.id
                WHERE m.conversation_id = ?
                ORDER BY m.created_at ASC";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $conversation_id);
        $stmt->execute();
        $messages = $stmt->get_result();
        
        // Mark messages as read
        $sql = "UPDATE messages SET is_read = 1 
                WHERE conversation_id = ? AND recipient_id = ? AND is_read = 0";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("ii", $conversation_id, $user_id);
        $stmt->execute();
    }
} elseif ($recipient_id > 0) {
    // New conversation with a specific recipient
    $sql = "SELECT id, username, name FROM users WHERE id = ? LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $recipient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $recipient = $result->fetch_assoc();
        $other_user = [
            'other_user_id' => $recipient['id'],
            'other_username' => $recipient['username'],
            'other_name' => $recipient['name']
        ];
    } else {
        redirect('messages');
    }
}

// Get group members for new message
$sql = "SELECT DISTINCT u.id, u.username, u.name 
        FROM users u
        JOIN group_members gm1 ON u.id = gm1.user_id
        JOIN group_members gm2 ON gm1.group_id = gm2.group_id
        WHERE gm2.user_id = ? AND u.id != ?
        ORDER BY u.name";
$stmt = $db->prepare($sql);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$group_members = $stmt->get_result();
?>

<div class="container mt-4">
    <h1>Messages</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Conversations list -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Conversations</h5>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newMessageModal">
                        <i class="bi bi-pencil-square"></i> New Message
                    </button>
                </div>
                <ul class="list-group list-group-flush conversation-list">
                    <?php if ($conversations->num_rows === 0): ?>
                        <li class="list-group-item text-center text-muted">No conversations yet</li>
                    <?php else: ?>
                        <?php while ($conversation = $conversations->fetch_assoc()): ?>
                            <a href="?route=messages&conversation=<?php echo $conversation['id']; ?>" class="list-group-item list-group-item-action <?php echo $conversation_id == $conversation['id'] ? 'active' : ''; ?>">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($conversation['other_name'] ?: $conversation['other_username']); ?></h6>
                                    <?php if ($conversation['unread_count'] > 0): ?>
                                        <span class="badge bg-primary rounded-pill"><?php echo $conversation['unread_count']; ?></span>
                                    <?php endif; ?>
                                </div>
                                <p class="mb-1 small text-truncate"><?php echo htmlspecialchars($conversation['last_message']); ?></p>
                                <small class="text-muted"><?php echo time_elapsed_string($conversation['last_message_time']); ?></small>
                            </a>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        
        <!-- Message area -->
        <div class="col-md-8">
            <?php if ($conversation_id > 0 || $recipient_id > 0): ?>
                <div class="card">
                    <div class="card-header">
                        <h5><?php echo htmlspecialchars($other_user['other_name'] ?: $other_user['other_username']); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="messages-container mb-3">
                            <?php if (isset($messages) && $messages->num_rows === 0): ?>
                                <div class="text-center text-muted">No messages yet. Start the conversation!</div>
                            <?php else: ?>
                                <?php while ($message = $messages->fetch_assoc()): ?>
                                    <div class="message <?php echo $message['sender_id'] == $user_id ? 'outgoing' : 'incoming'; ?>">
                                        <div class="message-content">
                                            <p><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                                            <small class="text-muted"><?php echo date('M j, g:i a', strtotime($message['created_at'])); ?></small>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </div>
                        
                        <form method="post" action="?route=messages<?php echo $conversation_id ? '&conversation=' . $conversation_id : ''; ?>">
                            <div class="input-group">
                                <input type="hidden" name="recipient_id" value="<?php echo $recipient_id; ?>">
                                <input type="hidden" name="conversation_id" value="<?php echo $conversation_id; ?>">
                                <textarea class="form-control" name="message" placeholder="Type your message..." rows="2" required></textarea>
                                <button class="btn btn-primary" type="submit" name="send_message">Send</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="card text-center py-5">
                    <div class="card-body">
                        <h5 class="card-title">Select a conversation or start a new one</h5>
                        <p class="card-text text-muted">
                            Choose a conversation from the left or click "New Message" to start a new one.
                        </p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newMessageModal">
                            <i class="bi bi-pencil-square"></i> New Message
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- New Message Modal -->
<div class="modal fade" id="newMessageModal" tabindex="-1" aria-labelledby="newMessageModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newMessageModalLabel">New Message</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="get" action="">
                    <input type="hidden" name="route" value="messages">
                    <div class="mb-3">
                        <label for="recipient" class="form-label">Recipient</label>
                        <select class="form-select" id="recipient" name="recipient" required>
                            <option value="">Select a recipient</option>
                            <?php while ($member = $group_members->fetch_assoc()): ?>
                                <option value="<?php echo $member['id']; ?>"><?php echo htmlspecialchars($member['name'] ?: $member['username']); ?></option>
                            <?php endwhile; ?>
                        </select>
                        <small class="text-muted">You can only message users who are in the same study groups as you.</small>
                    </div>
                    <button type="submit" class="btn btn-primary">Start Conversation</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.conversation-list .list-group-item {
    border-radius: 0;
}
.conversation-list .list-group-item.active {
    background-color: #f8f9fa;
    color: #212529;
    border-color: #dee2e6;
    border-left: 3px solid #007bff;
}
.messages-container {
    height: 400px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
}
.message {
    margin-bottom: 15px;
    display: flex;
}
.message.outgoing {
    justify-content: flex-end;
}
.message-content {
    max-width: 70%;
    padding: 10px 15px;
    border-radius: 15px;
    position: relative;
}
.message.incoming .message-content {
    background-color: #f1f0f0;
}
.message.outgoing .message-content {
    background-color: #007bff;
    color: white;
}
.message.outgoing .message-content small {
    color: rgba(255, 255, 255, 0.7);
}
</style>

<?php
// Helper function for time formatting
function time_elapsed_string($datetime) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->d > 0) {
        return $diff->d > 1 ? $diff->d . ' days ago' : 'Yesterday';
    } elseif ($diff->h > 0) {
        return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    } elseif ($diff->i > 0) {
        return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    } else {
        return 'Just now';
    }
}
?>
