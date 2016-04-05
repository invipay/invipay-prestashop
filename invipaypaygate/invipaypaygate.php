<?php
/**
*   http://www.invipay.com
*
*   @author     Kuba Pilecki (kpilecki@invipay.com)
*   @copyright  (C) 2016 inviPay.com
*   @license    OSL-3.0
*
*   Redistribution and use in source and binary forms, with or
*   without modification, are permitted provided that the following
*   conditions are met: Redistributions of source code must retain the
*   above copyright notice, this list of conditions and the following
*   disclaimer. Redistributions in binary form must reproduce the above
*   copyright notice, this list of conditions and the following disclaimer
*   in the documentation and/or other materials provided with the
*   distribution.
*   
*   THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED
*   WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
*   MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN
*   NO EVENT SHALL CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
*   INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
*   BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS
*   OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
*   ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR
*   TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE
*   USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH
*   DAMAGE.
*/

if (!defined('_PS_VERSION_')) { exit; }

require_once dirname(__FILE__).'/helpers/Helper.php';

class InvipayPaygate extends PaymentModule
{
    protected $helper;

    /***********************************************************************************
    * Module configuration, installation and uninstallation
    ***********************************************************************************/

    public function __construct()
    {
        $this->helper = new InvipaypaygateHelper();
        $this->controllers = array('payment', 'statuslistener', 'statuscheck', 'validation', 'error');

        $this->name = 'invipaypaygate';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'inviPay.com';
        $this->need_instance = 0;
        $this->is_eu_compatible = true;
        $this->currencies = false;
        $this->bootstrap = true;
        $this->module_key = '3b5a58e798efb11fa97cee72b09051d2';

        parent::__construct();

        $this->displayName = 'inviPay.com';//$this->l('module_display_name');
        $this->description = 'Metoda płatności dzięki której sprzedajesz na fakturę z odroczonym terminem płatności, bez żadnego ryzyka.';//$this->l('module_description');

        $this->confirmUninstall = $this->l('uninstall_confirmation');

        if (_PS_VERSION_ < '1.5') {
            require(_PS_MODULE_DIR_.$this->name.'/backward_compatibility/backward.php');
        }

        if (!Configuration::get(InvipaypaygateHelper::ADMIN_CONFIGURATION_KEY))
        {
            $this->warning = $this->l('no_configuration_data');
        }

        $this->configureSmarty();
    }

    public function install()
    {
        return
        (
            parent::install() &&
            $this->addOrderState(InvipaypaygateHelper::ORDER_STATUS_PAYMENT_STARTED, '#4169E1', array('pl' => 'Płatność w inviPay rozpoczęta'), 'Payment via inviPay started') &&
            $this->installDatabaseChanges() &&
            $this->registerHook('header') &&
            $this->registerHook('displayFooter') &&
            $this->registerHook('payment') &&
            $this->registerHook('paymentReturn') &&
            $this->registerHook('actionObjectOrderUpdateAfter') &&
            $this->helper->saveConfiguration($this->getAdministractionConfigurationMetaData('default'))
        );
    }

    public function uninstall()
    {
        return (
                    parent::uninstall() &&
                    $this->deleteOrderState(InvipaypaygateHelper::ORDER_STATUS_PAYMENT_STARTED) &&
                    $this->uninstallDatabaseChanges() &&
                    Configuration::deleteByName(InvipaypaygateHelper::ADMIN_CONFIGURATION_KEY) && 
                    Configuration::deleteByName(InvipaypaygateHelper::ADMIN_CONFIGURATION_VIRTUAL_PAYMENT_METHOD_KEY)
                );
    }

