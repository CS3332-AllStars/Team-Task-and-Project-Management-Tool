<?php
// CS3332 AllStars Team Task & Project Management System
// Main Landing Page - Professional User Interface

session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Task & Project Management System</title>
    
    <!-- External Stylesheets -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/forms.css">
</head>
<body class="landing-page">
    <!-- Hero Section -->
    <div class="hero-section">
        <h1 class="hero-title">Team Task Management</h1>
        <p class="hero-subtitle">
            Streamline your team's workflow with intuitive project management designed for learning and collaboration
        </p>
        
        <?php if (isset($_SESSION['user_id'])): ?>
            <!-- Welcome Back Message -->
            <div class="user-welcome">
                <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>! 👋</h2>
                <p>Ready to tackle your projects?</p>
                <a href="dashboard.php" class="dashboard-btn">Go to Dashboard</a>
                <br><br>
                <a href="logout.php" style="color: rgba(255,255,255,0.8); text-decoration: none;">Logout</a>
            </div>
        <?php else: ?>
            <!-- Call to Action Buttons -->
            <div class="cta-buttons">
                <a href="register.php" class="btn-hero btn-primary-hero">Get Started Free</a>
                <a href="login.php" class="btn-hero btn-secondary-hero">Sign In</a>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Features Section -->
    <div class="features-section">
        <div class="features-container">
            <div style="text-align: center; margin-bottom: 20px;">
                <h2 style="font-size: 2.5rem; color: #2c3e50; margin-bottom: 20px;">Why Choose Our Platform?</h2>
                <p style="font-size: 1.2rem; color: #6c757d; max-width: 600px; margin: 0 auto;">
                    Built for teams who want to learn project management while getting work done effectively
                </p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card">
                    <span class="feature-icon">📋</span>
                    <h3 class="feature-title">Task Management</h3>
                    <p class="feature-description">
                        Create, assign, and track tasks with status updates. Multi-user assignments and deadline tracking keep your team organized.
                    </p>
                </div>
                
                <div class="feature-card">
                    <span class="feature-icon">👥</span>
                    <h3 class="feature-title">Team Collaboration</h3>
                    <p class="feature-description">
                        Real-time comments, notifications, and activity feeds. Every team member stays informed and engaged.
                    </p>
                </div>
                
                <div class="feature-card">
                    <span class="feature-icon">📊</span>
                    <h3 class="feature-title">Project Insights</h3>
                    <p class="feature-description">
                        Visual progress tracking and team activity overview. See how your projects advance in real-time.
                    </p>
                </div>
                
                <div class="feature-card">
                    <span class="feature-icon">🎓</span>
                    <h3 class="feature-title">Learning Focused</h3>
                    <p class="feature-description">
                        Built-in guidance and best practices help teams learn project management concepts while working.
                    </p>
                </div>
                
                <div class="feature-card">
                    <span class="feature-icon">🔒</span>
                    <h3 class="feature-title">Secure & Reliable</h3>
                    <p class="feature-description">
                        Enterprise-grade security with role-based access control. Your team's data is protected and private.
                    </p>
                </div>
                
                <div class="feature-card">
                    <span class="feature-icon">⚡</span>
                    <h3 class="feature-title">Easy to Use</h3>
                    <p class="feature-description">
                        Intuitive interface designed for teams new to project management tools. Get productive immediately.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Development Access (Remove in production) -->
    <?php if (isset($_GET['dev'])): ?>
    <div style="position: fixed; bottom: 20px; right: 20px; background: rgba(0,0,0,0.8); color: white; padding: 10px; border-radius: 4px; font-size: 0.8rem;">
        <a href="dev-status.php?dev=status" style="color: #4CAF50;">Dev Status</a> | 
        <a href="index_backup.php" style="color: #ffa500;">Original</a>
    </div>
    <?php endif; ?>
</body>
</html>
