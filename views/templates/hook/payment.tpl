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
	<div class="col-xs-12 col-md-12 module-nimble-payment">
		<div class="title-nimble-payment">
			<h4 id="nimble-credit-card">{l s='Pay by Credit card' mod='nimblepayment'}</h4>
		</div>
		<div class="nimble-cards">
			<ul class="nimble-stored-cards">		
			   {if !$hideCards}
				   {if !empty($cards)}
					   {l s='Select a saved card:' mod='nimblepayment'}
					   {foreach from=$cards item=card}
						   <li class="list-card-nimble">
							   <input class="input-radio" type="radio" id="nimblepayment_storedcard_1" name="nimblepayment_storedcard" {if $card['default']} checked {/if} value="{$card|json_encode|base64_encode}"/>
							   <label for="nimblepayment_storedcard_1" class="stored_card {$card['cardBrand']|lower}">{$card['maskedPan']}</label>
						   </li>	
					   {/foreach}
					   <input class="input-radio" type="radio" id="nimblepayment_storedcard_2" name="nimblepayment_storedcard" value=""/>
				   {else}
					   <input class="input-radio" type="radio" id="nimblepayment_storedcard_2" name="nimblepayment_storedcard" checked value=""/>
				   {/if}
				   <label for="nimblepayment_storedcard_2" class="stored_card">{l s='New card' mod='nimblepayment'}</label>
			   {/if}
			</ul>
			<p class="payment_module cart_navigation clearfix">
				 <a 
					 href="{$link->getModuleLink('nimblepayment', 'payment', $params, $ssl)|escape:'htmlall':'UTF-8'}" data-href="{$link->getModuleLink('nimblepayment', 'payment', $params, $ssl)|escape:'htmlall':'UTF-8'}"
					 class="nimblepayment button btn btn-default standard-checkout button-medium" 
					 title="{l s='Pay' mod='nimblepayment'}" >
					 <span id="title-nimble-pay">{l s='Pay' mod='nimblepayment'}</span>
				 </a>
			 </p>
		</div>		   
		<div class="img-logo-nimble">	   
			<img src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/img-boton.png" alt="{l s='Pay' mod='nimblepayment'}"/>
		</div>
	</div>
</div>
<div class="nimblepayments-overlay"><div class="overlay"></div></div>
<div class="nimblepayments-overlay"><div class="box-info"><img src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/loading.gif"/><h2>PROCESANDO PEDIDO</h2></div></div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#nimblepayment_gateway").one( "click", function(event) {
            $(".nimblepayments-overlay").show();
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
                        $(location).attr('href', response['redirect']);
                    } else if ('error' in response){
                        $('#HOOK_PAYMENT .alert').remove();
                        $('#HOOK_PAYMENT').prepend('<p class="alert alert-danger">' + response['error']['message'] + '</p>');
                    }
                    $('#nimblepayment_gateway').data('clicked', true);
                }
            });
        }).click(function(){
            if ( ! $(this).data('clicked') ){
                event.preventDefault();
            }
        });
    });
</script>