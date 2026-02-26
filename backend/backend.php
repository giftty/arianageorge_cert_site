<?php
session_start();

// Include email sender utility
require_once __DIR__ . '/email_sender.php';

$dbFile = __DIR__ . '/data.db';
try {
    $pdo = new PDO("sqlite:" . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Initialize Database
    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL,
        email TEXT NOT NULL,
        password TEXT NOT NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS certificates (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        unique_number TEXT UNIQUE NOT NULL,
        cert_code TEXT UNIQUE NOT NULL,
        owner TEXT NOT NULL,
        cert_type TEXT,
        date_issued DATE,
        expiration_date DATE,
        event TEXT,
        views INTEGER DEFAULT 0,
        downloads INTEGER DEFAULT 0,
        generated_status TEXT DEFAULT 'not-generated',
        status TEXT DEFAULT 'not active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        firstname TEXT NOT NULL,
        lastname TEXT NOT NULL,
        email TEXT,
        phone TEXT,
        address TEXT,
        course TEXT,
        duration_of_course TEXT,
        resumption_date DATE,
        cert_code TEXT,
        profile_image TEXT,
        created_on DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Messages table for contact form
    $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT,
        email TEXT,
        subject TEXT,
        message TEXT,
        status TEXT DEFAULT 'waiting',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
}
catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Ensure 'status' column exists in certificates table for backward compatibility
try {
    $cols = $pdo->query("PRAGMA table_info(certificates)")->fetchAll(PDO::FETCH_ASSOC);
    $hasStatus = false;
    foreach ($cols as $col) {
        if (isset($col['name']) && $col['name'] === 'status') {
            $hasStatus = true;
            break;
        }
    }
    if (!$hasStatus) {
        $pdo->exec("ALTER TABLE certificates ADD COLUMN status TEXT DEFAULT 'not active'");
    }
}
catch (PDOException $e) {
    // non-fatal: continue without breaking the app
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'register') {
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if ($username && $email && $password) {
            $stmt = $pdo->prepare("INSERT INTO admins (username, email, password) VALUES (:username, :email, :password)");
            $stmt->execute([
                ':username' => $username,
                ':email' => $email,
                ':password' => $password
            ]);

            if (isset($_GET['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'success', 'message' => 'Registration successful! Redirecting to login...', 'redirect' => '/admin/login.html']);
                exit;
            }
            header("Location: /admin/login.html?registered=1");
            exit;
        }
        else {
            if (isset($_GET['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Please fill all fields.']);
                exit;
            }
            die("Please fill all fields.");
        }
    }

    if ($action === 'login') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE (username = :identifier OR email = :identifier) AND password = :password");
        $stmt->execute([
            ':identifier' => $username,
            ':password' => $password
        ]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];

            if (isset($_GET['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'success', 'redirect' => '/admin/index.html']);
                exit;
            }

            header("Location: /admin/index.html");
            exit;
        }
        else {
            if (isset($_GET['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Invalid username or password.']);
                exit;
            }

            header("Location: /admin/login.html?error=invalid_credentials");
            exit;
        }
    }

    if ($action === 'add_certificate') {
        // Function to generate a random certificate code snippet
        function generateRandomSnippet()
        {
            $chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
            $snippet = "";
            for ($i = 0; $i < 12; $i++) {
                $snippet .= $chars[rand(0, strlen($chars) - 1)];
            }
            return $snippet;
        }

        $owner = $_POST['owner'] ?? '';
        $cert_type = $_POST['cert_type'] ?? '';
        $date_issued = $_POST['date_issued'] ?? '';
        $expiration_date = $_POST['expiration_date'] ?? null;
        $event = $_POST['event'] ?? '';

        if ($cert_type && $date_issued && $expiration_date) {
            try {
                $pdo->beginTransaction();

                // 1. Generate a temporary random certificate code to satisfy unique constraint
                $temp_code = "TEMP_" . uniqid();
                $stmt = $pdo->prepare("INSERT INTO certificates (cert_code, owner, cert_type, date_issued, expiration_date, event, generated_status) VALUES (:cert_code, :owner, :cert_type, :date_issued, :expiration_date, :event, 'not-generated')");
                $stmt->execute([
                    ':cert_code' => $temp_code,
                    ':owner' => $owner,
                    ':cert_type' => $cert_type ?: null,
                    ':date_issued' => $date_issued ?: null,
                    ':expiration_date' => $expiration_date ?: null,
                    ':event' => $event ?: null
                ]);

                $cert_id = $pdo->lastInsertId();

                // 2. Define prefix mapping
                $prefixes = [
                    'Riggers Work at Height Safety' => 'AGISL/EHS/WAH/024/',
                    'Basic CPR AED and First Aid' => 'AGISL/EHS/FA/003/',
                    'Electrical Safety' => 'AGISL/EHS/ES/024/',
                    'Fire Prevention and Fighting' => 'AGISL/EHS/FPF/020/',
                    'Risk Assessment' => 'AGISL/EHS/RA/025/',
                    'Confined Space Safety' => 'AGISL/EHS/CSS/030/',
                    'Mechanical Works Safety' => 'AGISL/EHS/MWS/038/',
                    'Hot Work Safety' => 'AGISL/EHS/HWS/050/',
                    'Civil Works Safety' => 'AGISL/EHS/CWS/041/',
                    'Rigging and Lifting' => 'AGISL/EHS/RLC/50/',
                    'Fall Arrest and Basic Rescue' => 'AGISL/EHS/EMF/RF/027/',
                    'Driving Safely_Defensive Driving' => 'AGISL/EHS/DDC/022'
                ];
                $stmt = $pdo->query("SELECT COUNT(*) FROM certificates");
                $count = $stmt->fetchColumn();
                $base_prefix = $prefixes[$cert_type] ;
                $random_snippet = generateRandomSnippet();

                // Format: Prefix/0.ID/RandomCode
                $final_cert_code = $base_prefix . (10000+$count);

                // 3. Update the certificate with the final code
                $updateStmt = $pdo->prepare("UPDATE certificates SET cert_code = :cert_code, unique_number = :unic_num WHERE id = :id");
                $updateStmt->execute([
                    ':cert_code' => $final_cert_code,
                    ':unic_num' => $random_snippet,
                    ':id' => $cert_id
                ]);

                $pdo->commit();

                if (isset($_GET['ajax'])) {
                    header('Content-Type: application/json');
                    echo json_encode(['status' => 'success', 'message' => 'Certificate registered successfully!', 'cert_code' => $final_cert_code]);
                    exit;
                }
                header("Location: /admin/index.html?success=cert_added");
                exit;
            }
            catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = "Database error: " . $e->getMessage();

                if (isset($_GET['ajax'])) {
                    header('Content-Type: application/json');
                    echo json_encode(['status' => 'error', 'message' => $error]);
                    exit;
                }
                die($error);
            }
        }
        else {
            if (isset($_GET['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Please fill all required fields.']);
                exit;
            }
            die("Please fill all required fields.");
        }
    }

    if ($action === 'add_user') {
        $firstname = $_POST['firstname'] ?? '';
        $lastname = $_POST['lastname'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $address = $_POST['address'] ?? '';
        $course = $_POST['course'] ?? '';
        $duration_of_course = $_POST['duration_of_course'] ?? '';
        $resumption_date = $_POST['resumption_date'] ?? '';
        $cert_code = $_POST['cert_code'] ?? '';

        $errors = [];
        if (!$firstname)
            $errors['firstname'] = 'Firstname is required.';
        if (!$lastname)
            $errors['lastname'] = 'Lastname is required.';
        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL))
            $errors['email'] = 'Invalid email format.';

        $profile_image = '';
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $fileSize = $_FILES['profile_image']['size'];
            $fileType = $_FILES['profile_image']['type'];
            $fileName = $_FILES['profile_image']['name'];
            $fileTmpPath = $_FILES['profile_image']['tmp_name'];

            // Validate size (2MB)
            if ($fileSize > 2 * 1024 * 1024) {
                $errors['profile_image'] = 'Image size must be less than 2MB.';
            }

            // Validate type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($fileType, $allowedTypes)) {
                $errors['profile_image'] = 'Invalid image format. Only JPG, PNG, GIF, and WEBP are allowed.';
            }

            if (empty($errors)) {
                $uploadDir = __DIR__ . '/../uploads/users/';
                if (!file_exists($uploadDir))
                    mkdir($uploadDir, 0777, true);
                $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
                $newFileName = uniqid('user_', true) . '.' . $fileExtension;
                $destPath = $uploadDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    $profile_image = '/uploads/users/' . $newFileName;
                }
            }
        }
        else {
            $errors['profile_image'] = 'Profile image is required.';
        }

        if (!empty($errors)) {
            if (isset($_GET['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Please fix the errors below.', 'errors' => $errors]);
                exit;
            }
            die(implode("\n", $errors));
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO users (firstname, lastname, email, phone, address, course, duration_of_course, resumption_date, cert_code, profile_image) VALUES (:firstname, :lastname, :email, :phone, :address, :course, :duration_of_course, :resumption_date, :cert_code, :profile_image)");
            $stmt->execute([
                ':firstname' => $firstname,
                ':lastname' => $lastname,
                ':email' => $email ?: null,
                ':phone' => $phone ?: null,
                ':address' => $address ?: null,
                ':course' => $course ?: null,
                ':duration_of_course' => $duration_of_course ?: null,
                ':resumption_date' => $resumption_date ?: null,
                ':cert_code' => $cert_code ?: null,
                ':profile_image' => $profile_image ?: null
            ]);

            // Update certificate owner if cert_code is provided
            if ($cert_code) {
                $fullName = $firstname . ' ' . $lastname;
                $updateStmt = $pdo->prepare("UPDATE certificates SET owner = :owner WHERE cert_code = :cert_code");
                $updateStmt->execute([
                    ':owner' => $fullName,
                    ':cert_code' => $cert_code
                ]);
            }

            if (isset($_GET['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'success', 'message' => 'User added successfully!']);
                exit;
            }
            header("Location: /admin/users.html?success=user_added");
            exit;
        }
        catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
            if (isset($_GET['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => $error]);
                exit;
            }
            die($error);
        }
    }

    if ($action === 'edit_user') {
        $id = $_GET['id'] ?? '';
        if (!$id) {
            if (isset($_GET['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Missing user id.']);
                exit;
            }
            die('Missing user id.');
        }

        // Fetch existing user
        $existingStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $existingStmt->execute([$id]);
        $existing = $existingStmt->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {
            if (isset($_GET['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'User not found.']);
                exit;
            }
            die('User not found.');
        }

        $firstname = $_POST['firstname'] ?? $existing['firstname'];
        $lastname = $_POST['lastname'] ?? $existing['lastname'];
        $email = $_POST['email'] ?? $existing['email'];
        $phone = $_POST['phone'] ?? $existing['phone'];
        $address = $_POST['address'] ?? $existing['address'];
        $course = $_POST['course'] ?? $existing['course'];
        $duration_of_course = $_POST['duration_of_course'] ?? $existing['duration_of_course'];
        $resumption_date = $_POST['resumption_date'] ?? $existing['resumption_date'];
        $cert_code = $_POST['cert_code'] ?? $existing['cert_code'];

        $errors = [];
        if (!$firstname)
            $errors['firstname'] = 'Firstname is required.';
        if (!$lastname)
            $errors['lastname'] = 'Lastname is required.';
        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL))
            $errors['email'] = 'Invalid email format.';

        $profile_image = $existing['profile_image'] ?? '';
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $fileSize = $_FILES['profile_image']['size'];
            $fileType = $_FILES['profile_image']['type'];
            $fileName = $_FILES['profile_image']['name'];
            $fileTmpPath = $_FILES['profile_image']['tmp_name'];

            if ($fileSize > 2 * 1024 * 1024) {
                $errors['profile_image'] = 'Image size must be less than 2MB.';
            }

            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($fileType, $allowedTypes)) {
                $errors['profile_image'] = 'Invalid image format. Only JPG, PNG, GIF, and WEBP are allowed.';
            }

            if (empty($errors)) {
                $uploadDir = __DIR__ . '/../uploads/users/';
                if (!file_exists($uploadDir))
                    mkdir($uploadDir, 0777, true);
                $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
                $newFileName = uniqid('user_', true) . '.' . $fileExtension;
                $destPath = $uploadDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    // delete old image
                    if (!empty($existing['profile_image'])) {
                        $oldPath = __DIR__ . '/..' . $existing['profile_image'];
                        if (file_exists($oldPath)) unlink($oldPath);
                    }
                    $profile_image = '/uploads/users/' . $newFileName;
                }
            }
        }

        if (!empty($errors)) {
            if (isset($_GET['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Please fix the errors below.', 'errors' => $errors]);
                exit;
            }
            die(implode("\n", $errors));
        }

        try {
            $stmt = $pdo->prepare("UPDATE users SET firstname = :firstname, lastname = :lastname, email = :email, phone = :phone, address = :address, course = :course, duration_of_course = :duration_of_course, resumption_date = :resumption_date, cert_code = :cert_code, profile_image = :profile_image WHERE id = :id");
            $stmt->execute([
                ':firstname' => $firstname,
                ':lastname' => $lastname,
                ':email' => $email ?: null,
                ':phone' => $phone ?: null,
                ':address' => $address ?: null,
                ':course' => $course ?: null,
                ':duration_of_course' => $duration_of_course ?: null,
                ':resumption_date' => $resumption_date ?: null,
                ':cert_code' => $cert_code ?: null,
                ':profile_image' => $profile_image ?: null,
                ':id' => $id
            ]);

            // Update certificate owners if changed
            $fullName = $firstname . ' ' . $lastname;
            if (!empty($existing['cert_code']) && $existing['cert_code'] !== $cert_code) {
                // clear previous certificate owner
                $clearStmt = $pdo->prepare("UPDATE certificates SET owner = '' WHERE cert_code = :code");
                $clearStmt->execute([':code' => $existing['cert_code']]);
            }
            if ($cert_code) {
                $updateCert = $pdo->prepare("UPDATE certificates SET owner = :owner WHERE cert_code = :cert_code");
                $updateCert->execute([':owner' => $fullName, ':cert_code' => $cert_code]);
            }

            if (isset($_GET['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'success', 'message' => 'User updated successfully!']);
                exit;
            }
            header("Location: /admin/users.html?success=user_updated");
            exit;
        }
        catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
            if (isset($_GET['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => $error]);
                exit;
            }
            die($error);
        }
    }
}

if ($action === 'get_dashboard_data') {
    if (!isLoggedIn()) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }

    try {
        // Get analytics
        $stmt = $pdo->query("SELECT COUNT(*) as total, SUM(views) as views, SUM(downloads) as downloads FROM certificates");
        $analytics = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get certificate list
        $stmt = $pdo->query("SELECT * FROM certificates ORDER BY created_at DESC");
        $certificates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'analytics' => [
                'total' => $analytics['total'] ?? 0,
                'views' => $analytics['views'] ?? 0,
                'downloads' => $analytics['downloads'] ?? 0
            ],
            'certificates' => $certificates
        ]);
        exit;
    }
    catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

