<?php

class Config_Setting_AjaxDesktopNotification extends Base_AjaxBase
{

    function _execute(&$form)
    {
        global $gen_db;
        
        $userId = Gen_Auth::getCurrentUserId();
        
        switch ($form['op']) {
            case 'get':
                // デスクトップ通知用の情報取得。

                // 前回の通知から規定の時間が経過していないときは、デスクトップ通知を行わないようにする。
                // 複数タブを開いているときに通知が出まくるのを防ぐため。
                $msg = "";
                if (!isset($_SESSION['gen_last_desktop_notification']) 
                        || $_SESSION['gen_last_desktop_notification'] < strtotime('-' . GEN_DESKTOP_NOTIFICATION_SPAN . 'min')) {

                    $_SESSION['gen_last_desktop_notification'] = time();
                    $msgArr = array();
                    
                    // ----- トークボード未読件数 -----
                    if (isset($_SESSION['gen_setting_user']->desktopNotification_chat)
                            && $_SESSION['gen_setting_user']->desktopNotification_chat) {
                        $unreadArr = self::_getChatUnreadCount($userId);
                        if ($unreadArr[0] > 0) {
                            $msgArr[] = _g("トークボード") . " : " . $unreadArr[0];
                        }
                        if ($unreadArr[1] > 0) {
                            $msgArr[] = _g("イー・コモードからのお知らせ") . " : " . $unreadArr[1];
                        }
                    }
                    
                    // デスクトップ通知するカテゴリを増やすときはここに追加
                    
                    // 結果
                    if (count($msgArr) > 0) {
                        $msg = join("[br]", $msgArr);
                    }
                }
                return array("msg" => $msg);
                
            case 'change':
                // デスクトップ通知状態の変更。
                $name = "";
                switch($form['cat']) {
                    // ----- トークボード未読件数 -----
                    case 'chat':
                        $name = "desktopNotification_chat";
                        break;
                    
                    // デスクトップ通知するカテゴリを増やすときはここに追加
                    
                    default:
                        return;
                }
                $val = false;
                if (isset($form['val']) && $form['val'] == 'true') {
                    $val = true;
                }
                $_SESSION['gen_setting_user']->$name = $val;
                Gen_Setting::saveSetting();
        }
        
    }
    
    // トークボード未読件数の取得
    private function _getChatUnreadCount($userId)
    {
        global $gen_db;
        
        $query = "
        select 
            count(case when not coalesce(chat_header.is_ecom,false) and not coalesce(chat_header.is_system,false) then chat_detail.chat_detail_id end) as unread
            ,count(case when chat_header.is_ecom then chat_detail.chat_detail_id end) as unread_ecom
            ,count(case when chat_header.is_system then chat_detail.chat_detail_id end) as unread_system
        from 
            chat_user
            inner join chat_detail on chat_user.chat_header_id = chat_detail.chat_header_id 
                and coalesce(chat_user.readed_chat_detail_id,-1) < chat_detail.chat_detail_id 
                and chat_detail.user_id <> '{$userId}'
            inner join chat_header on chat_user.chat_header_id = chat_header.chat_header_id
        where
            chat_user.user_id = '{$userId}'
        ";

        $chatUnreadObj = $gen_db->queryOneRowObject($query);
        return array($chatUnreadObj->unread, $chatUnreadObj->unread_ecom, $chatUnreadObj->unread_system);
    }
}