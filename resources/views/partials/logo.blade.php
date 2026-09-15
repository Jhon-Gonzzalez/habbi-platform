{{-- Logotipo de HABBI. $tono: 'oscuro' (por defecto) o 'claro' para fondos oscuros. --}}
@php $tono = $tono ?? 'oscuro'; @endphp

<span class="hb-logo {{ $tono === 'claro' ? 'hb-logo--claro' : '' }}">
    <span class="hb-logo__mark" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none">
            <path d="M3.4 10.6 12 3.8l8.6 6.8" stroke="currentColor" stroke-width="2.2"
                  stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M5.6 11.9v7.4c0 .5.4.9.9.9h11c.5 0 .9-.4.9-.9v-7.4"
                  stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M10 21v-4.3c0-.5.4-.9.9-.9h2.2c.5 0 .9.4.9.9V21"
                  stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </span>
    <span class="hb-logo__word">Habbi</span>
</span>
