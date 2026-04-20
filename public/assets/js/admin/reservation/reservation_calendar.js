/**
 * Reservation Calendar with Drag & Drop
 * MamiViet Admin Panel
 */

class ReservationCalendar {
    constructor() {
        this.currentWeekStart = window.currentWeekStart;
        this.calendarData = window.calendarData;
        this.translations = window.reservationTranslations;
        this.routes = window.reservationRoutes;
        this.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        this.init();
    }

    init() {
        this.initializeEventListeners();
        this.initializeDragAndDrop();
        this.initializeStatusModal();

        // Check if there's a week parameter in URL
        const urlParams = new URLSearchParams(window.location.search);
        const weekParam = urlParams.get('week');

        if (weekParam) {
            // Load the specified week
            const startDate = new Date(weekParam);
            this.currentWeekStart = weekParam;
            this.loadWeekData(startDate);
        } else {
            // Load current week and update URL
            this.updateWeekDisplay();
            this.updateURLWithCurrentWeek();
        }

        // Initial row height synchronization
        setTimeout(() => this.synchronizeRowHeights(), 200);

        // Initialize tooltips with debug
        setTimeout(() => {
            console.log('Starting tooltip initialization...');
            const reservationItems = document.querySelectorAll('.reservation-item[data-bs-toggle="tooltip"]');
            console.log('Found reservation items:', reservationItems.length);

            this.initializeTooltips();

            // Test if tooltips are working after 2 seconds
            setTimeout(() => {
                console.log('Testing tooltip functionality...');
                if (reservationItems.length > 0) {
                    console.log('First reservation item:', reservationItems[0]);
                    console.log('Has tooltip data:', reservationItems[0].getAttribute('data-bs-title'));
                }
            }, 2000);
        }, 300);
    }

