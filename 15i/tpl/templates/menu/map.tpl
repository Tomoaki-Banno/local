{include file="common_header.tpl"}

{*************** Javascript ***************}

<script type="text/javascript">
{literal}
$(function() {
    $('#gen_headerGap').css('height','0px');
    if (gen.util.isIE) {
        window.onhelp = function() {
            return false;
        }
    }
}); 
{/literal}
</script>

{*************** CSS ***************}
{literal}
<style TYPE="text/css">
<!--
.map_span_row {
    height: 60px;   /* BOX行と行の間の高さ */
}
.map_label_box {
    width: 114px;   /* ひとつのBOXの幅 - (padding * 2) */
    height: 57px;   /* ひとつのBOXの高さの半分 - (padding) */
    padding: 3px;
    padding-top: 60px;   /* ひとつのBOXの高さの半分 */
}
.map_pointer {
    cursor: pointer;
}
.map_label_box_inner {
    max-height: 50px;   /* ひとつのBOXの高さの半分 - (10px程度) */
    overflow: hidden;
    text-align: center;
    font-size: 13px;
}
.map_label_box_inner2 {
}
.map_span_box {
    width: 60px;    /* BOXとBOXの間の幅 */
    height: 60px;   /* ひとつのBOXの高さの半分 */
}
.map_color_delivery {
    color: #ff8a00;
}
.map_color_manufacturing {
    color: #2cbaac;
}
.map_color_patner {
    color: #1779c3;
}
.map_color_stock {
    color: #b149a3;
}
-->
</style>
{/literal}

{*************** Contents ***************}

<div id="main" align='center' style="background-image: url('img/map_background.png')">

<div style='height:20px'></div>

<table align="center" cellspacing="0" cellpadding="0">
    <tr valign="middle" style="height:20px">
        <td>
            <span style="color: #475966; font-size:16px; font-weight: bold;letter-spacing: 0.5em;">｜{$form.gen_pageTitle|escape}｜</span>
        </td>
    </tr>
</table>

<div style='height:30px'></div>
        
<div style="position: relative; width:840px">
{if $smarty.const.GEN_GRADE=='Si'}
    <img src="img/map_si.png">

    <div style="position: absolute; top:0px; left:0px">
        <table cellspacing="0" cellpadding="0" style="width:100%" border="0">
            <tr>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Manufacturing_Estimate_List'"><div class="map_label_box_inner map_color_delivery">{gen_tr}_g("見積登録"){/gen_tr}</div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box"><div class="map_label_box_inner"></div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box"><div class="map_label_box_inner"></div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box"><div class="map_label_box_inner"></div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box map_pointer map_pointer" onclick="location.href='index.php?action=Partner_Order_List'"><div class="map_label_box_inner map_color_patner">{gen_tr}_g("注文登録"){/gen_tr}</div></td>
            </tr>
            
            <tr class="map_span_row">
                <td></td>
            </tr>
            
            <tr>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Manufacturing_Received_List'"><div class="map_label_box_inner map_color_delivery">{gen_tr}_g("受注登録"){/gen_tr}</div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box"><div class="map_label_box_inner"></div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box"><div class="map_label_box_inner"></div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box"><div class="map_label_box_inner"></div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Partner_Accepted_List'"><div class="map_label_box_inner map_color_patner">{gen_tr}_g("注文受入登録"){/gen_tr}</div></td>
            </tr>
            
            <tr class="map_span_row">
                <td></td>
            </tr>
            
            <tr>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Delivery_Delivery_List'"><div class="map_label_box_inner map_color_delivery">{gen_tr}_g("納品登録"){/gen_tr}</div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box"><div class="map_label_box_inner"></div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box"><div class="map_label_box_inner"></div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box"><div class="map_label_box_inner"></div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Partner_BuyList_List'"><div class="map_label_box_inner map_color_patner">{gen_tr}_g("買掛リスト"){/gen_tr}</div></td>
            </tr>
            
            <tr class="map_span_row">
                <td></td>
            </tr>
            
            <tr>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Monthly_Bill_List'"><div class="map_label_box_inner map_color_delivery">{gen_tr}_g("請求書発行（締め）"){/gen_tr}</div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box"><div class="map_label_box_inner"></div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box"><div class="map_label_box_inner"></div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box"><div class="map_label_box_inner"></div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Partner_PaymentCalendar_List'"><div class="map_label_box_inner map_color_patner">{gen_tr}_g("支払予定表"){/gen_tr}</div></td>
                
            </tr>
            
            <tr class="map_span_row">
                <td></td>
            </tr>
            
            <tr>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Monthly_Bill_BillList'"><div class="map_label_box_inner map_color_delivery">{gen_tr}_g("請求書リスト"){/gen_tr}</div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Stock_SeibanChange_List'"><div class="map_label_box_inner map_color_stock">{gen_tr}_g("製番引当登録"){/gen_tr}</div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Stock_Stocklist_List'"><div class="map_label_box_inner map_color_stock">{gen_tr}_g("在庫リスト"){/gen_tr}</div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box"><div class="map_label_box_inner"></div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Partner_Payment_List'"><div class="map_label_box_inner map_color_patner">{gen_tr}_g("支払登録"){/gen_tr}</div></td>
            </tr>
            
            <tr class="map_span_row">
                <td></td>
            </tr>
            
            <tr>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Delivery_ReceivableCalendar_List'"><div class="map_label_box_inner map_color_delivery">{gen_tr}_g("回収予定表"){/gen_tr}</div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Monthly_StockInput_List'"><div class="map_label_box_inner map_color_stock">{gen_tr}_g("棚卸登録"){/gen_tr}</div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Stock_StockFlow_List'"><div class="map_label_box_inner map_color_stock">{gen_tr}_g("在庫推移リスト"){/gen_tr}</div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Stock_Inout_List&classification=out'"><div class="map_label_box_inner map_color_stock">{gen_tr}_g("出庫登録"){/gen_tr}</div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Partner_PaymentList_List'"><div class="map_label_box_inner map_color_patner">{gen_tr}_g("買掛残高表"){/gen_tr}</div></td>
            </tr>
            
            <tr class="map_span_row">
                <td></td>
            </tr>
            
            <tr>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Delivery_PayingIn_List'"><div class="map_label_box_inner map_color_delivery">{gen_tr}_g("入金登録"){/gen_tr}</div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Stock_Assessment_List'"><div class="map_label_box_inner map_color_stock">{gen_tr}_g("在庫評価単価更新"){/gen_tr}</div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Stock_StockHistory_List'"><div class="map_label_box_inner map_color_stock">{gen_tr}_g("受払履歴"){/gen_tr}</div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Stock_Move_List'"><div class="map_label_box_inner map_color_stock">{gen_tr}_g("ロケーション間移動登録"){/gen_tr}</div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box"><div class="map_label_box_inner"></div></td>
            </tr>
            
            <tr class="map_span_row">
                <td></td>
            </tr>
            
            <tr>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Delivery_ReceivableList_List'"><div class="map_label_box_inner map_color_delivery">{gen_tr}_g("売掛残高表"){/gen_tr}</div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box"><div class="map_label_box_inner"></div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box"><div class="map_label_box_inner"></div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box"><div class="map_label_box_inner"></div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box"><div class="map_label_box_inner"></div></td>
            </tr>
            <tr class="map_span_row">
                <td></td>
            </tr>
            
            <tr>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Manufacturing_BaseCost_List'"><div class="map_label_box_inner map_color_delivery">{gen_tr}_g("原価リスト"){/gen_tr}</div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box"><div class="map_label_box_inner"></div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box"><div class="map_label_box_inner"></div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box"><div class="map_label_box_inner"></div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box"><div class="map_label_box_inner"></div></td>
            </tr>
        </table>
    </div>

