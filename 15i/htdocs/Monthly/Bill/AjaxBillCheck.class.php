<?php

class Monthly_Bill_AjaxBillCheck extends Base_AjaxBase
{

    // 取引先id毎に締日のチェックを実施する。

    function _execute(&$form)
    {
        global $gen_db;

        // 日付チェック
        if (!isset($form['close_date']) || !Gen_String::isDateString($form['close_date'])) {
            return
                array(
                    'status' => 'failure',
                    'msg' => printf(_g("%sが正しくありません。"), _g('請求締日'))
                );
        } else {
            // データロック日付のチェック。
            $lockDate = Logic_SystemDate::getStartDate();              // データロック
            $salesLockDate = Logic_SystemDate::getSalesLockDate();    // 販売ロック
            if ($lockDate < $salesLockDate)
                $lockDate = $salesLockDate;
            if (strtotime($form['close_date']) < $lockDate) {
                return
                    array(
                        'status' => 'failure',
                        'msg' => sprintf(_g("%sに指定された日付はデータがロックされています。"), _g('請求締日'))
                    );
            } else {
                // 対象データを配列に列挙する
                $idArr = array();
                foreach ($form as $name => $value) {
                    if (substr($name, 0, 6) == "check_") {
                        $idArr[] = $value;
                    }
                }

                // 得意先チェック
                if (count($idArr) == 0) {
                    return
                        array(
                            'status' => 'failure',
                            'msg' => _g("請求書発行する得意先が選択されていません。")
                        );
                }

                // 各得意先の請求条件の混在チェック
                // ******** 最終請求情報の計算（テンポラリテーブル temp_last_close & temp_delivery_base） ********
                Logic_Bill::createTempLastCloseTable($form['close_date'], $idArr);
                $query = "select max(pattern_count) as max_count
                    from (select customer_id, count(customer_id) as pattern_count from temp_delivery_base group by customer_id) as t_base";
                $patternCount = $gen_db->queryOneValue($query);
                if ($patternCount > 1) {
                    return
                        array(
                            'status' => 'failure',
                            'msg' => _g('複数の請求条件が含まれる得意先が存在します。請求締日を変更するか請求条件を変更してください。')
                        );
                }

                // 得意先毎の請求書発行チェック
                foreach ($idArr as $id) {
                    // 請求締日に対する請求書発行のチェック。
                    $query = "
                    select
                        bill_header.customer_id
                        , customer_no
                        , customer_name
                    from
                        bill_header
                        inner join customer_master on bill_header.customer_id = customer_master.customer_id
                    where
                        bill_header.close_date >= '{$form['close_date']}'::date
                        and bill_header.customer_id = '{$id}'
                    ";
                    $data = $gen_db->queryOneRowObject($query);
                    if (isset($data->customer_id) && is_numeric($data->customer_id)) {
                        return
                            array(
                                'status' => 'success',
                                'msg' => _g('指定した締日ですでに発行済みの請求書が存在します。未発行の得意先に対してのみ請求書を発行しますか？')
                            );
                    }
                }
            }
        }

        // 請求パターンのチェック
        $query = "
        select
            count(*)
        from
            (select monthly_limit_date from customer_master
            where customer_id in (" . join(",", $idArr) . ")
            group by monthly_limit_date) as t_date
        ";
        $limitCount = $gen_db->queryOneValue($query);
        if ($limitCount > 1) {
            $obj = array(
                'status' => 'success',
                'msg' => _g('指定された得意先の締日が異なります。指定した締日で請求書を発行しますか？')
            );
        } else {
            $obj = array(
                'status' => 'success',
                'msg' => _g('指定された得意先に対して請求書を発行しますか？')
            );
        }
        
        return $obj;
    }

}