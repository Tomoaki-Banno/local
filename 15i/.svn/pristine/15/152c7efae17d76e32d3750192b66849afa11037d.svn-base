<?php

require_once("Model.class.php");

class Stock_Move_Edit extends Base_EditBase
{

    function convert($converter, &$form)
    {
        $converter->nullBlankToValue('move_date', date("Y-m-d"));
    }

    // 既存データSQL実行前に処理
    function setQueryParam(&$form)
    {
        $this->keyColumn = 'move_id';
        $this->selectQuery = "
            select
                move_id
                ,cast(location_move.item_id as text) || '_' ||
                    location_move.seiban || '_' ||
                    cast(source_location_id as text) || '_' ||
                    '0' as item_seiban_location_lot
                ,location_move.item_id
                ,location_move.seiban
                ,move_date
                ,source_location_id
                ,dist_location_id
                ,quantity
                ,order_detail_id
                ,remarks
                ,coalesce(location_move.record_update_date, location_move.record_create_date) as gen_last_update
                ,coalesce(location_move.record_updater, location_move.record_creator) as gen_last_updater
            from
                location_move
                inner join item_master on location_move.item_id = item_master.item_id
            [Where]
        ";

        // データロックの判断基準となるフィールドを指定
        $form["gen_dateLockFieldArray"] = array("move_date");
    }

    // 既存データSQL実行後に処理
    // 画面表示のための設定
    function setViewParam(&$form)
    {
        global $gen_db;

        $this->modelName = "Stock_Move_Model";

        $form['gen_pageTitle'] = _g("ロケーション間移動登録");
        $form['gen_entryAction'] = "Stock_Move_Entry";
        $form['gen_listAction'] = "Stock_Move_List";
        $form['gen_onLoad_noEscape'] = "onStockIdChange();";
        $form['gen_pageHelp'] = _g("ロケーション");

        $form['gen_javascript_noEscape'] = "
            // 品目セレクタ変更イベント（ページロード時にも実行）。
            function onStockIdChange() {
                var id = document.getElementById('item_seiban_location_lot').value;
                if (id=='') return;
                gen.edit.submitDisabled();
                $('#seiban').val('');
                $('#source_location_name').val('');
                gen.ajax.connect('Stock_Move_AjaxStockParam', {item_seiban_location_lot : id}, 
                    function(j){
                        if (j != '') {
                            if (j.seiban == '') 
                                j.seiban = '(" . _g("なし") . ")';
                            $('#seiban').val(j.seiban);
                            if (j.location_name == '') 
                                j.location_name = '" . _g(GEN_DEFAULT_LOCATION_NAME) . "';
                            $('#source_location_name').val(j.location_name);
                            if ($('#quantity').val() == '')
                                $('#quantity').val(j.qty);
                        }
                        gen.edit.submitEnabled('" . h($form['gen_readonly']) . "');
                    });
            }
        ";

        $query = "select location_id, location_name from location_master order by location_code";
        $option_location_group = $gen_db->getHtmlOptionArray($query, false, array("0" => _g(GEN_DEFAULT_LOCATION_NAME)));

        $form['gen_editControlArray'] = array(
            array(
                'label' => _g('品目コード'),
                'type' => 'dropdown',
                'name' => 'item_seiban_location_lot',
                'value' => @$form['item_seiban_location_lot'],
                'dropdownCategory' => 'stock',
                'require' => true,
                'onChange_noEscape' => "onStockIdChange()",
                'noWrite' => true,
                'helpText_noEscape' => _g("移動する品目を選択します。選択肢には在庫がある品目だけが表示されます。"),
                'size' => '12',
                'subSize' => '15',
            ),
            array(
                'label' => _g('移動日'),
                'type' => 'calendar',
                'name' => 'move_date',
                'value' => @$form['move_date'],
                'size' => '8',
                'require' => true,
                'isCalendar' => true
            ),
            array(
                'label' => _g('移動元ロケーション'),
                'type' => 'text',
                'name' => 'source_location_name',
                'readonly' => 'true',
                'size' => '35'
            ),
            array(
                'label' => _g('製番'),
                'type' => 'textbox',
                'name' => 'seiban',
                'value' => @$form['seiban'],
                'readonly' => 'true',
                'size' => '10'
            ),
            array(
                'label' => _g('移動先ロケーション'),
                'type' => 'select',
                'name' => 'dist_location_id',
                'options' => $option_location_group,
                'selected' => @$form['dist_location_id'],
            ),
            array(
                'label' => _g('数量'),
                'type' => 'textbox',
                'name' => 'quantity',
                'value' => @$form['quantity'],
                'require' => true,
                'ime' => 'off',
                'size' => '8'
            ),
            array(
                'label' => _g('ロケ間移動備考'),
                'type' => 'textbox',
                'name' => 'remarks',
                'value' => @$form['remarks'],
                'ime' => 'on',
                'size' => '30'
            ),
            array(
                'label' => _g('オーダー番号'),
                'type' => 'dropdown',
                'name' => 'order_detail_id',
                'dropdownCategory' => 'manufacturing',
                'value' => @$form['order_detail_id'],
                'ime' => 'off',
                'size' => '10'
            ),
        );
    }

}
