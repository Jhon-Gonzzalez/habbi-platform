<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Aliado;
use App\Models\Auditoria;
use App\Services\Auditor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/** Alta, edición y baja de los negocios aliados que pagan por aparecer. */
class AliadoController extends Controller
{
    public function index(): View
    {
        $aliados = Aliado::orderByDesc('activo')->orderBy('orden')->orderBy('nombre')->get();

        return view('admin.aliados.index', [
            'aliados'   => $aliados,
            'vigentes'  => $aliados->filter->estaVigente(),
            'delMes'    => (int) Aliado::where('created_at', '>=', now()->startOfMonth())->sum('monto'),
            'clicsTotal' => (int) $aliados->sum('clics'),
        ]);
    }

    public function create(): View
    {
        return view('admin.aliados.form', ['aliado' => new Aliado([
            'desde' => today(),
            'hasta' => today()->addMonth(),
        ])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $aliado = Aliado::create($this->validado($request));

        $this->guardarLogo($request, $aliado);

        Auditor::registrar(
            Auditoria::ALIADO_GUARDADO,
            'Dio de alta al aliado «' . $aliado->nombre . '»',
            $aliado,
            ['monto' => $aliado->monto, 'hasta' => $aliado->hasta->toDateString()],
        );

        return redirect()->route('admin.aliados.index')
            ->with('success', '«' . $aliado->nombre . '» queda publicado hasta el '
                . $aliado->hasta->translatedFormat('d \d\e F') . '.');
    }

    public function edit(Aliado $aliado): View
    {
        return view('admin.aliados.form', compact('aliado'));
    }

    public function update(Request $request, Aliado $aliado): RedirectResponse
    {
        $aliado->update($this->validado($request));

        $this->guardarLogo($request, $aliado);

        Auditor::registrar(
            Auditoria::ALIADO_GUARDADO,
            'Editó al aliado «' . $aliado->nombre . '»',
            $aliado,
        );

        return redirect()->route('admin.aliados.index')->with('success', 'Aliado actualizado.');
    }

    public function destroy(Aliado $aliado): RedirectResponse
    {
        $nombre = $aliado->nombre;

        if ($aliado->logo_path) {
            Storage::disk('public')->delete($aliado->logo_path);
        }

        $aliado->delete();

        Auditor::registrar(Auditoria::ALIADO_BORRADO, 'Eliminó al aliado «' . $nombre . '»');

        return redirect()->route('admin.aliados.index')->with('success', '«' . $nombre . '» eliminado.');
    }

    /** @return array<string, mixed> */
    private function validado(Request $request): array
    {
        $datos = $request->validate([
            'nombre'      => ['required', 'string', 'max:120'],
            // Una línea, no un párrafo: el bloque de aliados es pequeño a
            // propósito y un texto largo lo convierte en un banner.
            'descripcion' => ['required', 'string', 'max:160'],
            // https obligatorio: enlazar a http desde una página segura
            // avisa al navegador y queda mal justo donde vendes confianza.
            'url'         => ['required', 'url', 'starts_with:https://', 'max:500'],
            'categoria'   => ['nullable', Rule::in(Aliado::CATEGORIAS)],
            'desde'       => ['required', 'date'],
            'hasta'       => ['required', 'date', 'after_or_equal:desde'],
            'monto'       => ['required', 'integer', 'min:0', 'max:10000000'],
            'orden'       => ['required', 'integer', 'min:0', 'max:999'],
            'activo'      => ['nullable', 'boolean'],
            'logo'        => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024'],
        ], [
            'url.starts_with' => 'El enlace tiene que empezar por https:// para no romper el candado del navegador.',
            'hasta.after_or_equal' => 'La fecha de fin no puede ser anterior a la de inicio.',
        ], [
            'nombre' => 'nombre', 'descripcion' => 'descripción', 'url' => 'enlace',
            'desde' => 'fecha de inicio', 'hasta' => 'fecha de fin', 'monto' => 'monto',
        ]);

        // Una casilla sin marcar no se envía, así que no llega a
        // validated() y «activo» se quedaría con su valor anterior.
        $datos['activo'] = $request->boolean('activo');

        return $datos;
    }

    private function guardarLogo(Request $request, Aliado $aliado): void
    {
        if (! $request->hasFile('logo')) {
            return;
        }

        // El anterior se borra: si no, cada cambio de logo deja basura en
        // el disco que nadie vuelve a mirar.
        if ($aliado->logo_path) {
            Storage::disk('public')->delete($aliado->logo_path);
        }

        $aliado->update([
            'logo_path' => $request->file('logo')->store('aliados', 'public'),
        ]);
    }
}
