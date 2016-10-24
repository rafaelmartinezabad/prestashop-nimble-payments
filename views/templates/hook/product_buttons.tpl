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
<link href="{$module_dir|escape:'htmlall':'UTF-8'}views/css/nimble.css{$version_css|escape:'htmlall':'UTF-8'}" rel="stylesheet" type="text/css" media="all">

<div{if (!$allow_oosp && $product->quantity <= 0) || !$product->available_for_order || (isset($restricted_country_mode) && $restricted_country_mode) || $PS_CATALOG_MODE} class="unvisible"{/if}>
    <p id="faster_checkout" class="buttons_bottom_block no-print">
        <button type="submit" name="{l s='Faster checkout' mod='nimblepayment'}" class="exclusive btn-outline-inverse" data-href="{$url_faster_checkout|escape:'htmlall':'UTF-8'}">
                <span>{l s='Faster checkout' mod='nimblepayment'}</span>
        </button>
    </p>
</div>
<script type="text/javascript">
    $(window).bind('hashchange', function(){
		updateFasterCheckoutDisplay();
    });
    
    $(document).ready(function()
    {
        updateFasterCheckoutDisplay();
		
		$('#faster_checkout>button').on('click', function(e){
			e.preventDefault();
			var new_action_url = $('#faster_checkout>button').data('href');
			$('#buy_block').attr('action', new_action_url);
			$('#buy_block').submit();
		});
    });
    
    //SHOW AND HIDE FASTERCHECKOUT BUTTON
    function updateFasterCheckoutDisplay()
    {
		if (!selectedCombination['unavailable'] && quantityAvailable > 0 && productAvailableForOrder == 1)
		{
			//show the "faster_checkout" button ONLY if it was hidden
			$('#faster_checkout:hidden').fadeIn(600);
		}
		else
		{
			//show the 'faster_checkout' button ONLY IF it's possible to buy when out of stock AND if it was previously invisible
			if (allowBuyWhenOutOfStock && !selectedCombination['unavailable'] && productAvailableForOrder)
			{
				$('#faster_checkout:hidden').fadeIn(600);

			}
			else
			{
				$('#faster_checkout:visible').fadeOut(600);
			}
		}
    }
</script>