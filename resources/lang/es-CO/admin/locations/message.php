<?php

return [

    'does_not_exist' => 'La ubicación no existe.',
    'assoc_users' => 'Esta ubicación no se puede eliminar porque es la ubicación registrada de al menos un elemento o persona, tiene activos asignados o es la ubicación superior de otra. Actualice sus registros para que ya no hagan referencia a esta ubicación e inténtelo de nuevo',
    'assoc_assets' => 'Esta ubicación está actualmente asociada con al menos un activo y no puede ser eliminada. Por favor actualice sus activos para que ya no hagan referencia a esta ubicación e inténtelo de nuevo. ',
    'assoc_child_loc' => 'Esta ubicación es actualmente el padre de al menos una ubicación hija y no puede ser eliminada.   Por favor actualice sus ubicaciones para que ya no hagan referencia a esta ubicación e inténtelo de nuevo. ',
    'assigned_assets' => 'Activos asignados',
    'current_location' => 'Ubicación actual',
    'deleted_warning' => 'Esta ubicación fue eliminada. Restáurela antes de hacer cambios.',

    'create' => [
        'error' => 'La ubicación no pudo ser creada, por favor, inténtelo de nuevo.',
        'success' => 'La ubicación fue creada exitosamente.',
    ],

    'update' => [
        'error' => 'La ubicación no pudo ser actualizada, por favor inténtelo de nuevo',
        'success' => 'La ubicación fue actualizada exitosamente.',
    ],

    'restore' => [
        'error' => 'No se ha restaurado la ubicación, inténtelo de nuevo',
        'success' => 'La ubicación fue restaurada exitosamente.',
    ],

    'delete' => [
        'confirm' => '¿Está seguro de que desea eliminar esta ubicación?',
        'error' => 'Hubo un problema eliminando la ubicación. Por favor, inténtelo de nuevo.',
        'success' => 'La ubicación fue eliminada exitosamente.',
    ],

    'bulkedit' => [
        'error' => 'Ningún cambio fue cambiado, así que nada se actualizó.',
        'success' => 'Ubicación actualizada correctamente.|Se actualizaron :count ubicaciones correctamente.',
        'warn' => 'Edite los campos de abajo para actualizar esta ubicación. Los campos que deje en blanco no cambian.|Edite los campos de abajo para actualizar las :count ubicaciones seleccionadas. Los campos que deje en blanco no cambian en ninguna.',
        'show_selected' => '1 ubicación seleccionada|:count ubicaciones seleccionadas',
        'company_scope_mismatch_partial' => 'No se cambió la compañía de 1 ubicación porque tiene elementos o personas de otras compañías. Actualícelos o muévalos primero.|No se cambió la compañía de :count ubicaciones porque tienen elementos o personas de otras compañías. Actualícelos o muévalos primero.',
        'company_scope_mismatch_all' => 'No se reasignó ninguna ubicación. La compañía indicada no coincide con los elementos o personas de la ubicación seleccionada.|No se reasignó ninguna ubicación. La compañía indicada no coincide con los elementos o personas de ninguna de las :count ubicaciones seleccionadas.',
        'parent_company_mismatch_partial' => 'No se cambió la ubicación superior o la compañía de 1 ubicación porque quedaría en una compañía distinta a la de su ubicación superior.|No se cambió la ubicación superior o la compañía de :count ubicaciones porque quedarían en una compañía distinta a la de su ubicación superior.',
        'parent_company_mismatch_all' => 'No se guardó ningún cambio. La ubicación superior o la compañía indicada dejaría la ubicación en una compañía distinta a la de su ubicación superior.|No se guardó ningún cambio. La ubicación superior o la compañía indicada dejaría cada una de las :count ubicaciones seleccionadas en una compañía distinta a la de su ubicación superior.',
    ],

];
