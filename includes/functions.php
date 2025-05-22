<?php
// Utility functions for the School Admin System

/**
 * Check if user is authenticated
 */
function isAuthenticated() {
    return isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
}

/**
 * Check if user has specific permission
 */
function hasPermission($permission) {
    if (!isAuthenticated()) {
        return false;
    }
    
    $userRole = $_SESSION['role'] ?? 'viewer';
    $permissions = ROLES[$userRole] ?? [];
    
    return in_array($permission, $permissions);
}

/**
 * Require authentication - redirect to login if not authenticated
 */
function requireAuth() {
    if (!isAuthenticated()) {
        header('Location: ../login.php');
        exit;
    }
}

/**
 * Require specific permission
 */
function requirePermission($permission) {
    requireAuth();
    
    if (!hasPermission($permission)) {
        http_response_code(403);
        die('Access denied. Insufficient permissions.');
    }
}

/**
 * Get current user info
 */
function getCurrentUser() {
    if (!isAuthenticated()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'full_name' => $_SESSION['full_name'] ?? null,
        'role' => $_SESSION['role'] ?? null
    ];
}

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email address
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (basic validation)
 */
function validatePhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    return strlen($phone) >= 10;
}

/**
 * Format phone number for display
 */
function formatPhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($phone) == 10) {
        return substr($phone, 0, 3) . '-' . substr($phone, 3, 3) . '-' . substr($phone, 6);
    }
    return $phone;
}

/**
 * Format date for display
 */
function formatDate($date, $format = 'M j, Y') {
    return date($format, strtotime($date));
}

/**
 * Generate random password
 */
function generateRandomPassword($length = 12) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*';
    $password = '';
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $password;
}

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Check if string contains search term (case insensitive)
 */
function containsSearchTerm($haystack, $needle) {
    return stripos($haystack, $needle) !== false;
}

/**
 * Highlight search terms in text
 */
function highlightSearchTerm($text, $searchTerm) {
    if (empty($searchTerm)) {
        return $text;
    }
    return preg_replace('/(' . preg_quote($searchTerm, '/') . ')/i', '<mark>$1</mark>', $text);
}

/**
 * Get status badge HTML
 */
function getStatusBadge($status) {
    $badges = [
        'pending' => '<span class="status-badge status-pending">Pending</span>',
        'reviewed' => '<span class="status-badge status-reviewed">Reviewed</span>',
        'approved' => '<span class="status-badge status-approved">Approved</span>',
        'rejected' => '<span class="status-badge status-rejected">Rejected</span>',
        'scheduled' => '<span class="status-badge status-scheduled">Scheduled</span>',
        'completed' => '<span class="status-badge status-completed">Completed</span>',
        'cancelled' => '<span class="status-badge status-cancelled">Cancelled</span>'
    ];
    
    return isset($badges[$status]) ? $badges[$status] : '<span class="status-badge">' . ucfirst($status) . '</span>';
}

/**
 * Log activity
 */
