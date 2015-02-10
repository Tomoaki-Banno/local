<?php

class Config_AdminUser_AjaxAddUser extends Base_AjaxBase
{
    function _execute(&$form)
    {
        global $gen_db;

        if (!isset($form['qty']) || !is_numeric($form['qty']))
            return;
        if (!isset($form['len']) || !is_numeric($form['len']))
            return;

        $qty = $form['qty'];    // ユーザー数
        $len = $form['len'];    // パスワード桁数
        $msg = "\n\n";

        // ユーザー数を取得
        $query = "select count(*) as cnt from user_master";
        $maxNo = $gen_db->queryOneValue($query);
        if (!isset($maxNo) || !is_numeric($maxNo)) $maxNo = 0;

        $gen_db->begin();

        // ------------------------------------------------
        // ユーザー登録
        // ------------------------------------------------
        $idArr = array();
        for ($i=1; $i<=$qty; $i++) {
            // ユーザー名生成
            $maxNo++;
            if ($maxNo<10) {
                $user_name = 'u' . sprintf("%02d", $maxNo);
            } else {
                $user_name = 'u' . $maxNo;
            }

            // パスワード生成
            $password = self::createPassword($len);

            // ハッシュ化して登録する。
            // 単なるパスワードのハッシュ化だと、頻出ワードに対する ハッシュ値 -> 元値 の変換辞書（レインボーテーブル）を
            // 使用した攻撃で破られる可能性がある。そのため、ランダム値（Salt）を付加してからハッシュ化する。
            // Saltはパスワードといっしょに格納しておき、ログイン照合時に使用する。
            // これだとパスワードとSaltを同時に盗まれる可能性があるが、その場合も攻撃者は盗んだSaltを付加した辞書を独自に
            // 作成しなければならないので、危険は小さい。
            $salt =Gen_String::makeRandomString(10);
            $pwd = hash('sha256', $salt . $password) . ',' . $salt;

            // 登録処理
            $data = array(
                'login_user_id' => $user_name,
                'user_name' => $user_name,
                'password' => $pwd,
                'company_id' => 1,
                'account_lockout' => 'false',
                'start_action' => $form['act'],
                'language' => $form['lang'],
                'customer_id' => null,
            );
            $gen_db->insert('user_master', $data);

            // id取得
            $newID = $gen_db->getSequence("user_master_user_id_seq");
            $idArr[] = $newID;

            // e-comチャットに対する権限
            $query = "select chat_header_id from chat_header where is_ecom";
            $headerId = $gen_db->queryOneValue($query);
            if ($headerId) {
                $query = "insert into chat_user (chat_header_id, user_id) values ({$headerId}, {$newID})";
                $gen_db->query($query);
            }

            $msg .= "{$user_name} : {$password}\n";
        }

        // ------------------------------------------------
        //  パーミッションの登録
        // ------------------------------------------------
        foreach ($idArr as $value) {
            // 既存のアクセス権を削除
            $query = "delete from permission_master where user_id = '{$value}'";
            $gen_db->query($query);

            // 一般ユーザー
            $menu = new Logic_Menu();
            $menuArr = $menu->getMenuArray();

            $entryArr = array();
            foreach ($menuArr as $menuGroup) {
                foreach ($menuGroup as $menu) {
                    if (!in_array(strtolower($menu[0]), $entryArr)) {
                        $data = array(
                            'user_id' => $value,
                            'class_name' => strtolower($menu[0]),
                            'permission' => ($menu[3]==2 ? 1 : 2),
                        );
                        $gen_db->insert('permission_master', $data);
                        $entryArr[] = strtolower($menu[0]);
                    }
                }
            }
        }

        // データアクセスログ
        Gen_Log::dataAccessLog(_g("ユーザー追加"), _g("追加"), _g("追加数")."：".$qty);

        $gen_db->commit();

        $obj = array(
            'result' => 'success',
            'msg' => $msg,
        );

        return $obj;
    }

    private function createPassword($length = 6)
    {
       $strArr = array(
            "sletter" => range('a', 'z'),
            "cletter" => range('A', 'Z'),
            "number"  => range('0', '9'),
            //"symbol"  => array_merge(range('!', '/'), range(':', '?'), range('{', '~')),
            "symbol"  => array('#', '$', '%', '&'),
        );

        $pwd = array();

        while (count($pwd) < $length) {
            // 4種類必ず入れる
            if (count($pwd) < 4) {
                $key = key($strArr);
                next($strArr);
            } else {
                // 後はランダムに取得
                $key = array_rand($strArr);
            }
            $pwd[] = $strArr[$key][array_rand($strArr[$key])];
            // 重複文字を削除
            $pwd = array_unique($pwd);
        }

        // 生成したパスワードの順番をランダムに並び替え
        shuffle($pwd);
        shuffle($pwd);

        return implode($pwd);
    }
}
