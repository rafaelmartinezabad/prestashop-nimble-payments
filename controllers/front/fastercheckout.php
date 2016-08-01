<?php
/**
* 2007-2015 PrestaShop
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
*  @copyright 2007-2015 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

require_once _PS_MODULE_DIR_.'nimblepayment/library/sdk/lib/Nimble/base/NimbleAPI.php';
require_once _PS_MODULE_DIR_.'nimblepayment/library/sdk/lib/Nimble/api/NimbleAPIPayments.php';
require_once _PS_MODULE_DIR_.'nimblepayment/library/sdk/lib/Nimble/base/NimbleAPIAuthorization.php';
require_once _PS_MODULE_DIR_.'nimblepayment/library/sdk/lib/Nimble/api/NimbleAPIStoredCards.php';

class NimblePaymentFasterCheckoutModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $display_column_left = false;
    public $display_column_right = false;
    public $nimblepayment_client_secret = '';
    public $nimblepayment_client_id = '';
    public $type_error = 0;
    public $nimbleapi;

    /**
     * Initialize order opc controller
     * @see FrontController::init()
     */
    public function initContent()
    {
			parent::initContent();
            $this->isLogged = $this->context->customer->id && Customer::customerIdExistsStatic((int)$this->context->cookie->id_customer);
            $this->context->cart->checkedTOS = 1;

            $this->context->smarty->assign('checkedTOS', $this->context->cart->checkedTOS);
            $this->context->smarty->assign('isVirtualCart', $this->context->cart->isVirtualCart());
            $this->_processAddressFormat();
            $this->_assignAddress();
            $this->_getCarrierList();
            $this->_assignPayment();
            $this->context->smarty->assign('back', Tools::safeOutput(Tools::getValue('back')));
            
            $display = (_PS_VERSION_ < '1.5') ? new BWDisplay() : new FrontController();
            $display->setTemplate(_PS_MODULE_DIR_.'nimblepayment/views/templates/hook/fastercheckout.tpl');
            $display->run();
    }   
    
    
 protected function _assignAddress()
    {
        //if guest checkout disabled and flag is_guest  in cookies is actived
        if (Configuration::get('PS_GUEST_CHECKOUT_ENABLED') == 0 && ((int)$this->context->customer->is_guest != Configuration::get('PS_GUEST_CHECKOUT_ENABLED'))) {
            $this->context->customer->logout();
            Tools::redirect('');
        } elseif (!Customer::getAddressesTotalById($this->context->customer->id)) {
            $multi = (int)Tools::getValue('multi-shipping');
            Tools::redirect('index.php?controller=address&back='.urlencode('order.php?step=1'.($multi ? '&multi-shipping='.$multi : '')));
        }

        $customer = $this->context->customer;
        if (Validate::isLoadedObject($customer)) {
            /* Getting customer addresses */
            $customerAddresses = $customer->getAddresses($this->context->language->id);

            // Getting a list of formated address fields with associated values
            $formatedAddressFieldsValuesList = array();

            foreach ($customerAddresses as $i => $address) {
                if (!Address::isCountryActiveById((int)$address['id_address'])) {
                    unset($customerAddresses[$i]);
                }
                $tmpAddress = new Address($address['id_address']);
                $formatedAddressFieldsValuesList[$address['id_address']]['ordered_fields'] = AddressFormat::getOrderedAddressFields($address['id_country']);
                $formatedAddressFieldsValuesList[$address['id_address']]['formated_fields_values'] = AddressFormat::getFormattedAddressFieldsValues(
                    $tmpAddress,
                    $formatedAddressFieldsValuesList[$address['id_address']]['ordered_fields']);

                unset($tmpAddress);
            }

            $customerAddresses = array_values($customerAddresses);

            if (!count($customerAddresses) && !Tools::isSubmit('ajax')) {
                $bad_delivery = false;
                if (($bad_delivery = (bool)!Address::isCountryActiveById((int)$this->context->cart->id_address_delivery)) || !Address::isCountryActiveById((int)$this->context->cart->id_address_invoice)) {
                    $params = array();
                    if ($this->step) {
                        $params['step'] = (int)$this->step;
                    }
                    if ($multi = (int)Tools::getValue('multi-shipping')) {
                        $params['multi-shipping'] = $multi;
                    }
                    $back_url = $this->context->link->getPageLink('order', true, (int)$this->context->language->id, $params);

                    $params = array('back' => $back_url, 'id_address' => ($bad_delivery ? (int)$this->context->cart->id_address_delivery : (int)$this->context->cart->id_address_invoice));
                    if ($multi) {
                        $params['multi-shipping'] = $multi;
                    }

                    Tools::redirect($this->context->link->getPageLink('address', true, (int)$this->context->language->id, $params));
                }
            }
            $this->context->smarty->assign(array(
                'addresses' => $customerAddresses,
                'formatedAddressFieldsValuesList' => $formatedAddressFieldsValuesList)
            );

            /* Setting default addresses for cart */
            if (count($customerAddresses)) {
                if ((!isset($this->context->cart->id_address_delivery) || empty($this->context->cart->id_address_delivery)) || !Address::isCountryActiveById((int)$this->context->cart->id_address_delivery)) {
                    $this->context->cart->id_address_delivery = (int)$customerAddresses[0]['id_address'];
                    $update = 1;
                }
                if ((!isset($this->context->cart->id_address_invoice) || empty($this->context->cart->id_address_invoice)) || !Address::isCountryActiveById((int)$this->context->cart->id_address_invoice)) {
                    $this->context->cart->id_address_invoice = (int)$customerAddresses[0]['id_address'];
                    $update = 1;
                }

                /* Update cart addresses only if needed */
                if (isset($update) && $update) {
                    $this->context->cart->update();
                    if (!$this->context->cart->isMultiAddressDelivery()) {
                        $this->context->cart->setNoMultishipping();
                    }
                    // Address has changed, so we check if the cart rules still apply
                    CartRule::autoRemoveFromCart($this->context);
                    CartRule::autoAddToCart($this->context);
                }
            }

            /* If delivery address is valid in cart, assign it to Smarty */
            if (isset($this->context->cart->id_address_delivery)) {
                $deliveryAddress = new Address((int)$this->context->cart->id_address_delivery);
                if (Validate::isLoadedObject($deliveryAddress) && ($deliveryAddress->id_customer == $customer->id)) {
                    $this->context->smarty->assign('delivery', $deliveryAddress);
                }
            }

            /* If invoice address is valid in cart, assign it to Smarty */
            if (isset($this->context->cart->id_address_invoice)) {
                $invoiceAddress = new Address((int)$this->context->cart->id_address_invoice);
                if (Validate::isLoadedObject($invoiceAddress) && ($invoiceAddress->id_customer == $customer->id)) {
                    $this->context->smarty->assign('invoice', $invoiceAddress);
                }
            }
        }
        if ($oldMessage = Message::getMessageByCartId((int)$this->context->cart->id)) {
            $this->context->smarty->assign('oldMessage', $oldMessage['message']);
        }
    }
    
    
  public function setMedia()
    {
        parent::setMedia();

        
         if (!$this->useMobileTheme()) {
            // Adding CSS style sheet
            $this->addCSS(_THEME_CSS_DIR_.'addresses.css');
        }
            $this->addJS(_THEME_JS_DIR_.'order-address.js');

        // Adding JS files
        $this->addJS(_THEME_JS_DIR_.'tools.js');  // retro compat themes 1.5
        if ((Configuration::get('PS_ORDER_PROCESS_TYPE') == 0 && Tools::getValue('step') == 1) || Configuration::get('PS_ORDER_PROCESS_TYPE') == 1) {
            $this->addJS(_THEME_JS_DIR_.'order-address.js');
        }
        $this->addJqueryPlugin('fancybox');

        if (in_array((int)Tools::getValue('step'), array(0, 2, 3)) || Configuration::get('PS_ORDER_PROCESS_TYPE')) {
            $this->addJqueryPlugin('typewatch');
            $this->addJS(_THEME_JS_DIR_.'cart-summary.js');
        }
        
        
        
        if (!$this->useMobileTheme()) {
            // Adding CSS style sheet
            $this->addCSS(_THEME_CSS_DIR_.'order-opc.css');
            // Adding JS files
            $this->addJS(_THEME_JS_DIR_.'order-opc.js');
            $this->addJqueryPlugin('scrollTo');
        } else {
            $this->addJS(_THEME_MOBILE_JS_DIR_.'opc.js');
        }

        $this->addJS(array(
            _THEME_JS_DIR_.'tools/vatManagement.js',
            _THEME_JS_DIR_.'tools/statesManagement.js',
            _THEME_JS_DIR_.'order-carrier.js',
            _PS_JS_DIR_.'validate.js'
        ));
    }

    /**
     * Assign template vars related to page content
     * @see FrontController::initContent()
     */

    protected function _getGuestInformations()
    {
        $customer = $this->context->customer;
        $address_delivery = new Address($this->context->cart->id_address_delivery);

        $id_address_invoice = $this->context->cart->id_address_invoice != $this->context->cart->id_address_delivery ? (int)$this->context->cart->id_address_invoice : 0;
        $address_invoice = new Address($id_address_invoice);

        if ($customer->birthday) {
            $birthday = explode('-', $customer->birthday);
        } else {
            $birthday = array('0', '0', '0');
        }

        return array(
            'id_customer' => (int)$customer->id,
            'email' => Tools::htmlentitiesUTF8($customer->email),
            'customer_lastname' => Tools::htmlentitiesUTF8($customer->lastname),
            'customer_firstname' => Tools::htmlentitiesUTF8($customer->firstname),
            'newsletter' => (int)$customer->newsletter,
            'optin' => (int)$customer->optin,
            'id_address_delivery' => (int)$this->context->cart->id_address_delivery,
            'company' => Tools::htmlentitiesUTF8($address_delivery->company),
            'lastname' => Tools::htmlentitiesUTF8($address_delivery->lastname),
            'firstname' => Tools::htmlentitiesUTF8($address_delivery->firstname),
            'vat_number' => Tools::htmlentitiesUTF8($address_delivery->vat_number),
            'dni' => Tools::htmlentitiesUTF8($address_delivery->dni),
            'address1' => Tools::htmlentitiesUTF8($address_delivery->address1),
            'postcode' => Tools::htmlentitiesUTF8($address_delivery->postcode),
            'city' => Tools::htmlentitiesUTF8($address_delivery->city),
            'phone' => Tools::htmlentitiesUTF8($address_delivery->phone),
            'phone_mobile' => Tools::htmlentitiesUTF8($address_delivery->phone_mobile),
            'id_country' => (int)$address_delivery->id_country,
            'id_state' => (int)$address_delivery->id_state,
            'id_gender' => (int)$customer->id_gender,
            'sl_year' => $birthday[0],
            'sl_month' => $birthday[1],
            'sl_day' => $birthday[2],
            'company_invoice' => Tools::htmlentitiesUTF8($address_invoice->company),
            'lastname_invoice' => Tools::htmlentitiesUTF8($address_invoice->lastname),
            'firstname_invoice' => Tools::htmlentitiesUTF8($address_invoice->firstname),
            'vat_number_invoice' => Tools::htmlentitiesUTF8($address_invoice->vat_number),
            'dni_invoice' => Tools::htmlentitiesUTF8($address_invoice->dni),
            'address1_invoice' => Tools::htmlentitiesUTF8($address_invoice->address1),
            'address2_invoice' => Tools::htmlentitiesUTF8($address_invoice->address2),
            'postcode_invoice' => Tools::htmlentitiesUTF8($address_invoice->postcode),
            'city_invoice' => Tools::htmlentitiesUTF8($address_invoice->city),
            'phone_invoice' => Tools::htmlentitiesUTF8($address_invoice->phone),
            'phone_mobile_invoice' => Tools::htmlentitiesUTF8($address_invoice->phone_mobile),
            'id_country_invoice' => (int)$address_invoice->id_country,
            'id_state_invoice' => (int)$address_invoice->id_state,
            'id_address_invoice' => $id_address_invoice,
            'invoice_company' => Tools::htmlentitiesUTF8($address_invoice->company),
            'invoice_lastname' => Tools::htmlentitiesUTF8($address_invoice->lastname),
            'invoice_firstname' => Tools::htmlentitiesUTF8($address_invoice->firstname),
            'invoice_vat_number' => Tools::htmlentitiesUTF8($address_invoice->vat_number),
            'invoice_dni' => Tools::htmlentitiesUTF8($address_invoice->dni),
            'invoice_address' => $this->context->cart->id_address_invoice !== $this->context->cart->id_address_delivery,
            'invoice_address1' => Tools::htmlentitiesUTF8($address_invoice->address1),
            'invoice_address2' => Tools::htmlentitiesUTF8($address_invoice->address2),
            'invoice_postcode' => Tools::htmlentitiesUTF8($address_invoice->postcode),
            'invoice_city' => Tools::htmlentitiesUTF8($address_invoice->city),
            'invoice_phone' => Tools::htmlentitiesUTF8($address_invoice->phone),
            'invoice_phone_mobile' => Tools::htmlentitiesUTF8($address_invoice->phone_mobile),
            'invoice_id_country' => (int)$address_invoice->id_country,
            'invoice_id_state' => (int)$address_invoice->id_state,
        );
    }

    protected function _assignCarrier()
    {
        if (!$this->isLogged) {
            $carriers = $this->context->cart->simulateCarriersOutput();
            $old_message = Message::getMessageByCartId((int)$this->context->cart->id);
            $this->context->smarty->assign(array(
                'HOOK_EXTRACARRIER' => null,
                'HOOK_EXTRACARRIER_ADDR' => null,
                'oldMessage' => isset($old_message['message'])? $old_message['message'] : '',
                'HOOK_BEFORECARRIER' => Hook::exec('displayBeforeCarrier', array(
                    'carriers' => $carriers,
                    'checked' => $this->context->cart->simulateCarrierSelectedOutput(),
                    'delivery_option_list' => $this->context->cart->getDeliveryOptionList(),
                    'delivery_option' => $this->context->cart->getDeliveryOption(null, true)
                ))
            ));
        } else {
            parent::_assignCarrier();
        }
    }


    
    protected function _assignPayment()
    {
        if ((bool)Configuration::get('PS_ADVANCED_PAYMENT_API')) {
            $this->context->smarty->assign(array(
                'HOOK_TOP_PAYMENT' => ($this->isLogged ? Hook::exec('displayPaymentTop') : ''),
                'HOOK_PAYMENT' => $this->_getPaymentMethods(),
                'HOOK_ADVANCED_PAYMENT' => Hook::exec('advancedPaymentOptions', array(), null, true),
                'link_conditions' => $this->link_conditions
            ));
        } else {
            error_log(print_r($this->_getPaymentMethods(),true));
            $this->context->smarty->assign(array(
                'HOOK_TOP_PAYMENT' => ($this->isLogged ? Hook::exec('displayPaymentTop') : ''),
                'HOOK_PAYMENT' => $this->_getPaymentMethods()
            ));
        }
    }

   

    protected function _getCarrierList()
    {
        $address_delivery = new Address($this->context->cart->id_address_delivery);

        $cms = new CMS(Configuration::get('PS_CONDITIONS_CMS_ID'), $this->context->language->id);
        $link_conditions = $this->context->link->getCMSLink($cms, $cms->link_rewrite, Configuration::get('PS_SSL_ENABLED'));
        if (!strpos($link_conditions, '?')) {
            $link_conditions .= '?content_only=1';
        } else {
            $link_conditions .= '&content_only=1';
        }

        $carriers = $this->context->cart->simulateCarriersOutput();
        $delivery_option = $this->context->cart->getDeliveryOption(null, false, false);

        $wrapping_fees = $this->context->cart->getGiftWrappingPrice(false);
        $wrapping_fees_tax_inc = $this->context->cart->getGiftWrappingPrice();
        $old_message = Message::getMessageByCartId((int)$this->context->cart->id);

        $free_shipping = false;
        foreach ($this->context->cart->getCartRules() as $rule) {
            if ($rule['free_shipping'] && !$rule['carrier_restriction']) {
                $free_shipping = true;
                break;
            }
        }

        $this->context->smarty->assign('isVirtualCart', $this->context->cart->isVirtualCart());
        $this->context->cart->checkedTOS = 1;
        $vars = array(
            'advanced_payment_api' => (bool)Configuration::get('PS_ADVANCED_PAYMENT_API'),
            'free_shipping' => $free_shipping,
            'checkedTOS' => $this->context->cart->checkedTOS,
            'recyclablePackAllowed' => (int)Configuration::get('PS_RECYCLABLE_PACK'),
            'giftAllowed' => (int)Configuration::get('PS_GIFT_WRAPPING'),
            'cms_id' => (int)Configuration::get('PS_CONDITIONS_CMS_ID'),
            'conditions' => (int)Configuration::get('PS_CONDITIONS'),
            'link_conditions' => $link_conditions,
            'recyclable' => (int)$this->context->cart->recyclable,
            'gift_wrapping_price' => (float)$wrapping_fees,
            'total_wrapping_cost' => Tools::convertPrice($wrapping_fees_tax_inc, $this->context->currency),
            'total_wrapping_tax_exc_cost' => Tools::convertPrice($wrapping_fees, $this->context->currency),
            'delivery_option_list' => $this->context->cart->getDeliveryOptionList(),
            'carriers' => $carriers,
            'checked' => $this->context->cart->simulateCarrierSelectedOutput(),
            'delivery_option' => $delivery_option,
            'address_collection' => $this->context->cart->getAddressCollection(),
            'opc' => true,
            'oldMessage' => isset($old_message['message'])? $old_message['message'] : '',
            'HOOK_BEFORECARRIER' => Hook::exec('displayBeforeCarrier', array(
                'carriers' => $carriers,
                'delivery_option_list' => $this->context->cart->getDeliveryOptionList(),
                'delivery_option' => $delivery_option
            ))
        );

        Cart::addExtraCarriers($vars);

        $this->context->smarty->assign($vars);

        if (!Address::isCountryActiveById((int)$this->context->cart->id_address_delivery) && $this->context->cart->id_address_delivery != 0) {
            $this->errors[] = Tools::displayError('This address is not in a valid area.');
        } elseif ((!Validate::isLoadedObject($address_delivery) || $address_delivery->deleted) && $this->context->cart->id_address_delivery != 0) {
            $this->errors[] = Tools::displayError('This address is invalid.');
        } else {
            $result = array(
                'HOOK_BEFORECARRIER' => Hook::exec('displayBeforeCarrier', array(
                    'carriers' => $carriers,
                    'delivery_option_list' => $this->context->cart->getDeliveryOptionList(),
                    'delivery_option' => $this->context->cart->getDeliveryOption(null, true)
                )),
                'carrier_block' => $this->context->smarty->fetch(_PS_THEME_DIR_.'order-carrier.tpl')
            );

            Cart::addExtraCarriers($result);
            return $result;
        }
        if (count($this->errors)) {
            return array(
                'hasError' => true,
                'errors' => $this->errors,
                'carrier_block' => $this->context->smarty->fetch(_PS_THEME_DIR_.'order-carrier.tpl')
            );
        }
    }

    protected function _processAddressFormat()
    {
        $address_delivery = new Address((int)$this->context->cart->id_address_delivery);
        $address_invoice = new Address((int)$this->context->cart->id_address_invoice);

        $inv_adr_fields = AddressFormat::getOrderedAddressFields((int)$address_delivery->id_country, false, true);
        $dlv_adr_fields = AddressFormat::getOrderedAddressFields((int)$address_invoice->id_country, false, true);
        $require_form_fields_list = AddressFormat::getFieldsRequired();

        // Add missing require fields for a new user susbscription form
        foreach ($require_form_fields_list as $field_name) {
            if (!in_array($field_name, $dlv_adr_fields)) {
                $dlv_adr_fields[] = trim($field_name);
            }
        }

        foreach ($require_form_fields_list as $field_name) {
            if (!in_array($field_name, $inv_adr_fields)) {
                $inv_adr_fields[] = trim($field_name);
            }
        }

        $inv_all_fields = array();
        $dlv_all_fields = array();

        foreach (array('inv', 'dlv') as $adr_type) {
            foreach (${$adr_type.'_adr_fields'} as $fields_line) {
                foreach (explode(' ', $fields_line) as $field_item) {
                    ${$adr_type.'_all_fields'}[] = trim($field_item);
                }
            }

            ${$adr_type.'_adr_fields'} = array_unique(${$adr_type.'_adr_fields'});
            ${$adr_type.'_all_fields'} = array_unique(${$adr_type.'_all_fields'});

            $this->context->smarty->assign(array(
                $adr_type.'_adr_fields' => ${$adr_type.'_adr_fields'},
                $adr_type.'_all_fields' => ${$adr_type.'_all_fields'},
                'required_fields' => $require_form_fields_list
            ));
        }
    }

    protected function getFormatedSummaryDetail()
    {
        $result = array('summary' => $this->context->cart->getSummaryDetails(),
                        'customizedDatas' => Product::getAllCustomizedDatas($this->context->cart->id, null, true));

        foreach ($result['summary']['products'] as $key => &$product) {
            $product['quantity_without_customization'] = $product['quantity'];
            if ($result['customizedDatas']) {
                if (isset($result['customizedDatas'][(int)$product['id_product']][(int)$product['id_product_attribute']])) {
                    foreach ($result['customizedDatas'][(int)$product['id_product']][(int)$product['id_product_attribute']] as $addresses) {
                        foreach ($addresses as $customization) {
                            $product['quantity_without_customization'] -= (int)$customization['quantity'];
                        }
                    }
                }
            }
        }

        if ($result['customizedDatas']) {
            Product::addCustomizationPrice($result['summary']['products'], $result['customizedDatas']);
        }
        return $result;
    }
    protected function _getPaymentMethods()
    {
        if (!$this->isLogged) {
            return '<p class="warning">'.Tools::displayError('Please sign in to see payment methods.').'</p>';
        }
        if ($this->context->cart->OrderExists()) {
            return '<p class="warning">'.Tools::displayError('Error: This order has already been validated.').'</p>';
        }
        if (!$this->context->cart->id_customer || !Customer::customerIdExistsStatic($this->context->cart->id_customer) || Customer::isBanned($this->context->cart->id_customer)) {
            return '<p class="warning">'.Tools::displayError('Error: No customer.').'</p>';
        }
        $address_delivery = new Address($this->context->cart->id_address_delivery);
        $address_invoice = ($this->context->cart->id_address_delivery == $this->context->cart->id_address_invoice ? $address_delivery : new Address($this->context->cart->id_address_invoice));
        if (!$this->context->cart->id_address_delivery || !$this->context->cart->id_address_invoice || !Validate::isLoadedObject($address_delivery) || !Validate::isLoadedObject($address_invoice) || $address_invoice->deleted || $address_delivery->deleted) {
            return '<p class="warning">'.Tools::displayError('Error: Please select an address.').'</p>';
        }
        if (count($this->context->cart->getDeliveryOptionList()) == 0 && !$this->context->cart->isVirtualCart()) {
            if ($this->context->cart->isMultiAddressDelivery()) {
                return '<p class="warning">'.Tools::displayError('Error: None of your chosen carriers deliver to some of the addresses you have selected.').'</p>';
            } else {
                return '<p class="warning">'.Tools::displayError('Error: None of your chosen carriers deliver to the address you have selected.').'</p>';
            }
        }
        if (!$this->context->cart->getDeliveryOption(null, false) && !$this->context->cart->isVirtualCart()) {
            return '<p class="warning">'.Tools::displayError('Error: Please choose a carrier.').'</p>';
        }
        if (!$this->context->cart->id_currency) {
            return '<p class="warning">'.Tools::displayError('Error: No currency has been selected.').'</p>';
        }
        if (!$this->context->cart->checkedTOS && Configuration::get('PS_CONDITIONS')) {
            return '<p class="warning">'.Tools::displayError('Please accept the Terms of Service.').'</p>';
        }

        /* If some products have disappear */
        if (is_array($product = $this->context->cart->checkQuantities(true))) {
            return '<p class="warning">'.sprintf(Tools::displayError('An item (%s) in your cart is no longer available in this quantity. You cannot proceed with your order until the quantity is adjusted.'), $product['name']).'</p>';
        }

        if ((int)$id_product = $this->context->cart->checkProductsAccess()) {
            return '<p class="warning">'.sprintf(Tools::displayError('An item in your cart is no longer available (%s). You cannot proceed with your order.'), Product::getProductName((int)$id_product)).'</p>';
        }

        /* Check minimal amount */
        $currency = Currency::getCurrency((int)$this->context->cart->id_currency);

        $minimal_purchase = Tools::convertPrice((float)Configuration::get('PS_PURCHASE_MINIMUM'), $currency);
        if ($this->context->cart->getOrderTotal(false, Cart::ONLY_PRODUCTS) < $minimal_purchase) {
            return '<p class="warning">'.sprintf(
                Tools::displayError('A minimum purchase total of %1s (tax excl.) is required to validate your order, current purchase total is %2s (tax excl.).'),
                Tools::displayPrice($minimal_purchase, $currency), Tools::displayPrice($this->context->cart->getOrderTotal(false, Cart::ONLY_PRODUCTS), $currency)
            ).'</p>';
        }

        /* Bypass payment step if total is 0 */
        if ($this->context->cart->getOrderTotal() <= 0) {
            return '<p class="center"><button class="button btn btn-default button-medium" name="confirmOrder" id="confirmOrder" onclick="confirmFreeOrder();" type="submit"> <span>'.Tools::displayError('I confirm my order.').'</span></button></p>';
        }

        //$return = Hook::exec('displayPayment');
        $params['cart'] = $this->context->cart;
        $nimble_payment =  new NimblePayment();
        $return = $nimble_payment->hookPayment($params);
        
        if (!$return) {
            return '<p class="warning">'.Tools::displayError('No payment method is available for use at this time. ').'</p>';
        }
        return $return;
    }
}
