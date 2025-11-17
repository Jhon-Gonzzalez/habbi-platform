@extends('layouts.admin')

@section('content')
<div class="row">
    <h1>usuario: {{$usuario->name}}</h1>
</div>

<hr>

<div class="row">
    <div class="col-md-6">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Llene los datos</h3>
            </div>
            <div class="card-body">
            
                    @csrf
                    <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="name">Nombre del usuario</label> 
                            <p>{{$usuario->name}}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="name">Email</label>
                            <p>{{$usuario->email}}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                          <a href="{{url('admin.usuarios')}}"class = "btn btn-secondary">Cancelar</a>
                     
                        </div>
                    </div>
                </div>


                
            

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
