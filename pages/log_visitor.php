<?php
require_once '../includes/config.php';
require_once '../includes/connection.php';
require_once '../includes/functions.php';

// Require authentication and manage_visitors permission
requirePermission('manage_visitors');

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $currentUser = getCurrentUser();
        
        $data = array(
            'visitor_name' => trim($_POST['visitor_name']),
            'email' => trim($_POST['email']),
            'phone_number' => trim($_POST['phone_number']),
            'purpose' => $_POST['purpose'],
            'visit_date' => $_POST['visit_date'],
            'created_by' => $currentUser['id']
        );

        // Basic validation
        if (empty($data['visitor_name']) || empty($data['phone_number']) || empty($data['purpose']) || empty($data['visit_date'])) {
            throw new Exception('Please fill in all required fields.');
        }

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Please enter a valid email address.');
        }

        if (!validatePhone($data['phone_number'])) {
            throw new Exception('Please enter a valid phone number.');
        }

        // Validate date
        $visitDate = DateTime::createFromFormat('Y-m-d', $data['visit_date']);
        if (!$visitDate) {
            throw new Exception('Please enter a valid date.');
        }

        $id = $db->insert('visitors', $data);
        $message = 'Visitor logged successfully! Reference ID: ' . $id;
        $messageType = 'success';
        
        logActivity('Created Visitor Log', "ID: $id, Name: {$data['visitor_name']}");
        
        // Clear form data after successful submission
        $_POST = array();
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Visitor - School Admin System</title>
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
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M7.24189 26.4066C7.31369 26.4411 7.64204 26.5637 8.52504 26.3738C9.59462 26.1438 11.0343 25.5311 12.7183 24.4963C14.7583 23.2426 17.0256 21.4503 19.238 19.238C21.4503 17.0256 23.2426 14.7583 24.4963 12.7183C25.5311 11.0343 26.1438 9.59463 26.3738 8.52504C26.5637 7.64204 26.4411 7.31369 26.4066 7.24189C26.345 7.21246 26.143 7.14535 25.6664 7.1918C24.9745 7.25925 23.9954 7.5498 22.7699 8.14278C20.3369 9.32007 17.3369 11.4915 14.4142 14.4142C11.4915 17.3369 9.32007 20.3369 8.14278 22.7699C7.5498 23.9954 7.25925 24.9745 7.1918 25.6664C7.14534 26.143 7.21246 26.345 7.24189 26.4066ZM29.9001 10.7285C29.4519 12.0322 28.7617 13.4172 27.9042 14.8126C26.465 17.1544 24.4686 19.6641 22.0664 22.0664C19.6641 24.4686 17.1544 26.465 14.8126 27.9042C13.4172 28.7617 12.0322 29.4519 10.7285 29.9001L21.5754 40.747C21.6001 40.7606 21.8995 40.931 22.8729 40.7217C23.9424 40.4916 25.3821 39.879 27.0661 38.8441C29.1062 37.5904 31.3734 35.7982 33.5858 33.5858C35.7982 31.3734 37.5904 29.1062 38.8441 27.0661C39.879 25.3821 40.4916 23.9425 40.7216 22.8729C40.931 21.8995 40.7606 21.6001 40.747 21.5754L29.9001 10.7285ZM29.2403 4.41187L43.5881 18.7597C44.9757 20.1473 44.9743 22.1235 44.6322 23.7139C44.2714 25.3919 43.4158 27.2666 42.252 29.1604C40.8128 31.5022 38.8165 34.012 36.4142 36.4142C34.012 38.8165 31.5022 40.8128 29.1604 42.252C27.2666 43.4158 25.3919 44.2714 23.7139 44.6322C22.1235 44.9743 20.1473 44.9757 18.7597 43.5881L4.41187 29.2403C3.29027 28.1187 3.08209 26.5973 3.21067 25.2783C3.34099 23.9415 3.8369 22.4852 4.54214 21.0277C5.96129 18.0948 8.43335 14.7382 11.5858 11.5858C14.7382 8.43335 18.0948 5.9613 21.0277 4.54214C22.4852 3.8369 23.9415 3.34099 25.2783 3.21067C26.5973 3.08209 28.1187 3.29028 29.2403 4.41187Z" fill="currentColor"></path>
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
                    <a class="nav-link" href="dashboard.php">Dashboard</a>
                    <?php if (hasPermission('manage_admissions')): ?>
                    <a class="nav-link" href="admission_inquiry.php">Admissions</a>
                    <?php endif; ?>
                    <a class="nav-link active" href="log_visitor.php">Visitors</a>
                    <a class="nav-link" href="logout.php">Logout</a>
                </nav>
                <div class="user-avatar" style="background-image: url('https://ui-avatars.com/api/?name=<?php echo urlencode($currentUser['full_name']); ?>&background=f4c653&color=1c170d');"></div>
            </div>
        </header>
        
        <div class="content-wrapper">
            <div class="content-container">
                <div class="page-header">
                    <h1 class="page-title">Log Visitor</h1>
                </div>

                <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>" id="message">
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <form id="visitorForm" method="POST" action="">
                    <div class="form-group">
                        <div class="form-field">
                            <label class="form-label required">Visit Date</label>
                            <input
                                name="visit_date"
                                type="date"
                                class="form-input"
                                value="<?php echo isset($_POST['visit_date']) ? $_POST['visit_date'] : date('Y-m-d'); ?>"
                                required
                            />
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-field">
                            <label class="form-label required">Visitor Name</label>
                            <input
                                name="visitor_name"
                                placeholder="Enter visitor name"
                                class="form-input"
                                value="<?php echo isset($_POST['visitor_name']) ? htmlspecialchars($_POST['visitor_name']) : ''; ?>"
                                required
                            />
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-field">
                            <label class="form-label">Email Address</label>
                            <input
                                name="email"
                                type="email"
                                placeholder="Enter email address"
                                class="form-input"
                                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                            />
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-field">
                            <label class="form-label required">Purpose of Visit</label>
                            <select
                                name="purpose"
                                class="form-input form-select"
                                required
                            >
                                <option value="">Select purpose of visit</option>
                                <option value="Tour" <?php echo (isset($_POST['purpose']) && $_POST['purpose'] == 'Tour') ? 'selected' : ''; ?>>School Tour</option>
                                <option value="Meeting" <?php echo (isset($_POST['purpose']) && $_POST['purpose'] == 'Meeting') ? 'selected' : ''; ?>>Meeting with Staff</option>
                                <option value="Interview" <?php echo (isset($_POST['purpose']) && $_POST['purpose'] == 'Interview') ? 'selected' : ''; ?>>Interview</option>
                                <option value="Event" <?php echo (isset($_POST['purpose']) && $_POST['purpose'] == 'Event') ? 'selected' : ''; ?>>School Event</option>
                                <option value="Consultation" <?php echo (isset($_POST['purpose']) && $_POST['purpose'] == 'Consultation') ? 'selected' : ''; ?>>Consultation</option>
                                <option value="Delivery" <?php echo (isset($_POST['purpose']) && $_POST['purpose'] == 'Delivery') ? 'selected' : ''; ?>>Delivery</option>
                                <option value="Maintenance" <?php echo (isset($_POST['purpose']) && $_POST['purpose'] == 'Maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                                <option value="Other" <?php echo (isset($_POST['purpose']) && $_POST['purpose'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-field">
                            <label class="form-label required">Contact Number</label>
                            <input
                                name="phone_number"
                                placeholder="Enter contact number"
                                class="form-input"
                                value="<?php echo isset($_POST['phone_number']) ? htmlspecialchars($_POST['phone_number']) : ''; ?>"
                                required
                            />
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            Submit
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        // Form validation on submit
        document.getElementById('visitorForm').addEventListener('submit', function(e) {
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            
            const errors = FormValidator.validateVisitorForm(data);
            
            if (Object.keys(errors).length > 0) {
                e.preventDefault();
                FormValidator.showFormErrors(errors);
                Utils.showError('Please correct the errors below.');
                return;
            }
        });

        // Clear form errors on input change
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('input', function() {
                this.classList.remove('error');
                const errorElement = this.parentNode.querySelector('.form-error');
                if (errorElement) {
                    errorElement.remove();
                }
            });
        });
    </script>
</body>
</html>