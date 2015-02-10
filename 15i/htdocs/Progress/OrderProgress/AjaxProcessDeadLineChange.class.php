<?php

class Progress_OrderProgress_AjaxProcessDeadLineChange extends Base_AjaxBase
{

    function validate($validator, &$form)
    {
        $validator->existRecord('order_detail_id', '', "select * from order_detail where order_detail_id = $1", false);
        $validator->range('seq', '', 0, 1000);
        $validator->dateString('deadLine', '');
        return 'simple.tpl';  // if error
    }

    function _execute(&$form)
    {
        global $gen_db;

        $orderDetailId = $form['order_detail_id'];
        $seq = $form['seq'];
        $deadLine = $form['deadLine'];

        if ($form['gen_readonly'] == "true" || !is_numeric($orderDetailId) || !is_numeric($seq) || !Gen_String::isDateString($deadLine)) {
            return
                array(
                    "status" => "failure"
                );
        }

        // 工程納期を工程開始日より前にすることはできない（前工程やその前の工程に影響がでてしまうし、オーダー全体の着手日を超えてしまう可能性もあるので）
        $query = "select process_start_date from order_process where order_detail_id = '{$orderDetailId}' and machining_sequence = '{$seq}'";
        $startDate = $gen_db->queryOneValue($query);
        if (strtotime($startDate) > strtotime($deadLine)) {
            return
                array(
                    "status" => "dateerr1"
                );
        }

        // 工程納期を次の工程の納期より後にすることはできない（後工程のその後の工程に影響を与える可能性があるし、オーダー納期を超えてしまう可能性もあるので）
        $query = "select process_dead_line from order_process where order_detail_id = '{$orderDetailId}' and machining_sequence = '" . ($seq + 1) . "'";
        $nextDeadline = $gen_db->queryOneValue($query);
        if ($nextDeadline != null && strtotime($deadLine) > strtotime($nextDeadline)) {
            return
                array(
                    "status" => "dateerr2"
                );
        }

        // 工程納期を修正する
        $data = array("process_dead_line" => $deadLine);
        $where = "order_detail_id = '{$orderDetailId}' and machining_sequence = '{$seq}'";
        $gen_db->update("order_process", $data, $where);

        // 後工程（あれば）の開始日を修正する
        $seq++;
        $data = array("process_start_date" => $deadLine);
        $where = "order_detail_id = '{$orderDetailId}' and machining_sequence = '{$seq}'";
        $gen_db->update("order_process", $data, $where);

        // データアクセスログ
        $res = $gen_db->queryOneValue("select order_process_no from order_process where {$where}");
        $msg = "[" . _g("実績登録コード") . "] " . $res;
        Gen_Log::dataAccessLog(_g("オーダー別進捗"), _g("工程納期変更"), $msg);

        return
            array(
                "status" => "success"
            );
    }

}