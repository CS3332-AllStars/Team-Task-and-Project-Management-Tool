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
  - [ ] Message includes task name
  - [ ] Links to correct task
  - [ ] Timestamp accurate

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

## References
- Notification Types: `task_assigned`, `comment_added`, `task_updated`, `project_invitation`, `deadline_reminder`, `task_completed`
- Related Files: 
  - `database/schema.sql` (notifications table)
  - `database/sample_data.sql` (test notifications)