<?php

class Logic_Chat
{
    static function deleteChat($headerId)
    {
        // $headerIdの妥当性チェック、削除権限チェックは呼び出し元ですること
        
        global $gen_db;
        
        $userId = Gen_Auth::getCurrentUserId();
        
        // 添付ファイルリストの取得    
        $query = "select file_name from chat_detail where chat_header_id = '{$headerId}'";
        $arr = $gen_db->getArray($query);

        // スレッドの削除
        $query = "
            delete from chat_header where chat_header_id = '{$headerId}';
            delete from chat_detail where chat_header_id = '{$headerId}';
            delete from chat_user where chat_header_id = '{$headerId}';
            ";
        $gen_db->query($query);

        // 添付ファイルの削除。念のため delete が成功してから行う
        if ($arr) {
            $storage = new Gen_Storage("ChatFiles");
            foreach($arr as $row) {
                if ($row['file_name'] != "") {
                    $storage->delete($row['file_name']);
                }
            }
        }

        $where = ($userId == -1 ? "" : "where user_id = '{$userId}'");
        $table = ($userId == -1 ? "company_master" : "user_master");
        // アトリビュートは更新しない
        $query = "update {$table} set last_chat_header_id = null {$where}";
        $gen_db->query($query);
    }
    
    // Mobile Push Notification 
    //  via AWS SDK for PHP 1 (PHP5.2). AWS SDKの詳細は Components/S3.class.php 冒頭
    static function pushNotification($headerId, $content) 
    {
        global $gen_db;
        
        $query = "
            select 
                endpoint_arn
                ,max(app_device_token.user_id) as user_id
            from
                chat_user
                inner join app_device_token on chat_user.user_id = app_device_token.user_id
            where
                chat_user.chat_header_id = '{$headerId}'
            group by
                endpoint_arn
        ";
        $endpointArnArr = $gen_db->getArray($query);
        if ($endpointArnArr) {
            require_once(ROOT_DIR."aws/sdk.class.php");
            $sns = new AmazonSNS();
            $sns->set_region(AmazonSNS::REGION_APAC_NE1);
            $brContent = str_replace("[br]"," ",$content);
            $query = "select title from chat_header where chat_header_id = '{$headerId}'";
            $title = $gen_db->queryOneValue($query);
            $userId = Gen_Auth::getCurrentUserId();
            foreach($endpointArnArr as $endpointRow) {
                if ($endpointRow['user_id'] == $userId) {
                    // 自分自身には通知しない
                    continue;
                }
                $unreadArr = self::getUnreadCount($endpointRow['user_id']);
                $unread = $unreadArr[0] + $unreadArr[1] + $unreadArr[2];
                $payload = json_encode(
                    array(
                        "aps" => array(
                            // alertを省略するとサイレント通知になる。バッジだけ出したり、バックグラウンドで更新するときに使う
                            "alert" => $_SESSION['user_name'] . ": " . $title . "\n" . $brContent,
                            "sound" => "default", 
                            "badge" => $unread,
                            // この項目を設定しておくとバックグラウンド更新が有効になる（didReceiveRemoteNotificationが呼ばれる）
                            "content-available" => 1
                         ),
                        "chatHeaderId" => $headerId
                    )
                );

                // publish_for_endpoint は Genオリジナル関数（aws/services/sns.class.php）
                $sns->publish_for_endpoint(
                    $endpointRow['endpoint_arn'],
                    json_encode(array("default"=>"default", "APNS_SANDBOX"=>$payload, "APNS"=>$payload)),
                    array('MessageStructure'=>"json")
                );
            }
        }
    }
    
    static function getUnreadCount($userId) 
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
