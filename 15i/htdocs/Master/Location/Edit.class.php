<?php

require_once("Model.class.php");

class Master_Location_Edit extends Base_EditBase
{

    function setQueryParam(&$form)
    {
        $this->keyColumn = 'location_id';
        $this->selectQuery = "
            select
                *
                ,coalesce(record_update_date, record_create_date) as gen_last_update
                ,coalesce(record_updater, record_creator) as gen_last_updater
            from
                location_master
            [Where]
            -- for excel
            order by
                location_code
        ";
    }

    function setViewParam(&$form)
    {
        $this->modelName = "Master_Location_Model";

        $form['gen_pageTitle'] = _g('ロケーションマスタ');
        $form['gen_entryAction'] = "Master_Location_Entry";
        $form['gen_listAction'] = "Master_Location_List";
        $form['gen_pageHelp'] = _g("サプライヤーロケ");

        $form['gen_editControlArray'] = array(
            array(
                'label' => _g('ロケーションコード'),
                'type' => 'textbox',
                'name' => 'location_code',
                'value' => @$form['location_code'],
                'size' => '12',
                'ime' => 'off',
                'require' => true
            ),
            array(
                'label' => _g('ロケーション名'),
                'type' => 'textbox',
                'name' => 'location_name',
                'value' => @$form['location_name'],
                'size' => '20',
                'ime' => 'on',
                'require' => true
            ),
            array(
                'label' => _g('サプライヤー'),
                'type' => 'dropdown',
                'name' => 'customer_id',
                'value' => @$form['customer_id'],
                'size' => '12',
                'subSize' => '20',
                'dropdownCategory' => (isset($_REQUEST['gen_excel']) && $_REQUEST['gen_excel'] == "true" ? 'partner' : 'partner_for_location'),
                'dropdownParam' => (is_numeric(@$form['customer_id']) ? $form['customer_id'] : ""),
                'helpText_noEscape' => _g("ここにサプライヤーを登録すると、このロケーションはサプライヤーロケ（他社在庫を管理するためのロケーション）になります。") . "<br>"
                    . _g("サプライヤーロケは主に、外製先に支給した材料（子品目）の在庫を管理するために使用されます。") . "<br><br>"
                    . _g("サプライヤーロケが存在する発注先に対して外製指示（支給あり）を発行すると、支給される子品目は次のような動きをします。まず外製指示の発行日に支給元ロケからサプライヤーロケに在庫が移動します。そして外製受入登録を行った日にサプライヤーロケから在庫が引き落とされます。") . "<br>"
                    . _g("なお、サプライヤーロケが存在する発注先については、自社情報マスタの「外製支給のタイミング」の設定は無視されることに注意してください。"),
            ),
        );
    }

}
