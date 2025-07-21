<?php
// CS3332 AllStars Team Task & Project Management System
// CS3-17: Frontend UI Framework - Layout Footer Component

$isLoggedIn = isset($_SESSION['user_id']);
?>

        </div> <!-- End main container -->
    </div> <!-- End main wrapper -->
    
    <!-- Footer -->
    <footer class="footer mt-auto py-3 bg-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <span class="text-muted">
                        &copy; <?php echo date('Y'); ?> CS3332 AllStars Team Task & Project Management System
                    </span>
                </div>
                <div class="col-md-6 text-md-end">
                    <small class="text-muted">
                        Built with <i class="bi bi-heart-fill text-danger"></i> by CS3332 AllStars
                    </small>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap 5.3 JavaScript Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery 3.7 -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <!-- Core JavaScript Modules -->
    <script src="assets/js/api.js"></script>
    <script src="assets/js/toast.js"></script>
    <script src="assets/js/tooltips.js"></script>
    
    <!-- Additional JavaScript -->
    <?php if (isset($additionalJS)): ?>
        <?php foreach ($additionalJS as $js): ?>
            <script src="<?php echo htmlspecialchars($js); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Inline JavaScript -->
    <?php if (isset($inlineJS)): ?>
        <script>
            <?php echo $inlineJS; ?>
        </script>
    <?php endif; ?>
    
    <!-- Initialize Core Modules -->
    <script>
        // Initialize core frontend modules
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize toast system
            if (typeof ToastManager !== 'undefined') {
                window.toastManager = new ToastManager();
                
                // Create global helper functions
                window.toastSuccess = (message) => window.toastManager.success(message);
                window.toastError = (message) => window.toastManager.error(message);
                window.toastWarning = (message) => window.toastManager.warning(message);
                window.toastInfo = (message) => window.toastManager.info(message);
            }
            
            // Initialize tooltip system
            if (typeof TooltipManager !== 'undefined') {
                window.tooltipManager = new TooltipManager();
            }
            
            // Initialize API manager
            if (typeof APIManager !== 'undefined') {
                window.api = new APIManager();
            }
            
            <?php if ($isLoggedIn): ?>
            // Initialize role-based UI features for logged-in users
            if (typeof window.initRoleBasedUI === 'function') {
                window.initRoleBasedUI('<?php echo htmlspecialchars($_SESSION['role'] ?? 'user'); ?>');
            }
            <?php endif; ?>
        });
    </script>
</body>
</html>