if ($action === 'get_users_data') {
    if (!isLoggedIn()) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }

    try {
        // Get analytics
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
        $analytics = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get user list
        $stmt = $pdo->query("SELECT * FROM users ORDER BY created_on DESC");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'analytics' => [
                'total' => $analytics['total'] ?? 0
            ],
            'users' => $users
        ]);
        exit;
    }
    catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

    if ($action === 'get_user') {
        if (!isLoggedIn()) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }

        $id = $_GET['id'] ?? '';
        if (!$id) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Missing id']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$user) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'User not found']);
                exit;
            }

            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'user' => $user]);
            exit;
        }
        catch (PDOException $e) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
    }

if ($action === 'delete_user') {
    if (!isLoggedIn()) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }

    $id = $_GET['id'] ?? '';
    if ($id) {
        try {
            // Fetch linked cert_code and profile image before deleting
            $certStmt = $pdo->prepare("SELECT cert_code, profile_image FROM users WHERE id = ?");
            $certStmt->execute([$id]);
            $linkedData = $certStmt->fetch(PDO::FETCH_ASSOC);

            // Delete profile image from disk if it exists
            if ($linkedData && !empty($linkedData['profile_image'])) {
                $imgPath = __DIR__ . '/..' . $linkedData['profile_image'];
                if (file_exists($imgPath))
                    unlink($imgPath);
            }

            // Delete the user
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);

            // Cascade: delete linked certificate
            if ($linkedData && !empty($linkedData['cert_code'])) {
                $delCert = $pdo->prepare("DELETE FROM certificates WHERE cert_code = ?");
                $delCert->execute([$linkedData['cert_code']]);
            }

            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'User and linked certificate deleted successfully.']);
            exit;
        }
        catch (PDOException $e) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
    }
}

