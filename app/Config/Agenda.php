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
}
