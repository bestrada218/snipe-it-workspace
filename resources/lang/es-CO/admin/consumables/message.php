<?php

return [

    'invalid_category_type' => 'La categoría debe ser una categoría para consumibles.',
    'does_not_exist' => 'El consumible no existe.',

    'create' => [
        'error' => 'El consumible no fue creado, por favor inténtelo nuevamente.',
        'success' => 'Consumible creado con éxito.',
    ],

    'update' => [
        'error' => 'El consumible no fue actualizado, por favor, inténtelo de nuevo',
        'success' => 'Consumible actualizado correctamente.',
    ],

    'delete' => [
        'confirm' => '¿Está seguro de que desea eliminar este consumible?',
        'error' => 'Hubo un problema al eliminar el consumible. Por favor inténtelo de nuevo.',
        'success' => 'El consumible ha sido eliminado correctamente.',
        'bulk_success' => 'Consumible eliminado correctamente.|Se eliminaron :count consumibles correctamente.',
        'partial_success' => 'Se eliminó :count consumible correctamente, pero otros no se pudieron eliminar. Vea los detalles abajo.|Se eliminaron :count consumibles correctamente, pero otros no se pudieron eliminar. Vea los detalles abajo.',
    ],

    'checkout' => [
        'error' => 'El consumible no fue asignado, por favor inténtelo de nuevo',
        'success' => 'Consumible asignado correctamente.',
        'user_does_not_exist' => 'Ese usuario no es válido. Por favor, inténtelo de nuevo.',
        'unavailable' => 'No hay suficientes consumibles para esta asignación. Por favor, compruebe la cantidad disponible. ',
    ],

    'consumed' => [
        'success' => 'Entrega marcada como consumida.',
        'bulk_success' => 'Se marcó :count entrega como consumida.|Se marcaron :count entregas como consumidas.',
        'already' => 'Esa entrega ya estaba marcada como consumida.',
        'error' => 'No se pudo marcar la entrega como consumida, inténtelo de nuevo.',
        'no_rows_selected' => 'No se seleccionó ninguna entrega válida.',
    ],

    'checkin' => [
        'error' => 'El consumible no fue ingresado, por favor inténtelo de nuevo',
        'success' => 'El consumible fue ingresado correctamente.',
        'user_does_not_exist' => 'Ese usuario no es válido. Por favor, inténtelo de nuevo.',
    ],

    'restore' => [
        'error' => 'El consumible no se restauró, inténtelo de nuevo',
        'success' => 'Consumible restaurado correctamente.',
    ],

    'bulk' => [
        'no_consumables_selected' => 'No se seleccionó ningún consumible.',
        'no_action_selected' => 'Elija una acción en el menú desplegable.',
        'no_fields_updated' => 'Ningún cambio fue cambiado, así que nada se actualizó.',
        'skipped_unauthorized' => 'Se omitieron :count consumible(s) porque no tiene permiso para actualizarlos.',

        'update' => [
            'success' => 'Se actualizó :count consumible.|Se actualizaron :count consumibles.',
            'none_updated' => 'No se pudo actualizar el consumible seleccionado. Vea los errores de abajo.|No se pudo actualizar ninguno de los consumibles seleccionados. Vea los errores de abajo.',
        ],

        'restore' => [
            'success' => 'Se restauró :count consumible.|Se restauraron :count consumibles.',
        ],
    ],
];
