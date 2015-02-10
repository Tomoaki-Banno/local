<?php

define("SESSION_ID_LENGTH", 30);    // セッションIDの桁数
define("SESSION_LIMIT_HOUR", 48);   // セッション有効期限

class Gen_Auth
{

    // ログイン処理
    static function login($loginUserId, $password, $isExcel, $deviceToken = false, $isDeviceTokenDev = false)
    {
        global $gen_db;

        // サニタイジング処理
        $loginUserId = $gen_db->quoteParam($loginUserId);

        if ($loginUserId == ADMIN_LOGIN_ID) {
            // 管理者ユーザー
            $user_id = -1;
            $company_id = 1;
            $user_name = ADMIN_NAME;

            // パスワードはsha256でハッシュ化されて格納されている。
            $server_password = $gen_db->queryOneValue("select admin_password from company_master");
            $pwArr = explode(',', $server_password);
            if (count($pwArr) > 1) {
                // Saltが付加されている場合。Saltについては Master_User_Model のパスワード登録部のコメントを参照
                $server_password = $pwArr[0];
                $salt = $pwArr[1];
            } else {
                // Salt導入前に格納されたPW
                $salt = '';
            }
            // adminパスワードは最後に+(記号)と現在月2桁を加える
            $server_password .= '+' . date('m');
            $post_password = hash('sha256', $salt . substr($password, 0, strlen($password) - 3)) . substr($password, strlen($password) - 3);
        } else {
            // 一般ユーザー
            $query = "
                select
                    user_id
                    ,user_name
                    ,password
                    ,company_id
                    ,last_password_change_date
                    ,account_lockout
                    ,password_miss_count
                    ,password_miss_time
                    ,customer_id
                from
                    user_master
                    left join (select customer_id as cid, classification
                        from customer_master) as t_customer on user_master.customer_id = t_customer.cid
                where
                    login_user_id = '{$loginUserId}'
            ";
            $userData = $gen_db->queryOneRowObject($query);
            if ($userData) {
                // アカウントロック処理
                if ($userData->account_lockout == 't') {
                    return 4;   // アカウントロック
                }

                // 取引先ユーザー情報
                $customer_id = $userData->customer_id;

                // パスワード等の読み出し
                $user_id = $userData->user_id;
                $company_id = $userData->company_id;
                $last_password_change_date = $userData->last_password_change_date;
                $user_name = $userData->user_name;

                // パスワードはsha256でハッシュ化されて格納されている。
                $pwArr = explode(',', $userData->password);
                if (count($pwArr) > 1) {
                    // Saltが付加されている場合。Saltについては Master_User_Model のパスワード登録部のコメントを参照
                    $server_password = $pwArr[0];
                    $salt = $pwArr[1];
                } else {
                    // Salt導入前に格納されたPW
                    $server_password = $userData->password;
                    $salt = '';
                }
                $post_password = hash('sha256', $salt . $password);

                if ($server_password !== $post_password) {
                    // パスワードが間違っている場合
                    $query = "
                        select
                            coalesce(account_lockout_threshold,0) as threshold
                            ,coalesce(account_lockout_reset_minute,0) as reset_minute
                        from
                            company_master
                    ";
                    $companyData = $gen_db->queryOneRowObject($query);
                    // 失敗回数
                    if (!is_numeric($userData->password_miss_count)) {
                        $missCount = 1;
                    } else {
                        $missCount = $userData->password_miss_count + 1;
                    }
                    // 失敗回数リセット時間が経過していたら失敗回数をリセット
                    if (strtotime($userData->password_miss_time) + ($companyData->reset_minute) * 60 <= time()) {
                        $missCount = 1;
                    }
                    if ($companyData->threshold > 0 && $missCount >= $companyData->threshold) {
                        // アカウントロックアウト
                        $query = "update user_master set account_lockout = true, password_miss_count = 0 where user_id = '{$userData->user_id}'";
                    } else {
                        // パスワード連続間違いの回数と時刻を記録
                        $now = date('Y-m-d H:i:s');
                        $query = "update user_master set password_miss_count = '{$missCount}', password_miss_time = '{$now}' where user_id = '{$userData->user_id}'";
                    }
                    $gen_db->query($query);
                } else {
                    // パスワードが正しい場合。連続間違いの回数、時刻をリセット
                    $query = "update user_master set password_miss_count = 0, password_miss_time = null where user_id = '{$userData->user_id}'";
                    $gen_db->query($query);
                }
            } else {
                // 該当ユーザー名なし
                return false;
            }
        }

        // パスワードチェック
        if ($server_password !== $post_password) {
            return false;   // パスワード違い
        }

        // ヘッダ部表示用に、会社名をクッキーへキャッシュしておく
        $query = "select company_name from company_master ";
        $_SESSION["company_name"] = $gen_db->queryOneValue($query);

        // 前回ログイン時間と機能制限ユーザー区分を取得
        if ($user_id == -1) {
            $query = "select admin_last_login as last_login, false as restricted_user from company_master";
        } else {
            $query = "select last_login_date as last_login, restricted_user from user_master where user_id = '{$user_id}'";
        }
        $obj = $gen_db->queryOneRowObject($query);
        if ($obj) {
            $_SESSION["last_login"] = $obj->last_login;
            $_SESSION["restricted_user"] = $obj->restricted_user == "t";
        } else {
            $_SESSION["last_login"] = "0000-00-00 00:00:00";
        }

        // setting。設定項目をsession変数へ読み出し
        Gen_Setting::loadSetting($user_id);

        // Session Fixation攻撃への対策。
        //    ユーザーがログイン前に、「?PHPSESSID=xxx」を埋め込んだURLを踏まされると、PHPSESSIDが「xxx」に
        //    固定されてしまう。攻撃者はこのPHPSESSIDを使って容易にセッションをのっとることができる。
        //    その対策として、ログイン成功時に下の関数でPHPSESSIDを作り直しておく。
        session_regenerate_id(true);

        // セッションIDを発行
        $sessionId = Gen_Auth::_makeRandomString(SESSION_ID_LENGTH);

        // セッション変数（テーブル登録より前に）
        $_SESSION["session_id"] = $sessionId;
        $_SESSION["user_id"] = $user_id;
        $_SESSION["user_name"] = $user_name;
        $_SESSION["user_customer_id"] = (isset($customer_id) && is_numeric($customer_id) ? $customer_id : -1);

        unset($_SESSION['gen_page_request_id']);

        // そのユーザーの過去のセッション情報を削除。（adminを除く）
        // これにより、同じアカウントで複数のデバイス(PC)/ブラウザから同時に使用することができなくなる
        // ブラウザセッションとエクセルセッションは区別する（ブラウザとエクセルを同時に使用できるようにする）
        if (GEN_ALLOW_CONCURRENT_USE != 'true' && $user_id != -1) {
            $query = "delete from session_table where user_id = '{$user_id}' and company_id = '{$company_id}' and excel_flag = " . ($isExcel ? 'true' : 'false');
            $gen_db->query($query);
        }

        // セッションIDをテーブルに登録
        $key = array("session_id" => $sessionId);
        $data = array("login_date" => date("Y-m-d H:i:s"), "user_id" => $user_id, "company_id" => $company_id, "excel_flag" => ($isExcel ? 'true' : 'false'));
        $gen_db->updateOrInsert("session_table", $key, $data);

        // ユーザーマスタにログイン日時を記録
        if ($user_id == -1) {
            $data = array("admin_last_login" => date("Y-m-d H:i:s"));
            $gen_db->update("company_master", $data, "");
        } else {
            $data = array("last_login_date" => date("Y-m-d H:i:s"));
            $where = "user_id = {$user_id}";
            $gen_db->update("user_master", $data, $where);
        }
        
        // パスワード有効期限チェック
        $query = "select password_valid_until from company_master";
        $limitDays = $gen_db->queryOneValue($query);
        if (is_numeric($limitDays) && $limitDays != "0") {
            if (@$last_password_change_date != "") {
                $limit = strtotime("{$last_password_change_date} + {$limitDays} days");
                if ($limit < time()) {
                    // 有効期限切れ
                    $sql = "select permission from permission_master " .
                            " where (class_name = 'config_passwordchange' or class_name = 'Config_PasswordChange')" .
                            " and user_id = '{$user_id}'";

                    if ($gen_db->queryOneValue($sql) == 2) {
                        // ユーザーによるパスワード変更が許可されている場合
                        // 仮ログイン状態（パスワード変更画面のみアクセス可能）にする
                        Gen_Auth::changeLoginState($sessionId, true);
                        return 2;   // 仮ログイン（パスワード変更が必要）。パスワード変更画面へ
                    } else {
                        // パスワード変更が許可されていない場合
                        return 3;   // 「有効期限切れ」とログイン画面に表示
                    }
                }
            }
        }
        
        // Mobile Push Notification 
        //  via AWS SDK for PHP 1 (PHP5.2). AWS SDKの詳細は Components/S3.class.php 冒頭
        
        // Amazon SNS の applicationARN。いずれymlなどに切り出す
        if ($isDeviceTokenDev) {
            $AMAZON_SNS_APP_ARN = "arn:aws:sns:ap-northeast-1:934209047358:app/APNS_SANDBOX/Gen";   // 開発用（SANDBOX）
        } else {
            $AMAZON_SNS_APP_ARN = "arn:aws:sns:ap-northeast-1:934209047358:app/APNS/Genesiss";      // 本番用
        }
        
        if ($deviceToken) {
            $deviceToken = $gen_db->quoteParam($deviceToken);
            $query = "select device_token from app_device_token where device_token = '{$deviceToken}'";
            $dbDeviceToken = $gen_db->queryOneValue($query);
            if ($dbDeviceToken) {
                $gen_db->update("app_device_token", array("user_id" => $user_id), "device_token = '{$deviceToken}'");
            } else {
                require_once(ROOT_DIR."aws/sdk.class.php");
                $sns = new AmazonSNS();
                $sns->set_region(AmazonSNS::REGION_APAC_NE1);
                // すでに登録済みの場合もEndpointArnは正しく帰ってくる
                $res = $sns->create_platform_endpoint($AMAZON_SNS_APP_ARN, $deviceToken);
                $regSuccess = false;
                if (isset($res->body->CreatePlatformEndpointResult->EndpointArn)) {
                    $endpointArn = $res->body->CreatePlatformEndpointResult->EndpointArn;
                    if ($endpointArn && $endpointArn != "") {
                        $data = array(
                            "device_token" => $deviceToken,
                            "endpoint_arn" => $endpointArn,
                            "user_id" => $user_id,
                        );
                        $gen_db->insert("app_device_token", $data);
                        $regSuccess = true;
                    }
                }
                // 本来は登録失敗の場合（$regSuccess == false）にそのことをクライアントに知らせるべきだが、その処理は未実装。
                // ただし登録失敗しても、次回ログイン時に再び登録が試みられる。
            }
        }

        // 13i⇒15i移行時のための特別処理。
        // 年度開始月は自社情報を登録したときに保存される。
        // 移行後、まだ一度も自社情報を登録していないときに年度表示に不具合が出るのを防ぐ
        if (!isset($_SESSION['gen_setting_company']->starting_month_of_accounting_period)) {
            $_SESSION['gen_setting_company']->starting_month_of_accounting_period = 1;
            Gen_Setting::saveSetting();
        }
        
        $_SESSION["last_access"] = time();

        return 1;    // ログイン成功
    }

