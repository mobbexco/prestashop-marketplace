Mobbex for PrestaShop
Requisitos

    PrestaShop >= 1.6
    PHP >= 7.0
    Mobbex plugin >= 2.6

Instalación

    Descargar la última versión del módulo desde https://github.com/mobbexco/prestashop-marketplace/releases.
    Ir a la sección "Module Manager" del panel de administración.
    Clickear el botón "Subir un módulo" y arrastrar el archivo comprimido.

Soluciones a Posibles Problemas
Hooks no conectados en Multi-Sitio

    Identificar el ID de Hook, en la tabla ps_hook.
    Identificar ID del módulo en la tabla ps_module.
    En ps_hook_module, insertar ambos IDs respetando el formato de la tabla.
