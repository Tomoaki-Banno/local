<?php

class Delivery_Delivery_BulkDelete extends Base_BulkDeleteBase
{

    function setParam(&$form)
    {
        $this->listAction = 'Delivery_Delivery_List';
        if (isset($form['returnHeaderId']) && is_numeric($form['returnHeaderId'])) {
            $act = "Edit";
            $form['delivery_header_id'] = $form['returnHeaderId'];
            $form['gen_updated'] = "true";
        } else {
            $act = "List";
        }
        $this->deleteAfterAction = 'Delivery_Delivery_' . $act;
    }

    function _validate($validator, &$form)
    {
        global $gen_db;

        $this->isDetailMode = isset($form['detail']) && $form['detail'] == "true";

        foreach ($form as $name => $value) {
            if (substr($name, 0, 7) == "delete_") {
                // id配列を取得
                $id = $gen_db->quoteParam(substr($name, 7, strlen($name) - 7));
                if (!is_numeric($id)) {
                    continue;
                } else {
                    $this->deleteIdArray[] = $id;
                }

                // このあとのチェックに使用する値
                $query = "select receivable_report_timing from company_master";
                $timing = $gen_db->queryOneValue($query);
                if ($this->isDetailMode) {
                    // ヘッダー情報取得
                    $arr = Logic_Delivery::getDeliveryDataById($id, true);
                    $deliveryDate = Logic_Delivery::getDeliveryDateByDetailId($id);
                    if ($timing == "1") {
                        $checkDate = $arr[0]['inspection_date'];
                    } else {
                        $checkDate = $deliveryDate;
                    }
                    $customerId = Logic_Delivery::getCustomerIdByDetailId($id);
                } else {
                    // ヘッダー情報取得
                    $arr = Logic_Delivery::getDeliveryDataById($id, false);
                    $deliveryDate = Logic_Delivery::getDeliveryDateByTranId($id);
                    if ($timing == "1") {
                        $checkDate = $arr[0]['inspection_date'];
                    } else {
                        $checkDate = $deliveryDate;
                    }
                    $customerId = Logic_Delivery::getCustomerIdByTranId($id);
                }

                // ロック年月チェック
                if ($timing == "1") {   // 売上計上基準が検収日の時は納品日もチェックする
                    $validator->notSalesLockDateForDelete($deliveryDate, _g("納品日"));
                }
                if ($timing != "1" || $checkDate != '') {   // 検収日基準で検収日が空欄のときはチェックしない
                    $validator->notSalesLockDateForDelete($checkDate, ($timing == "1" ? _g("検収日") : _g("納品日")));
                }
                if ($validator->hasError())
                    break;

                // 請求書発行状況チェック
                if ($arr[0]['bill_pattern'] == "2") {
                    // 「都度請求」チェック
                    $isBill = Logic_Bill::hasBillByDeliveryId($this->isDetailMode, $id);
                    if ($isBill) {
                        $validator->raiseError(sprintf(_g("納品書番号 %1\$s に対し都度請求書が発行されているため削除を行えません。"), $arr[0]['delivery_no']));
                    }
                    if ($validator->hasError())
                        break;
                } else {
                    // 「締め請求」チェック
                    $closeDate = Logic_Bill::getLastCloseDateByCustomerId($customerId);
                    if ($closeDate != null && $checkDate != '') {
                        if (strtotime($closeDate) >= strtotime($checkDate)) {
                            $validator->raiseError(sprintf(_g("この得意先に対し %1\$s 締の請求書が発行されているため、削除を行えません。"), $closeDate));
                        }
                    }
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

        // ログ用に納品書番号と受注製番を取得
        $idCsv = join(',', $this->deleteIdArray);
        $query = "
        select
            delivery_header.delivery_no || ' (' || string_agg(received_detail.seiban, ',') || ')' as col1
        from
            delivery_detail
            left join delivery_header on delivery_detail.delivery_header_id = delivery_header.delivery_header_id
            left join received_detail on delivery_detail.received_detail_id = received_detail.received_detail_id
            left join item_master on received_detail.item_id = item_master.item_id
        where
            delivery_detail.delivery_" . ($this->isDetailMode ? "detail" : "header") . "_id in ({$idCsv})
        group by
            delivery_header.delivery_no
        order by
            delivery_header.delivery_no
        ";
        $res = $gen_db->getArray($query);
        foreach ($res as $row) {
            $this->numberArray[] = $row['col1'];
        }
        $numberCsv = join(', ', $this->numberArray);
        $numberCsvForMsg = $this->_makeNumberCsvForMsg($numberCsv);

         // メッセージ
        $this->afterDeleteMessage = sprintf(_g("%1\$s件のデータ（%2\$s：%3\$s）を削除しました。"), $count,  _g('納品書番号') . ' (' . _g('受注製番') . ')', $numberCsvForMsg);

        // データアクセスログ
        $this->log1 = _g("納品");
        $this->log2 = sprintf(_g("%1\$s件（%2\$s：%3\$s）"), $count, _g('納品書番号') . ' (' . _g('受注製番') . ')', $numberCsv);
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
                    delivery_detail
                set
                    line_no = delivery_detail.line_no - 1
                from
                    delivery_detail as t2
                where
                    delivery_detail.delivery_header_id = t2.delivery_header_id
                    and t2.delivery_detail_id = '{$id}'
                    and delivery_detail.line_no > t2.line_no
                ";
                $gen_db->query($query);

                // 削除
                $headerId = Logic_Delivery::deleteDeliveryDetail($id);

                // 添付ファイル削除用にヘッダーidを記録
                if (isset($headerId) && is_numeric($headerId)) {
                    $this->recordIdArray[] = $headerId;
                }
            } else {
                Logic_Delivery::deleteDelivery($id);
            }
        }
    }

}