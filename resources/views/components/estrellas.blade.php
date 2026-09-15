@props([
    'valor' => 0,
    'total' => null,
    'grande' => false,
])

@php
    $valor     = (float) $valor;
    $completas = (int) floor($valor);
    $media     = ($valor - $completas) >= 0.25 && ($valor - $completas) < 0.75;

    if (($valor - $completas) >= 0.75) {
        $completas++;
        $media = false;
    }

    $vacias = max(0, 5 - $completas - ($media ? 1 : 0));
    $id     = 'media-' . uniqid();
    $path   = 'M12 .587l3.668 7.568L24 9.748l-6 5.848L19.335 24 12 19.897 4.665 24 6 15.596l-6-5.848 8.332-1.593z';
@endphp

<span {{ $attributes->class(['hb-stars', 'hb-stars--lg' => $grande]) }}
      role="img"
      aria-label="{{ $valor > 0 ? number_format($valor, 1) . ' de 5 estrellas' : 'Sin calificaciones' }}">

    @for ($i = 0; $i < $completas; $i++)
        <svg viewBox="0 0 24 24" fill="#FFB020" aria-hidden="true"><path d="{{ $path }}"/></svg>
    @endfor

    @if ($media)
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <defs>
                <linearGradient id="{{ $id }}">
                    <stop offset="50%" stop-color="#FFB020"/>
                    <stop offset="50%" stop-color="#D5DBE5"/>
                </linearGradient>
            </defs>
            <path fill="url(#{{ $id }})" d="{{ $path }}"/>
        </svg>
    @endif

    @for ($i = 0; $i < $vacias; $i++)
        <svg viewBox="0 0 24 24" fill="#D5DBE5" aria-hidden="true"><path d="{{ $path }}"/></svg>
    @endfor

    @if (!is_null($total))
        <span class="hb-stars__count">{{ $valor > 0 ? number_format($valor, 1) : '—' }} ({{ $total }})</span>
    @endif
</span>
