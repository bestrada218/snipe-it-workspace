<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Permissions
    |--------------------------------------------------------------------------
    | The following language lines are used in the user permissions system.
    | Each permission has a 'name' and a 'note' that describes
    | the permission in detail.
    |
    | DO NOT edit the keys (left-hand side) of each permission as these are
    | used throughout the system for translations.
    |---------------------------------------------------------------------------
    */

    'superuser' => [
        'name' => 'Super Usuario',
        'note' => 'Determina si la persona tiene acceso completo a todo el sistema. Esta opción anula TODOS los permisos más específicos y restrictivos del sistema.',
    ],
    'admin' => [
        'name' => 'Acceso de administrador',
        'note' => 'Determina si la persona tiene acceso a casi todo el sistema EXCEPTO la configuración de administración. Puede gestionar personas, ubicaciones, categorías, etc., pero SÍ queda limitada por el soporte completo para múltiples compañías si está activo.',
    ],

    'import' => [
        'name' => 'Importar CSV',
        'note' => 'Esto permitirá a los usuarios importar aunque se niegue el acceso a usuarios, activos, etc en otros lugares.',
    ],

    'reports' => [
        'name' => 'Acceso a Informes',
        'note' => 'Determina si la persona tiene acceso a la sección de informes.',
    ],

    'assets' => [
        'name' => 'Activos',
        'note' => 'Dar acceso a la sección de Activos de la aplicación. ',
    ],

    'assetsview' => [
        'name' => 'Ver activos',
        'note' => 'Las personas con este permiso también podrán ver (sin modificar ni eliminar) los archivos cargados en el modelo del activo. Así se comparten documentos comunes, como manuales, sin cargarlos en cada activo y sin dar permiso para modificar archivos de activos. También podrán ver el historial de ediciones e ingresos.',
    ],

    'assetscreate' => [
        'name' => 'Crear nuevo activo',
    ],

    'assetsedit' => [
        'name' => 'Editar Activo',
    ],

    'assetsdelete' => [
        'name' => 'Eliminar Activos',
    ],

    'assetscheckin' => [
        'name' => 'Ingresar',
        'note' => 'Ingresar al inventario activos que están asignados.',
    ],

    'assetscheckout' => [
        'name' => 'Asignar',
        'note' => 'Asignar activos del inventario.',
    ],

    'assetsaudit' => [
        'name' => 'Auditar Activos',
        'note' => 'Permite marcar un activo como inventariado físicamente.',
    ],

    'assetsviewrequestable' => [
        'name' => 'Ver elementos solicitables',
        'note' => 'Permite ver los elementos marcados como solicitables.',
    ],

    'assetsviewencrypted-custom-fields' => [
        'name' => 'Ver Campos Personalizados Encriptados',
        'note' => 'Permite ver y modificar los campos personalizados cifrados de los activos.',
    ],

    'accessories' => [
        'name' => 'Accesorios',
        'note' => 'Da acceso a la sección de accesorios.',
    ],

    'accessoriesview' => [
        'name' => 'Ver Accesorios',
    ],
    'accessoriescreate' => [
        'name' => 'Crear nuevos accesorios',
    ],
    'accessoriesedit' => [
        'name' => 'Editar Accesorios',
    ],
    'accessoriesdelete' => [
        'name' => 'Eliminar Accesorios',
    ],
    'accessoriescheckout' => [
        'name' => 'Asignar accesorios',
        'note' => 'Asignar accesorios del inventario.',
    ],
    'accessoriescheckin' => [
        'name' => 'Ingresar accesorios',
        'note' => 'Ingresar al inventario accesorios que están asignados.',
    ],
    'accessoriesfiles' => [
        'name' => 'Ges',
        'note' => 'Permite cargar, descargar y eliminar archivos de accesorios. (Solo tiene sentido con permiso de ver o superior).',
    ],

    'assetsfiles' => [
        'name' => 'Gestionar Archivos de Activos',
        'note' => 'Permite cargar, descargar y eliminar archivos de activos. (Solo tiene sentido con permiso de ver o superior).',
    ],

    'usersfiles' => [
        'name' => 'Administrar archivos de usuario',
        'note' => 'Permite cargar, descargar y eliminar archivos de personas. (Solo tiene sentido con permiso de ver o superior).',
    ],

    'modelsfiles' => [
        'name' => 'Gestionar Archivos de Modelos',
        'note' => 'Permite cargar, descargar y eliminar archivos de modelos de activo, tanto en la vista del modelo como en la del activo. (Solo tiene sentido con permiso de ver o superior).',
    ],

    'departmentsfiles' => [
        'name' => 'Gestionar Ficheros del Departamento',
        'note' => 'Permite cargar, descargar y eliminar archivos de departamentos. (Solo tiene sentido con permiso de ver o superior).',
    ],

    'suppliersfiles' => [
        'name' => 'Gestionar Ficheros del Proveedor',
        'note' => 'Permite cargar, descargar y eliminar archivos de proveedores. (Solo tiene sentido con permiso de ver o superior).',
    ],

    'locationsfiles' => [
        'name' => 'Gestionar Archivos de Ubicaciones',
        'note' => 'Permite cargar, descargar y eliminar archivos de ubicaciones. (Solo tiene sentido con permiso de ver o superior).',
    ],

    'companiesfiles' => [
        'name' => 'Gestionar Archivos de Empresa',
        'note' => 'Permite al usuario subir, descargar y eliminar archivos asociados con empresas. (Esto sólo tiene sentido con privilegios de visualización o superiores)',
    ],

    'consumablesfiles' => [
        'name' => 'Gestionar Ficheros de los Consumibles',
        'note' => 'Permite cargar, descargar y eliminar archivos de consumibles. (Solo tiene sentido con permiso de ver o superior).',
    ],

    'consumables' => [
        'name' => 'Consumibles',
        'note' => 'Da acceso a la sección de consumibles.',
    ],
    'consumablesview' => [
        'name' => 'Ver Comsumibles',
    ],
    'consumablescreate' => [
        'name' => 'Crear nuevos consumibles',
    ],
    'consumablesedit' => [
        'name' => 'Editar Consumibles',
    ],
    'consumablesdelete' => [
        'name' => 'Eliminar Consumibles',
    ],
    'consumablescheckout' => [
        'name' => 'Asignar consumibles',
        'note' => 'Asignar consumibles del inventario.',
    ],

    'licenses' => [
        'name' => 'Licencias',
        'note' => 'Da acceso a la sección de licencias.',
    ],
    'licensesview' => [
        'name' => 'Ver licencias',
    ],
    'licensescreate' => [
        'name' => 'Crear Nuevas Licencias',
    ],
    'licensesedit' => [
        'name' => 'Editar Licencias',
    ],
    'licensesdelete' => [
        'name' => 'Eliminar Licencias',
    ],
    'licensescheckout' => [
        'name' => 'Asignar Licencias',
        'note' => 'Permitir al usuario asignar licencias a activos o a usuarios.',
    ],
    'licensescheckin' => [
        'name' => 'Desvinculación Licencias',
        'note' => 'Permite al usuario desvincular licencias de activos o de usuarios.',
    ],
    'licensesfiles' => [
        'name' => 'Gestionar Ficheros de Licencias',
        'note' => 'Permitir al usuario subir, descargar, y eliminar ficheros asociados a licencias. ',
    ],
    'componentsfiles' => [
        'name' => 'Gestionar Ficheros de Componentes',
        'note' => 'Permite cargar, descargar y eliminar archivos de componentes.',
    ],

    'licenseskeys' => [
        'name' => 'Administrar claves de licencia',
        'note' => 'Permite ver las claves de producto de las licencias.',
    ],
    'components' => [
        'name' => 'Componentes',
        'note' => 'Da acceso a la sección de componentes.',
    ],
    'componentsview' => [
        'name' => 'Ver componentes',
    ],
    'componentscreate' => [
        'name' => 'Crear nuevos componentes',
    ],
    'componentsedit' => [
        'name' => 'Editar Componentes',
    ],
    'componentsdelete' => [
        'name' => 'Eliminar Componentes',
    ],

    'componentscheckout' => [
        'name' => 'Asignar componentes',
        'note' => 'Asignar componentes del inventario.',
    ],
    'componentscheckin' => [
        'name' => 'Ingresar componentes',
        'note' => 'Ingresar al inventario componentes que están asignados.',
    ],
    'kits' => [
        'name' => 'Kits predefinidos',
        'note' => 'Da acceso a la sección de kits predefinidos.',
    ],
    'kitsview' => [
        'name' => 'Ver Kits Predefinidos',
    ],
    'kitscreate' => [
        'name' => 'Crear Nuevos Kit Predefinidos',
    ],
    'kitsedit' => [
        'name' => 'Editar Kits Predefinidos',
    ],
    'kitsdelete' => [
        'name' => 'Eliminar Kits Predefinidos',
    ],
    'users' => [
        'name' => 'Usuarios',
        'note' => 'Da acceso a la sección de personas.',
    ],
    'usersview' => [
        'name' => 'Ver usuarios',
        'note' => 'Las personas con este permiso también podrán ver (sin modificar ni eliminar) los archivos cargados a la persona, y el historial de ediciones e ingresos.',
    ],
    'userscreate' => [
        'name' => 'Crear Nuevos Usuarios',
    ],
    'usersedit' => [
        'name' => 'Editar Usuarios',
    ],
    'usersdelete' => [
        'name' => 'Eliminar Usuarios',
    ],
    'models' => [
        'name' => 'Modelos',
        'note' => 'Dar acceso a la sección de Modelos de la aplicación.',
    ],
    'modelsview' => [
        'name' => 'Ver modelos',
    ],

    'modelscreate' => [
        'name' => 'Crear nuevos modelos',
    ],
    'modelsedit' => [
        'name' => 'Editar Modelos',
    ],
    'modelsdelete' => [
        'name' => 'Eliminar Modelos',
    ],
    'categories' => [
        'name' => 'Categorías',
        'note' => 'Dar acceso a la sección de Categorías de la aplicación.',
    ],
    'categoriesview' => [
        'name' => 'Ver categorias',
    ],
    'categoriescreate' => [
        'name' => 'Crer Nuevas Categorias',
    ],
    'categoriesedit' => [
        'name' => 'Editar Categorias',
    ],
    'categoriesdelete' => [
        'name' => 'Eliminar Categorias',
    ],
    'departments' => [
        'name' => 'Departamentos',
        'note' => 'Dar acceso a la sección de Departamentos de la aplicación.',
    ],
    'departmentsview' => [
        'name' => 'Ver Departamentos',
    ],
    'departmentscreate' => [
        'name' => 'Crear nuevos departamentos',
    ],
    'departmentsedit' => [
        'name' => 'Editar Departamentos',
    ],
    'departmentsdelete' => [
        'name' => 'Eliminar Departamentos',
    ],
    'locations' => [
        'name' => 'Ubicaciones',
        'note' => 'Da acceso a la sección de ubicaciones.',
    ],
    'locationsview' => [
        'name' => 'Ver Ubicaciones',
    ],
    'locationscreate' => [
        'name' => 'Crear Nuevas Ubicaciones',
    ],
    'locationsedit' => [
        'name' => 'Editar Ubicaciones',
    ],
    'locationsdelete' => [
        'name' => 'Eliminar Ubicaciones',
    ],
    'status-labels' => [
        'name' => 'Etiquetas de estado',
        'note' => 'Da acceso a la sección de etiquetas de estado de los activos.',
    ],
    'statuslabelsview' => [
        'name' => 'Ver etiquetas de estado',
    ],
    'statuslabelscreate' => [
        'name' => 'Crear etiquetas de estado',
    ],
    'statuslabelsedit' => [
        'name' => 'Editar etiquetas de estado',
    ],
    'statuslabelsdelete' => [
        'name' => 'Eliminar Etiquetas de Estado',
    ],
    'custom-fields' => [
        'name' => 'Campos personalizados',
        'note' => 'Dar acceso a la sección de Campos Personalizados de la aplicación utilizada por los Activos.',
    ],
    'customfieldsview' => [
        'name' => 'Ver Campos Personalizados',
    ],
    'customfieldscreate' => [
        'name' => 'Crear Nuevos Campos Personalizados',
    ],
    'customfieldsedit' => [
        'name' => 'Editar Campos Personalizados',
    ],
    'customfieldsdelete' => [
        'name' => 'Eliminar Campos Personalizados',
    ],
    'suppliers' => [
        'name' => 'Proveedores',
        'note' => 'Dar acceso a la sección de Proveedores  de la aplicación. ',
    ],
    'suppliersview' => [
        'name' => 'Ver proveedor',
    ],
    'supplierscreate' => [
        'name' => 'Crear nuevos proveedores',
    ],
    'suppliersedit' => [
        'name' => 'Editar Proveedores',
    ],
    'suppliersdelete' => [
        'name' => 'Eliminar Proveedores',
    ],
    'manufacturers' => [
        'name' => 'Fabricantes',
        'note' => 'Da acceso a la sección de fabricantes.',
    ],
    'manufacturersview' => [
        'name' => 'Ver Fabricantes',
    ],
    'manufacturerscreate' => [
        'name' => 'Crear Nuevos Fabricantes',
    ],
    'manufacturersedit' => [
        'name' => 'Editar Fabricantes',
    ],
    'manufacturersdelete' => [
        'name' => 'Eliminar Fabricantes',
    ],
    'companies' => [
        'name' => 'Compañías',
        'note' => 'Da acceso a la sección de compañías.',
    ],
    'companiesview' => [
        'name' => 'Ver empresas',
    ],
    'companiescreate' => [
        'name' => 'Crear Nuevas Empresas',
    ],
    'companiesedit' => [
        'name' => 'Editar Empresas',
    ],
    'companiesdelete' => [
        'name' => 'Eliminar Empresas',
    ],
    'user-self-accounts' => [
        'name' => 'Cuentas propias del usuario',
        'note' => 'Permite a personas que no son administradoras gestionar algunos aspectos de su propia cuenta.',
    ],
    'selftwo-factor' => [
        'name' => 'Administrar Autenticación de dos Factores',
        'note' => 'Permite a los usuarios habilitar, desactivar y administrar la autenticación de dos factores para sus propias cuentas.',
    ],
    'selfapi' => [
        'name' => 'Administrar las claves del API',
        'note' => 'Permite a los usuarios crear, ver y revocar sus propios tokens API. Los tokens de usuario tendrán los mismos permisos que el usuario que los creo.',
    ],
    'selfedit-location' => [
        'name' => 'Editar Ubicación',
        'note' => 'Permite a los usuarios editar la ubicación asociada con su propia cuenta de usuario.',
    ],
    'selfcheckout-assets' => [
        'name' => 'Autoasignarse activos',
        'note' => 'Permite que las personas se asignen activos a sí mismas sin intervención de un administrador.',
    ],
    'selfview-purchase-cost' => [
        'name' => 'Ver Coste de Compra',
        'note' => 'Permite ver el costo de compra de los elementos en la vista de su cuenta.',
    ],

    'depreciations' => [
        'name' => 'Gestión de depreciación',
        'note' => 'Permite gestionar y ver los detalles de depreciación de los activos.',
    ],
    'depreciationsview' => [
        'name' => 'Ver detalles de depreciación',
    ],
    'depreciationsedit' => [
        'name' => 'Editar configuración de depreciación',
    ],
    'depreciationsdelete' => [
        'name' => 'Eliminar registros de depreciación',
    ],
    'depreciationscreate' => [
        'name' => 'Crear registros de depreciación',
    ],

    'grant_all' => 'Permitir todos los permisos para :area',
    'deny_all' => 'Negar todos los permisos de :area',
    'inherit_all' => 'Heredar todos los permisos del :area de grupos de permisos',
    'grant' => 'Otorgar permiso para :area',
    'deny' => 'Denegar permiso para :area',
    'inherit' => 'Heredar permiso para :area de los grupos de permisos',
    'use_groups' => 'Recomendamos usar grupos de permisos en lugar de asignar permisos individuales, para que sea más fácil de gestionar.',

];