// Save a contact message (public)
if ($action === 'save_message') {
    // Accept POST or GET (prefer POST)
    $name = $_POST['name'] ?? $_GET['name'] ?? '';
    $email = $_POST['email'] ?? $_GET['email'] ?? '';
    $subject = $_POST['subject'] ?? $_GET['subject'] ?? '';
    $message = $_POST['message'] ?? $_GET['message'] ?? '';

    if (!$email || !$message) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Email and message are required.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO messages (name, email, subject, message, status) VALUES (:name, :email, :subject, :message, 'waiting')");
        $stmt->execute([
            ':name' => $name ?: null,
            ':email' => $email,
            ':subject' => $subject ?: null,
            ':message' => $message
        ]);

        // Send notification email to admin
        $admin_email = 'ariana_admin@arianageorgegroups.com';
        $email_subject = 'New Contact Message: ' . ($subject ?: 'No Subject');
        $email_body = "<html><body style='font-family: Arial, sans-serif;'>";
        $email_body .= "<h2>New Contact Message</h2>";
        $email_body .= "<p><strong>From:</strong> " . htmlspecialchars($name ?: 'Unknown') . "</p>";
        $email_body .= "<p><strong>Email:</strong> <a href='mailto:" . htmlspecialchars($email) . "'>" . htmlspecialchars($email) . "</a></p>";
        if ($subject) {
            $email_body .= "<p><strong>Subject:</strong> " . htmlspecialchars($subject) . "</p>";
        }
        $email_body .= "<p><strong>Message:</strong></p>";
        $email_body .= "<div style='background-color:#f5f5f5;padding:10px;border-radius:5px;'>" . nl2br(htmlspecialchars($message)) . "</div>";
        $email_body .= "<p style='margin-top:20px;color:#666;'><a href='http://" . $_SERVER['HTTP_HOST'] . "/admin/messages.html'>View message in admin panel</a></p>";
        $email_body .= "</body></html>";
        
        @send_email($admin_email, $email_subject, $email_body);

        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Message sent.']);
        exit;
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

// Admin: list messages
if ($action === 'get_messages') {
    if (!isLoggedIn()) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }

    try {
        $stmt = $pdo->query("SELECT * FROM messages ORDER BY created_at DESC");
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'messages' => $messages]);
        exit;
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

