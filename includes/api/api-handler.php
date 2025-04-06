<?php
/**
 * API Handler
 */

class ApiHandler {
    /**
     * Handle API requests
     */
    public function handleRequest($route) {
        // Get request method
        $method = $_SERVER['REQUEST_METHOD'];
        
        // Parse JSON body for POST, PUT requests
        $data = [];
        if ($method === 'POST' || $method === 'PUT') {
            $input = file_get_contents('php://input');
            if (!empty($input)) {
                $data = json_decode($input, true);
            } else {
                $data = $_POST;
            }
        }
        
        // Route API requests
        switch ($route) {
            // Authentication endpoints
            case 'auth/login':
                $this->handleLogin($data);
                break;
            case 'auth/register':
                $this->handleRegister($data);
                break;
            case 'auth/logout':
                $this->handleLogout();
                break;
                
            // Resource endpoints
            case 'resources':
                if ($method === 'GET') {
                    $this->getResources();
                } elseif ($method === 'POST') {
                    $this->createResource($data);
                } else {
                    $this->sendResponse(['error' => 'Method not allowed'], 405);
                }
                break;
            case (preg_match('/resources\/(\d+)/', $route, $matches) ? true : false):
                $id = $matches[1];
                if ($method === 'GET') {
                    $this->getResource($id);
                } elseif ($method === 'PUT') {
                    $this->updateResource($id, $data);
                } elseif ($method === 'DELETE') {
                    $this->deleteResource($id);
                } else {
                    $this->sendResponse(['error' => 'Method not allowed'], 405);
                }
                break;
                
            // Discussion endpoints
            case 'discussions':
                if ($method === 'GET') {
                    $this->getDiscussions();
                } elseif ($method === 'POST') {
                    $this->createDiscussion($data);
                } else {
                    $this->sendResponse(['error' => 'Method not allowed'], 405);
                }
                break;
            case (preg_match('/discussions\/(\d+)/', $route, $matches) ? true : false):
                $id = $matches[1];
                if ($method === 'GET') {
                    $this->getDiscussion($id);
                } elseif ($method === 'PUT') {
                    $this->updateDiscussion($id, $data);
                } elseif ($method === 'DELETE') {
                    $this->deleteDiscussion($id);
                } else {
                    $this->sendResponse(['error' => 'Method not allowed'], 405);
                }
                break;
                
            // Comments endpoints
            case (preg_match('/discussions\/(\d+)\/comments/', $route, $matches) ? true : false):
                $discussion_id = $matches[1];
                if ($method === 'GET') {
                    $this->getComments($discussion_id);
                } elseif ($method === 'POST') {
                    $this->createComment($discussion_id, $data);
                } else {
                    $this->sendResponse(['error' => 'Method not allowed'], 405);
                }
                break;
                
            // Study group endpoints
            case 'study-groups':
                if ($method === 'GET') {
                    $this->getStudyGroups();
                } elseif ($method === 'POST') {
                    $this->createStudyGroup($data);
                } else {
                    $this->sendResponse(['error' => 'Method not allowed'], 405);
                }
                break;
            case (preg_match('/study-groups\/(\d+)/', $route, $matches) ? true : false):
                $id = $matches[1];
                if ($method === 'GET') {
                    $this->getStudyGroup($id);
                } elseif ($method === 'PUT') {
                    $this->updateStudyGroup($id, $data);
                } elseif ($method === 'DELETE') {
                    $this->deleteStudyGroup($id);
                } else {
                    $this->sendResponse(['error' => 'Method not allowed'], 405);
                }
                break;
                
            default:
                $this->sendResponse(['error' => 'Endpoint not found'], 404);
                break;
        }
    }
    
    /**
     * Authentication handlers
     */
    private function handleLogin($data) {
        if (!isset($data['username']) || !isset($data['password'])) {
            $this->sendResponse(['error' => 'Username and password are required'], 400);
        }
        
        $result = login_user($data['username'], $data['password']);
        
        if ($result['status'] === 'success') {
            $this->sendResponse([
                'status' => 'success',
                'message' => $result['message'],
                'user' => [
                    'id' => $_SESSION['user_id'],
                    'username' => $_SESSION['username'],
                    'name' => $_SESSION['name']
                ]
            ]);
        } else {
            $this->sendResponse(['status' => 'error', 'message' => $result['message']], 401);
        }
    }
    
    private function handleRegister($data) {
        if (!isset($data['username']) || !isset($data['email']) || 
            !isset($data['password']) || !isset($data['name'])) {
            $this->sendResponse(['error' => 'All fields are required'], 400);
        }
        
        $result = register_user(
            $data['username'],
            $data['email'],
            $data['password'],
            $data['name']
        );
        
        if ($result['status'] === 'success') {
            $this->sendResponse($result);
        } else {
            $this->sendResponse($result, 400);
        }
    }
    
