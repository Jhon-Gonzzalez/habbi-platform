@props([
    'usuario',
    'tamano' => 40,
    'enlace' => false,
])

@php
    $foto      = $usuario->fotoUrl();
    $esEnlace  = $enlace && ($usuario->esArrendador() || $usuario->isAdmin());
    $etiqueta  = $esEnlace ? 'a' : 'span';
    $atributos = $esEnlace ? ['href' => route('arrendadores.perfil', $usuario)] : [];
@endphp

<{{ $etiqueta }}
    {{ $attributes->merge($atributos)->class(['hb-avatar']) }}
    style="width:{{ $tamano }}px;height:{{ $tamano }}px;font-size:{{ max(10, (int) ($tamano * 0.36)) }}px"
    title="{{ $usuario->name }}">

    @if ($foto)
        <img src="{{ $foto }}" alt="{{ $usuario->name }}" loading="lazy">
    @else
        {{ $usuario->initials() }}
    @endif

    @if ($usuario->estaVerificado() && $tamano >= 40)
        <span class="hb-avatar__sello" title="Cuenta verificada" aria-label="Verificado">✓</span>
    @endif
</{{ $etiqueta }}>
