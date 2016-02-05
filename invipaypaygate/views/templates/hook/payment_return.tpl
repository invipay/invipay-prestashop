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

<div id="invipay_paygate_state_waiting" style="display: {if $invipay_paygate.start_data.state === null}block{else}none{/if};">
	<h3>{l s='return_state_waiting_header' sprintf=$shop_name mod='invipaypaygate'}</h3>
	<p>{l s='return_state_waiting_message' sprintf=$shop_name mod='invipaypaygate'}</p>
</div>

<div id="invipay_paygate_state_completed" style="display: {if $invipay_paygate.start_data.state === true}block{else}none{/if};">
	<h3>{l s='return_state_completed_header' sprintf=$shop_name mod='invipaypaygate'}</h3>
	<p class="alert alert-success">{l s='return_state_completed_message' sprintf=$shop_name mod='invipaypaygate'}</p>
</div>

<div id="invipay_paygate_state_failed" style="display: {if $invipay_paygate.start_data.state === false}block{else}none{/if};">
	<h3>{l s='return_state_failed_header' sprintf=$shop_name mod='invipaypaygate'}</h3>
	<p class="alert alert-warning">{l s='return_state_failed_message' sprintf=$shop_name mod='invipaypaygate'}</p>
</div>


{if $invipay_paygate.start_data.state !== true}
	{literal}
		<script type="text/javascript">

			(function(){

				ajaxCall = function(url, callback) {
					
					var xhr;

					if(typeof XMLHttpRequest !== 'undefined') {
						xhr = new XMLHttpRequest();
					}
					else {
						var versions = ["MSXML2.XmlHttp.5.0", "MSXML2.XmlHttp.4.0", "MSXML2.XmlHttp.3.0",  "MSXML2.XmlHttp.2.0", "Microsoft.XmlHttp"]

						for(var i = 0, len = versions.length; i < len; i++) {
							try {
								xhr = new ActiveXObject(versions[i]);
								break;
							}
							catch(e){}
						}
					}

					xhr.onreadystatechange = function() {
						if(xhr.readyState < 4) {
							return;
						}

						if(xhr.status !== 200) {
							return;
						}

						if(xhr.readyState === 4) {
							callback(xhr);
						}
					}

					xhr.open('GET', url, true);
					xhr.send('');
				}

				doCheckPayment = function()
				{
					ajaxCall('{/literal}{$invipay_paygate.status_check_url|escape:'htmlall':'UTF-8'}{literal}', function(xhr) {

						if (xhr) {
							var response = xhr.responseText;
							if (response) {
								var obj = JSON.parse(response);
								if (obj) {
									if (obj['state'] == true) {
										document.getElementById('invipay_paygate_state_waiting').style.display = 'none';
										document.getElementById('invipay_paygate_state_completed').style.display = 'block';
										document.getElementById('invipay_paygate_state_failed').style.display = 'none';
										
									} else if (obj['state'] == false) {
										document.getElementById('invipay_paygate_state_waiting').style.display = 'none';
										document.getElementById('invipay_paygate_state_completed').style.display = 'none';
										document.getElementById('invipay_paygate_state_failed').style.display = 'block';
									} else {
										window.setTimeout(doCheckPayment, 1000);
									}
								}
							}
						}
					});
				};

				doCheckPayment();

			})();
		</script>
	{/literal}
{/if}