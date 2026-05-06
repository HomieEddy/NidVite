document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.Echo === 'undefined') {
        console.warn('Echo not initialized');
        return;
    }

    window.Echo.private('admin.reports')
        .listen('.report.created', function (event) {
            console.log('New report received:', event);

            // Show browser notification if permitted
            if ('Notification' in window && Notification.permission === 'granted') {
                new Notification('NidVite — Nouveau signalement', {
                    body: event.address || 'Adresse non specifiee',
                    icon: '/images/icons/icon-192x192.png',
                });
            }

            // Show Filament notification if Filament is available
            if (typeof window.$wire !== 'undefined' && window.$wire.notify) {
                window.$wire.notify({
                    title: 'Nouveau signalement',
                    body: event.address || 'Adresse non specifiee',
                    icon: 'heroicon-o-bell',
                    status: 'warning',
                });
            }
        });
});
