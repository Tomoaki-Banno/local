<?php

class Partner_Order_BulkDelete extends Base_BulkDeleteBase
{

    function setParam(&$form)
    {
        $this->listAction = 'Partner_Order_List';
        if (isset($form['returnHeaderId']) && is_numeric($form['returnHeaderId'])) {
            $act = "Edit";
            $form['order_header_id'] = $form['returnHeaderId'];
            $form['gen_updated'] = "true";
        } else {
            $act = "List";
        }
        $this->deleteAfterAction = 'Partner_Order_' . $act;
    }

    function _validate($validator, &$form)
    {
        global $gen_db;

        $this->isDetailMode = isset($form['detail']) && $form['detail'] == "true";

        // データロック対象外
        $query = "select unlock_object_3 from company_master";
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

                // データロック対象外でなければチェック
                if ($unlock != "1") {
                    // ロック年月チェック
                    if ($this->isDetailMode) {
                        $date = Logic_Order::getOrderDateByDetailId($id);
                    } else {
                        $date = Logic_Order::getOrderDateByTranId($id);
                    }
                    $validator->notBuyLockDateForDelete($date, _g("発注日"));
                    if ($validator->hasError())
                        break;
                }

                // 受入状況チェック
                // 本来は受入を連動削除すればいいのかもしれないが、付随データの処理や
                // 現在処理月チェックなどいろいろ面倒なので、削除禁止とした。
                if ($this->isDetailMode) {
                    $isErr = Logic_Accepted::hasAcceptedByOrderDetailId($id);
                    $no = Logic_Order::getOrderIdForUserByTranId($id);
                } else {
                    $isErr = Logic_Accepted::hasAcceptedByOrderHeaderId($id);
                    $no = Logic_Order::getOrderIdForUserByDetailId($id);
                }
                if ($isErr) {
                    $validator->raiseError(sprintf(_g("注文書番号 %s に対し、すでに受入が登録されているため削除できません。"), $no));
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

        // ログ用に注文書番号・オーダー番号を取得
        $idCsv = join(',', $this->deleteIdArray);
        if ($this->isDetailMode) {
            $query = "
            select
                order_no as col1
                ,order_no || ' [' || item_code || ']' as col2
            from
                order_detail
            where
                order_detail_id in ({$idCsv})
            order by
                order_no
            ";
        } else {
            $query = "select order_id_for_user as col1 from order_header where order_header_id in ({$idCsv}) order by order_id_for_user";
        }
        $res = $gen_db->getArray($query);
        foreach ($res as $row) {
            $this->numberArray[] = $row['col1'];
            if ($this->isDetailMode)
                $this->numberDetailArray[] = $row['col2'];
        }

        $numberCsv = join(', ', array_unique($this->numberArray));
        $numberDetailCsv = join(', ', $this->numberDetailArray);
        $numberCsvForMsg = $this->_makeNumberCsvForMsg($numberCsv);

        // メッセージ
        $this->afterDeleteMessage = sprintf(_g("%1\$s件のデータ（%2\$s：%3\$s）を削除しました。"), $count, ($this->isDetailMode ? _g('オーダー番号') : _g('注文書番号')), $numberCsvForMsg);

        // データアクセスログ
        $this->log1 = _g("注文");
        $this->log2 = sprintf(_g("%1\$s件（%2\$s：%3\$s）"), $count, ($this->isDetailMode ? _g('オーダー番号') . ' [' . _g('品目コード') . ']' : _g('注文書番号')), ($this->isDetailMode ? $numberDetailCsv : $numberCsv));
    }

    function _delete(&$form)
    {
        global $gen_db;

        // 削除処理
        foreach ($this->deleteIdArray as $id) {
            if ($this->isDetailMode) {
                // 行番号の振りなおし
                //　削除行より後の行の行番号を-1する
                $query = "
                update
                    order_detail
                set
                    line_no = order_detail.line_no - 1
                from
                    order_detail as t2
                where
                    order_detail.order_header_id = t2.order_header_id
                    and t2.order_detail_id = '{$id}'
                    and order_detail.line_no > t2.line_no
                ";
                $gen_db->query($query);

                // 削除
                $headerId = Logic_Order::deleteOrderDetail($id);

                $query = "select order_header_id from order_header where order_header_id = {$headerId}";
                if (!$gen_db->existRecord($query)) {
                    $this->recordIdArray[] = $headerId;
                }
            } else {
                Logic_Order::deleteOrder($id);
            }
        }
    }

}