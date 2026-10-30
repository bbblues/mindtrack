<?php

namespace App\Modules\Patients\Libraries;

/**
 * PatientColor
 *
 * Centraliza a paleta de identificacao visual dos pacientes e o calculo
 * de contraste (formula YIQ) usado para garantir a legibilidade do texto
 * sobre a cor do paciente na Agenda.
 */
class PatientColor
{
    /**
     * Paleta fixa de 30 cores HEX contrastantes (pasteis + vibrantes).
     *
     * @return list<string>
     */
    public static function getPalette(): array
    {
        return [
            // Pasteis
            '#fecaca', '#fed7aa', '#fde68a', '#bbf7d0', '#a7f3d0',
            '#bae6fd', '#c7d2fe', '#ddd6fe', '#fbcfe8', '#f5d0fe',
            // Vibrantes
            '#ef4444', '#f97316', '#f59e0b', '#84cc16', '#22c55e',
            '#10b981', '#14b8a6', '#06b6d4', '#0ea5e9', '#3b82f6',
            '#6366f1', '#8b5cf6', '#a855f7',
            // Profundas
            '#d946ef', '#ec4899', '#f43f5e', '#dc2626', '#2563eb',
            '#7c3aed', '#be185d',
        ];
    }

    /**
     * Calcula a cor do texto (black|white) com base na luminosidade
     * (formula YIQ) para garantir leitura sobre a cor do paciente.
     */
    public static function getContrastText(string $hex): string
    {
        $hex = ltrim(trim($hex), '#');

        // Suporta o formato curto #RGB -> #RRGGBB.
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        if (! preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            return 'black';
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;

        return $yiq >= 128 ? 'black' : 'white';
    }
}