// Admin: get single message
if ($action === 'get_message') {
    if (!isLoggedIn()) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }
    $id = $_GET['id'] ?? '';
    if (!$id) { header('Content-Type: application/json'); echo json_encode(['status'=>'error','message'=>'Missing id']); exit; }
    try {
        $stmt = $pdo->prepare("SELECT * FROM messages WHERE id = ?");
        $stmt->execute([$id]);
        $msg = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$msg) { header('Content-Type: application/json'); echo json_encode(['status'=>'error','message'=>'Not found']); exit; }
        header('Content-Type: application/json'); echo json_encode(['status'=>'success','message'=>$msg]); exit;
    } catch (PDOException $e) { header('Content-Type: application/json'); echo json_encode(['status'=>'error','message'=>$e->getMessage()]); exit; }
}

// Admin: mark message read
if ($action === 'mark_message_read') {
    if (!isLoggedIn()) { header('Content-Type: application/json'); echo json_encode(['status'=>'error','message'=>'Unauthorized']); exit; }
    $id = $_GET['id'] ?? '';
    if (!$id) { header('Content-Type: application/json'); echo json_encode(['status'=>'error','message'=>'Missing id']); exit; }
    try { $stmt = $pdo->prepare("UPDATE messages SET status = 'read' WHERE id = ?"); $stmt->execute([$id]); header('Content-Type: application/json'); echo json_encode(['status'=>'success']); exit; }
    catch (PDOException $e) { header('Content-Type: application/json'); echo json_encode(['status'=>'error','message'=>$e->getMessage()]); exit; }
}

