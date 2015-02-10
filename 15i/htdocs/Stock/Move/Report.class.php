<?php

class Stock_Move_Report extends Base_PDFReportBase
{

    protected function _getQuery(&$form)
    {
        // 印刷対象データを配列に列挙する
        $idArr = array();
        foreach ($form as $name => $value) {
            if (substr($name, 0, 6) == "check_") {
                $idArr[] = substr($name, 6, strlen($name) - 6);
            }
        }

        // 印刷フラグ更新用
        $form['idArr'] = $idArr;

        // 印刷対象データの取得
        $query = "
            select
                -- headers

                1 as dummy
            from
                location_move
                left join item_master on location_move.item_id = item_master.item_id
                left join (select location_id as lid, location_code as source_location_code, location_name as source_location_name from location_master) as t_loc1 on location_move.source_location_id = t_loc1.lid
                left join (select location_id as lid, location_code as dist_location_code, location_name as dist_location_name from location_master) as t_loc2 on location_move.dist_location_id = t_loc2.lid
                left join order_detail as order_detail_manufacturing on location_move.order_detail_id = order_detail_manufacturing.order_detail_id
                left join order_header as order_header_manufacturing on order_detail_manufacturing.order_header_id = order_header_manufacturing.order_header_id
            where
                location_move.move_id in (" . join(",", $idArr) . ")
            order by
                -- テンプレート内の指定が優先されることに注意
                location_move.move_date
                ,item_master.item_code
                ,t_loc1.source_location_code
        ";

        return $query;
    }

    // テンプレート情報
    protected function _getReportParam()
    {
        $info = array();
        $info['reportTitle'] = _g("在庫移動表");
        $info['report'] = "StockMove";
        $info['pageKeyColumn'] = "dummy";

        // SQLのfromで指定されているテーブルのリスト。
        // ここで指定されたテーブルのカラムはSQL selectとタグリストに自動追加される。
        $info['tables'] = array(
            array("location_move", true, ""),
            array("item_master", true, ""),
            array("order_header_manufacturing", true, ""),
            array("order_detail_manufacturing", true, ""),
        );

        // タグリスト（この帳票固有のもの）
        $info['tagList'] = array(
        );

        return $info;
    }

    // 印刷フラグの更新
    protected function _setPrintFlag($form)
    {
        // 帳票発行済みフラグ
        Logic_Move::setMovePrintedFlag($form['idArr'], true);
        return;
    }

}