    // ログアウト処理
    static function logout($deviceToken = false)
    {
        global $gen_db;

        $sessionId = @$_SESSION["session_id"];
        if (strlen($sessionId) == SESSION_ID_LENGTH) {
            // ユーザーマスタにログアウト日時を記録
            $sql = "select user_id from session_table where session_id = '{$sessionId}'";
            $user_id = $gen_db->queryOneValue($sql);

            if (is_numeric($user_id)) {
                $data = array("last_logout_date" => date("Y-m-d H:i:s"));
                $where = "user_id = $user_id";
                $gen_db->update("user_master", $data, $where);
                
                // Mobile Push Notification 
                if ($deviceToken) {
                    $gen_db->update("app_device_token", array("user_id" => null), "device_token = '$deviceToken'");
                }
            }

            $sql = "delete from session_table where session_id = '{$sessionId}'";
            $gen_db->query($sql);
        }
 
       // 全てのセッションを解放
        session_unset();
        // セッション情報を初期化
        $_SESSION = array();
        // Cookie削除（なくても問題ないはずだが一応）
        setcookie(session_name(), '', time() - 3600, "/");
    }

    // セッションチェックとパーミッションの取得
    // 第一引数は、クラス名の第2階層まで（Master_Item_xxx なら Master_Item）
    // 戻り値  -1: セッション不正  0: アクセス権限なし  1: 読み取りのみ  2: 読み書き可能
    static function sessionCheck($classGroup)
    {
        global $gen_db;

        if (isset($_SESSION["session_id"])) {
            $sessionId = $_SESSION["session_id"];
        } else {
            // セッションクッキーが存在していない
            return -1;
        }

        if (strlen($sessionId) != SESSION_ID_LENGTH) {
            // セッション文字列の長さが不正
            return -1;
        }

        // セッションテーブルのチェックおよびパーミッション情報の取得
        $sql = "select login_date, permission, temp_flag " .
                "from session_table " .
                "left join (select user_id, permission from permission_master where class_name = '{$classGroup}') as t0 " .
                " on session_table.user_id = t0.user_id " .
                "where session_id = '{$sessionId}'";

        if (!$res = $gen_db->queryOneRowObject($sql)) {
            // セッションIDがテーブルにない
            return -2;
        }

        // 仮ログインのときはパスワード変更画面のみ許可
        if ($res->temp_flag == 1) {
            return (strtolower($classGroup) == "config_passwordchange" ? 2 : -1);
        }

        // タイムアウト処理（最終アクセスから一定時間経過したら自動ログアウト）
        if (!isset($_SESSION['last_access']) || time() - $_SESSION["last_access"] > GEN_SESSION_TIMEOUT) {
            self::logout();
            return -3;
        }
        $_SESSION["last_access"] = time();

        // 管理者ユーザーは全画面読み書き可能
        if ($_SESSION["user_id"] == "-1") {
            return 2;
        }

        // セッションOKのとき：パーミッションを返す
        switch ($res->permission) {
            case 1:         // 読み取りのみ
                return 1;
            case 2:         // 読み書き可能
                return 2;
            default:        // アクセス権なし
                return 0;
        }
    }

    // 現在ログインしているユーザーのユーザーIDを返す
    static function getCurrentUserId()
    {
        global $gen_db;

        // セッションチェックが済んでいることが前提
        $sql = "select user_id from session_table where session_id = '{$_SESSION["session_id"]}'";
        return $gen_db->queryOneValue($sql);
    }

    // ログイン状態（仮/正式）の切り替え
    static function changeLoginState($sessionId, $isTemp)
    {
        global $gen_db;

        if ($sessionId == "")
            $sessionId = $_SESSION['session_id'];
        $data = array("temp_flag" => ($isTemp ? 1 : null));
        $where = "session_id = '{$sessionId}'";
        $gen_db->update("session_table", $data, $where);
    }

    // ランダムな文字列を生成する
    static function _makeRandomString($length)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        mt_srand((double)microtime() * 974353);
        $randStr = "";
        for ($i = 0; $i < $length; $i++) {
            $randStr .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $randStr;
    }

    // 現在接続しているユーザーのIP アドレスを返す
    static function getRemoteIpAddress()
    {
        if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            return $_SERVER["HTTP_X_FORWARDED_FOR"];
        } else {
            return $_SERVER["REMOTE_ADDR"];
        }
    }

}
