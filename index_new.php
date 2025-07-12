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
    
    <style>
        /* Landing page specific styles */
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 0;
        }
        
        .hero-section {
            text-align: center;
            padding: 80px 20px 60px;
            color: white;
        }
        
        .hero-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .hero-subtitle {
            font-size: 1.3rem;
            opacity: 0.9;
            margin-bottom: 40px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.6;
        }
        
        .cta-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 60px;
        }
        
        .btn-hero {
            padding: 16px 32px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
            min-width: 180px;
            text-align: center;
        }
        
        .btn-primary-hero {
            background: white;
            color: #667eea;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .btn-primary-hero:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        }
        
        .btn-secondary-hero {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 2px solid rgba(255,255,255,0.3);
            backdrop-filter: blur(10px);
        }
        
        .btn-secondary-hero:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }
        
        .features-section {
            background: white;
            padding: 80px 20px;
        }
        
        .features-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
            margin-top: 60px;
        }
        
        .feature-card {
            background: #f8f9fa;
            padding: 40px 30px;
            border-radius: 12px;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid #e9ecef;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            display: block;
        }
        
        .feature-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .feature-description {
            color: #6c757d;
            line-height: 1.6;
        }
        
        .user-welcome {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 30px;
            margin: 40px auto;
            max-width: 500px;
            text-align: center;
            color: white;
        }
        
        .user-welcome h2 {
            margin-bottom: 15px;
            color: white;
        }
        
        .dashboard-btn {
            background: white;
            color: #667eea;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 20px;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .dashboard-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-subtitle {
                font-size: 1.1rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
                gap: 30px;
            }
        }
    </style>
</head>
<body>
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
</body>
</html>
