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
                            {{-- El cuerpo va en su propio elemento y SIN saltos de
                                 línea dentro de la etiqueta: lleva white-space:
                                 pre-wrap para respetar los saltos que escriba la
                                 persona, y eso dibuja también la sangría del
                                 Blade. Con el texto suelto aquí, un «hola» salía
                                 en una burbuja de tres líneas de alto. --}}
                            @if ($m->cuerpo !== '')
                                <p class="hb-chat__texto">{{ $m->cuerpo }}</p>
                            @endif

                            @if ($m->tieneAdjunto())
                                @if ($m->adjuntoEsImagen())
                                    <a class="hb-chat__adjunto" target="_blank" rel="noopener"
                                       href="{{ route('mensajes.adjunto', [$conversacion, $m]) }}">
                                        <img src="{{ route('mensajes.adjunto', [$conversacion, $m]) }}"
                                             alt="Foto enviada por {{ $m->user_id === $yo->id ? 'usted' : ($otro?->name ?? 'la otra persona') }}"
                                             loading="lazy">
                                    </a>
                                @else
                                    <a class="hb-chat__archivo" target="_blank" rel="noopener"
                                       href="{{ route('mensajes.adjunto', [$conversacion, $m]) }}">
                                        📎 {{ \Illuminate\Support\Str::limit($m->adjunto_nombre, 32) }}
                                    </a>
                                @endif
                            @endif

                            <time>{{ $m->created_at->format('d/m H:i') }}</time>
                        </div>
                    </div>
                @endif
            @empty
                <p class="hb-muted hb-center hb-mb-0" style="padding:1.5rem 0">
                    Rompa el hielo: pregunta si sigue disponible, desde cuándo, y qué incluye el precio.
                </p>
            @endforelse
        </div>

        <div class="hb-card__footer">
            <form method="POST" action="{{ route('mensajes.enviar', $conversacion) }}"
                  enctype="multipart/form-data">
                @csrf
                <div class="hb-field hb-mb-0">
                    {{-- Sin «required»: mandar solo una foto del cuarto es un
                         mensaje válido. Lo comprueba el servidor, que exige
                         texto O archivo. --}}
                    <textarea class="hb-textarea @error('cuerpo') is-invalid @enderror"
                              name="cuerpo" rows="3" maxlength="2000"
                              aria-label="Escriba su mensaje"
                              placeholder="Escriba su mensaje…">{{ old('cuerpo') }}</textarea>
                    @error('cuerpo') <span class="hb-error">{{ $message }}</span> @enderror
                </div>

                <div class="hb-field hb-mb-0" style="margin-top:.6rem">
                    <label class="hb-label hb-small" for="adjunto">Adjuntar una foto o un PDF</label>
                    <input class="hb-input" id="adjunto" name="adjunto" type="file"
                           accept="image/jpeg,image/png,image/webp,application/pdf">
                    <span class="hb-hint">Hasta 5 MB. Solo lo ven ustedes dos.</span>
                    @error('adjunto') <span class="hb-error">{{ $message }}</span> @enderror
                </div>

                <div class="hb-row hb-row--between" style="margin-top:.75rem">
                    <span class="hb-hint hb-mb-0">
                        Acuerde ver el lugar en persona antes de pagar nada.
                    </span>
                    <button class="hb-btn hb-btn--primary" type="submit">Enviar</button>
                </div>
            </form>
        </div>
    </div>

    <p class="hb-muted hb-small hb-center" style="margin-top:1rem">
        Hablar aquí deja constancia de lo que se promete.
        <strong>Nunca envíe dinero por adelantado</strong> a alguien que no conozca.
        Si algo huele mal, <a href="{{ route('reportes.create', $alojamiento) }}">repórtelo</a>.
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
