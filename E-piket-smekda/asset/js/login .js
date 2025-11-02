// Role Tab Selection
const roleTabs = document.querySelectorAll('.role-tab');
const roleInput = document.getElementById('role');

roleTabs.forEach(tab => {
    tab.addEventListener('click', function() {
        // Remove active class from all tabs
        roleTabs.forEach(t => t.classList.remove('active'));
        
        // Add active class to clicked tab
        this.classList.add('active');
        
        // Update hidden input value
        roleInput.value = this.dataset.role;
    });
});

// Auto hide alerts after 5 seconds
setTimeout(() => {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        alert.style.transition = 'opacity 0.5s';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 500);
    });
}, 5000);