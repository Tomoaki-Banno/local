<?php

class Delivery_Delivery_BulkInspection
{

    function validate(&$validator, &$form)
    {
        if ($form['inspection_date'] != "") {
            $validator->salesLockDateOrLater('inspection_date', _g("検収日"));
        }

        foreach ($form as $name => $value) {
            if (substr($name, 0, 8) == "chk_ins_") {
                // id配列を取得
                $id = substr($name, 8, strlen($name) - 8);
                if (!is_numeric($id)) {
                    continue;
                } else {
                    $isDetail = (isset($form['detail']) && $form['detail'] == "true" ? true : false);
                    $arr = Logic_Delivery::getDeliveryDataById($id, $isDetail);
                    $checkDate = $arr[0]['inspection_date'];
                    if (Gen_String::isDateString($checkDate)) {
                        $validator->notSalesLockDateForRegist($checkDate, _g("検収日"));
                        if ($validator->hasError())
                            break;
                    }
                }
            }
        }

        $form['gen_restore_search_condition'] = 'true';
        return 'action:Delivery_Delivery_List';        // if error
    }

    function execute(&$form)
    {
        global $gen_db;

        // 対象データを配列に列挙する
        $idArr = array();
        foreach ($form as $name => $value) {
            if (substr($name, 0, 8) == "chk_ins_") {
                $idArr[] = substr($name, 8, strlen($name) - 8);
            }
        }

        // 日付取得
        $inspectionDate = $form['inspection_date'];

        // アクセス権チェック
        if ($form['gen_readonly'] == 'true') {
            return "action:Delivery_Delivery_List";
        }

        // Where
        $where = (isset($form['detail']) && $form['detail'] == "true" ?
                        "delivery_header.delivery_header_id in (select delivery_header_id from delivery_detail where delivery_detail_id in (" . join(",", $idArr) . "))" :
                        "delivery_header.delivery_header_id in (" . join(",", $idArr) . ")"
                );

        // 最終請求締日以前の日付での検収登録は行えないようにする。
        //　　本来はこれ以外にも、請求済みの納品の検収日を変更・削除できないようにチェックする必要がある。
        //　　しかし、リストにおいて請求済みレコードに検収チェックボックスを表示しないようになっているので、
        //　　大きな問題はないだろうと判断しチェックを省略している。本来はサーバー側でもチェックすべきだが・・。
        $isOK = true;
        if ($inspectionDate != '') {
            $query = "
            select
                bill_header.customer_id
                ,max(customer_master.customer_name) as customer_name
                ,max(close_date) as close_date
            from
                bill_header
                inner join customer_master on bill_header.customer_id = customer_master.customer_id
            where
                bill_header.customer_id in (
                select
                    coalesce(customer_master.bill_customer_id, received_header.customer_id)
                from
                    delivery_header
                    inner join delivery_detail on delivery_header.delivery_header_id = delivery_detail.delivery_header_id
                    inner join received_detail on delivery_detail.received_detail_id = received_detail.received_detail_id
                    inner join received_header on received_detail.received_header_id = received_header.received_header_id
                    inner join customer_master on received_header.customer_id = customer_master.customer_id
                where
                    {$where}
                    /* 締め請求の時のみ */
                    and customer_master.bill_pattern <> 2
                )
            group by
                bill_header.customer_id
            having
                max(close_date) >= '{$inspectionDate}'::date
            ";
            $obj = $gen_db->queryOneRowObject($query);

            if ($obj) {
                $form['gen_message_noEscape'] = "<font color='red'>" . sprintf(_g('得意先「%1$s」に対して %2$s 付の請求書が発行されています。検収日をそれ以前の日付にすることはできません。'), h($obj->customer_name), h($obj->close_date)) . "</font>";
                $isOK = false;
            }
        }

        if ($isOK) {
            // 検収日の更新
            Logic_Delivery::updateInspectionDate($where, $inspectionDate);

            $form['gen_afterEntryMessage'] = _g("一括検収登録を実行しました。");

            // データアクセスログ
            Gen_Log::dataAccessLog(_g("納品"), _g("一括検収"), _g("[検収日] ") . ($inspectionDate != "" ? date('Y-m-d', strtotime($inspectionDate)) : _g("削除")));
        }

        $form['gen_restore_search_condition'] = 'true';
        return 'action:Delivery_Delivery_List';
    }

}
