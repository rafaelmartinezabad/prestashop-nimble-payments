{*
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
*  @author    Devtopia Coop <hello@devtopia.coop>
*  @copyright 2007-2015 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

<input id="payment_detail_ajax_url" type="hidden" name="payment_detail_ajax_url" value="{$link->getModuleLink('nimblepayment', 'paymentDetails', $parameters, $ssl)|escape:'htmlall':'UTF-8'}"/>
<script type="text/javascript">
   $(document).ready(function () {
        $(".open_payment_information").click(function(event) {
            if ( ! $(this).data('clicked') ){
                event.preventDefault();
                $.ajax({
                    type: 'POST',
                    url: $('#payment_detail_ajax_url').val(),
                    data: {
                        'order_id': $('input[name="id_order"]').val(),
                    },
                    //dataType: 'json',
                    async: false,
                    success: function(response) {
                        //$('tr.payment_information').addClass('hidden');
                        $("tr.payment_information").html(response);
                    }
                });
            }
        });
    });
</script>