// Admin: delete message
if ($action === 'delete_message') {
    if (!isLoggedIn()) { header('Content-Type: application/json'); echo json_encode(['status'=>'error','message'=>'Unauthorized']); exit; }
    $id = $_GET['id'] ?? '';
    if (!$id) { header('Content-Type: application/json'); echo json_encode(['status'=>'error','message'=>'Missing id']); exit; }
    try { $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?"); $stmt->execute([$id]); header('Content-Type: application/json'); echo json_encode(['status'=>'success']); exit; }
    catch (PDOException $e) { header('Content-Type: application/json'); echo json_encode(['status'=>'error','message'=>$e->getMessage()]); exit; }
}

// Admin: send reply to contact message
if ($action === 'send_reply') {
    if (!isLoggedIn()) { header('Content-Type: application/json'); echo json_encode(['status'=>'error','message'=>'Unauthorized']); exit; }
    $message_id = $_POST['message_id'] ?? $_GET['message_id'] ?? '';
    $reply_text = $_POST['reply_text'] ?? $_GET['reply_text'] ?? '';
    
    if (!$message_id || !$reply_text) { header('Content-Type: application/json'); echo json_encode(['status'=>'error','message'=>'Missing message_id or reply_text']); exit; }
    
    try {
        // Get the original message
        $stmt = $pdo->prepare("SELECT * FROM messages WHERE id = ?");
        $stmt->execute([$message_id]);
        $msg = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$msg) { header('Content-Type: application/json'); echo json_encode(['status'=>'error','message'=>'Message not found']); exit; }
        
        // Send reply email
        $subject = 'Re: ' . ($msg['subject'] ?: 'Your Message');
        $body = "<html><body style='font-family: Arial, sans-serif;'>";
        $body .= "<p>Dear " . htmlspecialchars($msg['name'] ?: 'Visitor') . ",</p>";
        $body .= "<div style='background-color:#f5f5f5;padding:10px;border-radius:5px;margin:15px 0;'>";
        $body .= nl2br(htmlspecialchars($reply_text));
        $body .= "</div>";
        $body .= "<p>Best regards,<br>Ariana Groups</p>";
        $body .= "</body></html>";
        
        send_email($msg['email'], $subject, $body);
        
        // Mark original message as read
        $updateStmt = $pdo->prepare("UPDATE messages SET status = 'read' WHERE id = ?");
        $updateStmt->execute([$message_id]);
        
        header('Content-Type: application/json');
        echo json_encode(['status'=>'success','message'=>'Reply sent']);
        exit;
    } catch (PDOException $e) { header('Content-Type: application/json'); echo json_encode(['status'=>'error','message'=>$e->getMessage()]); exit; }
}

