<?php
// CS3332 AllStars Team Task & Project Management System
// CS3-17: Frontend UI Framework - Base Page Template

/**
 * Base page template that provides consistent layout structure
 * 
 * Usage:
 * $templateData = [
 *     'pageTitle' => 'Page Title',
 *     'pageDescription' => 'Page description for SEO',
 *     'additionalCSS' => ['assets/css/custom.css'],
 *     'additionalJS' => ['assets/js/custom.js'],
 *     'hideNavigation' => false,
 *     'contentFile' => 'path/to/content.php',
 *     'inlineJS' => 'console.log("Custom JS");'
 * ];
 * renderTemplate($templateData);
 */

function renderTemplate($data = []) {
    // Extract template data
    $pageTitle = $data['pageTitle'] ?? 'Team Task & Project Management';
    $pageDescription = $data['pageDescription'] ?? 'Collaborative project management system';
    $additionalCSS = $data['additionalCSS'] ?? [];
    $additionalJS = $data['additionalJS'] ?? [];
    $hideNavigation = $data['hideNavigation'] ?? false;
    $contentFile = $data['contentFile'] ?? null;
    $inlineJS = $data['inlineJS'] ?? '';
    
    // Include header
    include __DIR__ . '/../../includes/layouts/header.php';
    
    // Include main content
    if ($contentFile && file_exists($contentFile)) {
        include $contentFile;
    } else {
        // Default content container
        echo '<div class="row"><div class="col-12">';
        if (isset($data['content'])) {
            echo $data['content'];
        } else {
            echo '<h1>Page Content</h1>';
            echo '<p>No content specified for this page.</p>';
        }
        echo '</div></div>';
    }
    
    // Include footer
    include __DIR__ . '/../../includes/layouts/footer.php';
}

/**
 * Render a simple page with basic content
 */
function renderSimplePage($title, $content, $options = []) {
    $templateData = array_merge([
        'pageTitle' => $title,
        'content' => $content
    ], $options);
    
    renderTemplate($templateData);
}

/**
 * Render an error page
 */
function renderErrorPage($errorCode, $errorMessage, $details = '') {
    $content = '
        <div class="row justify-content-center">
            <div class="col-md-6 text-center">
                <div class="error-container p-5">
                    <h1 class="display-1 text-danger">' . htmlspecialchars($errorCode) . '</h1>
                    <h2 class="mb-3">' . htmlspecialchars($errorMessage) . '</h2>
                    ' . ($details ? '<p class="text-muted">' . htmlspecialchars($details) . '</p>' : '') . '
                    <a href="dashboard.php" class="btn btn-primary">
                        <i class="bi bi-house"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    ';
    
    renderTemplate([
        'pageTitle' => $errorCode . ' - ' . $errorMessage,
        'content' => $content,
        'hideNavigation' => false
    ]);
}

/**
 * Render a loading page with optional redirect
 */
function renderLoadingPage($message = 'Loading...', $redirectUrl = null, $redirectDelay = 2000) {
    $content = '
        <div class="row justify-content-center">
            <div class="col-md-6 text-center">
                <div class="loading-container p-5">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h3>' . htmlspecialchars($message) . '</h3>
                </div>
            </div>
        </div>
    ';
    
    $inlineJS = '';
    if ($redirectUrl) {
        $inlineJS = 'setTimeout(() => { window.location.href = "' . htmlspecialchars($redirectUrl) . '"; }, ' . $redirectDelay . ');';
    }
    
    renderTemplate([
        'pageTitle' => 'Loading...',
        'content' => $content,
        'inlineJS' => $inlineJS
    ]);
}
?>