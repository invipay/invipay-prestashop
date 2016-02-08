{*
*   Copyright (C) 2016 inviPay.com
*   
*   http://www.invipay.com
*
*   Redistribution and use in source and binary forms, with or
*   without modification, are permitted provided that the following
*   conditions are met: Redistributions of source code must retain the
*   above copyright notice, this list of conditions and the following
*   disclaimer. Redistributions in binary form must reproduce the above
*   copyright notice, this list of conditions and the following disclaimer
*   in the documentation and/or other materials provided with the
*   distribution.
*   
*   THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED
*   WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
*   MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN
*   NO EVENT SHALL CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
*   INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
*   BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS
*   OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
*   ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR
*   TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE
*   USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH
*   DAMAGE.
*}

<h2 class="page-heading">Podsumowanie zamówienia</h2>

{capture name=path}
	<a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'htmlall':'UTF-8'}" title="{l s='order_summary_change_payment_method' mod='invipaypaygate'}">{l s='order_summary_checkout_breadcrumb' mod='invipaypaygate'}</a><span class="navigation-pipe">{$navigationPipe|escape:'htmlall':'UTF-8'}</span>{$invipay_paygate.method_title|escape:'htmlall':'UTF-8'}
{/capture}

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if $nbProducts <= 0}
	<p class="warning">{l s='empty_shopping_cart' mod='invipaypaygate'}</p>
{else}

