// Confirmation dialog for delete operations
function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this item? This action cannot be undone.');
}

// Toggle content type fields (movie vs series)
function toggleContentType() {
    const typeSelect = document.getElementById('content_type');
    const durationField = document.getElementById('duration_field');
    
    if (typeSelect && durationField) {
        if (typeSelect.value === 'movie') {
            durationField.style.display = 'block';
            document.getElementById('duration_min').required = true;
        } else {
            durationField.style.display = 'none';
            document.getElementById('duration_min').required = false;
            document.getElementById('duration_min').value = '';
        }
    }
}

// Modal functions
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
    }
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('active');
    }
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize content type toggle if on add content page
    const typeSelect = document.getElementById('content_type');
    if (typeSelect) {
        toggleContentType();
        typeSelect.addEventListener('change', toggleContentType);
    }
});
