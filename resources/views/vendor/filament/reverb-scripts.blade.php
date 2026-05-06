@if(auth()->check() && (auth()->user()->isAdmin() || auth()->user()->isManager()))
    @vite(['resources/js/echo.js', 'resources/js/reverb-listener.js'])
@endif
