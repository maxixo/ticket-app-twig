/**
 * TicketFlow Frontend JavaScript
 * Handles client-side interactions, validation, and dynamic UI updates
 */

document.addEventListener('DOMContentLoaded', function() {
    // Auto-dismiss toasts after 3 seconds
    const toasts = document.querySelectorAll('[id$="-toast"]');
    toasts.forEach(toast => {
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    });

    // Character counter for form inputs
    const titleInput = document.getElementById('title');
    const descInput = document.getElementById('description');
    const titleCount = document.getElementById('titleCount');
    const descCount = document.getElementById('descCount');

    if (titleInput && titleCount) {
        titleInput.addEventListener('input', function() {
            titleCount.textContent = this.value.length;
        });
    }

    if (descInput && descCount) {
        descInput.addEventListener('input', function() {
            descCount.textContent = this.value.length;
        });
    }

    // Form validation
    const ticketForm = document.getElementById('ticketForm');
    if (ticketForm) {
        ticketForm.addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const status = document.querySelector('select[name="status"]').value;
            const priority = document.querySelector('select[name="priority"]').value;
            
            let errors = [];
            
            if (!title) {
                errors.push('Title is required');
            } else if (title.length < 3) {
                errors.push('Title must be at least 3 characters');
            } else if (title.length > 100) {
                errors.push('Title must not exceed 100 characters');
            }
            
            if (!status) {
                errors.push('Status is required');
            }
            
            if (!priority) {
                errors.push('Priority is required');
            }
            
            if (errors.length > 0) {
                e.preventDefault();
                showToast(errors[0], 'error');
            }
        });
    }

    // Delete confirmation with enhanced UX
    const deleteLinks = document.querySelectorAll('a[href*="/delete"]');
    deleteLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this ticket? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });

    // Mobile navigation toggle
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');
    
    if (mobileMenuButton && mobileMenu) {
        mobileMenuButton.addEventListener('click', function() {
            mobileMenu.classList.toggle('hidden');
        });
    }

    // Form field real-time validation
    const emailInput = document.querySelector('input[type="email"]');
    if (emailInput) {
        emailInput.addEventListener('blur', function() {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (this.value && !emailRegex.test(this.value)) {
                showFieldError(this, 'Please enter a valid email address');
            } else {
                clearFieldError(this);
            }
        });
    }

    // Password strength indicator
    const passwordInput = document.querySelector('input[type="password"]');
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const strength = calculatePasswordStrength(this.value);
            updatePasswordStrength(strength);
        });
    }

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // Auto-save draft functionality (optional)
    const draftSaveInterval = 30000; // 30 seconds
    if (ticketForm) {
        setInterval(() => {
            saveDraft();
        }, draftSaveInterval);
    }
});

/**
 * Show toast notification
 * @param {string} message - Message to display
 * @param {string} type - 'success' or 'error'
 */
