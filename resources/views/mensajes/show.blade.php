@extends('layouts.app')

@section('titulo', 'Chat con ' . ($otro?->name ?? 'usuario'))

@section('contenido')
@php($yo = auth()->user())
@php($alojamiento = $conversacion->alojamiento)

<div style="max-width:760px;margin-inline:auto">

    {{-- Sobre qué se habla --}}
    <div class="hb-card">
        <div class="hb-card__body hb-row hb-row--between">
            <div class="hb-row">
                <x-avatar :usuario="$otro" tamano="44" />
                <div>
                    <strong>{{ $otro?->name ?? 'Cuenta eliminada' }}</strong>
                    <div class="hb-muted hb-small">
                        <a href="{{ route('alojamientos.show', $alojamiento) }}">{{ $alojamiento->title }}</a>
                        · {{ $alojamiento->precioFormateado() }}/{{ $alojamiento->price_period }}
                    </div>
                </div>
            </div>
            <a class="hb-btn hb-btn--ghost hb-btn--sm" href="{{ route('mensajes.index') }}">← Mensajes</a>
        </div>
    </div>

    {{-- Estado del acuerdo --}}
    @include('mensajes._acuerdo', ['conversacion' => $conversacion, 'yo' => $yo, 'alojamiento' => $alojamiento])

    {{-- Hilo --}}
    <div class="hb-card" style="margin-top:1.25rem">
        <div class="hb-card__body hb-chat" id="hilo">
            @forelse ($conversacion->mensajes as $m)
                @if ($m->es_del_sistema)
                    <div class="hb-chat__aviso">
                        <span>{{ $m->cuerpo }}</span>
                        <time class="hb-muted">{{ $m->created_at->format('d/m H:i') }}</time>
                    </div>
                @else
                    <div class="hb-chat__linea {{ $m->user_id === $yo->id ? 'is-mio' : '' }}">
                        <div class="hb-chat__burbuja">
                            {{ $m->cuerpo }}
                            <time>{{ $m->created_at->format('d/m H:i') }}</time>
                        </div>
                    </div>
                @endif
            @empty
                <p class="hb-muted hb-center hb-mb-0" style="padding:1.5rem 0">
                    Rompe el hielo: pregunta si sigue disponible, desde cuándo, y qué incluye el precio.
                </p>
            @endforelse
        </div>

        <div class="hb-card__footer">
            <form method="POST" action="{{ route('mensajes.enviar', $conversacion) }}">
                @csrf
                <div class="hb-field hb-mb-0">
                    <textarea class="hb-textarea @error('cuerpo') is-invalid @enderror"
                              name="cuerpo" rows="3" maxlength="2000" required
                              placeholder="Escribe tu mensaje…">{{ old('cuerpo') }}</textarea>
                    @error('cuerpo') <span class="hb-error">{{ $message }}</span> @enderror
                </div>
                <div class="hb-row hb-row--between" style="margin-top:.75rem">
                    <span class="hb-hint hb-mb-0">
                        Acuerda ver el lugar en persona antes de pagar nada.
                    </span>
                    <button class="hb-btn hb-btn--primary" type="submit">Enviar</button>
                </div>
            </form>
        </div>
    </div>

    <p class="hb-muted hb-small hb-center" style="margin-top:1rem">
        Hablar aquí deja constancia de lo que se promete.
        <strong>Nunca envíes dinero por adelantado</strong> a alguien que no conoces.
        Si algo huele mal, <a href="{{ route('reportes.create', $alojamiento) }}">repórtalo</a>.
    </p>

</div>
@endsection

@push('scripts')
<script>
    // Abrir el hilo abajo del todo, que es donde está lo último.
    var hilo = document.getElementById('hilo');
    if (hilo) hilo.scrollTop = hilo.scrollHeight;
</script>
@endpush
