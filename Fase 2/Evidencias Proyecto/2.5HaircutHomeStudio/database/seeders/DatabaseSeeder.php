<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::table('usuarios')->insert([
            ['id' => 1, 'nombre' => 'Administrador', 'email' => 'admin@admin.cl', 'password' => '$2y$10$VoCY9sMcCNHglwe.P/2DfumxW29mcf5OtcWHN/WVSuccjEPCmJmXa', 'rol' => 'admin', 'es_invitado' => false, 'origen' => 'email', 'ai_sin_limite' => false],
            ['id' => 2, 'nombre' => 'Ricardo', 'email' => 'ricardo@me.com', 'password' => '$2y$10$EbBQ8mLf7lekhA.01lr6zeRX/..gby5ug6zb51oi5uiMQ9/DaP9Sq', 'rol' => 'peluquero', 'es_invitado' => false, 'origen' => 'email', 'ai_sin_limite' => false],
            ['id' => 3, 'nombre' => 'Cliente Demo', 'email' => 'cliente@haircut.cl', 'password' => '$2y$10$3tAusVgmF5O2dgde4RgTjukQ1LDM.7TKjpE.OtECw0CATUo5DDAli', 'rol' => 'cliente', 'es_invitado' => false, 'origen' => 'email', 'ai_sin_limite' => false],
            ['id' => 4, 'nombre' => 'Ana', 'email' => 'ana@haircut.cl', 'password' => '$2y$10$fj.pjC1kYGdi5vAOuKcts.XmZqJPaZrYrdZ4Gtxn8mUzlwKO4LbPK', 'rol' => 'cliente', 'es_invitado' => false, 'origen' => 'email', 'ai_sin_limite' => false],
            ['id' => 5, 'nombre' => 'Camila Rojas', 'email' => 'camila@correo.cl', 'password' => '$2y$10$3tAusVgmF5O2dgde4RgTjukQ1LDM.7TKjpE.OtECw0CATUo5DDAli', 'rol' => 'cliente', 'es_invitado' => false, 'origen' => 'email', 'ai_sin_limite' => false],
            ['id' => 6, 'nombre' => 'Diego Pérez', 'email' => 'diego@correo.cl', 'password' => '$2y$10$3tAusVgmF5O2dgde4RgTjukQ1LDM.7TKjpE.OtECw0CATUo5DDAli', 'rol' => 'cliente', 'es_invitado' => false, 'origen' => 'email', 'ai_sin_limite' => false],
            ['id' => 7, 'nombre' => 'Fernanda López', 'email' => 'fernanda@correo.cl', 'password' => '$2y$10$3tAusVgmF5O2dgde4RgTjukQ1LDM.7TKjpE.OtECw0CATUo5DDAli', 'rol' => 'cliente', 'es_invitado' => false, 'origen' => 'email', 'ai_sin_limite' => false],
            ['id' => 8, 'nombre' => 'Admin Test Imagen', 'email' => 'admin.imagen@haircut.cl', 'password' => Hash::make('ImagenTest2026!'), 'rol' => 'admin', 'es_invitado' => false, 'origen' => 'email', 'ai_sin_limite' => true],
        ]);

        DB::table('categorias')->insert([
            ['id' => 1, 'nombre' => 'Cortes', 'slug' => 'corte', 'imagen' => 'ico-cat-corte.png'],
            ['id' => 2, 'nombre' => 'Color', 'slug' => 'color', 'imagen' => 'ico-color.png'],
            ['id' => 3, 'nombre' => 'Tratamientos', 'slug' => 'tratamientos', 'imagen' => 'ico-tratamiento.png'],
        ]);

        $this->call(CatalogoServiciosSeeder::class);

        DB::table('productos')->insert([
            ['categoria_id' => 2, 'nombre' => 'Shampoo de color', 'descripcion' => 'Cuidado para cabello teñido', 'precio' => 12990, 'activo' => true, 'orden' => 1],
            ['categoria_id' => 1, 'nombre' => 'Acondicionador nutritivo', 'descripcion' => 'Hidratación y brillo', 'precio' => 11990, 'activo' => true, 'orden' => 2],
            ['categoria_id' => 3, 'nombre' => 'Tratamiento Olaplex', 'descripcion' => 'Reparación de fibra capilar', 'precio' => 24990, 'activo' => true, 'orden' => 3],
            ['categoria_id' => 1, 'nombre' => 'Leave-in protector', 'descripcion' => 'Protección térmica diaria', 'precio' => 9990, 'activo' => true, 'orden' => 4],
            ['categoria_id' => 3, 'nombre' => 'Aceite capilar', 'descripcion' => 'Nutrición y anti-frizz', 'precio' => 14990, 'activo' => true, 'orden' => 5],
            ['categoria_id' => 3, 'nombre' => 'Ampolleta de reparación', 'descripcion' => 'Dosis de tratamiento intensivo', 'precio' => 7990, 'activo' => true, 'orden' => 6],
        ]);

        DB::table('configuracion')->insert([
            'id' => 1,
            'hora_inicio' => '10:30:00',
            'hora_fin' => '19:30:00',
            'dias_atencion' => '2,3,4,5,6',
        ]);

        DB::table('meses_visibles')->insert([
            ['configuracion_id' => 1, 'anio' => 2026, 'mes' => 8],
            ['configuracion_id' => 1, 'anio' => 2026, 'mes' => 9],
        ]);

        DB::table('dias_off')->insert([
            ['configuracion_id' => 1, 'fecha' => '2026-08-17'],
            ['configuracion_id' => 1, 'fecha' => '2026-08-25'],
        ]);

        DB::table('reservas')->insert([
            ['usuario_id' => 4, 'servicio_id' => 1, 'fecha' => '2026-09-15', 'hora' => '10:30:00', 'lugar' => 'salon', 'estado' => 'confirmada'],
            ['usuario_id' => 5, 'servicio_id' => 5, 'fecha' => '2026-09-16', 'hora' => '11:30:00', 'lugar' => 'salon', 'estado' => 'pendiente'],
            ['usuario_id' => 6, 'servicio_id' => 12, 'fecha' => '2026-09-17', 'hora' => '10:30:00', 'lugar' => 'domicilio', 'estado' => 'pendiente'],
            ['usuario_id' => 4, 'servicio_id' => 1, 'fecha' => '2026-08-12', 'hora' => '11:30:00', 'lugar' => 'salon', 'estado' => 'realizada'],
            ['usuario_id' => 7, 'servicio_id' => 2, 'fecha' => '2026-09-10', 'hora' => '15:30:00', 'lugar' => 'salon', 'estado' => 'cancelada'],
            ['usuario_id' => 5, 'servicio_id' => 10, 'fecha' => '2026-09-08', 'hora' => '10:30:00', 'lugar' => 'salon', 'estado' => 'no_asistio'],
        ]);
    }
}
