<?php

class Manufacturing_Order_BulkDelete extends Base_BulkDeleteBase
{

    function setParam(&$form)
    {
        $this->listAction = 'Manufacturing_Order_List';
        $this->deleteAfterAction = 'Manufacturing_Order_List';
    }

    function _validate($validator, &$form)
    {
        global $gen_db;

        // データロック対象外
        $query = "select unlock_object_2 from company_master";
        $unlock = $gen_db->queryOneValue($query);

        foreach ($form as $name => $value) {
            if (substr($name, 0, 7) == "delete_") {
                // id配列を取得
                $id = $gen_db->quoteParam(substr($name, 7, strlen($name) - 7));
                if (!is_numeric($id)) {
                    continue;
                } else {
                    $this->deleteIdArray[] = $id;
                }

                // すでに実績(完成)が登録されている場合は削除できない。
                // 本来は実績を連動削除すればいいのかもしれないが、付随データの処理や
                // 現在処理月チェックなどいろいろ面倒なので、削除禁止とした。
                if (Logic_Achievement::existAchievement($id)) {
                    $query = "select order_no from order_detail inner join order_header on order_detail.order_header_id = order_header.order_header_id where order_detail.order_header_id = '{$id}'";
                    $no = $gen_db->queryOneValue($query);
                    $validator->raiseError(sprintf(_g("オーダー番号 %s の製造指示に対し、製造実績が登録されているため削除できません。"), $no));
                    break;
                }

                // 同様に、外製工程受入が登録されている場合も削除できない。
                $query = "
                select
                    *
                from
                    order_detail
                    inner join order_detail as t_sub_order on order_detail.order_no = t_sub_order.subcontract_parent_order_no
                    inner join accepted on t_sub_order.order_detail_id = accepted.order_detail_id
                where
                    order_detail.order_header_id = '{$id}'
                ";
                if ($gen_db->existRecord($query)) {
                    $query = "select order_no from order_detail inner join order_header on order_detail.order_header_id = order_header.order_header_id where order_detail.order_header_id = '{$id}'";
                    $no = $gen_db->queryOneValue($query);
                    $validator->raiseError(sprintf(_g("オーダー番号 %s の製造指示に対し、外製工程の受入が登録されているため削除できません。"), $no));
                }

                // ロケ間移動で参照されいている場合は削除できない
                $query = "select move_id from location_move inner join order_detail on location_move.order_detail_id = order_detail.order_detail_id
                            where order_header_id = '{$id}'";
                if ($gen_db->existRecord($query)) {
                    $query = "select order_no from order_detail inner join order_header on order_detail.order_header_id = order_header.order_header_id where order_detail.order_header_id= '{$id}'";
                    $no = $gen_db->queryOneValue($query);
                    $validator->raiseError(sprintf(_g("オーダー番号 %s の製造指示が、ロケーション間移動で参照されているため削除できません。"), $no));
                }

                // データロック対象外でなければチェック
                if ($unlock != "1") {
                    // ロック年月チェック
                    $query = "select order_date from order_header where order_header_id = '{$id}'";
                    $date = $gen_db->queryOneValue($query);
                    $validator->notLockDateForDelete($date, _g("オーダー日"));
                    if ($validator->hasError())
                        break;
                }
            }
        }
        if (count($this->deleteIdArray) == 0)
            $validator->raiseError(_g("削除するデータがありません。"));
    }

    function setLogParam($form)
    {
        global $gen_db;

        // 削除件数
        $count = count($this->deleteIdArray);

        // ログ用にオーダー番号を取得
        $idCsv = join(',', $this->deleteIdArray);
        $query = "
        select
            order_no as col1
            ,order_no || ' [' || item_code || ']' as col2
        from
            order_detail
        where
            order_header_id in ({$idCsv})
        order by
            order_no
        ";
        $res = $gen_db->getArray($query);
        foreach ($res as $row) {
            $this->numberArray[] = $row['col1'];
            $this->numberDetailArray[] = $row['col2'];
        }

        $numberCsv = join(', ', $this->numberArray);
        $numberDetailCsv = join(', ', $this->numberDetailArray);
        $numberCsvForMsg = $this->_makeNumberCsvForMsg($numberCsv);

        // メッセージ
        $this->afterDeleteMessage = sprintf(_g("%1\$s件のデータ（%2\$s：%3\$s）を削除しました。"), $count, _g('オーダー番号'), $numberCsvForMsg);

        // データアクセスログ
        $this->log1 = _g("製造指示書");
        $this->log2 = sprintf(_g("%1\$s件（%2\$s：%3\$s）"), $count, _g('オーダー番号') . ' [' . _g('品目コード') . ']', $numberDetailCsv);
    }

    function _delete(&$form)
    {
        // 削除処理
        foreach ($this->deleteIdArray as $id) {
            Logic_Order::deleteOrder($id);
        }
    }

}