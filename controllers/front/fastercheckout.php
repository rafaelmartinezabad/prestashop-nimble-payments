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

/**
 * Class FreeOrder to use PaymentModule (abstract class, cannot be instancied)
 */
class NimbleFreeOrder extends PaymentModule
{
    public $active = 1;
    public $name = 'free_order';
    public $displayName = 'free_order';
}

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
    protected $ajax_refresh = false;

	public function __construct()
	{
		parent::__construct();
		$this->context = Context::getContext();
	}

	public function initContent()
	{
		parent::initContent();

		$this->isLogged = $this->context->customer->id && Customer::customerIdExistsStatic((int)$this->context->cookie->id_customer);
		$this->context->cart->checkedTOS = 1; //terms of service

		$nimble_credentials = Configuration::get('PS_NIMBLE_CREDENTIALS');
		$faster_checkout_enabled = Configuration::get('FASTER_CHECKOUT_NIMBLE');
		$ssl = Configuration::get('PS_SSL_ENABLED');

		if ($this->context->cart->nbProducts()) {
			if (Tools::isSubmit('ajax')) {
				if (Tools::isSubmit('method')) {
					switch (Tools::getValue('method')) {
                        case 'updateMessage':
                            if (Tools::isSubmit('message')) {
                                $txt_message = urldecode(Tools::getValue('message'));
                                $this->_updateMessage($txt_message);
                                if (count($this->errors)) {
                                    $this->ajaxDie('{"hasError" : true, "errors" : ["'.implode('\',\'', $this->errors).'"]}');
                                }
                                $this->ajaxDie(true);
                            }
                            break;
                            
						case 'updateCarrierAndGetPayments':
							if ((Tools::isSubmit('delivery_option') || Tools::isSubmit('id_carrier')) && Tools::isSubmit('recyclable') && Tools::isSubmit('gift') && Tools::isSubmit('gift_message')) {
								//$this->_assignWrappingAndTOS();
								if ($this->_processCarrier()) {
									$carriers = $this->context->cart->simulateCarriersOutput();
									$return = array_merge(array(
										'HOOK_TOP_PAYMENT' => Hook::exec('displayPaymentTop'),
										'HOOK_PAYMENT' => $this->_getPaymentMethods(),
										'carrier_data' => $this->_getCarrierList(),
										'HOOK_BEFORECARRIER' => Hook::exec('displayBeforeCarrier', array('carriers' => $carriers))
										),
										$this->getFormatedSummaryDetail()
									);
									Cart::addExtraCarriers($return);
									$this->ajaxDie(Tools::jsonEncode($return));
								} else {
									$this->errors[] = Tools::displayError('An error occurred while updating the cart.');
								}
								if (count($this->errors)) {
									$this->ajaxDie('{"hasError" : true, "errors" : ["'.implode('\',\'', $this->errors).'"]}');
								}
								exit;
							}
							break;
                            
                        case 'updateTOSStatusAndGetPayments':
                            if (Tools::isSubmit('checked')) {
                                $this->context->cookie->checkedTOS = (int)Tools::getValue('checked');
                                $this->ajaxDie(Tools::jsonEncode(array(
                                    'HOOK_TOP_PAYMENT' => Hook::exec('displayPaymentTop'),
                                    'HOOK_PAYMENT' => $this->_getPaymentMethods()
                                )));
                            }
                            break;
                            
                        case 'getCarrierList':
                            $this->ajaxDie(Tools::jsonEncode($this->_getCarrierList()));
                            break;
                        
                        case 'editCustomer':
                            if (!$this->isLogged || !$this->context->customer->is_guest) {
                                exit;
                            }

                            if (Validate::isEmail($email = Tools::getValue('email')) && !empty($email)) {
                                if (Customer::customerExists($email)) {
                                    $this->errors[] = Tools::displayError('An account using this email address has already been registered.', false);
                                }
                            }

                            if (Tools::getValue('years')) {
                                $this->context->customer->birthday = (int)Tools::getValue('years').'-'.(int)Tools::getValue('months').'-'.(int)Tools::getValue('days');
                            }

                            $_POST['lastname'] = $_POST['customer_lastname'];
                            $_POST['firstname'] = $_POST['customer_firstname'];
                            $this->errors = array_merge($this->errors, $this->context->customer->validateController());
                            $this->context->customer->newsletter = (int)Tools::isSubmit('newsletter');
                            $this->context->customer->optin = (int)Tools::isSubmit('optin');
                            $this->context->customer->is_guest = (Tools::isSubmit('is_new_customer') ? !Tools::getValue('is_new_customer', 1) : 0);
                            $return = array(
                                'hasError' => !empty($this->errors),
                                'errors' => $this->errors,
                                'id_customer' => (int)$this->context->customer->id,
                                'token' => Tools::getToken(false)
                            );
                            if (!count($this->errors)) {
                                $return['isSaved'] = (bool)$this->context->customer->update();
                            } else {
                                $return['isSaved'] = false;
                            }
                            $this->ajaxDie(Tools::jsonEncode($return));
                            break;

                        case 'getAddressBlockAndCarriersAndPayments':
                            if ($this->context->customer->isLogged() || $this->context->customer->isGuest()) {
                                // check if customer have addresses
                                if (!Customer::getAddressesTotalById($this->context->customer->id)) {
                                    $this->ajaxDie(Tools::jsonEncode(array('no_address' => 1)));
                                }
                                if (file_exists(_PS_MODULE_DIR_.'blockuserinfo/blockuserinfo.php')) {
                                    include_once(_PS_MODULE_DIR_.'blockuserinfo/blockuserinfo.php');
                                    $block_user_info = new BlockUserInfo();
                                }
                                $this->context->smarty->assign('isVirtualCart', $this->context->cart->isVirtualCart());
                                $this->_processAddressFormat();
                                $this->_assignAddress();

                                if (!($formated_address_fields_values_list = $this->context->smarty->getTemplateVars('formatedAddressFieldsValuesList'))) {
                                    $formated_address_fields_values_list = array();
                                }

                                // Wrapping fees
                                $wrapping_fees = $this->context->cart->getGiftWrappingPrice(false);
                                $wrapping_fees_tax_inc = $this->context->cart->getGiftWrappingPrice();
                                $is_adv_api = Tools::getValue('isAdvApi');

                                if ($is_adv_api) {
                                    $tpl = 'order-address-advanced.tpl';
                                    $this->context->smarty->assign(
                                        array('products' => $this->context->cart->getProducts())
                                    );
                                } else {
                                    $tpl = 'order-address.tpl';
                                }

                                $return = array_merge(array(
                                    'order_opc_adress' => $this->context->smarty->fetch(_PS_THEME_DIR_.$tpl),
                                    'block_user_info' => (isset($block_user_info) ? $block_user_info->hookDisplayTop(array()) : ''),
                                    'block_user_info_nav' => (isset($block_user_info) ? $block_user_info->hookDisplayNav(array()) : ''),
                                    'formatedAddressFieldsValuesList' => $formated_address_fields_values_list,
                                    'carrier_data' => ($is_adv_api ? '' : $this->_getCarrierList()),
                                    'HOOK_TOP_PAYMENT' => ($is_adv_api ? '' : Hook::exec('displayPaymentTop')),
                                    'HOOK_PAYMENT' => ($is_adv_api ? '' : $this->_getPaymentMethods()),
                                    'no_address' => 0,
                                    'gift_price' => Tools::displayPrice(Tools::convertPrice(
                                        Product::getTaxCalculationMethod() == 1 ? $wrapping_fees : $wrapping_fees_tax_inc,
                                        new Currency((int)$this->context->cookie->id_currency)))
                                    ),
                                    $this->getFormatedSummaryDetail()
                                );
                                $this->ajaxDie(Tools::jsonEncode($return));
                            }
                            die(Tools::displayError());
                            break;

                        case 'makeFreeOrder':
                            /* Bypass payment step if total is 0 */
                            if (($id_order = $this->_checkFreeOrder()) && $id_order) {
                                $order = new Order((int)$id_order);
                                $email = $this->context->customer->email;
                                if ($this->context->customer->is_guest) {
                                    $this->context->customer->logout();
                                } // If guest we clear the cookie for security reason
                                $this->ajaxDie('freeorder:'.$order->reference.':'.$email);
                            }
                            exit;
                            break;
                        
                        case 'updateAddressesSelected':
                            if ($this->context->customer->isLogged(true)) {
                                $address_delivery = new Address((int)Tools::getValue('id_address_delivery'));
                                $this->context->smarty->assign('isVirtualCart', $this->context->cart->isVirtualCart());
                                $address_invoice = ((int)Tools::getValue('id_address_delivery') == (int)Tools::getValue('id_address_invoice') ? $address_delivery : new Address((int)Tools::getValue('id_address_invoice')));
                                if ($address_delivery->id_customer != $this->context->customer->id || $address_invoice->id_customer != $this->context->customer->id) {
                                    $this->errors[] = Tools::displayError('This address is not yours.');
                                } elseif (!Address::isCountryActiveById((int)Tools::getValue('id_address_delivery'))) {
                                    $this->errors[] = Tools::displayError('This address is not in a valid area.');
                                } elseif (!Validate::isLoadedObject($address_delivery) || !Validate::isLoadedObject($address_invoice) || $address_invoice->deleted || $address_delivery->deleted) {
                                    $this->errors[] = Tools::displayError('This address is invalid.');
                                } else {
                                    $this->context->cart->id_address_delivery = (int)Tools::getValue('id_address_delivery');
                                    $this->context->cart->id_address_invoice = Tools::isSubmit('same') ? $this->context->cart->id_address_delivery : (int)Tools::getValue('id_address_invoice');
                                    if (!$this->context->cart->update()) {
                                        $this->errors[] = Tools::displayError('An error occurred while updating your cart.');
                                    }

                                    $infos = Address::getCountryAndState((int)$this->context->cart->id_address_delivery);
                                    if (isset($infos['id_country']) && $infos['id_country']) {
                                        $country = new Country((int)$infos['id_country']);
                                        $this->context->country = $country;
                                    }

                                    // Address has changed, so we check if the cart rules still apply
                                    $cart_rules = $this->context->cart->getCartRules();
                                    CartRule::autoRemoveFromCart($this->context);
                                    CartRule::autoAddToCart($this->context);
                                    if ((int)Tools::getValue('allow_refresh')) {
                                        // If the cart rules has changed, we need to refresh the whole cart
                                        $cart_rules2 = $this->context->cart->getCartRules();
                                        if (count($cart_rules2) != count($cart_rules)) {
                                            $this->ajax_refresh = true;
                                        } else {
                                            $rule_list = array();
                                            foreach ($cart_rules2 as $rule) {
                                                $rule_list[] = $rule['id_cart_rule'];
                                            }
                                            foreach ($cart_rules as $rule) {
                                                if (!in_array($rule['id_cart_rule'], $rule_list)) {
                                                    $this->ajax_refresh = true;
                                                    break;
                                                }
                                            }
                                        }
                                    }

                                    if (!$this->context->cart->isMultiAddressDelivery()) {
                                        $this->context->cart->setNoMultishipping();
                                    } // As the cart is no multishipping, set each delivery address lines with the main delivery address

                                    if (!count($this->errors)) {
                                        $result = $this->_getCarrierList();
                                        // Wrapping fees
                                        $wrapping_fees = $this->context->cart->getGiftWrappingPrice(false);
                                        $wrapping_fees_tax_inc = $this->context->cart->getGiftWrappingPrice();
                                        $result = array_merge($result, array(
                                            'HOOK_TOP_PAYMENT' => Hook::exec('displayPaymentTop'),
                                            'HOOK_PAYMENT' => $this->_getPaymentMethods(),
                                            'gift_price' => Tools::displayPrice(Tools::convertPrice(Product::getTaxCalculationMethod() == 1 ? $wrapping_fees : $wrapping_fees_tax_inc, new Currency((int)$this->context->cookie->id_currency))),
                                            'carrier_data' => $this->_getCarrierList(),
                                            'refresh' => (bool)$this->ajax_refresh),
                                            $this->getFormatedSummaryDetail()
                                        );
                                        $this->ajaxDie(Tools::jsonEncode($result));
                                    }
                                }
                                if (count($this->errors)) {
                                    $this->ajaxDie(Tools::jsonEncode(array(
                                        'hasError' => true,
                                        'errors' => $this->errors
                                    )));
                                }
                            }
                            die(Tools::displayError());
                            break;
                            
                            
						default:
							throw new PrestaShopException('Unknown method "'.Tools::getValue('method').'"');
					}
				} else {
					throw new PrestaShopException('Method is not defined');
				}
			}
		} elseif (Tools::isSubmit('ajax')) {
			$this->errors[] = Tools::displayError('There is no product in your cart.');
			$this->ajaxDie('{"hasError" : true, "errors" : ["'.implode('\',\'', $this->errors).'"]}');
		}
                
		$this->context->smarty->assign(
			array(
				'checkedTOS'					=>	$this->context->cart->checkedTOS,
				'isVirtualCart'					=>	$this->context->cart->isVirtualCart(),
				'productNumber'					=>	$this->context->cart->nbProducts(),
				'back'							=>	Tools::safeOutput(Tools::getValue('back')),
				'isLogged'						=>	$this->isLogged,
				'faster_checkout_enabled'		=>	$faster_checkout_enabled,
				'nimble_credentials'			=>	$nimble_credentials,
				'ssl'							=>	$ssl,
				'params'						=>	array(),
				)
		);

		$this->_assignSummaryInformations();
        
        if (Configuration::get('PS_RESTRICT_DELIVERED_COUNTRIES')) {
            $countries = Carrier::getDeliveredCountries($this->context->language->id, true, true);
        } else {
            $countries = Country::getCountries($this->context->language->id, true);
        }
        
        // If a rule offer free-shipping, force hidding shipping prices
        $free_shipping = false;
        foreach ($this->context->cart->getCartRules() as $rule) {
            if ($rule['free_shipping'] && !$rule['carrier_restriction']) {
                $free_shipping = true;
                break;
            }
        }

        $this->context->smarty->assign(array(
            'free_shipping' => $free_shipping,
            'isGuest' => isset($this->context->cookie->is_guest) ? $this->context->cookie->is_guest : 0,
            'countries' => $countries,
            'sl_country' => (int)Tools::getCountry(),
            'PS_GUEST_CHECKOUT_ENABLED' => Configuration::get('PS_GUEST_CHECKOUT_ENABLED'),
            'errorCarrier' => Tools::displayError('You must choose a carrier.', false),
            'errorTOS' => Tools::displayError('You must accept the Terms of Service.', false),
            'isPaymentStep' => isset($_GET['isPaymentStep']) && $_GET['isPaymentStep'],
            'genders' => Gender::getGenders(),
            'one_phone_at_least' => (int)Configuration::get('PS_ONE_PHONE_AT_LEAST'),
            'HOOK_CREATE_ACCOUNT_FORM' => Hook::exec('displayCustomerAccountForm'),
            'HOOK_CREATE_ACCOUNT_TOP' => Hook::exec('displayCustomerAccountFormTop')
        ));
        $years = Tools::dateYears();
        $months = Tools::dateMonths();
        $days = Tools::dateDays();
        $this->context->smarty->assign(array(
            'years' => $years,
            'months' => $months,
            'days' => $days,
        ));

        /* Load guest informations */
        if ($this->isLogged && $this->context->cookie->is_guest) {
            $this->context->smarty->assign('guestInformations', $this->_getGuestInformations());
        }
        
		// ADDRESS
        if ($this->isLogged) {
            $this->_assignAddress();
        }
        // CARRIER
		$this->_getCarrierList();
        // PAYMENT
		$this->_assignPayment();
        Tools::safePostVars();
        
        $newsletter = Configuration::get('PS_CUSTOMER_NWSL') || (Module::isInstalled('blocknewsletter') && Module::getInstanceByName('blocknewsletter')->active);
        $this->context->smarty->assign('newsletter', $newsletter);
        $this->context->smarty->assign('optin', (bool)Configuration::get('PS_CUSTOMER_OPTIN'));
        $this->context->smarty->assign('field_required', $this->context->customer->validateFieldsRequiredDatabase());

        if ((bool)Configuration::get('PS_ADVANCED_PAYMENT_API')) {
            $this->addJS(_THEME_JS_DIR_ . 'advanced-payment-api.js');
        }
        
        $this->_processAddressFormat();
        
		$this->setTemplate('fastercheckout.tpl');
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
    
	protected function _assignSummaryInformations()
	{
		$summary = $this->context->cart->getSummaryDetails();
		$customizedDatas = Product::getAllCustomizedDatas($this->context->cart->id);

		// override customization tax rate with real tax (tax rules)
		if ($customizedDatas) {
			foreach ($summary['products'] as &$productUpdate) {
				$productId = (int)isset($productUpdate['id_product']) ? $productUpdate['id_product'] : $productUpdate['product_id'];
				$productAttributeId = (int)isset($productUpdate['id_product_attribute']) ? $productUpdate['id_product_attribute'] : $productUpdate['product_attribute_id'];

				if (isset($customizedDatas[$productId][$productAttributeId])) {
					$productUpdate['tax_rate'] = Tax::getProductTaxRate($productId, $this->context->cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')});
				}
			}

			Product::addCustomizationPrice($summary['products'], $customizedDatas);
		}

		$cart_product_context = Context::getContext()->cloneContext();
		foreach ($summary['products'] as $key => &$product) {
			$product['quantity'] = $product['cart_quantity'];// for compatibility with 1.2 themes

			if ($cart_product_context->shop->id != $product['id_shop']) {
				$cart_product_context->shop = new Shop((int)$product['id_shop']);
			}
			$product['price_without_specific_price'] = Product::getPriceStatic(
				$product['id_product'],
				!Product::getTaxCalculationMethod(),
				$product['id_product_attribute'],
				6,
				null,
				false,
				false,
				1,
				false,
				null,
				null,
				null,
				$null,
				true,
				true,
				$cart_product_context);

			if (Product::getTaxCalculationMethod()) {
				$product['is_discounted'] = Tools::ps_round($product['price_without_specific_price'], _PS_PRICE_COMPUTE_PRECISION_) != Tools::ps_round($product['price'], _PS_PRICE_COMPUTE_PRECISION_);
			} else {
				$product['is_discounted'] = Tools::ps_round($product['price_without_specific_price'], _PS_PRICE_COMPUTE_PRECISION_) != Tools::ps_round($product['price_wt'], _PS_PRICE_COMPUTE_PRECISION_);
			}
		}

		// Get available cart rules and unset the cart rules already in the cart
		$available_cart_rules = CartRule::getCustomerCartRules($this->context->language->id, (isset($this->context->customer->id) ? $this->context->customer->id : 0), true, true, true, $this->context->cart, false, true);
		$cart_cart_rules = $this->context->cart->getCartRules();
		foreach ($available_cart_rules as $key => $available_cart_rule) {
			foreach ($cart_cart_rules as $cart_cart_rule) {
				if ($available_cart_rule['id_cart_rule'] == $cart_cart_rule['id_cart_rule']) {
					unset($available_cart_rules[$key]);
					continue 2;
				}
			}
		}

		$show_option_allow_separate_package = (!$this->context->cart->isAllProductsInStock(true) && Configuration::get('PS_SHIP_WHEN_AVAILABLE'));
		$advanced_payment_api = (bool)Configuration::get('PS_ADVANCED_PAYMENT_API');

		$this->context->smarty->assign($summary);
		$this->context->smarty->assign(array(
			'token_cart' => Tools::getToken(false),
			'isLogged' => $this->isLogged,
			'isVirtualCart' => $this->context->cart->isVirtualCart(),
			'productNumber' => $this->context->cart->nbProducts(),
			'voucherAllowed' => CartRule::isFeatureActive(),
			'shippingCost' => $this->context->cart->getOrderTotal(true, Cart::ONLY_SHIPPING),
			'shippingCostTaxExc' => $this->context->cart->getOrderTotal(false, Cart::ONLY_SHIPPING),
			'customizedDatas' => $customizedDatas,
			'CUSTOMIZE_FILE' => Product::CUSTOMIZE_FILE,
			'CUSTOMIZE_TEXTFIELD' => Product::CUSTOMIZE_TEXTFIELD,
			'lastProductAdded' => $this->context->cart->getLastProduct(),
			'displayVouchers' => $available_cart_rules,
			'show_option_allow_separate_package' => $show_option_allow_separate_package,
			'smallSize' => Image::getSize(ImageType::getFormatedName('small')),
			'advanced_payment_api' => $advanced_payment_api

		));
		$this->context->smarty->assign(array(
			'HOOK_SHOPPING_CART' => ''
		));
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
        $this->addCSS(_PS_ROOT_DIR_ . '/modules/nimblepayment/views/css/nimble.css');

        // Adding JS files
        $this->addJS(_PS_ROOT_DIR_. '/modules/nimblepayment/views/js/tools.js');  // retro compat themes 1.5

        $this->addJS(_PS_ROOT_DIR_. '/modules/nimblepayment/views/js/order-address.js');

        // Adding JS files
        $this->addJS(_PS_ROOT_DIR_. '/modules/nimblepayment/views/js/order-opc.js');
        $this->addJqueryPlugin('fancybox');
        $this->addJS(_PS_ROOT_DIR_. '/modules/nimblepayment/views/js/order-carrier.js');

        $this->addJqueryPlugin('typewatch');
        $this->addJS(_PS_ROOT_DIR_. '/modules/nimblepayment/views/js/cart-summary.js');
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

    /**
     * Validate get/post param delivery option
     *
     * @param array $delivery_option
     *
     * @return bool
     */
    protected function validateDeliveryOption($delivery_option)
    {
        if (!is_array($delivery_option)) {
            return false;
        }

        foreach ($delivery_option as $option) {
            if (!preg_match('/(\d+,)?\d+/', $option)) {
                return false;
            }
        }

        return true;
    }

    protected function _processCarrier()
    {
        $this->context->cart->recyclable = (int)Tools::getValue('recyclable');
        $this->context->cart->gift = (int)Tools::getValue('gift');
        if ((int)Tools::getValue('gift')) {
            if (!Validate::isMessage(Tools::getValue('gift_message'))) {
                $this->errors[] = Tools::displayError('Invalid gift message.');
            } else {
                $this->context->cart->gift_message = strip_tags(Tools::getValue('gift_message'));
            }
        }
		
        if (isset($this->context->customer->id) && $this->context->customer->id) {
            $address = new Address((int)$this->context->cart->id_address_delivery);
            if (!($id_zone = Address::getZoneById($address->id))) {
                $this->errors[] = Tools::displayError('No zone matches your address.');
            }
        } else {
            $id_zone = (int)Country::getIdZone((int)Tools::getCountry());
        }
		
        if (Tools::getIsset('delivery_option')) {
            if ($this->validateDeliveryOption(Tools::getValue('delivery_option'))) {
                $this->context->cart->setDeliveryOption(Tools::getValue('delivery_option'));
            }
        } elseif (Tools::getIsset('id_carrier')) {
            // For retrocompatibility reason, try to transform carrier to an delivery option list
            $delivery_option_list = $this->context->cart->getDeliveryOptionList();
            if (count($delivery_option_list) == 1) {
                $delivery_option = reset($delivery_option_list);
                $key = Cart::desintifier(Tools::getValue('id_carrier'));
                foreach ($delivery_option_list as $id_address => $options) {
                    if (isset($options[$key])) {
                        $this->context->cart->id_carrier = (int)Tools::getValue('id_carrier');
                        $this->context->cart->setDeliveryOption(array($id_address => $key));
                        if (isset($this->context->cookie->id_country)) {
                            unset($this->context->cookie->id_country);
                        }
                        if (isset($this->context->cookie->id_state)) {
                            unset($this->context->cookie->id_state);
                        }
                    }
                }
            }
        }

        Hook::exec('actionCarrierProcess', array('cart' => $this->context->cart));

        if (!$this->context->cart->update()) {
            return false;
        }

        // Carrier has changed, so we check if the cart rules still apply
        CartRule::autoRemoveFromCart($this->context);
        CartRule::autoAddToCart($this->context);

        return true;
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
    
    protected function _updateMessage($messageContent)
    {
        if ($messageContent) {
            if (!Validate::isMessage($messageContent)) {
                $this->errors[] = Tools::displayError('Invalid message');
            } elseif ($oldMessage = Message::getMessageByCartId((int)$this->context->cart->id)) {
                $message = new Message((int)$oldMessage['id_message']);
                $message->message = $messageContent;
                $message->update();
            } else {
                $message = new Message();
                $message->message = $messageContent;
                $message->id_cart = (int)$this->context->cart->id;
                $message->id_customer = (int)$this->context->cart->id_customer;
                $message->add();
            }
        } else {
            if ($oldMessage = Message::getMessageByCartId($this->context->cart->id)) {
                $message = new Message($oldMessage['id_message']);
                $message->delete();
            }
        }
        return true;
    }
    
    /**
     * Check if order is free
     * @return bool
     */
    protected function _checkFreeOrder()
    {
        if ($this->context->cart->getOrderTotal() <= 0) {
            $order = new NimbleFreeOrder();
            $order->free_order_class = true;
            $order->validateOrder($this->context->cart->id, Configuration::get('PS_OS_PAYMENT'), 0, Tools::displayError('Free order', false), null, array(), null, false, $this->context->cart->secure_key);
            return (int)Order::getOrderByCartId($this->context->cart->id);
        }
        return false;
    }

}