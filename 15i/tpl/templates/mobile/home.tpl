{include file="mobile/common_header.tpl"}

<ul data-role="listview" data-inset="true">
    <li data-role="list-divider">{gen_tr}_g("トークボード"){/gen_tr}</li>
    <li><a href="index.php?action=Mobile_Chat" data-ajax="false"><img src="img/menu/mobile/stock/img401.png" class="ui-li-icon" style="max-height:40px;max-width:40px"/><h3>　{gen_tr}_g("トークボード"){/gen_tr}</h3></a></li>
    <li data-role="list-divider">{gen_tr}_g("資材管理"){/gen_tr}</li>
    <li><a href="index.php?action=Mobile_Stock_List" data-ajax="false"><img src="img/menu/mobile/stock/img401.png" class="ui-li-icon" style="max-height:40px;max-width:40px"/><h3>　{gen_tr}_g("在庫リスト"){/gen_tr}</h3></a></li>
    {*
    <li><a href="index.php?action=Mobile_StockInput_List" data-ajax="false"><img src="img/menu/mobile/stock/img404.png" class="ui-li-icon" style="max-height:40px;max-width:40px"/><h3>　{gen_tr}_g("棚卸リスト"){/gen_tr}</h3></a></li>
    <li><a href="index.php?action=Mobile_StockInput_Edit" data-ajax="false"><img src="img/menu/mobile/stock/img404.png" class="ui-li-icon" style="max-height:40px;max-width:40px"/><h3>　{gen_tr}_g("棚卸登録"){/gen_tr}</h3></a></li>
    *}
    
    <li data-role="list-divider">{gen_tr}_g("販売管理"){/gen_tr}</li>
    <li><a href="index.php?action=Mobile_Received_List" data-ajax="false"><img src="img/menu/mobile/delivery/img103.png" class="ui-li-icon" style="max-height:40px;max-width:40px"/><h3>　{gen_tr}_g("受注リスト"){/gen_tr}</h3></a></li>
    <li><a href="index.php?action=Mobile_Received_Summary&gen_search_key=0&gen_search_period=0" data-ajax="false"><img src="img/menu/mobile/report/img501.png" class="ui-li-icon" style="max-height:40px;max-width:40px"/><h3>　{gen_tr}_g("ﾗﾝｷﾝｸﾞ（得意先・当月）"){/gen_tr}</h3></a></li>
    <li><a href="index.php?action=Mobile_Received_Summary&gen_search_key=0&gen_search_period=1" data-ajax="false"><img src="img/menu/mobile/report/img501.png" class="ui-li-icon" style="max-height:40px;max-width:40px"/><h3>　{gen_tr}_g("ﾗﾝｷﾝｸﾞ（得意先・先月）"){/gen_tr}</h3></a></li>
    <li><a href="index.php?action=Mobile_Received_Summary&gen_search_key=0&gen_search_period=3" data-ajax="false"><img src="img/menu/mobile/report/img501.png" class="ui-li-icon" style="max-height:40px;max-width:40px"/><h3>　{gen_tr}_g("ﾗﾝｷﾝｸﾞ（得意先・今年）"){/gen_tr}</h3></a></li>
    <li><a href="index.php?action=Mobile_Received_Summary&gen_search_key=1&gen_search_period=0" data-ajax="false"><img src="img/menu/mobile/report/img501.png" class="ui-li-icon" style="max-height:40px;max-width:40px"/><h3>　{gen_tr}_g("ﾗﾝｷﾝｸﾞ（品目・当月）"){/gen_tr}</h3></a></li>
    <li><a href="index.php?action=Mobile_Received_Summary&gen_search_key=1&gen_search_period=1" data-ajax="false"><img src="img/menu/mobile/report/img501.png" class="ui-li-icon" style="max-height:40px;max-width:40px"/><h3>　{gen_tr}_g("ﾗﾝｷﾝｸﾞ（品目・先月）"){/gen_tr}</h3></a></li>
    <li><a href="index.php?action=Mobile_Received_Summary&gen_search_key=1&gen_search_period=3" data-ajax="false"><img src="img/menu/mobile/report/img501.png" class="ui-li-icon" style="max-height:40px;max-width:40px"/><h3>　{gen_tr}_g("ﾗﾝｷﾝｸﾞ（品目・今年）"){/gen_tr}</h3></a></li>

    <li data-role="list-divider">{gen_tr}_g("生産管理"){/gen_tr}</li>
    <li><a href="index.php?action=Mobile_Mrp_List" data-ajax="false"><img src="img/menu/mobile/manufacturing/img202.png" class="ui-li-icon" style="max-height:40px;max-width:40px"/><h3>　{gen_tr}_g("所要量計算"){/gen_tr}</h3></a></li>

    <li data-role="list-divider">{gen_tr}_g("購買管理"){/gen_tr}</li>
    <li><a href="index.php?action=Mobile_PartnerOrder_List" data-ajax="false"><img src="img/menu/mobile/partner/img301.png" class="ui-li-icon" style="max-height:40px;max-width:40px"/><h3>　{gen_tr}_g("注文書リスト"){/gen_tr}</h3></a></li>

    <li data-role="list-divider">{gen_tr}_g("マスタ"){/gen_tr}</li>
    <li><a href="index.php?action=Mobile_ItemMaster_List" data-ajax="false"><img src="img/menu/mobile/master/img601.png" class="ui-li-icon" style="max-height:40px;max-width:40px"/><h3>　{gen_tr}_g("品目マスタ"){/gen_tr}</h3></a></li>
    <li><a href="index.php?action=Mobile_CustomerMaster_List&gen_search_classification=0" data-ajax="false"><img src="img/menu/mobile/master/img602.png" class="ui-li-icon" style="max-height:40px;max-width:40px"/><h3>　{gen_tr}_g("取引先マスタ(得意先)"){/gen_tr}</h3></a></li>
    <li><a href="index.php?action=Mobile_CustomerMaster_List&gen_search_classification=1" data-ajax="false"><img src="img/menu/mobile/master/img602.png" class="ui-li-icon" style="max-height:40px;max-width:40px"/><h3>　{gen_tr}_g("取引先マスタ(サプライヤー)"){/gen_tr}</h3></a></li>
</ul>

{include file="mobile/common_footer.tpl"}
