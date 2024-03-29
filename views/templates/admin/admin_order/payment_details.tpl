{*
* 2007-2016 PrestaShop
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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2016 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}
<input id="payment_detail_ajax_token" type="hidden" name="payment_detail_ajax_token" value="{$token|escape:'htmlall':'UTF-8'}"/>
<script type="text/javascript">
   $(document).ready(function () {
        $(".open_payment_information").first().click(function(event) {
            if ( ! $(this).data('clicked') ){
                $("tr.payment_information").first().html('<td id="nimble-payment-details-td" colspan="6"></td>');
                $(this).data('clicked', true);
                event.preventDefault();
                $.ajax({
                    type: 'POST',
                    url : 'ajax-tab.php',
                    data: {
                        ajax : true,
                        controller : 'NimblePaymentAdminPaymentDetails',
                        action : 'paymentDetails',
                        token : $('#payment_detail_ajax_token').val(),
                        order_id : $('input[name="id_order"]').val(),
                    },
                    success: function(response) {
                        $("#nimble-payment-details-td").html(response);
                    },
                    error: function() {
                        console.log("Error on ajax")
                    }
                });
            }
        });
    });
</script>
