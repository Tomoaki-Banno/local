<?php

class Master_AlertMail_Model extends Base_ModelBase
{

    protected function _getKeyColumn()
    {
        return 'mail_address_id';
    }

    protected function _setDefault(&$param, $entryMode)
    {
    }

    protected function _getColumns()
    {
        $columns = array(
            array(
                "column" => "mail_address_id",
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
                "column" => "mail_address",
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
                        "msg" => _g('メールアドレスを指定してください。')
                    ),
                    // 新規登録時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('メールアドレスはすでに使用されています。別の名前を指定してください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[mail_address_id]]!=''", // 修正はスキップ
                        "param" => "select mail_address_id from mail_address_master where mail_address = $1"
                    ),
                    // 修正時の重複チェック
                    array(
                        "cat" => "notExistRecord",
                        "msg" => _g('メールアドレスはすでに使用されています。別の名前を指定してください。'),
                        "skipHasError" => true,
                        "skipValidatePHP" => "[[mail_address_id]]==''", // 新規登録はスキップ
                        // 更新のときは、自分自身の番号をチェック対象としない（自分自身と重複するのは当然）
                        "param" => "select mail_address_id from mail_address_master where mail_address = $1
                            and mail_address_id <> [[mail_address_id]]"
                    ),
                    array(
                        "cat" => "eval",
                        "msg" => _g('メールアドレスが正しくありません。'),
                        "evalPHP" => "\$res=(preg_match('/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/', $1));",
                        "evalJS" => "res=($1.match(/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/));"
                    ),
                ),
            ),
            array(
                "column" => "mail_address",
                "dependentColumn" => "mail_address2",
                "validate" => array(
                    array(
                        "cat" => "required",
                        "msg" => _g('メールアドレスを入力してください。'),
                    ),
                ),
            ),
            array(
                "column" => "mail_address2",
                "dependentColumn" => "mail_address",
                "validate" => array(
                    array(
                        "cat" => "eval",
                        "msg" => _g('メールアドレスが確認入力と一致していません。'),
                        "evalPHP" => "\$res=($1==[[mail_address]]);",
                        "evalJS" => "res=($1==[[mail_address]]);"
                    ),
                ),
            ),
        );

        return $columns;
    }

    protected function _regist(&$param, $isFirstRegist)
    {
        global $gen_db;

        // メールアドレスが新規かどうかの判断
        //  修正モードでも新規アドレスが指定されることがあるので、モードでは判断できない

        $isNewAddress = !$gen_db->existRecord("select * from mail_address_master where mail_address = '{$param['mail_address']}'");

        // -----------------------
        //  mail_address_master の登録
        // -----------------------

        if (isset($param['mail_address_id']) && is_numeric($param['mail_address_id'])) {
            $key = array("mail_address_id" => $param['mail_address_id']);
        } else {
            $key = null;
        }
        $data = array(
            'mail_address' => $param['mail_address'],
        );
        if ($isNewAddress) {
            // 新規アドレスの場合（必ずしも新規モードとは限らない。更新モードで新規アドレスが指定される可能性もあることに注意）
            // 仮登録メール発行の準備
            $pw = Gen_String::makeRandomString(20);
            $registLimit = date('Y-m-d H:i:s', strtotime('+2 hours'));     // 2時間後まで有効;
            $data['regist_flag'] = 'false';
            $data['regist_password'] = $pw;
            $data['regist_limit'] = $registLimit;
        }
        $gen_db->updateOrInsert('mail_address_master', $key, $data);
        if (isset($param['mail_address_id']) && is_numeric($param['mail_address_id'])) {
            $mailAddressId = $param['mail_address_id'];
        } else {
            $mailAddressId = $gen_db->getSequence('mail_address_master_mail_address_id_seq');
        }

        // -----------------------
        //  alert_mail_master の登録
        // -----------------------
        // 更新モードでは、まず既存のレコードを削除する
        if (isset($param['mail_address_id']) && is_numeric($param['mail_address_id'])) {
            $query = "delete from alert_mail_master where mail_address_id = '{$param['mail_address_id']}'";
            $gen_db->query($query);
        }

        // アラートメールを登録する。
        // $param['form'] にはPOSTされた$form配列の内容がそのまま入っている。
        //  Entryクラス参照。
        foreach ($param['form'] as $name => $value) {
            // 不正アクセス防止のため、登録可能リスト配列に挙がっていないクラスは登録しない。またアクセス禁止のクラスは登録しない
            if (substr($name, 0, 6) == 'alert_' && $value == 'true') {
                $alertId = substr($name, 6);
                if ($alertId != '') {
                    $data = array(
                        'mail_address_id' => $mailAddressId,
                        'alert_id' => $alertId,
                    );
                    $gen_db->insert('alert_mail_master', $data);
                }
            }
        }

        // -----------------------
        //  新規アドレスの場合、仮登録メールを送信
        // -----------------------
        if ($isNewAddress) {
            $from = "from_genesiss@e-commode.co.jp";
            $subject = _g("Genesiss メール通知システム 仮登録メール");
            $body = _g("本メールに心当たりのない方は、大変お手数ですがこのメールを削除していただくようお願いいたします。\n\n")
                    . _g("Genesissのメール通知を有効にするには、Genesissにログインした状態で下のURLをクリックしてください。\n\n")
                    . _g("「メール通知を登録しました」と表示されれば登録成功です。\n\n")
                    . "http" . (GEN_HTTPS_PROTOCOL === false ? "" : "s") . "://" . $_SERVER['SERVER_NAME'] . $_SERVER['SCRIPT_NAME'] . "?action=Master_AlertMail_Regist&id={$pw}\n\n"
                    . sprintf("上記のURLの有効期限は %s です。", $registLimit);

            $res = Gen_Mail::sendMail($from, $param['mail_address'], $subject, $body);
            if (!$res) {
                header('Location:' . 'index.php?action=Master_AlertMail_Error');
                die();
            }
        }
        
        // id(keyColumnの値)を戻す。keyColumnがないModelではfalseを戻す。
        return $mailAddressId;
    }

}
