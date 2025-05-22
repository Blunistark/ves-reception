<?php
require_once 'includes/connection.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

// Require authentication for all AJAX requests
requireAuth();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_admission_details':
            requirePermission('manage_admissions');
            
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('Invalid ID');
            }
            
            $admission = $db->fetchOne('SELECT * FROM admission_inquiries WHERE id = ?', [$id]);
            if (!$admission) {
                throw new Exception('Admission inquiry not found');
            }
            
            echo json_encode([
                'success' => true,
                'data' => $admission
            ]);
            break;
            
        case 'get_visitor_details':
            requirePermission('manage_visitors');
            
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('Invalid ID');
            }
            
            $visitor = $db->fetchOne('SELECT * FROM visitors WHERE id = ?', [$id]);
            if (!$visitor) {
                throw new Exception('Visitor not found');
            }
            
            echo json_encode([
                'success' => true,
                'data' => $visitor
            ]);
            break;
            
        case 'update_admission_status':
            requirePermission('manage_admissions');
            
            $id = intval($_POST['id'] ?? 0);
            $status = $_POST['status'] ?? '';
            
            if ($id <= 0) {
                throw new Exception('Invalid ID');
            }
            
            if (!in_array($status, ['pending', 'reviewed', 'approved', 'rejected'])) {
                throw new Exception('Invalid status');
            }
            
            $db->update('admission_inquiries', ['status' => $status], 'id = ?', [$id]);
            
            logActivity('Update Admission Status', "ID: $id, Status: $status");
            
            echo json_encode([
                'success' => true,
                'message' => 'Status updated successfully'
            ]);
            break;
            
        case 'update_visitor_status':
            requirePermission('manage_visitors');
            
            $id = intval($_POST['id'] ?? 0);
            $status = $_POST['status'] ?? '';
            
            if ($id <= 0) {
                throw new Exception('Invalid ID');
            }
            
            if (!in_array($status, ['scheduled', 'completed', 'cancelled'])) {
                throw new Exception('Invalid status');
            }
            
            $db->update('visitors', ['status' => $status], 'id = ?', [$id]);
            
            logActivity('Update Visitor Status', "ID: $id, Status: $status");
            
            echo json_encode([
                'success' => true,
                'message' => 'Status updated successfully'
            ]);
            break;
            
        case 'delete_admission':
            requirePermission('manage_admissions');
            
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('Invalid ID');
            }
            
            // Check if admission exists
            $admission = $db->fetchOne('SELECT child_name FROM admission_inquiries WHERE id = ?', [$id]);
            if (!$admission) {
                throw new Exception('Admission inquiry not found');
            }
            
            $db->delete('admission_inquiries', 'id = ?', [$id]);
            
            logActivity('Delete Admission', "ID: $id, Child: " . $admission['child_name']);
            
            echo json_encode([
                'success' => true,
                'message' => 'Admission inquiry deleted successfully'
            ]);
            break;
            
        case 'delete_visitor':
            requirePermission('manage_visitors');
            
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('Invalid ID');
            }
            
            // Check if visitor exists
            $visitor = $db->fetchOne('SELECT visitor_name FROM visitors WHERE id = ?', [$id]);
            if (!$visitor) {
                throw new Exception('Visitor not found');
            }
            
            $db->delete('visitors', 'id = ?', [$id]);
            
            logActivity('Delete Visitor', "ID: $id, Name: " . $visitor['visitor_name']);
            
            echo json_encode([
                'success' => true,
                'message' => 'Visitor deleted successfully'
            ]);
            break;
            
        case 'get_dashboard_stats':
            requirePermission('view_dashboard');
            
            $today = date('Y-m-d');
            $thisWeek = date('Y-m-d', strtotime('-7 days'));
            $thisMonth = date('Y-m-d', strtotime('-30 days'));
            
            $stats = [
                'today' => [
                    'admissions' => $db->count('admission_inquiries', 'DATE(inquiry_date) = ?', [$today]),
                    'visitors' => $db->count('visitors', 'visit_date = ?', [$today])
                ],
                'week' => [
                    'admissions' => $db->count('admission_inquiries', 'DATE(inquiry_date) >= ?', [$thisWeek]),
                    'visitors' => $db->count('visitors', 'visit_date >= ?', [$thisWeek])
                ],
                'month' => [
                    'admissions' => $db->count('admission_inquiries', 'DATE(inquiry_date) >= ?', [$thisMonth]),
                    'visitors' => $db->count('visitors', 'visit_date >= ?', [$thisMonth])
                ],
                'total' => [
                    'admissions' => $db->count('admission_inquiries'),
                    'visitors' => $db->count('visitors')
                ]
            ];
            
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
            break;
            
        case 'backup_database':
            requirePermission('backup_database');
            
            $backup = backupDatabase($db);
            $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
            
            // Save backup file
            if (!file_exists('backups')) {
                mkdir('backups', 0755, true);
            }
            
            file_put_contents('backups/' . $filename, $backup);
            
            logActivity('Database Backup', $filename);
            
            echo json_encode([
                'success' => true,
                'message' => 'Database backup created successfully',
                'filename' => $filename
            ]);
            break;
            
        case 'validate_form':
            $formType = $_POST['form_type'] ?? '';
            $formData = $_POST['data'] ?? [];
            
            $errors = [];
            
            if ($formType === 'admission') {
                requirePermission('manage_admissions');
                
                if (empty($formData['child_name'])) {
                    $errors['child_name'] = 'Child name is required';
                }
                if (empty($formData['parent_name'])) {
                    $errors['parent_name'] = 'Parent name is required';
                }
                if (empty($formData['phone_number'])) {
                    $errors['phone_number'] = 'Phone number is required';
                } elseif (!validatePhone($formData['phone_number'])) {
                    $errors['phone_number'] = 'Invalid phone number';
                }
                if (!empty($formData['parent_email']) && !validateEmail($formData['parent_email'])) {
                    $errors['parent_email'] = 'Invalid email address';
                }
                if (empty($formData['desired_class'])) {
                    $errors['desired_class'] = 'Desired class is required';
                }
            } elseif ($formType === 'visitor') {
                requirePermission('manage_visitors');
                
                if (empty($formData['visitor_name'])) {
                    $errors['visitor_name'] = 'Visitor name is required';
                }
                if (empty($formData['phone_number'])) {
                    $errors['phone_number'] = 'Phone number is required';
                } elseif (!validatePhone($formData['phone_number'])) {
                    $errors['phone_number'] = 'Invalid phone number';
                }
                if (!empty($formData['email']) && !validateEmail($formData['email'])) {
                    $errors['email'] = 'Invalid email address';
                }
                if (empty($formData['purpose'])) {
                    $errors['purpose'] = 'Purpose is required';
                }
                if (empty($formData['visit_date'])) {
                    $errors['visit_date'] = 'Visit date is required';
                }
            }
            
            echo json_encode([
                'success' => empty($errors),
                'errors' => $errors
            ]);
            break;

        case 'search_records':
            $type = $_POST['type'] ?? '';
            $query = $_POST['query'] ?? '';
            $limit = intval($_POST['limit'] ?? 10);
            
            if (!in_array($type, ['admissions', 'visitors'])) {
                throw new Exception('Invalid search type');
            }
            
            if ($type === 'admissions') {
                requirePermission('manage_admissions');
                
                $where = 'child_name LIKE ? OR parent_name LIKE ? OR parent_email LIKE ? OR phone_number LIKE ?';
                $searchTerm = "%$query%";
                $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
                
                $results = $db->fetchAll(
                    "SELECT id, child_name, parent_name, parent_email, phone_number, desired_class, inquiry_date, status 
                     FROM admission_inquiries WHERE $where ORDER BY inquiry_date DESC LIMIT ?", 
                    array_merge($params, [$limit])
                );
            } else {
                requirePermission('manage_visitors');
                
                $where = 'visitor_name LIKE ? OR email LIKE ? OR phone_number LIKE ? OR purpose LIKE ?';
                $searchTerm = "%$query%";
                $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
                
                $results = $db->fetchAll(
                    "SELECT id, visitor_name, email, phone_number, purpose, visit_date, status 
                     FROM visitors WHERE $where ORDER BY visit_date DESC LIMIT ?", 
                    array_merge($params, [$limit])
                );
            }
            
            echo json_encode([
                'success' => true,
                'data' => $results
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>