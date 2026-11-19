<?php

return [
    'quick_structure' => 'XX ubicación padre · KKK consecutivo global de 3 dígitos · PP tipo de elemento · TTT información adicional del equipo (opcional).',
    'full_guide' => 'Ver guía completa',
    'next_sequence' => 'Siguiente consecutivo global (KKK): :sequence. Úsalo en la placa de este elemento.',
    'sequence_exhausted' => 'La secuencia global de tres dígitos llegó a 999.',
    'title' => 'Guía de placas',
    'format' => 'Formato de la placa',
    'optional' => 'Los corchetes indican que el sufijo es opcional. Si no aplica, omite también el último guion.',
    'segment' => 'Segmento',
    'meaning' => 'Significado',
    'parts' => [
        'XX' => 'Ubicación padre, normalmente el estado. Ejemplos: NY y NJ.',
        'KKK' => 'Consecutivo global de tres dígitos. Cada número identifica una sola pieza en todo el inventario, incluidos activos y accesorios, aunque cambien el estado, el tipo o el sufijo.',
        'PP' => 'Tipo de elemento. LP = portátil, DT = equipo de escritorio, HS = diadema, KB = teclado, MS = ratón, WC = cámara web, DK = estación de acoplamiento. TV = televisor, SP = parlante, PJ = proyector, AP = punto de acceso, VC = cámara de videoconferencia.',
        'TTT' => 'Información adicional. En equipos puede indicar el sistema operativo: W11 = Windows 11, MAC = macOS, LNX = Linux.',
    ],
    'examples' => 'Ejemplos',
    'laptop_example' => 'Portátil con Windows 11 en Nueva York.',
    'desktop_example' => 'Equipo de escritorio con Linux en Nueva York.',
    'accessory_example' => 'Diadema en Nueva York, sin sufijo.',
    'storage' => 'Registra el código en el campo Placa del activo de cada módulo de inventario.',
];
