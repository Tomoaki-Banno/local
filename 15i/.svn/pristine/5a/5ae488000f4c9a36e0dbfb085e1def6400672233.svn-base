<?php

class Menu_Chat
{

    function execute(&$form)
    {
        global $gen_db;
        
        $form['gen_pageTitle'] = _g("トークボード");
        
        if (isset($form['chat_detail_id']) && Gen_String::isNumeric($form['chat_detail_id'])) {
            $query = "select chat_header_id from chat_detail where chat_detail_id = '{$form['chat_detail_id']}'";
            $form['chat_header_id'] = $gen_db->queryOneValue($query);
            if (!is_numeric($form['chat_header_id'])) {
                $form['chat_header_id'] = -999; // 「このチャットは存在しない」を表示させるため
            }
        }
        if (!isset($form['chat_header_id']) || !Gen_String::isNumeric($form['chat_header_id'])) {
            // 最終読み出しスレッドを取得。
            //  index.php でもheader用に同じ処理を行なっているが、execute実行より後で行われているため間に合わない。
            $query = "select last_chat_header_id from ";
            $userId = Gen_Auth::getCurrentUserId();
            if ($userId == -1) {
                $query .= "company_master";
            } else {
                $query .= "user_master where user_id = '{$userId}'";
            }
            $chatInfo = $gen_db->queryOneRowObject($query);
            if ($chatInfo && $chatInfo->last_chat_header_id) {
                $form['chat_header_id'] = $chatInfo->last_chat_header_id;
            } else {
                unset($form['chat_header_id']);
            }
        }

        return 'menu_chat.tpl';
    }
}