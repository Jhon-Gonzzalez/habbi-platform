@extends('layouts.admin')

@section('content')
<div class="row">
        <h1>borrar usuario: {{$usuario->name}}</h1>
</div>

<hr>

<div class="row">
    <div class="col-md-6">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">estas seguro de eliminar?</h3>
            </div>
            <div class="card-body">
                <form action="{{url('/admin/usuarios',$usuario->id)}}" method ="POST">
                    @csrf
                    @method('DELETE')
                    <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="name">Nombre del usuario</label> 
                            <input type="text" name="name" value="{{$usuario->name}}"class="form-control" disabled>
                            @error('name')
                            <small class="text-danger">{{$message}}</small>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="name">Email</label>
                            <input type="text" name="email" value="{{$usuario->email}}" class="form-control" disabled>
                            @error('email')
                            <small class="text-danger">{{$message}}</small>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                          <a href="{{url('admin.usuarios')}}"class = "btn btn-secondary">Cancelar</a>
                        <button type="submit"class = "btn btn-danger">eliminar</button>
                        </div>
                    </div>
                </div>


                
                </form>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
