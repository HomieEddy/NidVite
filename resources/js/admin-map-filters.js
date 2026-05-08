function initAdminMapFilters() {
    const frame = document.getElementById('admin-reports-map-frame');
    const statusInput = document.getElementById('admin-map-status-filter');
    const neighborhoodInput = document.getElementById('admin-map-neighborhood-filter');
    const applyButton = document.getElementById('admin-map-apply-filter');
    const resetButton = document.getElementById('admin-map-reset-filter');

    if (!frame || !statusInput || !neighborhoodInput || !applyButton || !resetButton) {
        return;
    }

    const updateFrameSource = function () {
        const url = new URL(frame.dataset.baseSrc || frame.src, window.location.origin);
        url.searchParams.set('embed', '1');

        const status = statusInput.value.trim();
        const neighborhood = neighborhoodInput.value.trim();

        if (status !== '') {
            url.searchParams.set('status', status);
        } else {
            url.searchParams.delete('status');
        }

        if (neighborhood !== '') {
            url.searchParams.set('neighborhood', neighborhood);
        } else {
            url.searchParams.delete('neighborhood');
        }

        frame.src = url.toString();
    };

    applyButton.addEventListener('click', updateFrameSource);
    resetButton.addEventListener('click', function () {
        statusInput.value = '';
        neighborhoodInput.value = '';
        updateFrameSource();
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAdminMapFilters);
} else {
    initAdminMapFilters();
}
