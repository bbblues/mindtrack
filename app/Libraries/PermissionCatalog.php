<?php

namespace App\Libraries;

/**
 * Fonte da verdade (em código) do catálogo de permissões.
 *
 * Agrega os arquivos `permissions.php` de cada módulo e expõe também o
 * mapeamento padrão de permissões por papel (admin, gestor, profissional,
 * recepção) usado pelo Seeder e pelo comando `spark rbac:sync`.
 */
class PermissionCatalog
{
    /**
     * Módulos (em minúsculo) que definem permissões.
     *
     * @var list<string>
     */
    private const MODULES = ['patients', 'agenda', 'assessments', 'core'];

    /**
     * Retorna todos os itens do catálogo (um array por permissão).
     *
     * @return list<array<string, mixed>>
     */
    public static function all(): array
    {
        $catalog = [];

        foreach (self::MODULES as $module) {
            $file = APPPATH . 'Modules/' . ucfirst($module) . '/Config/permissions.php';

            if (! is_file($file)) {
                continue;
            }

            $items = require $file;

            if (! is_array($items)) {
                continue;
            }

            foreach ($items as $item) {
                $item['module'] = $item['module'] ?? $module;
                $catalog[]      = $item;
            }
        }

        return $catalog;
    }

    /**
     * Slugs de todas as permissões do catálogo.
     *
     * @return list<string>
     */
    public static function slugs(): array
    {
        return array_values(array_unique(array_filter(array_map('strval', array_column(self::all(), 'slug')))));
    }

    /**
     * Conjunto padrão de permissões de cada papel padrão do sistema.
     *
     * @return array<string, list<string>>
     */
    public static function defaultRolePermissions(): array
    {
        $all = self::slugs();

        return [
            'admin'         => $all,
            'gestor'        => array_values(array_diff($all, ['settings.manage_roles'])),
            'profissional'  => [
                'patients.view.any',
                'patients.view.own',
                'patients.edit.own',
                'patients.deactivate.own',
                'agenda.view.own',
                'agenda.edit.own',
                'agenda.confirm.own',
                'assessments.start',
                'assessments.edit.own',
                'assessments.finalize.own',
                'assessments.export_pdf',
            ],
            'recepcao'      => [
                'patients.create',
                'patients.view.any',
                'agenda.create',
                'agenda.view.any',
                'agenda.edit.any',
                'agenda.confirm.any',
            ],
        ];
    }
}
