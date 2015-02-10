<?php
class Mobile_Received_Edit
{
    function execute(&$form)
    {
        global $gen_db;
        
        if ($form['gen_readonly']) {
            return 'action:Mobile_ShowError_PermissionError';
        }

        $form['gen_pageTitle'] = _g("受注登録");

        $form['gen_headerLeftButtonURL'] = "index.php?action=Mobile_Received_List";
        $form['gen_headerLeftButtonIcon'] = "delete";
        $form['gen_headerLeftButtonText'] = _g("閉じる");

        // ページリクエストIDの発行処理
        global $_SESSION;
        $reqId = sha1(uniqid(rand(), true));
        $_SESSION['gen_page_request_id'][] = $reqId;
        $form['gen_page_request_id'] = $reqId;

        // 部門選択肢
        $query = "select section_id, section_name from section_master order by section_code";
        $form['sectionOptions'] = $gen_db->getHtmlOptionArray($query, true, false);

        // 担当者選択肢
        $query = "select worker_id, worker_name from worker_master order by worker_code";
        $form['workerOptions'] = $gen_db->getHtmlOptionArray($query, true, false);
        
        // カスタム項目
        $form['gen_columnArray'] = array();
        if ($form['gen_customColumnArray']) {
            foreach($form['gen_customColumnArray'] as $customCol => $customArr) {
                $customMode = $customArr[0];
                $customName = $customArr[1];
                $customColumnName = $customArr[2];
                list($type, $options) = Logic_CustomColumn::getCustomElementTypeAndOptions($customColumnName, $customMode);
                switch ($type) {
                    case "textbox": $type = "text"; break;
                    case "calendar": $type = "date"; break;
                }
                $form['gen_columnArray'][] = 
                    array(
                        "label" => $customName,
                        "type" => $type,
                        "field" => "gen_custom_{$customCol}",
                        'options' => (isset($options) ? $options : null),
                        'selected' => @$form["gen_custom_{$customCol}"]
                    );
            }
        }
        return 'mobile_received_edit.tpl';
    }
}