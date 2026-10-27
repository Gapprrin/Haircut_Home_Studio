<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaService
{
    public function store(?UploadedFile $archivo, string $prefijo): ?string
    {
        if (! $archivo) {
            return null;
        }

        $prefijo = preg_replace('/[^a-z0-9_]/i', '', $prefijo) ?: 'foto_';
        $nombre = $prefijo.now()->format('Ymd_His').'_'.Str::lower(Str::random(8)).'.'.$archivo->extension();

        return $archivo->storeAs('fotos', $nombre, 'public');
    }

    public function delete(?string $ruta): void
    {
        if (! $ruta) {
            return;
        }

        Storage::disk('public')->delete($this->normalizarRuta($ruta));
    }

    public function url(?string $ruta): ?string
    {
        if (! $ruta) {
            return null;
        }

        if (! str_contains($ruta, '/') && str_starts_with($ruta, 'ico-')) {
            return asset('img/iconos/'.substr($ruta, 4));
        }

        return asset('storage/'.$this->normalizarRuta($ruta));
    }

    private function normalizarRuta(string $ruta): string
    {
        $ruta = ltrim(str_replace('\\', '/', $ruta), '/');

        return str_starts_with($ruta, 'fotos/') ? $ruta : 'fotos/'.$ruta;
    }
}
