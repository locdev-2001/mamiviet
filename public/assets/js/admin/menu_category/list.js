$(document).ready(function() {
    // Get translations from global window object (will be set in blade template)
    var translations = window.menuCategoryTranslations || {};

    var table = $('#categoriesTable').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: '/api/admin/menu-categories',
            type: 'GET',
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('admin_token'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            dataSrc: function(json) {
                if (json.data) {
                    return json.data;
                }
                return [];
            },
            error: function(xhr, error, code) {
                console.error('Error loading data:', xhr.responseText);
                if (xhr.status === 401) {
                    alert(translations.unauthorized_access || 'Unauthorized access');
                    window.location.href = '/admin/login';
                }
            }
        },
        columns: [
            {
                data: 'id',
                render: function(data, type, row) {
                    return '<span class="badge bg-secondary">' + data + '</span>';
                },
                orderable: true,
                searchable: true
            },
            {
                data: 'image',
                render: function(data, type, row) {
                    if (data) {
                        return '<img src="' + data + '" class="img-thumbnail" style="max-width: 50px; max-height: 50px;">';
                    }
                    return '<span class="text-muted"><i class="fas fa-image"></i></span>';
                },
                orderable: false,
                searchable: false
            },
            {
                data: 'name',
                render: function(data, type, row) {
                    let nameHtml = '<strong>' + (data || '') + '</strong>';
                    if (row.parent) {
                        nameHtml = '<i class="fas fa-level-up-alt text-muted me-1"></i>' + nameHtml;
                        nameHtml += '<br><small class="text-muted">Parent: ' + row.parent.name + '</small>';
                    } else if (row.is_parent) {
                        nameHtml = '<i class="fas fa-folder text-primary me-1"></i>' + nameHtml;
                    }
                    return nameHtml;
                }
            },
            {
                data: 'description',
                render: function(data, type, row) {
                    if (!data) return '<span class="text-muted">' + (translations.no_description || 'No description') + '</span>';
                    return data.length > 100 ? data.substring(0, 100) + '...' : data;
                }
            },
            {
                data: 'is_active',
                render: function(data, type, row) {
                    if (data == 1) {
                        return '<span class="badge bg-success">' + (translations.active || 'Active') + '</span>';
                    } else {
                        return '<span class="badge bg-secondary">' + (translations.inactive || 'Inactive') + '</span>';
                    }
                }
            },
            {
                data: 'position',
                render: function(data, type, row) {
                    return '<span class="badge bg-info">' + (data || 0) + '</span>';
                }
            },
            {
                data: null,
                render: function(data, type, row) {
                    if (row.parent_id) {
                        return '<span class="badge bg-secondary"><i class="fas fa-level-up-alt me-1"></i>Sub-category</span>';
                    } else if (row.is_parent) {
                        return '<span class="badge bg-primary"><i class="fas fa-folder me-1"></i>Parent</span>';
                    } else {
                        return '<span class="badge bg-info"><i class="fas fa-tag me-1"></i>Category</span>';
                    }
                },
                orderable: false,
                searchable: false
            },
            {
                data: null,
                render: function(data, type, row) {
                    return '<div class="btn-group" role="group">' +
                        '<button type="button" class="btn btn-sm btn-outline-primary edit-btn" ' +
                        'data-id="' + row.id + '" title="' + (translations.edit || 'Edit') + '">' +
                        '<i class="fas fa-edit"></i>' +
                        '</button>' +
                        '<button type="button" class="btn btn-sm btn-outline-warning position-btn" ' +
                        'data-id="' + row.id + '" title="' + (translations.update_position || 'Update Position') + '">' +
                        '<i class="fas fa-arrows-alt-v"></i>' +
                        '</button>' +
                        '<button type="button" class="btn btn-sm btn-outline-danger delete-btn" ' +
                        'data-id="' + row.id + '" title="' + (translations.delete || 'Delete') + '">' +
                        '<i class="fas fa-trash"></i>' +
                        '</button>' +
                        '</div>';
                },
                orderable: false,
                searchable: false
            }
        ],
        order: [[4, 'asc']], // Sort by position
        pageLength: 20,
        lengthChange: true,
        searching: true,
        ordering: true,
        info: true,
        autoWidth: false,
        responsive: true,
        stateSave: true,
        stateDuration: 60 * 60 * 24, // Save state for 24 hours
        language: {
            processing: translations.processing || "Processing...",
            search: (translations.search || "Search") + ":",
            lengthMenu: (translations.show || "Show") + " _MENU_ " + (translations.entries || "entries"),
            info: (translations.showing || "Showing") + " _START_ " + (translations.to || "to") + " _END_ " + (translations.of || "of") + " _TOTAL_ " + (translations.entries || "entries"),
            infoEmpty: (translations.showing || "Showing") + " 0 " + (translations.to || "to") + " 0 " + (translations.of || "of") + " 0 " + (translations.entries || "entries"),
            infoFiltered: "(" + (translations.filtered_from || "filtered from") + " _MAX_ " + (translations.total_entries || "total entries") + ")",
            infoPostFix: "",
            thousands: ",",
            loadingRecords: (translations.loading || "Loading") + "...",
            zeroRecords: translations.no_data || "No data available!",
            emptyTable: translations.no_data || "No data available!",
            paginate: {
                first: translations.first || "First",
                previous: translations.previous || "Previous",
                next: translations.next || "Next",
                last: translations.last || "Last"
            }
        }
    });

    // Event handlers for action buttons
    $('#categoriesTable').on('click', '.edit-btn', function() {
        var id = $(this).data('id');

        // Redirect to edit page
        window.location.href = '/admin/menu-categories/edit/' + id;
    });

    $('#categoriesTable').on('click', '.position-btn', function() {
        var id = $(this).data('id');
        var button = $(this);
        var row = table.row(button.closest('tr')).data();

        // Store category data for modal
        window.currentCategoryData = {
            id: id,
            name: row.name,
            position: row.position,
            button: button
        };

        // Populate modal with category data
        $('#categoryName').val(row.name);
        $('#currentPosition').val(row.position || 0);
        $('#newPosition').val(row.position || 0);

        // Show modal
        $('#updatePositionModal').modal('show');
    });

    $('#categoriesTable').on('click', '.delete-btn', function() {
        var id = $(this).data('id');
        var button = $(this);

        if (confirm(translations.confirm_delete || 'Are you sure you want to delete this item?')) {
            // Disable button during request
            button.prop('disabled', true);
            button.html('<i class="fas fa-spinner fa-spin"></i>');

            $.ajax({
                url: '/api/admin/menu-categories/' + id,
                type: 'DELETE',
                headers: {
                    'Authorization': 'Bearer ' + localStorage.getItem('admin_token'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                success: function(response) {
                    // Show success message
                    alert(translations.delete_success || 'Category deleted successfully!');

                    // Reload table data
                    table.ajax.reload();
                },
                error: function(xhr, status, error) {
                    console.error('Error deleting category:', xhr.responseText);

                    var errorMessage = translations.delete_error || 'Error deleting category!';

                    // Try to get error message from response
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }

                    alert(errorMessage);

                    // Handle unauthorized access
                    if (xhr.status === 401) {
                        alert(translations.unauthorized_access || 'Unauthorized access');
                        window.location.href = '/admin/login';
                    }
                },
                complete: function() {
                    // Re-enable button
                    button.prop('disabled', false);
                    button.html('<i class="fas fa-trash"></i>');
                }
            });
        }
    });

    // Handle modal submit
    $('#confirmUpdatePosition').on('click', function() {
        var newPosition = $('#newPosition').val();
        var categoryData = window.currentCategoryData;

        if (!categoryData) {
            console.error('No category data found');
            return;
        }

        // Validate position
        newPosition = parseInt(newPosition);
        if (isNaN(newPosition) || newPosition < 0) {
            alert(translations.invalid_position || 'Please enter a valid positive number for position');
            return;
        }

        var button = categoryData.button;
        var confirmButton = $(this);

        // Disable buttons during request
        button.prop('disabled', true);
        button.html('<i class="fas fa-spinner fa-spin"></i>');
        confirmButton.prop('disabled', true);
        confirmButton.html('<i class="fas fa-spinner fa-spin me-2"></i>Updating...');

        $.ajax({
            url: '/api/admin/menu-categories/' + categoryData.id + '/position',
            type: 'PUT',
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('admin_token'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            data: JSON.stringify({
                position: newPosition
            }),
            success: function(response) {
                // Hide modal
                $('#updatePositionModal').modal('hide');

                // Show success message
                alert(translations.position_update_success || 'Position updated successfully!');

                // Reload table data to reflect changes
                table.ajax.reload();
            },
            error: function(xhr, status, error) {
                console.error('Error updating position:', xhr.responseText);

                var errorMessage = translations.position_update_error || 'Error updating position!';

                // Try to get error message from response
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }

                alert(errorMessage);

                // Handle unauthorized access
                if (xhr.status === 401) {
                    alert(translations.unauthorized_access || 'Unauthorized access');
                    window.location.href = '/admin/login';
                }
            },
            complete: function() {
                // Re-enable buttons
                button.prop('disabled', false);
                button.html('<i class="fas fa-arrows-alt-v"></i>');
                confirmButton.prop('disabled', false);
                confirmButton.html('<i class="fas fa-save me-2"></i>' + (translations.update || 'Update'));
            }
        });
    });

    // Reset modal when closed
    $('#updatePositionModal').on('hidden.bs.modal', function() {
        $('#updatePositionForm')[0].reset();
        window.currentCategoryData = null;
    });

    $('#addCategoryBtn').on('click', function() {
        console.log('Add new category');
        // TODO: Implement add functionality

        // Redirect to edit page
        window.location.href = '/admin/menu-categories/create/';
    });
});
