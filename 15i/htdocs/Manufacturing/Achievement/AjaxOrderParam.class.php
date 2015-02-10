<?php

class Manufacturing_Achievement_AjaxOrderParam extends Base_AjaxBase
{

    // order_detail_id を受け取り、各種情報を返す

    function _execute(&$form)
    {
        global $gen_db;

        if (!isset($form['order_detail_id']) || !is_numeric(@$form['order_detail_id'])) {
            return;
        }

        $query = "
        select
            item_code
            ,item_name
            ,seiban
            ,order_detail_quantity
            ,achievement_quantity
            ,classification
            ,default_location_id_3
            ,coalesce(T1.remarks, T2.remarks) as remarks
        from
            order_detail
            left join (
                select
                    order_detail_id
                    ,SUM(achievement_quantity) as achievement_quantity
                    ,MAX(remarks) as remarks
                from
                    achievement
                where
                    middle_process is null
                group by
                    order_detail_id
                ) as T1
                on order_detail.order_detail_id = T1.order_detail_id
            left join (select order_header_id, classification, remarks_header as remarks from order_header) as T2 on order_detail.order_header_id = T2.order_header_id
            left join (select item_id, default_location_id_3 from item_master) as t3 on order_detail.item_id = t3.item_id
        where
            order_detail.order_detail_id = '{$form['order_detail_id']}'
        ";
        $data = $gen_db->queryOneRowObject($query);

        if (!$data || $data == null)
            return;

        // 在庫製番の決定
        $stockSeiban = Logic_Seiban::getStockSeiban($data->seiban);

        // 前工程の製造数を取得。ag.cgi?page=ProjectDocView&pid=1574&did=208745
        //  前工程とは、完了した工程のうち、製造日/登録日時が最後のもの。
        //  必ずしも品目マスタの工程の登録順ではない。
        $processId = false;
        if (isset($form['achievement_id']) && Gen_String::isNumeric($form['achievement_id'])) {
            $processId = $gen_db->queryOneValue("select process_id from achievement where achievement_id = '{$form['achievement_id']}'");
        }
        $beforeQty = Logic_Achievement::getBeforeProcessAchievementQuantity($form['order_detail_id'], $processId);
        if (!$beforeQty) {
            $beforeQty = $data->order_detail_quantity;
        }

        // データの準備
        $obj = array(
            'item_code' => $data->item_code,
            'item_name' => $data->item_name,
            'order_seiban' => $data->seiban,
            'stock_seiban' => $stockSeiban,
            'order_detail_quantity' => $data->order_detail_quantity,
            // 前工程の製造数を計画数とする。
            //    13iまでは単純にオーダー数を各工程の計画数に、また「オーダー数 - その工程での製造済数」をデフォルト製造数に
            //    していた。しかし、製造過程で仕損などがあった場合、それ以降の工程では計画数を減らすべきという考えに基づいて、
            //    前工程の製造数を計画数とするように変更した。
            //    「前工程」は、品目マスタの工程順によるものではない。詳細は「前工程の製造数を取得」部分のコメントを参照。
            //    ag.cgi?page=ProjectDocView&pid=1574&did=208745 
            'plan_quantity' => $beforeQty,
            'remained_quantity' => ($beforeQty - $data->achievement_quantity),
            'remarks' => $data->remarks,
            'classification' => $data->classification,
            'default_location_id_3' => $data->default_location_id_3,
        );
        
        // オーダーに関連付けられた工程のリスト　（id;工程名;残数）
        $query = "
        select
            order_process.process_id
            ,coalesce(machining_sequence,0) + 1 as machining_sequence_no
            ,process_name
            /*  工程の製造残（=デフォルト製造数）。
                13iまでは単純に「オーダー数 - この工程での製造済数」をデフォルト製造数としていたが、
                製造過程で仕損などがあった場合、それ以降の工程では数量を減らすべきという考えに基づいて変更した。
                ag.cgi?page=ProjectDocView&pid=1574&did=202110 
            */
            ,{$beforeQty} - coalesce(ach_qty,0) as remained_qty
            ,coalesce(subcontract_partner_id,0) as subcontract_partner_id 
        from
            order_detail
            left join order_process on order_detail.order_detail_id = order_process.order_detail_id
            left join process_master on order_process.process_id = process_master.process_id
            left join (
                select
                    order_detail_id
                    ,process_id
                    ,sum(achievement_quantity) as ach_qty
                from
                    achievement
                group by
                    order_detail_id
                    ,process_id
                ) as t1
                on order_process.order_detail_id = t1.order_detail_id
                and order_process.process_id = t1.process_id
        where
            order_detail.order_detail_id = '{$form['order_detail_id']}'
            /* 完了工程は表示しない */
            /* ただし完了工程の修正モードの場合はその工程を表示する */
            and (not coalesce(order_process.process_completed, false)
            " . (isset($form['achievement_id']) && Gen_String::isNumeric($form['achievement_id']) ? 
                    " or order_process.process_id = (select process_id from achievement where achievement_id = '{$form['achievement_id']}')" : "") . ")
        order by
            machining_sequence
        ";

        $arr = $gen_db->getArray($query);

        $str = "";
        $isFinSub = 0;
        if (is_array($arr)) {
            foreach ($arr as $row) {
                // 外製工程は表示しない（外製工程は実績登録不可）
                if ($row['subcontract_partner_id'] == "0") {
                    $str .=
                        $row['process_id'] . ";" .
                        $row['machining_sequence_no'] . ";" .
                        $row['process_name'] . ";" .
                        $row['remained_qty'] . ";";
                }
            }
            if ($str != "") {
                $str = substr($str, 0, strlen($str) - 1);    // 最後のセミコロンを取る
            }
            // 最終工程が外製工程かどうか
            $isFinSub = ($row['subcontract_partner_id'] == "0" ? 0 : 1);
        }

        $obj['isFinSub'] = $isFinSub;
        $obj['str'] = $str;
        
        return $obj;
    }

}