if ($action === 'delete_certificate') {
    if (!isLoggedIn()) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }

    $code = $_GET['code'] ?? '';
    if ($code) {
        try {
            // Fetch linked user before deleting the certificate
            $userStmt = $pdo->prepare("SELECT * FROM users WHERE cert_code = ?");
            $userStmt->execute([$code]);
            $linkedUser = $userStmt->fetch(PDO::FETCH_ASSOC);

            // Delete the certificate
            $stmt = $pdo->prepare("DELETE FROM certificates WHERE cert_code = ?");
            $stmt->execute([$code]);

            // Cascade: delete linked user and their profile image
            if ($linkedUser) {
                if (!empty($linkedUser['profile_image'])) {
                    $imgPath = __DIR__ . '/..' . $linkedUser['profile_image'];
                    if (file_exists($imgPath))
                        unlink($imgPath);
                }
                $delUser = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $delUser->execute([$linkedUser['id']]);
            }

            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Certificate and linked user deleted successfully.']);
            exit;
        }
        catch (PDOException $e) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
    }
}

if ($action === 'generate_certificate') {
    if (!isLoggedIn()) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }

    $code = $_GET['code'] ?? '';
    if ($code) {
        try {
            $stmt = $pdo->prepare("UPDATE certificates SET generated_status = 'generated' WHERE cert_code = ?");
            $stmt->execute([$code]);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Certificate generated successfully.']);
            exit;
        }
        catch (PDOException $e) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
    }
}

