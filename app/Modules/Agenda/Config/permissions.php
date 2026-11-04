<?php

/**
 * Catálogo de permissões do módulo de Agenda.
 */
return [
    ['module' => 'agenda', 'slug' => 'agenda.create', 'name' => 'Criar agendamento', 'scope' => null, 'sort_order' => 1],
    ['module' => 'agenda', 'slug' => 'agenda.view.any', 'name' => 'Visualizar agenda de todos os profissionais', 'scope' => 'any', 'sort_order' => 2],
    ['module' => 'agenda', 'slug' => 'agenda.view.own', 'name' => 'Visualizar apenas a própria agenda', 'scope' => 'own', 'sort_order' => 3],
    ['module' => 'agenda', 'slug' => 'agenda.edit.any', 'name' => 'Editar qualquer agendamento', 'scope' => 'any', 'sort_order' => 4],
    ['module' => 'agenda', 'slug' => 'agenda.edit.own', 'name' => 'Editar apenas os próprios agendamentos', 'scope' => 'own', 'sort_order' => 5],
    ['module' => 'agenda', 'slug' => 'agenda.confirm.any', 'name' => 'Confirmar/Faltar em qualquer agendamento', 'scope' => 'any', 'sort_order' => 6],
    ['module' => 'agenda', 'slug' => 'agenda.confirm.own', 'name' => 'Confirmar/Faltar apenas nos próprios agendamentos', 'scope' => 'own', 'sort_order' => 7],
];
