<?php
// CS3332 AllStars Team Task & Project Management System
// CS3-17E: Frontend Component Includes - Quick Tip/Help Component

/**
 * Reusable Quick Tip Component for educational tooltips and help sections
 * 
 * @param array $tip Tip data
 * @param array $options Display options
 */
function renderQuickTip($tip, $options = []) {
    // Default options
    $defaults = [
        'type' => 'tooltip', // 'tooltip', 'popover', 'inline', 'modal'
        'variant' => 'info', // 'info', 'warning', 'success', 'danger', 'light'
        'size' => 'default', // 'small', 'default', 'large'
        'dismissible' => false,
        'showIcon' => true,
        'placement' => 'top', // For tooltips/popovers: 'top', 'bottom', 'left', 'right'
        'trigger' => 'hover', // 'hover', 'click', 'focus'
        'autoShow' => false
    ];
    $options = array_merge($defaults, $options);
    
    // Sanitize tip data
    $title = htmlspecialchars($tip['title'] ?? '');
    $content = htmlspecialchars($tip['content'] ?? '');
    $category = htmlspecialchars($tip['category'] ?? 'general');
    $icon = $tip['icon'] ?? 'bi-info-circle';
    $target = $tip['target'] ?? null; // Element to attach tooltip to
    $id = $tip['id'] ?? 'tip_' . uniqid();
    
    // Variant styling
    $variantClass = match($options['variant']) {
        'warning' => 'alert-warning text-warning-emphasis border-warning',
        'success' => 'alert-success text-success-emphasis border-success',
        'danger' => 'alert-danger text-danger-emphasis border-danger',
        'light' => 'alert-light text-body border-light',
        default => 'alert-info text-info-emphasis border-info'
    };
    
    $iconClass = match($options['variant']) {
        'warning' => 'bi-exclamation-triangle-fill text-warning',
        'success' => 'bi-check-circle-fill text-success',
        'danger' => 'bi-x-circle-fill text-danger',
        default => 'bi-info-circle-fill text-info'
    };
    
    // Size classes
    $sizeClass = match($options['size']) {
        'small' => 'tip-sm',
        'large' => 'tip-lg',
        default => ''
    };
    
    ob_start();
    
    switch ($options['type']) {
        case 'tooltip':
            ?>
            <span class="quick-tip-trigger" 
                  data-bs-toggle="tooltip" 
                  data-bs-placement="<?php echo $options['placement']; ?>" 
                  data-bs-title="<?php echo $content; ?>"
                  data-bs-custom-class="quick-tip-tooltip">
                <?php if ($options['showIcon']): ?>
                    <i class="bi <?php echo $icon; ?> text-muted"></i>
                <?php else: ?>
                    <span class="text-decoration-underline text-muted"><?php echo $title; ?></span>
                <?php endif; ?>
            </span>
            <?php
            break;
            
        case 'popover':
            ?>
            <button type="button" 
                    class="btn btn-link btn-sm p-0 quick-tip-trigger" 
                    data-bs-toggle="popover" 
                    data-bs-placement="<?php echo $options['placement']; ?>"
                    data-bs-title="<?php echo $title; ?>" 
                    data-bs-content="<?php echo $content; ?>"
                    data-bs-trigger="<?php echo $options['trigger']; ?>">
                <i class="bi <?php echo $icon; ?> text-muted"></i>
            </button>
            <?php
            break;
            
        case 'inline':
            ?>
            <div class="quick-tip alert <?php echo $variantClass; ?> <?php echo $sizeClass; ?> d-flex align-items-start" 
                 id="<?php echo $id; ?>" 
                 role="alert">
                <?php if ($options['showIcon']): ?>
                    <div class="flex-shrink-0 me-2">
                        <i class="bi <?php echo $iconClass; ?>"></i>
                    </div>
                <?php endif; ?>
                
                <div class="flex-grow-1">
                    <?php if ($title): ?>
                        <h6 class="alert-heading mb-1"><?php echo $title; ?></h6>
                    <?php endif; ?>
                    <div class="tip-content"><?php echo nl2br($content); ?></div>
                </div>
                
                <?php if ($options['dismissible']): ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                <?php endif; ?>
            </div>
            <?php
            break;
            
        case 'modal':
            ?>
            <button type="button" 
                    class="btn btn-link btn-sm p-0 quick-tip-trigger" 
                    data-bs-toggle="modal" 
                    data-bs-target="#tipModal<?php echo $id; ?>">
                <i class="bi <?php echo $icon; ?> text-muted"></i>
            </button>
            
            <!-- Modal -->
            <div class="modal fade" id="tipModal<?php echo $id; ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="bi <?php echo $iconClass; ?> me-2"></i>
                                <?php echo $title ?: 'Quick Tip'; ?>
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <?php echo nl2br($content); ?>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Got it!</button>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            break;
    }
    
    return ob_get_clean();
}

