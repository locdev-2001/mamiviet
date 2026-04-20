/**
 * Reservation List View JavaScript
 * MamiViet Admin Panel
 */

class ReservationList {
    constructor() {
        this.translations = window.reservationTranslations;
        this.routes = window.reservationRoutes;
        this.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        this.init();
    }

    init() {
        this.initializeEventListeners();
        this.initializeStatusModal();
    }

    initializeEventListeners() {
        // Status buttons
        document.querySelectorAll('.status-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.openStatusModal(btn.dataset.id, btn.dataset.currentStatus);
            });
        });

        // Delete buttons
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.confirmDelete(btn.dataset.id, btn.dataset.name);
            });
        });

        // Save status button
        document.getElementById('saveStatusBtn').addEventListener('click', () => this.saveStatus());

        // Export button
        const exportBtn = document.getElementById('exportBtn');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => this.exportReservations());
        }

        // Form auto-submit on filter change
        const filterForm = document.getElementById('filterForm');
        if (filterForm) {
            const inputs = filterForm.querySelectorAll('select, input[type="date"]');
            inputs.forEach(input => {
                input.addEventListener('change', () => {
                    // Small delay to allow user to make multiple changes
                    setTimeout(() => {
                        if (this.autoSubmitTimeout) {
                            clearTimeout(this.autoSubmitTimeout);
                        }
                        this.autoSubmitTimeout = setTimeout(() => {
                            filterForm.submit();
                        }, 500);
                    }, 100);
                });
            });

            // Search input with debounce
            const searchInput = filterForm.querySelector('input[name="search"]');
            if (searchInput) {
                searchInput.addEventListener('input', () => {
                    if (this.searchTimeout) {
                        clearTimeout(this.searchTimeout);
                    }
                    this.searchTimeout = setTimeout(() => {
                        filterForm.submit();
                    }, 1000);
                });
            }
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case 'f':
                        e.preventDefault();
                        const searchInput = document.querySelector('input[name="search"]');
                        if (searchInput) {
                            searchInput.focus();
                        }
                        break;
                    case 'e':
                        e.preventDefault();
                        this.exportReservations();
                        break;
                }
            }
        });
    }

    initializeStatusModal() {
        this.statusModal = new bootstrap.Modal(document.getElementById('statusModal'));
    }

    openStatusModal(reservationId, currentStatus) {
        document.getElementById('reservationId').value = reservationId;
        document.getElementById('reservationStatus').value = currentStatus;

        // Clear admin notes
        document.getElementById('adminNotes').value = '';

        this.statusModal.show();
    }

    async saveStatus() {
        const reservationId = document.getElementById('reservationId').value;
        const status = document.getElementById('reservationStatus').value;
        const adminNotes = document.getElementById('adminNotes').value;

        const saveBtn = document.getElementById('saveStatusBtn');
        const originalText = saveBtn.innerHTML;

        try {
            // Show loading state
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
            saveBtn.disabled = true;

            const response = await fetch(this.routes.updateStatus.replace(':id', reservationId), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    status: status,
                    admin_notes: adminNotes,
                    _method: 'PUT'
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showSuccess(data.message);
                this.updateReservationStatus(reservationId, data.reservation);
                this.statusModal.hide();
            } else {
                this.showError(data.message);
            }
        } catch (error) {
            console.error('Error updating status:', error);
            this.showError(this.translations.status_update_error);
        } finally {
            // Restore button state
            saveBtn.innerHTML = originalText;
            saveBtn.disabled = false;
        }
    }

    updateReservationStatus(reservationId, reservationData) {
        // Update table row
        const tableRow = document.querySelector(`tr[data-id="${reservationId}"]`);
        if (tableRow) {
            const statusBadge = tableRow.querySelector('.status-badge');
            if (statusBadge) {
                statusBadge.className = `badge status-badge status-${reservationData.status}`;
                statusBadge.textContent = reservationData.status_label;
            }

            const statusBtn = tableRow.querySelector('.status-btn');
            if (statusBtn) {
                statusBtn.dataset.currentStatus = reservationData.status;
            }

            // Add animation
            tableRow.classList.add('status-updated');
            setTimeout(() => tableRow.classList.remove('status-updated'), 1000);
        }

        // Update card view
        const card = document.querySelector(`.reservation-item-card[data-id="${reservationId}"]`);
        if (card) {
            const statusBadge = card.querySelector('.status-badge');
            if (statusBadge) {
                statusBadge.className = `badge status-badge status-${reservationData.status}`;
                statusBadge.textContent = reservationData.status_label;
            }

            const statusBtn = card.querySelector('.status-btn');
            if (statusBtn) {
                statusBtn.dataset.currentStatus = reservationData.status;
            }

            // Update admin notes if present
            if (reservationData.admin_notes) {
                let notesSection = card.querySelector('.admin-notes-section');
                if (!notesSection) {
                    notesSection = document.createElement('div');
                    notesSection.className = 'col-12 mt-2 admin-notes-section';
                    card.querySelector('.row.text-muted').appendChild(notesSection);
                }
                notesSection.innerHTML = `
                    <strong>${this.translations.admin_notes || 'Admin Notes'}:</strong>
                    <p class="text-muted mb-0">${reservationData.admin_notes}</p>
                `;
            }

            // Add animation
            card.classList.add('status-updated');
            setTimeout(() => card.classList.remove('status-updated'), 1000);
        }
    }

    confirmDelete(reservationId, reservationName) {
        if (confirm(this.translations.confirm_delete_item.replace(':name', reservationName))) {
            this.deleteReservation(reservationId);
        }
    }

    async deleteReservation(reservationId) {
        try {
            const response = await fetch(this.routes.destroy.replace(':id', reservationId), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    _method: 'DELETE'
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showSuccess(data.message);
                this.removeReservationFromView(reservationId);
            } else {
                this.showError(data.message);
            }
        } catch (error) {
            console.error('Error deleting reservation:', error);
            this.showError(this.translations.delete_error);
        }
    }

    removeReservationFromView(reservationId) {
        // Remove from table
        const tableRow = document.querySelector(`tr[data-id="${reservationId}"]`);
        if (tableRow) {
            tableRow.style.opacity = '0';
            tableRow.style.transform = 'translateX(-100%)';
            setTimeout(() => {
                tableRow.remove();
                this.updateSerialNumbers();
            }, 300);
        }

        // Remove from card view
        const card = document.querySelector(`.reservation-item-card[data-id="${reservationId}"]`);
        if (card) {
            card.style.opacity = '0';
            card.style.transform = 'translateX(-100%)';
            setTimeout(() => {
                card.remove();
            }, 300);
        }
    }

    updateSerialNumbers() {
        // Update serial numbers in table
        const tableRows = document.querySelectorAll('tbody tr[data-id]');
        tableRows.forEach((row, index) => {
            const serialCell = row.querySelector('td:first-child strong');
            if (serialCell) {
                serialCell.textContent = index + 1;
            }
        });

        // Update serial numbers in cards
        const cards = document.querySelectorAll('.reservation-item-card[data-id]');
        cards.forEach((card, index) => {
            const serialBadge = card.querySelector('.badge.bg-primary');
            if (serialBadge) {
                serialBadge.textContent = `#${index + 1}`;
            }
        });
    }

    exportReservations() {
        // Get current filters
        const form = document.getElementById('filterForm');
        const formData = new FormData(form);
        const params = new URLSearchParams(formData);
        params.append('export', 'true');

        // Create temporary link and download
        const link = document.createElement('a');
        link.href = `${form.action}?${params.toString()}`;
        link.download = `reservations_${new Date().toISOString().split('T')[0]}.csv`;
        link.style.display = 'none';

        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        this.showSuccess('Export started. File will download shortly.');
    }

    showSuccess(message) {
        this.showToast(message, 'success');
    }

    showError(message) {
        this.showToast(message, 'error');
    }

    showToast(message, type) {
        // Remove existing toasts
        document.querySelectorAll('.custom-toast').forEach(toast => toast.remove());

        // Create toast element
        const toast = document.createElement('div');
        toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed custom-toast`;
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px; max-width: 500px;';
        toast.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                <div class="flex-grow-1">${message}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;

        document.body.appendChild(toast);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (toast.parentNode) {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 150);
            }
        }, 5000);
    }

    // Utility methods
    formatDate(dateString) {
        return new Date(dateString).toLocaleDateString('de-DE', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
        });
    }

    formatTime(timeString) {
        return timeString.substring(0, 5); // HH:MM format
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    new ReservationList();

    // Add smooth scrolling for page navigation
    document.querySelectorAll('.pagination a').forEach(link => {
        link.addEventListener('click', function() {
            setTimeout(() => {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }, 100);
        });
    });

    // Add loading state to filter form
    const filterForm = document.getElementById('filterForm');
    if (filterForm) {
        filterForm.addEventListener('submit', function() {
            const submitBtn = filterForm.querySelector('button[type="submit"]');
            if (submitBtn) {
                const originalHTML = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Filtering...';
                submitBtn.disabled = true;

                // Restore after delay (in case navigation is slow)
                setTimeout(() => {
                    submitBtn.innerHTML = originalHTML;
                    submitBtn.disabled = false;
                }, 3000);
            }
        });
    }
});