<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::table('usuarios')->insert([
            ['id' => 1, 'nombre' => 'Administrador', 'email' => 'admin@admin.cl', 'password' => '$2y$10$VoCY9sMcCNHglwe.P/2DfumxW29mcf5OtcWHN/WVSuccjEPCmJmXa', 'rol' => 'admin', 'es_invitado' => false, 'origen' => 'email'],
            ['id' => 2, 'nombre' => 'Ricardo', 'email' => 'ricardo@me.com', 'password' => '$2y$10$EbBQ8mLf7lekhA.01lr6zeRX/..gby5ug6zb51oi5uiMQ9/DaP9Sq', 'rol' => 'peluquero', 'es_invitado' => false, 'origen' => 'email'],
            ['id' => 3, 'nombre' => 'Cliente Demo', 'email' => 'cliente@haircut.cl', 'password' => '$2y$10$3tAusVgmF5O2dgde4RgTjukQ1LDM.7TKjpE.OtECw0CATUo5DDAli', 'rol' => 'cliente', 'es_invitado' => false, 'origen' => 'email'],
            ['id' => 4, 'nombre' => 'Ana', 'email' => 'ana@haircut.cl', 'password' => '$2y$10$fj.pjC1kYGdi5vAOuKcts.XmZqJPaZrYrdZ4Gtxn8mUzlwKO4LbPK', 'rol' => 'cliente', 'es_invitado' => false, 'origen' => 'email'],
            ['id' => 5, 'nombre' => 'Camila Rojas', 'email' => 'camila@correo.cl', 'password' => '$2y$10$3tAusVgmF5O2dgde4RgTjukQ1LDM.7TKjpE.OtECw0CATUo5DDAli', 'rol' => 'cliente', 'es_invitado' => false, 'origen' => 'email'],
            ['id' => 6, 'nombre' => 'Diego Pérez', 'email' => 'diego@correo.cl', 'password' => '$2y$10$3tAusVgmF5O2dgde4RgTjukQ1LDM.7TKjpE.OtECw0CATUo5DDAli', 'rol' => 'cliente', 'es_invitado' => false, 'origen' => 'email'],
            ['id' => 7, 'nombre' => 'Fernanda López', 'email' => 'fernanda@correo.cl', 'password' => '$2y$10$3tAusVgmF5O2dgde4RgTjukQ1LDM.7TKjpE.OtECw0CATUo5DDAli', 'rol' => 'cliente', 'es_invitado' => false, 'origen' => 'email'],
        ]);

        DB::table('categorias')->insert([
            ['id' => 1, 'nombre' => 'Cortes', 'slug' => 'corte', 'imagen' => 'ico-cat-corte.png'],
            ['id' => 2, 'nombre' => 'Color', 'slug' => 'color', 'imagen' => 'ico-color.png'],
            ['id' => 3, 'nombre' => 'Tratamientos', 'slug' => 'tratamientos', 'imagen' => 'ico-tratamiento.png'],
        ]);

        DB::table('servicios')->insert([
            ['id' => 1, 'categoria_id' => 1, 'nombre' => 'Corte de damas', 'descripcion' => '15 a 30 minutos. En la agenda se reserva 1 hora.', 'duracion_min' => 60, 'precio' => 15000, 'imagen' => 'ico-corte.png', 'activo' => true],
            ['id' => 2, 'categoria_id' => 1, 'nombre' => 'Corte infantil', 'descripcion' => 'En la agenda se reserva 1 hora.', 'duracion_min' => 60, 'precio' => 12000, 'imagen' => 'ico-infantil.png', 'activo' => true],
            ['id' => 3, 'categoria_id' => 1, 'nombre' => 'Lavado y brushing', 'descripcion' => '25 a 40 minutos. En la agenda se reserva 1 hora.', 'duracion_min' => 60, 'precio' => 16000, 'imagen' => 'ico-brushing.png', 'activo' => true],
            ['id' => 4, 'categoria_id' => 1, 'nombre' => 'Peinado', 'descripcion' => '20 a 40 minutos. En la agenda se reserva 1 hora.', 'duracion_min' => 60, 'precio' => 15000, 'imagen' => 'ico-peinado.png', 'activo' => true],
            ['id' => 5, 'categoria_id' => 1, 'nombre' => 'Corte + lavado', 'descripcion' => 'A veces también incluye peinado. En la agenda se reserva 1 hora.', 'duracion_min' => 60, 'precio' => 18000, 'imagen' => 'ico-corte-lavado.png', 'activo' => true],
            ['id' => 6, 'categoria_id' => 2, 'nombre' => 'Cobertura de canas', 'descripcion' => '1 a 1,5 horas.', 'duracion_min' => 120, 'precio' => 28000, 'imagen' => 'ico-cobertura.png', 'activo' => true],
            ['id' => 7, 'categoria_id' => 2, 'nombre' => 'Retoque de crecimiento', 'descripcion' => '1 a 1,5 horas.', 'duracion_min' => 120, 'precio' => 22000, 'imagen' => 'ico-retoque.png', 'activo' => true],
            ['id' => 8, 'categoria_id' => 2, 'nombre' => 'Visos', 'descripcion' => '2 a 3 horas.', 'duracion_min' => 180, 'precio' => 35000, 'imagen' => 'ico-visos.png', 'activo' => true],
            ['id' => 9, 'categoria_id' => 2, 'nombre' => 'Mechas', 'descripcion' => '2 a 3 horas.', 'duracion_min' => 180, 'precio' => 35000, 'imagen' => 'ico-mechas.png', 'activo' => true],
            ['id' => 10, 'categoria_id' => 2, 'nombre' => 'Balayage', 'descripcion' => '3 a 5 horas, a veces hasta 6. El tiempo varía con el cabello.', 'duracion_min' => 300, 'precio' => 45000, 'imagen' => 'ico-balayage.png', 'activo' => true],
            ['id' => 11, 'categoria_id' => 2, 'nombre' => 'Baby lights', 'descripcion' => '4 a 5 horas, a veces hasta 6.', 'duracion_min' => 300, 'precio' => 50000, 'imagen' => 'ico-babylight.png', 'activo' => true],
            ['id' => 12, 'categoria_id' => 2, 'nombre' => 'Color fantasía', 'descripcion' => 'En la agenda se reservan 4 horas.', 'duracion_min' => 240, 'precio' => 35000, 'imagen' => 'ico-fantasia.png', 'activo' => true],
            ['id' => 13, 'categoria_id' => 3, 'nombre' => 'Masaje capilar', 'descripcion' => 'Aproximadamente 1 hora.', 'duracion_min' => 60, 'precio' => 18000, 'imagen' => 'ico-masaje.png', 'activo' => true],
            ['id' => 14, 'categoria_id' => 3, 'nombre' => 'Botox capilar', 'descripcion' => '1 a 2 horas.', 'duracion_min' => 120, 'precio' => 30000, 'imagen' => 'ico-botox.png', 'activo' => true],
            ['id' => 15, 'categoria_id' => 3, 'nombre' => 'Liso permanente', 'descripcion' => '2 a 3,5 horas. A veces se combina con retoque de color.', 'duracion_min' => 240, 'precio' => 40000, 'imagen' => 'ico-liso.png', 'activo' => true],
            ['id' => 16, 'categoria_id' => 3, 'nombre' => 'Maquillaje social', 'descripcion' => 'Para eventos, de forma ocasional, 30 minutos a 1 hora.', 'duracion_min' => 60, 'precio' => 18000, 'imagen' => 'ico-maquillaje.png', 'activo' => true],
            ['id' => 17, 'categoria_id' => 3, 'nombre' => 'Olaplex', 'descripcion' => 'Reparación de la fibra capilar. Aprox. 1 hora.', 'duracion_min' => 60, 'precio' => 25000, 'imagen' => 'ico-olaplex.png', 'activo' => true],
        ]);

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
