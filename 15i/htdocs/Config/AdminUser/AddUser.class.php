<?php

class Config_AdminUser_AddUser extends Base_ListBase
{
    function setSearchCondition(&$form)
    {
        $form['gen_noSavedSearchCondition'] = true;

        $menu = new Logic_Menu();
        $menuArr = $menu->getMenuArray(true);
        $option_action = array();
        foreach ($menuArr as $menuGroup) {
            foreach ($menuGroup as $menu) {
                $act = $menu[0] . ($menu[1]=="" ? "" : "_") . $menu[1];
                $cap = str_replace("　", "", $menu[2]);
                if ($cap!="" && $menu[0]!="Logout")
                    $option_action[$act] = $cap;
            }
        }

        // 使用言語
        //  $form['gen_support_lang'] は index.php で設定
        $option_lang[''] = _g("自動選択");
        $supportLangArr = Gen_String::getSupportLangList();
        foreach ($supportLangArr as $supportLang) {
            $option_lang[$supportLang[0]] = $supportLang[2];
        }

        $form['gen_searchControlArray'] =
            array(
                array(
                    'label'=>_g('追加ユーザー数'),
                    'field'=>'user_quantity',
                    'nosql'=>true,
                    'notShowMatchBox'=>true,
                    'ime'=>'off',
                    'default'=>5,
                ),
                array(
                    'label'=>_g('パスワード桁数'),
                    'field'=>'password_length',
                    'nosql'=>true,
                    'notShowMatchBox'=>true,
                    'ime'=>'off',
                    'default'=>6,
                ),
                array(
                    'label'=>_g('ログイン後表示画面'),
                    'type'=>'select',
                    'field'=>'start_action',
                    'options'=>$option_action,
                    'nosql'=>true,
                ),
                array(
                    'label'=>_g('言語'),
                    'type'=>'select',
                    'field'=>'language',
                    'options'=>$option_lang,
                    'nosql'=>true,
                ),
            );
    }

    function convertSearchCondition($converter, &$form)
    {
        $converter->nullBlankToValue('user_quantity', 5);
        $converter->nullBlankToValue('password_length', 6);
    }

    function beforeLogic(&$form)
    {
    }

    function setQueryParam(&$form)
    {
        $this->selectQuery = "select 1 from company_master ";    // ダミーSQL
        $this->orderbyDefault = 'company_id';
    }

    function setViewParam(&$form)
    {
        global $gen_db;

        $this->tpl = 'issue.tpl';

        $form['gen_pageTitle'] = _g("ユーザー追加");

        $form['gen_message_noEscape'] = "
            <div align='center'>
            <span style='font-size:14px'>" . _g("追加条件を指定して「ユーザー追加」ボタンを押してください。") . "</span>
            <br><br><span id='doButton'><input type=\"button\" class=\"gen-button\" value='" . _g("ユーザー追加") . "' style='width:160px' onClick='addUser()'></span>
            </div>
        ";

        $form['gen_javascript_noEscape'] = "
            function addUser() {
                var qty = $('#gen_search_user_quantity').val();
                var len = $('#gen_search_password_length').val();
                var act = $('#gen_search_start_action').val();
                var lang = $('#gen_search_start_language').val();
                if (!gen.util.isNumeric(qty) || qty<=0) {
                    alert('" . _g("追加ユーザー数が正しくありません。") . "');
                    $('#gen_search_user_quantity').focus().select();
                    return;
                }
                if (!gen.util.isNumeric(len) || len<=0 || len>10) {
                    // 桁数が多いとサーバーにかなり負荷がかかる
                    alert('" . _g("パスワード桁数が正しくありません。0から10の数値を入力してください。") . "');
                    $('#gen_search_password_length').focus().select();
                    return;
                }
                if (!confirm('" . _g("ユーザーを追加します。実行してもよろしいですか？") . "')) return;

                document.body.style.cursor = 'wait';
                $('#doButton').html(\"<table><tr><td bgcolor='#ffcc33'>" . _g("実行中") . "...</td></tr></table>\");

                var p = {
                    qty : qty,
                    len : len,
                    act : act,
                    lang : lang
                };
                gen.ajax.connect('Config_AdminUser_AjaxAddUser', p,
                    function(j) {
                        if (j.result=='success') {
                            alert('" . _g("以下のユーザーが追加されました。") . "' + j.msg);
                            $('#doButton').html(\"<input type='button' class='gen-button' value='" . _g("ユーザー追加") . "' style='width:160px' onClick='addUser()'>\");
                        } else {
                            alert('" . _g("ユーザーの追加に失敗しました。") . "');
                            $('#doButton').html(\"<input type='button' class='gen-button' value='" . _g("ユーザー追加") . "' style='width:160px' onClick='addUser()'>\");
                        }
                        document.body.style.cursor = 'auto';
                    });
            }
        ";

    }
}
