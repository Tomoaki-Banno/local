<?php
class Mobile_Stock_History
{
    function execute(&$form)
    {
        global $gen_db;

        $form['gen_pageTitle'] = _g("受払履歴");
        $form['gen_headerLeftButtonURL'] = "index.php?action=Mobile_Stock_List";
        $form['gen_headerLeftButtonIcon'] = "arrow-l";
        $form['gen_headerLeftButtonText'] = _g("戻る");

        require_once(LOGIC_DIR . "Stock.class.php");

        if (!isset($form['item_id']) || !Gen_String::isNumeric($form['item_id'])) {
            return "action:Mobile_Stock_List";
        }

        // temp_inout に情報取得
        Logic_Stock::createTempInoutTable(
                date('Y-m-1', strtotime('-3 month')),
                date('Y-m-t', strtotime('+3 month')),
                $form['item_id'],
                null,       // $seiban
                null,       // $locationId
                null,       // $lotId
                true,       // $isIncludePartnerStock
                true,       // $isIncludeUsePlan
                false,      // $isUsePlanAllMinus
                true,       // $isCalcStockBySum
                false       // $isDailyStockMode
                );

        $query = "
            select
                *,
                coalesce(date, '1970-01-01') as date_for_orderby     /* 並べ替えで先頭に持ってくるためのダミーデータ */
            from
                temp_inout
                left join (select location_id as locId, location_name from location_master) as t_loc on temp_inout.location_id = t_loc.locId
                left join (select lot_id as lotId, lot_no from lot_master) as t_lot on temp_inout.lot_id = t_lot.lotId
            order by
                id
        ";
        $form['result'] = $gen_db->getArray($query);

        $query = "select item_code, item_name, measure from item_master where item_id = '{$form['item_id']}'";
        $form['itemParam'] = $gen_db->queryOneRowObject($query);

        return 'mobile_stock_history.tpl';
    }
}