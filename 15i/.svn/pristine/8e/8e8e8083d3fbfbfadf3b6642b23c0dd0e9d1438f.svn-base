<?php

class Config_Setting_AjaxStickynote extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;

        $gen_db->begin();

        $userId = Gen_Auth::getCurrentUserId();

        if ($form['op'] != 'add' && !is_numeric($form['noteId'])) {
            return;
        }

        switch ($form['op']) {
            case 'add':
                $xPos = $form['x_pos'];
                if (!is_numeric($xPos))
                    $xPos = 0;
                $yPos = $form['y_pos'];
                if (!is_numeric($yPos))
                    $yPos = 0;
                $data = array(
                    'user_id' => $userId,
                    'show_all_user' => 'false', // デフォルトは自分だけ表示
                    'allow_edit_all_user' => 'false', // デフォルトは自分だけ編集可能
                    'show_all_action' => 'false', // デフォルトはこの画面のみ表示
                    'action' => @$form['action_name'],
                    'x_pos' => (int) $xPos,
                    'y_pos' => (int) $yPos,
                    'width' => 200,
                    'height' => 200,
                    'content' => '',
                    'color' => '#FFFF00', // yellow
                );
                $gen_db->insert('stickynote_info', $data);
                $id = $gen_db->getSequence("stickynote_info_stickynote_id_seq");
                $resArr = $data;
                $resArr['note_id'] = $id;
                $resArr['user_name'] = $_SESSION['user_name'];
                break;

            case 'reg':
                // 権限チェック
                $query = "
                select
                    allow_edit_all_user or user_id = '{$_SESSION['user_id']}' as allowEdit
                from
                    stickynote_info
                where
                    stickynote_id = '{$form['noteId']}'
                ";
                if ($gen_db->queryOneValue($query) == 'f')
                    return;

                // $formはindex.phpでDBサニタイジングされており、「'」が「’」に変換されてしまっているので、
                // ここでは特別に $_REQUEST から取得する。
                $content = urldecode($_REQUEST['content']);
                $content = Gen_String::escapeDangerTags($content);      // 危険なタグ・属性をサニタイジング
                $dbContent = str_replace("'", "[gen_quot]", $content);    // 「'」を「[gen_quot]」に。表示時（index.php）にもとに戻す
                $dbContent = $gen_db->quoteParam($dbContent);               // DBサニタイジング

                $data = array(
                    "content" => $dbContent,
                    'show_all_user' => (isset($form['show_all_user']) && $form['show_all_user'] == 'true' ? 'true' : 'false'),
                    'allow_edit_all_user' => (isset($form['allow_edit_all_user']) && $form['allow_edit_all_user'] == 'true' ? 'true' : 'false'),
                    'show_all_action' => (isset($form['show_all_action']) && $form['show_all_action'] == 'true' ? 'true' : 'false'),
                    "color" => @$form['color'],
                );
                $where = "stickynote_id = '{$form['noteId']}'";
                $gen_db->update("stickynote_info", $data, $where);
                $resArr = array(
                    'content' => $content,
                    'status' => 'success'
                );
                break;

            case 'del':
                // 権限チェック
                $query = "
                select
                    allow_edit_all_user or user_id = '{$_SESSION['user_id']}' as allowEdit
                from
                    stickynote_info
                where
                    stickynote_id = '{$form['noteId']}'
                ";
                if ($gen_db->queryOneValue($query) == 'f')
                    return;

                $query = "delete from stickynote_info where stickynote_id = '{$form['noteId']}'";
                $gen_db->query($query);
                $resArr = array('status' => 'success');
                break;

            case 'mov':
                if (!is_numeric($form['x_pos']) || !is_numeric($form['y_pos']))
                    return;
                if ($form['y_pos'] < 0)
                    $form['y_pos'] = 0;   // ブラウザ外対応
                $data = array("x_pos" => $form['x_pos'], "y_pos" => $form['y_pos']);
                $where = "stickynote_id = '{$form['noteId']}'";
                $gen_db->update("stickynote_info", $data, $where);
                $resArr = array('status' => 'success');
                break;

            case 'resize':
                if (!is_numeric($form['width']) || !is_numeric($form['height']))
                    return;
                $data = array("width" => $form['width'], "height" => $form['height']);
                if (!is_numeric($form['x_pos']))
                    $data['x_pos'] = 0;    // ブラウザ外対応
                if (!is_numeric($form['y_pos']) || $form['y_pos'] < 0)
                    $data['y_pos'] = 0;    // ブラウザ外対応
                $where = "stickynote_id = '{$form['noteId']}'";
                $gen_db->update("stickynote_info", $data, $where);
                $resArr = array('status' => 'success');
                break;

            default:
                return;
        }

        $gen_db->commit();

        return $resArr;
    }

}