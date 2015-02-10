<?php

require_once("Model.class.php");

class Stock_SeibanChange_Edit extends Base_EditBase
{

    function convert($converter, &$form)
    {
        $converter->nullBlankToValue('change_date', date("Y-m-d"));
    }

    // 既存データSQL実行前に処理
    function setQueryParam(&$form)
    {
        $this->keyColumn = 'change_id';
        $this->selectQuery = "
            select
                change_id
                ,seiban_change.item_id
                ,change_date
                ,cast(seiban_change.item_id as text) || '_' ||
                    seiban_change.source_seiban || '_' ||
                    cast(seiban_change.location_id as text) || '_' ||
                    '0' as item_seiban_location_lot
                ,cast(seiban_change.item_id as text) || '_' ||
                    seiban_change.dist_seiban || '_' ||
                    cast(seiban_change.location_id as text) || '_' ||
                    '0' as dist_item_seiban_location_lot
                ,quantity
                ,remarks
                ,coalesce(seiban_change.record_update_date, seiban_change.record_create_date) as gen_last_update
                ,coalesce(seiban_change.record_updater, seiban_change.record_creator) as gen_last_updater
            from
                seiban_change
                inner join item_master on seiban_change.item_id = item_master.item_id
            [Where]
        ";

        // データロックの判断基準となるフィールドを指定
        $form["gen_dateLockFieldArray"] = array("change_date");
    }

    // 既存データSQL実行後に処理
    // 画面表示のための設定
    function setViewParam(&$form)
    {
        $this->modelName = "Stock_SeibanChange_Model";

        $form['gen_pageTitle'] = _g("製番引当登録");
        $form['gen_entryAction'] = "Stock_SeibanChange_Entry";
        $form['gen_listAction'] = "Stock_SeibanChange_List";
        $form['gen_onLoad_noEscape'] = "onStockIdChange();";
        $form['gen_pageHelp'] = _g("フリー製番");

        $form['gen_javascript_noEscape'] = "
            var beforeItemCode = '';
            // 引当元製番在庫セレクタ変更イベント（ページロード時にも実行）。
            function onStockIdChange() {
                var id = $('#item_seiban_location_lot').val();
                if (id=='') return;
                beforeItemCode = $('#item_code').val();
                gen.edit.submitDisabled();
                gen.ui.disabled($('#dist_item_seiban_location_lot_dropdown'));
                gen.ajax.connect('Stock_SeibanChange_AjaxStockParam', {item_seiban_location_lot : id}, 
                    function(j){
                        if (j != '') {
                            if (j.location_name == '') 
                                j.location_name = '" . _g(GEN_DEFAULT_LOCATION_NAME) . "';
                            $('#item_code').val(j.item_code);
                            $('#item_name').val(j.item_name);
                            $('#location_name').val(j.location_name);
                            if ($('#quantity').val() == '')
                                $('#quantity').val(j.qty);
                            // 引当元の品目が変更されたときは引当先をクリア。
                            // 条件の2行目は、変更モードでの初回呼び出し時にクリアされないようにするため
                            if (beforeItemCode != j.item_code && beforeItemCode != '') {
                               $('#dist_item_seiban_location_lot').val('');
                               $('#dist_item_seiban_location_lot_show').val('');
                            }
                        }
                        gen.edit.submitEnabled('" . h($form['gen_readonly']) . "');
                        // 引当元が指定されているときのみ引当先を選べるようにする
                        if ($('#item_code').value != '')
                            gen.ui.enabled($('#dist_item_seiban_location_lot_dropdown'));
                        seibanBlankToNashi();
                    });
                $('#item_code').val('');
                $('#item_name').val('');
                $('#location_name').val('');
            }

            // 製番なしの場合は「(なし)」と表示。
            function seibanBlankToNashi() {
               var idElm = $('#item_seiban_location_lot');
               var idElm_show = $('#item_seiban_location_lot_show');
               if (idElm.val() != '' && idElm_show.val() == '' ) idElm_show.val('(" . _g("なし") . ")');
               idElm = $('#dist_item_seiban_location_lot');
               idElm_show = $('#dist_item_seiban_location_lot_show');
               if (idElm.val() != '' && idElm_show.val() == '' ) idElm_show.val('(" . _g("なし") . ")');
            }
        ";

        $form['gen_editControlArray'] = array(
            array(
                'label' => _g('引当元製番'),
                'type' => 'dropdown',
                'name' => 'item_seiban_location_lot',
                'value' => @$form['item_seiban_location_lot'],
                'dropdownCategory' => 'seiban_stock',   // 詳細な表示条件はLogic_Dropdownの該当箇所を参照
                'noWrite' => true,
                'require' => true,
                'size' => '12',
                'onChange_noEscape' => "onStockIdChange()",
                'helpText_noEscape' => _g("この画面では、製番品目の在庫の製番を振り替え（引き当て）ます。フリー製番在庫（製番なしの在庫）を別の製番に振り替えたり、製番在庫を別の製番、あるいはフリーにすることができます。") . "<br><br>"
                    . _g("この項目で振り替え前の製番を指定します。製番在庫が存在する製番が選択肢として表示されます。") . "<br>"
                    . _g("MRP品目や、在庫のない製番品目は表示されません。")
            ),
            array(
                'label' => _g('品目コード'),
                'type' => 'textbox',
                'name' => 'item_code',
                'value' => @$form['item_code'],
                'size' => '20',
                'readonly' => true
            ),
            array(
                'label' => _g('引当先製番'),
                'type' => 'dropdown',
                'name' => 'dist_item_seiban_location_lot',
                'value' => @$form['dist_item_seiban_location_lot'],
                'dropdownCategory' => 'seiban_stock_dist',   // 詳細な表示条件はLogic_Dropdownの該当箇所を参照
                'dropdownParam' => '[item_code];[item_seiban_location_lot_show]',
                'noWrite' => true,
                'require' => true,
                'size' => '12',
                'helpText_noEscape' => _g('振り替え（引き当て）後の製番を指定します。フリー製番にしたい場合は「なし」を指定します。')
            ),
            array(
                'label' => _g('品目名'),
                'type' => 'textbox',
                'name' => 'item_name',
                'value' => @$form['item_name'],
                'size' => '20',
                'readonly' => true
            ),
            array(
                'label' => _g('日付'),
                'type' => 'calendar',
                'name' => 'change_date',
                'value' => @$form['change_date'],
                'size' => '8',
                'require' => true,
                'isCalendar' => true
            ),
            array(
                'label' => _g('ロケーション'),
                'type' => 'textbox',
                'name' => 'location_name',
                'value' => @$form['location_name'],
                'size' => '20',
                'readonly' => true
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
                'label' => _g('製番引当備考'),
                'type' => 'textbox',
                'name' => 'remarks',
                'value' => @$form['remarks'],
                'ime' => 'on',
                'size' => '20'
            ),
        );
    }

}