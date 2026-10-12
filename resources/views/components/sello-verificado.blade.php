@props(['usuario', 'texto' => true])

@if ($usuario->estaVerificado())
    <span class="hb-verificado" title="HABBI comprobó su identidad y la existencia del inmueble">
        <svg viewBox="0 0 24 24" aria-hidden="true" width="14" height="14">
            <path fill="currentColor" d="M12 2 9.8 4.2 6.7 3.9 6 7 2.9 7.7 3.9 10.7 2 13l2.3 2.2-.4 3.1 3.1.7.7 3.1 3-1 2.3 1.9 2.2-1.9 3.1.4.7-3.1 3.1-.7-1-3L22 13l-1.9-2.3.4-3.1-3.1-.7-.7-3.1-3 1z"/>
            <path fill="#fff" d="m10.9 15.4-2.8-2.8 1.1-1.1 1.7 1.7 4-4 1.1 1.1z"/>
        </svg>
        @if ($texto) Verificado @endif
    </span>
@elseif ($usuario->esArrendador())
    <span class="hb-badge hb-badge--muted" title="Aún no ha completado la verificación">Sin verificar</span>
@endif
