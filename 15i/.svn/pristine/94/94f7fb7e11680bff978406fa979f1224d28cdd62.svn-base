<?php

class Manufacturing_Received_BulkDelete extends Base_BulkDeleteBase
{

    function setParam(&$form)
    {
        $this->listAction = ($form['gen_mobile'] ? 'Mobile_Received_List' : 'Manufacturing_Received_List');
        if (isset($form['returnHeaderId']) && is_numeric($form['returnHeaderId'])) {
            $act = "Edit";
            $form['received_header_id'] = $form['returnHeaderId'];
            $form['gen_updated'] = "true";
        } else {
            $act = "List";
        }
        $this->deleteAfterAction = ($form['gen_mobile'] ? 'Mobile_Received_' : 'Manufacturing_Received_') . $act;
    }

    function _validate($validator, &$form)
    {
        global $gen_db;

        $this->isDetailMode = isset($form['detail']) && $form['detail'] == "true";

        // データロック対象外
        $query = "select unlock_object_1 from company_master";
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

                // このあとのチェックに使用する値
                if ($this->isDetailMode) {
                    $deliveryDate = Logic_Received::getReceivedDateByDetailId($id);
                    $receivedNumber = Logic_Received::getReceivedNumberByDetailId($id);
                    $customerId = Logic_Received::getCustomerIdByDetailId($id);
                } else {
                    $deliveryDate = Logic_Received::getReceivedDateByTranId($id);
                    $receivedNumber = Logic_Received::getReceivedNumberByTranId($id);
                    $customerId = Logic_Received::getCustomerIdByTranId($id);
                }

                // データロック対象外でなければチェック
                if ($unlock != "1") {
                    // ロック年月チェック
                    $validator->notSalesLockDateForDelete($deliveryDate, _g("受注日"));
                    if ($validator->hasError())
                        break;
                }

                // 納品状況チェック
                if (!$form['gen_mobile']) { //　モバイル版では納品登録されていても削除する
                    if ($this->isDetailMode) {
                        $isErr = Logic_Delivery::hasDeliveryByReceivedDetailId($id);
                    } else {
                        $isErr = Logic_Delivery::hasDeliveryByReceivedHeaderId($id);
                    }
                    if ($isErr) {
                        $validator->raiseError(sprintf(_g("受注番号 %s に対して納品が登録されているため、削除できません。"), $receivedNumber));
                        break;
                    }
                }

                // 受注製番チェック（製造指示書・注文書・外製指示書）
                for ($i = 0; $i <= 2; $i++) {
                    $query = "
                    select
                        received_detail_id
                    from
                        received_detail
                        inner join item_master on received_detail.item_id = item_master.item_id
                        inner join order_detail on received_detail.seiban = order_detail.seiban and received_detail.item_id = order_detail.item_id
                        inner join order_header on order_detail.order_header_id = order_header.order_header_id
                    where
                        received_detail_id = '{$id}'
                        and order_class = 0
                        and order_header.classification = '{$i}'
                    ";
                    if ($gen_db->existRecord($query)) {
                        if ($i == 1) {
                            $class = _g("注文");
                        } elseif ($i == 2) {
                            $class = _g("外製指示");
                        } else {
                            $class = _g("製造指示");
                        }
                        $query = "select seiban from received_detail where received_detail_id = '{$id}'";
                        $seiban = $gen_db->queryOneValue($query);
                        $validator->raiseError(sprintf(_g("受注製番 %1\$s が、%2\$sで参照されているため削除できません。"), $seiban, $class));
                        break;
                    }
                    if ($validator->hasError())
                        break;
                }

                // 受注製番チェック（製番引当）
                $query = "
                select
                    received_detail_id
                from
                    received_detail
                    inner join item_master on received_detail.item_id = item_master.item_id
                    inner join seiban_change on received_detail.item_id = seiban_change.item_id
                        and (received_detail.seiban = seiban_change.source_seiban or received_detail.seiban = seiban_change.dist_seiban)
                where
                    received_detail_id = '{$id}'
                    and order_class = 0
                ";
                if ($gen_db->existRecord($query)) {
                    $query = "select seiban from received_detail where received_detail_id = '{$id}'";
                    $seiban = $gen_db->queryOneValue($query);
                    $validator->raiseError(sprintf(_g("受注製番 %s が、製番引当で参照されているため削除できません。"), $seiban));
                    break;
                }
                if ($validator->hasError())
                    break;

                // 以前はここで請求書発行状況チェック（受注納期より後の請求書が発行されていたら削除できない）を行なっていたが、
                // 未納品であれば受注削除できるべきなのではとの指摘があり、チェックを外した。
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

        // ログ用に受注番号・製番を取得
        $idCsv = join(',', $this->deleteIdArray);
        if ($this->isDetailMode) {
            $query = "
            select
                received_detail.seiban as col1
                ,cast(received_detail.seiban as text) || ' [' || item_code || ']' as col2
                ,coalesce(mrp_flag,0) as col3
            from
                received_detail
                left join item_master on received_detail.item_id = item_master.item_id
                left join (select 1 as mrp_flag, seiban from mrp where (arrangement_quantity > 0 or arrangement_quantity < 0) group by seiban) as t_mrp on received_detail.seiban = t_mrp.seiban
            where
                received_detail_id in ({$idCsv})
            order by
                received_detail.seiban
            ";
        } else {
            $query = "
            select
                received_number as col1
                ,max(coalesce(mrp_flag,0)) as col3
            from
                received_header
                left join received_detail on received_header.received_header_id = received_detail.received_header_id
                left join (select 1 as mrp_flag, seiban from mrp where (arrangement_quantity > 0 or arrangement_quantity < 0) group by seiban) as t_mrp on received_detail.seiban = t_mrp.seiban
            where
                received_header.received_header_id in ({$idCsv})
            group by
                received_number
            order by
                received_number
            ";
        }
        $res = $gen_db->getArray($query);

        $mrpFlag = false;
        foreach ($res as $row) {
            $this->numberArray[] = $row['col1'];
            if ($this->isDetailMode)
                $this->numberDetailArray[] = $row['col2'];
            if ($row['col3'] == 1)
                $mrpFlag = true;
        }

        $numberCsv = join(', ', $this->numberArray);
        $numberDetailCsv = join(', ', $this->numberDetailArray);
        $numberCsvForMsg = $this->_makeNumberCsvForMsg($numberCsv);

        // メッセージ
        $this->afterDeleteMessage = sprintf(_g("%1\$s件のデータ（%2\$s：%3\$s）を削除しました。"), $count, ($this->isDetailMode ? _g('受注製番') : _g('受注番号')), $numberCsvForMsg);

        if ($mrpFlag) {
            $this->afterDeleteMessage .= _g('所要量計算の結果に削除された受注製番のオーダーが含まれています。所要量計算を再実行してください。');
        }

        // データアクセスログ
        $this->log1 = _g("受注");
        $this->log2 = sprintf(_g("%1\$s件（%2\$s：%3\$s）"), $count, ($this->isDetailMode ? _g('受注製番') . ' [' . _g('品目コード') . ']' : _g('受注番号')), ($this->isDetailMode ? $numberDetailCsv : $numberCsv));
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
                    received_detail
                set
                    line_no = received_detail.line_no - 1
                from
                    received_detail as t2
                where
                    received_detail.received_header_id = t2.received_header_id
                    and t2.received_detail_id = '{$id}'
                    and received_detail.line_no > t2.line_no
                ";
                $gen_db->query($query);

                // 削除
                $headerId = Logic_Received::deleteReceivedDetail($id);

                // 添付ファイル削除用にヘッダーidを記録
                if (isset($headerId) && is_numeric($headerId)) {
                    $this->recordIdArray[] = $headerId;
                }
            } else {
                Logic_Received::deleteReceivedHeader($id);
            }
        }
    }

}