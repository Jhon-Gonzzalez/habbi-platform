@extends('layouts.admin')

@section('titulo', 'Tarifas y cobro')
@section('subtitulo', 'Cuánto cuesta una campaña y a dónde se paga')

@section('acciones')
    <a class="hb-btn hb-btn--ghost" href="{{ route('admin.campanas.index') }}">Ver campañas</a>
@endsection

@section('contenido')
<div class="hb-admin-grid">

    <div>
        <div class="hb-card">
            <div class="hb-card__header">
                <h2 style="font-size:1.05rem">Tarifas</h2>
                <span class="hb-badge hb-badge--muted">Las cambias tú, sin tocar código</span>
            </div>

            @if ($tarifas->isEmpty())
                <div class="hb-card__body">
                    <p class="hb-muted">Todavía no hay ninguna tarifa.</p>
                    <p class="hb-hint hb-mb-0">
                        Un punto de partida razonable para Montería: una semana por
                        $12.000, quincena por $20.000 y mes por $35.000. Súbelos en
                        enero y julio-agosto, que es cuando los estudiantes buscan,
                        y bájalos el resto del año.
                    </p>
                </div>
            @else
                <table class="hb-table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Días</th>
                            <th>Precio</th>
                            <th>Al día</th>
                            <th>Estado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tarifas as $tarifa)
                            <tr>
                                <td>
                                    <strong>{{ $tarifa->nombre }}</strong>
                                    @if ($tarifa->nota)
                                        <div class="hb-muted hb-small">{{ $tarifa->nota }}</div>
                                    @endif
                                </td>
                                <td>{{ $tarifa->dias }}</td>
                                <td><strong>{{ $tarifa->precioFormateado() }}</strong></td>
                                <td class="hb-muted">${{ number_format($tarifa->precioPorDia(), 0, ',', '.') }}</td>
                                <td>
                                    @if ($tarifa->activa)
                                        <span class="hb-badge hb-badge--success">Publicada</span>
                                    @else
                                        <span class="hb-badge hb-badge--muted">Oculta</span>
                                    @endif
                                    @if ($tarifa->destacada)
                                        <span class="hb-badge hb-badge--warning">Resaltada</span>
                                    @endif
                                </td>
                                <td style="text-align:right">
                                    <details>
                                        <summary class="hb-btn hb-btn--ghost hb-btn--sm">Editar</summary>
                                        <form method="POST" style="margin-top:.75rem;text-align:left"
                                              action="{{ route('admin.tarifas.update', $tarifa) }}">
                                            @csrf @method('PUT')
                                            @include('admin.campanas._campos_tarifa', ['tarifa' => $tarifa])
                                            <button class="hb-btn hb-btn--primary hb-btn--sm hb-btn--block" type="submit">
                                                Guardar
                                            </button>
                                        </form>

                                        <form method="POST" style="margin-top:.5rem"
                                              action="{{ route('admin.tarifas.destroy', $tarifa) }}"
                                              data-confirmar="¿Eliminar la tarifa «{{ $tarifa->nombre }}»? Las campañas ya cobradas no cambian.">
                                            @csrf @method('DELETE')
                                            <button class="hb-btn hb-btn--danger hb-btn--sm hb-btn--block" type="submit">
                                                Eliminar
                                            </button>
                                        </form>
                                    </details>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="hb-card__footer">
                    <p class="hb-hint hb-mb-0">
                        Cambiar o borrar una tarifa <strong>no toca las campañas ya
                        cobradas</strong>: el monto y los días quedaron copiados en
                        cada cobro. Un precio nuevo no puede reescribir lo que alguien
                        ya pagó.
                    </p>
                </div>
            @endif
        </div>

        {{-- Datos de cobro --}}
        <div class="hb-card" style="margin-top:1.5rem">
            <div class="hb-card__header"><h2 style="font-size:1.05rem">¿A dónde se paga?</h2></div>
            <div class="hb-card__body">

                <form method="POST" action="{{ route('admin.cobro.guardar') }}">
                    @csrf

                    <div class="hb-fields-2">
                        <div class="hb-field">
                            <label class="hb-label" for="cobro_nequi">Nequi</label>
                            <input class="hb-input @error('cobro_nequi') is-invalid @enderror" type="text"
                                   id="cobro_nequi" name="cobro_nequi" maxlength="40"
                                   value="{{ old('cobro_nequi', $ajustes[\App\Models\Ajuste::COBRO_NEQUI] ?? '') }}"
                                   placeholder="300 000 0000">
                            @error('cobro_nequi') <span class="hb-error">{{ $message }}</span> @enderror
                        </div>

                        <div class="hb-field">
                            <label class="hb-label" for="cobro_daviplata">Daviplata</label>
                            <input class="hb-input" type="text" id="cobro_daviplata" name="cobro_daviplata"
                                   maxlength="40"
                                   value="{{ old('cobro_daviplata', $ajustes[\App\Models\Ajuste::COBRO_DAVIPLATA] ?? '') }}"
                                   placeholder="300 000 0000">
                        </div>
                    </div>

                    <div class="hb-field">
                        <label class="hb-label" for="cobro_correo">Correo al que escriben para pedirla</label>
                        <input class="hb-input @error('cobro_correo') is-invalid @enderror" type="email"
                               id="cobro_correo" name="cobro_correo" maxlength="120"
                               value="{{ old('cobro_correo', $ajustes[\App\Models\Ajuste::COBRO_CORREO] ?? '') }}">
                        @error('cobro_correo') <span class="hb-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="hb-field">
                        <label class="hb-label" for="cobro_instrucciones">Instrucciones (opcional)</label>
                        <textarea class="hb-textarea" id="cobro_instrucciones" name="cobro_instrucciones"
                                  rows="4" maxlength="600">{{ old('cobro_instrucciones', $ajustes[\App\Models\Ajuste::COBRO_INSTRUCCIONES] ?? '') }}</textarea>
                        <span class="hb-hint">
                            Si pones algo aquí, reemplaza los tres pasos que ve el arrendador.
                            Déjalo vacío para usar los de siempre.
                        </span>
                    </div>

                    <button class="hb-btn hb-btn--primary" type="submit">Guardar datos de cobro</button>
                </form>

                <span class="hb-hint" style="margin-top:1rem">
                    Estos números los ve cualquier arrendador con una publicación
                    aprobada. En la auditoría queda <strong>que alguien los cambió y
                    cuándo</strong>, pero no los números: copiarlos ahí solo los
                    duplicaría en un sitio más.
                </span>
            </div>
        </div>
    </div>

    {{-- Nueva tarifa --}}
    <aside>
        <div class="hb-card">
            <div class="hb-card__header"><h2 style="font-size:1.05rem">Nueva tarifa</h2></div>
            <div class="hb-card__body">
                <form method="POST" action="{{ route('admin.tarifas.store') }}">
                    @csrf
                    @include('admin.campanas._campos_tarifa', ['tarifa' => new \App\Models\TarifaCampana()])
                    <button class="hb-btn hb-btn--primary hb-btn--block" type="submit">Crear tarifa</button>
                </form>

                <span class="hb-hint" style="margin-top:1rem">
                    El arrendador elige el tiempo que quiere estar, y según eso paga.
                    Puedes tener tantas opciones como quieras: una de fin de semana
                    para quien tiene prisa, una de temporada para enero.
                </span>
            </div>
        </div>
    </aside>

</div>
@endsection
