<link href="{$module_dir|escape:'htmlall':'UTF-8'}views/css/nimble.css" rel="stylesheet" type="text/css" media="all">

<div id="nimble-refund-panel"></div>
<div class="row">
	<div class="col-lg-12">
		<div class="panel">
                    <div class="text">{l s='You have not yet Prestashop authorized to perform operations on Nimble Payments.' mod='nimblepayment'}
                        <p class="btn">
                            <a href="{$Oauth3Url}" class="btn btn-primary link">{l s='Authorize Prestashop' mod='nimblepayment'}</a>
                        </p>
                    </div>
                </div>
	</div>
</div>