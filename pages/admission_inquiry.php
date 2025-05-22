<?php
require_once '../includes/config.php';
require_once '../includes/connection.php';
require_once '../includes/functions.php';

// Require authentication and manage_admissions permission
requirePermission('manage_admissions');

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $currentUser = getCurrentUser();
        
        $data = array(
            'child_name' => trim($_POST['child_name']),
            'parent_name' => trim($_POST['parent_name']),
            'parent_email' => trim($_POST['parent_email']),
            'phone_number' => trim($_POST['phone_number']),
            'desired_class' => $_POST['desired_class'],
            'address' => trim($_POST['address']),
            'specific_requirements' => trim($_POST['specific_requirements']),
            'parent_notes' => trim($_POST['parent_notes']),
            'created_by' => $currentUser['id']
        );

        // Basic validation
        if (empty($data['child_name']) || empty($data['parent_name']) || empty($data['phone_number'])) {
            throw new Exception('Please fill in all required fields.');
        }

        if (!empty($data['parent_email']) && !filter_var($data['parent_email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Please enter a valid email address.');
        }

        if (!validatePhone($data['phone_number'])) {
            throw new Exception('Please enter a valid phone number.');
        }

        if (empty($data['desired_class'])) {
            throw new Exception('Please select a desired class.');
        }

        $id = $db->insert('admission_inquiries', $data);
        $message = 'Admission inquiry submitted successfully! Reference ID: ' . $id;
        $messageType = 'success';
        
        logActivity('Created Admission Inquiry', "ID: $id, Child: {$data['child_name']}");
        
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
    <title>New Admission Inquiry - School Admin System</title>
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
                        <g clip-path="url(#clip0_6_319)">
                            <path d="M8.57829 8.57829C5.52816 11.6284 3.451 15.5145 2.60947 19.7452C1.76794 23.9758 2.19984 28.361 3.85056 32.3462C5.50128 36.3314 8.29667 39.7376 11.8832 42.134C15.4698 44.5305 19.6865 45.8096 24 45.8096C28.3135 45.8096 32.5302 44.5305 36.1168 42.134C39.7033 39.7375 42.4987 36.3314 44.1494 32.3462C45.8002 28.361 46.2321 23.9758 45.3905 19.7452C44.549 15.5145 42.4718 11.6284 39.4217 8.57829L24 24L8.57829 8.57829Z" fill="currentColor"></path>
                        </g>
                        <defs>
                            <clipPath id="clip0_6_319"><rect width="48" height="48" fill="white"></rect></clipPath>
                        </defs>
                    </svg>
                </div>
                <h2 class="logo-text">School Admin</h2>
            </div>
            <div class="nav-section">
                <nav class="nav-links">
                    <a class="nav-link" href="dashboard.php">Dashboard</a>
                    <a class="nav-link active" href="admission_inquiry.php">Admissions</a>
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
                    <h1 class="page-title">New Admission Inquiry</h1>
                </div>

                <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>" id="message">
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <form id="admissionForm" method="POST" action="">
                    <div class="form-group">
                        <div class="form-field">
                            <label class="form-label required">Child's Name</label>
                            <input
                                name="child_name"
                                placeholder="Enter child's name"
                                class="form-input"
                                value="<?php echo isset($_POST['child_name']) ? htmlspecialchars($_POST['child_name']) : ''; ?>"
                                required
                            />
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-field">
                            <label class="form-label required">Parent's Name</label>
                            <input
                                name="parent_name"
                                placeholder="Enter parent's name"
                                class="form-input"
                                value="<?php echo isset($_POST['parent_name']) ? htmlspecialchars($_POST['parent_name']) : ''; ?>"
                                required
                            />
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-field">
                            <label class="form-label">Parent's Email</label>
                            <input
                                name="parent_email"
                                type="email"
                                placeholder="Enter parent's email"
                                class="form-input"
                                value="<?php echo isset($_POST['parent_email']) ? htmlspecialchars($_POST['parent_email']) : ''; ?>"
                            />
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-field">
                            <label class="form-label">Specific Requirements</label>
                            <input
                                name="specific_requirements"
                                placeholder="Enter specific requirements"
                                class="form-input"
                                value="<?php echo isset($_POST['specific_requirements']) ? htmlspecialchars($_POST['specific_requirements']) : ''; ?>"
                            />
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-field">
                            <label class="form-label required">Phone Number</label>
                            <input
                                name="phone_number"
                                placeholder="Enter phone number"
                                class="form-input"
                                value="<?php echo isset($_POST['phone_number']) ? htmlspecialchars($_POST['phone_number']) : ''; ?>"
                                required
                            />
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-field">
                            <label class="form-label required">Desired Class</label>
                            <select
                                name="desired_class"
                                class="form-input form-select"
                                required
                            >
                                <option value="">Select desired class</option>
                                <option value="Pre-K" <?php echo (isset($_POST['desired_class']) && $_POST['desired_class'] == 'Pre-K') ? 'selected' : ''; ?>>Pre-K</option>
                                <option value="Kindergarten" <?php echo (isset($_POST['desired_class']) && $_POST['desired_class'] == 'Kindergarten') ? 'selected' : ''; ?>>Kindergarten</option>
                                <option value="Grade 1" <?php echo (isset($_POST['desired_class']) && $_POST['desired_class'] == 'Grade 1') ? 'selected' : ''; ?>>Grade 1</option>
                                <option value="Grade 2" <?php echo (isset($_POST['desired_class']) && $_POST['desired_class'] == 'Grade 2') ? 'selected' : ''; ?>>Grade 2</option>
                                <option value="Grade 3" <?php echo (isset($_POST['desired_class']) && $_POST['desired_class'] == 'Grade 3') ? 'selected' : ''; ?>>Grade 3</option>
                                <option value="Grade 4" <?php echo (isset($_POST['desired_class']) && $_POST['desired_class'] == 'Grade 4') ? 'selected' : ''; ?>>Grade 4</option>
                                <option value="Grade 5" <?php echo (isset($_POST['desired_class']) && $_POST['desired_class'] == 'Grade 5') ? 'selected' : ''; ?>>Grade 5</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-field">
                            <label class="form-label">Address</label>
                            <input
                                name="address"
                                placeholder="Enter address"
                                class="form-input"
                                value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>"
                            />
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-field">
                            <label class="form-label">Parent Notes</label>
                            <textarea
                                name="parent_notes"
                                placeholder="Enter parent notes"
                                class="form-input form-textarea"
                            ><?php echo isset($_POST['parent_notes']) ? htmlspecialchars($_POST['parent_notes']) : ''; ?></textarea>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            Save Inquiry
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        // Form validation on submit
        document.getElementById('admissionForm').addEventListener('submit', function(e) {
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            
            const errors = FormValidator.validateAdmissionForm(data);
            
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