<?php

class Delivery_PayingIn_BulkDelete extends Base_BulkDeleteBase
{

    function setParam(&$form)
    {
        $this->listAction = 'Delivery_PayingIn_List';
        $this->deleteAfterAction = 'Delivery_PayingIn_List';
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
                $date = $gen_db->queryOneValue("select paying_in_date from paying_in where paying_in_id = '{$id}'");
                $validator->notSalesLockDateForDelete($date, _g("入金日"));
                if ($validator->hasError())
                    break;

                // 請求書発行状況チェック
                $query = "select max(close_date) from bill_header where customer_id = " .
                        "(select customer_id from paying_in where paying_in_id = '{$id}') and bill_pattern = 1";
                $closeDate = $gen_db->queryOneValue($query);
                if ($closeDate != null) {
                    $query = "select paying_in_date from paying_in where paying_in_id = '{$id}'";
                    $payingInDate = $gen_db->queryOneValue($query);
                    if (strtotime($closeDate) >= strtotime($payingInDate)) {
                        $validator->raiseError(sprintf(_g("この得意先に対し %1\$s 締の請求書が発行されているため、入金を削除できません。先に請求書を削除してください。"), $closeDate));
                    }
                }
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

        // ログ用に取引先コード・金額を取得
        $idCsv = join(',', $this->deleteIdArray);
        $query = "
        select
            customer_no as col1
            ,customer_no || ' [' || cast(amount as text) || ']' as col2
        from
            paying_in
            inner join customer_master on paying_in.customer_id = customer_master.customer_id
        where
            paying_in_id in ({$idCsv})
        order by
            customer_no
        ";
        $res = $gen_db->getArray($query);
        foreach ($res as $row) {
            $this->numberArray[] = $row['col1'];
            $this->numberDetailArray[] = $row['col2'];
        }

        $numberDetailCsv = join(', ', $this->numberDetailArray);
        $numberCsvForMsg = $this->_makeNumberCsvForMsg($numberDetailCsv);

        // メッセージ
        $this->afterDeleteMessage = sprintf(_g("%1\$s件のデータ（%2\$s：%3\$s）を削除しました。"), $count, _g('得意先コード') . ' [' . _g('金額') . ']', $numberCsvForMsg);

        // データアクセスログ
        $this->log1 = _g("入金");
        $this->log2 = sprintf(_g("%1\$s件（%2\$s：%3\$s）"), $count, _g('得意先コード') . ' [' . _g('金額') . ']', $numberDetailCsv);
    }

    function _delete(&$form)
    {
        global $gen_db;

        // 削除処理
        $idCsv = join(',', $this->deleteIdArray);
        $query = "delete from paying_in where paying_in_id in ({$idCsv})";
        $gen_db->query($query);
    }

}