<h3 class="page-subheading">{$invipay_paygate.method_title|escape:'htmlall':'UTF-8'|replace:'(':'<span>('|replace:')':')</span>'}</h3>
<form action="{$link->getModuleLink('invipaypaygate', 'validation', [], true)|escape:'htmlall':'UTF-8'}" method="post">
    <div class="box">
        <div style="margin-bottom: 10px;">
            {if $invipay_paygate.method_description == 'method_description_standard'}
                <p style="margin-bottom: 10px;">Po odbiorze zamówionego towaru, zapłacisz za niego w&nbsp;terminie płatności wynikającym z&nbsp;faktury VAT ({$invipay_paygate.base_due_date|escape:'htmlall':'UTF-8'} dni), którą od nas otrzymasz za pośrednictwem aplikacji inviPay.com, powiększonym&nbsp;o&nbsp;7&nbsp;dni od inviPay.com (łącznie masz {$invipay_paygate.total_due_date|escape:'htmlall':'UTF-8'} dni na zapłatę).</p>
            {/if}

            {if $invipay_paygate.method_description == 'method_description_short'}
                <p style="margin-bottom: 10px;">Po odbiorze zamówionego towaru, zapłacisz za niego w&nbsp;terminie płatności wynikającym z&nbsp;faktury VAT, którą od nas otrzymasz za pośrednictwem aplikacji inviPay.com powiększonym o&nbsp;7&nbsp;dni. Możesz też wydłużyć sobie ten termin do 4 miesięcy od zakupu (za dodatkową opłatą do inviPay.com).</p>
            {/if}

            {if $invipay_paygate.method_description == 'method_description_medium'}
                <p style="margin-bottom: 10px;">Bezpieczna, szybka i&nbsp;wygodna metoda płatności świadczona za pośrednictwem inviPay.com. Po wyborze tej metody płatności, zostaniesz przekierowany do bramki płatności inviPay.com gdzie będziesz mógł potwierdzić swoje zakupy (jeżeli posiadasz konto w&nbsp;inviPay.com) lub założyć konto w&nbsp;inviPay.com i&nbsp;dokonać potwierdzenia. Założenie konta w&nbsp;inviPay.com trwa 40 sekund i&nbsp;jest całkowicie bezpłatne oraz niezobowiązujące. Z&nbsp;chwilą potwierdzenia, Twoje zamówienie trafia do realizacji.</p>

                <p style="margin-bottom: 10px;">Po odbiorze zamówionego towaru, inviPay.com zapłaci za Twoje zakupy do sklepu, a&nbsp;Ty rozliczysz się z nich z inviPay.com w terminie płatności wynikającym z&nbsp;faktury VAT wystawionej przez sklep powiększonym o&nbsp;7 dni od inviPay.com. Rozliczenie zakupu w&nbsp;ww.&nbsp;terminie płatności faktury będzie dla Ciebie całkowicie bezpłatne. </p>
            {/if}

            {if $invipay_paygate.method_description == 'method_description_long'}
                <p style="margin-bottom: 10px;">Robisz zakupy firmowe i&nbsp;chciałbyś zapłacić za nie tak jak to robisz poza Internetem tj. w terminie płatności wynikającym z faktury otrzymanej od sklepu? Nie lubisz płacić z góry przed otrzymaniem zamówienia? A&nbsp;może nie masz w&nbsp;tej chwili wolnych środków? Skorzystaj z&nbsp;metody płatności inviPay.com.</p>

                <p style="margin-bottom: 10px;">Dzięki inviPay.com możesz kupować i&nbsp;płacić za zakupy firmowe po ich odbiorze w&nbsp;terminie płatności wynikającym z&nbsp;faktury otrzymanej od sklepu powiększonym o&nbsp;7 dni od inviPay.com (oczywiście całkowicie bezpłatnie). Jeżeli masz życzenie, możesz też wydłużyć sobie ten termin płatności nawet do 4 miesięcy od zakupu ponosząc dodatkową niewielką opłatę w&nbsp;wysokości 1%, za każde 15 dni wydłużenia. Zawsze to Ty decydujesz kiedy płacisz za swoje zakupy.</p>

                <p style="margin-bottom: 10px;">InviPay.com to nie tylko bezpieczeństwo i&nbsp;komfort zakupów. To też łatwość dokonywania zwrotów zakupionych towarów. Wystarczy zawiadomić inviPay.com o&nbsp;problemie z&nbsp;zamówieniem i&nbsp;ewentualnie odesłać towar do sklepu. Z&nbsp;uwagi na to, że nie dokonywałeś żadnych płatności przed otrzymaniem towaru nie musisz też czekać na ich zwrot. Tak inviPay.com na nowo definiuje &quot;wygodne i&nbsp;bezpieczne zakupy online&quot;.</p>

                <p style="margin-bottom: 10px;">Aby dokonać zakupów na fakturę z&nbsp;odroczonym terminem, podczas składania zamówienia wybierz metodę płatności inviPay.com. Następnie zaloguj się na swoje konto inviPay.com i&nbsp;potwierdź kodem sms swoje zakupy. Jeżeli nie posiadasz konta, możesz je błyskawicznie założyć (zajmie Ci to 40 sekund, a&nbsp;założenie i&nbsp;prowadzenie konta jest całkowicie bezpłatne). Po potwierdzeniu zakupów, Twoje zamówienie trafia do realizacji.</p>
            {/if}

            <a href="#" class="learn_more" onclick="window.open('//invipay.com/zakupy-w-internecie-z-invipay-com/', 'inviPayLearnMore', 'width=400,height=700,status=0,titlebar=0,toolbar=0,menubar=0,scrollbars=1'); return false;" style="color: #00B2DD !important; font-weight: normal !important;">
                {l s='learn_more' mod='invipaypaygate'}
            </a>
        </div>

        <hr>

        <p>
                <span>{l s='order_summary_cart_value' mod='invipaypaygate'}</span> <span id="amount" class="price">{displayPrice price=$total}</span><br>
                <span>{l s='order_summary_payment_cost' mod='invipaypaygate'}</span> <span id="amount" class="price">{displayPrice price=$payment_cost}</span><br>
                <strong>{l s='order_summary_total_value' mod='invipaypaygate'}</strong> <strong><span id="amount" class="price">{displayPrice price=$total+$payment_cost}</span></strong>
        </p>

        <hr>

        <p>
            {l s='order_summary_instructions' mod='invipaypaygate'}
        </p>
    </div>

    <p class="cart_navigation" id="cart_navigation">
        <a href="{$link->getPageLink('order', true, NULL, 'step=3')|escape:'htmlall':'UTF-8'}" class="button_large button-exclusive btn btn-default">
            <i class="icon-chevron-left"></i>{l s='order_summary_change_payment_method' mod='invipaypaygate'}
        </a>

        {*
            <button type="submit" class="exclusive_large button btn btn-default button-medium">
                <span>{l s='order_summary_confirm' mod='invipaypaygate'}<i class="icon-chevron-right right"></i></span>
            </button>
        *}

        <input type="submit" value="{l s='order_summary_confirm' mod='invipaypaygate'}" class="exclusive_large" />
    </p>
</form>
{/if}
