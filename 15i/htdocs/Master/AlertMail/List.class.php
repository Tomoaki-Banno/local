<?php

class Master_AlertMail_List extends Base_ListBase
{

    function setSearchCondition(&$form)
    {
        $form['gen_searchControlArray'] = array(
            array(
                'label' => _g('メールアドレス'),
                'field' => 'mail_address',
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
        $this->selectQuery = "
            select
                mail_address_id
                ,mail_address
                ,case when regist_flag then 0 else
                    case when regist_limit < now() then 1 else 2 end
                 end as regist_status
                ,case when regist_flag then '" . _g("有効") . "' else
                    case when regist_limit < now() then '" . _g("無効（本登録期限切れ）") . "' else '" . _g("保留（本登録待ち）") . "' end
                 end as regist_status_show

                ,mail_address_master.record_create_date as gen_record_create_date
                ,mail_address_master.record_creator as gen_record_creater
                ,coalesce(mail_address_master.record_update_date, mail_address_master.record_create_date) as gen_record_update_date
                ,coalesce(mail_address_master.record_updater, mail_address_master.record_creator) as gen_record_updater

             from
                mail_address_master
             [Where]
             [Orderby]
            ";

        $this->orderbyDefault = 'mail_address_id';
    }

    function setViewParam(&$form)
    {
        $form['gen_pageTitle'] = _g('通知メールの設定');
        $form['gen_menuAction'] = "Menu_Master";
        $form['gen_listAction'] = "Master_AlertMail_List";
        $form['gen_editAction'] = "Master_AlertMail_Edit";
        $form['gen_idField'] = 'mail_address_id';
        $form['gen_excel'] = "true";
        $form['gen_pageHelp'] = _g("通知メール");

        $form['gen_isClickableTable'] = "true";

        $form['gen_rowColorCondition'] = array(
            "#d7d7d7" => "'[regist_status]'=='1'", // 無効
            "#b6eae4" => "'[regist_status]'=='2'", // 保留
        );
        $form['gen_colorSample'] = array(
            "d7d7d7" => array(_g("シルバー"), _g("無効")),
            "b6eae4" => array(_g("グリーン"), _g("保留")),
        );

        $form['gen_columnArray'] = array(
            array(
                'label' => _g('明細'),
                'type' => 'edit',
            ),
            array(
                'label' => _g('コピー'),
                'type' => 'copy',
                'isOrderby' => false,
            ),
            array(
                'label' => _g('削除'),
                'type' => 'delete_check',
                'deleteAction' => 'Master_AlertMail_BulkDelete',
                // readonlyであれば表示しない
                'showCondition' => ($form['gen_readonly'] != 'true' ? "true" : "false"),
            ),
            array(
                'label' => _g('メールアドレス'),
                'field' => 'mail_address',
                'width' => '300',
            ),
            array(
                'label' => _g('状態'),
                'field' => 'regist_status_show',
                'align' => 'center',
            ),
        );
    }

}