    protected function installDatabaseChanges()
    {
        $sql = 
        '
            CREATE TABLE `'._DB_PREFIX_.'invipay_payment_requests` (
                `id_payment_request` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `date_add` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `id_cart` INT(10) UNSIGNED NOT NULL,
                `id_order` INT(10) UNSIGNED NOT NULL,
                `payment_id` VARCHAR(255) NOT NULL,
                `payment_status` VARCHAR(255) NOT NULL,
                `delivery_confirmed` TINYINT(1) NOT NULL DEFAULT 0,
                `completed` TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id_payment_request`),
            INDEX `idx_invipay_payment_requests_payment_id` (`payment_id` ASC),
            INDEX `idx_invipay_payment_requests_payment_status` (`payment_status` ASC),
            INDEX `idx_invipay_payment_requests_completed` (`completed` ASC),
            INDEX `FK_invipay_payment_requests_idx` (`id_cart` ASC),
            INDEX `FK_invipay_payment_requests_order_idx` (`id_order` ASC)
            );
        ';

        return Db::getInstance()->Execute($sql);
    }

    protected function addOrderState($key, $color, $names, $defaultName)
    {
        if (!Configuration::get($key))
        {
            $os = new OrderState();
            $os->name = array();

            foreach (Language::getLanguages(false) as $language)
            {
                $code = Tools::strtolower($language['iso_code']);
                $os->name[(int)$language['id_lang']] = isset($names[$code]) ? $names[$code] : $defaultName;
            }

            $os->color = $color;
            $os->hidden = false;
            $os->send_email = false;
            $os->delivery = false;
            $os->logable = false;
            $os->invoice = false;
            $os->module_name = $this->name;

            if ($os->add())
            {
                Configuration::updateValue($key, $os->id);
                copy(dirname(__FILE__).'/logo.gif', getcwd().'/../img/os/'.((int)$os->id).'.gif');
                return true;
            }
            else
            {
                return false;
            }
        }
        else
        {
            return true;
        }
    }

    protected function deleteOrderState($key)
    {
        $id = Configuration::get($key);
        
        if ($id)
        {
            // $os = new OrderState($id);
            // $os->delete();
            // unlink(getcwd().'/../img/os/'.((int)$os->id).'.gif');
        }

        Configuration::deleteByName($key);

        return true;
    }

    protected function uninstallDatabaseChanges()
    {
        $sql = 'DROP TABLE `'._DB_PREFIX_.'invipay_payment_requests`;';
        return Db::getInstance()->Execute($sql);
    }

    /***********************************************************************************
    * Module administration and configuration
    ***********************************************************************************/

    protected function configureSmarty()
    {
        $this->smarty->assign('_module_path', dirname(__FILE__));
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submit'.$this->name))
        {
            $config = array();
            $validationErrors = array();
            $validationResult = $this->getAdministrationConfigurationInput($config, $validationErrors);
            if ($validationResult == false)
            {
                foreach ($validationErrors as $value)
                {
                    $output .= $this->displayError($value);
                }

                return $output.$this->displayAdminConfigurationForm($config);
            }
            else
            {
                $this->helper->saveConfiguration($config);
                $output .= $this->displayConfirmation($this->l('admin_configuration_saved'));
            }
        }

        return $output.$this->displayAdminConfigurationForm($this->helper->loadConfiguration());
    }

    protected function getAdministrationConfigurationInput(&$output, &$validationErrors)
    {
        $validationResult = true;

        foreach ($this->getAdministractionConfigurationMetaData(array('default', 'validation', 'form')) as $key => $metaData)
        {
            //$name = $metaData['form']['name'];
            $description = $metaData['form']['label'];
            $value = Tools::getValue($key);

            foreach ($metaData['validation'] as $validationCallback)
            {
                if (call_user_func($validationCallback, $value) == false)
                {
                    $validationResult = false;
                    $validationErrors[] = sprintf($this->l('validation_wrong_value %s'), $description);
                    break;
                }
            }

            $output[$key] = $value;
        }

        return $validationResult;
    }

    protected function getAdministractionConfigurationMetaData($type, $sections = array(0,1,2,3))
    {
        $data = array
        (
            array
            (
                'API_KEY' => array('default' => '', 'validation' => array('notempty'), 'form' => array('type' => 'text', 'error' => 'dupa', 'label' => $this->l('admin_configuration_api_key'), 'name' => 'API_KEY', 'size' => 50, 'required' => true)),
                'SIGNATURE_KEY' => array('default' => '', 'validation' => array('notempty'), 'form' => array('type' => 'text', 'label' => $this->l('admin_configuration_signature_key'), 'name' => 'SIGNATURE_KEY', 'size' => 50, 'required' => true)),
                'DEMO_MODE' => array('default' => 0, 'validation' => array('Validate::isInt'), 'form' => array('type' => 'radio', 'label' => $this->l('admin_configuration_demo_mode'), 'name' => 'DEMO_MODE', 'required' => true, 'is_bool' => true, 'values' => array (array('id' => 'demo_on', 'value' => 1, 'label' => $this->l('Yes')), array('id' => 'demo_off', 'value' => 0, 'label' => $this->l('No'))))),
            ),

            array
            (
                'BASE_DUE_DATE' => array('default' => 14, 'validation' => array('notempty', 'Validate::isInt'), 'form' => array('type' => 'text', 'label' => $this->l('admin_configuration_base_due_date'), 'name' => 'BASE_DUE_DATE', 'size' => 4, 'required' => true)),
                'MINIMAL_BASKET_VALUE' => array('default' => 200.00, 'validation' => array('notempty', 'Validate::isFloat'), 'form' => array('type' => 'text', 'label' => $this->l('admin_configuration_minimal_basket_value'), 'name' => 'MINIMAL_BASKET_VALUE', 'size' => 5, 'required' => true)),
                'PAYMENT_METHOD_TITLE' => array('default' => $this->l('default_payment_method_title'), 'validation' => array('notempty'), 'form' => array('type' => 'text', 'label' => $this->l('admin_configuration_payment_method_title'), 'name' => 'PAYMENT_METHOD_TITLE', 'size' => 255, 'required' => true)),
            ),

            array
            (
                'PAYMENT_METHOD_COST_ACTIVE' => array('default' => 0, 'validation' => array('Validate::isInt'), 'form' => array('type' => 'radio', 'label' => $this->l('admin_configuration_payment_method_cost_active'), 'name' => 'PAYMENT_METHOD_COST_ACTIVE', 'required' => true, 'is_bool' => true, 'values' => array (array('id' => 'cost_on', 'value' => 1, 'label' => $this->l('Yes')), array('id' => 'cost_off', 'value' => 0, 'label' => $this->l('No'))))),    
                'PAYMENT_METHOD_COST_TITLE' => array('default' => $this->l('default_payment_method_cost_title'), 'validation' => array('notempty'), 'form' => array('type' => 'text', 'label' => $this->l('admin_configuration_payment_method_cost_title'), 'name' => 'PAYMENT_METHOD_COST_TITLE', 'size' => 255, 'required' => true)),
                'PAYMENT_METHOD_COST_CONSTANT' => array('default' => 0.00, 'validation' => array('notempty', 'Validate::isFloat'), 'form' => array('type' => 'text', 'label' => $this->l('admin_configuration_payment_method_cost_constant'), 'name' => 'PAYMENT_METHOD_COST_CONSTANT', 'size' => 5, 'required' => false)),
                'PAYMENT_METHOD_COST_VARIABLE' => array('default' => 2.5, 'validation' => array('notempty', 'Validate::isFloat'), 'form' => array('type' => 'text', 'label' => $this->l('admin_configuration_payment_method_cost_variable'), 'name' => 'PAYMENT_METHOD_COST_VARIABLE', 'size' => 5, 'required' => false)),
                'PAYMENT_METHOD_COST_TAX_RULE' => array('default' => 0, 'validation' => array('Validate::isInt'), 'form' => array('type' => 'select', 'label' => $this->l('admin_configuration_payment_method_cost_tax_rule'), 'name' => 'PAYMENT_METHOD_COST_TAX_RULE', 'required' => false, 'options' => array('query' => array(), 'id' => 'id_tax_rules_group', 'name' => 'name'))),
            ),

            array
            (
                'WIDGETS_METHOD_DESCRIPTION' => array('default' => 'standard', 'validation' => array('notempty'), 'form' => array('type' => 'select', 'label' => $this->l('admin_configuration_widgets_method_description'), 'name' => 'WIDGETS_METHOD_DESCRIPTION', 'required' => false, 'is_bool' => true, 'options' => array('id' => 'value', 'name' => 'label', 'query' => array (array('value' => 'standard', 'label' => $this->l('admin_configuration_widgets_description_standard')), array('value' => 'short', 'label' => $this->l('admin_configuration_widgets_description_short')), array('value' => 'medium', 'label' => $this->l('admin_configuration_widgets_description_medium')), array('value' => 'long', 'label' => $this->l('admin_configuration_widgets_description_long')))))),
                'WIDGETS_FLOATING_PANEL' => array('default' => 'right', 'validation' => array('notempty'), 'form' => array('type' => 'select', 'label' => $this->l('admin_configuration_widgets_floating_panel'), 'name' => 'WIDGETS_FLOATING_PANEL', 'required' => false, 'is_bool' => true, 'options' => array('id' => 'value', 'name' => 'label', 'query' => array(array('value' => 'none', 'label' => $this->l('Disabled')), array('value' => 'right', 'label' => $this->l('admin_configuration_widgets_floating_panel_right')), array('value' => 'left', 'label' => $this->l('admin_configuration_widgets_floating_panel_left')))))),
                'WIDGETS_FOOTER_ICON' => array('default' => 1, 'validation' => array('Validate::isInt'), 'form' => array('type' => 'radio', 'label' => $this->l('admin_configuration_widgets_footer_icon'), 'name' => 'WIDGETS_FOOTER_ICON', 'required' => false, 'is_bool' => true, 'values' => array(array('id' => 'widget_footer_icon_on', 'value' => 1, 'label' => $this->l('Enabled')), array('id' => 'widget_footer_icon_off', 'value' => 0, 'label' => $this->l('Disabled'))))),
            ),
        );

        $output = array();

        foreach ($sections as $sectionKey)
        {
            foreach ($data[$sectionKey] as $entryKey => $entryData)
            {
                if (!is_array($type))
                {
                    $output[$entryKey] = $entryData[$type];
                }
                else
                {
                    $output[$entryKey] = array();
                    foreach ($type as $typeKey)
                    {
                        $output[$entryKey][$typeKey] = $entryData[$typeKey];
                    }
                }
            }
        }

        return $output;
    }

    protected function displayAdminConfigurationForm($configData)
    {
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        $fields_form = array();

        $fields_form[0] = array();
        $fields_form[0]['form'] = array
        (
            'legend' => array
            (
                'title' => $this->l('admin_configuration_invipay_account'),
            ),

            'input' => $this->getAdministractionConfigurationMetaData('form', array(0))
        );

        $fields_form[1] = array();
        $fields_form[1]['form'] = array
        (
            'legend' => array
            (
                'title' => $this->l('admin_configuration_invipay_presta'),
            ),

            'input' => $this->getAdministractionConfigurationMetaData('form', array(1))
        );

        $fields_form[2] = array();
        $fields_form[2]['form'] = array
        (
            'legend' => array
            (
                'title' => $this->l('admin_configuration_invipay_payment_cost'),
            ),

            'input' => $this->getAdministractionConfigurationMetaData('form', array(2))
        );

        $fields_form[3] = array();
        $fields_form[3]['form'] = array
        (
            'legend' => array
            (
                'title' => $this->l('admin_configuration_invipay_promo'),
            ),

            'input' => $this->getAdministractionConfigurationMetaData('form', array(3)),

            'submit' => array
            (
                'title' => $this->l('Save'),
                'class' => 'button'
            )
        );

        $fields_form[2]['form']['input']['PAYMENT_METHOD_COST_TAX_RULE']['options']['query'] = TaxRulesGroup::getTaxRulesGroupsForOptions();

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->submit_action = 'submit'.$this->name;
        $helper->toolbar_btn = array
        (
            'save' => array('desc' => $this->l('Save'), 'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules')),
            'back' => array('href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'), 'desc' => $this->l('Back to list'))
        );

        $helper->fields_value = $configData;

        return $helper->generateForm($fields_form);
    }


    /***********************************************************************************
    * Hooks definition and other related stuff
    ***********************************************************************************/

    public function hookDisplayFooter($params)
    {
        if ($this->active)
        {
            $output = '';

            $config = $this->helper->loadConfiguration();

            if ($config['WIDGETS_FOOTER_ICON'] == true)
            {
                $output .= $this->display(__file__, 'views/templates/hook/widgets/footer_icon.tpl');
            }

            if ($config['WIDGETS_FLOATING_PANEL'] != 'none')
            {
                $this->smarty->assign('invipay_widgets_floating_panel', array('position' => $config['WIDGETS_FLOATING_PANEL'], 'due_date_days' => $config['BASE_DUE_DATE'], 'minimum_value' => $config['MINIMAL_BASKET_VALUE']));
                $output .= $this->display(__file__, 'views/templates/hook/widgets/floating_panel.tpl');
            }

            return $output;
        }
    }

    public function hookHeader()
    {
        if ($this->active)
        {
            $this->context->controller->addJS('https://invipay.com/promo/InviPay.Widgets.min.js');
        }
    }

    public function hookPayment()
    {
        if ($this->active)
        {
            // Need to be turned off so all fancy on-page-checkouts would work
            $config = $this->helper->loadConfiguration();
            $this->smarty->assign('invipay_paygate', array
                (
                    'ps_version' => _PS_VERSION_,
                    'use_boostrap' => _PS_VERSION_ >= '1.6',
                    'minimum_value' => $config['MINIMAL_BASKET_VALUE'],
                    'method_title' => $config['PAYMENT_METHOD_TITLE'], 
                    'method_description' => $this->l('method_description_' . $config['WIDGETS_METHOD_DESCRIPTION']),
                    'method_not_available' => false,
                    'method_not_available_errors' => array(),
                ));

            return $this->display(__FILE__, 'payment.tpl');
        }
    }

    public function hookPaymentReturn($params)
    {
        if ($this->active)
        {
            $orderId = Tools::getValue('id_order');

            $this->smarty->assign('invipay_paygate', array
                (
                    'ps_version' => _PS_VERSION_,
                    'use_boostrap' => _PS_VERSION_ >= '1.6',
                    'status_check_url' => $this->context->link->getModuleLink('invipaypaygate', 'statuscheck', array('id_order' => $orderId)),
                    'start_data' => $this->helper->checkPaymentStatus($orderId),
                ));

            return $this->display(__FILE__, 'payment_return.tpl');
        }
    }

    public function hookActionObjectOrderUpdateAfter($params)
    {
        $object = array_key_exists('object', $params) ? $params['object'] : null;

        if ($object !== null && $object instanceof Order && $object->module == $this->name)
        {
            $this->helper->managePaymentRequestIfApplicable($object);
        }
    }
}
