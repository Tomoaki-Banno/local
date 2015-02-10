            </div>
            {* jQuery Mobile の ajax画面遷移の際、ここまでのセクションが読み込まれる（ここより下は読み込まれない） *}

            <div data-role="footer" data-position="fixed">
                <div data-role="navbar">
                    <ul>
                        <li><a href="index.php?action=Mobile_Home" data-ajax="false" {if $form.action=='Mobile_Home'}class="ui-btn-active"{/if}>{gen_tr}_g("ホーム"){/gen_tr}</a></li>
                        <li><a href="index.php?action=Mobile_Stock_List" data-ajax="false" {if $form.action=='Mobile_Stock_List' || $form.action=='Mobile_Stock_History'}class="ui-btn-active"{/if}>{gen_tr}_g("在庫"){/gen_tr}</a></li>
                        <!--
                        <li><a href="index.php?action=Mobile_StockInput_List" data-ajax="false" {if $form.action=='Mobile_StockInput_Edit' || $form.action=='Mobile_StockInput_List'}class="ui-btn-active"{/if}>{gen_tr}_g("棚卸"){/gen_tr}</a></li>
                        -->
                        <li><a href="index.php?action=Mobile_Received_List" data-ajax="false" {if $form.action=='Mobile_Received_List' ||  $form.action=='Mobile_Received_Detail'}class="ui-btn-active"{/if}>{gen_tr}_g("受注"){/gen_tr}</a></li>
                        <li><a href="index.php?action=Mobile_PartnerOrder_List" data-ajax="false" {if $form.action=='Mobile_PartnerOrder_List' ||  $form.action=='Mobile_PartnerOrder_Detail'}class="ui-btn-active"{/if}>{gen_tr}_g("注文"){/gen_tr}</a></li>
                        <li><a href="index.php?action=Logout" data-ajax="false">{gen_tr}_g("終了"){/gen_tr}</a></li>
                    </ul>
                </div><!-- /navbar -->
            </div>
         </div>
    </body>
</html>
