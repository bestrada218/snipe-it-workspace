<?php

return [

    'deleted' => 'Se eliminó el modelo del activo',
    'does_not_exist' => 'Modelo inexistente.',
    'no_association' => '¡ADVERTENCIA! ¡El modelo de activo para este artículo no es válido o no existe!',
    'no_association_fix' => 'Esto causará problemas raros y horribles. Edite este activo ahora para asignarle un modelo.',
    'assoc_users' => 'Este modelo está asociado a uno o más activos y no puede ser eliminado. Por favor, elimine los activos y vuelva a intentarlo. ',
    'invalid_category_type' => 'El tipo de esta categoría debe ser categoría de activos.',

    'create' => [
        'error' => 'El modelo no fue creado, por favor inténtelo de nuevo.',
        'success' => 'El modelo fue creado exitosamente.',
        'duplicate_set' => 'Ya existe un modelo de activo con el mismo nombre, fabricante y número de modelo.',
    ],

    'update' => [
        'error' => 'El modelo no pudo ser actualizado, por favor inténtelo de nuevo',
        'success' => 'El modelo fue actualizado exitosamente.',
    ],

    'delete' => [
        'confirm' => '¿Está seguro de que desea eliminar este modelo de activo?',
        'error' => 'Hubo un problema eliminando el modelo. Por favor, inténtelo de nuevo.',
        'success' => 'El modelo fue eliminado exitosamente.',
    ],

    'restore' => [
        'error' => 'El modelo no fue restaurado, por favor intente nuevamente',
        'success' => 'El modelo fue restaurado exitosamente.',
    ],

    'bulkedit' => [
        'error' => 'Ningún cambio fue cambiado, así que nada se actualizó.',
        'success' => 'Modelo actualizado correctamente. |:model_count modelos actualizados correctamente.',
        'warn' => 'Está a punto de actualizar las propiedades del siguiente modelo:|Está a punto de editar las propiedades de los siguientes :model_count modelos:',

    ],

    'bulkdelete' => [
        'error' => 'Ningún modelo fue seleccionado, no se eliminó nada.',
        'nothing_deletable' => 'No se puede eliminar ninguno de los modelos seleccionados porque aún tienen activos asociados.',
        'success' => 'Modelo eliminado!|:success_count modelos eliminados!',
        'success_partial' => ':success_count modelo(s) fueron eliminados, sin embargo, :fail_count no pudieron ser eliminados debido a que aún tienen activos asociados a ellos.',
    ],

    'merge' => [
        'min_two' => 'Seleccione al menos dos modelos para combinar.',
        'no_target' => 'Seleccione qué modelo conservar antes de combinar.',
        'not_found' => 'No se pudo cargar uno o más de los modelos seleccionados. Actualice la lista de modelos e inténtelo de nuevo.',
        'information' => 'Está a punto de combinar :count modelos. Elija el modelo que quiere conservar. Todos los activos de los demás modelos se reasignarán al modelo elegido y luego se eliminarán los modelos de origen.',
        'warning' => 'No se puede deshacer. Los activos reasignados heredarán la categoría, el grupo de campos y la depreciación del modelo que se conserva.',
        'pick_target' => '¿Qué modelo quiere conservar?',
        'success' => 'Se combinaron :source_count modelo(s) en ":target". Se reasignaron :asset_count activo(s).',
    ],

];