{else}
    <img src="img/map_mi.png">

    <div style="position: absolute; top:0px; left:0px">
        <table cellspacing="0" cellpadding="0" style="width:100%" border="0">
            <tr>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Manufacturing_Estimate_List'"><div class="map_label_box_inner map_color_delivery">{gen_tr}_g("見積登録"){/gen_tr}</div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Manufacturing_Plan_List'"><div class="map_label_box_inner map_color_manufacturing">{gen_tr}_g("計画登録"){/gen_tr}</div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Partner_Order_List'"><div class="map_label_box_inner map_color_patner">{gen_tr}_g("注文登録"){/gen_tr}</div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Partner_Accepted_List'"><div class="map_label_box_inner map_color_patner">{gen_tr}_g("注文受入登録"){/gen_tr}</div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Partner_BuyList_List'"><div class="map_label_box_inner map_color_patner">{gen_tr}_g("買掛リスト"){/gen_tr}</div></td>
            </tr>
            
            <tr class="map_span_row">
                <td></td>
            </tr>
            
            <tr>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Manufacturing_Received_List'"><div class="map_label_box_inner map_color_delivery">{gen_tr}_g("受注登録"){/gen_tr}</div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Manufacturing_Mrp_List'"><div class="map_label_box_inner map_color_manufacturing">{gen_tr}_g("所要量計算"){/gen_tr}</div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Partner_Subcontract_List'"><div class="map_label_box_inner map_color_patner">{gen_tr}_g("外製指示登録"){/gen_tr}</div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Partner_SubcontractAccepted_List'"><div class="map_label_box_inner map_color_patner">{gen_tr}_g("外製受入登録"){/gen_tr}</div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Partner_PaymentCalendar_List'"><div class="map_label_box_inner map_color_patner">{gen_tr}_g("支払予定表"){/gen_tr}</div></td>
            </tr>
            
            <tr class="map_span_row">
                <td></td>
            </tr>
            
            <tr>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Progress_SeibanProgress_List'"><div class="map_label_box_inner map_color_delivery">{gen_tr}_g("受注別進捗"){/gen_tr}</div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Manufacturing_Mrp_Analyze'"><div class="map_label_box_inner map_color_manufacturing">{gen_tr}_g("所要量計算分析"){/gen_tr}</div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Manufacturing_Order_List'"><div class="map_label_box_inner map_color_manufacturing">{gen_tr}_g("製造指示登録"){/gen_tr}</div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Stock_Inout_List&classification=payout'"><div class="map_label_box_inner map_color_patner">{gen_tr}_g("支給登録"){/gen_tr}</div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Partner_Payment_List'"><div class="map_label_box_inner map_color_patner">{gen_tr}_g("支払登録"){/gen_tr}</div></td>
            </tr>
            
            <tr class="map_span_row">
                <td></td>
            </tr>
            
            <tr>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Delivery_Delivery_List'"><div class="map_label_box_inner map_color_delivery">{gen_tr}_g("納品登録"){/gen_tr}</div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box"><div class="map_label_box_inner"></div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Manufacturing_Achievement_List'"><div class="map_label_box_inner map_color_manufacturing">{gen_tr}_g("実績登録"){/gen_tr}</div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Progress_ProcessLoad_List'"><div class="map_label_box_inner map_color_manufacturing">{gen_tr}_g("工程別負荷"){/gen_tr}</div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Partner_PaymentList_List'"><div class="map_label_box_inner map_color_patner">{gen_tr}_g("買掛残高表"){/gen_tr}</div></td>
            </tr>
            
            <tr class="map_span_row">
                <td></td>
            </tr>
            
            <tr>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Monthly_Bill_List'"><div class="map_label_box_inner map_color_delivery">{gen_tr}_g("請求書発行（締め）"){/gen_tr}</div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box"><div class="map_label_box_inner"></div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box"><div class="map_label_box_inner"></div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Progress_OrderProgress_List'"><div class="map_label_box_inner map_color_manufacturing">{gen_tr}_g("オーダー別進捗"){/gen_tr}</div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box"><div class="map_label_box_inner"></div></td>
            </tr>
            
            <tr class="map_span_row">
                <td></td>
            </tr>
            
            <tr>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Monthly_Bill_BillList'"><div class="map_label_box_inner map_color_delivery">{gen_tr}_g("請求書リスト"){/gen_tr}</div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box"><div class="map_label_box_inner"></div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Stock_SeibanChange_List'"><div class="map_label_box_inner map_color_stock">{gen_tr}_g("製番引当登録"){/gen_tr}</div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Stock_Stocklist_List'"><div class="map_label_box_inner map_color_stock">{gen_tr}_g("在庫リスト"){/gen_tr}</div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Stock_Inout_List&classification=in'"><div class="map_label_box_inner map_color_stock">{gen_tr}_g("入庫登録"){/gen_tr}</div></td>
            </tr>
            
            <tr class="map_span_row">
                <td></td>
            </tr>
            
            <tr>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Delivery_ReceivableCalendar_List'"><div class="map_label_box_inner map_color_delivery">{gen_tr}_g("回収予定表"){/gen_tr}</div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box"><div class="map_label_box_inner"></div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Monthly_StockInput_List'"><div class="map_label_box_inner map_color_stock">{gen_tr}_g("棚卸登録"){/gen_tr}</div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Stock_StockFlow_List'"><div class="map_label_box_inner map_color_stock">{gen_tr}_g("在庫推移リスト"){/gen_tr}</div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Stock_Inout_List&classification=out'"><div class="map_label_box_inner map_color_stock">{gen_tr}_g("出庫登録"){/gen_tr}</div></td>
            </tr>
            
            <tr class="map_span_row">
                <td></td>
            </tr>
            
            <tr>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Delivery_PayingIn_List'"><div class="map_label_box_inner map_color_delivery">{gen_tr}_g("入金登録"){/gen_tr}</div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box"><div class="map_label_box_inner"></div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Stock_Assessment_List'"><div class="map_label_box_inner map_color_stock">{gen_tr}_g("在庫評価単価更新"){/gen_tr}</div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Stock_StockHistory_List'"><div class="map_label_box_inner map_color_stock">{gen_tr}_g("受払履歴"){/gen_tr}</div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Stock_Move_List'"><div class="map_label_box_inner map_color_stock">{gen_tr}_g("ロケーション間移動登録"){/gen_tr}</div></td>
            </tr>
            
            <tr class="map_span_row">
                <td></td>
            </tr>
            
            <tr>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Delivery_ReceivableList_List'"><div class="map_label_box_inner map_color_delivery">{gen_tr}_g("売掛残高表"){/gen_tr}</div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box"><div class="map_label_box_inner"></div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box"><div class="map_label_box_inner"></div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box"><div class="map_label_box_inner"></div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box"><div class="map_label_box_inner"></div></td>
            </tr>
            
            <tr class="map_span_row">
                <td></td>
            </tr>
            
            <tr>
                <td class="map_label_box map_pointer" onclick="location.href='index.php?action=Manufacturing_BaseCost_List'"><div class="map_label_box_inner map_color_delivery">{gen_tr}_g("原価リスト"){/gen_tr}</div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box"><div class="map_label_box_inner"></div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box"><div class="map_label_box_inner"></div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box"><div class="map_label_box_inner"></div></td>
                <td class="map_span_box"></td>
                <td class="map_label_box"><div class="map_label_box_inner"></div></td>
            </tr>
        </table>
    </div>
{/if}                
</div>    

{include file="common_footer.tpl"}