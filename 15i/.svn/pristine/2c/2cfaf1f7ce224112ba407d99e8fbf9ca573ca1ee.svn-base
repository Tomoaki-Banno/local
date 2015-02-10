<?php

class Master_User_Model extends Base_ModelBase
{

    protected function _getKeyColumn()
    {
        return 'user_id';
    }

    protected function _setDefault(&$param, $entryMode)
    {
        // ユーザー名が空欄のときはユーザーIDと同じにする。
        if ($param['user_name'] === '' || $param['user_name'] === null) {
            $param['user_name'] = $param['login_user_id'];
        }
    }

    protected function _getColumns()
    {
        global $gen_db;

        $passwordMinLen = $gen_db->queryOneValue("select password_minimum_length from company_master");
        if (!is_numeric($passwordMinLen)) {
            $passwordMinLen = 100;
        }

        $columns = array(
            array(
                "column" => "user_id",
                "validate" => array(
                    array(
                        "cat" => "blankOrNumeric",
                        "msg" => _g('IDが正しくありません。'),
                        "skipValidatePHP" => "$1==''",
                        "skipValidateJS" => "true", // 画面上には存在しない項目
                    ),
                ),
            ),
            array(
                "column" => "account_lockout",
                "pattern" => "bool",
            ),
            array(
                "column" => "login_user_id",
                "convert" => array(
                    array(
                        "cat" => "trimEx",
                    ),
                ),
                // 「ユーザー指定できるが全体としてユニークでなければならない」値は、
                // validateでの重複チェックだけでなく、このlockNumberの指定が必要。
                // くわしくは ModelBase の lockNumber処理の箇所のコメントを参照。
                "lockNumber" => true,
                "validate" => array(
                    array(
                        "cat" => "required",
                        "msg" => _g('ユーザーIDを指定してください。')
                    ),
                    array(
                        "cat" => "notEqualString",
                        "msg" => _g('そのユーザーIDを指定することはできません。'),
                        "param" => ADMIN_LOGIN_ID
                    ),
                    // 新規登録時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('ユーザーIDはすでに使用されています。別のIDを指定してください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[user_id]]!=''", // 修正はスキップ
                        "param" => "select user_id from user_master where login_user_id = $1"
                    ),
                    // 修正時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('ユーザーIDはすでに使用されています。別のIDを指定してください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[user_id]]==''", // 新規登録はスキップ
                        // 更新のときは、自分自身の番号をチェック対象としない（自分自身と重複するのは当然）
                        "param" => "select user_id from user_master where login_user_id = $1
                            and user_id <> [[user_id]]"
                    ),
                ),
            ),
            array(
                "column" => "user_name",
                "convert" => array(
                    array(
                        "cat" => "trimEx",
                    ),
                ),
                // 「ユーザー指定できるが全体としてユニークでなければならない」値は、
                // validateでの重複チェックだけでなく、このlockNumberの指定が必要。
                // くわしくは ModelBase の lockNumber処理の箇所のコメントを参照。
                "lockNumber" => true,
                "validate" => array(
                    array(
                        "cat" => "notEqualString",
                        "msg" => _g('そのユーザー名を指定することはできません。'),
                        "param" => ADMIN_NAME
                    ),
                    // 新規登録時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('ユーザー名はすでに使用されています。別の名前を指定してください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[user_id]]!=''", // 修正はスキップ
                        "param" => "select user_id from user_master where user_name = $1"
                    ),
                    // 修正時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('ユーザー名はすでに使用されています。別の名前を指定してください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[user_id]]==''", // 新規登録はスキップ
                        // 更新のときは、自分自身の番号をチェック対象としない（自分自身と重複するのは当然）
                        "param" => "select user_id from user_master where user_name = $1
                            and user_id <> [[user_id]]"
                    ),
                ),
            ),
            array(
                "column" => "password",
                "dependentColumn" => "password2",
                "validate" => array(
                    // パスワードは新規モードでは必須だが、修正モードでは空欄OK（変更しないときは空欄にする）。
                    // 09iでは必須だったが、10iではパスワードのハッシュ化にともない元パスワードを表示
                    // できなくなったので、このような仕様にした。
                    array(
                        "cat" => "required",
                        "msg" => _g('パスワードを入力してください。'),
                        // 新規のみ必須、修正は空欄OK。user_idが画面上にないので、JSでは新規と修正を判別できず、チェックできない。
                        // どうせ新規登録できるのはadminだけなので、サーバーチェックのみでもよしとした
                        "skipValidateJS" => "true",
                        "skipValidatePHP" => "[[user_id]]!=''",
                    ),
                    array(
                        "cat" => "minLength",
                        "msg" => sprintf(_g('パスワードは%s文字以上にしてください。'), $passwordMinLen),
                        "skipValidateJS" => "$1===''",
                        "skipValidatePHP" => "$1===''",
                        "param" => $passwordMinLen,
                    ),
                ),
            ),
            array(
                "column" => "password2",
                "dependentColumn" => "password",
                "validate" => array(
                    array(
                        "cat" => "eval",
                        "msg" => _g('パスワードが確認入力と一致していません。'),
                        "evalPHP" => "\$res=($1==[[password]]);",
                        "evalJS" => "res=($1==[[password]]);"
                    ),
                ),
            ),
            array(
                "column" => "section_id",
                "pattern" => "section_id",
            ),
            array(
                "column" => "start_action",
                "validate" => array(
                    array(
                        "cat" => "required",
                        "msg" => _g('ログイン後に表示する画面を指定してください。')
                    ),
                    array(
                        "cat" => "eval",
                        "msg" => _g('ログイン後に表示する画面の指定が正しくありません。'),
                        "skipHasError" => true,
                        // パラメータつきActionに対応。パラメータ部分は削除してチェックする
                        "evalPHP" => "\$act=preg_replace('/&.*$/', '', str_replace('_', '/', $1));\$res=file_exists(APP_DIR.\$act.'.class.php');",
                    ),
                ),
            ),
            array(
                "column" => "customer_id",
                "pattern" => "customer_id", // null ok
                "label" => _g("取引先"),
                "addwhere" => "classification in (0,1)",
            ),
        );

        return $columns;
    }

    protected function _regist(&$param, $isFirstRegist)
    {
        global $gen_db;

        // ------------------------------------------------
        //  user_master の登録
        // ------------------------------------------------
        // 取引先ユーザー
        $isCustomerUser = (isset($param['customer_id']) && is_numeric($param['customer_id']) && $param['customer_id'] != "0");
        
        // 新規登録の判断
        $isNew = (!isset($param['user_id']) || !is_numeric($param['user_id']));
        
        // 機能制限ユーザー
        $isRestrictedUser = false;
        if ($isNew) {
            // 新規モード：　操作ユーザーがadminなら一般、一般ユーザーなら機能限定
            if (Gen_Auth::getCurrentUserId() != -1) {
                $isRestrictedUser = true;
            }
        } else {
            // 編集モード：　編集対象ユーザーが機能限定ユーザーかどうかで判断
            $query = "select restricted_user from user_master where user_id = '{$param['user_id']}'";
            $res = $gen_db->queryOneValue($query);
            if ($res == "t") {
                $isRestrictedUser = true;
            }
        }

        // ユーザー登録
        if ($isNew) {
            $key = null;
        } else {
            $key = array("user_id" => $param['user_id']);
        }
        $data = array(
            'login_user_id' => $param['login_user_id'],
            'user_name' => $param['user_name'],
            'company_id' => 1,
            'account_lockout' => $param['account_lockout'],
            'start_action' => $param['start_action'],
            'language' => $param['language'],
            'customer_id' => ($isCustomerUser ? $param['customer_id'] : null),
            'section_id' => $param['section_id'],
            'restricted_user' => $isRestrictedUser ? 'true' : 'false',
        );

        // パスワードの登録。
        // パスワードは修正モードでは空欄OK
        if (isset($param['password']) && $param['password'] !== "") {
            // ハッシュ化して登録する。
            // 単なるパスワードのハッシュ化だと、頻出ワードに対する ハッシュ値 -> 元値 の変換辞書（レインボーテーブル）を
            // 使用した攻撃で破られる可能性がある。そのため、ランダム値（Salt）を付加してからハッシュ化する。
            // Saltはパスワードといっしょに格納しておき、ログイン照合時に使用する。
            // これだとパスワードとSaltを同時に盗まれる可能性があるが、その場合も攻撃者は盗んだSaltを付加した辞書を独自に
            // 作成しなければならないので、危険は小さい。
            $salt = Gen_String::makeRandomString(10);
            $data['password'] = hash('sha256', $salt . $param['password']) . ',' . $salt;
        }

        // 新規の場合かパスワードが変更されている場合、パスワード最終更新日の登録を行う
        if (!isset($param['user_id']) || @$param['password'] !== "") {
            $data['last_password_change_date'] = date('Y-m-d H:i:s');
        }

        $gen_db->updateOrInsert('user_master', $key, $data);
        
        // 新規登録のときはユーザーIDが未知なので取得
        $userId = @$param['user_id'];
        if (!is_numeric($userId)) {
            $userId = $gen_db->getSequence("user_master_user_id_seq");
        }
        
        // e-comチャットに対する権限
        $query = "select chat_header_id from chat_header where is_ecom";
        $headerId = $gen_db->queryOneValue($query);
        if ($headerId) {
            $key = array(
                "chat_header_id" => $headerId,
                "user_id" => $userId,
            );
            $data = array();
            $gen_db->updateOrInsert('chat_user', $key, $data);
        }    
        
        // ------------------------------------------------
        //  パーミッションの登録
        // ------------------------------------------------
        // まずすべての画面に対する既存のアクセス権を削除する
        if (isset($param['user_id']) && is_numeric($param['user_id'])) {
            $query = "delete from permission_master where user_id = '{$param['user_id']}'";
            $gen_db->query($query);
        }

        // アクセス権を登録する。
        // 不正アクセス防止のため、登録できるクラス名を限定する
        if ($isCustomerUser) {
            // 取引先ユーザー
            $obj = new Logic_Menu();
            $menuArr = $obj->getCustomerMenuArray($param['customer_id']);

            $classGroupArray = array();
            foreach ($menuArr as $menuGroup) {
                foreach ($menuGroup as $menu) {
                    if ($menu[4] === true || $param['form'][strtolower($menu[0])] != "0") {
                        $data = array(
                            'user_id' => $userId,
                            'class_name' => strtolower($menu[0]),
                            'permission' => ($menu[4] === true ? ($menu[3] == 2 ? 1 : 2) : $param['form'][strtolower($menu[0])]),
                        );
                        $gen_db->insert('permission_master', $data);
                    }
                }
            }
        } else {
            // 一般/機能制限ユーザー
            $obj = new Logic_Menu();
            $menuArr = $obj->getMenuArray(false, $isRestrictedUser);

            $classGroupArray = array();
            foreach ($menuArr as $menuGroup) {
                foreach ($menuGroup as $menu) {
                    $classGroupArray[] = strtolower($menu[0]);
                }
            }

            // 登録
            // $param['form'] にはPOSTされた$form配列の内容がそのまま入っている。
            //  Entryクラス参照。
            foreach ($param['form'] as $classGroup => $value) {
                // 不正アクセス防止のため、登録可能リスト配列に挙がっていないクラスは登録しない。またアクセス禁止のクラスは登録しない
                if (in_array($classGroup, $classGroupArray) && $value <> "0") {
                    $data = array(
                        'user_id' => $userId,
                        'class_name' => $classGroup,
                        'permission' => $value,
                    );
                    $gen_db->insert('permission_master', $data);
                }
            }
        }
        
        // id(keyColumnの値)を戻す。keyColumnがないModelではfalseを戻す。
        return $userId;
    }

}
