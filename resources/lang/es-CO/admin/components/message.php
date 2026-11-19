<?php

return [

    'does_not_exist' => 'El componente no existe.',

    'create' => [
        'error' => 'El componente no fue creado, por favor inténtelo de nuevo.',
        'success' => 'El componente se creó satisfactoriamente.',
    ],

    'update' => [
        'error' => 'El componente no se actualizó, por favor inténtelo de nuevo',
        'success' => 'Componente actualizado correctamente.',
    ],

    'restore' => [
        'error' => 'El componente no se restauró, inténtelo de nuevo',
        'success' => 'Componente restaurado correctamente.',
    ],

    'delete' => [
        'confirm' => '¿Está seguro de que desea eliminar este componente?',
        'error' => 'Hubo un problema eliminando el componente. Por favor, inténtelo de nuevo.',
        'success' => 'El componente fue borrado correctamente.',
        'error_qty' => 'Todavía hay componentes de este tipo asignados. Ingréselos e inténtelo de nuevo.',
    ],

    'checkout' => [
        'error' => 'El componente no fue asignado, por favor, inténtelo de nuevo',
        'success' => 'El componente se ha asignado correctamente.',
        'user_does_not_exist' => 'Ese usuario no es válido. Por favor, inténtelo de nuevo.',
        'unavailable' => 'No quedan suficientes componentes: :remaining disponibles, :requested solicitados ',
    ],

    'bulk' => [
        'no_components_selected' => 'No se seleccionó ningún componente.',
        'no_action_selected' => 'Elija una acción en el menú desplegable.',
        'no_fields_updated' => 'Ningún cambio fue cambiado, así que nada se actualizó.',
        'skipped_unauthorized' => 'Se omitieron :count componente(s) porque no tiene permiso para actualizarlos.',

        'update' => [
            'success' => 'Se actualizó :count componente.|Se actualizaron :count componentes.',
            'none_updated' => 'No se pudo actualizar el componente seleccionado. Vea los errores de abajo.|No se pudo actualizar ninguno de los componentes seleccionados. Vea los errores de abajo.',
        ],

        'delete' => [
            'success' => 'Se eliminó :count componente.|Se eliminaron :count componentes.',
            'partial_success' => 'Se eliminó :count componente. Vea abajo los errores del resto.|Se eliminaron :count componentes. Vea abajo los errores del resto.',
        ],

        'restore' => [
            'success' => 'Se restauró :count componente.|Se restauraron :count componentes.',
        ],

        'checkin_selected' => [
            'no_rows_selected' => 'No se seleccionó ningún componente asignado.',
            'success' => 'Se ingresó :unit_count unidad en :count asignación.|Se ingresaron :unit_count unidades en :count asignaciones.',
        ],
    ],

    'checkin' => [
        'error' => 'No se realizó el ingreso del componente, por favor inténtelo de nuevo',
        'success' => 'Componente ingresado con éxito.',
        'user_does_not_exist' => 'Ese usuario no es válido. Por favor, inténtelo de nuevo.',
    ],

];
