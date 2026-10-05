<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        return view('productos.index', [
            'productos' => Producto::query()->where('activo', true)->orderBy('orden')->orderBy('id')->get(),
        ]);
    }
}
