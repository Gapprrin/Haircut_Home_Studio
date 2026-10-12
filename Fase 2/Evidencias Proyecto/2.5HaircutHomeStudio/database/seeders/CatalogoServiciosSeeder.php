<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogoServiciosSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $categories = [
                'corte' => ['Cortes y lavado', 'ico-cat-corte.png'],
                'color' => ['Coloración e iluminación', 'ico-color.png'],
                'tratamientos' => ['Alisados y tratamientos', 'ico-tratamiento.png'],
            ];
            foreach ($categories as $slug => [$name, $image]) {
                DB::table('categorias')->updateOrInsert(['slug' => $slug], ['nombre' => $name, 'imagen' => $image]);
            }

            // Match equivalent services by name, never reuse an unrelated ID with reservations.
            $services = [
                ['corte', 'Corte de hombre', 8000, false, 60, 'ico-corte.png', []],
                ['corte', 'Corte de mujer', 8000, false, 60, 'ico-corte.png', ['Corte de damas']],
                ['corte', 'Lavado', 4000, false, 60, 'ico-brushing.png', []],
                ['corte', 'Lavado y secado', 8000, false, 60, 'ico-brushing.png', ['Lavado y brushing']],
                ['color', 'Tintura de retoque', 30000, false, 120, 'ico-retoque.png', ['Retoque de crecimiento']],
                ['color', 'Retoque de tintura + baño de color', 35000, false, 120, 'ico-retoque.png', []],
                ['color', 'Balayage', 80000, true, 300, 'ico-balayage.png', []],
                ['color', 'Babylights', 80000, true, 300, 'ico-babylight.png', ['Baby lights']],
                ['color', 'Mechas', 45000, true, 180, 'ico-mechas.png', []],
                ['color', 'Visos', 45000, true, 180, 'ico-visos.png', []],
                ['color', 'Reflejos', 40000, true, 180, 'ico-visos.png', []],
                ['color', 'Colores fantasía', 30000, true, 240, 'ico-fantasia.png', ['Color fantasía']],
                ['tratamientos', 'Liso permanente', 50000, false, 240, 'ico-liso.png', []],
                ['tratamientos', 'Alisado Brasil Cacau', 85000, true, 240, 'ico-liso.png', []],
                ['tratamientos', 'Botox capilar', 30000, false, 120, 'ico-botox.png', []],
                ['tratamientos', 'Masaje de queratina', 20000, false, 60, 'ico-masaje.png', ['Masaje capilar']],
                ['tratamientos', 'Tratamiento Olaplex', 35000, false, 60, 'ico-olaplex.png', ['Olaplex']],
            ];
            $activeIds = [];
            foreach ($services as [$slug, $name, $price, $from, $duration, $image, $aliases]) {
                $categoryId = DB::table('categorias')->where('slug', $slug)->value('id');
                $existing = DB::table('servicios')->where('categoria_id', $categoryId)->where('nombre', $name)->first()
                    ?? DB::table('servicios')->where('categoria_id', $categoryId)->whereIn('nombre', $aliases)->orderBy('id')->first();
                $data = [
                    'categoria_id' => $categoryId,
                    'nombre' => $name,
                    'precio' => $price,
                    'precio_desde' => $from,
                    'duracion_min' => $existing?->duracion_min ?? $duration,
                    'descripcion' => 'Bloque de agenda referencial; confirma la duración con el profesional.',
                    'imagen' => $existing?->imagen ?? $image,
                    'activo' => true,
                ];
                if ($existing) {
                    DB::table('servicios')->where('id', $existing->id)->update($data);
                    $activeIds[] = $existing->id;
                } else {
                    $activeIds[] = DB::table('servicios')->insertGetId($data);
                }
            }
            DB::table('servicios')->whereNotIn('id', $activeIds)->update(['activo' => false]);
        });
    }
}
