<?php

class Gen_Chat
{
    // ログイン時（正確にはログイン前。ログイン失敗時も実行）の、ecomチャット関連の処理
    static function ecomChatAutoUpdate()
    {
        global $gen_db;

        // ecomチャットバージョンを取得
        $query = "select ecom_chat_version from company_master";
        $dbEcomChatVer = $gen_db->queryOneValue($query);
        if ($dbEcomChatVer === null) {
            $dbEcomChatVer = 0;
        } else {
            $dbEcomChatVer = (int) $dbEcomChatVer;
        }

        // ecomチャットファイルを読み込み
        $ecomChatFile = dirname(dirname(dirname(__FILE__))) . '/gen_ecom_chat.yml';
        if (!file_exists($ecomChatFile)) {
            // gen_ecom_chat.yml がない
            return;
        }
        $ecomChat = Spyc::YAMLLoad($ecomChatFile);

        // 現行ecomチャットバージョンを取得
        ksort($ecomChat, SORT_NUMERIC);    // キー昇順でソート
        end($ecomChat);
        list($ecomChatVer, $dummy) = each($ecomChat);
        if (!is_numeric($ecomChatVer)) {
            throw new Exception('gen_ecom_chat.yml の ecom_chat_version が数字ではありません。');
        }
        reset($ecomChat);

        // 現行ecomチャットバージョンよりデータのecomチャットバージョンが古ければ、更新処理を行う
        if ($dbEcomChatVer < $ecomChatVer) {
            Gen_Chat::_updateEcomChat($ecomChat, $dbEcomChatVer);
        }
    }

    // 上記のsum（ecomチャットを更新）
    private static function _updateEcomChat($ecomChat, $dbEcomChatVer)
    {
        global $gen_db;

        $gen_db->begin();

        // スレッドが存在しない場合は作成
        $query = "select chat_header_id from chat_header where is_ecom";
        $headerId = $gen_db->queryOneValue($query);
        if (!$headerId) {
            $firstChat = reset($ecomChat);
            $data = array(
                "title" => _g("イー・コモードからのお知らせ"),
                "author_id" => -1,
                "create_time" => $firstChat['chat_time'],
                "is_ecom" => true,
            );
            $gen_db->insert("chat_header", $data);
            $headerId = $gen_db->getSequence("chat_header_chat_header_id_seq");

            // ユーザーリスト（chat_userテーブル）
            // これ以後に追加されたアカウントについては、ユーザー作成のところで追加している
            $query = "insert into chat_user (chat_header_id, user_id) select {$headerId}, user_id from user_master";
            $gen_db->query($query);
            $query = "insert into chat_user (chat_header_id, user_id) values ({$headerId}, -1)";    // admin
            $gen_db->query($query);
        }

        // $ecomChat配列はキー昇順に並び替えられている
        $lastVer = '';
        foreach ($ecomChat as $ecomChatVer => $ecomChat) {
            if ($ecomChatVer > $dbEcomChatVer) {
                $lastVer = $ecomChatVer;

                if (isset($ecomChat['delete'])) {
                    $deleteArr = explode(",", $ecomChat['delete']);
                    foreach($deleteArr as $delete) {
                        if (is_numeric($delete)) {
                            $query = "delete from chat_detail where ecom_chat_key = '{$delete}'";
                            $gen_db->query($query);
                        }
                    }
                    continue;
                }
                $dbContent = $ecomChat['content'];
                $dbContent = str_replace(array("\r\n","\r","\n"), "[br]", $dbContent);
                $dbContent = str_replace("'", "[gen_quot]", $dbContent);    // 「'」を「[gen_quot]」に。表示時にもとに戻す
                $dbContent = $gen_db->quoteParam($dbContent);               // DBサニタイジング
                $data = array(
                    "chat_header_id" =>$headerId,
                    "user_id" => -2,
                    "chat_time" => $ecomChat['chat_time'],
                    "content" => $dbContent,
                    "ecom_chat_key" => $ecomChatVer,
                );
                $gen_db->insert("chat_detail", $data);
            }
        }
        if ($lastVer != '') {
            $query = "update company_master set ecom_chat_version = {$lastVer}";
            $gen_db->query($query);
        }

        $gen_db->commit();
    }

    // ログイン時の、通知センター関連の処理
    static function initSystemChat()
    {
        global $gen_db;

        $maxRecord = 50;

        // 通知センタースレッドがなければ作成する
        $userId = Gen_Auth::getCurrentUserId();
        $query = "
            select
                chat_header.chat_header_id
            from
                chat_header
            where
                is_system and author_id = '{$userId}'
        ";
        $headerId = $gen_db->queryOneValue($query);
        if ($headerId) {
            // 通知センタースレッドがある場合：　最新の50件以外は削除する
            //  件数制限処理は本来は通知登録のたびにやったほうがいいが、通知登録はできるだけ短時間で
            //  終えたい場合があるので、ログイン時にまとめて行う）

            $query = "
                delete from
                    chat_detail
                where
                    chat_time <
                        (select
                            min(chat_time)
                         from
                            (select
                                chat_time
                             from
                                chat_detail
                             where
                                chat_header_id = '{$headerId}'
                             order by
                                chat_time desc
                             limit {$maxRecord}
                             ) as t1
                        )
                    and chat_header_id =  '{$headerId}';
            ";
            $gen_db->query($query);
        } else {
            // 通知センタースレッドがない場合：　作成する

            $data = array(
                "title" => _g("GENESISS 通知センター"),
                // 自分自身をオーナーとする（ただしスレッド操作はUI的にできないようになっている）。
                // このIDで、どのユーザーの通知センターかが判断される。
                "author_id" => $userId,
                "create_time" => date('Y-m-d H:i:s'),
                "is_system" => true,
            );
            $gen_db->insert("chat_header", $data);
            $headerId = $gen_db->getSequence("chat_header_chat_header_id_seq");

            $data = array(
                "chat_header_id" =>$headerId,
                "user_id" => $userId,
            );
            $gen_db->insert("chat_user", $data);

            $data = array(
                "chat_header_id" =>$headerId,
                "user_id" => -3,  // Config_Setting_AjaxChat でこの番号を使用
                "chat_time" => date('Y-m-d H:i:s'),
                "content" => sprintf(_g("このスレッドにはジェネシスからの各種通知が表示されます。（最新の%s件）"),$maxRecord) . "<br><br>" ._g("このスレッドには書き込みできません。"),
            );
            $gen_db->insert("chat_detail", $data);
        }
    }

    // 通知センターへの書き込み。
    //  $users（ユーザーID。複数の場合はカンマ区切り）が指定されている場合、そのユーザーの通知センターに書き込まれる。
    //  $users がfalseの場合、現在存在するすべてのユーザーの通知センターに書き込まれる。
    static function writeSystemChat($users, $content) {
        global $gen_db;

        $query = "
            insert into chat_detail (chat_header_id, user_id, chat_time, content)
            select
                chat_header_id
                ,-3 as user_id
                ,'" . (date('Y-m-d H:i:s')) . "'
                ,'{$content}'
            from
                chat_header
            where
                is_system
        ";
        if ($users) {
            $users = $gen_db->quoteParam($users);
            $query .= " and author_id in ({$users})";
        }
        $gen_db->query($query);
    }
}
