<?php
require_once '../includes/config.php';
require_once '../includes/connection.php';
require_once '../includes/functions.php';

// Require authentication and view_dashboard permission
requirePermission('view_dashboard');

// Get today's statistics
$today = date('Y-m-d');
$todayAdmissions = $db->count('admission_inquiries', 'DATE(inquiry_date) = ?', [$today]);
$todayVisitors = $db->count('visitors', 'visit_date = ?', [$today]);

// Handle search for admissions
$admissionSearch = isset($_GET['admission_search']) ? trim($_GET['admission_search']) : '';
$admissionWhere = '1=1';
$admissionParams = [];

if ($admissionSearch) {
    $admissionWhere = 'child_name LIKE ? OR parent_name LIKE ? OR parent_email LIKE ? OR phone_number LIKE ?';
    $searchTerm = "%$admissionSearch%";
    $admissionParams = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
}

// Get admission inquiries
$admissions = $db->fetchAll(
    "SELECT * FROM admission_inquiries WHERE $admissionWhere ORDER BY inquiry_date DESC LIMIT 50",
    $admissionParams
);

// Handle search for visitors
$visitorSearch = isset($_GET['visitor_search']) ? trim($_GET['visitor_search']) : '';
$visitorWhere = '1=1';
$visitorParams = [];

if ($visitorSearch) {
    $visitorWhere = 'visitor_name LIKE ? OR email LIKE ? OR phone_number LIKE ? OR purpose LIKE ?';
    $searchTerm = "%$visitorSearch%";
    $visitorParams = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
}

// Get visitors
$visitors = $db->fetchAll(
    "SELECT * FROM visitors WHERE $visitorWhere ORDER BY visit_date DESC LIMIT 50",
    $visitorParams
);

