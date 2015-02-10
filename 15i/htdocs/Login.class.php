<?php

class Login
{

    function execute(&$form)
    {
        global $gen_db;

        // ----- 背景画像関連 ------
        
        if (!isset($form['gen_app'])) {
            $imageArr = array();    // 画像配列

            if (GEN_IS_BACKGROUND) {
                // 画像ディレクトリ設定
                $dirArr = explode(";", BACKGROUND_IMAGE_DIR);
                if (isset($dirArr)) {
                    foreach ($dirArr as $value) {
                        $path = BACKGROUND_IMAGE_PATH . $value;
                        // 指定のディレクトリをオープンし内容を取得
                        if (is_dir($path)) {
                            if ($dh = opendir($path)) {
                                while (($file = readdir($dh)) !== false) {
                                    // ログイン画像を取得
                                    if (preg_match("/-log.jpg/", $file)) {
                                        $imageArr[] = BACKGROUND_IMAGE_URL . "{$value}/{$file}";
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // 画像配列補間
            if (!isset($dirArr) || count($imageArr) == 0) {
                $imageArr[] = "img/background/default-log.jpg";     // 緊急時設定
            }

            // ランダムセレクト
            $randKeys = array_rand($imageArr, 1);
            $form['gen_login_image'] = $imageArr[$randKeys];
            $form['gen_image_id'] = str_replace("-log", '', $imageArr[$randKeys]);
        }

        // ----- 使用期限関連 ------
        
        if (GEN_LOGIN_LIMIT != "") {
            if (strtotime(GEN_LOGIN_LIMIT) < strtotime(date('Y-m-d'))) {
                // adminは有効期限を過ぎていてもログイン可能
                if (!isset($form['loginUserId']) || $form['loginUserId'] != ADMIN_LOGIN_ID) {
                    $form['error'] = sprintf(_g("使用期限（%s）を過ぎています。"), GEN_LOGIN_LIMIT);
                }
            } else {
                $limit = strtotime(GEN_LOGIN_LIMIT);
                $today = strtotime(date("Y-m-d"));
                $left = (int) (($limit - $today) / (3600 * 24)) + 1;
                if (GEN_LOGIN_LIMIT_DAYS_NOTICE == 0 || GEN_LOGIN_LIMIT_DAYS_NOTICE >= $left) {
                    $form['login_msg'] = sprintf(_g("使用期限 ： %s　　　残り%s日です"), GEN_LOGIN_LIMIT, $left);
                }
            }
        }

        // ----- セッションエラーでここへ来たときのメッセージ表示 ------
        
        if (@$form['gen_concurrent_error']) {
            $form['error'] = _g("同じユーザー名で別のコンピュータ（またはブラウザ）からログインしたため、ログアウトしました。");
        } else if (@$form['gen_session_timeout']) {
            $form['error'] = _g("最後のアクセスから一定の時間が経過したため、ログアウトしました。");
        } else if (@$form['gen_ajax_error']) {
            $form['error'] = _g("最後のアクセスから一定の時間が経過したか、同じユーザー名で別のコンピュータ（またはブラウザ）からログインしたため、ログアウトしました。");
        }
        
        // ----- ログイン処理 ------
        
        if (!isset($form['error']) && isset($form['loginUserId'])) {
            if ($form['loginUserId'] != '') {
                // 機種依存文字対応
                $form['loginUserId'] = mb_convert_encoding($form['loginUserId'], "UTF-8", "auto");

                // パスワード未入力対応
                if (!isset($form['password']))
                    $form['password'] = '';

                // ----- ログイン成功・失敗にかかわらず行う処理 ------
                
                // スキーマ自動更新
                //　次のGen_Auth::login() の中で更新されたスキーマを使用する場合があるので、このタイミングで処理しておく必要がある。
                $_SESSION['user_name'] = $form['loginUserId'];
                $gen_db->schemaAutoUpdate();

                // ecomチャットの処理
                Gen_Chat::ecomChatAutoUpdate();

                // process_master に標準工程レコードが存在するかどうかを調べ、存在しなければ追加する
                $query = "select * from process_master where process_id = 0";
                if (!$gen_db->existRecord($query)) {
                    $data = array(
                        'process_id' => 0,
                        'process_code' => "gen_default_process",
                        'process_name' => "(" . _g("標準工程") . ")",
                        'equipment_name' => "",
                    );
                    $gen_db->insert('process_master', $data);
                }

                // 古い access_log の削除
                require_once dirname(dirname(__FILE__)) . '/Components/Spyc.php';
                $confFile = dirname(dirname(dirname(__FILE__))) . '/gen_server_config.yml';
                if (!file_exists($confFile)) {
                    throw new Exception('gen_server_config.yml がありません。');
                }
                $serverConfig = Spyc::YAMLLoad($confFile);
                $confFile = dirname(dirname(__FILE__)) . '/gen_config.yml';
                if (!file_exists($confFile)) {
                    throw new Exception('gen_config.yml がありません。');
                }
                $appConfig = Spyc::YAMLLoad($confFile);
                $config = array_merge($serverConfig, $appConfig);
                $logStorageLife = 6;   // デフォルト6ヶ月
                if (isset($config['access_log_storage_life']) && Gen_String::isNumeric($config['access_log_storage_life'])) {
                    $logStorageLife = $config['access_log_storage_life'];
                }
                $logLimit = date("Y-m-d", strtotime("-{$logStorageLife}month"));
                $query = "delete from access_log where access_time < '{$logLimit}'";
                $gen_db->query($query);

                // ----- ログイン処理 ------
                
                $loginFlag = Gen_Auth::login(
                    $form['loginUserId'], 
                    $form['password'], 
                    @$form['gen_sjis'] || isset($form['gen_app']), 
                    isset($form['deviceToken']) ? $form['deviceToken'] : false,
                    isset($form['deviceTokenDev'])
                );

                // ----- ログイン成功時（ログイン成功 or パスワード有効期限切れ） のみ行う処理 ------
                
                if ($loginFlag == 1 || $loginFlag == 2) {
                    // start action（user_home_menu）の設定
                    $query = "select start_action from user_master where login_user_id = '{$form['loginUserId']}'";
                    $startAction = $gen_db->queryOneValue($query);
                    if ($form['gen_mobile']) {
                        $startAction = "Mobile_Home";
                    }
                    $isCustomer = false;
                    if (isset($_SESSION["user_customer_id"]) && is_numeric($_SESSION["user_customer_id"]) && $_SESSION["user_customer_id"] != "-1") {
                        $isCustomer = true;
                    }
                    if ($isCustomer) {
                        $query = "select classification from customer_master where customer_id = '{$_SESSION["user_customer_id"]}'";
                        $classification = $gen_db->queryOneValue($query);
                        if ($classification == "0") {
                            $startAction = "Manufacturing_CustomerEdi_List";  // 得意先
                            $_SESSION["user_home_menu"] = $startAction;
                        } else {
                            $startAction = "Partner_PartnerEdi_List";   // サプライヤー
                            $_SESSION["user_home_menu"] = $startAction;
                        }
                    } else {
                        $_SESSION["user_home_menu"] = "Menu_Home";
                    }

                    unset($_SESSION["gen_language"]);

                    // Ajax用トークン発行
                    //  詳細は Base_AjaxBase のコメントを参照。
                    $_SESSION['gen_ajax_token'] = sha1(uniqid(rand(), true));

                    // メニューバーはデフォルトOPENとする
                    if (!isset($_SESSION['gen_setting_user']->gen_slider_gen_menubar)) {
                        $_SESSION['gen_setting_user']->gen_slider_gen_menubar = true;
                        Gen_Setting::saveSetting();
                    }

                    // システムチャット（通知センター）の処理
                    Gen_Chat::initSystemChat();

                    $_SESSION['gen_app'] = isset($form['gen_app']);
                    $mobile = ($_SESSION['gen_app'] ? " (". _g("app") .")" : ($form['gen_iPad'] ? " (". _g("iPad") .")" : ($form['gen_iPhone'] ? " (". _g("iPhone") .")" : "")));
                    Gen_Log::dataAccessLog(_g("ログイン") . ($form['gen_mobile'] ? " (" . _g("モバイル") . ")" : ""), _g("ログイン成功") . $mobile, 'IP： ' . Gen_Auth::getRemoteIpAddress() . ($loginFlag == 2 ? '　' . _g("パスワード期限切れ") : ''));
                    if (!$_SESSION['gen_app']) {
                        $body = _g("ユーザーがログインしました。") . "\n\n"
                                . "[" . _g("ログイン日時") . "] " . date('Y-m-d H:i:s') . "\n"
                                . "[" . _g("ユーザー名") . "] " . $_SESSION['user_name']
                                . "";
                        Gen_Mail::sendAlertMail('login_success', _g("ログイン"), $body);
                    }
                }
                
                // ----- ログイン後 ------
                
                switch ($loginFlag) {
                    case 1:
                        // ログイン成功
                        // 別Actionへリダイレクト
                        $action = preg_replace("/&.*$/", "", $startAction);  // パラメータを除いたaction
                        $path = APP_DIR . str_replace('_', SEPARATOR, $action) . ".class.php";
                        if (!file_exists($path)) {
                            $action = $_SESSION["user_home_menu"];
                        }

                        preg_match("/&.*$/", $startAction, $matches);
                        if (count($matches) > 0) {
                            $param = str_replace("&", "", $matches[0]);
                            $paramArr = explode("=", $param);
                            $form[$paramArr[0]] = $paramArr[1];
                        }
                        if (isset($form['gen_app'])) {
                            $obj = array(
                                "user_id" => $_SESSION['user_id'], 
                                "user_name" => $_SESSION['user_name'], 
                                "company_name" => $_SESSION['company_name'], 
                                "ajax_token" => $_SESSION['gen_ajax_token']
                            );
                            $form['response_noEscape'] = json_encode($obj);
                            return 'simple.tpl';
                        }
                        return "action:{$action}";
                    case 2:
                        // パスワード期限切れで、ユーザーにパスワード変更権限あり
                        if (isset($form['gen_app'])) {
                            $form['error'] = _g("パスワードの期限が切れています。PC版Genesissでパスワードを変更してください。");
                            break;
                        }
                        
                        // 仮ログイン状態でパスワード変更画面へリダイレクト
                        $form['gen_needPasswordChange'] = true;
                        $_SESSION['user_name'] = $form['loginUserId'];

                        return "action:Config_PasswordChange_EditNeed";
                    case 3:
                        // パスワード期限切れで、ユーザーにパスワード変更権限なし
                        $form['error'] = _g("パスワードの期限が切れています。システム管理者にご連絡ください。");
                        $_SESSION['user_name'] = $form['loginUserId'];

                        Gen_Log::dataAccessLog(_g("ログイン"), _g("ログイン失敗"), 'IP： ' . Gen_Auth::getRemoteIpAddress() . '　' . _g("パスワード期限切れ"));

                        $body = _g("ユーザーがログインを試みましたが、パスワードの期限が切れており、ユーザーにパスワードの変更権限がなかったため、ログインできませんでした。") . "\n\n"
                                . "[" . _g("ログイン日時") . "] " . date('Y-m-d H:i:s') . "\n"
                                . "[" . _g("ユーザー名") . "] " . $_SESSION['user_name']
                                . "";
                        Gen_Mail::sendAlertMail('login_fail', _g("ログイン失敗"), $body);

                        unset($_SESSION['user_name']);

                        break;
                    case 4:
                        // ロックアウト
                        $form['error'] = _g("このユーザーアカウントはロックアウトされているため使用できません。システム管理者にご相談ください。");
                        $_SESSION['user_name'] = $form['loginUserId'];

                        Gen_Log::dataAccessLog(_g("ログイン"), _g("ログイン失敗"), 'IP： ' . Gen_Auth::getRemoteIpAddress() . '　' . _g("ロックアウト"));

                        $body = _g("ユーザーがログインを試みましたが、アカウントがロックアウトされているためログインできませんでした。") . "\n\n"
                                . "[" . _g("ログイン日時") . "] " . date('Y-m-d H:i:s') . "\n"
                                . "[" . _g("ユーザー名") . "] " . $_SESSION['user_name']
                                . "";
                        Gen_Mail::sendAlertMail('login_fail', _g("ログイン失敗"), $body);

                        unset($_SESSION['user_name']);

                        break;
                    default:
                        $form['error'] = _g("ユーザー名またはパスワードが違います。");
                        $_SESSION['user_name'] = '';

                        // イーコモード事務所からの接続失敗は記録しない
                        $res = explode(';', GEN_OFFICE_IP);
                        $ipArr = array();
                        foreach ($res as $value) {
                            $ipArr[] = trim($value);
                        }
                        if (!is_array($ipArr) || !in_array(Gen_Auth::getRemoteIpAddress(), $ipArr)) {
                            // ログイン失敗時のパスワード記録はやめた。
                            // adminのユーザー名は残さない。
                            // ag.cgi?page=ProjectDocView&pid=1195&did=100710
                            $loginUserId = ($form['loginUserId'] === ADMIN_LOGIN_ID ? '' : '　' . _g("ユーザーID：") . $form['loginUserId']);
                            Gen_Log::dataAccessLog(_g("ログイン"), _g("ログイン失敗"), 'IP： ' . Gen_Auth::getRemoteIpAddress() . $loginUserId);

                            $body = _g("ユーザーがログインを試みましたが、ユーザーIDかパスワードが間違っているためログインできませんでした。") . "\n\n"
                                    . "[" . _g("ログイン日時") . "] " . date('Y-m-d H:i:s') . "\n"
                                    . "[" . _g("ユーザーID") . "] " . ($form['loginUserId'] === ADMIN_LOGIN_ID ? '' : $form['loginUserId'])
                                    . "";
                            Gen_Mail::sendAlertMail('login_fail', _g("ログイン失敗"), $body);
                        }

                        unset($_SESSION['user_name']);
                }
            } else {
                $form['error'] = _g("ユーザー名が指定されていません。");
            }
        }

        // ----- ログイン画面表示 ------
        
        $form['gen_trust_code'] = str_replace("\"", "", GEN_TRUST_CODE);
        
        if (isset($form['gen_app'])) {
            // appの場合、ここへ来るということは何らかのエラーが発生したということ
            $obj = array("error" => $form['error']);
            $form['response_noEscape'] = json_encode($obj);
            return 'simple.tpl';
        } else if ($form['gen_mobile']) {
            return 'mobile/login.tpl';
        } else {
            return 'login.tpl';
        }
    }

}
