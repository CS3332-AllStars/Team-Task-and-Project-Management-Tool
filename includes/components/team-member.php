<?php
// CS3332 AllStars Team Task & Project Management System
// CS3-17E: Frontend Component Includes - Team Member Component

/**
 * Process variable injection in strings using {{variable}} syntax
 */
if (!function_exists('injectVariables')) {
    function injectVariables($content, $variables = []) {
        if (empty($variables) || !is_string($content)) {
            return $content;
        }
        
        foreach ($variables as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $subKey => $subValue) {
                    $placeholder = '{{' . $key . '.' . $subKey . '}}';
                    $content = str_replace($placeholder, htmlspecialchars((string)$subValue), $content);
                }
            } else {
                $placeholder = '{{' . $key . '}}';
                $content = str_replace($placeholder, htmlspecialchars((string)$value), $content);
            }
        }
        
        return $content;
    }
}

/**
 * Reusable Team Member Component with Variable Injection
 * 
 * @param array $member Member data
 * @param array $options Display options
 * @param array $variables Variables for injection
 */
function renderTeamMember($member, $options = [], $variables = []) {
    $defaults = [
        'size' => 'default', // 'small', 'default', 'large'
        'showRole' => true,
        'showEmail' => true,
        'showJoinDate' => true,
        'showActions' => false,
        'showAvatar' => true,
        'layout' => 'horizontal', // 'horizontal', 'vertical'
        'clickable' => false,
        'href' => '#',
        'template' => null,
        'customFields' => []
    ];
    $options = array_merge($defaults, $options);
    
    // Sanitize member data
    $userId = (int) ($member['user_id'] ?? 0);
    $username = htmlspecialchars($member['username'] ?? 'Unknown');
    $fullName = htmlspecialchars($member['name'] ?? $member['full_name'] ?? $username);
    $email = htmlspecialchars($member['email'] ?? '');
    $role = htmlspecialchars($member['role'] ?? 'member');
    $joinDate = $member['joined_at'] ?? $member['join_date'] ?? null;
    $avatarUrl = $member['avatar_url'] ?? null;
    $isOnline = $member['is_online'] ?? false;
    $lastActive = $member['last_active'] ?? null;
    
    // Role styling
    $roleClass = match($role) {
        'admin' => 'bg-danger text-white',
        'moderator' => 'bg-warning text-dark',
        'lead' => 'bg-info text-white',
        default => 'bg-secondary text-white'
    };
    
    // Size classes
    $sizeClass = match($options['size']) {
        'small' => 'member-card-sm',
        'large' => 'member-card-lg',
        default => ''
    };
    
    // Layout classes
    $layoutClass = $options['layout'] === 'vertical' ? 'member-card-vertical text-center' : 'member-card-horizontal';
    
    // Generate avatar
    $avatarSrc = $avatarUrl ?: generateAvatar($fullName);
    
    // Online status
    $onlineIndicator = $isOnline ? '<span class="online-indicator bg-success"></span>' : '';
    
    // Clickable wrapper
    $wrapperStart = $options['clickable'] ? '<a href="' . htmlspecialchars($options['href']) . '" class="text-decoration-none">' : '';
    $wrapperEnd = $options['clickable'] ? '</a>' : '';
    $hoverClass = $options['clickable'] ? 'member-card-clickable' : '';
    
    $injectionVars = array_merge([
        'member' => [
            'id' => $userId,
            'username' => $username,
            'full_name' => $fullName,
            'email' => $email,
            'role' => $role,
            'avatar_url' => $avatarSrc,
            'is_online' => $isOnline,
            'join_date' => $joinDate ? date('M j, Y', strtotime($joinDate)) : '',
            'last_active' => $lastActive ? timeAgo($lastActive) : ''
        ],
        'role_class' => $roleClass,
        'layout' => $options['layout'],
        'size' => $options['size'],
        'online_indicator' => $onlineIndicator
    ], $variables, $options['customFields']);
    
    // Use custom template if provided
    if ($options['template']) {
        return injectVariables($options['template'], $injectionVars);
    }
    
    ob_start();
    ?>
    
    <?php echo $wrapperStart; ?>
    <div class="card member-card <?php echo $sizeClass; ?> <?php echo $layoutClass; ?> <?php echo $hoverClass; ?> h-100">
        <div class="card-body">
            <div class="<?php echo $options['layout'] === 'vertical' ? 'd-block' : 'd-flex align-items-center'; ?>">
                
                <?php if ($options['showAvatar']): ?>
                    <div class="member-avatar-wrapper position-relative <?php echo $options['layout'] === 'vertical' ? 'mb-3' : 'me-3'; ?> flex-shrink-0">
                        <img src="<?php echo htmlspecialchars($avatarSrc); ?>" 
                             alt="<?php echo $fullName; ?>" 
                             class="member-avatar rounded-circle"
                             style="width: <?php echo $options['size'] === 'large' ? '64px' : ($options['size'] === 'small' ? '32px' : '48px'); ?>; height: <?php echo $options['size'] === 'large' ? '64px' : ($options['size'] === 'small' ? '32px' : '48px'); ?>;">
                        <?php echo $onlineIndicator; ?>
                    </div>
                <?php endif; ?>
                
                <div class="member-info flex-grow-1">
                    <div class="member-name-role mb-1">
                        <h6 class="member-name mb-0"><?php echo $fullName; ?></h6>
                        <?php if ($username !== $fullName): ?>
                            <small class="text-muted">@<?php echo $username; ?></small>
                        <?php endif; ?>
                        
                        <?php if ($options['showRole']): ?>
                            <span class="badge <?php echo $roleClass; ?> ms-2"><?php echo ucfirst($role); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($options['showEmail'] && $email): ?>
                        <div class="member-email mb-1">
                            <small class="text-muted">
                                <i class="bi bi-envelope"></i> <?php echo $email; ?>
                            </small>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($options['showJoinDate'] && $joinDate): ?>
                        <div class="member-join-date mb-1">
                            <small class="text-muted">
                                <i class="bi bi-calendar-plus"></i> 
                                Joined <?php echo date('M j, Y', strtotime($joinDate)); ?>
                            </small>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($lastActive): ?>
                        <div class="member-activity">
                            <small class="text-muted">
                                <i class="bi bi-clock"></i>
                                <?php if ($isOnline): ?>
                                    Online now
                                <?php else: ?>
                                    Last active <?php echo timeAgo($lastActive); ?>
                                <?php endif; ?>
                            </small>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($options['showActions']): ?>
                    <div class="member-actions <?php echo $options['layout'] === 'vertical' ? 'mt-3' : 'ms-2'; ?>">
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" 
                                    data-user-id="<?php echo $userId; ?>" 
                                    data-tooltip="Send message">
                                <i class="bi bi-chat"></i>
                            </button>
                            <button class="btn btn-outline-info" 
                                    data-user-id="<?php echo $userId; ?>" 
                                    data-tooltip="View profile">
                                <i class="bi bi-person"></i>
                            </button>
                            <?php if ($_SESSION['role'] === 'admin' && $role !== 'admin'): ?>
                                <button class="btn btn-outline-danger remove-member-btn" 
                                        data-user-id="<?php echo $userId; ?>" 
                                        data-tooltip="Remove from project">
                                    <i class="bi bi-person-dash"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php echo $wrapperEnd; ?>
    
    <?php
    $output = ob_get_clean();
    
    return injectVariables($output, $injectionVars);
}

