<?php
require_once 'includes/connection.php';
require_once 'includes/functions.php';

// Require authentication and export permission
requirePermission('export_data');

// Check if export type is specified
if (!isset($_GET['type']) || !in_array($_GET['type'], ['admissions', 'visitors'])) {
    die('Invalid export type');
}

$type = $_GET['type'];
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $type . '_' . date('Y-m-d') . '.csv"');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

// Open output stream
$output = fopen('php://output', 'w');

if ($type === 'admissions') {
    // CSV headers for admissions
    fputcsv($output, [
        'ID',
        'Child Name',
        'Parent Name', 
        'Parent Email',
        'Phone Number',
        'Desired Class',
        'Address',
        'Specific Requirements',
        'Parent Notes',
        'Inquiry Date',
        'Status'
    ]);

    // Build query with search
    $where = '1=1';
    $params = [];
    
    if ($search) {
        $where = 'child_name LIKE ? OR parent_name LIKE ? OR parent_email LIKE ? OR phone_number LIKE ?';
        $searchTerm = "%$search%";
        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
    }

    // Get admission data
    $admissions = $db->fetchAll(
        "SELECT * FROM admission_inquiries WHERE $where ORDER BY inquiry_date DESC",
        $params
    );

    // Output data rows
    foreach ($admissions as $admission) {
        fputcsv($output, [
            $admission['id'],
            $admission['child_name'],
            $admission['parent_name'],
            $admission['parent_email'],
            $admission['phone_number'],
            $admission['desired_class'],
            $admission['address'],
            $admission['specific_requirements'],
            $admission['parent_notes'],
            $admission['inquiry_date'],
            $admission['status']
        ]);
    }

} else if ($type === 'visitors') {
    // CSV headers for visitors
    fputcsv($output, [
        'ID',
        'Visitor Name',
        'Email',
        'Phone Number',
        'Purpose',
        'Visit Date',
        'Created At',
        'Status'
    ]);

    // Build query with search
    $where = '1=1';
    $params = [];
    
    if ($search) {
        $where = 'visitor_name LIKE ? OR email LIKE ? OR phone_number LIKE ? OR purpose LIKE ?';
        $searchTerm = "%$search%";
        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
    }

    // Get visitor data
    $visitors = $db->fetchAll(
        "SELECT * FROM visitors WHERE $where ORDER BY visit_date DESC",
        $params
    );

    // Output data rows
    foreach ($visitors as $visitor) {
        fputcsv($output, [
            $visitor['id'],
            $visitor['visitor_name'],
            $visitor['email'],
            $visitor['phone_number'],
            $visitor['purpose'],
            $visitor['visit_date'],
            $visitor['created_at'],
            $visitor['status']
        ]);
    }
}

// Log the export activity
logActivity('Data Export', "Type: $type, Search: $search");

fclose($output);
exit;
?>