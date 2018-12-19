<?php
/**
* 2007-2018 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2018 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class whatsapp extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'whatsapp';
        $this->tab = 'administration';
        $this->version = '2.0.0';
        $this->author = 'svats.com';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Whatsapp Chat');
        $this->description = $this->l('Add your number to whatsapp');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        include(dirname(__FILE__) . '/sql/install.php');
        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('displayFooter') &&
            $this->registerHook('leftColumn') &&
            $this->registerHook('rightColumn');
    }

    public function uninstall()
    {
        include(dirname(__FILE__) . '/sql/uninstall.php');
        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        $add = 0;
        if (((bool) Tools::isSubmit('telekle')) == true) {
            $telefon = Tools::getValue('telefon');
            $shook = Tools::getValue('shook');
            $shareThis = Tools::getValue('shareThis');
            $shareMessage = Tools::getValue('shareMessage');

            if (Validate::isPhoneNumber($telefon) && Validate::isCountryName($shook) && Validate::isInt($shareThis) && Validate::isCleanHtml($shareMessage))
                {
                Db::getInstance()->update('whatsapp', array(
                'telefon' => pSQL($telefon),
                'shareThis' => (int) $shareThis,
                'shareMessage' => pSQL($shareMessage),
                'hook' => pSQL($shook),
                    ), 'id_whatsapp = 1');
                $add = 1;
            }
        }
        $iso_code = $this->context->language->iso_code;
        $no = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'whatsapp WHERE id_whatsapp = 1');
        $whatasppno = $no['telefon'];
        $hook = $no['hook'];
        $shareThis = $no['shareThis'];
        $shareMessage = $no['shareMessage'];

        $this->context->smarty->assign(array(
        'whatasppno' => $whatasppno,
        'hook' => $hook,
        'shareThis' => $shareThis,
        'shareMessage' => $shareMessage,
        'whp_mdir' => $this->_path,
        'lang_iso' => $iso_code,
        'pyazi' => "{PRODUCT}",
        'add' => $add,
        ));
        return $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path . '/views/js/whatsapp.js');
        $this->context->controller->addCSS($this->_path . '/views/css/whatsapp.css');
    }

    public function whatsapp($params)
    {
        $detect = new Mobile_Detect;
        $deviceType = ($detect->isMobile() ? ($detect->isTablet() ? 'tablet' : 'phone') : 'computer');

        $no = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'whatsapp WHERE id_whatsapp = 1');
        $whatasppno = $no['telefon'];
        $hook = $no['hook'];
        $shareThis = $no['shareThis'];
        $shareMessage = $no['shareMessage'];

        $page = Tools::getValue('controller');
        if (Validate::isCountryName($page) && $page == 'product') {
            $idPr = (int) Tools::getValue('id_product');
            $lang = (int) $params['cookie']->id_lang;
            $pr = Db::getInstance()->getRow('SELECT * FROM `' . _DB_PREFIX_ . 'product_lang` 
                                             WHERE id_product = ' . $idPr . ' AND id_lang = ' . $lang . '');
            $name = $pr['name'];

            $shareMessage = str_replace("{PRODUCT}", "*" . $name . "*", "{$shareMessage}");
        }
    
        $this->context->smarty->assign(array(
        'whatasppno' => $whatasppno,
        'hook' => $hook,
        'deviceType' => $deviceType,
        'shareThis' => $shareThis,
        'shareMessage' => $shareMessage,
        'whataspp_module_dir' => $this->_path,
        'page_name' =>$page,
        ));
        return $this->context->smarty->fetch($this->local_path . 'views/templates/front/footer.tpl');
    }

    public function hookDisplayFooter($params)
    {
        $no = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'whatsapp WHERE id_whatsapp = 1');
        $hook = $no['hook'];
        if ($hook == 'footer') {
            return $this->whatsapp($params);
        }
    }

    public function hookLeftColumn($params)
    {
        $no = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'whatsapp WHERE id_whatsapp = 1');
        $hook = $no['hook'];
        if (isset($hook) and ($hook == 'leftColumn')) {
            return $this->whatsapp($params);
        }
    }

    public function hookRightColumn($params)
    {
        $no = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'whatsapp WHERE id_whatsapp = 1');
        $hook = $no['hook'];
        if ($hook == 'rightColumn') {
            return $this->whatsapp($params);
        }
    }
}
