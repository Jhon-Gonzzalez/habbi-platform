@extends('layouts.admin')

@section('titulo', 'Solicitud de ' . $solicitud->user->name)
@section('subtitulo', 'Enviada el ' . $solicitud->created_at->translatedFormat('d \d\e F \d\e Y'))

@section('acciones')
    <a class="hb-btn hb-btn--ghost" href="{{ route('admin.solicitudes.index') }}">Volver</a>
@endsection

@section('contenido')
<div class="hb-admin-grid">

    <div>
        {{-- Quién la envía --}}
        <div class="hb-card">
            <div class="hb-card__header"><h2 style="font-size:1.05rem">Solicitante</h2></div>
            <div class="hb-card__body">
                <div class="hb-row" style="margin-bottom:1.25rem">
                    <span class="hb-table__avatar" style="width:48px;height:48px;font-size:.95rem">
                        {{ $solicitud->user->initials() }}
                    </span>
                    <div>
                        <strong style="font-size:1.05rem">{{ $solicitud->user->name }}</strong>
                        <div class="hb-muted hb-small">{{ $solicitud->user->email }}</div>
                    </div>
                </div>

                <div class="hb-specs">
                    <div class="hb-spec">
                        <div class="hb-spec__label">Teléfono</div>
                        <div class="hb-spec__value">{{ $solicitud->telefono }}</div>
                    </div>
                    <div class="hb-spec">
                        <div class="hb-spec__label">Cuenta creada</div>
                        <div class="hb-spec__value">{{ $solicitud->user->created_at->translatedFormat('d M Y') }}</div>
                    </div>
                    <div class="hb-spec">
                        <div class="hb-spec__label">Rol actual</div>
                        <div class="hb-spec__value">{{ $solicitud->user->nombreRol() }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Lo que cuenta --}}
        <div class="hb-card" style="margin-top:1.5rem">
            <div class="hb-card__header"><h2 style="font-size:1.05rem">Qué dice</h2></div>
            <div class="hb-card__body">
                <p style="white-space:pre-line;margin:0">{{ $solicitud->mensaje }}</p>
            </div>
        </div>

        {{-- Documentos --}}
        <div class="hb-card" style="margin-top:1.5rem">
            <div class="hb-card__header">
                <h2 style="font-size:1.05rem">Documentos</h2>
                <span class="hb-badge hb-badge--muted">Solo visibles aquí</span>
            </div>
            <div class="hb-card__body">
                @if ($solicitud->tieneDocumentos())
                    <div class="hb-row">
                        <a class="hb-btn hb-btn--ghost" target="_blank" rel="noopener"
                           href="{{ route('arrendador.documento', [$solicitud, 'documento']) }}">
                            Ver documento de identidad
                        </a>
                        <a class="hb-btn hb-btn--ghost" target="_blank" rel="noopener"
                           href="{{ route('arrendador.documento', [$solicitud, 'prueba']) }}">
                            Ver prueba del inmueble
                        </a>
                    </div>
                    <span class="hb-hint" style="margin-top:.75rem">
                        Están fuera de la carpeta pública y no hay ninguna dirección que
                        lleve a ellos sin pasar por esta comprobación de permisos.
                        <strong>Se borrarán solos en cuanto tomes la decisión.</strong>
                    </span>
                @else
                    <p class="hb-muted hb-mb-0">
                        <strong>Ya se eliminaron</strong>, el
                        {{ $solicitud->documentos_borrados_en?->format('d/m/Y \a \l\a\s H:i') }}.
                    </p>
                    <span class="hb-hint" style="margin-top:.5rem">
                        Los documentos se borran al resolver la solicitud. Se piden para
                        comprobar una cosa concreta y, comprobada, guardarlos solo añade
                        riesgo: una cédula escaneada que se filtra sirve para suplantar a
                        alguien. Queda constancia de quién revisó y cuándo en la auditoría.
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- Decisión --}}
    <aside>
        @if ($solicitud->estaPendiente())
            <div class="hb-card">
                <div class="hb-card__header"><h2 style="font-size:1.05rem">Decisión</h2></div>
                <div class="hb-card__body">

                    <p class="hb-small hb-muted">
                        Comprueba que el nombre del documento coincide con el de la cuenta
                        y que la dirección de la prueba es de Montería.
                    </p>

                    <form method="POST" action="{{ route('admin.solicitudes.aprobar', $solicitud) }}"
                          data-confirmar="Al aprobar, {{ $solicitud->user->name }} podrá publicar y aparecerá como verificado. ¿Continuar?">
                        @csrf
                        <button class="hb-btn hb-btn--success hb-btn--block" type="submit">Aprobar</button>
                    </form>

                    <hr style="border:0;border-top:1px solid var(--hb-border);margin:1.25rem 0">

                    <form method="POST" action="{{ route('admin.solicitudes.rechazar', $solicitud) }}">
                        @csrf
                        <div class="hb-field">
                            <label class="hb-label" for="motivo">Motivo del rechazo</label>
                            <textarea class="hb-textarea @error('motivo') is-invalid @enderror" id="motivo"
                                      name="motivo" rows="4" minlength="15" maxlength="500"
                                      placeholder="Ej: La foto de la cédula está borrosa y no se lee el número.">{{ old('motivo') }}</textarea>
                            <span class="hb-hint">La persona lo va a leer. Sé concreto para que pueda corregirlo.</span>
                            @error('motivo') <span class="hb-error">{{ $message }}</span> @enderror
                        </div>
                        <button class="hb-btn hb-btn--danger hb-btn--block" type="submit">Rechazar</button>
                    </form>

                </div>
            </div>
        @else
            <div class="hb-card">
                <div class="hb-card__body hb-center">
                    <span class="hb-badge {{ $solicitud->fueAprobada() ? 'hb-badge--success' : 'hb-badge--danger' }}">
                        {{ $solicitud->nombreEstado() }}
                    </span>
                    <p class="hb-muted hb-small" style="margin-top:1rem">
                        Revisada por {{ $solicitud->revisor?->name ?? 'un administrador' }}
                        {{ $solicitud->revisada_en?->diffForHumans() }}
                    </p>
                    @if ($solicitud->motivo_rechazo)
                        <p class="hb-small" style="text-align:left;margin-top:1rem">
                            <strong>Motivo:</strong> {{ $solicitud->motivo_rechazo }}
                        </p>
                    @endif

                    @if ($solicitud->fueAprobada() && $solicitud->user->esArrendador())
                        <form method="POST" action="{{ route('admin.usuarios.revocar', $solicitud->user) }}"
                              style="margin-top:1.25rem"
                              data-confirmar="{{ $solicitud->user->name }} dejará de poder publicar y perderá el sello. ¿Continuar?">
                            @csrf
                            <button class="hb-btn hb-btn--danger hb-btn--sm hb-btn--block" type="submit">
                                Revocar condición de arrendador
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @endif
    </aside>

</div>
@endsection