/**
 * Render a simple member badge for lists
 */
function renderMemberBadge($member) {
    $fullName = htmlspecialchars($member['name'] ?? $member['username'] ?? 'Unknown');
    $role = htmlspecialchars($member['role'] ?? 'member');
    $avatarSrc = $member['avatar_url'] ?? generateAvatar($fullName);
    
    $roleClass = match($role) {
        'admin' => 'border-danger',
        'moderator' => 'border-warning',
        'lead' => 'border-info',
        default => 'border-secondary'
    };
    
    return '<span class="member-badge d-inline-flex align-items-center me-2 mb-1">
                <img src="' . htmlspecialchars($avatarSrc) . '" 
                     alt="' . $fullName . '" 
                     class="rounded-circle me-1 ' . $roleClass . '" 
                     style="width: 24px; height: 24px; border-width: 2px;">
                <small>' . $fullName . '</small>
            </span>';
}

/**
 * Render member list for dropdowns
 */
function renderMemberDropdownList($members, $selectedIds = []) {
    $html = '';
    foreach ($members as $member) {
        $userId = (int) $member['user_id'];
        $fullName = htmlspecialchars($member['name'] ?? $member['username'] ?? 'Unknown');
        $role = htmlspecialchars($member['role'] ?? 'member');
        $checked = in_array($userId, $selectedIds) ? 'checked' : '';
        
        $html .= '<div class="form-check">
                    <input class="form-check-input" type="checkbox" 
                           name="assignees[]" value="' . $userId . '" 
                           id="member_' . $userId . '" ' . $checked . '>
                    <label class="form-check-label d-flex align-items-center" for="member_' . $userId . '">
                        ' . renderMemberBadge($member) . '
                        <span class="badge bg-secondary ms-2">' . ucfirst($role) . '</span>
                    </label>
                  </div>';
    }
    return $html;
}

/**
 * Generate avatar URL from name initials
 */
function generateAvatar($name, $size = 64) {
    $initials = '';
    $words = explode(' ', trim($name));
    foreach ($words as $word) {
        if (!empty($word)) {
            $initials .= strtoupper($word[0]);
        }
        if (strlen($initials) >= 2) break;
    }
    
    $bgColor = substr(md5($name), 0, 6);
    return "https://ui-avatars.com/api/?name=" . urlencode($initials) . "&size={$size}&background={$bgColor}&color=ffffff&bold=true";
}

/**
 * Helper function for time ago display
 */
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' mins ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    
    return date('M j, Y', strtotime($datetime));
}
?>