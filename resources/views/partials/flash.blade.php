{{-- Mensajes de sesión y errores de validación --}}
@if (session('success'))
    <div class="hb-alert hb-alert--success" role="status">
        <span class="hb-alert__icon">✓</span>
        <div>{{ session('success') }}</div>
        <button type="button" class="hb-alert__close" data-cerrar-alerta aria-label="Cerrar">&times;</button>
    </div>
@endif

@if (session('error'))
    <div class="hb-alert hb-alert--error" role="alert">
        <span class="hb-alert__icon">!</span>
        <div>{{ session('error') }}</div>
        <button type="button" class="hb-alert__close" data-cerrar-alerta aria-label="Cerrar">&times;</button>
    </div>
@endif

@if ($errors->any())
    <div class="hb-alert hb-alert--error" role="alert">
        <span class="hb-alert__icon">!</span>
        <div>
            <strong>Revisa los siguientes campos:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
