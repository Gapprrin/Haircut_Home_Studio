<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\CatalogoProductosSeeder;
use Database\Seeders\CatalogoServiciosSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class InstallStudio extends Command
{
    protected $signature = 'studio:install';

    protected $description = 'Inicializa una base nueva de la versión 2.7 con catálogos y cuentas propias, sin datos demo';

    public function handle(): int
    {
        foreach (['USUARIO', 'ROL', 'TIPO_SERVICIO', 'SERVICIO', 'CATEGORIA', 'PRODUCTO', 'RESERVA', 'ESTADO_RESERVA', 'TIPO_RESERVA'] as $table) {
            if (DB::table($table)->exists()) {
                $this->error('La base ya contiene datos. No se modificó nada; este comando es solo para la primera instalación.');

                return self::FAILURE;
            }
        }

        $accounts = [];
        foreach (['admin' => 'administrador', 'peluquero' => 'peluquero'] as $role => $label) {
            $accounts[$role] = [
                'nombre' => trim((string) $this->ask("Nombre del {$label}")),
                'email' => Str::lower(trim((string) $this->ask("Correo del {$label}"))),
                'password' => $this->secret("Contraseña del {$label} (mínimo 12 caracteres)"),
                'password_confirmation' => $this->secret("Repite la contraseña del {$label}"),
            ];
        }

        $validator = Validator::make($accounts, [
            '*.nombre' => ['required', 'string', 'max:150'],
            '*.email' => ['required', 'email', 'max:100'],
            '*.password' => ['required', 'string', 'min:12', 'confirmed'],
            'peluquero.email' => ['required', 'email', 'max:100', 'different:admin.email'],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }
        foreach ($accounts as $account) {
            if (strcasecmp($account['email'], 'admin.imagen@haircut.cl') === 0) {
                $this->error('Elige un correo propio: la cuenta de imágenes ilimitadas no debe publicarse.');

                return self::FAILURE;
            }
        }

        DB::transaction(function () use ($accounts): void {
            DB::table('ROL')->insert([
                ['id_rol' => 1, 'rol' => 'Admin'],
                ['id_rol' => 2, 'rol' => 'Peluquero'],
                ['id_rol' => 3, 'rol' => 'Cliente'],
                ['id_rol' => 4, 'rol' => 'CliAdmin'],
            ]);
            DB::table('CATEGORIA')->insert([
                ['id_categoria' => 1, 'categoria' => 'Cortes'],
                ['id_categoria' => 2, 'categoria' => 'Color'],
                ['id_categoria' => 3, 'categoria' => 'Tratamientos'],
            ]);
            DB::table('ESTADO_RESERVA')->insert([
                ['id_estado_reserva' => 1, 'estado_reserva' => 'En aprovación'],
                ['id_estado_reserva' => 2, 'estado_reserva' => 'Agendada'],
                ['id_estado_reserva' => 3, 'estado_reserva' => 'Rechazada'],
                ['id_estado_reserva' => 4, 'estado_reserva' => 'Cancelada'],
                ['id_estado_reserva' => 5, 'estado_reserva' => 'Realizada'],
                ['id_estado_reserva' => 6, 'estado_reserva' => 'No asistió'],
            ]);
            DB::table('TIPO_RESERVA')->insert([
                ['id_tipo_reserva' => 1, 'tipo_reserva' => 'Salón'],
                ['id_tipo_reserva' => 2, 'tipo_reserva' => 'Domicilio'],
            ]);
            (new CatalogoServiciosSeeder)->run();
            (new CatalogoProductosSeeder)->run();
            foreach (['admin' => 1, 'peluquero' => 2] as $role => $roleId) {
                $account = $accounts[$role];
                User::create([
                    'nombre_usuario' => $account['nombre'],
                    'email' => $account['email'],
                    'contrasenia' => $account['password'],
                    'id_rol' => $roleId,
                ]);
            }
        });

        $this->info('Instalación completada: 17 servicios, 15 productos y dos cuentas propias. No se crearon reservas ni cuentas demo.');
        $this->info('Entra con los correos completos que elegiste. Revisa precios e imágenes de productos antes de presentar.');

        return self::SUCCESS;
    }
}
