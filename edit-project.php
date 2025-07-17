<?php
// CS3332 AllStars Team Task & Project Management System
// Edit Project Details - FR-11

require_once 'includes/session-check.php';

// Database connection
$host = 'localhost';
$dbname = 'ttpm_system';
$username = 'root';
$password = '';

$mysqli = new mysqli($host, $username, $password, $dbname);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Get project ID
$project_id = (int)($_GET['id'] ?? 0);
if (!$project_id) {
    header('Location: dashboard.php');
    exit;
}

// Check if user is admin of this project
$stmt = $mysqli->prepare("SELECT role FROM project_memberships WHERE user_id = ? AND project_id = ?");
$stmt->bind_param("ii", $_SESSION['user_id'], $project_id);
$stmt->execute();
$result = $stmt->get_result();
$membership = $result->fetch_assoc();
$stmt->close();

if (!$membership || $membership['role'] !== 'admin') {
    echo "Access denied: Only project administrators can edit project details.";
    exit;
}

// Get project details
$stmt = $mysqli->prepare("SELECT * FROM projects WHERE project_id = ?");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();
$project = $result->fetch_assoc();
$stmt->close();

if (!$project) {
    echo "Project not found.";
    exit;
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    // Validation
    if (empty($title)) {
        $error = 'Project title is required.';
    } elseif (strlen($title) > 255) {
        $error = 'Project title must be less than 255 characters.';
    } elseif (strlen($description) > 1000) {
        $error = 'Project description must be less than 1000 characters.';
    } else {
        // Update project
        $stmt = $mysqli->prepare("UPDATE projects SET title = ?, description = ?, updated_at = NOW() WHERE project_id = ?");
        $stmt->bind_param("ssi", $title, $description, $project_id);
        
        if ($stmt->execute()) {
            $success = 'Project updated successfully!';
            // Update local project data
            $project['title'] = $title;
            $project['description'] = $description;
        } else {
            $error = 'Failed to update project: ' . $mysqli->error;
        }
        $stmt->close();
    }
}

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Project - <?php echo htmlspecialchars($project['title']); ?></title>
    <link rel="stylesheet" href="assets/css/project.css">
</head>
<body>
    <div class="container small">
        <a href="project.php?id=<?php echo $project_id; ?>" class="back-link">← Back to Project</a>
        
        <div class="header center">
            <h1>Edit Project</h1>
            <p>Update project details</p>
        </div>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="title">Project Title *</label>
                <input type="text" id="title" name="title" required maxlength="255" 
                       value="<?php echo htmlspecialchars($project['title']); ?>"
                       placeholder="Enter project title">
            </div>

            <div class="form-group">
                <label for="description">Project Description</label>
                <textarea id="description" name="description" maxlength="1000" 
                          placeholder="Describe your project"><?php echo htmlspecialchars($project['description'] ?? ''); ?></textarea>
            </div>

            <div class="text-center mt-2">
                <button type="submit" class="btn btn-primary large">Update Project</button>
                <a href="project.php?id=<?php echo $project_id; ?>" class="btn btn-secondary large">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>