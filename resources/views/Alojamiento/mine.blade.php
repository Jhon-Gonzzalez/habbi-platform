@extends('layouts.app')

@section('title','Mis alojamientos')

@section('content')

  <link rel="stylesheet" href="{{ asset('assets/styles.css') }}">
<div class="container results" style="margin-top:20px">
  <div class="results__topbar">
    <h2 style="margin:0">Mis alojamientos</h2>
    
  </div>

  @if(session('success'))
    <div class="alert-success">{{ session('success') }}</div>
  @endif

  @if($alojamientos->count())
    <div class="cards grid" id="mineCards" style="margin-top:14px">
      @foreach($alojamientos as $a)
        <article class="card">
          <div class="card__media">
            <img class="card__img"
                 src="{{ $a->cover_path ? Storage::url($a->cover_path) : asset('assets/img/placeholder.webp') }}"
                 alt="Portada de {{ $a->title }}">
            <span class="badge">{{ number_format($a->price,0,',','.') }} / {{ $a->price_period }}</span>
          </div>

          <div class="card__body">
            <div class="card__title">
              <h3>{{ $a->title }}</h3>
              <div class="price">{{ number_format($a->price,0,',','.') }} <small>/{{ $a->price_period }}</small></div>
            </div>

            <div class="meta">
              <span>{{ $a->type }}</span> ·
              <span>{{ $a->guests }} huésped(es)</span> ·
              <span>{{ $a->city }}@if($a->neighborhood), {{ $a->neighborhood }}@endif</span>
            </div>

            <div class="actions">
              <a class="btn" href="{{ route('alojamientos.show',$a->id) }}">Ver</a>
              <a class="btn" href="{{ route('alojamiento.edit',$a) }}">Editar</a>
              <form action="{{ route('alojamiento.destroy',$a) }}" method="POST" onsubmit="return confirm('¿Eliminar este alojamiento?')" style="margin:0">
                @csrf @method('DELETE')
                <button class="btn" type="submit">Eliminar</button>
              </form>
            </div>
          </div>
        </article>
      @endforeach
    </div>
<a class="btn btn-primary" href="{{ route('publicar') }}">Publicar nuevo</a>
    <div class="pagination">{{ $alojamientos->links() }}</div>
  @else
    <p class="muted">Aún no has publicado alojamientos.</p>
  @endif
</div>
@endsection



