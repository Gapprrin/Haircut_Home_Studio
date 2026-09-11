<?php

namespace App\Services;

use App\Models\AiGeneration;
use App\Models\Servicio;
use Illuminate\Support\Str;
use InvalidArgumentException;

class AiPromptService
{
    public function forGeneration(AiGeneration $generation): string
    {
        if ($generation->servicio) {
            return $this->forService($generation->servicio);
        }

        return $this->forPreset($generation->preset);
    }

    public function forService(Servicio $servicio): string
    {
        $servicio->loadMissing('categoria');
        $category = (string) $servicio->categoria?->slug;
        if (! in_array($category, ['corte', 'color'], true)) {
            throw new InvalidArgumentException('El servicio no está disponible para simulación.');
        }

        $serviceName = Str::lower(Str::ascii(trim($servicio->nombre)));
        $definitions = (array) config("ai.service_prompts.{$category}");
        $requestedChange = $definitions[$serviceName] ?? $this->fallbackFor($servicio, $category);

        return $this->build($requestedChange);
    }

    public function forPreset(string $preset): string
    {
        $definition = config("ai.presets.{$preset}");
        if (! is_array($definition) || empty($definition['prompt'])) {
            throw new InvalidArgumentException('Preset de simulación inválido.');
        }

        return $this->build($definition['prompt']);
    }

    private function build(string $requestedChange): string
    {
        return implode("\n", [
            'Create one photorealistic salon consultation preview from the supplied portrait.',
            'Edit only the person\'s hair. Preserve identity, facial features, skin tone, expression, age, body, clothing, pose, background, framing, camera angle, and lighting.',
            'Do not beautify, reshape the face, alter makeup, add accessories, add text, or change anything outside the hair.',
            'Keep realistic hair texture, roots, strand detail, shadows, and plausible salon results.',
            'Requested salon service: '.$requestedChange,
            'Return only the edited portrait as an image.',
        ]);
    }

    private function fallbackFor(Servicio $servicio, string $category): string
    {
        $safeName = preg_replace('/[^\pL\pN +\-]/u', '', $servicio->nombre) ?: 'servicio de peluquería';

        return $category === 'corte'
            ? "Create a realistic professional salon haircut corresponding to the catalog service '{$safeName}', preserving the current hair color."
            : "Create a realistic professional salon hair color corresponding to the catalog service '{$safeName}', preserving the current haircut.";
    }
}
