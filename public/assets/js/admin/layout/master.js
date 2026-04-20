$(document).ready(function() {
    const $menuToggle = $('#menuToggle');
    const $sidebar = $('#sidebar');
    const $mainContent = $('#mainContent');
    const $sidebarOverlay = $('#sidebarOverlay');

    function toggleSidebar() {
        if ($(window).width() <= 768) {
            $sidebar.toggleClass('show');
            $sidebarOverlay.toggleClass('show');
        } else {
            $sidebar.toggleClass('collapsed');
            $mainContent.toggleClass('expanded');
        }
    }

    $menuToggle.on('click', toggleSidebar);

    $sidebarOverlay.on('click', function() {
        $sidebar.removeClass('show');
        $sidebarOverlay.removeClass('show');
    });


    // Handle window resize
    $(window).on('resize', function() {
        if ($(window).width() > 768) {
            $sidebar.removeClass('show');
            $sidebarOverlay.removeClass('show');
            $mainContent.removeClass('expanded');
        }
    });
});