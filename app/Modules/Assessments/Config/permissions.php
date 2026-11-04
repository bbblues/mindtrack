<?php

/**
 * Catálogo de permissões do módulo de Avaliações.
 */
return [
    ['module' => 'assessments', 'slug' => 'assessments.start', 'name' => 'Iniciar avaliação', 'scope' => null, 'sort_order' => 1],
    ['module' => 'assessments', 'slug' => 'assessments.edit.any', 'name' => 'Editar avaliações em andamento (qualquer)', 'scope' => 'any', 'sort_order' => 2],
    ['module' => 'assessments', 'slug' => 'assessments.edit.own', 'name' => 'Editar apenas as próprias avaliações', 'scope' => 'own', 'sort_order' => 3],
    ['module' => 'assessments', 'slug' => 'assessments.finalize.any', 'name' => 'Finalizar qualquer avaliação', 'scope' => 'any', 'sort_order' => 4],
    ['module' => 'assessments', 'slug' => 'assessments.finalize.own', 'name' => 'Finalizar apenas as próprias avaliações', 'scope' => 'own', 'sort_order' => 5],
    ['module' => 'assessments', 'slug' => 'assessments.export_pdf', 'name' => 'Exportar avaliação em PDF', 'scope' => null, 'sort_order' => 6],
];
