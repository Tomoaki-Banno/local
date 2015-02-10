<?php

class Master_User_List extends Base_ListBase
{

    function setSearchCondition(&$form)
    {
        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('ユーザー名'),
                'field' => 'user_name',
                'ime' => 'on',
            ),
            array(
                'label' => _g('取引先コード/名'),
                'field' => 'customer_no',
                'field2' => 'customer_name',
            ),
        );
    }

    function convertSearchCondition($converter, &$form)
    {
    }

    function beforeLogic(&$form)
    {
    }

    function setQueryParam(&$form)
    {
        global $gen_db;

        $query = "select password_valid_until from company_master";
        $passwordValidDays = $gen_db->queryOneValue($query);

        $menuClass = new Logic_Menu();
        $menuArr = $menuClass->getMenuArray();
        $actionList = array();
        $queryCore1 = "";
        $queryCore2 = "";
        $field = "";
        foreach ($menuArr as $menuGroup) {
            foreach ($menuGroup as $menu) {
                $cap = str_replace("　", "", $menu[2]);
                // 同じクラスグループが複数回出てくる場合（Stock_Inoutなど）、最初の1つのみ表示。
                //   Stock_Inoutについては「入庫登録」を表示させるため、先に出てくる「支給」を排除。
                if ($cap == "" || in_array($menu[0], $actionList) || $menu[0] == 'Logout' || $cap == _g("支給登録"))
                    continue;
                $act = strtolower($menu[0]);
                if ($menu[3] == "2") {
                    $queryCore1 .= ",max(case when class_name = '{$act}' and permission = 1 then '" . _g('○') . "' end) as {$act}";
                } else {
                    $queryCore1 .= ",max(case when class_name = '{$act}' and permission = 2 then '" . _g('○') . "'
                                    when class_name = '{$act}' and permission = 1 then '" . _g('△') . "' end) as {$act}";
                }
                $queryCore2.= ",max(case when class_name = '{$act}' then 1 else 0 end) as {$act}_flag";
                $field .= ",case when {$act}_flag = 0 then '" . _g('×') . "' else {$act} end {$act}";
                $actionList[] = $menu[0];
            }
        }

        $this->selectQuery = "
            select
                user_master.user_id
                ,user_master.login_user_id
                ,user_master.user_name
                ,case when restricted_user then '" . _g("限定") . "' else '' end as show_restricted_user
                ,case when account_lockout then '" . _g("ロック") . "' else '' end as show_account_lockout
                ,last_login_date
                ,last_logout_date
                ,customer_no
                ,customer_name
                ";
                if (is_numeric($passwordValidDays) && $passwordValidDays != "0") {
                    // ::date はクロス集計対策。ag.cgi?page=ProjectDocView&pid=1574&did=195088
                    $this->selectQuery .= " ,to_char(last_password_change_date + '{$passwordValidDays} days', 'yyyy-mm-dd')::date as valid_date ";
                } else {
                    $this->selectQuery .= " ,cast('' as text) as valid_date";
                }
                $this->selectQuery .= "
                ,case when last_logout_date >= last_login_date then last_logout_date else null end as last_logout
                ,restricted_user
                ,section_code
                ,section_name

                ,user_master.record_create_date as gen_record_create_date
                ,user_master.record_creator as gen_record_creater
                ,coalesce(user_master.record_update_date, user_master.record_create_date) as gen_record_update_date
                ,coalesce(user_master.record_updater, user_master.record_creator) as gen_record_updater
                {$field}
            from
                user_master
                left join (select user_id {$queryCore1} from permission_master group by user_id) as t_permission1
                    on user_master.user_id = t_permission1.user_id
                left join (select user_id {$queryCore2} from permission_master group by user_id) as t_permission2
                    on user_master.user_id = t_permission2.user_id
                left join (select customer_id as cid, customer_no, customer_name
                    from customer_master) as t_customer on user_master.customer_id = t_customer.cid
                left join (select section_id, section_code, section_name
                    from section_master) as t_section on user_master.section_id = t_section.section_id
            [Where]
            [Orderby]
        ";

        $this->orderbyDefault = 'user_name';
    }

    function setViewParam(&$form)
    {
        $form['gen_pageTitle'] = _g("ユーザー管理");
        $form['gen_menuAction'] = "Menu_Master";
        $form['gen_listAction'] = "Master_User_List";
        $form['gen_editAction'] = "Master_User_Edit";
        $form['gen_deleteAction'] = "Master_User_Delete";
        $form['gen_idField'] = 'user_id';
        $form['gen_idFieldForUpdateFile'] = "user_master.user_id";   // これをセットすると「ファイル」「トークボード」列が自動追加される
        $form['gen_excel'] = "true";
        $form['gen_pageHelp'] = _g("権限");

        $isAdmin = (Gen_Auth::getCurrentUserId() == -1);
        
        $form['gen_isClickableTable'] = "true";     // 行をクリックして明細を開く
        // 登録ロジックが特殊なためうまくいかない
        //$form['gen_directEditEnable'] = "true";     // 直接編集

        $form['gen_javascript_noEscape'] = "
            // 削除処理
            function doDelete() {
                var count = 0;
                var postUrl = 'index.php?action=Master_User_BulkDelete';
                var frm = new gen.postSubmit();
                var elms = document.getElementById('form1').elements;
                var ids = '';
                for (i=0; i<elms.length; i++) {
                    if (elms[i].name.substr(0,7) == 'delete_' &&
                    elms[i].checked == true) {
                        frm.add(elms[i].name, elms[i].value);
                        if (ids != '') ids += ',';
                        ids += elms[i].name;
                        count++;
                    }
                }
                if (count == 0) {
                    alert('" . _g("削除するデータを選択してください。") . "');
                } else {
                    gen.ajax.connect('Master_User_AjaxDeleteCheck', {ids:ids}, 
                        function(j) {
                            if (j.status == 'success') {
                                if (!confirm('" . _g("選択されたユーザーが作成したメモパッドが存在します。ユーザーを削除するとメモパッドも削除されます。実行してもよろしいですか？") . "')) {
                                    return;
                                } else {
                                    if (!confirm('" . _g("チェックボックスがオンになっているレコードを削除します。この操作は取り消すことができません。実行してもよろしいですか？") . "')) return;
                                    frm.submit(postUrl, null);
                                }
                            } else {
                                if (!confirm('" . _g("チェックボックスがオンになっているレコードを削除します。この操作は取り消すことができません。実行してもよろしいですか？") . "')) return;
                                frm.submit(postUrl, null);
                            }
                        });
                }
            }
        ";

        $form['gen_fixColumnArray'] = array(
            array(
                'label' => _g('明細'),
                'type' => 'edit',
            ),
        );
        if ($isAdmin) {
            $form['gen_fixColumnArray'][] = array(
                'label' => _g('コピー'),
                'type' => 'copy',
                'isOrderby' => false
            );
            $form['gen_fixColumnArray'][] = array(
                'label' => _g('削除'),
                'type' => 'delete_check',
                'deleteClick' => 'javascript:doDelete();',  // escapeされるので & < > ' " は使えないことに注意
            );
        } else {
        $form['gen_fixColumnArray'][] = array(
            'label' => _g('削除'),
            'type' => 'delete_check',
            'showCondition' => "'[restricted_user]'=='t'",  // 一般ユーザーは、機能限定ユーザーのみ削除可能
            'deleteClick' => 'javascript:doDelete();',  // escapeされるので & < > ' " は使えないことに注意
        );
        }
        $form['gen_fixColumnArray'][] = array(
            'label' => _g('ユーザーID'),
            'field' => 'login_user_id',
            'width' => '200',
        );
        $form['gen_fixColumnArray'][] = array(
            'label' => _g('ユーザー名'),
            'field' => 'user_name',
            'width' => '200',
        );
        $form['gen_fixColumnArray'][] = array(
            'label' => _g('部門名'),
            'field' => 'section_name',
            'width' => '120',
        );
        $form['gen_fixColumnArray'][] = array(
            'label' => _g('取引先名'),
            'field' => 'customer_name',
            'width' => '200',
            'editType'=>'dropdown',
            'dropdownCategory'=>'customer_or_suppler',
            'entryField'=>'customer_id', 
        );
        $form['gen_columnArray'][] = array(
            'label' => _g('限定'),
            'field' => 'show_restricted_user',
            'width' => '40',
            'align' => 'center',
            'editType'=>'none',
            'helpText_noEscape' => _g("「限定」と表示されている場合、そのユーザーはパティオ・トークボード・スケジュール機能限定になっています。") . "<br><br>"
                . _g("新規作成されたユーザーはすべて機能限定ユーザーとなります。一般ユーザーを作成するには、ユーザーライセンス追加が必要です。詳細は販売元にお問い合わせください。")
        );
        $form['gen_columnArray'][] = array(
            'label' => _g('最終ログイン'),
            'field' => 'last_login_date',
            'width' => '130',
            'align' => 'center',
            'editType'=>'none',
        );
        $form['gen_columnArray'][] = array(
            'label' => _g('ロック'),
            'field' => 'show_account_lockout',
            'width' => '40',
            'align' => 'center',
            'editType'=>'none',
            'helpText_noEscape' => _g("「ロック」と表示されている場合、そのユーザーはログイン不可になっています。詳細は編集画面の「アカウントのロックアウト」のチップヘルプを参照してください。")
        );
        $form['gen_columnArray'][] = array(
            'label' => _g('パスワード有効期限'),
            'field' => 'valid_date',
            'width' => '120',
            'type' => 'date',
            'editType'=>'none',
        );

        $menuClass = new Logic_Menu();
        $menuArr = $menuClass->getMenuArray();
        $actionList = array();
        $option_3state = array(0 => '×', 1 => '△', 2 => '○');
        $option_2state = array(0 => '×', 1 => '○');
        foreach ($menuArr as $menuGroup) {
            foreach ($menuGroup as $menu) {
                $cap = str_replace("　", "", $menu[2]);
                // 同じクラスグループが複数回出てくる場合（Stock_Inoutなど）、最初の1つのみ表示。
                //   Stock_Inoutについては「入庫登録」を表示させるため、先に出てくる「支給」を排除。
                if ($cap == "" || in_array($menu[0], $actionList) || $menu[0] == 'Logout' || $cap == _g("支給登録"))
                    continue;
                $act = strtolower($menu[0]);
                $form['gen_columnArray'][] = array(
                    'label' => $cap,
                    'field' => $act,
                    'width' => '50',
                    'align' => 'center',
                    'editType'=>'select',
                    'editOptions'=>($menu[3] == "2" ? $option_2state : $option_3state),
                    'entryField'=>strtolower($menu[0]),                    
                    'hide' => true,
                );
                $actionList[] = $menu[0];
            }
        }
    }

}