$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - School Admin System</title>
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin="">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?display=swap&family=Noto+Sans:wght@400;500;700;900&family=Public+Sans:wght@400;500;700;900">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="data:image/x-icon;base64,">
</head>
<body>
    <div class="main-container">
        <header class="header">
            <div class="logo-section">
                <div class="logo-icon">
                    <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <g clip-path="url(#clip0_6_543)">
                            <path d="M42.1739 20.1739L27.8261 5.82609C29.1366 7.13663 28.3989 10.1876 26.2002 13.7654C24.8538 15.9564 22.9595 18.3449 20.6522 20.6522C18.3449 22.9595 15.9564 24.8538 13.7654 26.2002C10.1876 28.3989 7.13663 29.1366 5.82609 27.8261L20.1739 42.1739C21.4845 43.4845 24.5355 42.7467 28.1133 40.548C30.3042 39.2016 32.6927 37.3073 35 35C37.3073 32.6927 39.2016 30.3042 40.548 28.1133C42.7467 24.5355 43.4845 21.4845 42.1739 20.1739Z" fill="currentColor"></path>
                        </g>
                        <defs>
                            <clipPath id="clip0_6_543"><rect width="48" height="48" fill="white"></rect></clipPath>
                        </defs>
                    </svg>
                </div>
                <h2 class="logo-text">School Admin</h2>
            </div>
            <div class="nav-section">
                <nav class="nav-links">
                    <a class="nav-link active" href="dashboard.php">Dashboard</a>
                    <?php if (hasPermission('manage_admissions')): ?>
                    <a class="nav-link" href="admission_inquiry.php">Admissions</a>
                    <?php endif; ?>
                    <?php if (hasPermission('manage_visitors')): ?>
                    <a class="nav-link" href="log_visitor.php">Visitors</a>
                    <?php endif; ?>
                    <a class="nav-link" href="logout.php">Logout</a>
                </nav>
                <div class="user-avatar" style="background-image: url('https://ui-avatars.com/api/?name=<?php echo urlencode($currentUser['full_name']); ?>&background=f4c653&color=1c170d');"></div>
            </div>
        </header>
        
        <div class="content-wrapper">
            <div class="content-container">
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Visitors & Admissions Dashboard</h1>
                        <p class="page-subtitle">Welcome back, <?php echo htmlspecialchars($currentUser['full_name']); ?>! Manage all visitor and admission inquiries</p>
                    </div>
                </div>
                
                <div class="stats-container">
                    <div class="stat-card">
                        <p class="stat-label">Today's Admission Inquiries</p>
                        <p class="stat-value"><?php echo $todayAdmissions; ?></p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-label">Today's Visitors</p>
                        <p class="stat-value"><?php echo $todayVisitors; ?></p>
                    </div>
                </div>
                
                <?php if (hasPermission('manage_admissions')): ?>
                <h2 class="section-title">Admission Inquiries</h2>
                <div class="search-container">
                    <form method="GET" action="">
                        <div class="search-field">
                            <div class="search-wrapper">
                                <div class="search-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" fill="currentColor" viewBox="0 0 256 256">
                                        <path d="M229.66,218.34l-50.07-50.06a88.11,88.11,0,1,0-11.31,11.31l50.06,50.07a8,8,0,0,0,11.32-11.32ZM40,112a72,72,0,1,1,72,72A72.08,72.08,0,0,1,40,112Z"></path>
                                    </svg>
                                </div>
                                <input name="admission_search" placeholder="Search admission inquiries" class="search-input" value="<?php echo htmlspecialchars($admissionSearch); ?>">
                                <input type="hidden" name="visitor_search" value="<?php echo htmlspecialchars($visitorSearch); ?>">
                            </div>
                        </div>
                    </form>
                </div>
                
                <?php if (hasPermission('export_data')): ?>
                <div class="table-actions">
                    <div class="flex gap-2">
                        <button onclick="exportAdmissions()" class="export-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" fill="currentColor" viewBox="0 0 256 256">
                                <path d="M224,152v56a16,16,0,0,1-16,16H48a16,16,0,0,1-16-16V152a8,8,0,0,1,16,0v56H208V152a8,8,0,0,1,16,0Zm-101.66,5.66a8,8,0,0,0,11.32,0l40-40a8,8,0,0,0-11.32-11.32L136,132.69V40a8,8,0,0,0-16,0v92.69L93.66,106.34a8,8,0,0,0-11.32,11.32Z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="table-container">
                    <div class="table-wrapper">
                        <table class="data-table">
                            <thead class="table-header">
                                <tr>
                                    <th>Child Name</th>
                                    <th>Parent</th>
                                    <th>Phone</th>
                                    <th>Class</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody class="table-body">
                                <?php foreach ($admissions as $admission): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($admission['child_name']); ?></td>
                                    <td class="secondary-text">
                                        <?php echo htmlspecialchars($admission['parent_name']); ?>
                                        <?php if ($admission['parent_email']): ?>
                                        <br><small><?php echo htmlspecialchars($admission['parent_email']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="secondary-text"><?php echo htmlspecialchars($admission['phone_number']); ?></td>
                                    <td>
                                        <button class="table-badge">
                                            <?php echo htmlspecialchars($admission['desired_class']); ?>
                                        </button>
                                    </td>
                                    <td class="secondary-text"><?php echo date('M j, Y', strtotime($admission['inquiry_date'])); ?></td>
                                    <td>
                                        <a href="#" onclick="viewAdmission(<?php echo $admission['id']; ?>)" class="table-link">View</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($admissions)): ?>
                                <tr>
                                    <td colspan="6" class="empty-state">
                                        <?php echo $admissionSearch ? 'No admission inquiries found matching your search.' : 'No admission inquiries found.'; ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (hasPermission('manage_visitors')): ?>
                <h2 class="section-title">Visitors</h2>
                <div class="search-container">
                    <form method="GET" action="">
                        <div class="search-field">
                            <div class="search-wrapper">
                                <div class="search-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" fill="currentColor" viewBox="0 0 256 256">
                                        <path d="M229.66,218.34l-50.07-50.06a88.11,88.11,0,1,0-11.31,11.31l50.06,50.07a8,8,0,0,0,11.32-11.32ZM40,112a72,72,0,1,1,72,72A72.08,72.08,0,0,1,40,112Z"></path>
                                    </svg>
                                </div>
                                <input name="visitor_search" placeholder="Search visitor inquiries" class="search-input" value="<?php echo htmlspecialchars($visitorSearch); ?>">
                                <input type="hidden" name="admission_search" value="<?php echo htmlspecialchars($admissionSearch); ?>">
                            </div>
                        </div>
                    </form>
                </div>
                
                <?php if (hasPermission('export_data')): ?>
                <div class="table-actions">
                    <div class="flex gap-2">
                        <button onclick="exportVisitors()" class="export-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" fill="currentColor" viewBox="0 0 256 256">
                                <path d="M224,152v56a16,16,0,0,1-16,16H48a16,16,0,0,1-16-16V152a8,8,0,0,1,16,0v56H208V152a8,8,0,0,1,16,0Zm-101.66,5.66a8,8,0,0,0,11.32,0l40-40a8,8,0,0,0-11.32-11.32L136,132.69V40a8,8,0,0,0-16,0v92.69L93.66,106.34a8,8,0,0,0-11.32,11.32Z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="table-container">
                    <div class="table-wrapper">
                        <table class="data-table">
                            <thead class="table-header">
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Purpose</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody class="table-body">
                                <?php foreach ($visitors as $visitor): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($visitor['visitor_name']); ?></td>
                                    <td class="secondary-text"><?php echo htmlspecialchars($visitor['email'] ?? ''); ?></td>
                                    <td class="secondary-text"><?php echo htmlspecialchars($visitor['phone_number']); ?></td>
                                    <td>
                                        <button class="table-badge">
                                            <?php echo htmlspecialchars($visitor['purpose']); ?>
                                        </button>
                                    </td>
                                    <td class="secondary-text"><?php echo date('M j, Y', strtotime($visitor['visit_date'])); ?></td>
                                    <td>
                                        <a href="#" onclick="viewVisitor(<?php echo $visitor['id']; ?>)" class="table-link">View</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($visitors)): ?>
                                <tr>
                                    <td colspan="6" class="empty-state">
                                        <?php echo $visitorSearch ? 'No visitors found matching your search.' : 'No visitors found.'; ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        // Export functions
        function exportAdmissions() {
            const search = '<?php echo urlencode($admissionSearch); ?>';
            window.open('../export.php?type=admissions&search=' + search, '_blank');
        }

        function exportVisitors() {
            const search = '<?php echo urlencode($visitorSearch); ?>';
            window.open('../export.php?type=visitors&search=' + search, '_blank');
        }

        function viewAdmission(id) {
            showAdmissionDetails(id);
        }

        function viewVisitor(id) {
            showVisitorDetails(id);
        }

        // Auto-submit search forms
        document.querySelectorAll('input[name="admission_search"], input[name="visitor_search"]').forEach(input => {
            let timeout;
            input.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    this.form.submit();
                }, 500);
            });
        });
    </script>
</body>
</html>