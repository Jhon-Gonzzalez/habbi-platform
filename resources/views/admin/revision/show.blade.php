@extends('layouts.admin')

@section('titulo', 'Revisar publicación')
@section('subtitulo', $alojamiento->title)

@section('contenido')
<div class="hb-admin-grid">
    <div>
        <div class="hb-card">
            <div class="hb-card__body">
                <img src="{{ $alojamiento->coverUrl() }}" alt="Foto de {{ $alojamiento->title }}"
                     style="width:100%;border-radius:var(--hb-radius);margin-bottom:1.25rem">

                <h2 style="font-size:1.2rem">{{ $alojamiento->title }}</h2>

                <div class="hb-specs" style="margin:1rem 0">
                    <div class="hb-spec">
                        <div class="hb-spec__label">Precio</div>
                        <div class="hb-spec__value">{{ $alojamiento->precioFormateado() }}/{{ $alojamiento->price_period }}</div>
                    </div>
                    <div class="hb-spec">
                        <div class="hb-spec__label">Tipo</div>
                        <div class="hb-spec__value">{{ $alojamiento->type }}</div>
                    </div>
                    <div class="hb-spec">
                        <div class="hb-spec__label">Plazas</div>
                        <div class="hb-spec__value">{{ $alojamiento->cupos }}</div>
                    </div>
                    <div class="hb-spec">
                        <div class="hb-spec__label">Fotos</div>
                        <div class="hb-spec__value">{{ count($alojamiento->photos ?? []) }}</div>
                    </div>
                </div>

                <p class="hb-muted">
                    {{ $alojamiento->city }}@if ($alojamiento->neighborhood), {{ $alojamiento->neighborhood }}@endif
                    @if ($alojamiento->address) · {{ $alojamiento->address }} @endif
                </p>

                <p style="white-space:pre-wrap">{{ $alojamiento->description }}</p>

                <p>
                    <a class="hb-btn hb-btn--ghost hb-btn--sm" target="_blank" rel="noopener"
                       href="{{ $alojamiento->urlMapa() }}">Ver la dirección en el mapa</a>
                </p>
            </div>
        </div>
    </div>

    <aside>
        <div class="hb-card">
            <div class="hb-card__header"><h2 style="font-size:1.05rem">Quién publica</h2></div>
            <div class="hb-card__body">
                <strong>{{ $alojamiento->user->name }}</strong>
                <div class="hb-muted hb-small">
                    {{ \App\Support\Texto::ocultarCorreo($alojamiento->user->email) }}
                </div>
                <p class="hb-small" style="margin-top:.6rem">
                    @if ($alojamiento->user->estaVerificado())
                        <span class="hb-badge hb-badge--success">Arrendador verificado</span>
                    @else
                        <span class="hb-badge hb-badge--warning">Sin verificar</span>
                    @endif
                    · {{ $alojamiento->user->alojamientos()->count() }} publicaciones
                </p>
            </div>
        </div>

        @if ($alojamiento->estaPendiente() || $alojamiento->fueRechazada())
            <div class="hb-card" style="margin-top:1.5rem">
                <div class="hb-card__header"><h2 style="font-size:1.05rem">Decisión</h2></div>
                <div class="hb-card__body">
                    <p class="hb-small hb-muted">
                        Comprueba que la dirección exista, que las fotos correspondan al
                        texto y que el precio sea creíble para la zona.
                    </p>

                    <form method="POST" action="{{ route('admin.revision.aprobar', $alojamiento) }}"
                          data-confirmar="Al aprobar, «{{ $alojamiento->title }}» aparecerá en el buscador. ¿Continuar?">
                        @csrf
                        <button class="hb-btn hb-btn--success hb-btn--block" type="submit">Aprobar</button>
                    </form>

                    <hr style="border:0;border-top:1px solid var(--hb-border);margin:1.25rem 0">

                    <form method="POST" action="{{ route('admin.revision.rechazar', $alojamiento) }}">
                        @csrf
                        <div class="hb-field">
                            <label class="hb-label" for="motivo">Motivo del rechazo</label>
                            <textarea class="hb-textarea @error('motivo') is-invalid @enderror"
                                      id="motivo" name="motivo" rows="4" required
                                      placeholder="El arrendador va a leer esto. Dile qué corregir.">{{ old('motivo') }}</textarea>
                            @error('motivo') <span class="hb-error">{{ $message }}</span> @enderror
                        </div>
                        <button class="hb-btn hb-btn--danger hb-btn--block" type="submit">Rechazar</button>
                    </form>
                </div>
            </div>
        @else
            <div class="hb-card" style="margin-top:1.5rem">
                <div class="hb-card__body">
                    <span class="hb-badge hb-badge--success">{{ $alojamiento->nombreEstado() }}</span>
                    @if ($alojamiento->revisada_en)
                        <p class="hb-muted hb-small" style="margin-top:.6rem">
                            Revisada {{ $alojamiento->revisada_en->diffForHumans() }}
                            @if ($alojamiento->revisada_por)
                                por {{ \App\Models\User::find($alojamiento->revisada_por)?->name ?? 'una cuenta eliminada' }}
                            @endif
                        </p>
                    @endif
                </div>
            </div>
        @endif

        <p style="margin-top:1rem">
            <a class="hb-btn hb-btn--ghost hb-btn--sm" href="{{ route('admin.revision.index') }}">← Volver a la cola</a>
        </p>
    </aside>
</div>
@endsection
