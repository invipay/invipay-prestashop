{*
*	Copyright (C) 2016 inviPay.com
*	
*	http://www.invipay.com
*
*	Redistribution and use in source and binary forms, with or
*	without modification, are permitted provided that the following
*	conditions are met: Redistributions of source code must retain the
*	above copyright notice, this list of conditions and the following
*	disclaimer. Redistributions in binary form must reproduce the above
*	copyright notice, this list of conditions and the following disclaimer
*	in the documentation and/or other materials provided with the
*	distribution.
*	
*	THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED
*	WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
*	MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN
*	NO EVENT SHALL CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
*	INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
*	BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS
*	OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
*	ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR
*	TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE
*	USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH
*	DAMAGE.
*
*}

<div class="row invipay_payment_method">
	<div class="col-xs-12">
		<p class="payment_module invipay_payment_method">
			
			{if $invipay_paygate.method_not_available == false}
				<a href="{$link->getModuleLink('invipaypaygate', 'payment', [], true)|escape:'htmlall':'UTF-8'}" title="{$invipay_paygate.method_title|strip_tags|escape:'htmlall':'UTF-8'}">
			{else}
				<a class="not_available_main">
			{/if}
				{if $invipay_paygate.method_not_available == false}
					{$invipay_paygate.method_title|escape:'htmlall':'UTF-8'} <span>({l s='payment_method_subtitle' mod='invipaypaygate'})</span>
				{else}
					{l s='method_not_available_info' mod='invipaypaygate' sprintf=$invipay_paygate.minimum_value}
				{/if}
				<br>
				<span class="learn_more" onclick="window.open('//invipay.com/zakupy-w-internecie-z-invipay-com/', 'inviPayLearnMore', 'width=400,height=700,status=0,titlebar=0,toolbar=0,menubar=0,scrollbars=1'); return false;">{l s='learn_more' mod='invipaypaygate'}</span>
			
			{if $invipay_paygate.method_not_available == false}
				</a>
			{else}
				</a>
			{/if}
		</p>
	</div>
</div>

<style type="text/css">
	.row.invipay_payment_method p.payment_module a { padding-left: 140px; background-color: #fbfbfb; background-repeat: no-repeat; background-size: auto 32px; background-position: 15px center; background-image: url(//invipay.com/promo/images/logo_slogan_medium.png); }
	.row.invipay_payment_method p.payment_module a:hover { background-color: #f6f6f6; }
	.row.invipay_payment_method p.payment_module a::after { display: block; content: "\f054"; position: absolute; right: 15px; margin-top: -11px; top: 50%; font-family: "FontAwesome"; font-size: 25px;height: 22px; width: 14px; color: #777; }
	.payment_module.invipay_payment_method img { height: 30px; margin-right: 15px; }
	.payment_module.invipay_payment_method a.not_available_main { opacity: 0.5; }
	.row.invipay_payment_method span.learn_more { padding: 0px !important; margin: 0px !important; color: #00B2DD !important; font-weight: normal !important; font-size: small !important; }
	.row.invipay_payment_method span.learn_more:hover { text-decoration: underline !important; }
</style>