function showToast(message, type = 'success') {
    // Remove existing toasts
    const existingToasts = document.querySelectorAll('.toast-notification');
    existingToasts.forEach(toast => toast.remove());

    // Create new toast
    const toast = document.createElement('div');
    toast.className = `toast-notification fixed top-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg text-white flex items-center gap-2 ${
        type === 'success' ? 'bg-green-500' : 'bg-red-500'
    }`;
    
    const icon = type === 'success' ? '✓' : '⚠️';
    toast.innerHTML = `
        <span class="text-xl">${icon}</span>
        <span>${message}</span>
        <button onclick="this.parentElement.remove()" class="ml-2 text-white hover:text-gray-200">
            ✕
        </button>
    `;
    
    document.body.appendChild(toast);
    
    // Auto-dismiss after 3 seconds
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.3s';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

/**
 * Show field-specific error
 * @param {HTMLElement} field - Input field element
 * @param {string} message - Error message
 */
function showFieldError(field, message) {
    clearFieldError(field);
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error text-red-500 text-sm mt-1';
    errorDiv.textContent = message;
    
    field.parentNode.appendChild(errorDiv);
    field.classList.add('border-red-500');
}

/**
 * Clear field-specific error
 * @param {HTMLElement} field - Input field element
 */
function clearFieldError(field) {
    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
    field.classList.remove('border-red-500');
}

/**
 * Calculate password strength
 * @param {string} password - Password string
 * @returns {number} Strength score (0-4)
 */
function calculatePasswordStrength(password) {
    let strength = 0;
    
    if (password.length >= 6) strength++;
    if (password.length >= 10) strength++;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
    if (/\d/.test(password)) strength++;
    if (/[^a-zA-Z\d]/.test(password)) strength++;
    
    return Math.min(strength, 4);
}

/**
 * Update password strength indicator
 * @param {number} strength - Strength score (0-4)
 */
function updatePasswordStrength(strength) {
    const indicator = document.getElementById('password-strength');
    if (!indicator) return;
    
    const labels = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
    const colors = ['bg-red-500', 'bg-orange-500', 'bg-yellow-500', 'bg-blue-500', 'bg-green-500'];
    
    indicator.innerHTML = `
        <div class="h-2 rounded-full bg-gray-200 mt-2">
            <div class="${colors[strength]} h-full rounded-full transition-all" style="width: ${(strength + 1) * 20}%"></div>
        </div>
        <p class="text-xs text-gray-600 mt-1">${labels[strength]}</p>
    `;
}

/**
 * Save form draft to localStorage
 */
function saveDraft() {
    const titleInput = document.getElementById('title');
    const descInput = document.getElementById('description');
    
    if (titleInput && descInput) {
        const draft = {
            title: titleInput.value,
            description: descInput.value,
            timestamp: Date.now()
        };
        
        localStorage.setItem('ticket_draft', JSON.stringify(draft));
    }
}

/**
 * Load form draft from localStorage
 */
function loadDraft() {
    const draft = localStorage.getItem('ticket_draft');
    if (draft) {
        const data = JSON.parse(draft);
        const titleInput = document.getElementById('title');
        const descInput = document.getElementById('description');
        
        if (titleInput && !titleInput.value) {
            titleInput.value = data.title;
        }
        if (descInput && !descInput.value) {
            descInput.value = data.description;
        }
    }
}

/**
 * Clear saved draft
 */
function clearDraft() {
    localStorage.removeItem('ticket_draft');
}

/**
 * Format date for display
 * @param {string} dateString - Date string
 * @returns {string} Formatted date
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diff = now - date;
    
    // Less than 1 minute
    if (diff < 60000) {
        return 'Just now';
    }
    
    // Less than 1 hour
    if (diff < 3600000) {
        const minutes = Math.floor(diff / 60000);
        return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
    }
    
    // Less than 1 day
    if (diff < 86400000) {
        const hours = Math.floor(diff / 3600000);
        return `${hours} hour${hours > 1 ? 's' : ''} ago`;
    }
    
    // Less than 1 week
    if (diff < 604800000) {
        const days = Math.floor(diff / 86400000);
        return `${days} day${days > 1 ? 's' : ''} ago`;
    }
    
    // Default format
    return date.toLocaleDateString();
}

/**
 * Filter tickets by status
 * @param {string} status - Status to filter by
 */
function filterTickets(status) {
    const tickets = document.querySelectorAll('.ticket-card');
    
    tickets.forEach(ticket => {
        if (status === 'all' || ticket.dataset.status === status) {
            ticket.style.display = 'block';
        } else {
            ticket.style.display = 'none';
        }
    });
}

/**
 * Search tickets
 * @param {string} query - Search query
 */
function searchTickets(query) {
    const tickets = document.querySelectorAll('.ticket-card');
    const lowerQuery = query.toLowerCase();
    
    tickets.forEach(ticket => {
        const title = ticket.querySelector('.ticket-title').textContent.toLowerCase();
        const description = ticket.querySelector('.ticket-description').textContent.toLowerCase();
        
        if (title.includes(lowerQuery) || description.includes(lowerQuery)) {
            ticket.style.display = 'block';
        } else {
            ticket.style.display = 'none';
        }
    });
}

/**
 * Sort tickets
 * @param {string} sortBy - Field to sort by ('title', 'status', 'priority', 'date')
 */
function sortTickets(sortBy) {
    const container = document.querySelector('.tickets-grid');
    if (!container) return;
    
    const tickets = Array.from(container.querySelectorAll('.ticket-card'));
    
    tickets.sort((a, b) => {
        let valueA, valueB;
        
        switch(sortBy) {
            case 'title':
                valueA = a.querySelector('.ticket-title').textContent;
                valueB = b.querySelector('.ticket-title').textContent;
                return valueA.localeCompare(valueB);
            
            case 'status':
                valueA = a.dataset.status;
                valueB = b.dataset.status;
                return valueA.localeCompare(valueB);
            
            case 'priority':
                const priorityOrder = { low: 0, medium: 1, high: 2 };
                valueA = priorityOrder[a.dataset.priority];
                valueB = priorityOrder[b.dataset.priority];
                return valueB - valueA; // High to low
            
            case 'date':
                valueA = parseInt(a.dataset.id);
                valueB = parseInt(b.dataset.id);
                return valueB - valueA; // Newest first
            
            default:
                return 0;
        }
    });
    
    // Re-append sorted tickets
    tickets.forEach(ticket => container.appendChild(ticket));
}

/**
 * Export tickets to CSV
 */
function exportToCSV() {
    const tickets = Array.from(document.querySelectorAll('.ticket-card'));
    
    let csv = 'ID,Title,Description,Status,Priority\n';
    
    tickets.forEach(ticket => {
        const id = ticket.dataset.id;
        const title = ticket.querySelector('.ticket-title').textContent;
        const description = ticket.querySelector('.ticket-description').textContent;
        const status = ticket.dataset.status;
        const priority = ticket.dataset.priority;
        
        csv += `${id},"${title}","${description}",${status},${priority}\n`;
    });
    
    // Create download link
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `tickets_${Date.now()}.csv`;
    a.click();
    window.URL.revokeObjectURL(url);
}

/**
 * Print tickets
 */
function printTickets() {
    window.print();
}

/**
 * Initialize tooltips
 */
function initTooltips() {
    const tooltips = document.querySelectorAll('[data-tooltip]');
    
    tooltips.forEach(element => {
        element.addEventListener('mouseenter', function() {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip absolute bg-gray-800 text-white text-sm px-2 py-1 rounded';
            tooltip.textContent = this.dataset.tooltip;
            tooltip.id = 'active-tooltip';
            
            this.appendChild(tooltip);
            
            // Position tooltip
            const rect = this.getBoundingClientRect();
            tooltip.style.bottom = '100%';
            tooltip.style.left = '50%';
            tooltip.style.transform = 'translateX(-50%)';
            tooltip.style.marginBottom = '5px';
        });
        
        element.addEventListener('mouseleave', function() {
            const tooltip = document.getElementById('active-tooltip');
            if (tooltip) tooltip.remove();
        });
    });
}

// Initialize on load
window.addEventListener('load', function() {
    initTooltips();
    loadDraft();
});

// Clear draft on successful form submission
document.addEventListener('submit', function(e) {
    if (e.target.id === 'ticketForm') {
        clearDraft();
    }
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + S to save draft
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        saveDraft();
        showToast('Draft saved', 'success');
    }
    
    // Ctrl/Cmd + N to create new ticket
    if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
        e.preventDefault();
        const newTicketBtn = document.querySelector('a[href*="/create"]');
        if (newTicketBtn) newTicketBtn.click();
    }
    
    // Escape to close modals/forms
    if (e.key === 'Escape') {
        const cancelBtn = document.querySelector('a[href="/tickets"]');
        if (cancelBtn && window.location.pathname.includes('/tickets/')) {
            cancelBtn.click();
        }
    }
});

// Make functions available globally
window.TicketFlow = {
    showToast,
    filterTickets,
    searchTickets,
    sortTickets,
    exportToCSV,
    printTickets,
    saveDraft,
    loadDraft,
    clearDraft
};