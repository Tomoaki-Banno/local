<?php

require_once("Model.class.php");

class Master_User_Edit extends Base_EditBase
{
    var $isRestrictedUser = false;
    
    function setQueryParam(&$form)
    {
        global $gen_db;
        
        // 機能限定ユーザーの判断
        if (isset($form['user_id']) && Gen_String::isNumeric($form['user_id'])) {
            // 編集モード：　編集対象ユーザーが機能限定ユーザーかどうかで判断
            $query = "select restricted_user from user_master where user_id = '{$form['user_id']}'";
            $res = $gen_db->queryOneValue($query);
            if ($res == "t") {
                $this->isRestrictedUser = true;
            }
        } else {
            // 新規モード：　操作ユーザーがadminなら一般、一般ユーザーなら機能限定
            if (Gen_Auth::getCurrentUserId() != -1) {
                $this->isRestrictedUser = true;
            }
        }

        $this->keyColumn = 'user_id';
        $menuClass = new Logic_Menu();
        $menuArr = $menuClass->getMenuArray(false, $this->isRestrictedUser);
        $queryCore = "";
        foreach ($menuArr as $menuGroup) {
            foreach ($menuGroup as $menu) {
                $act = strtolower($menu[0]);
                $queryCore .= ",max(case when class_name = '{$act}' then permission end) as {$act}";
            }
        }

        $this->selectQuery = "
            select
                max(t0.user_id) as user_id
                ,max(login_user_id) as login_user_id
                ,max(user_name) as user_name
                ,max(case when account_lockout then 'true' else '' end) as account_lockout
                ,max(start_action) as start_action
                ,max(language) as language
                {$queryCore}
                ,max(gen_last_update) as gen_last_update
                ,max(gen_last_updater) as gen_last_updater
                ,max(customer_id) as customer_id
                ,max(section_id) as section_id
            from (
                select
                    login_user_id
                    ,user_id
                    ,user_name
                    ,account_lockout
                    ,start_action
                    ,language
                    ,coalesce(record_update_date, record_create_date) as gen_last_update
                    ,coalesce(record_updater, record_creator) as gen_last_updater
                    ,customer_id
                    ,section_id
                from
                    user_master
                [Where]
                ) as t0
                left join permission_master on t0.user_id = permission_master.user_id
            group by
                t0.user_id
        ";
    }

