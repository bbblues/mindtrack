<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Agenda extends BaseConfig
{
    /**
     * Chave usada na tabela `settings` para armazenar a duração padrão
     * de sessão por clínica (tenant).
     */
    public string $settingKey = 'appointment_default_duration_minutes';

    /**
     * Duração padrão (em minutos) usada como fallback quando a clínica
     * ainda não configurou um valor próprio.
     */
    public int $defaultDuration = 40;

    /**
     * Horário de funcionamento padrão da clínica (um turno por dia da semana).
     * Usado como fallback quando a clínica ainda não configurou o próprio
     * horário. Dias ausentes são tratados como "fechado".
     *
     * @var array<int, array{start_time: string, end_time: string}>
     */
    public array $defaultClinicSchedule = [
        1 => ['start_time' => '08:00', 'end_time' => '18:00'], // Segunda
        2 => ['start_time' => '08:00', 'end_time' => '18:00'], // Terça
        3 => ['start_time' => '08:00', 'end_time' => '18:00'], // Quarta
        4 => ['start_time' => '08:00', 'end_time' => '18:00'], // Quinta
        5 => ['start_time' => '08:00', 'end_time' => '18:00'], // Sexta
        6 => ['start_time' => '08:00', 'end_time' => '12:00'], // Sábado
        // 0 (Domingo) ausente = fechado
    ];

    /**
     * URL base da API pública de feriados nacionais (BrasilAPI).
     * O ano é concatenado ao final: {base}{ano}.
     */
    public string $holidayApiBaseUrl = 'https://brasilapi.com.br/api/feriados/v1/';

    /**
     * Quantos anos à frente (além do ano corrente) a sincronização mensal
     * de feriados nacionais deve buscar.
     */
    public int $holidaySyncYearsAhead = 1;

    /**
     * Por padrão, agendamentos recorrentes ignoram silenciosamente as
     * ocorrências que caem fora da agenda do profissional ou em feriados.
     */
    public bool $defaultIgnoreImpediments = true;

    /**
     * Badges (rótulo + cor) usadas para classificar feriados e datas de
     * fechamento na agenda e no cadastro de Configurações.
     *
     * @var array<string, array{label: string, color: string}>
     */
    public array $holidayBadges = [
        'national'  => ['label' => 'Feriado Nacional', 'color' => '#ef4444'],
        'state'     => ['label' => 'Feriado Estadual', 'color' => '#f97316'],
        'municipal' => ['label' => 'Feriado Municipal', 'color' => '#f59e0b'],
        'closure'   => ['label' => 'Fechamento', 'color' => '#64748b'],
    ];
}
