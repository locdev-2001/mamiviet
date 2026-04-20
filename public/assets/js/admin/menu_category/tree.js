$(document).ready(function() {
    // Get translations from global window object
    var translations = window.menuCategoryTranslations || {};
    var sortableInstances = [];

    // Load initial tree data
    loadCategoryTree();

    function loadCategoryTree() {
        $('#loadingSpinner').show();
        $('#categoryTree').hide();
        $('#emptyState').hide();

        $.ajax({
            url: '/api/admin/menu-categories',
            type: 'GET',
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('admin_token'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            success: function(response) {
                if (response.data && response.data.length > 0) {
                    buildTree(response.data);
                    $('#categoryTree').show();
                } else {
                    $('#emptyState').show();
                }
            },
            error: function(xhr, error, code) {
                console.error('Error loading categories:', xhr.responseText);
                showError(translations.loading_error || 'Error loading categories');
                
                if (xhr.status === 401) {
                    alert(translations.unauthorized_access || 'Unauthorized access');
                    window.location.href = '/admin/login';
                }
            },
            complete: function() {
                $('#loadingSpinner').hide();
            }
        });
    }

    function buildTree(categories) {
        // Organize categories into hierarchical structure
        const categoryMap = {};
        const rootCategories = [];

        // First pass: create a map of all categories
        categories.forEach(category => {
            categoryMap[category.id] = {
                ...category,
                children: []
            };
        });

        // Second pass: organize into hierarchy
        categories.forEach(category => {
            if (category.parent_id && categoryMap[category.parent_id]) {
                categoryMap[category.parent_id].children.push(categoryMap[category.id]);
            } else {
                rootCategories.push(categoryMap[category.id]);
            }
        });

        // Sort by position
        sortCategoriesByPosition(rootCategories);

        // Build HTML
        const treeHtml = buildTreeHTML(rootCategories, 0);
        $('#categoryTree').html(treeHtml);

        // Initialize tree state (collapse all children initially)
        initializeTreeState();

        // Initialize drag and drop
        initializeDragDrop();

        // Bind event handlers
        bindEventHandlers();
        
        // Debug: Count toggle icons
        console.log('Total toggle icons found:', $('.toggle-icon').length);
        console.log('Total category items:', $('.category-item').length);
    }

    function initializeTreeState() {
        // Expand all children initially and set correct toggle icons
        $('.category-children').show();
        $('.toggle-icon').removeClass('fa-chevron-right').addClass('fa-chevron-down');
        
        console.log('Tree state initialized - all expanded');
    }

    function sortCategoriesByPosition(categories) {
        categories.sort((a, b) => (a.position || 0) - (b.position || 0));
        categories.forEach(category => {
            if (category.children.length > 0) {
                sortCategoriesByPosition(category.children);
            }
        });
    }

    function buildTreeHTML(categories, level) {
        if (!categories || categories.length === 0) return '';

        let html = '<ul class="category-list' + (level === 0 ? ' root-level' : '') + '">';

        categories.forEach(category => {
            const hasChildren = category.children && category.children.length > 0;
            const imageHtml = category.image 
                ? `<img src="${category.image}" class="category-image" alt="${category.name}">` 
                : '<i class="fas fa-image category-image-placeholder"></i>';

            console.log(`Category: ${category.name}, hasChildren: ${hasChildren}, children count: ${category.children ? category.children.length : 0}`);

            html += `
                <li class="category-item" data-id="${category.id}" data-parent-id="${category.parent_id || ''}" data-position="${category.position || 0}">
                    <div class="category-content">
                        <div class="category-toggle">
                            ${hasChildren ? '<i class="fas fa-chevron-down toggle-icon" title="Click to collapse"></i>' : '<i class="fas fa-grip-vertical drag-handle"></i>'}
                        </div>
                        <div class="category-info">
                            <div class="category-image-container">
                                ${imageHtml}
                            </div>
                            <div class="category-details">
                                <div class="category-name">
                                    <strong>${category.name}</strong>
                                    <span class="badge ${category.is_active ? 'bg-success' : 'bg-secondary'} ms-2">
                                        ${category.is_active ? (translations.active || 'Active') : (translations.inactive || 'Inactive')}
                                    </span>
                                </div>
                                <div class="category-meta">
                                    <small class="text-muted">
                                        ID: ${category.id} | Position: ${category.position || 0}
                                        ${category.description ? ` | ${category.description.substring(0, 50)}${category.description.length > 50 ? '...' : ''}` : ''}
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="category-actions">
                            <button type="button" class="btn btn-sm btn-outline-primary edit-btn" data-id="${category.id}" title="${translations.edit || 'Edit'}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger delete-btn" data-id="${category.id}" title="${translations.delete || 'Delete'}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    ${hasChildren ? '<div class="category-children">' + buildTreeHTML(category.children, level + 1) + '</div>' : ''}
                </li>
            `;
        });

        html += '</ul>';
        return html;
    }

    function initializeDragDrop() {
        // Destroy existing instances
        sortableInstances.forEach(instance => {
            if (instance.destroy) {
                instance.destroy();
            }
        });
        sortableInstances = [];

        // Initialize sortable for each category list
        $('.category-list').each(function() {
            const sortable = new Sortable(this, {
                group: 'category-tree',
                animation: 150,
                handle: '.category-content',
                filter: '.toggle-icon, .category-actions, .category-actions *',
                preventOnFilter: false,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                onStart: function(evt) {
                    $('body').addClass('dragging');
                },
                onEnd: function(evt) {
                    $('body').removeClass('dragging');
                    handleDragEnd(evt);
                },
                onFilter: function(evt) {
                    // Prevent dragging when clicking on toggle or action buttons
                    evt.stopPropagation();
                    return false;
                }
            });
            sortableInstances.push(sortable);
        });
    }

    function handleDragEnd(evt) {
        const item = $(evt.item);
        const categoryId = item.data('id');
        const newParentList = $(evt.to);
        const newIndex = evt.newIndex;

        // Determine new parent ID
        let newParentId = null;
        const parentItem = newParentList.closest('.category-item');
        if (parentItem.length > 0) {
            newParentId = parentItem.data('id');
        }

        // Calculate new position
        const siblings = newParentList.children('.category-item');
        let newPosition = 0;

        if (siblings.length > 1) {
            if (newIndex === 0) {
                // First position
                const nextSibling = siblings.eq(1);
                const nextPosition = parseInt(nextSibling.data('position')) || 0;
                newPosition = Math.max(0, nextPosition - 1);
            } else if (newIndex === siblings.length - 1) {
                // Last position
                const prevSibling = siblings.eq(newIndex - 1);
                const prevPosition = parseInt(prevSibling.data('position')) || 0;
                newPosition = prevPosition + 1;
            } else {
                // Middle position
                const prevSibling = siblings.eq(newIndex - 1);
                const nextSibling = siblings.eq(newIndex + 1);
                const prevPosition = parseInt(prevSibling.data('position')) || 0;
                const nextPosition = parseInt(nextSibling.data('position')) || 0;
                newPosition = Math.floor((prevPosition + nextPosition) / 2);
            }
        }

        // Update category structure
        updateCategoryStructure(categoryId, newParentId, newPosition);
    }

    function updateCategoryStructure(categoryId, newParentId, newPosition) {
        const data = {
            parent_id: newParentId,
            position: newPosition
        };

        $.ajax({
            url: `/api/admin/menu-categories/${categoryId}/structure`,
            type: 'PUT',
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('admin_token'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            data: JSON.stringify(data),
            success: function(response) {
                showSuccess(translations.structure_update_success || 'Category structure updated successfully!');
                // Reload tree to reflect changes
                loadCategoryTree();
            },
            error: function(xhr, status, error) {
                console.error('Error updating structure:', xhr.responseText);
                showError(translations.structure_update_error || 'Error updating category structure!');
                
                // Reload tree to reset positions
                loadCategoryTree();

                if (xhr.status === 401) {
                    alert(translations.unauthorized_access || 'Unauthorized access');
                    window.location.href = '/admin/login';
                }
            }
        });
    }

    function bindEventHandlers() {
        // Toggle expand/collapse - use direct binding after content is loaded
        $('.toggle-icon').off('click').on('click', function(e) {
            e.stopPropagation();
            e.preventDefault();
            
            console.log('Toggle clicked!', this); // Debug log
            
            const $toggleIcon = $(this);
            const $categoryItem = $toggleIcon.closest('.category-item');
            const $children = $categoryItem.find('> .category-children');
            
            console.log('Category item:', $categoryItem);
            console.log('Children found:', $children.length);
            console.log('Children HTML:', $children.html());
            
            if ($children.length === 0) {
                console.log('No children container found');
                return;
            }
            
            if ($children.is(':visible')) {
                $children.slideUp(200);
                $toggleIcon.removeClass('fa-chevron-down').addClass('fa-chevron-right');
                console.log('Collapsed');
            } else {
                $children.slideDown(200);
                $toggleIcon.removeClass('fa-chevron-right').addClass('fa-chevron-down');
                console.log('Expanded');
            }
        });

        // Edit button
        $('.edit-btn').off('click').on('click', function(e) {
            e.stopPropagation();
            const id = $(this).data('id');
            window.location.href = '/admin/menu-categories/edit/' + id;
        });

        // Delete button
        $('.delete-btn').off('click').on('click', function(e) {
            e.stopPropagation();
            const id = $(this).data('id');
            const button = $(this);

            if (confirm(translations.confirm_delete || 'Are you sure you want to delete this category?')) {
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
                        showSuccess(translations.delete_success || 'Category deleted successfully!');
                        loadCategoryTree();
                    },
                    error: function(xhr, status, error) {
                        console.error('Error deleting category:', xhr.responseText);
                        showError(translations.delete_error || 'Error deleting category!');

                        if (xhr.status === 401) {
                            alert(translations.unauthorized_access || 'Unauthorized access');
                            window.location.href = '/admin/login';
                        }
                    },
                    complete: function() {
                        button.prop('disabled', false);
                        button.html('<i class="fas fa-trash"></i>');
                    }
                });
            }
        });
    }

    // Initialize expand all toggle state
    $('#expandAllToggle').prop('checked', true);
    $('#expandAllToggle').next('label').text(translations.collapse_all || 'Collapse All');

    // Expand/Collapse all toggle
    $('#expandAllToggle').on('change', function() {
        const isChecked = $(this).is(':checked');
        const label = $(this).next('label');
        
        if (isChecked) {
            $('.category-children').slideDown(200);
            $('.toggle-icon').removeClass('fa-chevron-right').addClass('fa-chevron-down');
            label.text(translations.collapse_all || 'Collapse All');
        } else {
            $('.category-children').slideUp(200);
            $('.toggle-icon').removeClass('fa-chevron-down').addClass('fa-chevron-right');
            label.text(translations.expand_all || 'Expand All');
        }
    });

    function showSuccess(message) {
        // Create and show success toast/alert
        const alertHtml = `
            <div class="alert alert-success alert-dismissible fade show position-fixed" 
                 style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        $('body').append(alertHtml);
        
        // Auto remove after 3 seconds
        setTimeout(() => {
            $('.alert-success').alert('close');
        }, 3000);
    }

    function showError(message) {
        // Create and show error toast/alert
        const alertHtml = `
            <div class="alert alert-danger alert-dismissible fade show position-fixed" 
                 style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        $('body').append(alertHtml);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            $('.alert-danger').alert('close');
        }, 5000);
    }
});