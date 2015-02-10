<?php

require_once("Model.class.php");

class Config_PasswordChange_Edit extends Base_EditBase
{

    function convert($converter, &$form)
    {
        $form['user_id'] = Gen_Auth::getCurrentUserId();
    }

    function validate($validator, &$form)
    {
    }

    function setQueryParam(&$form)
    {
        $this->keyColumn = 'user_id';
        $this->selectQuery = "select user_id, user_name from user_master [Where] order by user_name";
    }

    function setViewParam(&$form)
    {
        $this->modelName = "Config_PasswordChange_Model";

        $form['gen_pageTitle'] = _g('パスワード変更');
        $form['gen_entryAction'] = "Config_PasswordChange_Entry";
        $form['gen_listAction'] = @$_SESSION["user_home_menu"];

        if (@$form['password2'] == "")
            $form['password2'] = @$form['password'];
        if (@$form['password2'] == "error")
            $form['password2'] = "";    // EntryのValidator参照

        // adminユーザーの場合、パスワードを変更できない。
        if ($_SESSION['user_id'] == "-1") {
            $form['gen_message_noEscape'] = "<font color='red'><b>" . _g("管理者ユーザーはパスワードを変更できません。") . "</b></font>";
            $form['gen_readonly'] = 'true';
        }

        if (isset($form['gen_needPasswordChange'])) {
            $form['gen_message_noEscape'] = "<font color='blue'><b>" . _g("パスワードの有効期限が切れています。パスワードを変更してください。") . "</b></font>";
        }

        $form['gen_editControlArray'] = array(
            //  user_nameがPOSTされるよう、divではなくtextboxにした。
            //  エラーリダイレクト時の不具合に対処（ユーザー名が表示されない）に対処するため。
            array(
                'label' => _g('ユーザー名'),
                'type' => 'textbox',
                'name' => 'user_name',
                'size' => '11',
                'readonly' => true,
                'value' => @$form['user_name'],
            ),
            array(
                'label' => _g('現在のパスワード'),
                'type' => 'password',
                'name' => 'now_password',
                'value' => @$form['now_password'],
                'require' => true,
                'size' => '20',
            ),
            array(
                'label' => _g('新しいパスワード'),
                'type' => 'password',
                'name' => 'password',
                'value' => @$form['password'],
                'require' => true,
                'size' => '20',
            ),
            array(
                'label' => _g('新しいパスワード（確認入力）'),
                'type' => 'password',
                'name' => 'password2',
                'value' => @$form['password2'],
                'require' => true,
                'size' => '20',
            ),
        );
    }

}