    private function handleLogout() {
        logout_user();
        $this->sendResponse(['status' => 'success', 'message' => 'Logged out successfully']);
    }
    
    /**
     * Resource handlers
     */
    private function getResources() {
        global $db;
        
        $category = isset($_GET['category']) ? $_GET['category'] : null;
        $search = isset($_GET['search']) ? $_GET['search'] : null;
        
        $query = "SELECT r.*, u.name as author_name FROM resources r 
                 JOIN users u ON r.user_id = u.id
                 WHERE 1=1";
        $params = [];
        
        if ($category) {
            $query .= " AND r.category = ?";
            $params[] = $category;
        }
        
        if ($search) {
            $query .= " AND (r.title LIKE ? OR r.description LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        $query .= " ORDER BY r.created_at DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $resources = $stmt->fetchAll();
        
        $this->sendResponse($resources);
    }
    
    private function getResource($id) {
        global $db;
        
        $stmt = $db->prepare("SELECT r.*, u.name as author_name FROM resources r 
                             JOIN users u ON r.user_id = u.id
                             WHERE r.id = ?");
        $stmt->execute([$id]);
        $resource = $stmt->fetch();
        
        if (!$resource) {
            $this->sendResponse(['error' => 'Resource not found'], 404);
        }
        
        $this->sendResponse($resource);
    }
    
    private function createResource($data) {
        if (!is_logged_in()) {
            $this->sendResponse(['error' => 'Authentication required'], 401);
        }
        
        if (!isset($data['title']) || !isset($data['category'])) {
            $this->sendResponse(['error' => 'Title and category are required'], 400);
        }
        
        global $db;
        
        try {
            $stmt = $db->prepare("INSERT INTO resources (title, description, user_id, category, subject, course_code, created_at) 
                                VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                $data['title'],
                $data['description'] ?? '',
                $_SESSION['user_id'],
                $data['category'],
                $data['subject'] ?? null,
                $data['course_code'] ?? null
            ]);
            
            $id = $db->lastInsertId();
            
            $this->sendResponse([
                'status' => 'success',
                'message' => 'Resource created successfully',
                'resource_id' => $id
            ]);
        } catch (Exception $e) {
            $this->sendResponse(['error' => 'Failed to create resource: ' . $e->getMessage()], 500);
        }
    }
    
    private function updateResource($id, $data) {
        if (!is_logged_in()) {
            $this->sendResponse(['error' => 'Authentication required'], 401);
        }
        
        global $db;
        
        // Check if resource exists and belongs to the user
        $stmt = $db->prepare("SELECT * FROM resources WHERE id = ?");
        $stmt->execute([$id]);
        $resource = $stmt->fetch();
        
        if (!$resource) {
            $this->sendResponse(['error' => 'Resource not found'], 404);
        }
        
        if ($resource['user_id'] != $_SESSION['user_id']) {
            $this->sendResponse(['error' => 'Permission denied'], 403);
        }
        
        try {
            $stmt = $db->prepare("UPDATE resources SET 
                               title = ?, 
                               description = ?,
                               category = ?,
                               subject = ?,
                               course_code = ?
                               WHERE id = ?");
            $stmt->execute([
                $data['title'] ?? $resource['title'],
                $data['description'] ?? $resource['description'],
                $data['category'] ?? $resource['category'],
                $data['subject'] ?? $resource['subject'],
                $data['course_code'] ?? $resource['course_code'],
                $id
            ]);
            
            $this->sendResponse([
                'status' => 'success',
                'message' => 'Resource updated successfully'
            ]);
        } catch (Exception $e) {
            $this->sendResponse(['error' => 'Failed to update resource: ' . $e->getMessage()], 500);
        }
    }
    
    private function deleteResource($id) {
        if (!is_logged_in()) {
            $this->sendResponse(['error' => 'Authentication required'], 401);
        }
        
        global $db;
        
        // Check if resource exists and belongs to the user
        $stmt = $db->prepare("SELECT * FROM resources WHERE id = ?");
        $stmt->execute([$id]);
        $resource = $stmt->fetch();
        
        if (!$resource) {
            $this->sendResponse(['error' => 'Resource not found'], 404);
        }
        
        if ($resource['user_id'] != $_SESSION['user_id']) {
            $this->sendResponse(['error' => 'Permission denied'], 403);
        }
        
        try {
            $stmt = $db->prepare("DELETE FROM resources WHERE id = ?");
            $stmt->execute([$id]);
            
            $this->sendResponse([
                'status' => 'success',
                'message' => 'Resource deleted successfully'
            ]);
        } catch (Exception $e) {
            $this->sendResponse(['error' => 'Failed to delete resource: ' . $e->getMessage()], 500);
        }
    }
    
    // Send a JSON response
    private function sendResponse($data, $status = 200) {
        http_response_code($status);
        echo json_encode($data);
        exit;
    }
}
