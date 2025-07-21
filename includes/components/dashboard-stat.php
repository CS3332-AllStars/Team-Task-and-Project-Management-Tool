<?php
// CS3332 AllStars Team Task & Project Management System
// CS3-17E: Frontend Component Includes - Dashboard Statistics Component

/**
 * Reusable Dashboard Statistics Card Component
 * 
 * @param array $stat Statistics data
 * @param array $options Display options
 */
function renderDashboardStat($stat, $options = []) {
    // Default options
    $defaults = [
        'size' => 'default', // 'small', 'default', 'large'
        'variant' => 'primary', // 'primary', 'success', 'warning', 'danger', 'info'
        'showIcon' => true,
        'showTrend' => false,
        'animated' => false,
        'clickable' => false,
        'href' => '#'
    ];
    $options = array_merge($defaults, $options);
    
    // Sanitize stat data
    $title = htmlspecialchars($stat['title'] ?? 'Statistic');
    $value = $stat['value'] ?? 0;
    $subtitle = htmlspecialchars($stat['subtitle'] ?? '');
    $icon = $stat['icon'] ?? 'bi-graph-up';
    $trend = $stat['trend'] ?? null; // 'up', 'down', 'stable'
    $trendValue = $stat['trend_value'] ?? '';
    $description = htmlspecialchars($stat['description'] ?? '');
    
    // Variant styling
    $variantClass = match($options['variant']) {
        'success' => 'bg-success',
        'warning' => 'bg-warning',
        'danger' => 'bg-danger',
        'info' => 'bg-info',
        'secondary' => 'bg-secondary',
        default => 'bg-primary'
    };
    
    // Size classes
    $sizeClass = match($options['size']) {
        'small' => 'stat-card-sm',
        'large' => 'stat-card-lg',
        default => ''
    };
    
    // Trend styling
    $trendClass = '';
    $trendIcon = '';
    if ($trend) {
        $trendClass = match($trend) {
            'up' => 'text-success',
            'down' => 'text-danger',
            default => 'text-muted'
        };
        $trendIcon = match($trend) {
            'up' => 'bi-arrow-up',
            'down' => 'bi-arrow-down',
            default => 'bi-dash'
        };
    }
    
    // Animation
    $animationClass = $options['animated'] ? 'animate-fade-in' : '';
    
    // Clickable wrapper
    $wrapperStart = $options['clickable'] ? '<a href="' . htmlspecialchars($options['href']) . '" class="text-decoration-none">' : '';
    $wrapperEnd = $options['clickable'] ? '</a>' : '';
    $hoverClass = $options['clickable'] ? 'stat-card-clickable' : '';
    
    ob_start();
    ?>
    
    <?php echo $wrapperStart; ?>
    <div class="card stat-card <?php echo $sizeClass; ?> <?php echo $animationClass; ?> <?php echo $hoverClass; ?> h-100">
        <div class="card-body d-flex align-items-center">
            <?php if ($options['showIcon']): ?>
                <div class="stat-icon-wrapper flex-shrink-0 me-3">
                    <div class="stat-icon rounded-circle d-flex align-items-center justify-content-center <?php echo $variantClass; ?> text-white">
                        <i class="bi <?php echo $icon; ?> fs-4"></i>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="stat-content flex-grow-1">
                <div class="stat-value-wrapper d-flex align-items-center justify-content-between">
                    <div>
                        <h3 class="stat-value mb-0"><?php echo number_format($value); ?></h3>
                        <p class="stat-title text-muted mb-0"><?php echo $title; ?></p>
                    </div>
                    
                    <?php if ($options['showTrend'] && $trend): ?>
                        <div class="stat-trend text-end">
                            <span class="trend-value <?php echo $trendClass; ?>">
                                <i class="bi <?php echo $trendIcon; ?>"></i>
                                <?php echo $trendValue; ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($subtitle): ?>
                    <small class="stat-subtitle text-muted"><?php echo $subtitle; ?></small>
                <?php endif; ?>
                
                <?php if ($description): ?>
                    <p class="stat-description text-muted small mt-1 mb-0"><?php echo $description; ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php echo $wrapperEnd; ?>
    
    <?php
    return ob_get_clean();
}

/**
 * Render a simple numeric statistic
 */
function renderSimpleStat($title, $value, $icon = 'bi-graph-up', $variant = 'primary') {
    return renderDashboardStat([
        'title' => $title,
        'value' => $value,
        'icon' => $icon
    ], ['variant' => $variant]);
}

/**
 * Render a percentage statistic with progress bar
 */
function renderProgressStat($title, $value, $total, $options = []) {
    $percentage = $total > 0 ? round(($value / $total) * 100) : 0;
    
    $progressBarClass = match(true) {
        $percentage >= 80 => 'bg-success',
        $percentage >= 60 => 'bg-info',
        $percentage >= 40 => 'bg-warning',
        default => 'bg-danger'
    };
    
    ob_start();
    ?>
    
    <div class="card stat-card h-100">
        <div class="card-body">
            <h6 class="card-title text-muted"><?php echo htmlspecialchars($title); ?></h6>
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h3 class="mb-0"><?php echo $percentage; ?>%</h3>
                <small class="text-muted"><?php echo number_format($value); ?> / <?php echo number_format($total); ?></small>
            </div>
            <div class="progress" style="height: 8px;">
                <div class="progress-bar <?php echo $progressBarClass; ?>" 
                     role="progressbar" 
                     style="width: <?php echo $percentage; ?>%"
                     aria-valuenow="<?php echo $percentage; ?>" 
                     aria-valuemin="0" 
                     aria-valuemax="100">
                </div>
            </div>
        </div>
    </div>
    
    <?php
    return ob_get_clean();
}

/**
 * Render a trend statistic with comparison
 */
function renderTrendStat($title, $currentValue, $previousValue, $icon = 'bi-graph-up') {
    $difference = $currentValue - $previousValue;
    $trend = $difference > 0 ? 'up' : ($difference < 0 ? 'down' : 'stable');
    $percentage = $previousValue > 0 ? abs(round(($difference / $previousValue) * 100)) : 0;
    
    return renderDashboardStat([
        'title' => $title,
        'value' => $currentValue,
        'icon' => $icon,
        'trend' => $trend,
        'trend_value' => $percentage . '%'
    ], ['showTrend' => true]);
}
?>