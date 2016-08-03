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

<link href="{$module_dir|escape:'htmlall':'UTF-8'}views/css/nimble.css" rel="stylesheet" type="text/css" media="all">
<div class="row">
	<div class="col-xs-12 col-md-6">
        <p class="payment_module">
            <a id="nimblepayment_gateway"
            class="nimblepayment bankwire"
            href="{$link->getModuleLink('nimblepayment', 'payment', $params, $ssl)|escape:'htmlall':'UTF-8'}" data-href="{$link->getModuleLink('nimblepayment', 'payment', $params, $ssl)|escape:'htmlall':'UTF-8'}"
            title="{l s='Pay by Credit card' mod='nimblepayment'}">
                {l s='Pay by Credit card' mod='nimblepayment'} 
                
                <img src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/img-boton.png" alt="{l s='Pay by Credit card' mod='nimblepayment'}"/>
            </a>
        </p>    
        <li>
            <input class="input-radio" type="radio" id="nimblepayment_storedcard_1" name="nimblepayment_storedcard" {if $cards['default']}  checked {/if} value="{$cards|json_encode|base64_encode}" />
            <label for="nimblepayment_storedcard_1" class="stored_card {$cards['cardBrand']|lower}"> {$cards['maskedPan']}</label>
            <input class="input-radio" type="radio" id="nimblepayment_storedcard_2" name="nimblepayment_storedcard"  value="" />
            <label for="nimblepayment_storedcard_2" class="stored_card">{l s='New card' mod='nimblepayment'}</label>
        </li>

    </div>
</div>
<script type="text/javascript">
    $(document).ready(function () {
        $("#nimblepayment_gateway").one( "click", function(event) {
			event.preventDefault();
			$.ajax({
				type: 'POST',
				url: $(this).data('href'),
				data: {
					'action': 'payment',
					'nimblepayment_storedcard': $('input[name="nimblepayment_storedcard"]:checked').val()
				},
				dataType: 'json',
				success: function(response) {
					if ('redirect' in response){
						$('#nimblepayment_gateway').attr('href', response['redirect']);
						if ($('#cgv:checked').length != 0) {
							$(location).attr('href', response['redirect'])
						}
					} else if ('error' in response){
						$('#HOOK_PAYMENT .alert').remove();
						$('#HOOK_PAYMENT').prepend('<p class="alert alert-danger">' + response['error']['message'] + '</p>');
					}
					$('#nimblepayment_gateway').data('clicked', true);
				}
			});
        }).click(function(){
			if ($('#cgv:checked').length == 0) {
				$('#HOOK_PAYMENT .alert').remove();
				$('#HOOK_PAYMENT').prepend('<p class="alert alert-danger">RAFIKI</p>');
				event.preventDefault();
			} else if ( ! $(this).data('clicked') ){
				event.preventDefault();
			}
        });
    });
</script>            