function logActivity($action, $details = '', $userId = null) {
    if ($userId === null) {
        $user = getCurrentUser();
        $userId = $user['id'] ?? null;
    }
    
    $logFile = LOGS_PATH . 'activity.log';
    
    $timestamp = date('Y-m-d H:i:s');
    $ipAddress = getClientIP();
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $logEntry = "[{$timestamp}] User ID: {$userId} | IP: {$ipAddress} | Action: {$action} | Details: {$details} | User Agent: {$userAgent}" . PHP_EOL;
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * Get client IP address
 */
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

/**
 * Rate limiting (simple implementation)
 */
function checkRateLimit($identifier, $maxAttempts = 10, $timeWindow = 3600) {
    $rateLimitFile = LOGS_PATH . 'rate_limit.json';
    
    $rateLimits = [];
    if (file_exists($rateLimitFile)) {
        $rateLimits = json_decode(file_get_contents($rateLimitFile), true) ?: [];
    }
    
    $now = time();
    $key = md5($identifier);
    
    // Clean old entries
    foreach ($rateLimits as $k => $data) {
        if ($now - $data['first_attempt'] > $timeWindow) {
            unset($rateLimits[$k]);
        }
    }
    
    // Check current identifier
    if (!isset($rateLimits[$key])) {
        $rateLimits[$key] = [
            'attempts' => 1,
            'first_attempt' => $now
        ];
    } else {
        $rateLimits[$key]['attempts']++;
    }
    
    // Save rate limits
    file_put_contents($rateLimitFile, json_encode($rateLimits));
    
    return $rateLimits[$key]['attempts'] <= $maxAttempts;
}

/**
 * Backup database tables to SQL file
 */
function backupDatabase($db) {
    $tables = ['users', 'admission_inquiries', 'visitors'];
    $backup = "-- School Admin System Database Backup\n";
    $backup .= "-- Created: " . date('Y-m-d H:i:s') . "\n\n";
    
    foreach ($tables as $table) {
        $backup .= "-- Table: {$table}\n";
        $backup .= "DROP TABLE IF EXISTS `{$table}`;\n";
        
        // Get table structure
        $createTable = $db->fetchOne("SHOW CREATE TABLE `{$table}`");
        $backup .= $createTable['Create Table'] . ";\n\n";
        
        // Get table data
        $rows = $db->fetchAll("SELECT * FROM `{$table}`");
        
        if (!empty($rows)) {
            $backup .= "INSERT INTO `{$table}` VALUES\n";
            $values = [];
            
            foreach ($rows as $row) {
                $escapedValues = array_map(function($value) use ($db) {
                    return $value === null ? 'NULL' : $db->getConnection()->quote($value);
                }, array_values($row));
                $values[] = '(' . implode(',', $escapedValues) . ')';
            }
            
            $backup .= implode(",\n", $values) . ";\n\n";
        }
    }
    
    return $backup;
}

/**
 * Send notification email (basic function)
 */
function sendNotificationEmail($to, $subject, $message, $from = 'admin@school.com') {
    $headers = [
        'From: ' . $from,
        'Reply-To: ' . $from,
        'Content-Type: text/html; charset=UTF-8',
        'X-Mailer: PHP/' . phpversion()
    ];
    
    return mail($to, $subject, $message, implode("\r\n", $headers));
}

/**
 * Validate file upload
 */
function validateFileUpload($file, $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf'], $maxSize = 2097152) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error.'];
    }
    
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($fileExtension, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type. Allowed: ' . implode(', ', $allowedTypes)];
    }
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File too large. Maximum size: ' . formatBytes($maxSize)];
    }
    
    return ['success' => true, 'message' => 'File is valid.'];
}

/**
 * Format bytes to human readable format
 */
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Logout user
 */
function logout() {
    // Log the logout activity
    logActivity('User Logout');
    
    // Clear session data
    $_SESSION = array();
    
    // Destroy session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Clear remember me cookie
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 42000, '/', '', false, true);
    }
    
    // Destroy session
    session_destroy();
    
    // Redirect to login
    header('Location: ../login.php');
    exit;
}

/**
 * Truncate text with ellipsis
 */
function truncateText($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    return substr($text, 0, $length) . $suffix;
}

/**
 * Generate pagination HTML
 */
function generatePagination($currentPage, $totalPages, $baseUrl, $params = []) {
    if ($totalPages <= 1) {
        return '';
    }
    
    $html = '<div class="pagination">';
    
    // Build query string
    $queryParams = $params;
    
    // Previous button
    if ($currentPage > 1) {
        $queryParams['page'] = $currentPage - 1;
        $html .= '<a href="' . $baseUrl . '?' . http_build_query($queryParams) . '" class="pagination-btn">Previous</a>';
    }
    
    // Page numbers
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        $queryParams['page'] = $i;
        $class = ($i == $currentPage) ? 'pagination-btn active' : 'pagination-btn';
        $html .= '<a href="' . $baseUrl . '?' . http_build_query($queryParams) . '" class="' . $class . '">' . $i . '</a>';
    }
    
    // Next button
    if ($currentPage < $totalPages) {
        $queryParams['page'] = $currentPage + 1;
        $html .= '<a href="' . $baseUrl . '?' . http_build_query($queryParams) . '" class="pagination-btn">Next</a>';
    }
    
    $html .= '</div>';
    
    return $html;
}
?>