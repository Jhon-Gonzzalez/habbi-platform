<?php

namespace App\Http\Controllers;

use App\Http\Requests\PerfilRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Edición del perfil propio. */
class PerfilController extends Controller
{
    private const CARPETA_FOTOS = 'perfiles';

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function edit(Request $request): View
    {
        return view('perfil.edit', ['usuario' => $request->user()]);
    }

    public function update(PerfilRequest $request): RedirectResponse
    {
        $usuario = $request->user();
        $datos   = $request->safe()->except(['foto', 'quitar_foto']);

        if ($request->boolean('quitar_foto')) {
            $this->borrarFoto($usuario->foto_perfil);
            $datos['foto_perfil'] = null;
        } elseif ($request->hasFile('foto')) {
            $this->borrarFoto($usuario->foto_perfil);
            $datos['foto_perfil'] = $request->file('foto')->store(self::CARPETA_FOTOS, 'public');
        }

        $usuario->update($datos);

        return redirect()
            ->route('perfil.edit')
            ->with('success', 'Perfil actualizado.');
    }

    private function borrarFoto(?string $ruta): void
    {
        if ($ruta && Storage::disk('public')->exists($ruta)) {
            Storage::disk('public')->delete($ruta);
        }
    }
}
