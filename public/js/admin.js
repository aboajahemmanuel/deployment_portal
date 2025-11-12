/**
 * Admin JavaScript functions
 * Global functions for admin panel
 */

document.addEventListener('DOMContentLoaded', function() {
    // Add confirmation dialog to delete buttons
    const deleteButtons = document.querySelectorAll('[data-confirm-delete]');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const form = this.closest('form');
            const message = this.getAttribute('data-confirm-message') || 'Are you sure you want to delete this item? This action cannot be undone.';
            
            console.log('Delete confirmation dialog triggered', { form, message });
            confirmAction(message, function() {
                console.log('Delete confirmed, submitting form');
                form.submit();
            });
        });
    });
    
    // Add confirmation dialog to promote author buttons
    const promoteButtons = document.querySelectorAll('[data-confirm-promote]');
    
    promoteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const form = this.closest('form');
            const message = this.getAttribute('data-confirm-message') || 'Are you sure you want to promote this user to author?';
            
            console.log('Promote confirmation dialog triggered', { form, message });
            confirmAction(message, function() {
                console.log('Promote confirmed, submitting form');
                form.submit();
            });
        });
    });
    
    // Add confirmation dialog to reset password buttons
    const resetPasswordButtons = document.querySelectorAll('[data-confirm-reset]');
    
    resetPasswordButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const form = this.closest('form');
            const message = this.getAttribute('data-confirm-message') || 'Are you sure you want to reset this user\'s password?';
            
            console.log('Reset password confirmation dialog triggered', { form, message });
            confirmAction(message, function() {
                console.log('Reset password confirmed, submitting form');
                form.submit();
            });
        });
    });
});