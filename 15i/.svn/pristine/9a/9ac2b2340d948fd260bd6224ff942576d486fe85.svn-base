<?php

require_once("Model.class.php");

class Master_AlertMail_Entry extends Base_EntryBase
{

    function setParam(&$form)
    {
        // 基本パラメータ
        $this->listAction = "Master_AlertMail_List";
        $this->errorAction = "Master_AlertMail_Edit";
        $this->modelName = "Master_AlertMail_Model";
        $this->newRecordNotKeepField = array("mail_address", "mail_address2");

        // POSTされた内容（$form）を、Modelのformプロパティにそのまま入れるための処理。
        // どんなカテゴリがPOSTされるかわからないため、POSTの内容をそのままModelに渡し、
        // Model側で処理するようにしている。
        $form['form'] = $form;
    }

    function setLogParam($form)
    {
        global $gen_db;

        if (is_numeric(@$form['mail_address_id'])) {
            $id = $form['mail_address_id'];
        } else {
            $id = $gen_db->getSequence("mail_address_master_mail_address_id_seq");
        }
        $query = "select regist_flag from mail_address_master where mail_address_id = '{$id}'";
        // 登録時に仮登録メールを送信したかどうかを判断。仮登録状態かどうかで判断している。
        // これだと仮登録状態のデータを修正したときもtrueになってしまうが、かといってほかにやりようがない
        $isSendRegistMail = ($gen_db->queryOneValue($query) == 'f');

        $this->log1 = _g("通知メール");
        $this->log2 = "[" . _g("メールアドレス") . "] {$form['mail_address']}";
        if ($isSendRegistMail) {
            $this->afterEntryMessage = sprintf(_g("%s へ仮登録メールを送信しました。\nメールを確認して、そこに書かれている手順に従って本登録を行ってください。"), $form['mail_address']);
        } else {
            $this->afterEntryMessage = sprintf(_g("%s への通知メールを登録しました。"), $form['mail_address']);
        }
    }

}
