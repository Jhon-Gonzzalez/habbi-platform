@extends('layouts.admin')

@section('titulo', 'Campañas')
@section('subtitulo', 'Publicaciones con una campaña pagada en marcha')

@section('acciones')
    <a class="hb-btn hb-btn--ghost" href="{{ route('admin.tarifas.index') }}">Tarifas y cobro</a>
@endsection

@section('contenido')

<div class="hb-stats" style="margin-bottom:1.5rem">
    <div class="hb-stat">
        <div class="hb-stat__label">Recaudado este mes</div>
        <div class="hb-stat__value">${{ number_format($delMes, 0, ',', '.') }}</div>
    </div>
    <div class="hb-stat">
        <div class="hb-stat__label">Destacadas ahora</div>
        <div class="hb-stat__value">{{ $vigentes->count() }}</div>
    </div>
    <div class="hb-stat">
        <div class="hb-stat__label">Cobros registrados</div>
        <div class="hb-stat__value">{{ $cobros->total() }}</div>
    </div>
</div>

<div class="hb-admin-grid">

    <div>
        {{-- Vigentes --}}
        <div class="hb-card">
            <div class="hb-card__header"><h2 style="font-size:1.05rem">Destacadas ahora mismo</h2></div>

            @if ($vigentes->isEmpty())
                <div class="hb-card__body">
                    <p class="hb-muted hb-mb-0">Ninguna publicación está destacada en este momento.</p>
                </div>
            @else
                <table class="hb-table">
                    <thead>
                        <tr>
                            <th>Publicación</th>
                            <th>Arrendador</th>
                            <th>Le quedan</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($vigentes as $a)
                            <tr>
                                <td>
                                    <a href="{{ route('alojamientos.show', $a) }}">{{ $a->title }}</a>
                                    <div class="hb-muted hb-small">
                                        hasta el {{ $a->campana_hasta->translatedFormat('d \d\e F, H:i') }}
                                    </div>
                                </td>
                                <td>{{ $a->user->name }}</td>
                                <td><span class="hb-badge hb-badge--warning">{{ $a->diasDeCampanaRestantes() }} día(s)</span></td>
                                <td style="text-align:right">
                                    <form method="POST" action="{{ route('admin.campanas.destroy', $a) }}"
                                          data-confirmar="«{{ $a->title }}» dejará de salir de primeras. El cobro queda en el historial. ¿Continuar?">
                                        @csrf @method('DELETE')
                                        <button class="hb-btn hb-btn--danger hb-btn--sm" type="submit">Quitar</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        {{-- Historial de cobros --}}
        <div class="hb-card" style="margin-top:1.5rem">
            <div class="hb-card__header">
                <h2 style="font-size:1.05rem">Historial de cobros</h2>
                <span class="hb-badge hb-badge--muted">No se puede editar</span>
            </div>

            @if ($cobros->isEmpty())
                <div class="hb-card__body">
                    <p class="hb-muted hb-mb-0">Todavía no has registrado ningún cobro.</p>
                </div>
            @else
                <table class="hb-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Publicación</th>
                            <th>Días</th>
                            <th>Monto</th>
                            <th>Medio</th>
                            <th>Referencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cobros as $c)
                            <tr>
                                <td class="hb-small">{{ $c->created_at->format('d/m/Y') }}</td>
                                <td>{{ $c->alojamiento?->title ?? 'publicación eliminada' }}</td>
                                <td>{{ $c->dias }}</td>
                                <td><strong>{{ $c->montoFormateado() }}</strong></td>
                                <td>{{ $c->medio }}</td>
                                <td class="hb-muted hb-small">{{ $c->referencia ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="hb-card__footer">{{ $cobros->links() }}</div>
            @endif
        </div>
    </div>

    {{-- Registrar un cobro --}}
    <aside>
        <div class="hb-card">
            <div class="hb-card__header"><h2 style="font-size:1.05rem">Registrar un cobro</h2></div>
            <div class="hb-card__body">

                <p class="hb-small hb-muted">
                    El arrendador paga por Nequi, Daviplata o en efectivo y tú lo anotas
                    aquí. <strong>Comprueba que el dinero llegó</strong> antes de activar:
                    esto no verifica nada, solo deja constancia.
                </p>

                <form method="POST" action="{{ route('admin.campanas.store') }}">
                    @csrf

                    <div class="hb-field">
                        <label class="hb-label" for="alojamiento_id">Publicación</label>
                        <select class="hb-select @error('alojamiento_id') is-invalid @enderror"
                                id="alojamiento_id" name="alojamiento_id" required>
                            <option value="">Elige una…</option>
                            @foreach ($candidatas as $a)
                                <option value="{{ $a->id }}" @selected(old('alojamiento_id') == $a->id)>
                                    {{ \Illuminate\Support\Str::limit($a->title, 45) }} · {{ $a->user->name }}
                                </option>
                            @endforeach
                        </select>
                        <span class="hb-hint">
                            Solo salen las que están visibles en el buscador. Una publicación
                            pendiente o pausada no se puede destacar: no se ve.
                        </span>
                        @error('alojamiento_id') <span class="hb-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="hb-field">
                        <label class="hb-label" for="tarifa_id">Tarifa</label>
                        <select class="hb-select @error('tarifa_id') is-invalid @enderror"
                                id="tarifa_id" name="tarifa_id">
                            <option value="">A medida (escribo días y monto)</option>
                            @foreach ($tarifas as $t)
                                <option value="{{ $t->id }}" @selected(old('tarifa_id') == $t->id)>
                                    {{ $t->nombre }} · {{ $t->dias }} días · {{ $t->precioFormateado() }}
                                </option>
                            @endforeach
                        </select>
                        <span class="hb-hint">
                            Las tarifas se cambian en
                            <a href="{{ route('admin.tarifas.index') }}">Tarifas y cobro</a>.
                        </span>
                        @error('tarifa_id') <span class="hb-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="hb-fields-2">
                        <div class="hb-field">
                            <label class="hb-label" for="dias">Días</label>
                            <input class="hb-input @error('dias') is-invalid @enderror" type="number"
                                   id="dias" name="dias" value="{{ old('dias') }}" min="1" max="365"
                                   placeholder="de la tarifa">
                            @error('dias') <span class="hb-error">{{ $message }}</span> @enderror
                        </div>

                        <div class="hb-field">
                            <label class="hb-label" for="monto">Monto (COP)</label>
                            <input class="hb-input @error('monto') is-invalid @enderror" type="number"
                                   id="monto" name="monto" value="{{ old('monto') }}"
                                   min="0" max="1000000" step="1000" placeholder="de la tarifa">
                            @error('monto') <span class="hb-error">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <span class="hb-hint" style="margin-bottom:1rem;display:block">
                        Déjalos vacíos y se toman de la tarifa. Rellénalos para cobrar
                        algo distinto: un descuento, un pago a medias o una campaña de
                        regalo en temporada baja. Lo que escribas aquí queda copiado en
                        el cobro, así que subir el precio después no reescribe lo que ya
                        se pagó.
                    </span>

                    <div class="hb-field">
                        <label class="hb-label" for="medio">Medio de pago</label>
                        <select class="hb-select @error('medio') is-invalid @enderror" id="medio" name="medio" required>
                            @foreach (\App\Models\Campana::MEDIOS as $m)
                                <option value="{{ $m }}" @selected(old('medio') === $m)>{{ $m }}</option>
                            @endforeach
                        </select>
                        @error('medio') <span class="hb-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="hb-field">
                        <label class="hb-label" for="referencia">Referencia</label>
                        <input class="hb-input @error('referencia') is-invalid @enderror" type="text"
                               id="referencia" name="referencia" value="{{ old('referencia') }}"
                               maxlength="120" placeholder="Ej: Nequi M123456789">
                        <span class="hb-hint">Para poder cruzarlo con el extracto. No guardes números de cuenta.</span>
                        @error('referencia') <span class="hb-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="hb-field">
                        <label class="hb-label" for="nota">Nota</label>
                        <textarea class="hb-textarea" id="nota" name="nota" rows="2"
                                  maxlength="500" placeholder="Opcional">{{ old('nota') }}</textarea>
                    </div>

                    <button class="hb-btn hb-btn--primary hb-btn--block" type="submit">
                        Destacar y registrar el cobro
                    </button>
                </form>

                <span class="hb-hint" style="margin-top:1rem">
                    Si ya estaba destacada, los días se <strong>suman</strong> a los que le
                    quedaban. Renovar antes de tiempo nunca le quita días a nadie.
                </span>
            </div>
        </div>
    </aside>

</div>
@endsection
