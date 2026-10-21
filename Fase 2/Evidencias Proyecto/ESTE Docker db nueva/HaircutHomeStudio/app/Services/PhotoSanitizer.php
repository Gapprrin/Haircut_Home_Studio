<?php

namespace App\Services;

use InvalidArgumentException;

class PhotoSanitizer
{
    /** @return array{bytes: string, mime_type: string, extension: string, hash: string} */
    public function sanitize(string $bytes): array
    {
        $imageInfo = @getimagesizefromstring($bytes);
        $mimeType = $imageInfo['mime'] ?? null;

        $sanitized = match ($mimeType) {
            'image/jpeg' => $this->stripJpegMetadata($bytes),
            'image/png' => $this->stripPngMetadata($bytes),
            default => throw new InvalidArgumentException('Solo se permiten imágenes JPG o PNG.'),
        };

        if (@getimagesizefromstring($sanitized) === false) {
            throw new InvalidArgumentException('La imagen no se pudo validar después de limpiarla.');
        }

        return [
            'bytes' => $sanitized,
            'mime_type' => $mimeType,
            'extension' => $mimeType === 'image/png' ? 'png' : 'jpg',
            'hash' => hash('sha256', $sanitized),
        ];
    }

    private function stripJpegMetadata(string $bytes): string
    {
        if (! str_starts_with($bytes, "\xFF\xD8")) {
            throw new InvalidArgumentException('El archivo JPG no es válido.');
        }

        $result = "\xFF\xD8";
        $position = 2;
        $length = strlen($bytes);

        while ($position < $length) {
            if (ord($bytes[$position]) !== 0xFF || $position + 1 >= $length) {
                throw new InvalidArgumentException('El archivo JPG está incompleto.');
            }

            $marker = ord($bytes[$position + 1]);
            if ($marker === 0xDA) {
                return $result.substr($bytes, $position);
            }

            if ($marker === 0xD9) {
                return $result."\xFF\xD9";
            }

            if ($marker === 0x01 || ($marker >= 0xD0 && $marker <= 0xD7)) {
                $result .= substr($bytes, $position, 2);
                $position += 2;

                continue;
            }

            if ($position + 4 > $length) {
                throw new InvalidArgumentException('El archivo JPG está incompleto.');
            }

            $segmentLength = unpack('n', substr($bytes, $position + 2, 2))[1];
            $totalLength = $segmentLength + 2;
            if ($segmentLength < 2 || $position + $totalLength > $length) {
                throw new InvalidArgumentException('El archivo JPG contiene metadatos inválidos.');
            }

            if (! in_array($marker, [0xE1, 0xED, 0xFE], true)) {
                $result .= substr($bytes, $position, $totalLength);
            }
            $position += $totalLength;
        }

        throw new InvalidArgumentException('El archivo JPG no contiene datos de imagen.');
    }

    private function stripPngMetadata(string $bytes): string
    {
        $signature = "\x89PNG\r\n\x1A\n";
        if (! str_starts_with($bytes, $signature)) {
            throw new InvalidArgumentException('El archivo PNG no es válido.');
        }

        $result = $signature;
        $position = 8;
        $length = strlen($bytes);
        $metadataChunks = ['eXIf', 'tEXt', 'zTXt', 'iTXt'];

        while ($position + 12 <= $length) {
            $dataLength = unpack('N', substr($bytes, $position, 4))[1];
            $chunkLength = 12 + $dataLength;
            if ($position + $chunkLength > $length) {
                throw new InvalidArgumentException('El archivo PNG está incompleto.');
            }

            $type = substr($bytes, $position + 4, 4);
            if (! in_array($type, $metadataChunks, true)) {
                $result .= substr($bytes, $position, $chunkLength);
            }
            $position += $chunkLength;

            if ($type === 'IEND') {
                return $result;
            }
        }

        throw new InvalidArgumentException('El archivo PNG no contiene un cierre válido.');
    }
}
