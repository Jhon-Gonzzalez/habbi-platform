@extends('layouts.admin')

@section('titulo', 'Nuevo usuario')
@section('subtitulo', 'Crear una cuenta manualmente')

@section('contenido')
<form method="POST" action="{{ route('admin.usuarios.store') }}" style="max-width:760px">
    @csrf

    <div class="hb-card">
        <div class="hb-card__body">
            @include('admin.usuarios._form', ['usuario' => null])
        </div>
        <div class="hb-card__footer hb-row hb-row--between">
            <a class="hb-btn hb-btn--ghost" href="{{ route('admin.usuarios.index') }}">Cancelar</a>
            <button class="hb-btn hb-btn--primary" type="submit">Crear usuario</button>
        </div>
    </div>
</form>
@endsection
