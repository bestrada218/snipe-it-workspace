<?php

return [
    'title' => 'Reporte de daño',
    'intro' => 'Este estado impide asignar el activo. Documente qué está dañado antes de guardar; queda en el historial del activo.',
    'description' => 'Qué está dañado',
    'description_placeholder' => 'En inglés, p. ej. «Screen cracked and does not power on after the drop»',
    'description_required' => 'Describa qué está dañado para pasar el activo a este estado.',
    'repairable' => '¿Se puede reparar?',
    'repairable_options' => [
        'yes' => 'Sí, se puede reparar',
        'no' => 'No, no tiene reparación',
        'unknown' => 'Aún no se sabe',
    ],
    'repair_attempted' => '¿Ya se intentó reparar?',
    'repair_details' => 'Qué se intentó y cuál fue el resultado',
    'repair_details_placeholder' => 'En inglés, p. ej. «Vendor replaced the board in Oct; it failed again»',
    'yes' => 'Sí',
    'no' => 'No',
    'activity_title' => 'Daño reportado',
    'status_change' => 'Cambio de estado',
    'statuses' => 'Estados que requieren reporte de daño',
    'statuses_help' => 'Al pasar un activo a uno de estos estados (equipo malo o dañado, que ya no se puede asignar) se exige documentar qué está dañado, si se puede reparar y si ya se intentó reparar.',
];
