<?php

// このクラスだけはログインしていなくてもアクセスできる（index.php参照）
class Master_AlertMail_Regist
{

    function execute(&$form)
    {
        global $gen_db;

        // タブ、マイメニュー用
        $form['gen_pageTitle'] = '●' . _g('通知メールの設定');

        if (!isset($form['id'])) {
            throw new Exception();
        }

        // idのチェック
        $query = "
        select
            mail_address_id
            ,regist_flag
            ,regist_limit
        from
            mail_address_master
        where
            regist_password = '{$form['id']}'
        ";
        $obj = $gen_db->queryOneRowObject($query);

        if (!$obj) {
            $msg = _g("このURLは正しくありません。");
        } else if ($obj->regist_flag == 't') {
            $msg = _g("このメールアドレスはすでに本登録済みです。");
        } else if (strtotime($obj->regist_limit) < time()) {
            $msg = _g("このURLは期限切れです。メール通知画面でメールアドレスを再登録してください。");
        } else {
            $data = array("regist_flag" => true);
            $where = "mail_address_id = '{$obj->mail_address_id}'";
            $gen_db->update("mail_address_master", $data, $where);
            $msg = _g("メール通知が有効になりました。");
        }

        $form['msg'] = $msg;

        // PC or 携帯
        $UA = $_SERVER{'HTTP_USER_AGENT'};
        $isPhone = (substr_count($UA, "DoCoMo")
                || substr_count($UA, "SoftBank")
                || substr_count($UA, "vodafone")
                || substr_count($UA, "UP.Browser")   // au
                );

        return 'master_alertmail_regist' . ($isPhone ? '_m' : '') . '.tpl';
    }

}