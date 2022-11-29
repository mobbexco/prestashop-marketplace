<?php
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
        'title' => $this->module->l('id'), 
        'align' => 'center', 
        'class' => 'fixed-width-xs' 
      ],
      'tax_id' => [
        'title' => $this->module->l('tax_id'),
        'align' => 'left',
      ],
      'name' => [
        'title' => $this->module->l('name'),
        'align' => 'left',
      ],
      'fee' => [
        'title' => $this->module->l('fee'),
        'align' => 'left',
      ],
      'hold' => [
        'title' => $this->module->l('hold'),
        'align' => 'left',
      ],
      'created' => [
        'title' => $this->module->l('created'),
        'align' => 'left',
      ]
    ];

    $this->addRowAction('edit');
    $this->addRowAction('delete');
  }


  /**
   * 
   * @return string
   * @throws SmartyException
   */
  public function renderForm()
  {
    $this->fields_form = [

      'legend' => [
        'title' => $this->module->l('Editar Vendedores'),
        'icon' => 'icon-cog'
      ],

      'input' => [
        [
          'type'          => 'text', 
          'label'         => $this->module->l('Cuit'), 
          'name'          => 'tax_id',
          'required'      => true, 
          'empty_message' => $this->module->l('Rellena el codigo'), 
          'hint'          => $this->module->l('Ingresar el cuit del vendedor') 
        ],
        [
          'type'          => 'text',
          'label'         => $this->module->l('Nombre del vendedor'),
          'name'          => 'name',
          'required'      => true,
          'empty_message' => $this->module->l('Rellena el codigo'),
        ],
        [
          'type'          => 'text',
          'label'         => $this->module->l('Comisión (%)'),
          'name'          => 'fee',
          'required'      => false,
          'empty_message' => $this->module->l('Rellena el codigo'),
        ],
        [
          'type'     => 'switch',
          'label'    => $this->module->l('Retener'),
          'name'     => 'hold',
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
          'type'          => 'datetime',
          'label'         => $this->module->l('Creado'),
          'name'          => 'created',
          'empty_message' => $this->module->l('Rellena el codigo'),
        ]
      ],
      'submit' => [
        'title' => $this->module->l('Save'), 
      ]
    ];
    return parent::renderForm();
  }
}