@extends('layouts.app')

@section('titulo', 'La página caducó')

@section('contenido')
<div style="max-width:620px;margin-inline:auto">
    <div class="hb-card">
        <div class="hb-card__body hb-center">
            <div style="font-size:3rem;line-height:1" aria-hidden="true">⏱</div>

            <h1 style="margin-top:1rem;font-size:1.5rem">El formulario caducó</h1>

            <p class="hb-muted">
                Pasó demasiado tiempo con la página abierta y, por seguridad,
                dejó de ser válida. No se guardó nada. Vuelve atrás, recarga y
                envíalo otra vez.
            </p>

            <div class="hb-row hb-center" style="gap:.6rem;margin-top:1.5rem">
                <a class="hb-btn hb-btn--primary" href="{{ url()->previous() }}">Volver e intentar de nuevo</a>
            </div>
        </div>
    </div>
</div>
@endsection
