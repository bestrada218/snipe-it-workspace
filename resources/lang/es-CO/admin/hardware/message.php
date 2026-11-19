<?php

return [

    'undeployable' => 'Los siguientes activos no se pueden desplegar y se han eliminado del checkout: :asset_tags',
    'does_not_exist' => 'El recurso no existe.',
    'does_not_exist_var' => 'Activo con placa :asset_tag no encontrado.',
    'no_tag' => 'No se ha proporcionado ninguna placa de activo.',
    'does_not_exist_or_not_requestable' => 'Ese activo no existe o no puede ser solicitado.',
    'assoc_users' => 'Actualmente este activo está asignado a un usuario y no puede ser eliminado. Por favor, primero ingrese el activo y vuelva a intentarlo. ',
    'warning_audit_date_mismatch' => 'La próxima fecha de auditoría de este activo (:next_audit_date) es anterior a la última fecha de auditoría (:last_audit_date). Por favor, actualice la próxima fecha de auditoría.',
    'labels_generated' => 'Las etiquetas fueron generadas exitosamente.',
    'error_generating_labels' => 'Error en la generación de etiquetas.',
    'no_assets_selected' => 'No se han seleccionado activos.',

    'create' => [
        'error' => 'El activo no fue creado, por favor, inténtelo de nuevo. :(',
        'success' => 'Activo creado con éxito. :)',
        'success_no_checkout' => 'El activo se creó, pero no se asignó porque usted no tiene permiso para asignar activos.',
        'checkout_skipped_no_permission' => 'El activo se creó, pero no se asignó al destino indicado porque usted no tiene permiso para asignar activos.',
        'success_linked' => 'Activo con placa :tag creado con éxito. <strong><a href=":link" style="color: white;">Haga clic aquí para ver</a></strong>.',
        'multi_success_linked' => 'Activo con etiqueta :links fue creado exitosamente.|:count activos fueron creados correctamente. :links.',
        'partial_failure' => 'No se ha podido crear un activo: Motivo: :failures|No se pudieron crear :count activos. Motivos: :faiulres',
        'target_not_found' => [
            'user' => 'El usuario asignado no pudo ser encontrado.',
            'asset' => 'No se ha encontrado el recurso asignado.',
            'location' => 'No se pudo encontrar la ubicación asignada.',
        ],
    ],

    'update' => [
        'error' => 'El activo no pudo ser actualizado, por favor inténtelo de nuevo',
        'success' => 'Equipo actualizado correctamente.',
        'encrypted_warning' => 'El activo se actualizó correctamente, pero los campos personalizados cifrados no lo hicieron debido a los permisos',
        'nothing_updated' => 'Ningún campo fue seleccionado, por lo que no se actualizó nada.',
        'no_assets_selected' => 'Ningún activo fue seleccionado, por lo que no se actualizó nada.',
        'assets_do_not_exist_or_are_invalid' => 'Los activos seleccionados no se pueden actualizar.',
    ],

    'bulk_update' => [
        'success' => 'Activo actualizado correctamente.|Se actualizaron :count activos correctamente.',
        'partial' => ':success activo(s) actualizados correctamente y :failed con error. Vea el arreglo de resultados para más detalles.',
        'error' => 'No se actualizó ningún activo. Vea el arreglo de resultados para más detalles.',
    ],

    'restore' => [
        'error' => 'El activo no fue restaurado, por favor inténtelo nuevamente',
        'success' => 'Activo restaurado exitosamente.',
        'bulk_success' => 'Activo restaurado exitosamente.',
        'nothing_updated' => 'No se seleccionaron activos, por lo que no se restauró nada.',
    ],

    'audit' => [
        'error' => 'Auditoría de activos fallida: :error ',
        'success' => 'Auditoría de activos registrada correctamente.',
    ],

    'deletefile' => [
        'error' => 'Archivo no eliminado. Por favor inténtelo nuevamente.',
        'success' => 'Archivo eliminado correctamente.',
    ],

    'upload' => [
        'error' => 'Archivo(s) no cargado(s). Por favor, inténtelo nuevamente.',
        'success' => 'Archivo(s) cargado(s) exitosamente.',
        'nofiles' => 'No seleccionó ningún archivo para ser cargado, o el archivo que está tratando de cargar es demasiado grande',
        'invalidfiles' => 'Uno o más de sus archivos son demasiado grandes o son de un tipo de archivo que no está permitido. Los tipos de archivo permitidos son png, gif, jpg, doc, docx, pdf y txt.',
    ],

    'import' => [
        'import_button' => 'Importar',
        'error' => 'Algunos elementos no se pudieron importar correctamente.',
        'errorDetail' => 'Los siguientes elementos no fueron importados debido a errores.',
        'success' => 'Su archivo ha sido importado',
        'file_delete_success' => 'Su archivo se ha eliminado correctamente',
        'file_delete_error' => 'El archivo no se pudo eliminar',
        'file_missing' => 'Falta el archivo seleccionado',
        'file_already_deleted' => 'El archivo seleccionado ya fue eliminado',
        'file_missing_on_disk' => 'El archivo de esta importación ya no está en el disco. Puede que se haya borrado fuera de Snipe-IT. Elimine esta entrada y vuelva a cargar el archivo.',
        'file_empty' => 'Este archivo no tiene filas de datos. No hay nada que importar.',
        'already_processing' => 'Otra persona está procesando esta importación. Espere a que termine antes de volver a intentarlo.',
        'header_row_missing' => 'Este archivo no tiene una fila de encabezados reconocida. Elimine esta entrada y vuelva a cargar el archivo.',
        'header_row_has_malformed_characters' => 'Uno o más atributos en la fila del encabezado contienen caracteres UTF-8 mal formados',
        'content_row_has_malformed_characters' => 'Uno o más atributos en la primera fila contienen caracteres UTF-8 mal formados',
        'transliterate_failure' => 'La transliteración de :encoding a UTF-8 falló debido a caracteres no válidos en la entrada',
        'bulk_delete' => [
            'button' => 'Eliminar seleccionados (:count)',
            'confirm_title' => '¿Eliminar los archivos de importación seleccionados?',
            'confirm_body' => 'Está a punto de eliminar definitivamente :count archivo(s) de importación. No se puede deshacer.',
            'confirm_button' => 'Eliminar',
            'success' => 'Archivo de importación eliminado correctamente.|Se eliminaron :count archivos de importación correctamente.',
            'skipped' => 'Se omitieron :count archivo(s) porque no tiene permiso para eliminarlos.',
            'select_all' => 'Seleccionar todos los archivos de esta página',
            'select_row' => 'Seleccionar :file para eliminar',
        ],
        'row_count' => '{0} Este archivo no tiene filas de datos|{1} :count fila de datos por importar|[2,*] :count filas de datos por importar',
        'summary' => [
            'created' => 'Creado',
            'updated' => 'Actualizado',
            'skipped' => 'Omitidas por duplicadas',
            'errored' => 'Con error',
            'no_changes' => 'La importación terminó, pero no se creó ni actualizó nada. Todas las filas se omitieron, normalmente porque los registros ya existían. Revise los conteos de abajo y ajuste el CSV o el tipo de importación si no es lo que esperaba.',
        ],
        'type_required' => 'Seleccione un tipo de importación antes de continuar.',
        'processing' => 'Procesando su importación. Espere a que termine antes de cerrar la página.',
        'backup_running' => 'Haciendo una copia de seguridad antes de importar. Con archivos grandes puede tardar. Espere, por favor.',
        'backup_label' => 'Copia de seguridad previa a la importación',
        'backup_complete' => 'Copia de seguridad completada',
        'import_label' => 'Importar',
        'required_fields_missing' => 'Estos campos obligatorios no están asignados: :fields',
        'history' => [
            'missing_asset_tag_identity' => '(sin placa del activo)',
            'missing_asset_tag_message' => 'Fila omitida: no tiene placa del activo.',
            'asset_not_found_message' => 'No existe un activo con esta placa. Importe primero los activos y luego vuelva a ejecutar la importación del historial.',
            'target_not_matched_message' => 'Ningún :target_type coincide con ":name". Para personas, cambie las opciones de coincidencia del paso 1 o cree la persona primero. Para ubicaciones, verifique que el nombre en el CSV coincida exactamente con una ubicación existente.',
            'invalid_target_type_message' => 'El Target Type ":value" no se reconoce. Use "user" o "location", o deje la columna vacía para usar user.',
        ],
        'wizard' => [
            'step_type' => 'Elegir tipo',
            'step_map' => 'Asignar campos',
            'step_preview' => 'Vista previa',
            'back' => 'Atrás',
            'next' => 'Siguiente',
            'preview_button' => 'Vista previa',
            'process' => 'Procesar importación',
            'preview_intro' => 'Vista previa de las primeras :count fila(s) con su asignación de campos. Use el botón Atrás si necesita cambiar los campos antes de importar.',
        ],
    ],

    'delete' => [
        'confirm' => '¿Está seguro de que desea eliminar este activo?',
        'error' => 'Hubo un problema al eliminar el activo. Por favor, inténtelo de nuevo.',
        'assigned_to_error' => '{1}La placa :asset_tag está asignada actualmente. Ingrese este equipo antes de eliminarlo.|[2,*]Las placas :asset_tag están asignadas actualmente. Ingrese estos equipos antes de eliminarlos.',
        'nothing_updated' => 'No se seleccionaron activos, por lo que no se eliminó nada.',
        'success' => 'El activo se ha eliminado correctamente.',
    ],

    'checkout' => [
        'error' => 'El activo no fue asignado, por favor inténtelo de nuevo',
        'success' => 'Equipo asignado correctamente.',
        'user_does_not_exist' => 'Este usuario no es correcto. Por favor, inténtelo de nuevo.',
        'not_available' => '¡Ese equipo no está disponible para ser asignado!',
        'no_assets_selected' => 'Debe seleccionar al menos un activo de la lista',
    ],

    'multi-checkout' => [
        'error' => 'El activo no fue asignado, por favor, intente nuevamente|Los activos no fueron asignados, por favor, intente nuevamente',
        'success' => 'El activo fue asignado correctamente|Los activos fueron asignados correctamente.',
    ],

    'multi-checkin' => [
        'error' => 'El activo no se ingresó, inténtelo de nuevo|Los activos no se ingresaron, inténtelo de nuevo',
        'success' => 'Activo ingresado correctamente.|Activos ingresados correctamente.',
        'no_assets_selected' => 'Debe seleccionar al menos un activo de la lista',
    ],

    'multi-audit' => [
        'success' => ':count activo auditado correctamente.|:count activos auditados correctamente.',
        'partial_error' => ':success activo auditado y :failed con error. Revise los errores de abajo e inténtelo de nuevo.|:success activos auditados y :failed con error. Revise los errores de abajo e inténtelo de nuevo.',
        'no_assets_selected' => 'Debe seleccionar al menos un activo de la lista',
    ],

    'checkin' => [
        'error' => 'El activo no se pudo ingresar, por favor inténtelo de nuevo',
        'success' => 'El activo fue ingresado exitosamente.',
        'user_does_not_exist' => 'Este usuario no es correcto. Por favor, inténtelo de nuevo.',
        'already_checked_in' => 'El equipo ya ha sido recibido.',
        'force_checkin_orphaned_success' => 'Asignación inválida eliminada correctamente.',
        'force_checkin_not_orphaned' => 'El elemento no tiene una asignación inválida.',
        'force_checkin_error' => 'No se pudo eliminar la asignación inválida.',

    ],

    'requests' => [
        'error' => 'La solicitud no se realizó correctamente, por favor inténtelo de nuevo.',
        'success' => 'Solicitud enviada con éxito.',
        'canceled' => 'Petición cancelada con éxito.',
        'cancel' => 'Cancelar solicitud para este elemento',
        'duplicate' => 'Ya tiene una solicitud activa para este elemento.',
        'no_active' => 'No tiene una solicitud activa que cancelar para este elemento.',
        'insufficient_stock' => 'No hay existencias suficientes para atender esta solicitud. Reponga primero.',
        'confirm_cancel_by_admin' => '¿Cancelar la solicitud de :user para :item?',
        'no_selection' => 'No se seleccionó ninguna fila para atender.',
        'row_stale' => 'La solicitud #:id ya no está pendiente y se omitió.',
        'row_qty_invalid' => 'La solicitud #:id tiene una cantidad inválida y se omitió.',
        'row_user_missing' => 'La solicitud #:id no tiene una persona destino válida y se omitió.',
        'row_asset_missing' => 'La solicitud #:id no tiene un activo destino válido y se omitió.',
        'row_asset_not_requesters' => 'Solicitud #:id omitida: el activo seleccionado no pertenece a quien la hizo.',
        'row_asset_not_available' => 'Solicitud #:id omitida: el activo seleccionado no es una unidad disponible de este modelo.',
        'row_asset_taken' => 'Solicitud #:id omitida: el activo seleccionado se asignó a otra persona antes de enviar.',
        'row_company_mismatch' => 'Solicitud #:id omitida: :user no puede recibir elementos de esta compañía.',
        'row_over_allocated' => 'Solicitud #:id omitida: no había existencias suficientes al enviar.',
        'no_target_assets_for_user' => ':user no tiene activos en los que instalar.',
        'no_available_units' => 'No hay unidades disponibles de este modelo para entregar.',
        'bulk_summary' => 'Se atendieron :fulfilled de :total solicitudes.',
        'bulk_fulfill_notification_intro' => 'Al atender cada solicitud marcada se aplicará lo siguiente:',
    ],

];
