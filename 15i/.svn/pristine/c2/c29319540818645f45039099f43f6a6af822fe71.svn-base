<?php

class Config_UploadFile_List extends Base_ListBase
{

    function setSearchCondition(&$form)
    {
        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('画面名'),
                'field' => 'name',
            ),
            array(
                'label' => _g('ファイル名'),
                'field' => 'original_file_name',
            ),
            array(
                'label' => _g('登録者'),
                'field' => 'record_creator',
            ),
            array(
                'label' => _g('日時'),
                'type' => 'dateTimeFromTo',
                'field' => 'record_create_date',
                'size' => '120',
            ),
        );
    }

    function convertSearchCondition($converter, &$form)
    {
    }

    function beforeLogic(&$form)
    {
        global $gen_db;
        
        // action_group を画面名に変換するためのテンポラリテーブルを作る
        $query = "create temp table temp_action_group_name (action_group text, name text)";
        $gen_db->query($query);

        $menu = new Logic_Menu();
        $menuArr = $menu->getMenuBarArray();
        $query = "";
        $keyArr = array();
        foreach($menuArr as $menuGroup) {
            foreach($menuGroup as $menu) {
                if (!in_array($menu[0], $keyArr)) {
                    $query .= "insert into temp_action_group_name (action_group, name) values ('{$menu[0]}','$menu[2]');";
                    $keyArr[] = $menu[0];
                }
                if (!in_array($menu[0] . "_", $keyArr)) {
                    $query .= "insert into temp_action_group_name (action_group, name) values ('{$menu[0]}_','$menu[2]');";
                    $keyArr[] = $menu[0] . "_";
                }
            }
        }
        $gen_db->query($query);
        
    }

    function setQueryParam(&$form)
    {
        $this->selectQuery = "
            select
                temp_action_group_name.name
                ,upload_file_info.action_group
                ,record_id
                ,file_name
                ,original_file_name
                ,file_size
                ,upload_file_info.record_creator
                ,upload_file_info.record_create_date
            from
                upload_file_info
                left join temp_action_group_name on upload_file_info.action_group = temp_action_group_name.action_group
            [Where]
            [Orderby]
        ";
        $this->orderbyDefault = 'record_create_date desc';
    }

    function setViewParam(&$form)
    {
        $form['gen_pageTitle'] = _g("ファイルポケット一覧");
        $form['gen_listAction'] = "Config_UploadFile_List";
        $form['gen_excel'] = "true";
        
        // EditBaseを継承しているクラスで、$this->keyColumn が設定されているクラスはすべて
        // 添付ファイル登録の対象となるので、ここに書く必要がある。
        // 対象となるクラスを抽出するときは「$this->keyColumn = 」でソース全検索。
        $form['gen_javascript_noEscape'] = "
            function showRecord(actionGroup, id) {
                if (actionGroup.charAt(actionGroup.length - 1) == '_') {
                    actionGroup = actionGroup.substr(0, actionGroup.length - 1);
                }
                switch(actionGroup) {
        ";
        $arr = Logic_EditGroup::getAttachableGroupList();
        foreach($arr as $key => $val) {
            $form['gen_javascript_noEscape'] .= "case '{$key}': action = '{$val[0]}'; key = '{$val[1]}'; break;";
        }
        $form['gen_javascript_noEscape'] .= "
                default: alert('" . _g("この画面のレコードを直接開くことはできません。") . "'); return;
                }
                gen.modal.open('index.php?action=' + action + '&' + key + '=' + id);
            }
        ";

        $form['gen_fixColumnArray'] = array(
            array(
                'label' => _g('画面名'),
                'field' => 'name',
                'width' => 100,
            ),
            array(
                'label' => _g('ファイル名'),
                'field' => 'original_file_name',
                'width' => 250,
                'link' => "index.php?action=download&cat=files&file=[file_name]",
            ),
            array(
                'label' => _g('レコード'),
                'type' => 'literal',
                'literal_noEscape' => "<img src='img/application-form.png' class='gen_cell_img'>",
                'width' => 60,
                'align' => 'center',
                'link' => "javascript:showRecord('[action_group]','[record_id]')",
            ),
        );
        $form['gen_columnArray'] = array(
            array(
                'label' => _g('サイズ (Byte)'),
                'field' => 'file_size',
                'width' => 130,
                'type' => 'numeric',
            ),
            array(
                'label' => _g('登録者'),
                'field' => 'record_creator',
                'align' => 'center',
            ),
            array(
                'label' => _g('登録日時'),
                'field' => 'record_create_date',
                'type' => 'date',
                'align' => 'center',
                'width' => 200,
                'helpText_noEscape' => _g('日本時間で表示されます。'),
            ),
        );
    }

}