    function setViewParam(&$form)
    {
        global $gen_db;

        $this->modelName = "Master_User_Model";

        $form['gen_pageTitle'] = _g('ユーザー管理');
        $form['gen_entryAction'] = "Master_User_Entry";
        $form['gen_listAction'] = "Master_User_List";
        $option_3state = array(0 => _g('× アクセス禁止'), 1 => _g('△ 読み取りのみ'), 2 => _g('○ 書き込み可能'));
        $option_2state = array(0 => _g('× アクセス禁止'), 1 => _g('○ アクセス可能'));

        $form['gen_labelWidth'] = 150;
        
        $menuClass = new Logic_Menu();
        $menuArr = $menuClass->getMenuArray(true, $this->isRestrictedUser);
        
        $isAdmin = (Gen_Auth::getCurrentUserId() == -1);
        if ($isAdmin) {
            // admin
            if (isset($form['user_id']) && Gen_String::isNumeric($form['user_id'])) {
                if ($this->isRestrictedUser) {
                    $msg = "一般ユーザーに昇格する";
                } else {
                    $msg = "機能限定ユーザーに降格する";
                }
                $form['gen_message_noEscape'] = "<input type='button' value='{$msg} (admin限定)' onclick='alterRestricted({$form['user_id']})'><br><br>";
            }
        } else {
            // 一般ユーザー
            if ($this->isRestrictedUser) {
                if (isset($form['user_id']) && Gen_String::isNumeric($form['user_id'])) {
                    $form['gen_message_noEscape'] = "<font color='blue'>" . _g("このユーザーは使用できる機能が限定されています。他の機能が使用できるようにするにはユーザーライセンス追加が必要です。") . "</font><br><br>";
                } else {
                    $form['gen_message_noEscape'] = "<font color='blue'>" . _g("新規作成できるのは機能が限定されたユーザーだけです。一般ユーザーを作成するにはライセンス追加が必要です。") . "</font><br><br>";
                }
            }
        }
        
        // ログイン後の表示画面の選択肢
        $option_action = array();
        foreach ($menuArr as $menuGroup) {
            $isGroupFirst = true;
            foreach ($menuGroup as $menu) {
                // 複数の画面があるグループの先頭行はメニューバー用なので、選択肢に含めない
                if ($isGroupFirst && count($menuGroup) > 1) {
                    $isGroupFirst = false;
                    continue;
                }
                // ログアウトとモバイル関連、パスワード変更は選択肢に含めない
                if ($menu[0] == "Logout" || substr($menu[0], 0, 7) === "Mobile_" || $menu[0] == "Config_PasswordChange") {
                    continue;
                }
                $act = $menu[0] . ($menu[1] == "" ? "" : "_") . $menu[1];
                $cap = str_replace("　", "", $menu[2]);
                if ($cap != "") {
                    $option_action[$act] = $cap;
                }
            }
        }

        $query = "select section_id, section_name from section_master order by section_code";
        $option_section = $gen_db->getHtmlOptionArray($query, true);

        // 使用言語リスト
        //  $form['gen_support_lang'] は index.php で設定
        $option_lang[''] = _g("自動選択");
        $supportLangArr = Gen_String::getSupportLangList();
        foreach ($supportLangArr as $supportLang) {
            $option_lang[$supportLang[0]] = $supportLang[2];
        }

        if (@$form['password2'] == "")
            $form['password2'] = @$form['password'];
        if (@$form['password2'] == "error")
            $form['password2'] = "";    // EntryのValidator参照

        $form['gen_editControlArray'] = array(
            array(
                'label' => _g('ユーザーID'),
                'type' => 'textbox',
                'name' => 'login_user_id',
                'value' => @$form['login_user_id'],
                'require' => true,
                'ime' => 'off',
                'size' => '15',
                'helpText_noEscape' => _g("ログイン用のユーザーIDです。"),
            ),
            array(
                'label' => _g('ユーザー名'),
                'type' => 'textbox',
                'name' => 'user_name',
                'value' => @$form['user_name'],
                'ime' => 'off',
                'size' => '15',
                'helpText_noEscape' => _g("画面上に表示されるユーザー名です。省略するとユーザーIDと同じになります。"),
            ),
            array(
                'label' => _g('パスワード'),
                'type' => 'password',
                'name' => 'password',
                'value' => @$form['password'],
                'ime' => 'off',
                'size' => '15',
                'require' => (isset($form['user_id']) && !isset($form['gen_record_copy']) ? false : true), // 新規の場合のみ必須
                'helpText_noEscape' => _g("新規の場合は必須です。更新時は、パスワードを変更する場合のみ入力してください。（変更しないときは空欄にしてください。）"),
            ),
            array(
                'label' => _g('パスワードの確認入力'),
                'type' => 'password',
                'name' => 'password2',
                'value' => @$form['password2'],
                'ime' => 'off',
                'size' => '15',
                'require' => (isset($form['user_id']) && !isset($form['gen_record_copy']) ? false : true), // 新規の場合のみ必須
            ),
            array(
                'label' => _g('部門'),
                'type' => 'select',
                'name' => 'section_id',
                'options' => $option_section,
                'selected' => @$form['section_id'],
                'helpText_noEscape' => _g("部門を設定しておくと、スケジュール画面での絞り込みなどに便利です。"),
            ),
            array(
                'label' => _g('ロックアウト'),
                'type' => 'checkbox',
                'name' => 'account_lockout',
                'onvalue' => 'true', // trueのときの値。デフォルト値ではない
                'value' => @$form['account_lockout'],
                'helpText_noEscape' => _g("このチェックをオンにすると、このユーザーアカウントでログインできなくなります。") . "<br><br>" .
                _g("一定時間内にユーザーがログインに連続して失敗すると、このチェックが自動的にオンになり、アカウントがロックされます。") . "<br>" .
                _g("何回失敗した時点でロックされるかは、[メンテナンス]-[自社情報]の「ログイン失敗回数の上限」で設定できます。"),
            ),
            array(
                'label' => _g('使用する言語'),
                'type' => 'select',
                'name' => 'language',
                'options' => $option_lang,
                'selected' => @$form['language'],
                'helpText_noEscape' => _g("「自動選択」を選んだ場合、ブラウザの言語設定に従います。"),
            ),
            array(
                'label' => _g('ログイン後の表示画面'),
                'type' => 'select',
                'name' => 'start_action',
                'options' => $option_action,
                'selected' => @$form['start_action'],
            ),            
        );
        // 機能限定ユーザーは非表示
        if (!$this->isRestrictedUser) {
            $form['gen_editControlArray'][] = array(
                'label' => _g('取引先'),
                'type' => 'dropdown',
                'name' => 'customer_id',
                'value' => @$form['customer_id'],
                'size' => '8',
                'subSize' => '12',
                'dropdownCategory' => 'customer_or_suppler',
                'helpText_noEscape' => _g('簡易EDI機能用です。') . '<br><br>' .
                    _g('この項目を指定すると、このアカウントは取引先用アカウントになり、得意先の場合は「発注登録」、サプライヤーの場合は「注文受信」画面だけが使用できるようになります。') . '<br><br>' .
                    _g('指定できるのは取引先マスタで区分を「得意先」か「サプライヤー」に指定した取引先のみです。'),
                'readonly' => ($_SESSION["user_id"] == @$form['user_id'])
            );
            $form['gen_editControlArray'][] = array(
                'type' => 'literal',
                'denyMove' => true,
            );
        }
        $form['gen_editControlArray'][] = array(
            'type' => 'literal',
            'denyMove' => true,
        );
        $form['gen_editControlArray'][] = array(
            'type' => 'literal',
            'denyMove' => true,
        );
        $form['gen_editControlArray'][] = array(
            'type' => 'literal',
            'denyMove' => true,
        );
        $form['gen_editControlArray'][] = array(
            'type' => 'literal',
            'denyMove' => true,
        );
        // 機能限定ユーザーは非表示
        if (!$this->isRestrictedUser) {
            $form['gen_editControlArray'][] = array(
                'label_noEscape' => "<center><a href=\"javascript:selectChange(true)\" style='color:black;font-size:12px'>" . _g("すべて可能にする") . "</a>"
                    . "&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"javascript:selectChange(false)\" style='color:black;font-size:12px'>" . _g("すべて禁止にする") . "</a><center>",
                'type' => 'literal',
                'denyMove' => true,
                'colspan' => 5,
            );
        }
        $form['gen_editControlArray'][] = array(
            'type' => 'literal',
            'denyMove' => true,
        );
        $form['gen_editControlArray'][] = array(
            'type' => 'literal',
            'denyMove' => true,
        );

        $isLeft = true;
        $actionList = array();
        $elmNameListArray = array();
        foreach ($menuArr as $menuGroupKey => $menuGroup) {
            $isGroupFirst = true;
            $elmNameList = array();
            foreach ($menuGroup as $menu) {
                $cap = str_replace("　", "", $menu[2]);
                // 同じクラスグループが複数回出てくる場合（Stock_Inoutなど）、最初の1つのみ表示。
                //   Stock_Inoutについては「入庫登録」を表示させるため、先に出てくる「支給」を排除。
                if ($cap != "" && !in_array($menu[0], $actionList)
                        && $menu[0] != 'Logout' && $cap != _g("支給登録")) {

                    if ($isGroupFirst) {
                        $form['gen_editControlArray'][] = array(
                            'label_noEscape' => "<span style='height:20px;font-size:14px;color:blue;border-left:solid 5px blue'>&nbsp;&nbsp;" . h($menuGroup[0][2]) . "</span>"
                                .(count($menuGroup) == 1 ? "" : 
                                    "<div style='width:30px;display:inline-block'></div><a href=\"javascript:selectChange(true,'{$menuGroupKey}')\" style='color:#999;font-size:11px'>" . _g("すべて可能") . "</a>"
                                    . "&nbsp;&nbsp;<a href=\"javascript:selectChange(false,'{$menuGroupKey}')\" style='color:#999;font-size:11px'>" . _g("すべて禁止") . "</a>"
                                ),
                            'type' => 'literal',
                            'denyMove' => true,
                        );
                        $form['gen_editControlArray'][] = array(
                            'type' => 'literal',
                            'denyMove' => true,
                        );
                        if (count($menuGroup) > 1 && !$this->isRestrictedUser) {
                            $cap .= _g("メニュー");
                        }
                        $isGroupFirst = false;
                    }
                    $helpText_noEscape = "";
                    if ($menu[0] == "Stock_Inout") {
                        $cap = _g("入出庫登録");
                        $helpText_noEscape = _g("以下の各画面に適用されます。") . "<br><br>・" . _g("入庫登録") . "<br>・" . _g("出庫登録") . "<br>・" . _g("使用数リスト") . "<br>・" . _g("支給登録");
                    }

                    $form['gen_editControlArray'][] = array(
                        'label' => $cap,
                        'type' => 'select',
                        'name' => strtolower($menu[0]),
                        'options' => ($menu[3] == "2" ? $option_2state : $option_3state),
                        'selected' => @$form[strtolower($menu[0])],
                        'readonly' => (($menu[0] == "Menu_Admin" || $menu[0] == "Master_User") && $_SESSION['user_id'] == @$form['user_id'] ? true : false),
                        'helpText_noEscape' => $helpText_noEscape,
                        'denyMove' => true,
                    );
                    $isLeft = !$isLeft;
                    $actionList[] = $menu[0];
                    $elmNameList[] = strtolower($menu[0]);
                }
            }
            if (!$isLeft) {
                $form['gen_editControlArray'][] = array(
                    'type' => 'literal',
                    'denyMove' => true,
                );
                $isLeft = !$isLeft;
            }
            $form['gen_editControlArray'][] = array(
                'type' => 'literal',
                'denyMove' => true,
            );
            $form['gen_editControlArray'][] = array(
                'type' => 'literal',
                'denyMove' => true,
            );
            $elmNameListArray[$menuGroupKey] = $elmNameList;
        }
        
        $js = "";
        foreach($elmNameListArray as $menuGroupKey => $elmNameList) {
            $js .= "if (groupKey == undefined || groupKey == '{$menuGroupKey}') {";
            foreach ($elmNameList as $name) {
                $js .= "arr.push('" . h($name) . "');";
            }
            $js .= "};";
        }
        
        $form['gen_javascript_noEscape'] = "
            function selectChange(toAllow, groupKey) {
                var arr = new Array();
                {$js}
                $.each(arr, function(i, name) {
                    var elm = $('#' + name);
                    if (elm.attr('disabled')) {
                        return true;
                    }
                    if (toAllow) {
                        if (elm.children().length == 2) {
                            elm.val('1');
                        } else {
                            elm.val('2');
                        }
                    } else {
                        elm.val('0');
                    }
                });
            }
        ";
        if ($isAdmin) {
            $form['gen_javascript_noEscape'] .= "
                function alterRestricted(userId) {
                    if (!confirm('このユーザーのタイプを変更します。本当に実行しますか？')) {
                        return;
                    }
                    gen.ajax.connect('Master_User_AjaxAlterRestricted', {userId : userId},
                        function(j) {
                            if (j.status == 'success') {
                                // タイプ変更後は、すべての画面に対するアクセス権が削除された状態になるので画面もそれにあわせておく。columnResetの前に行う必要がある
                                selectChange(false);
                                gen.edit.columnReset('Master_User_Edit&user_id=' + userId, 'ユーザーのタイプを変更しました。このあと権限設定して「登録」ボタンを押してください。');
                            }
                        });
                }
            ";
        }
    }

}
