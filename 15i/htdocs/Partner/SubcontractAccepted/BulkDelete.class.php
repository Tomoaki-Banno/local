<?php

class Partner_SubcontractAccepted_BulkDelete extends Base_BulkDeleteBase
{

    function setParam(&$form)
    {
        $this->listAction = 'Partner_SubcontractAccepted_List';
        $this->deleteAfterAction = 'Partner_SubcontractAccepted_List';
    }

    function _validate($validator, &$form)
    {
        global $gen_db;

        foreach ($form as $name => $value) {
            if (substr($name, 0, 7) == "delete_") {
                // id配列を取得
                $id = $gen_db->quoteParam(substr($name, 7, strlen($name) - 7));
                if (!is_numeric($id)) {
                    continue;
                } else {
                    $this->deleteIdArray[] = $id;
                }

                // ロック年月チェック
                $date = Logic_Accepted::getAcceptedDate($id);
                $validator->notBuyLockDateForDelete($date, _g("受入日"));
                if ($validator->hasError())
                    break;
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
            accepted.order_no as col1
            ,accepted.order_no || ' [' || item_code || ']' as col2
        from
            accepted
            left join order_detail on accepted.order_detail_id = order_detail.order_detail_id
        where
            accepted_id in ({$idCsv})
        order by
            accepted.order_no
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
        $this->log1 = _g("外製受入登録");
        $this->log2 = sprintf(_g("%1\$s件（%2\$s：%3\$s）"), $count, _g('オーダー番号') . ' [' . _g('品目コード') . ']', $numberDetailCsv);
    }

    function _delete(&$form)
    {
        // 削除処理
        foreach ($this->deleteIdArray as $id) {
            Logic_Accepted::deleteAccepted($id);
        }
    }

}