# Email Notification Setup Guide

## Current Issue
Your scheduled deployments are not sending email notifications because the mail driver is set to 'log' instead of a real email service.

## Quick Fix Options

### Option 1: Using Gmail SMTP (Recommended for testing)
Update your `.env` file with these settings:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-gmail@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-gmail@gmail.com
MAIL_FROM_NAME="Deployment Management System"
```

**Important:** For Gmail, you need to:
1. Enable 2-factor authentication
2. Generate an "App Password" (not your regular password)
3. Use the app password in MAIL_PASSWORD

### Option 2: Using Mailtrap (For testing)
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-mailtrap-username
MAIL_PASSWORD=your-mailtrap-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourcompany.com
MAIL_FROM_NAME="Deployment Management System"
```

### Option 3: Using SendGrid
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your-sendgrid-api-key
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourcompany.com
MAIL_FROM_NAME="Deployment Management System"
```

## After Configuration

1. Clear the config cache:
   ```bash
   php artisan config:clear
   ```

2. Test email sending:
   ```bash
   php artisan tinker
   ```
   Then run:
   ```php
   Mail::raw('Test email', function ($message) {
       $message->to('your-email@example.com')->subject('Test');
   });
   ```

## Verification
- Check your email inbox for notifications
- Check `storage/logs/laravel.log` for any email sending errors
- Scheduled deployments should now send email notifications to all admins and developers

## Current Notification Recipients
The system sends notifications to:
- The user who triggered the deployment
- All users with 'admin' role
- All users with 'developer' role
