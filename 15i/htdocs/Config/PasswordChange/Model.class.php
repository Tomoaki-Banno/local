<?php

class Config_PasswordChange_Model extends Base_ModelBase
{

    protected function _getKeyColumn()
    {
        // キー項目がない場合、常に新規として扱いたければtrue、修正として扱いたければfalseを返す。
        return false;
    }

    protected function _setDefault(&$param, $entryMode)
    {
    }

    protected function _getColumns()
    {
        global $gen_db;

        $passwordMinLen = $gen_db->queryOneValue("select password_minimum_length from company_master");
        $beforePassword = $gen_db->queryOneValue("select password from user_master where user_id = '" . Gen_Auth::getCurrentUserId() . "'");
        $pwArr = explode(',', $beforePassword);
        if (count($pwArr) > 1) {
            // Saltが付加されている場合。Saltについては Master_User_Model のパスワード登録部のコメントを参照
            $beforePassword = $pwArr[0];
            $salt = $pwArr[1];
        } else {
            // Salt導入前に格納されたPW
            $salt = '';
        }

        $columns = array(
            array(
                "column" => "now_password",
                "validate" => array(
                    array(
                        "cat" => "required",
                        "msg" => _g('現在のパスワードを指定してください。'),
                    ),
                    // 現在のパスワードが合致するかどうか。
                    // Webブラウザを共有している場合や、成りすましされてしまった場合に、
                    // パスワードが更新されてしまわないためにチェックする。
                    array(
                        "cat" => "eval",
                        "msg" => _g('現在のパスワードが正しくありません。'),
                        "skipHasError" => true,
                        "evalPHP" => "\$res=(hash('sha256','{$salt}'.$1)==='{$beforePassword}')",
                    ),
                ),
            ),
            array(
                "column" => "password",
                "dependentColumn" => "password2",
                "validate" => array(
                    array(
                        "cat" => "required",
                        "msg" => _g('新しいパスワードを指定してください。'),
                    ),
                    array(
                        "cat" => "minLength",
                        "msg" => sprintf(_g('パスワードは%s文字以上にしてください。'), $passwordMinLen),
                        "skipHasError" => true,
                        "param" => $passwordMinLen,
                    ),
                    array(
                        "cat" => "eval",
                        "msg" => _g('新しいパスワードが確認入力と一致していません。'),
                        "skipHasError" => true,
                        "evalPHP" => "\$res=($1===[[password2]])",
                        "evalJS" => "res=($1===[[password2]])",
                    ),
                    // パスワードが変更されているかどうか。
                    // このチェックは必須。パスワード有効期限切れの場合に、変更せずに済んでしまうことを避けるため。
                    // クライアントサイドにパスワード文字列が送られてしまうのをさけるため、evalでサーバー側のみのチェックとする。
                    array(
                        "cat" => "eval",
                        "msg" => _g('これまでと同じパスワードを指定することはできません。'),
                        "skipHasError" => true,
                        "evalPHP" => "\$res=(hash('sha256','{$salt}'.$1)!=='{$beforePassword}')",
                    ),
                ),
            ),
        );

        return $columns;
    }

    protected function _regist(&$param, $isFirstRegist)
    {
        global $gen_db;

        // パスワードはハッシュ化して登録する。
        // Saltについては Master_User_Model のパスワード登録部のコメントを参照
        $salt = Gen_String::makeRandomString(10);
        $password = hash('sha256', $salt . $param['password']) . ',' . $salt;
        $data = array(
            "password" => $password,
            "last_password_change_date" => date('Y-m-d H:i:s')
        );
        $where = "user_id = '{$param['user_id']}'";
        $gen_db->update("user_master", $data, $where);

        // パスワード有効期限切れでログイン画面から転送されてきたときのために、
        // ログイン状態を正式ログインに変更しておく
        Gen_Auth::changeLoginState("", false);
        $_SESSION['last_access'] = time();
        
        // id(keyColumnの値)を戻す。keyColumnがないModelではfalseを戻す。
        return false;
    }

}
