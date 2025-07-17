<?php
// CS3332 AllStars Team Task & Project Management System
// Create New Project - FR-8

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
        // Begin transaction
        $mysqli->begin_transaction();
        
        try {
            // Insert project
            $stmt = $mysqli->prepare("INSERT INTO projects (title, description) VALUES (?, ?)");
            $stmt->bind_param("ss", $title, $description);
            $stmt->execute();
            
            $project_id = $mysqli->insert_id;
            $stmt->close();
            
            // Add creator as admin
            $stmt = $mysqli->prepare("INSERT INTO project_memberships (user_id, project_id, role) VALUES (?, ?, 'admin')");
            $stmt->bind_param("ii", $_SESSION['user_id'], $project_id);
            $stmt->execute();
            $stmt->close();
            
            // Commit transaction
            $mysqli->commit();
            
            // Redirect to project view
            header("Location: project.php?id=" . $project_id);
            exit;
            
        } catch (Exception $e) {
            $mysqli->rollback();
            $error = 'Failed to create project: ' . $e->getMessage();
        }
    }
}

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Project - TTPM</title>
    <link rel="stylesheet" href="assets/css/project.css">
</head>
<body>
    <div class="container small">
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
        
        <div class="header center">
            <h1>Create New Project</h1>
            <p>Start a new project and invite your team</p>
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
                       value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                       placeholder="Enter project title">
            </div>

            <div class="form-group">
                <label for="description">Project Description</label>
                <textarea id="description" name="description" maxlength="1000" 
                          placeholder="Describe your project (optional)"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
            </div>

            <div class="text-center mt-2">
                <button type="submit" class="btn btn-primary large">Create Project</button>
                <a href="dashboard.php" class="btn btn-secondary large">Cancel</a>
            </div>
        </form>

        <div class="text-center mt-2 pt-2 border-top text-muted">
            <p><small>You will automatically become the project administrator</small></p>
        </div>
    </div>
</body>
</html>