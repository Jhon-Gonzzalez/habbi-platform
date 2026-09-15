@extends('layouts.admin')

@section('titulo', 'Editar usuario')
@section('subtitulo', $usuario->email)

@section('contenido')
<form method="POST" action="{{ route('admin.usuarios.update', $usuario) }}" style="max-width:760px">
    @csrf
    @method('PUT')

    <div class="hb-card">
        <div class="hb-card__body">
            @include('admin.usuarios._form', ['usuario' => $usuario])
        </div>
        <div class="hb-card__footer hb-row hb-row--between">
            <a class="hb-btn hb-btn--ghost" href="{{ route('admin.usuarios.index') }}">Cancelar</a>
            <button class="hb-btn hb-btn--primary" type="submit">Guardar cambios</button>
        </div>
    </div>
</form>
@endsection