/**
 * Render a simple info tooltip
 */
function renderInfoTip($content, $placement = 'top') {
    return renderQuickTip([
        'content' => $content
    ], [
        'type' => 'tooltip',
        'placement' => $placement
    ]);
}

/**
 * Render a help popover with title and content
 */
function renderHelpPopover($title, $content, $placement = 'right') {
    return renderQuickTip([
        'title' => $title,
        'content' => $content,
        'icon' => 'bi-question-circle'
    ], [
        'type' => 'popover',
        'placement' => $placement
    ]);
}

/**
 * Render an inline warning tip
 */
function renderWarningTip($content, $dismissible = true) {
    return renderQuickTip([
        'content' => $content
    ], [
        'type' => 'inline',
        'variant' => 'warning',
        'dismissible' => $dismissible
    ]);
}

/**
 * Render an inline success tip
 */
function renderSuccessTip($content, $title = 'Success!') {
    return renderQuickTip([
        'title' => $title,
        'content' => $content
    ], [
        'type' => 'inline',
        'variant' => 'success'
    ]);
}

/**
 * Render contextual help for form fields
 */
function renderFieldHelp($content, $type = 'tooltip') {
    return renderQuickTip([
        'content' => $content,
        'icon' => 'bi-question-circle'
    ], [
        'type' => $type,
        'variant' => 'light',
        'size' => 'small'
    ]);
}

/**
 * Render a feature introduction tip
 */
function renderFeatureTip($title, $content, $category = 'feature') {
    return renderQuickTip([
        'title' => $title,
        'content' => $content,
        'category' => $category,
        'icon' => 'bi-lightbulb'
    ], [
        'type' => 'inline',
        'variant' => 'info',
        'dismissible' => true,
        'size' => 'large'
    ]);
}

/**
 * Render a collection of tips for a specific page/section
 */
function renderTipCollection($tips, $title = 'Quick Tips') {
    if (empty($tips)) return '';
    
    ob_start();
    ?>
    <div class="tip-collection card">
        <div class="card-header">
            <h6 class="card-title mb-0">
                <i class="bi bi-lightbulb me-2"></i>
                <?php echo htmlspecialchars($title); ?>
            </h6>
        </div>
        <div class="card-body">
            <?php foreach ($tips as $tip): ?>
                <div class="tip-item mb-3 last:mb-0">
                    <?php echo renderQuickTip($tip, ['type' => 'inline', 'size' => 'small']); ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Initialize tooltip and popover functionality
 */
function initQuickTips() {
    return '
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Initialize Bootstrap tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll("[data-bs-toggle=\"tooltip\"]"));
            tooltipTriggerList.forEach(function(tooltipTriggerEl) {
                new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Initialize Bootstrap popovers
            var popoverTriggerList = [].slice.call(document.querySelectorAll("[data-bs-toggle=\"popover\"]"));
            popoverTriggerList.forEach(function(popoverTriggerEl) {
                new bootstrap.Popover(popoverTriggerEl);
            });
        });
    </script>';
}
?>