    initializeEventListeners() {
        // Navigation buttons
        document.getElementById('prevWeekBtn').addEventListener('click', () => this.navigateWeek(-1));
        document.getElementById('nextWeekBtn').addEventListener('click', () => this.navigateWeek(1));
        document.getElementById('todayBtn').addEventListener('click', () => this.goToToday());

        // Status buttons
        document.querySelectorAll('.status-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const reservationId = btn.dataset.id;
                const currentStatus = btn.dataset.currentStatus;
                this.openStatusModal(reservationId, currentStatus);
            });
        });



        // Save status button
        document.getElementById('saveStatusBtn').addEventListener('click', () => this.saveStatus());

        // Save time button
        document.getElementById('saveTimeBtn').addEventListener('click', () => this.saveTime());

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case 'ArrowLeft':
                        e.preventDefault();
                        this.navigateWeek(-1);
                        break;
                    case 'ArrowRight':
                        e.preventDefault();
                        this.navigateWeek(1);
                        break;
                    case 't':
                        e.preventDefault();
                        this.goToToday();
                        break;
                }
            }
        });
    }

    initializeDragAndDrop() {
        // Initialize all droppable slots
        document.querySelectorAll('.droppable-slot').forEach(slot => {
            this.makeDroppable(slot);
        });

        // Initialize all draggable reservations
        document.querySelectorAll('.draggable-reservation').forEach(reservation => {
            this.makeDraggable(reservation);
        });
    }

    makeDraggable(element) {
        element.draggable = true;

        element.addEventListener('dragstart', (e) => {
            element.classList.add('dragging');
            e.dataTransfer.setData('text/plain', element.dataset.id);
            e.dataTransfer.effectAllowed = 'move';

            // Store original position for potential rollback
            element.dataset.originalDate = element.closest('.day-column').dataset.date;
            element.dataset.originalTime = element.closest('.time-slot-cell').dataset.time;
        });

        element.addEventListener('dragend', (e) => {
            element.classList.remove('dragging');
            this.clearDropZones();
        });
    }

    makeDroppable(element) {
        element.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';

            if (!element.classList.contains('drop-target')) {
                this.clearDropZones();
                element.classList.add('drop-target');
                element.setAttribute('data-drop-text', this.translations.drop_here);
            }
        });

        element.addEventListener('dragleave', (e) => {
            // Only remove highlight if leaving the element completely
            if (!element.contains(e.relatedTarget)) {
                element.classList.remove('drop-target');
            }
        });

        element.addEventListener('drop', (e) => {
            e.preventDefault();
            const reservationId = e.dataTransfer.getData('text/plain');
            const newDate = element.dataset.date;
            const newTime = element.dataset.time;

            this.clearDropZones();
            this.handleDrop(reservationId, newDate, newTime);
        });
    }

    clearDropZones() {
        document.querySelectorAll('.drop-target').forEach(el => {
            el.classList.remove('drop-target');
            el.removeAttribute('data-drop-text');
        });
    }

    async handleDrop(reservationId, newDate, newTime) {
        const reservation = document.querySelector(`[data-id="${reservationId}"]`);
        if (!reservation) return;

        const originalDate = reservation.dataset.originalDate;
        const originalTime = reservation.dataset.originalTime;

        // Check if actually moved
        if (originalDate === newDate && originalTime === newTime) {
            return;
        }

        // Validate time slot (11:00 - 22:00)
        const hour = parseInt(newTime.split(':')[0]);
        if (hour < 11 || hour > 22) {
            this.showError(this.translations.invalid_time_slot);
            return;
        }

        this.showLoading(true);

        try {
            const response = await fetch(this.routes.reschedule.replace(':id', reservationId), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    new_date: newDate,
                    new_time: newTime,
                    _method: 'PUT'
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showSuccess(data.message);
                // Reload page to reflect changes while keeping URL parameters
                setTimeout(() => {
                    window.location.href = window.location.href;
                }, 1000);
            } else {
                this.showError(data.message);
                this.revertReservationPosition(reservation, originalDate, originalTime);
            }
        } catch (error) {
            console.error('Error rescheduling reservation:', error);
            this.showError(this.translations.reschedule_error);
            this.revertReservationPosition(reservation, originalDate, originalTime);
        } finally {
            this.showLoading(false);
        }
    }

    moveReservationElement(reservation, newDate, newTime) {
        const newSlot = document.querySelector(`[data-date="${newDate}"][data-time="${newTime}"]`);
        if (newSlot) {
            const reservationsContainer = newSlot.querySelector('.reservations-container');
            if (reservationsContainer) {
                reservationsContainer.appendChild(reservation);
            } else {
                newSlot.appendChild(reservation);
            }


            reservation.classList.add('new-reservation');
            setTimeout(() => reservation.classList.remove('new-reservation'), 300);
        }
    }


    revertReservationPosition(reservation, originalDate, originalTime) {
        const originalSlot = document.querySelector(`[data-date="${originalDate}"][data-time="${originalTime}"]`);
        if (originalSlot) {
            const reservationsContainer = originalSlot.querySelector('.reservations-container');
            if (reservationsContainer) {
                reservationsContainer.appendChild(reservation);
            } else {
                originalSlot.appendChild(reservation);
            }

        }
    }

    updateCapacityBars() {
        document.querySelectorAll('.time-slot-cell').forEach(slot => {
            const reservations = slot.querySelectorAll('.draggable-reservation');
            const totalPersons = Array.from(reservations).reduce((sum, res) => {
                return sum + parseInt(res.dataset.persons || 0);
            }, 0);

            const maxCapacity = 50; // Should match backend configuration
            const percentage = Math.min((totalPersons / maxCapacity) * 100, 100);

            const capacityFill = slot.querySelector('.capacity-fill');
            if (capacityFill) {
                capacityFill.style.width = percentage + '%';
            }

            slot.dataset.capacity = percentage;


        });
    }

    navigateWeek(direction) {
        const currentDate = new Date(this.currentWeekStart);
        currentDate.setDate(currentDate.getDate() + (direction * 7));
        this.loadWeekData(currentDate);
        this.updateURL(currentDate);
    }

    goToToday() {
        const today = new Date();
        this.loadWeekData(today);
        this.updateURL(today);
    }

    updateURL(date) {
        const startOfWeek = new Date(date);
        startOfWeek.setDate(date.getDate() - date.getDay() + 1); // Monday
        const weekParam = startOfWeek.toISOString().split('T')[0];

        const url = new URL(window.location);
        url.searchParams.set('week', weekParam);
        window.history.replaceState({}, '', url);
    }

    updateURLWithCurrentWeek() {
        if (this.currentWeekStart) {
            const startDate = new Date(this.currentWeekStart);
            this.updateURL(startDate);
        }
    }

    async loadWeekData(startDate) {
        this.showLoading(true);

        try {
            const url = `${this.routes.getWeekData}?start_date=${startDate.toISOString().split('T')[0]}`;

            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.calendar_data) {
                this.calendarData = data.calendar_data;
                this.currentWeekStart = data.start_of_week;
                this.renderCalendar();
                this.updateWeekDisplay();

                // Update URL with the loaded week
                this.updateURL(new Date(this.currentWeekStart));
            } else {
                console.error('No calendar data in response:', data);
                this.showError('No calendar data received');
            }
        } catch (error) {
            console.error('Error loading week data:', error);
            this.showError(`Error loading calendar data: ${error.message}`);
        } finally {
            this.showLoading(false);
        }
    }

    renderCalendar() {
        // Clear existing calendar content
        document.querySelectorAll('.day-column').forEach(column => {
            column.remove();
        });

        // Generate new calendar columns
        const calendarGrid = document.querySelector('.calendar-grid');
        const timeColumn = calendarGrid.querySelector('.time-column');

        Object.entries(this.calendarData).forEach(([dateString, dayData]) => {
            const dayColumn = this.createDayColumn(dateString, dayData);
            calendarGrid.appendChild(dayColumn);
        });

        // Reinitialize drag and drop
        this.initializeDragAndDrop();

        // Synchronize row heights after rendering
        setTimeout(() => this.synchronizeRowHeights(), 100);

        // Initialize tooltips for reservation items
        this.initializeTooltips();

        // Fallback: simple tooltip initialization if custom one fails
        setTimeout(() => {
            if (document.querySelectorAll('.tooltip.show').length === 0) {
                this.initializeSimpleTooltips();
            }
        }, 1000);

        // Reinitialize status buttons
        document.querySelectorAll('.status-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.openStatusModal(btn.dataset.id, btn.dataset.currentStatus);
            });
        });

        // Reinitialize edit time buttons
        document.querySelectorAll('.edit-time-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.openEditTimeModal(btn.dataset.id, btn.dataset.currentTime, btn.dataset.currentDate);
            });
        });
    }

    createDayColumn(dateString, dayData) {
        const dayColumn = document.createElement('div');
        dayColumn.className = 'day-column';
        dayColumn.dataset.date = dateString;

        // Day header
        const dayHeader = document.createElement('div');
        dayHeader.className = `day-header ${dayData.is_today ? 'today' : ''}`;
        dayHeader.innerHTML = `
            <div class="day-name">${dayData.day_short}</div>
            <div class="day-number">${dayData.day_number}</div>
        `;
        dayColumn.appendChild(dayHeader);

        // Time slots
        Object.entries(dayData.time_slots).forEach(([timeSlot, slotData]) => {
            const timeSlotCell = this.createTimeSlotCell(dateString, timeSlot, slotData);
            dayColumn.appendChild(timeSlotCell);
        });

        return dayColumn;
    }

    createTimeSlotCell(dateString, timeSlot, slotData) {
        const cell = document.createElement('div');
        cell.className = 'time-slot-cell droppable-slot';
        cell.dataset.date = dateString;
        cell.dataset.time = timeSlot;
        cell.dataset.capacity = slotData.capacity_percentage;


        // Capacity bar
        const capacityBar = document.createElement('div');
        capacityBar.className = 'capacity-bar';
        capacityBar.innerHTML = `<div class="capacity-fill" style="width: ${slotData.capacity_percentage}%"></div>`;
        cell.appendChild(capacityBar);

        // Reservations container
        const reservationsContainer = document.createElement('div');
        reservationsContainer.className = 'reservations-container';

        // Reservations
        slotData.reservations.forEach(reservation => {
            const reservationItem = this.createReservationItem(reservation);
            reservationsContainer.appendChild(reservationItem);
        });

        cell.appendChild(reservationsContainer);



        return cell;
    }

    createReservationItem(reservation) {
        const item = document.createElement('div');
        item.className = `reservation-item draggable-reservation status-${reservation.status}`;
        item.dataset.id = reservation.id;
        item.dataset.name = reservation.name;
        item.dataset.persons = reservation.persons;
        item.dataset.status = reservation.status;

        // Create detailed tooltip content
        const tooltipContent = this.createTooltipContent(reservation);
        item.setAttribute('data-bs-toggle', 'tooltip');
        item.setAttribute('data-bs-placement', 'top');
        item.setAttribute('data-bs-html', 'true');
        item.setAttribute('data-bs-title', tooltipContent);

        item.innerHTML = `
            <div class="reservation-content">
                <div class="reservation-name">${reservation.name}</div>
                <div class="reservation-details">
                    <span class="exact-time">${reservation.exact_time}</span>
                    <span class="persons">${reservation.persons}p</span>
                    <span class="status-badge status-${reservation.status}">
                        ${reservation.status_label}
                    </span>
                </div>
            </div>
            <div class="reservation-actions">
                <button class="btn btn-sm btn-outline-light edit-time-btn"
                        data-id="${reservation.id}"
                        data-current-time="${reservation.exact_time}"
                        data-current-date="${reservation.date_string}"
                        title="Edit time">
                    <i class="fas fa-clock"></i>
                </button>
                <button class="btn btn-sm btn-outline-light status-btn"
                        data-id="${reservation.id}"
                        data-current-status="${reservation.status}"
                        title="${this.translations.update_status}">
                    <i class="fas fa-edit"></i>
                </button>
            </div>
        `;

        return item;
    }

    createTooltipContent(reservation) {
        return `
            <div class="reservation-tooltip">
                <div class="tooltip-header">
                    <strong>${reservation.name}</strong>
                    <span class="status-badge-tooltip status-${reservation.status}">${reservation.status_label}</span>
                </div>
                <div class="tooltip-body">
                    <div class="tooltip-row">
                        <i class="fas fa-clock"></i>
                        <span>${reservation.exact_time}</span>
                    </div>
                    <div class="tooltip-row">
                        <i class="fas fa-users"></i>
                        <span>${reservation.persons} ${this.translations.persons}</span>
                    </div>
                    <div class="tooltip-row">
                        <i class="fas fa-envelope"></i>
                        <span>${reservation.email}</span>
                    </div>
                    <div class="tooltip-row">
                        <i class="fas fa-phone"></i>
                        <span>${reservation.phone}</span>
                    </div>
                    ${reservation.admin_notes ? `
                    <div class="tooltip-row">
                        <i class="fas fa-sticky-note"></i>
                        <span>${reservation.admin_notes}</span>
                    </div>
                    ` : ''}
                </div>
            </div>
        `;
    }

    updateWeekDisplay() {
        const startDate = new Date(this.currentWeekStart);
        const endDate = new Date(startDate);
        endDate.setDate(endDate.getDate() + 6);

        const weekRange = document.getElementById('weekRange');
        if (weekRange) {
            weekRange.textContent = `${this.formatDate(startDate)} - ${this.formatDate(endDate)}`;
        }
    }

    formatDate(date) {
        return date.toLocaleDateString('de-DE', {
            month: 'short',
            day: 'numeric',
            year: date.getFullYear() !== new Date().getFullYear() ? 'numeric' : undefined
        });
    }

    initializeStatusModal() {
        this.statusModal = new bootstrap.Modal(document.getElementById('statusModal'));
        this.editTimeModal = new bootstrap.Modal(document.getElementById('editTimeModal'));
    }

    initializeTooltips() {
        console.log('Initializing tooltips...');

        // Dispose existing tooltips first
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(element => {
            const tooltip = bootstrap.Tooltip.getInstance(element);
            if (tooltip) {
                tooltip.dispose();
            }
        });

        // Get all reservation items
        const tooltipTriggerList = document.querySelectorAll('.reservation-item[data-bs-toggle="tooltip"]');
        console.log('Tooltip elements found:', tooltipTriggerList.length);

        if (tooltipTriggerList.length === 0) {
            console.log('No tooltip elements found');
            return;
        }

        // Simple approach first - just enable basic tooltips
        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => {
            console.log('Creating tooltip for element:', tooltipTriggerEl);

            const tooltip = new bootstrap.Tooltip(tooltipTriggerEl, {
                trigger: 'hover focus',
                delay: { show: 300, hide: 100 },
                placement: 'top',
                fallbackPlacements: ['bottom', 'left', 'right'],
                html: true
            });

            return tooltip;
        });

        console.log('Created tooltips:', tooltipList.length);

        // Add persistent hover behavior
        setTimeout(() => {
            this.addPersistentHover();
        }, 500);
    }

    addPersistentHover() {
        console.log('Adding persistent hover behavior...');

        document.querySelectorAll('.reservation-item[data-bs-toggle="tooltip"]').forEach(element => {
            const tooltip = bootstrap.Tooltip.getInstance(element);
            if (!tooltip) return;

            let hideTimeout = null;
            let isOverTooltip = false;

            // Listen for tooltip shown event to add listeners immediately
            element.addEventListener('shown.bs.tooltip', () => {
                console.log('Tooltip shown, adding hover listeners...');

                const tooltipEl = document.querySelector('.tooltip.show');
                if (tooltipEl && !tooltipEl.hasAttribute('data-hover-added')) {
                    console.log('Adding hover listeners to tooltip');
                    tooltipEl.setAttribute('data-hover-added', 'true');

                    tooltipEl.addEventListener('mouseenter', () => {
                        console.log('Mouse entered tooltip');
                        isOverTooltip = true;
                        if (hideTimeout) {
                            clearTimeout(hideTimeout);
                            hideTimeout = null;
                        }
                    });

                    tooltipEl.addEventListener('mouseleave', () => {
                        console.log('Mouse left tooltip');
                        isOverTooltip = false;
                        tooltip.hide();
                    });
                }
            });

            // Element mouseleave with delay
            element.addEventListener('mouseleave', (e) => {
                console.log('Mouse left element');
                hideTimeout = setTimeout(() => {
                    if (!isOverTooltip) {
                        console.log('Hiding tooltip after delay');
                        tooltip.hide();
                    }
                }, 300); // Increased delay
            });

            // Cancel hide on mouseenter
            element.addEventListener('mouseenter', () => {
                console.log('Mouse entered element');
                if (hideTimeout) {
                    clearTimeout(hideTimeout);
                    hideTimeout = null;
                }
            });

            // Reset tooltip state when hidden
            element.addEventListener('hidden.bs.tooltip', () => {
                isOverTooltip = false;
                const tooltipEl = document.querySelector('.tooltip');
                if (tooltipEl) {
                    tooltipEl.removeAttribute('data-hover-added');
                }
            });
        });
    }

    // Simple fallback tooltip initialization
    initializeSimpleTooltips() {
        console.log('Initializing simple tooltips fallback...');

        // Dispose any existing tooltips first
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(element => {
            const tooltip = bootstrap.Tooltip.getInstance(element);
            if (tooltip) {
                tooltip.dispose();
            }
        });

        // Simple tooltip initialization with hover persistence
        const tooltipTriggerList = document.querySelectorAll('.reservation-item[data-bs-toggle="tooltip"]');
        tooltipTriggerList.forEach(tooltipTriggerEl => {
            const tooltip = new bootstrap.Tooltip(tooltipTriggerEl, {
                trigger: 'hover focus',
                delay: { show: 300, hide: 500 },
                placement: 'top'
            });

            // Add custom behavior for persistent hover
            let hoverTimer = null;

            tooltipTriggerEl.addEventListener('mouseenter', () => {
                if (hoverTimer) {
                    clearTimeout(hoverTimer);
                    hoverTimer = null;
                }
            });

            tooltipTriggerEl.addEventListener('mouseleave', () => {
                hoverTimer = setTimeout(() => {
                    const tooltipEl = document.querySelector('.tooltip.show');
                    if (tooltipEl) {
                        // Add hover listeners to tooltip
                        tooltipEl.addEventListener('mouseenter', () => {
                            if (hoverTimer) {
                                clearTimeout(hoverTimer);
                                hoverTimer = null;
                            }
                        });

                        tooltipEl.addEventListener('mouseleave', () => {
                            tooltip.hide();
                        });
                    }
                }, 100);
            });
        });
    }

    // Synchronize heights of all cells in the same time slot row
    synchronizeRowHeights() {
        // Get all time slots (excluding header)
        const timeSlots = document.querySelectorAll('.time-slot-cell');

        // Group time slots by their time (data-time attribute)
        const timeGroups = {};
        timeSlots.forEach(slot => {
            const time = slot.dataset.time;
            if (!timeGroups[time]) {
                timeGroups[time] = [];
            }
            timeGroups[time].push(slot);
        });

        // For each time group, find the tallest cell and set all cells to that height
        Object.keys(timeGroups).forEach(time => {
            const slotsInRow = timeGroups[time];

            // Reset heights to auto to measure natural height
            slotsInRow.forEach(slot => {
                slot.style.height = 'auto';
            });

            // Find the maximum height in this row
            let maxHeight = 0;
            slotsInRow.forEach(slot => {
                const height = slot.offsetHeight;
                if (height > maxHeight) {
                    maxHeight = height;
                }
            });

            // Set all slots in this row to the maximum height
            slotsInRow.forEach(slot => {
                slot.style.height = maxHeight + 'px';
            });
        });

        // No time column in new layout
    }

    // Synchronize time column heights with the corresponding calendar rows
    synchronizeTimeColumnHeights() {
        const timeSlots = document.querySelectorAll('.time-column .time-slot');
        const calendarSlots = document.querySelectorAll('.time-slot-cell');

        // Group calendar slots by time
        const timeGroups = {};
        calendarSlots.forEach(slot => {
            const time = slot.dataset.time;
            if (!timeGroups[time]) {
                timeGroups[time] = [];
            }
            timeGroups[time].push(slot);
        });

        // Set each time column slot height to match its corresponding row
        timeSlots.forEach((timeSlot, index) => {
            const times = Object.keys(timeGroups);
            if (times[index]) {
                const correspondingSlots = timeGroups[times[index]];
                if (correspondingSlots.length > 0) {
                    const height = correspondingSlots[0].offsetHeight;
                    timeSlot.style.height = height + 'px';
                }
            }
        });
    }

    openStatusModal(reservationId, currentStatus) {
        document.getElementById('reservationId').value = reservationId;
        document.getElementById('reservationStatus').value = currentStatus;

        // Clear admin notes
        document.getElementById('adminNotes').value = '';

        this.statusModal.show();
    }

    openEditTimeModal(reservationId, currentTime, currentDate) {
        document.getElementById('editTimeReservationId').value = reservationId;
        document.getElementById('editTime').value = currentTime;
        document.getElementById('editDate').value = currentDate;

        this.editTimeModal.show();
    }

    async saveTime() {
        const reservationId = document.getElementById('editTimeReservationId').value;
        const newTime = document.getElementById('editTime').value;
        const newDate = document.getElementById('editDate').value;

        try {
            const response = await fetch(this.routes.reschedule.replace(':id', reservationId), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    new_date: newDate,
                    new_time: newTime,
                    _method: 'PUT'
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showSuccess(data.message);
                this.editTimeModal.hide();
                // Reload page to reflect changes while keeping URL parameters
                setTimeout(() => {
                    window.location.href = window.location.href;
                }, 1000);
            } else {
                this.showError(data.message);
            }
        } catch (error) {
            console.error('Error updating time:', error);
            this.showError('Error updating time');
        }
    }

    async saveStatus() {
        const reservationId = document.getElementById('reservationId').value;
        const status = document.getElementById('reservationStatus').value;
        const adminNotes = document.getElementById('adminNotes').value;

        try {
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
                this.statusModal.hide();
                // Reload page to reflect changes while keeping URL parameters
                setTimeout(() => {
                    window.location.href = window.location.href;
                }, 1000);
            } else {
                this.showError(data.message);
            }
        } catch (error) {
            console.error('Error updating status:', error);
            this.showError(this.translations.status_update_error);
        }
    }

    updateReservationStatus(reservationId, reservationData) {
        const reservation = document.querySelector(`[data-id="${reservationId}"]`);
        if (reservation) {
            // Update classes
            reservation.className = reservation.className.replace(/status-\w+/g, '');
            reservation.classList.add(`status-${reservationData.status}`);

            // Update status badge
            const statusBadge = reservation.querySelector('.status-badge');
            if (statusBadge) {
                statusBadge.className = statusBadge.className.replace(/status-\w+/g, '');
                statusBadge.classList.add(`status-${reservationData.status}`);
                statusBadge.textContent = reservationData.status_label;
            }

            // Update data attributes
            reservation.dataset.status = reservationData.status;

            // Update button
            const statusBtn = reservation.querySelector('.status-btn');
            if (statusBtn) {
                statusBtn.dataset.currentStatus = reservationData.status;
            }
        }
    }

    showLoading(show) {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.style.display = show ? 'flex' : 'none';
        }
    }

    showSuccess(message) {
        this.showToast(message, 'success');
    }

    showError(message) {
        this.showToast(message, 'error');
    }

    showToast(message, type) {
        // Create toast element
        const toast = document.createElement('div');
        toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        toast.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(toast);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (toast.parentNode) {
                toast.remove();
            }
        }, 5000);
    }
}

// Initialize calendar when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    new ReservationCalendar();
});