// Public endpoint to record a download triggered by client-side PDF save
if ($action === 'record_download') {
    $code = $_GET['code'] ?? $_POST['code'] ?? '';
    if (!$code) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Missing code']);
        exit;
    }

    // Use session to avoid double counting per session
    if (!isset($_SESSION['downloaded_certs']) || !is_array($_SESSION['downloaded_certs'])) {
        $_SESSION['downloaded_certs'] = [];
    }

    if (!empty($_SESSION['downloaded_certs'][$code])) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Already recorded in this session']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE certificates SET downloads = COALESCE(downloads,0) + 1 WHERE cert_code = :code");
        $stmt->execute([$code]);
        $_SESSION['downloaded_certs'][$code] = time();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Download recorded']);
        exit;
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

if ($action === 'update_certificate_status') {
    if (!isLoggedIn()) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }

    $code = $_GET['code'] ?? '';
    $newStatus = isset($_GET['status']) ? trim($_GET['status']) : '';

    $allowed = ['active', 'not active', 'revoked', 'expired', 'suspended'];

    if (!$code || $newStatus === '') {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Missing parameters.']);
        exit;
    }

    if (!in_array(strtolower($newStatus), $allowed)) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Invalid status value.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE certificates SET status = :status WHERE cert_code = :code");
        $stmt->execute([':status' => strtolower($newStatus), ':code' => $code]);

        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Certificate status updated.']);
        exit;
    }
    catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

const ENC_KEY = 'AGISL_CERT_SECRET_KEY'; // Secret key for encryption (must match certpdf.php)
const ENC_METHOD = 'AES-128-ECB'; // Encryption method

function encryptCode($code)
{
    return urlencode(base64_encode(openssl_encrypt($code, ENC_METHOD, ENC_KEY)));
}

if ($action === 'set_cert_session') {
    if (!isLoggedIn()) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }

    $code = $_GET['code'] ?? '';
    if ($code) {
        $_SESSION['view_cert_code'] = $code;
        $_SESSION['view_cert_timestamp'] = time();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'encrypted_code' => encryptCode($code)]);
        exit;
    }
    else {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Certificate code missing']);
        exit;
    }
}

if ($action === 'logout') {
    session_destroy();
    if (isset($_GET['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'redirect' => '/admin/login.html']);
        exit;
    }
    header("Location: /admin/login.html");
    exit;
}

// Function to check if logged in
function isLoggedIn()
{
    return isset($_SESSION['admin_id']);
}

if ($action === 'check_auth') {
    header('Content-Type: application/json');
    if (isLoggedIn()) {
        echo json_encode(['status' => 'authorized', 'username' => $_SESSION['admin_username']]);
    }
    else {
        echo json_encode(['status' => 'unauthorized']);
    }
    exit;
}

if ($action === 'logout') {
    session_destroy();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'redirect' => '/admin/login.html']);
    exit;
}
?>
