<?php

class Master_Customer_BulkDelete extends Base_BulkDeleteBase
{

    function setParam(&$form)
    {
        $this->listAction = 'Master_Customer_List';
        $this->deleteAfterAction = 'Master_Customer_List';
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

                $code = $gen_db->queryOneValue("select customer_no from customer_master where customer_id = '{$id}'");
                if ($code != "") {
                    if (!$this->_checkData($validator, _g("取引先コード %s に対する請求書が発行されているため削除できません。"), $code, "select customer_id from bill_header where customer_id = {$id}"))
                        break;
                    if (!$this->_checkData($validator, _g("取引先コード %s に対する入金が登録されているため削除できません。"), $code, "select customer_id from paying_in where customer_id = {$id}"))
                        break;
                    if (!$this->_checkData($validator, _g("取引先コード %s に対する支払が登録されているため削除できません。"), $code, "select customer_id from payment where customer_id = {$id}"))
                        break;
                    if (!$this->_checkData($validator, _g("取引先コード %s からの受注が登録されているため削除できません。"), $code, "select customer_id from received_detail inner join received_header on received_header.received_header_id=received_detail.received_header_id where customer_id = {$id}"))
                        break;
                    if (!$this->_checkData($validator, _g("取引先コード %s は受注において発送先として登録されているため削除できません。"), $code, "select customer_id from received_detail inner join received_header on received_header.received_header_id=received_detail.received_header_id where delivery_customer_id = {$id}"))
                        break;
                    if (!$this->_checkData($validator, _g("取引先コード %s に対する見積が登録されているため削除できません。"), $code, "select customer_id from estimate_header where customer_id = {$id}"))
                        break;
                    if (!$this->_checkData($validator, _g("取引先コード %s に対する支給が登録されているため削除できません。"), $code, "select partner_id from item_in_out where partner_id = {$id}"))
                        break;
                    if (!$this->_checkData($validator, _g("取引先コード %s に対する発注が登録されているため削除できません。"), $code, "select partner_id from order_header where partner_id = {$id}"))
                        break;
                    if (!$this->_checkData($validator, _g("取引先コード %s は発注において発送先として登録されているため削除できません。"), $code, "select delivery_partner_id from order_header where delivery_partner_id = {$id}"))
                        break;
                    if (!$this->_checkData($validator, _g("取引先コード %s は得意先販売価格マスタに登録されているため削除できません。"), $code, "select customer_id from customer_price_master where customer_id = {$id}"))
                        break;
                    if (!$this->_checkData($validator, _g("取引先コード %s は品目マスタにおいて発注先として登録されているため削除できません。"), $code, "select order_user_id from item_order_master where order_user_id = {$id}"))
                        break;
                    if (!$this->_checkData($validator, _g("取引先コード %s は品目マスタにおいて外製先として登録されているため削除できません。"), $code, "select subcontract_partner_id from item_process_master where subcontract_partner_id = {$id}"))
                        break;
                    if (!$this->_checkData($validator, _g("取引先コード %s は取引先マスタにおいて請求先として登録されているため削除できません。"), $code, "select customer_id from customer_master where bill_customer_id = {$id}"))
                        break;
                    if (!$this->_checkData($validator, _g("取引先コード %s と関連付けられたロケーションが登録されているため削除できません。"), $code, "select customer_id from location_master where customer_id = {$id}"))
                        break;
                    if (!$this->_checkData($validator, _g("取引先コード %s と関連付けられたユーザーが登録されているため削除できません。"), $code, "select customer_id from user_master where customer_id = {$id}"))
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

        // ログ用に取引先コードを取得
        $idCsv = join(',', $this->deleteIdArray);
        $query = "select customer_no as col1 from customer_master where customer_id in ({$idCsv}) order by customer_no";
        $res = $gen_db->getArray($query);
        foreach ($res as $row) {
            $this->numberArray[] = $row['col1'];
        }

        $numberCsv = join(', ', $this->numberArray);
        $numberCsvForMsg = $this->_makeNumberCsvForMsg($numberCsv);

        // メッセージ
        $this->afterDeleteMessage = sprintf(_g("%1\$s件のデータ（%2\$s：%3\$s）を削除しました。"), $count, _g('取引先コード'), $numberCsvForMsg);

        // データアクセスログ
        $this->log1 = _g("取引先");
        $this->log2 = sprintf(_g("%1\$s件（%2\$s：%3\$s）"), $count, _g('取引先コード'), $numberCsv);
    }

    function _delete(&$form)
    {
        global $gen_db;

        // 削除処理
        $idCsv = join(',', $this->deleteIdArray);
        // 得意先販売価格マスタの削除
        $query = "delete from customer_price_master where customer_id in ({$idCsv})";
        $gen_db->query($query);
        // 取引先マスタ
        $query = "delete from customer_master where customer_id in ({$idCsv})";
        $gen_db->query($query);
    }

}