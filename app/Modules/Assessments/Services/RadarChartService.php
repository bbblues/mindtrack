<?php

namespace App\Modules\Assessments\Services;

/**
 * RadarChartService
 *
 * Gera um grafico de teia (radar) como imagem PNG via GD, retornando um data
 * URI pronto para ser embutido no HTML do Dompdf (que nao executa JavaScript).
 *
 * A fonte DejaVu Sans vem embutida no pacote Dompdf e suporta acentos
 * (ex.: "Transição", "Nível", "Rastreamento").
 */
class RadarChartService
{
    private const FONT_PATH = ROOTPATH . 'vendor/dompdf/dompdf/lib/fonts/DejaVuSans.ttf';

    private int $size = 720;

    private int $padding = 110;

    private int $fontSize = 12;

    /**
     * @param list<array{label: string, percentage: float}> $sections
     */
    public function render(array $sections): string
    {
        $sections = array_values($sections);

        if ($sections === []) {
            return '';
        }

        $image = imagecreatetruecolor($this->size, $this->size);

        if ($image === false) {
            return '';
        }

        $white = imagecolorallocate($image, 255, 255, 255);
        imagefilledrectangle($image, 0, 0, $this->size - 1, $this->size - 1, $white);

        $cx     = (int) ($this->size / 2);
        $cy     = (int) ($this->size / 2);
        $radius = (int) (($this->size - (2 * $this->padding)) / 2);

        $n         = count($sections);
        $angleStep = (2 * M_PI) / $n;

        $gridColor  = imagecolorallocate($image, 209, 213, 219); // gray-300
        $axisColor  = imagecolorallocate($image, 229, 231, 235); // gray-200
        $fillColor  = imagecolorallocate($image, 191, 219, 254); // blue-200
        $lineColor  = imagecolorallocate($image, 37, 99, 235);   // blue-600
        $pointColor = imagecolorallocate($image, 29, 78, 216);   // blue-700
        $labelColor = imagecolorallocate($image, 55, 65, 81);    // gray-700

        // Anéis da grade (20% .. 100%).
        for ($level = 20; $level <= 100; $level += 20) {
            $points = $this->ringPoints($cx, $cy, $radius, $level, $n, $angleStep);
            imagepolygon($image, $points, $gridColor);
        }

        // Eixos (spokes).
        for ($i = 0; $i < $n; $i++) {
            $angle = $this->angleAt($i, $angleStep);
            $x     = (int) round($cx + $radius * cos($angle));
            $y     = (int) round($cy + $radius * sin($angle));
            imageline($image, $cx, $cy, $x, $y, $axisColor);
        }

        // Polígono dos dados.
        $dataPoints = [];
        $hasData    = false;

        foreach ($sections as $i => $section) {
            $pct = $this->clamp((float) ($section['percentage'] ?? 0.0));

            if ($pct > 0.0) {
                $hasData = true;
            }

            $angle        = $this->angleAt($i, $angleStep);
            $r            = ($radius * $pct) / 100;
            $dataPoints[] = (int) round($cx + $r * cos($angle));
            $dataPoints[] = (int) round($cy + $r * sin($angle));
        }

        if ($hasData && $n >= 3) {
            imagefilledpolygon($image, $dataPoints, $fillColor);
            imagepolygon($image, $dataPoints, $lineColor);
        }

        // Pontos de dados.
        foreach ($sections as $i => $section) {
            $pct   = $this->clamp((float) ($section['percentage'] ?? 0.0));
            $angle = $this->angleAt($i, $angleStep);
            $r     = ($radius * $pct) / 100;
            $x     = (int) round($cx + $r * cos($angle));
            $y     = (int) round($cy + $r * sin($angle));
            imagefilledellipse($image, $x, $y, 9, 9, $pointColor);
        }

        // Rótulos + percentual.
        $labelOffset = 38;

        foreach ($sections as $i => $section) {
            $angle = $this->angleAt($i, $angleStep);
            $x     = $cx + ($radius + $labelOffset) * cos($angle);
            $y     = $cy + ($radius + $labelOffset) * sin($angle);

            $this->drawText($image, $x, $y, (string) ($section['label'] ?? ''), $labelColor);

            $pct = $this->clamp((float) ($section['percentage'] ?? 0.0));
            $this->drawText($image, $x, $y + 15, ((int) round($pct)) . '%', $pointColor);
        }

        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);

        if (! is_string($png) || $png === '') {
            return '';
        }

        return 'data:image/png;base64,' . base64_encode($png);
    }

    /**
     * @return list<int>
     */
    private function ringPoints(int $cx, int $cy, int $radius, int $level, int $n, float $angleStep): array
    {
        $points = [];

        for ($i = 0; $i < $n; $i++) {
            $angle    = $this->angleAt($i, $angleStep);
            $r        = ($radius * $level) / 100;
            $points[] = (int) round($cx + $r * cos($angle));
            $points[] = (int) round($cy + $r * sin($angle));
        }

        return $points;
    }

    private function angleAt(int $index, float $angleStep): float
    {
        return -M_PI / 2 + ($index * $angleStep);
    }

    private function clamp(float $value): float
    {
        return max(0.0, min(100.0, $value));
    }

    private function drawText($image, float $x, float $y, string $text, int $color): void
    {
        $text = trim($text);

        if ($text === '') {
            return;
        }

        if (! is_file(self::FONT_PATH)) {
            imagestring($image, 5, (int) round($x) - 20, (int) round($y) - 8, $text, $color);

            return;
        }

        $box = imagettfbbox($this->fontSize, 0, self::FONT_PATH, $text);

        if ($box === false) {
            imagestring($image, 5, (int) round($x) - 20, (int) round($y) - 8, $text, $color);

            return;
        }

        $width = $box[2] - $box[0];
        $xx    = (int) round($x - ($width / 2));
        $yy    = (int) round($y);

        imagettftext($image, $this->fontSize, 0, $xx, $yy, $color, self::FONT_PATH, $text);
    }
}
