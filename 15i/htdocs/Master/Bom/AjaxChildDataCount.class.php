<?php

class Master_Bom_AjaxChildDataCount extends Base_AjaxBase
{

    // 構成表マスタのCSV/Excel出力（親品目指定時）の出力件数を取得
    function _execute(&$form)
    {
        global $gen_db;

        if (!isset($form['itemId']) || !is_numeric(@$form['itemId']))
            return;

        if (@$form['isCsv'] == "true") {
            // 画面で逆展開しているときも、CSVは常に正展開で出力する
            Logic_Bom::expandBom($form['itemId'], 1, false, true, false);

            // CSVの場合、temp_bom_expandにある行と、それにぶらさがっている子品目を出力
            $query = "
            select
                count(*)
            from
                temp_bom_expand
                inner join bom_master on temp_bom_expand.item_id = bom_master.item_id
                inner join item_master on temp_bom_expand.item_id = item_master.item_id
                inner join item_master as child_item_master on bom_master.child_item_id = child_item_master.item_id
            ";
        } else {
            Logic_Bom::expandBom($form['itemId'], 1, (@$form['reverse'] == 'true'), true, false);

            // Excelの場合、temp_bom_expandにあるすべての行を出力
            $query = "
            select
                count(*)
            from
                temp_bom_expand
                inner join item_master on temp_bom_expand.item_id = item_master.item_id
            ";
        }

        $count = $gen_db->queryOneValue($query);

        return
            array(
                'dataCount' => $count,
            );
    }

}