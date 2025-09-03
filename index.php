<?php
// Start session for flash messages
session_start();

// Database connection
$host = 'localhost';
$dbname = 'course_management';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Could not connect to the database: " . htmlspecialchars($e->getMessage()));
}

// Function to set flash messages
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

// Function to display flash messages
function displayFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $type = $_SESSION['flash']['type'] === 'success' ? 'success' : 'danger';
        echo "<div class='alert alert-$type alert-dismissible fade show' role='alert'>";
        echo htmlspecialchars($_SESSION['flash']['message']);
        echo "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>";
        echo "</div>";
        unset($_SESSION['flash']);
    }
}

// Function to validate file uploads
function handleLogoUpload($fieldName, $type) {
    if (isset($_FILES[$fieldName]) && $_FILES[$fieldName]['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        $fileType = $_FILES[$fieldName]['type'];
        $fileSize = $_FILES[$fieldName]['size'];

        if (!in_array($fileType, $allowedTypes)) {
            setFlashMessage('danger', 'Invalid file type. Only PNG and JPG are allowed.');
            return 'default_' . substr($type, 0, -1) . '.png';
        }

        if ($fileSize > $maxSize) {
            setFlashMessage('danger', 'File size exceeds 2MB limit.');
            return 'default_' . substr($type, 0, -1) . '.png';
        }

        $uploadDir = 'uploads/' . $type . '/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileExtension = pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION);
        $fileName = uniqid() . '.' . $fileExtension;
        $uploadPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES[$fieldName]['tmp_name'], $uploadPath)) {
            return $uploadPath;
        } else {
            setFlashMessage('danger', 'Failed to upload file.');
        }
    }
    return 'default_' . substr($type, 0, -1) . '.png';
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['add_student'])) {
            // Validate inputs
            if (empty($_POST['student_number']) || empty($_POST['first_name']) || empty($_POST['last_name']) || empty($_POST['birthday']) || empty($_POST['annual_course'])) {
                setFlashMessage('danger', 'All student fields are required.');
            } else {
                $logo = handleLogoUpload('student_logo', 'students');
                $stmt = $pdo->prepare("INSERT INTO students (student_number, first_name, last_name, birthday, annual_course, logo) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$_POST['student_number'], $_POST['first_name'], $_POST['last_name'], $_POST['birthday'], $_POST['annual_course'], $logo]);
                setFlashMessage('success', 'Student added successfully.');
            }
        } elseif (isset($_POST['add_teacher'])) {
            if (empty($_POST['identification_number']) || empty($_POST['first_name']) || empty($_POST['last_name']) || empty($_POST['subject'])) {
                setFlashMessage('danger', 'All teacher fields are required.');
            } else {
                $logo = handleLogoUpload('teacher_logo', 'teachers');
                $stmt = $pdo->prepare("INSERT INTO teachers (identification_number, first_name, last_name, subject, logo) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$_POST['identification_number'], $_POST['first_name'], $_POST['last_name'], $_POST['subject'], $logo]);
                setFlashMessage('success', 'Teacher added successfully.');
            }
        } elseif (isset($_POST['add_course'])) {
            if (empty($_POST['name']) || empty($_POST['start_date']) || empty($_POST['end_date']) || empty($_POST['teacher_id']) || empty($_POST['space_id'])) {
                setFlashMessage('danger', 'All course fields are required.');
            } else {
                $logo = handleLogoUpload('course_logo', 'courses');
                $stmt = $pdo->prepare("INSERT INTO courses (name, description, start_date, end_date, teacher_id, space_id, logo) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$_POST['name'], $_POST['description'], $_POST['start_date'], $_POST['end_date'], $_POST['teacher_id'], $_POST['space_id'], $logo]);
                setFlashMessage('success', 'Course added successfully.');
            }
        } elseif (isset($_POST['add_space'])) {
            if (empty($_POST['name']) || empty($_POST['capacity'])) {
                setFlashMessage('danger', 'All space fields are required.');
            } else {
                $logo = handleLogoUpload('space_logo', 'spaces');
                $stmt = $pdo->prepare("INSERT INTO spaces (name, capacity, logo) VALUES (?, ?, ?)");
                $stmt->execute([$_POST['name'], $_POST['capacity'], $logo]);
                setFlashMessage('success', 'Space added successfully.');
            }
        } elseif (isset($_POST['add_registration'])) {
            if (empty($_POST['student_id']) || empty($_POST['course_id'])) {
                setFlashMessage('danger', 'All registration fields are required.');
            } else {
                $logo = handleLogoUpload('registration_logo', 'registrations');
                $stmt = $pdo->prepare("INSERT INTO course_registrations (student_id, course_id, logo) VALUES (?, ?, ?)");
                $stmt->execute([$_POST['student_id'], $_POST['course_id'], $logo]);
                setFlashMessage('success', 'Registration added successfully.');
            }
        } elseif (isset($_POST['delete_student'])) {
            $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
            $stmt->execute([$_POST['student_id']]);
            setFlashMessage('success', 'Student deleted successfully.');
        } elseif (isset($_POST['delete_teacher'])) {
            $stmt = $pdo->prepare("DELETE FROM teachers WHERE id = ?");
            $stmt->execute([$_POST['teacher_id']]);
            setFlashMessage('success', 'Teacher deleted successfully.');
        } elseif (isset($_POST['delete_course'])) {
            $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
            $stmt->execute([$_POST['course_id']]);
            setFlashMessage('success', 'Course deleted successfully.');
        } elseif (isset($_POST['delete_space'])) {
            $stmt = $pdo->prepare("DELETE FROM spaces WHERE id = ?");
            $stmt->execute([$_POST['space_id']]);
            setFlashMessage('success', 'Space deleted successfully.');
        } elseif (isset($_POST['delete_registration'])) {
            $stmt = $pdo->prepare("DELETE FROM course_registrations WHERE id = ?");
            $stmt->execute([$_POST['registration_id']]);
            setFlashMessage('success', 'Registration deleted successfully.');
        }

        // Redirect to avoid form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } catch (PDOException $e) {
        setFlashMessage('danger', 'Database error: ' . htmlspecialchars($e->getMessage()));
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Fetch data for display
$students = $pdo->query("SELECT * FROM students")->fetchAll(PDO::FETCH_ASSOC);
$teachers = $pdo->query("SELECT * FROM teachers")->fetchAll(PDO::FETCH_ASSOC);
$courses = $pdo->query("SELECT courses.*, teachers.first_name as teacher_first_name, teachers.last_name as teacher_last_name, spaces.name as space_name FROM courses LEFT JOIN teachers ON courses.teacher_id = teachers.id LEFT JOIN spaces ON courses.space_id = spaces.id")->fetchAll(PDO::FETCH_ASSOC);
$spaces = $pdo->query("SELECT * FROM spaces")->fetchAll(PDO::FETCH_ASSOC);
$registrations = $pdo->query("SELECT cr.*, students.first_name as student_first_name, students.last_name as student_last_name, courses.name as course_name FROM course_registrations cr LEFT JOIN students ON cr.student_id = students.id LEFT JOIN courses ON cr.course_id = courses.id")->fetchAll(PDO::FETCH_ASSOC);

// Fetch data for dropdowns
$teachers_options = $pdo->query("SELECT id, first_name, last_name FROM teachers")->fetchAll(PDO::FETCH_ASSOC);
$spaces_options = $pdo->query("SELECT id, name FROM spaces")->fetchAll(PDO::FETCH_ASSOC);
$students_options = $pdo->query("SELECT id, first_name, last_name FROM students")->fetchAll(PDO::FETCH_ASSOC);
$courses_options = $pdo->query("SELECT id, name FROM courses")->fetchAll(PDO::FETCH_ASSOC);

// Handle AJAX requests for viewing details
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    try {
        if (isset($_POST['view_student'])) {
            $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
            $stmt->execute([$_POST['view_student']]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($student) {
                $output = "
                    <div class='text-center mb-3'>
                        <img src='" . htmlspecialchars($student['logo']) . "' alt='Student Logo' class='img-fluid rounded-circle' style='width: 100px; height: 100px; object-fit: cover;'>
                    </div>
                    <p><strong>Student Number:</strong> " . htmlspecialchars($student['student_number']) . "</p>
                    <p><strong>First Name:</strong> " . htmlspecialchars($student['first_name']) . "</p>
                    <p><strong>Last Name:</strong> " . htmlspecialchars($student['last_name']) . "</p>
                    <p><strong>Birthday:</strong> " . htmlspecialchars($student['birthday']) . "</p>
                    <p><strong>Annual Course:</strong> Year " . htmlspecialchars($student['annual_course']) . "</p>
                    <h5 class='mt-4'>Registered Courses</h5>
                ";

                $stmt = $pdo->prepare("
                    SELECT courses.name, courses.start_date 
                    FROM course_registrations cr 
                    JOIN courses ON cr.course_id = courses.id 
                    WHERE cr.student_id = ?
                ");
                $stmt->execute([$_POST['view_student']]);
                $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($courses) > 0) {
                    $output .= "<ul>";
                    foreach ($courses as $course) {
                        $output .= "<li>" . htmlspecialchars($course['name']) . " (Starts: " . htmlspecialchars($course['start_date']) . ")</li>";
                    }
                    $output .= "</ul>";
                } else {
                    $output .= "<p>No courses registered.</p>";
                }
                echo $output;
            }
            exit;
        } elseif (isset($_POST['view_teacher'])) {
            $stmt = $pdo->prepare("SELECT * FROM teachers WHERE id = ?");
            $stmt->execute([$_POST['view_teacher']]);
            $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($teacher) {
                $output = "
                    <div class='text-center mb-3'>
                        <img src='" . htmlspecialchars($teacher['logo']) . "' alt='Teacher Logo' class='img-fluid rounded-circle' style='width: 100px; height: 100px; object-fit: cover;'>
                    </div>
                    <p><strong>Identification Number:</strong> " . htmlspecialchars($teacher['identification_number']) . "</p>
                    <p><strong>First Name:</strong> " . htmlspecialchars($teacher['first_name']) . "</p>
                    <p><strong>Last Name:</strong> " . htmlspecialchars($teacher['last_name']) . "</p>
                    <p><strong>Subject:</strong> " . htmlspecialchars($teacher['subject']) . "</p>
                    <h5 class='mt-4'>Courses Taught</h5>
                ";

                $stmt = $pdo->prepare("
                    SELECT courses.name, courses.start_date, courses.end_date, spaces.name as space_name 
                    FROM courses 
                    LEFT JOIN spaces ON courses.space_id = spaces.id 
                    WHERE courses.teacher_id = ?
                ");
                $stmt->execute([$_POST['view_teacher']]);
                $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($courses) > 0) {
                    $output .= "<ul>";
                    foreach ($courses as $course) {
                        $output .= "<li>" . htmlspecialchars($course['name']) . " (" . htmlspecialchars($course['start_date']) . " to " . htmlspecialchars($course['end_date']) . ") in " . htmlspecialchars($course['space_name']) . "</li>";
                    }
                    $output .= "</ul>";
                } else {
                    $output .= "<p>No courses assigned.</p>";
                }
                echo $output;
            }
            exit;
        } elseif (isset($_POST['view_course'])) {
            $stmt = $pdo->prepare("
                SELECT courses.*, teachers.first_name as teacher_first_name, teachers.last_name as teacher_last_name, spaces.name as space_name 
                FROM courses 
                LEFT JOIN teachers ON courses.teacher_id = teachers.id 
                LEFT JOIN spaces ON courses.space_id = spaces.id 
                WHERE courses.id = ?
            ");
            $stmt->execute([$_POST['view_course']]);
            $course = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($course) {
                $output = "
                    <div class='text-center mb-3'>
                        <img src='" . htmlspecialchars($course['logo']) . "' alt='Course Logo' class='img-fluid' style='max-height: 150px;'>
                    </div>
                    <p><strong>Name:</strong> " . htmlspecialchars($course['name']) . "</p>
                    <p><strong>Description:</strong> " . htmlspecialchars($course['description']) . "</p>
                    <p><strong>Start Date:</strong> " . htmlspecialchars($course['start_date']) . "</p>
                    <p><strong>End Date:</strong> " . htmlspecialchars($course['end_date']) . "</p>
                    <p><strong>Teacher:</strong> " . htmlspecialchars($course['teacher_first_name'] . ' ' . $course['teacher_last_name']) . "</p>
                    <p><strong>Space:</strong> " . htmlspecialchars($course['space_name']) . "</p>
                    <h5 class='mt-4'>Registered Students</h5>
                ";

                $stmt = $pdo->prepare("
                    SELECT students.first_name, students.last_name, students.annual_course 
                    FROM course_registrations cr 
                    JOIN students ON cr.student_id = students.id 
                    WHERE cr.course_id = ?
                ");
                $stmt->execute([$_POST['view_course']]);
                $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($students) > 0) {
                    $output .= "<ul>";
                    foreach ($students as $student) {
                        $output .= "<li>" . htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) . " (Year " . htmlspecialchars($student['annual_course']) . ")</li>";
                    }
                    $output .= "</ul>";
                } else {
                    $output .= "<p>No students registered.</p>";
                }
                echo $output;
            }
            exit;
        } elseif (isset($_POST['view_space'])) {
            $stmt = $pdo->prepare("SELECT * FROM spaces WHERE id = ?");
            $stmt->execute([$_POST['view_space']]);
            $space = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($space) {
                $output = "
                    <div class='text-center mb-3'>
                        <img src='" . htmlspecialchars($space['logo']) . "' alt='Space Logo' class='img-fluid' style='max-height: 150px;'>
                    </div>
                    <p><strong>Name:</strong> " . htmlspecialchars($space['name']) . "</p>
                    <p><strong>Capacity:</strong> " . htmlspecialchars($space['capacity']) . "</p>
                    <h5 class='mt-4'>Courses in this Space</h5>
                ";

                $stmt = $pdo->prepare("
                    SELECT courses.name, courses.start_date, courses.end_date, teachers.first_name, teachers.last_name,
                    (SELECT COUNT(*) FROM course_registrations WHERE course_id = courses.id) as participants
                    FROM courses 
                    LEFT JOIN teachers ON courses.teacher_id = teachers.id 
                    WHERE courses.space_id = ?
                ");
                $stmt->execute([$_POST['view_space']]);
                $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($courses) > 0) {
                    $output .= "<table class='table table-sm'>";
                    $output .= "<thead><tr><th>Course</th><th>Teacher</th><th>Dates</th><th>Participants</th></tr></thead>";
                    $output .= "<tbody>";
                    foreach ($courses as $course) {
                        $warning = $course['participants'] > $space['capacity'] ? " <span class='warning-icon'><i class='fas fa-exclamation-triangle'></i></span>" : "";
                        $output .= "<tr>
                            <td>" . htmlspecialchars($course['name']) . "</td>
                            <td>" . htmlspecialchars($course['first_name'] . ' ' . $course['last_name']) . "</td>
                            <td>" . htmlspecialchars($course['start_date']) . " to " . htmlspecialchars($course['end_date']) . "</td>
                            <td>" . htmlspecialchars($course['participants']) . $warning . "</td>
                        </tr>";
                    }
                    $output .= "</tbody></table>";
                } else {
                    $output .= "<p>No courses scheduled in this space.</p>";
                }
                echo $output;
            }
            exit;
        }
    } catch (PDOException $e) {
        echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .card {
            margin-bottom: 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .navbar-brand {
            font-weight: bold;
            font-size: 1.5rem;
        }
        .stats-card {
            text-align: center;
            padding: 20px;
        }
        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
        }
        .warning-icon {
            color: #dc3545;
            font-size: 1.2rem;
        }
        .sidebar {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            height: 100%;
        }
        .main-content {
            padding: 20px;
        }
        .action-buttons .btn {
            margin-right: 5px;
        }
        .content-section {
            display: none;
        }
        #dashboard {
            display: block;
        }
        .logo-preview {
            max-width: 100px;
            max-height: 100px;
            margin-top: 10px;
        }
        .table-img {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 50%;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"><i class="fas fa-graduation-cap me-2"></i>Course Management System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#dashboard">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#students">Students</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#teachers">Teachers</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#courses">Courses</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#spaces">Spaces</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#registrations">Registrations</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <?php displayFlashMessage(); ?>
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3">
                <div class="sidebar">
                    <h4>Quick Actions</h4>
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary mb-2" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                            <i class="fas fa-user-plus me-2"></i>Add Student
                        </button>
                        <button class="btn btn-success mb-2" data-bs-toggle="modal" data-bs-target="#addTeacherModal">
                            <i class="fas fa-chalkboard-teacher me-2"></i>Add Teacher
                        </button>
                        <button class="btn btn-info mb-2" data-bs-toggle="modal" data-bs-target="#addCourseModal">
                            <i class="fas fa-book me-2"></i>Add Course
                        </button>
                        <button class="btn btn-warning mb-2" data-bs-toggle="modal" data-bs-target="#addSpaceModal">
                            <i class="fas fa-building me-2"></i>Add Space
                        </button>
                        <button class="btn btn-secondary mb-2" data-bs-toggle="modal" data-bs-target="#addRegistrationModal">
                            <i class="fas fa-clipboard-list me-2"></i>Add Registration
                        </button>
                    </div>
                    
                    <h4 class="mt-4">Statistics</h4>
                    <div class="card stats-card">
                        <div class="card-body">
                            <i class="fas fa-users fa-2x text-primary mb-2"></i>
                            <div class="stats-number"><?php echo count($students); ?></div>
                            <div>Students</div>
                        </div>
                    </div>
                    <div class="card stats-card">
                        <div class="card-body">
                            <i class="fas fa-chalkboard-teacher fa-2x text-success mb-2"></i>
                            <div class="stats-number"><?php echo count($teachers); ?></div>
                            <div>Teachers</div>
                        </div>
                    </div>
                    <div class="card stats-card">
                        <div class="card-body">
                            <i class="fas fa-book fa-2x text-info mb-2"></i>
                            <div class="stats-number"><?php echo count($courses); ?></div>
                            <div>Courses</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 main-content">
                <!-- Dashboard -->
                <div id="dashboard">
                    <h2 class="mb-4">Dashboard</h2>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5>Recent Course Registrations</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Logo</th>
                                                    <th>Student</th>
                                                    <th>Course</th>
                                                    <th>Registration Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach (array_slice($registrations, 0, 5) as $registration): ?>
                                                <tr>
                                                    <td><img src="<?php echo htmlspecialchars($registration['logo'] ?? 'default_registration.png'); ?>" class="table-img" alt="Registration Logo"></td>
                                                    <td><?php echo htmlspecialchars($registration['student_first_name'] . ' ' . $registration['student_last_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($registration['course_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($registration['registration_date']); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Students Section -->
                <div id="students" class="content-section">
                    <h2 class="mb-4">Students</h2>
                    <div class="card">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <h5>All Students</h5>
                            <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                                <i class="fas fa-plus me-1"></i> Add New
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Logo</th>
                                            <th>Student Number</th>
                                            <th>First Name</th>
                                            <th>Last Name</th>
                                            <th>Birthday</th>
                                            <th>Annual Course</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students as $student): ?>
                                        <tr>
                                            <td><img src="<?php echo htmlspecialchars($student['logo']); ?>" class="table-img" alt="Student Logo"></td>
                                            <td><?php echo htmlspecialchars($student['student_number']); ?></td>
                                            <td><?php echo htmlspecialchars($student['first_name']); ?></td>
                                            <td><?php echo htmlspecialchars($student['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($student['birthday']); ?></td>
                                            <td>Year <?php echo htmlspecialchars($student['annual_course']); ?></td>
                                            <td class="action-buttons">
                                                <button class="btn btn-sm btn-info" onclick="viewStudent(<?php echo $student['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <form method="post" style="display:inline;">
                                                    <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                                    <button type="submit" name="delete_student" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this student?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Teachers Section -->
                <div id="teachers" class="content-section">
                    <h2 class="mb-4">Teachers</h2>
                    <div class="card">
                        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                            <h5>All Teachers</h5>
                            <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addTeacherModal">
                                <i class="fas fa-plus me-1"></i> Add New
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Logo</th>
                                            <th>ID</th>
                                            <th>First Name</th>
                                            <th>Last Name</th>
                                            <th>Subject</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($teachers as $teacher): ?>
                                        <tr>
                                            <td><img src="<?php echo htmlspecialchars($teacher['logo']); ?>" class="table-img" alt="Teacher Logo"></td>
                                            <td><?php echo htmlspecialchars($teacher['identification_number']); ?></td>
                                            <td><?php echo htmlspecialchars($teacher['first_name']); ?></td>
                                            <td><?php echo htmlspecialchars($teacher['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($teacher['subject']); ?></td>
                                            <td class="action-buttons">
                                                <button class="btn btn-sm btn-info" onclick="viewTeacher(<?php echo $teacher['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <form method="post" style="display:inline;">
                                                    <input type="hidden" name="teacher_id" value="<?php echo $teacher['id']; ?>">
                                                    <button type="submit" name="delete_teacher" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this teacher?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Courses Section -->
                <div id="courses" class="content-section">
                    <h2 class="mb-4">Courses</h2>
                    <div class="card">
                        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                            <h5>All Courses</h5>
                            <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addCourseModal">
                                <i class="fas fa-plus me-1"></i> Add New
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Logo</th>
                                            <th>Name</th>
                                            <th>Description</th>
                                            <th>Start Date</th>
                                            <th>End Date</th>
                                            <th>Teacher</th>
                                            <th>Space</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($courses as $course): ?>
                                        <tr>
                                            <td><img src="<?php echo htmlspecialchars($course['logo']); ?>" class="table-img" alt="Course Logo"></td>
                                            <td><?php echo htmlspecialchars($course['name']); ?></td>
                                            <td><?php echo htmlspecialchars(substr($course['description'], 0, 50)) . '...'; ?></td>
                                            <td><?php echo htmlspecialchars($course['start_date']); ?></td>
                                            <td><?php echo htmlspecialchars($course['end_date']); ?></td>
                                            <td><?php echo htmlspecialchars($course['teacher_first_name'] . ' ' . $course['teacher_last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($course['space_name']); ?></td>
                                            <td class="action-buttons">
                                                <button class="btn btn-sm btn-info" onclick="viewCourse(<?php echo $course['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <form method="post" style="display:inline;">
                                                    <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                                    <button type="submit" name="delete_course" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this course?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Spaces Section -->
                <div id="spaces" class="content-section">
                    <h2 class="mb-4">Spaces</h2>
                    <div class="card">
                        <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                            <h5>All Spaces</h5>
                            <button class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#addSpaceModal">
                                <i class="fas fa-plus me-1"></i> Add New
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Logo</th>
                                            <th>Name</th>
                                            <th>Capacity</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($spaces as $space): ?>
                                        <tr>
                                            <td><img src="<?php echo htmlspecialchars($space['logo']); ?>" class="table-img" alt="Space Logo"></td>
                                            <td><?php echo htmlspecialchars($space['name']); ?></td>
                                            <td><?php echo htmlspecialchars($space['capacity']); ?></td>
                                            <td class="action-buttons">
                                                <button class="btn btn-sm btn-info" onclick="viewSpace(<?php echo $space['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <form method="post" style="display:inline;">
                                                    <input type="hidden" name="space_id" value="<?php echo $space['id']; ?>">
                                                    <button type="submit" name="delete_space" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this space?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Registrations Section -->
                <div id="registrations" class="content-section">
                    <h2 class="mb-4">Course Registrations</h2>
                    <div class="card">
                        <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                            <h5>All Registrations</h5>
                            <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addRegistrationModal">
                                <i class="fas fa-plus me-1"></i> Add New
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Logo</th>
                                            <th>Student</th>
                                            <th>Course</th>
                                            <th>Registration Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($registrations as $registration): ?>
                                        <tr>
                                            <td><img src="<?php echo htmlspecialchars($registration['logo'] ?? 'default_registration.png'); ?>" class="table-img" alt="Registration Logo"></td>
                                            <td><?php echo htmlspecialchars($registration['student_first_name'] . ' ' . $registration['student_last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($registration['course_name']); ?></td>
                                            <td><?php echo htmlspecialchars($registration['registration_date']); ?></td>
                                            <td class="action-buttons">
                                                <form method="post" style="display:inline;">
                                                    <input type="hidden" name="registration_id" value="<?php echo $registration['id']; ?>">
                                                    <button type="submit" name="delete_registration" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this registration?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <!-- Add Student Modal -->
    <div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addStudentModalLabel">Add New Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addStudentForm" method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="student_number" class="form-label">Student Number</label>
                            <input type="text" class="form-control" id="student_number" name="student_number" required>
                        </div>
                        <div class="mb-3">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="birthday" class="form-label">Birthday</label>
                            <input type="date" class="form-control" id="birthday" name="birthday" required>
                        </div>
                        <div class="mb-3">
                            <label for="annual_course" class="form-label">Annual Course (Year)</label>
                            <input type="number" class="form-control" id="annual_course" name="annual_course" min="1" max="12" required>
                        </div>
                        <div class="mb-3">
                            <label for="student_logo" class="form-label">Logo (PNG/JPG, max 2MB)</label>
                            <input type="file" class="form-control" id="student_logo" name="student_logo" accept="image/png,image/jpeg">
                            <img id="student_logo_preview" class="logo-preview d-none" alt="Logo Preview">
                        </div>
                        <button type="submit" name="add_student" class="btn btn-primary">Add Student</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Teacher Modal -->
    <div class="modal fade" id="addTeacherModal" tabindex="-1" aria-labelledby="addTeacherModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addTeacherModalLabel">Add New Teacher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addTeacherForm" method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="identification_number" class="form-label">Identification Number</label>
                            <input type="text" class="form-control" id="identification_number" name="identification_number" required>
                        </div>
                        <div class="mb-3">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="subject" name="subject" required>
                        </div>
                        <div class="mb-3">
                            <label for="teacher_logo" class="form-label">Logo (PNG/JPG, max 2MB)</label>
                            <input type="file" class="form-control" id="teacher_logo" name="teacher_logo" accept="image/png,image/jpeg">
                            <img id="teacher_logo_preview" class="logo-preview d-none" alt="Logo Preview">
                        </div>
                        <button type="submit" name="add_teacher" class="btn btn-success">Add Teacher</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Course Modal -->
    <div class="modal fade" id="addCourseModal" tabindex="-1" aria-labelledby="addCourseModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCourseModalLabel">Add New Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addCourseForm" method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="name" class="form-label">Course Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="teacher_id" class="form-label">Teacher</label>
                            <select class="form-control" id="teacher_id" name="teacher_id" required>
                                <option value="">Select Teacher</option>
                                <?php foreach ($teachers_options as $teacher): ?>
                                <option value="<?php echo $teacher['id']; ?>">
                                    <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="space_id" class="form-label">Space</label>
                            <select class="form-control" id="space_id" name="space_id" required>
                                <option value="">Select Space</option>
                                <?php foreach ($spaces_options as $space): ?>
                                <option value="<?php echo $space['id']; ?>">
                                    <?php echo htmlspecialchars($space['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="course_logo" class="form-label">Logo (PNG/JPG, max 2MB)</label>
                            <input type="file" class="form-control" id="course_logo" name="course_logo" accept="image/png,image/jpeg">
                            <img id="course_logo_preview" class="logo-preview d-none" alt="Logo Preview">
                        </div>
                        <button type="submit" name="add_course" class="btn btn-info">Add Course</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Space Modal -->
    <div class="modal fade" id="addSpaceModal" tabindex="-1" aria-labelledby="addSpaceModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addSpaceModalLabel">Add New Space</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addSpaceForm" method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="name" class="form-label">Space Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="capacity" class="form-label">Capacity</label>
                            <input type="number" class="form-control" id="capacity" name="capacity" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label for="space_logo" class="form-label">Logo (PNG/JPG, max 2MB)</label>
                            <input type="file" class="form-control" id="space_logo" name="space_logo" accept="image/png,image/jpeg">
                            <img id="space_logo_preview" class="logo-preview d-none" alt="Logo Preview">
                        </div>
                        <button type="submit" name="add_space" class="btn btn-warning">Add Space</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Registration Modal -->
    <div class="modal fade" id="addRegistrationModal" tabindex="-1" aria-labelledby="addRegistrationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addRegistrationModalLabel">Add New Registration</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addRegistrationForm" method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="student_id" class="form-label">Student</label>
                            <select class="form-control" id="student_id" name="student_id" required>
                                <option value="">Select Student</option>
                                <?php foreach ($students_options as $student): ?>
                                <option value="<?php echo $student['id']; ?>">
                                    <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="course_id" class="form-label">Course</label>
                            <select class="form-control" id="course_id" name="course_id" required>
                                <option value="">Select Course</option>
                                <?php foreach ($courses_options as $course): ?>
                                <option value="<?php echo $course['id']; ?>">
                                    <?php echo htmlspecialchars($course['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="registration_logo" class="form-label">Logo (PNG/JPG, max 2MB)</label>
                            <input type="file" class="form-control" id="registration_logo" name="registration_logo" accept="image/png,image/jpeg">
                            <img id="registration_logo_preview" class="logo-preview d-none" alt="Logo Preview">
                        </div>
                        <button type="submit" name="add_registration" class="btn btn-secondary">Add Registration</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- View Details Modal -->
    <div class="modal fade" id="viewDetailsModal" tabindex="-1" aria-labelledby="viewDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewDetailsModalLabel">Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="viewDetailsContent">
                    <!-- Content will be loaded via AJAX -->
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Navigation between sections
        $(document).ready(function() {
            $('.nav-link').click(function(e) {
                e.preventDefault();
                $('.content-section').hide();
                const target = $(this).attr('href');
                $(target).show();
                $('.nav-link').removeClass('active');
                $(this).addClass('active');
            });

            // Preview logo before upload
            function previewImage(input, previewId) {
                const preview = document.getElementById(previewId);
                if (input.files && input.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                        preview.classList.remove('d-none');
                    };
                    reader.readAsDataURL(input.files[0]);
                } else {
                    preview.src = '';
                    preview.classList.add('d-none');
                }
            }

            $('#student_logo').change(function() { previewImage(this, 'student_logo_preview'); });
            $('#teacher_logo').change(function() { previewImage(this, 'teacher_logo_preview'); });
            $('#course_logo').change(function() { previewImage(this, 'course_logo_preview'); });
            $('#space_logo').change(function() { previewImage(this, 'space_logo_preview'); });
            $('#registration_logo').change(function() { previewImage(this, 'registration_logo_preview'); });

            // Client-side form validation
            $('#addStudentForm, #addTeacherForm, #addCourseForm, #addSpaceForm, #addRegistrationForm').on('submit', function(e) {
                const form = $(this);
                let isValid = true;

                form.find('input[required], select[required]').each(function() {
                    if (!$(this).val()) {
                        isValid = false;
                        $(this).addClass('is-invalid');
                    } else {
                        $(this).removeClass('is-invalid');
                    }
                });

                form.find('input[type="file"]').each(function() {
                    if (this.files.length > 0) {
                        const file = this.files[0];
                        const allowedTypes = ['image/png', 'image/jpeg'];
                        const maxSize = 2 * 1024 * 1024; // 2MB
                        if (!allowedTypes.includes(file.type)) {
                            isValid = false;
                            $(this).addClass('is-invalid');
                            alert('Invalid file type. Only PNG and JPG are allowed.');
                        } else if (file.size > maxSize) {
                            isValid = false;
                            $(this).addClass('is-invalid');
                            alert('File size exceeds 2MB limit.');
                        } else {
                            $(this).removeClass('is-invalid');
                        }
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    alert('Please fill out all required fields correctly.');
                }
            });
        });

        // AJAX functions for viewing details
        function viewStudent(id) {
            $.ajax({
                url: '<?php echo $_SERVER['PHP_SELF']; ?>',
                type: 'POST',
                data: { view_student: id },
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function(response) {
                    $('#viewDetailsModalLabel').text('Student Details');
                    $('#viewDetailsContent').html(response);
                    $('#viewDetailsModal').modal('show');
                },
                error: function() {
                    alert('Error loading student details.');
                }
            });
        }

        function viewTeacher(id) {
            $.ajax({
                url: '<?php echo $_SERVER['PHP_SELF']; ?>',
                type: 'POST',
                data: { view_teacher: id },
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function(response) {
                    $('#viewDetailsModalLabel').text('Teacher Details');
                    $('#viewDetailsContent').html(response);
                    $('#viewDetailsModal').modal('show');
                },
                error: function() {
                    alert('Error loading teacher details.');
                }
            });
        }

        function viewCourse(id) {
            $.ajax({
                url: '<?php echo $_SERVER['PHP_SELF']; ?>',
                type: 'POST',
                data: { view_course: id },
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function(response) {
                    $('#viewDetailsModalLabel').text('Course Details');
                    $('#viewDetailsContent').html(response);
                    $('#viewDetailsModal').modal('show');
                },
                error: function() {
                    alert('Error loading course details.');
                }
            });
        }

        function viewSpace(id) {
            $.ajax({
                url: '<?php echo $_SERVER['PHP_SELF']; ?>',
                type: 'POST',
                data: { view_space: id },
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function(response) {
                    $('#viewDetailsModalLabel').text('Space Details');
                    $('#viewDetailsContent').html(response);
                    $('#viewDetailsModal').modal('show');
                },
                error: function() {
                    alert('Error loading space details.');
                }
            });
        }
    </script>
</body>
</html>