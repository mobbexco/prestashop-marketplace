<?php
require_once _PS_MODULE_DIR_ . 'mobbex/Models/AbstractModel.php';
require_once _PS_MODULE_DIR_ . 'mobbex_marketplace/Models/Vendor.php';

class AdminMobbexController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap  = true;
        $this->table      = \Mobbex\PS\Marketplace\Models\Vendor::$definition['table'];
        $this->identifier = \Mobbex\PS\Marketplace\Models\Vendor::$definition['primary'];
        $this->className  = \Mobbex\PS\Marketplace\Models\Vendor::class;

        parent::__construct();

        $this->fields_list = [
            'id' => [
                'title' => $this->module->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ],
            'name' => [
                'title' => $this->module->l('Nombre'),
                'align' => 'left',
            ],
            'uid' => [
                'title' => $this->module->l('Identificador en Mobbex'),
                'align' => 'left',
            ],
            'fee' => [
                'title' => $this->module->l('Comisión'),
                'align' => 'left',
            ],
            'hold' => [
                'title' => $this->module->l('Retener pagos'),
                'align' => 'left',
            ],
            'updated' => [
                'title' => $this->module->l('Actualizado'),
                'align' => 'left',
            ]
        ];

        $this->addRowAction('edit');
        $this->addRowAction('delete');
    }

    public function renderForm()
    {
        $this->fields_form = [
            'legend' => [
                'title' => $this->module->l('Crear/Editar vendedor'),
                'icon' => 'icon-cog'
            ],

            'input' => [
                [
                    'type'          => 'text',
                    'label'         => $this->module->l('Nombre del vendedor'),
                    'name'          => 'name',
                    'required'      => true,
                ],
                [
                    'type'          => 'text',
                    'label'         => $this->module->l('Identificador en Mobbex'),
                    'name'          => 'uid',
                    'required'      => true,
                    'desc'   => $this->module->l('Ingresar el UID del vendedor proporcionado por Mobbex')
                ],
                [
                    'type'          => 'text',
                    'label'         => $this->module->l('Comisión'),
                    'name'          => 'fee',
                    'required'      => false,
                    'desc'   => $this->module->l('La comisión que se le cobrará al vendedor, si corresponde. Use el signo % para cobrar en porcentaje. Ej. "10%"')
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->module->l('Retener pagos'),
                    'name'     => 'hold',
                    'desc'     => $this->module->l('Retener pagos hasta que el vendedor cumpla con ciertas condiciones'),
                    'values'   => [
                        [
                            'id'    => 'active_on_mdv',
                            'value' => true,
                            'label' => $this->module->l('Yes'),
                        ],
                        [
                            'id'    => 'active_off_mdv',
                            'value' => false,
                            'label' => $this->module->l('No'),
                        ],
                    ],
                ],
                [
                    'type'     => 'html',
                    'name'     => 'updated',
                    'html_content' => '<input type="hidden" name="updated" value="' . date('Y-m-d H:i:s') . '">'
                ]
            ],
            'submit' => [
                'title' => $this->module->l('Save'),
            ]
        ];

        return parent::renderForm();
    }
}
