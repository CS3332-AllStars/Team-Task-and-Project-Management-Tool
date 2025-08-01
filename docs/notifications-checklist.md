# Notification System QA Checklist

## Overview
This checklist validates the notification system functionality across the application. Use this document when:
- Testing new notification features
- Verifying notification behavior after updates
- Conducting user acceptance testing

## Notification Generation Tests

### Task Assignment Notifications
- [ ] New task assignment triggers notification
  - [ ] Correct user receives notification
      (Test by assigning a task to User A while logged in as User B. 
       Verify notification appears in User A's notification center but not in User B's. 
       Check notification database record matches User A's ID. 
       Use test accounts: james_ward/password123 and summer_hill/password123)
  - [ ] Title shows "New Task Assignment"
        (Test notification title formatting:
        Exact text matches "New Task Assignment"
        Proper capitalization on all words
        No extra whitespace or special characters
        Consistent across all browsers
       Database verification:
       ```sql
       SELECT notification_title FROM notifications 
       WHERE type = 'task_assigned' 
       AND notification_id = [ID];
       ```
       UI verification:
        Title appears bold in notification center
        Truncates properly if too long
        Maintains formatting in mobile view)
  - [ ] Message includes task name
  - [ ] Links to correct task
  - [ ] Timestamp accurate
        (Test by creating notifications and verifying:
       Shows in user's timezone (check system vs displayed time)
       Creation time matches database timestamp within 1 second
       Format matches "X minutes/hours ago" for recent, "MMM DD" for older
        Hover shows full datetime tooltip
       Use test query: SELECT created_at, NOW() FROM notifications WHERE notification_id = [ID])   

### Comment Notifications
- [ ] New comment triggers notification for task participants
  - [ ] Task creator notified
  - [ ] All assigned users notified
  - [ ] Previous commenters notified
  - [ ] Message shows commenter name
  - [ ] Links to task with comment

### Status Change Notifications
- [ ] Task status changes trigger notifications
  - [ ] "To Do" → "In Progress"
  - [ ] "In Progress" → "Done"
  - [ ] All task members notified
  - [ ] Status change reflected in message

### Project Invitation Notifications
- [ ] New member invitation creates notification
  - [ ] Shows project name
  - [ ] Contains accept/decline actions
  - [ ] Only visible to invited user

### Deadline Reminders
- [ ] Automated reminders sent for approaching deadlines
  - [ ] 2 days before deadline
  - [ ] On deadline day
  - [ ] Only to assigned users
  - [ ] Contains due date information

## Notification Display Tests

### Badge Counter
- [ ] Unread count shows correctly
  - [ ] Increments with new notifications
  - [ ] Decrements when marked as read
  - [ ] Shows on navigation menu
  - [ ] Updates in real-time

### Notification Center
- [ ] List shows newest first
- [ ] Shows timestamp
- [ ] Unread items highlighted
- [ ] Maximum 50 items displayed
- [ ] Scrolling loads more items
- [ ] Clear separation between read/unread

### Read/Unread Status
- [ ] Can mark individual notifications as read
- [ ] Can mark all as read
- [ ] Read status persists after refresh
- [ ] Unread count updates immediately
- [ ] Visual distinction between states

## User Privacy Tests

### Access Control
- [ ] Users only see their own notifications
- [ ] Project notifications limited to members
- [ ] Task notifications limited to participants
- [ ] Admin notifications properly restricted
- [ ] No data leakage between users

## Cross-Browser Testing

### Desktop Browsers
- [ ] Chrome (latest)
  - [ ] Notifications appear correctly
  - [ ] Badges update properly
  - [ ] Actions work as expected
  
- [ ] Firefox (latest)
  - [ ] Notifications appear correctly
  - [ ] Badges update properly
  - [ ] Actions work as expected
  
- [ ] Safari (latest)
  - [ ] Notifications appear correctly
  - [ ] Badges update properly
  - [ ] Actions work as expected

## Performance Tests

### Load Testing
- [ ] System handles 100+ simultaneous notifications
- [ ] No delay in notification display
- [ ] Badge updates remain accurate
- [ ] Database queries optimized

### Real-time Updates
- [ ] Notifications appear without refresh
- [ ] Badge counts update instantly
- [ ] No duplicate notifications
- [ ] WebSocket connection stable

## Error Handling

### Recovery Scenarios
- [ ] Handles network disconnection
- [ ] Recovers missed notifications
- [ ] Shows error messages clearly
- [ ] Retries failed notification delivery

## Definition of Done

### Verification Steps
- [ ] All test cases executed
- [ ] Cross-browser testing complete
- [ ] No critical bugs pending
- [ ] Performance metrics acceptable
- [ ] Privacy requirements met

### Documentation
- [ ] Test results recorded
- [ ] Issues documented in GitHub
- [ ] CS3-15 features verified
- [ ] Team sign-off obtained

### Mobile Testing
- [ ] Push Notifications
  - [ ] Appear on mobile devices
  - [ ] Deep link to correct task/project
  - [ ] Respect system notification settings
  - [ ] Badge appears on app icon

### Accessibility Tests
- [ ] Screen reader compatibility
  - [ ] Notifications announced properly
  - [ ] Proper ARIA labels present
- [ ] Keyboard navigation
  - [ ] Can access notification center with keyboard
  - [ ] Can mark as read using keyboard
- [ ] Color contrast meets WCAG standards

### Rate Limiting
- [ ] Notification throttling works
  - [ ] Max 10 notifications per minute per user
  - [ ] Grouped notifications for multiple actions
  - [ ] Priority notifications bypass limits

### Test Data Cleanup
- [ ] Clear test notifications after testing
```sql
-- Cleanup test notifications
DELETE FROM notifications 
WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)
AND (user_id IN (5,6,7) OR type LIKE 'test_%');
## References
- Notification Types: `task_assigned`, `comment_added`, `task_updated`, `project_invitation`, `deadline_reminder`, `task_completed`

- Related Files: 
  - `database/schema.sql` (notifications table)
  - `database/sample_data.sql` (test notifications)
