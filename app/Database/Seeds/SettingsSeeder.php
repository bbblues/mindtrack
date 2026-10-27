<?php

namespace App\Database\Seeds;

use App\Modules\Core\Models\HealthInsuranceModel;
use App\Modules\Core\Models\SpecialtyModel;
use App\Modules\Core\Models\UnitModel;
use CodeIgniter\Database\Seeder;
use RuntimeException;

/**
 * Semeia os catalogos basicos (unidades, convenios e especialidades).
 *
 * Como ainda nao existe fluxo de cadastro de tenant, estes registros sao
 * criados como padroes globais (tenant_id = null), ficando visiveis para o
 * Super Admin. Quando houver cadastro de tenants, cada clinica devera ter
 * seu proprio catalogo (com tenant_id preenchido) ou herdar os padroes.
 */
class SettingsSeeder extends Seeder
{
    public function run()
    {
        $this->seedUnits();
        $this->seedHealthInsurances();
        $this->seedSpecialties();
    }

    private function seedUnits(): void
    {
        $model = new UnitModel();

        if ($model->where('name', 'Matriz')->first() !== null) {
            return;
        }

        if ($model->insert([
            'tenant_id' => null,
            'name'      => 'Matriz',
            'cnpj'      => null,
            'is_active' => 1,
        ]) === false) {
            throw new RuntimeException('Falha ao semear a unidade Matriz: ' . implode(' ', $model->errors()));
        }
    }

    private function seedHealthInsurances(): void
    {
        $model = new HealthInsuranceModel();

        $insurances = [
            ['name' => 'Particular', 'ans_registry' => null, 'requires_card' => 0, 'card_format' => null],
            ['name' => 'Unimed', 'ans_registry' => null, 'requires_card' => 1, 'card_format' => null],
        ];

        foreach ($insurances as $insurance) {
            if ($model->where('name', $insurance['name'])->first() !== null) {
                continue;
            }

            if ($model->insert([
                'tenant_id'     => null,
                'name'          => $insurance['name'],
                'ans_registry'  => $insurance['ans_registry'],
                'requires_card' => $insurance['requires_card'],
                'card_format'   => $insurance['card_format'],
            ]) === false) {
                throw new RuntimeException(
                    'Falha ao semear o convenio ' . $insurance['name'] . ': ' . implode(' ', $model->errors())
                );
            }
        }
    }

    private function seedSpecialties(): void
    {
        $model = new SpecialtyModel();

        $specialties = ['Fonoaudiologia', 'Psicologia', 'Terapia Ocupacional'];

        foreach ($specialties as $name) {
            if ($model->where('name', $name)->first() !== null) {
                continue;
            }

            if ($model->insert([
                'tenant_id' => null,
                'name'      => $name,
            ]) === false) {
                throw new RuntimeException(
                    'Falha ao semear a especialidade ' . $name . ': ' . implode(' ', $model->errors())
                );
            }
        }
    }
}
