<?php

namespace App\Http\Controllers;
use App\Models\User;  // <-- IMPORTA EL MODELO
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as RulesPassword;
use Illuminate\Support\Facades\Resquest;



class UsuarioController extends Controller
{
   public function index() {
        $usuarios = User::all();
        return view('admin.usuarios.index', compact('usuarios'));
    }

    public function create() {
        return view(view:'admin.usuarios.create');
    }

    public function store(Request $request){
    // $datos = request()->all();
    //return response()->json($datos);

    $request->validate([
        'name'=>'required|max:250',
        'email'=>'required|max:250|unique:users',
        'password'=>'required|max:250|confirmed',
    ]);

    $usuario = new User();
    $usuario->name = $request->name;
    $usuario->email = $request->email;
    $usuario->password = Hash::make($request['password']);
    $usuario->save();
    return redirect()->route('admin.usuarios.index')
    ->with('success', 'Usuario creado exitosamente.')
    ->with('icono', 'success');
}

public function show($id) {
    $usuario = User::findorfail($id);   
    return view('admin.usuarios.show',compact('usuario'));
}

public function edit($id) {
    $usuario = User::findorfail($id);   
    return view('admin.usuarios.edit',compact('usuario'));
}
public function update(Request $request, $id){
    $usuario = User::find($id);
    $request->validate([    
        'name'=>'required|max:250',
        'email'=>'required|max:250|unique:users,email,'.$usuario->id,
        'password'=>'nullable|max:250|confirmed',
    ]);
    $usuario = User::find($id);   
    $usuario->name = $request->name;
    $usuario->email = $request->email;
    if($request->filled('password') ){
        $usuario->password = Hash::make($request['password']);
    }
    $usuario->save();
    return redirect()->route('admin.usuarios.index')
    ->with('success', 'Usuario actualizado exitosamente.')
    ->with('icono', 'success');

}

public function confirmDelete($id) {
    $usuario = User::findorfail($id);   
    return view('admin.usuarios.delete',compact('usuario'));
}

public function destroy($id) {
    user::destroy($id); 
    return redirect()->route('admin.usuarios.index')
    ->with('success', 'Usuario eliminado exitosamente.')
    ->with('icono', 'success');
}

}
