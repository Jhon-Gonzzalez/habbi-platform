@extends('layouts.admin')

@section('content')
<div class="row">
    <h1>modificar usuario: {{$usuario->name}}</h1>
</div>

<hr>

<div class="row">
    <div class="col-md-6">
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title">Llene los datos</h3>
            </div>
            <div class="card-body">
                <form action="{{url('admin/usuarios',$usuario->id)}}" method ="POST">
                    @csrf
                    @method('PUT')
                    <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="name">Nombre del usuario</label> <b>*</b>
                            <input type="text" name="name" value="{{$usuario->name}}"class="form-control" required>
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
                            <label for="name">Email</label><b>*</b>
                            <input type="text" name="email" value="{{$usuario->email}}" class="form-control" required>
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
                            <label for="name">Password</label>
                            <input type="password" name="password" class="form-control">
                            @error('password')
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
                            <label for="name">password verificacion</label>
                            <input type="password" name="password_confirmation" class="form-control">
                            @error('password_confirmation')
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
                        <button type="submit"class = "btn btn-success">Actualizar</button>
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
