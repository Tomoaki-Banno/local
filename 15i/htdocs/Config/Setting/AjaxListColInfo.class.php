<?php

class Config_Setting_AjaxListColInfo extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;

        if ($form['action_name'] == '' || !is_numeric($form['col_num'])) {
            return;
        }

        // クロス集計時は列情報の保存を行わない。（リスト列が通常時とは異なるので、保存できない）
        // クロス集計時かどうかはクライアントからのパラメータに頼っている。そのため不正POSTすればチェックを抜けられてしまう。
        // しかし、現在表示されているのがクロス集計であるかどうかを、サーバー側で確実に判断する方法がない。
        // それに、仮に不正POSTを行われても、当人の画面表示がおかしくなるだけ。
        if (isset($form['is_cross']) && $form['is_cross'] == "true") 
            return;
        
        // 列情報は必ず作成されているはず（ListBase）
        $data = array();
        if (is_numeric(@$form['col_width']))
            $data["column_width"] = $form['col_width'];
        if (isset($form['col_hide']))
            $data["column_hide"] = $form['col_hide'];
        if (is_numeric(@$form['col_keta']))
            $data["column_keta"] = $form['col_keta'];
        if (is_numeric(@$form['col_kanma']))
            $data["column_kanma"] = $form['col_kanma'];
        if (is_numeric(@$form['col_align']))
            $data["column_align"] = $form['col_align'];
        if (is_numeric(@$form['col_bgcolor']))
            $data["column_bgcolor"] = $form['col_bgcolor'];
        if (is_numeric(@$form['col_wrapon']))
            $data["column_wrapon"] = $form['col_wrapon'];

        if (isset($form['filter_type'])) {
            switch ($form['filter_type']) {
                case "data":
                case "numeric":
                    if ((isset($form['search1']) && $form['search1']!="") || (isset($form['match1']) && ($form['match1']=="98" || $form['match1']=="99"))
                            || (isset($form['search2']) && $form['search2']!="") || (isset($form['match2']) && ($form['match2']=="98" || $form['match2']=="99"))) {
                        // これだと半角「:」での絞込みができないが、SQLエラーになるよりマシ
                        if (isset($form['filter_type']))
                            $form['filter_type'] = str_replace (":", "：", $form['filter_type']);
                        if (isset($form['search1']))
                            $form['search1'] = str_replace (":", "：", $form['search1']);
                        if (isset($form['match1']))
                            $form['match1'] = str_replace (":", "：", $form['match1']);
                        if (isset($form['bool']))
                            $form['bool'] = str_replace (":", "：", $form['bool']);
                        if (isset($form['search2']))
                            $form['search2'] = str_replace (":", "：", $form['search2']);
                        if (isset($form['match2']))
                            $form['match2'] = str_replace (":", "：", $form['match2']);
                        $data["column_filter"] = $form['filter_type'].":::".@$form['search1'].":::".@$form['match1'].":::".@$form['bool'].":::".@$form['search2'].":::".@$form['match2'];
                    } else {
                        $data["column_filter"] = "";
                    }
                    break;
                case "date":
                    if ((isset($form['date_from']) && $form['date_from']!="") || (isset($form['date_to']) && $form['date_to']!="")) {
                        $data["column_filter"] = "date:::".@$form['date_from'].":::".@$form['date_to'];
                    } else {
                        $data["column_filter"] = "";
                    }
                    break;
            }
        } 

        $user_id = Gen_Auth::getCurrentUserId();
        $action = $form['action_name'];
        if (isset($user_id) && is_numeric($user_id)) {
            $where = "user_id = {$user_id} and action = '{$action}' and column_number = '{$form['col_num']}'";
            $gen_db->update("column_info", $data, $where);
        }

        return;
    }

}