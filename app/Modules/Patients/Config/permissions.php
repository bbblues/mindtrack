<?php

/**
 * Catálogo de permissões do módulo de Pacientes.
 *
 * Cada item descreve uma ação específica (modelo inspirado no Drupal).
 * O escopo (`scope`) pode ser null (ação sem escopo), "any" ou "own".
 */
return [
    ['module' => 'patients', 'slug' => 'patients.create', 'name' => 'Criar paciente', 'scope' => null, 'sort_order' => 1],
    ['module' => 'patients', 'slug' => 'patients.view.any', 'name' => 'Visualizar qualquer paciente', 'scope' => 'any', 'sort_order' => 2],
    ['module' => 'patients', 'slug' => 'patients.view.own', 'name' => 'Visualizar apenas os próprios pacientes', 'scope' => 'own', 'sort_order' => 3],
    ['module' => 'patients', 'slug' => 'patients.edit.any', 'name' => 'Editar qualquer paciente', 'scope' => 'any', 'sort_order' => 4],
    ['module' => 'patients', 'slug' => 'patients.edit.own', 'name' => 'Editar apenas os próprios pacientes', 'scope' => 'own', 'sort_order' => 5],
    ['module' => 'patients', 'slug' => 'patients.deactivate.any', 'name' => 'Desativar qualquer paciente', 'scope' => 'any', 'sort_order' => 6],
    ['module' => 'patients', 'slug' => 'patients.deactivate.own', 'name' => 'Desativar apenas os próprios pacientes', 'scope' => 'own', 'sort_order' => 7],
];
