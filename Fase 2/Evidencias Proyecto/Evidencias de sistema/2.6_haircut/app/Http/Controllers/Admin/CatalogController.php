<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Producto;
use App\Services\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function __construct(private readonly MediaService $media) {}

    public function index(): View
    {
        return view('admin.catalogo.index', [
            'productos' => Producto::query()->with('categoria')->orderBy('orden')->orderBy('id')->get(),
            'categorias' => Categoria::query()->orderBy('id')->get(),
            'proximoOrden' => ((int) Producto::query()->max('orden')) + 1,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);
        $datos['imagen'] = $this->media->store($request->file('imagen'), 'prod_');
        $datos['activo'] = true;
        $datos['orden'] = ((int) Producto::query()->max('orden')) + 1;
        Producto::create($datos);

        return back()->with('success', 'Producto agregado al catálogo.');
    }

    public function update(Request $request, Producto $producto): RedirectResponse
    {
        $datos = $this->validar($request, true);
        $datos['activo'] = $request->boolean('activo');
        $datos['orden'] = max(1, (int) ($datos['orden'] ?? $producto->orden));
        $imagenAnterior = $producto->imagen;
        $imagenNueva = $this->media->store($request->file('imagen'), 'prod_');
        if ($imagenNueva) {
            $datos['imagen'] = $imagenNueva;
        }
        $producto->update($datos);

        if ($imagenNueva) {
            $this->media->delete($imagenAnterior);
        }

        return back()->with('success', 'Producto actualizado.');
    }

    public function destroy(Producto $producto): RedirectResponse
    {
        $imagen = $producto->imagen;
        $producto->delete();
        $this->media->delete($imagen);

        return back()->with('success', 'Producto eliminado. El número de orden queda libre para el siguiente.');
    }

    /** @return array<string, mixed> */
    private function validar(Request $request, bool $edicion = false): array
    {
        return $request->validate([
            'categoria_id' => ['required', 'integer', Rule::exists('categorias', 'id')],
            'nombre' => ['required', 'string', 'max:120'],
            'descripcion' => ['nullable', 'string'],
            'precio' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'orden' => [$edicion ? 'nullable' : 'sometimes', 'integer', 'min:1'],
            'imagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:3072'],
        ]);
    }
}
