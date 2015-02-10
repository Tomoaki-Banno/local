<?php

class Partner_Subcontract_BulkDelete extends Base_BulkDeleteBase
{

    function setParam(&$form)
    {
        $this->listAction = 'Partner_Subcontract_List';
        $this->deleteAfterAction = 'Partner_Subcontract_List';
    }


    function _validate($validator, &$form)
    {
        global $gen_db;

        $idArr = array();                           // 最終工程チェック用
        $subcontractOrderProcessNoArr = array();    // 最終工程チェック用

        // データロック対象外
        $query = "select unlock_object_4 from company_master";
        $unlock = $gen_db->queryOneValue($query);

        foreach ($form as $name => $value) {
            if (substr($name, 0, 7) == "delete_") {
                // id配列を取得
                $id = $gen_db->quoteParam(substr($name, 7, strlen($name) - 7));
                if (!is_numeric($id)) {
                    continue;
                }
                
                // 他ユーザー・他タブによってすでにこのオーダーが削除済の場合のエラー回避 ag.cgi?page=ProjectDocView&pid=1574&did=217967
                $query = "select 1 from order_header where order_header_id = '{$id}'";
                if (!$gen_db->existRecord($query)) {
                    continue;
                }

                // すでに受入が登録されている場合は削除できない。
                if (Logic_Accepted::hasAcceptedByOrderHeaderId($id)) {
                    $query = "select order_no from order_detail inner join order_header on order_detail.order_header_id = order_header.order_header_id where order_detail.order_header_id = '{$id}'";
                    $no = $gen_db->queryOneValue($query);
                    $validator->raiseError(sprintf(_g("オーダー番号 %s の外製指示書に対し、受入が登録されているため削除できません。"), $no));
                    break;
                }

                // データロック対象外でなければチェック
                if ($unlock != "1") {
                    // ロック年月チェック
                    $query = "select order_date from order_header where order_header_id = '{$id}'";
                    $date = $gen_db->queryOneValue($query);
                    $validator->notLockDateForDelete($date, _g("オーダー日"));
                    if ($validator->hasError()) {
                        break;
                    }
                }

                // 親である製造指示が存在するか
                $query = "select subcontract_order_process_no from order_detail
                    left join order_header on order_detail.order_header_id = order_header.order_header_id
                    where order_detail.order_header_id = '{$id}'";
                $subcontractOrderProcessNo = $gen_db->queryOneValue($query);

                if ($subcontractOrderProcessNo != '') {
                    // 工程が最終工程かどうかを確認
                    $query = "select order_process.order_detail_id, process_id from order_process
                        where order_process_no = '{$subcontractOrderProcessNo}'";
                    $obj = $gen_db->queryOneRowObject($query);
                    $isFinalProcess = Logic_Achievement::isFinalProcess($obj->order_detail_id, $obj->process_id);

                    if ($isFinalProcess) {
                        $idArr[] = $id;    // 最終工程ならidを保持
                        if (!in_array($subcontractOrderProcessNo, $subcontractOrderProcessNoArr)) {
                            $subcontractOrderProcessNoArr[] = $subcontractOrderProcessNo;
                        }
                    }
                }
                
                $this->deleteIdArray[] = $id;
            }
        }
        // 最終工程が存在するなら唯一の外製指示かチェックする
        if (count($idArr) > 0) {
            $idCsv = join(',', $idArr);
            foreach ($subcontractOrderProcessNoArr as $value) {
                $query = "select order_header_id from order_detail
                    where subcontract_order_process_no = '{$value}'
                    and order_header_id not in ({$idCsv})";
                // 外製指示書が唯一の最終工程だった場合は削除できない。
                // あるいは最終工程の外製指示書がコピーされ、
                // 該当する外製指示がすべて選択された場合も削除できない。
                // 親の製造指示書の削除で該当する外製指示も削除される。
                if (!$gen_db->existRecord($query)) {
                    $validator->raiseError(_g("親オーダーの最終工程にあたる外製指示書は削除することができません。"));
                    break;
                }
            }
        }
        if (count($this->deleteIdArray) == 0) {
            $validator->raiseError(_g("削除するデータがありません。"));
        }
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
        $this->log1 = _g("外製指示登録");
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
