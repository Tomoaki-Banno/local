<?php

class Master_Item_BulkDelete extends Base_BulkDeleteBase
{

    function setParam(&$form)
    {
        $this->listAction = 'Master_Item_List';
        $this->deleteAfterAction = 'Master_Item_List';
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

                $code = $gen_db->queryOneValue("select item_code from item_master where item_id = '{$id}'");
                if ($code != "") {
                    if (!$this->_checkData($validator, _g("品目コード %s は実績が登録されているため削除できません。"), $code, "select item_id from achievement where item_id = {$id}"))
                        break;
                    if (!$this->_checkData($validator, _g("品目コード %s は構成表マスタに登録されているため削除できません。"), $code, "select item_id from bom_master where (item_id = {$id} or child_item_id = {$id})"))
                        break;
                    if (!$this->_checkData($validator, _g("品目コード %s は得意先販売価格が登録されているため削除できません。"), $code, "select item_id from customer_price_master where item_id = {$id}"))
                        break;
                    if (!$this->_checkData($validator, _g("品目コード %s は見積が登録されているため削除できません。"), $code, "select item_id from estimate_detail where item_id = {$id}"))
                        break;
                    if (!$this->_checkData($validator, _g("品目コード %s は入庫が登録されているため削除できません。"), $code, "select item_id from item_in_out where item_id = {$id} and classification='in'"))
                        break;
                    if (!$this->_checkData($validator, _g("品目コード %s は出庫が登録されているため削除できません。"), $code, "select item_id from item_in_out where item_id = {$id} and classification='out'"))
                        break;
                    if (!$this->_checkData($validator, _g("品目コード %s は使用数が登録されているため削除できません。"), $code, "select item_id from item_in_out where (item_id = {$id} or parent_item_id = $id) and classification='use'"))
                        break;
                    if (!$this->_checkData($validator, _g("品目コード %s は支給が登録されているため削除できません。"), $code, "select item_id from item_in_out where item_id = {$id} and classification='payout'"))
                        break;
                    if (!$this->_checkData($validator, _g("品目コード %s は納品が登録されているため削除できません。"), $code, "select item_id from item_in_out where item_id = {$id} and classification='delivery'"))
                        break;
                    if (!$this->_checkData($validator, _g("品目コード %s は製造使用数が登録されているため削除できません。"), $code, "select item_id from item_in_out where item_id = {$id} and classification='manufacturing'"))
                        break;
                    if (!$this->_checkData($validator, _g("品目コード %s は移動が登録されているため削除できません。"), $code, "select item_id from location_move where item_id = {$id}"))
                        break;
                    if (!$this->_checkData($validator, _g("品目コード %s は発注もしくは製造指示が登録されているため削除できません。"), $code, "select item_id from order_detail where item_id = {$id}"))
                        break;
                    if (!$this->_checkData($validator, _g("品目コード %s は製造指示もしくは外製指示の子品目として登録されているため削除できません。"), $code, "select child_item_id from order_child_item where child_item_id = {$id}"))
                        break;
                    if (!$this->_checkData($validator, _g("品目コード %s は計画が登録されているため削除できません。"), $code, "select item_id from plan where item_id = {$id}"))
                        break;
                    if (!$this->_checkData($validator, _g("品目コード %s は受注が登録されているため削除できません。"), $code, "select item_id from received_detail where item_id = {$id}"))
                        break;
                    if (!$this->_checkData($validator, _g("品目コード %s は製番引当が登録されているため削除できません。"), $code, "select item_id from seiban_change where item_id = {$id}"))
                        break;
                    if (!$this->_checkData($validator, _g("品目コード %s は棚卸が登録されているため削除できません。"), $code, "select item_id from inventory where item_id = {$id}"))
                        break;
                    if (!$this->_checkData($validator, _g("品目コード %s は引当が登録されているため削除できません。"), $code, "select item_id from use_plan where item_id = {$id}"))
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

        // ログ用に品目コードを取得
        $idCsv = join(',', $this->deleteIdArray);
        $query = "select item_code as col1 from item_master where item_id in ({$idCsv}) order by item_code";
        $res = $gen_db->getArray($query);
        foreach ($res as $row) {
            $this->numberArray[] = $row['col1'];
        }

        $numberCsv = join(', ', $this->numberArray);
        $numberCsvForMsg = $this->_makeNumberCsvForMsg($numberCsv);

         // メッセージ
        $this->afterDeleteMessage = sprintf(_g("%1\$s件のデータ（%2\$s：%3\$s）を削除しました。"), $count, _g('品目コード'), $numberCsvForMsg);

        // データアクセスログ
        $this->log1 = _g("品目");
        $this->log2 = sprintf(_g("%1\$s件（%2\$s：%3\$s）"), $count, _g('品目コード'), $numberCsv);
    }

    function _delete(&$form)
    {
        global $gen_db;

        // 削除処理
        $idCsv = join(',', $this->deleteIdArray);
        // MRP結果の削除
        $query = "delete from mrp where item_id in ({$idCsv})";
        $gen_db->query($query);
        // MRP工程の削除
        $query = "delete from mrp_process where item_id in ({$idCsv})";
        $gen_db->query($query);
        // 発注先マスタの削除
        $query = "delete from item_order_master where item_id in ({$idCsv})";
        $gen_db->query($query);
        // 品目工程マスタの削除
        $query = "delete from item_process_master where item_id in ({$idCsv})";
        $gen_db->query($query);
        // 得意先販売価格マスタの削除
        $query = "delete from customer_price_master where item_id in ({$idCsv})";
        $gen_db->query($query);
        // 在庫評価単価履歴の削除
        $query = "delete from stock_price_history where item_id in ({$idCsv})";
        $gen_db->query($query);
        // 品目画像の削除
        $fileNameArr = $gen_db->getArray("select image_file_name from item_master where item_id in ({$idCsv}) and image_file_name <> ''");
        if ($fileNameArr) {
            $storage = new Gen_Storage("ItemImage");
            foreach($fileNameArr as $fileName) {
                $storage->delete($fileName['image_file_name']);
            }
        }
        // 品目マスタの削除
        $query = "delete from item_master where item_id in ({$idCsv})";
        $gen_db->query($query);
    }

}