<?php

class Master_Currency_BulkDelete extends Base_BulkDeleteBase
{

    function setParam(&$form)
    {
        $this->listAction = 'Master_Currency_List';
        $this->deleteAfterAction = 'Master_Currency_List';
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

                $code = $gen_db->queryOneValue("select currency_name from currency_master where currency_id = '{$id}'");
                if ($code != "") {
                    if (!$this->_checkData($validator, _g("取引通貨 %s を使用する取引先が登録されているため削除できません。"), $code, "select currency_id from customer_master where currency_id = {$id}"))
                        break;
                    if (!$this->_checkData($validator, _g("取引通貨 %s を使用する為替レートが登録されているため削除できません。"), $code, "select currency_id from rate_master where currency_id = {$id}"))
                        break;
                    if (!$this->_checkData($validator, _g("取引通貨 %s 建ての入金が登録されているため削除できません。"), $code, "select foreign_currency_id from paying_in where foreign_currency_id = {$id}"))
                        break;
                    if (!$this->_checkData($validator, _g("取引通貨 %s 建ての請求書が登録されているため削除できません。"), $code, "select foreign_currency_id from bill_header where foreign_currency_id = {$id}"))
                        break;
                    if (!$this->_checkData($validator, _g("取引通貨 %s 建ての納品が登録されているため削除できません。"), $code, "select foreign_currency_id from delivery_header where foreign_currency_id = {$id}"))
                        break;
                    if (!$this->_checkData($validator, _g("取引通貨 %s 建ての受注が登録されているため削除できません。"), $code, "select foreign_currency_id from received_detail where foreign_currency_id = {$id}"))
                        break;
                    if (!$this->_checkData($validator, _g("取引通貨 %s 建ての支払が登録されているため削除できません。"), $code, "select foreign_currency_id from payment where foreign_currency_id = {$id}"))
                        break;
                    if (!$this->_checkData($validator, _g("取引通貨 %s 建ての注文が登録されているため削除できません。"), $code, "select foreign_currency_id from order_detail where foreign_currency_id = {$id}"))
                        break;
                } else {
                    $validator->raiseError(_g("存在しないデータが指定されています。"));
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

        // ログ用に取引通貨を取得
        $idCsv = join(',', $this->deleteIdArray);
        $query = "select currency_name as col1 from currency_master where currency_id in ({$idCsv}) order by currency_name";
        $res = $gen_db->getArray($query);
        foreach ($res as $row) {
            $this->numberArray[] = $row['col1'];
        }

        $numberCsv = join(', ', $this->numberArray);
        $numberCsvForMsg = $this->_makeNumberCsvForMsg($numberCsv);

        // メッセージ
        $this->afterDeleteMessage = sprintf(_g("%1\$s件のデータ（%2\$s：%3\$s）を削除しました。"), $count, _g('取引通貨'), $numberCsvForMsg);

        // データアクセスログ
        $this->log1 = _g("取引通貨");
        $this->log2 = sprintf(_g("%1\$s件（%2\$s：%3\$s）"), $count, _g('取引通貨'), $numberCsv);
    }

    function _delete(&$form)
    {
        global $gen_db;

        // 削除処理
        $idCsv = join(',', $this->deleteIdArray);
        $query = "delete from currency_master where currency_id in ({$idCsv}) ";
        $gen_db->query($query);
    }

}