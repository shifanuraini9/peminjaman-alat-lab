// assets/js/script.js
document.addEventListener('DOMContentLoaded', function() {
    // Toggle Sidebar
    const menuToggle = document.querySelector('.menu-toggle');
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        });
    }
    
    // Form Validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredInputs = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredInputs.forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                    input.style.borderColor = '#F44336';
                    input.focus();
                } else {
                    input.style.borderColor = '#E0E0E0';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Harap isi semua field yang wajib diisi!');
            }
        });
    });
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
    
    // Date picker setup
    const tanggalPinjam = document.getElementById('tanggal_pinjam');
    const tanggalKembali = document.getElementById('tanggal_kembali');
    
    if (tanggalPinjam) {
        const today = new Date().toISOString().split('T')[0];
        tanggalPinjam.min = today;
        tanggalPinjam.value = today;
        
        // Set max date to 30 days from now
        const maxDate = new Date();
        maxDate.setDate(maxDate.getDate() + 30);
        tanggalPinjam.max = maxDate.toISOString().split('T')[0];
    }
    
    if (tanggalKembali) {
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        tanggalKembali.min = tomorrow.toISOString().split('T')[0];
        tanggalKembali.value = tomorrow.toISOString().split('T')[0];
        
        const maxDate = new Date();
        maxDate.setDate(maxDate.getDate() + 30);
        tanggalKembali.max = maxDate.toISOString().split('T')[0];
    }
});

// Confirmation for actions
function confirmAction(message) {
    return confirm(message || 'Apakah Anda yakin?');
}

// Format date to Indonesian format
function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID');
}