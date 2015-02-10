<?php

class Gen_Mail
{

    // メール送信クラス
    //  簡易版。HTMLメールや添付ファイルは送れない。
    //  UNIX:
    //      PHPを実行するサーバーでsendmailが動いており、PHPのコンパイル時点でシステム上の
    //      sendmailバイナリにアクセスできる必要がある。
    //  Win:
    //      ポート25でなんらかのSMTPサーバーが動いている必要がある。
    //
    //  $fromAddress    送信元メールアドレス
    //  $toAddress      宛先メールアドレス
    //  $subject        件名    （日本語OK。自動エンコードされる）
    //  $body           本文    （日本語OK。自動エンコードされる）
    //  $cc             ccメールアドレス
    //
    //  ※使用例
    //      Gen_Mail::sendMail("xxx@gmail.com", "xxx@i.softbank.jp", "件名", "本文");
    //  ※件名や本文は日本語でも大丈夫（自動エンコードされる）が、送信元や宛先に日本語を含める場合は
    //  　次のようにエンコードする必要がある。
    //      $toAddress = "xxx@gmail.com<".mb_encode_mimeheader("宛先太郎", "ISO-2022-JP").">";

    static function sendMail($fromAddress, $toAddress, $subject, $body, $cc = "")
    {
        if ($fromAddress == "" || $toAddress == "")
            return false;   // fail

        // メール送信は 10:製品版 20:体験版 40:公開検証版 のみ
        if (GEN_SERVER_INFO_CLASS != 10 && GEN_SERVER_INFO_CLASS != 20 && GEN_SERVER_INFO_CLASS != 40)
            return false;

        // 言語設定、内部エンコーディングを指定する
        mb_language("Japanese");
        mb_internal_encoding("UTF-8");

        // メールヘッダ
        $header =
                "From: {$fromAddress}\r\n"
                . ($cc != "" ? "Cc: {$cc}\r\n" : "")
                . "Reply-To: {$fromAddress}\r\n"
                . "X-Mailer: PHP/" . phpversion();

        // エラーメール返信先。これがないとエラーメールが帰ってこない
        $param = "-f{$fromAddress}";

        // メール送信
        $result = mb_send_mail($toAddress, $subject, $body, $header, $param);

        // true: success, false: failed
        return $result;
    }

    // 通知メール送信
    //  特定のアクションが発生したときにこのfunctionを呼び出すと、
    //  そのアクションを通知設定しているメールアドレスにメールを送信する。
    static function sendAlertMail($alertId, $title, $body, $userName = "")
    {
        global $gen_db;

        // テスト
        //if ($alertId == 'login_success' && $_SERVER['REMOTE_ADDR'] != "127.0.0.1") {
        //    $body = _g("ユーザーがログインしました。")."\n\n"
        //        ."["._g("ログイン日時")."] ".date('Y-m-d H:i:s')."\n"
        //        ."["._g("ユーザー名")."] ".$_SESSION['user_name']."\n"
        //        ."[REMOTE_ADDR] ".$_SERVER['REMOTE_ADDR']
        //        ."";
        //    $body .= "\n\nhttp" . (GEN_HTTPS_PROTOCOL === false ? "" : "s") . "://" . $_SERVER['SERVER_NAME'] . "/" . basename(ROOT_DIR) . "";
        //    Gen_Mail::sendMail("ito_shunichiro@e-commode.co.jp", "ito_shun@i.softbank.jp", _g("ログイン"), $body);
        //}

        // adminによるアクションは通知しない
        if ($userName == "")
            $userName = $_SESSION['user_name'];
        // $_SESSION['user_id']は、ログイン前（ログイン失敗のときなど）は設定されていないので使えない
        if (@$_SESSION['user_name'] == ADMIN_NAME)
            return;
        // アクション通知は 10:製品版 20:体験版 40:公開検証版 のみ
        if (GEN_SERVER_INFO_CLASS != 10 && GEN_SERVER_INFO_CLASS != 20 && GEN_SERVER_INFO_CLASS != 40)
            return false;

        // 送信先メールアドレスの取得
        $query = "
            select
                mail_address
            from
                mail_address_master
            inner join
                alert_mail_master
                on mail_address_master.mail_address_id = alert_mail_master.mail_address_id
            where
                alert_id = '{$alertId}'
                and regist_flag     -- 本登録済みのアドレスのみ
        ";
        $arr = $gen_db->getArray($query);

        // メール送信
        if ($arr) {
            $from = "from_genesiss@e-commode.co.jp";
            $subject = _g("Genesiss メール通知【{$title}】");
            if (isset($_SERVER['SERVER_NAME'])) {   // 所要量計算の場合は $_SERVERがセットされていない
                $body .= "\n\nhttp" . (GEN_HTTPS_PROTOCOL === false ? "" : "s") . "://" . $_SERVER['SERVER_NAME'] . "/" . basename(ROOT_DIR) . "";
            }
            foreach ($arr as $row) {
                $res = self::sendMail($from, $row['mail_address'], $subject, $body);
                if (!$res) {
                    echo("通知メール送信に失敗しました。本サーバーではメールシステムが有効でない可能性があります。システム管理者にご相談ください。");
                    throw new Exception();
                }
            }
        }
    }

}