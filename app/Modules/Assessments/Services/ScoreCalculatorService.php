<?php

namespace App\Modules\Assessments\Services;

use App\Modules\Assessments\Models\PatientAssessmentItemModel;
use App\Modules\Assessments\Models\PatientAssessmentModel;
use InvalidArgumentException;

/**
 * ScoreCalculatorService
 *
 * Calculo de score desacoplado e generico para avaliacoes.
 *
 * - Se o item tem max_score = 1 e score_achieved em {0, 1}, a soma e simples.
 * - Se o item tem max_score > 1, os valores fracionados sao somados.
 *
 * O Service retorna o score total, o total possivel, a porcentagem geral e o
 * subtotal por habilidade (para graficos).
 */
class ScoreCalculatorService
{
    protected PatientAssessmentModel $assessmentModel;

    protected PatientAssessmentItemModel $assessmentItemModel;

    public function __construct()
    {
        $this->assessmentModel     = new PatientAssessmentModel();
        $this->assessmentItemModel = new PatientAssessmentItemModel();
    }

    /**
     * Calcula os scores da avaliacao.
     *
     * @return array{
     *     total_score: float,
     *     total_possible: float,
     *     percentage: float,
     *     by_skill: list<array{level: string, skill: string, score: float, max: float, percentage: float}>,
     *     by_section: list<array{section: string, score: float, max: float, percentage: float}>
     * }
     */
    public function calculate(int $assessmentId): array
    {
        $assessment = $this->assessmentModel->find($assessmentId);

        if ($assessment === null) {
            throw new InvalidArgumentException('Avaliacao nao encontrada.');
        }

        $rows = $this->assessmentItemModel
            ->select(
                'patient_assessment_items.score_achieved,'
                . ' patient_assessment_items.value_at_time,'
                . ' protocol_items.max_score,'
                . ' protocol_items.skill_id,'
                . ' protocol_skills.name AS skill_name,'
                . ' protocol_skills.level_id,'
                . ' protocol_levels.name AS level_name,'
                . ' protocol_levels.section AS level_section'
            )
            ->join('protocol_items', 'protocol_items.id = patient_assessment_items.protocol_item_id', 'inner')
            ->join('protocol_skills', 'protocol_skills.id = protocol_items.skill_id', 'inner')
            ->join('protocol_levels', 'protocol_levels.id = protocol_skills.level_id', 'inner')
            ->where('patient_assessment_items.assessment_id', $assessmentId)
            ->orderBy('protocol_levels.sort_order', 'ASC')
            ->orderBy('protocol_skills.sort_order', 'ASC')
            ->orderBy('protocol_items.sort_order', 'ASC')
            ->findAll();

        $totalScore    = 0.0;
        $totalPossible = 0.0;
        $bySkill       = [];
        $bySection     = [];

        foreach ($rows as $row) {
            $maxScore = (float) ($row['max_score'] ?? 1.0);
            $achieved = $this->resolveAchieved($row);
            $achieved = min(max($achieved, 0.0), $maxScore);

            $totalScore    += $achieved;
            $totalPossible += $maxScore;

            $skillId = (int) ($row['skill_id'] ?? 0);

            if (! isset($bySkill[$skillId])) {
                $bySkill[$skillId] = [
                    'level' => (string) ($row['level_name'] ?? ''),
                    'skill' => (string) ($row['skill_name'] ?? ''),
                    'score' => 0.0,
                    'max'   => 0.0,
                ];
            }

            $bySkill[$skillId]['score'] += $achieved;
            $bySkill[$skillId]['max']   += $maxScore;

            $section = trim((string) ($row['level_section'] ?? ''));
            $section = $section !== '' ? $section : 'Geral';

            if (! isset($bySection[$section])) {
                $bySection[$section] = [
                    'section' => $section,
                    'score'   => 0.0,
                    'max'     => 0.0,
                ];
            }

            $bySection[$section]['score'] += $achieved;
            $bySection[$section]['max']   += $maxScore;
        }

        foreach ($bySkill as &$skill) {
            $skill['score']      = round($skill['score'], 2);
            $skill['max']        = round($skill['max'], 2);
            $skill['percentage'] = $skill['max'] > 0 ? round(($skill['score'] / $skill['max']) * 100, 2) : 0.0;
        }
        unset($skill);

        foreach ($bySection as &$section) {
            $section['score']      = round($section['score'], 2);
            $section['max']        = round($section['max'], 2);
            $section['percentage'] = $section['max'] > 0 ? round(($section['score'] / $section['max']) * 100, 2) : 0.0;
        }
        unset($section);

        $percentage = $totalPossible > 0 ? round(($totalScore / $totalPossible) * 100, 2) : 0.0;

        return [
            'total_score'    => round($totalScore, 2),
            'total_possible' => round($totalPossible, 2),
            'percentage'     => $percentage,
            'by_skill'       => array_values($bySkill),
            'by_section'     => array_values($bySection),
        ];
    }

    /**
     * Resolve a nota alcancada, preferindo score_achieved e caindo para o
     * valor legado value_at_time quando necessario.
     *
     * @param array<string, mixed> $row
     */
    private function resolveAchieved(array $row): float
    {
        $value = $row['score_achieved'] ?? null;

        if ($value === null || $value === '') {
            $value = $row['value_at_time'] ?? 0;
        }

        return (float) $value;
    }
}
