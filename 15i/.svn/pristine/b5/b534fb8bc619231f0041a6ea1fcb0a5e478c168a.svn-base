<?php

require_once("Model.class.php");

class Master_Item_Edit extends Base_EditBase
{

    function convert($converter, &$form)
    {
        global $gen_db;

        // 10iまではLTのデフォルト値を0にしていたが、それだと手配先を選択したときに
        // 取引先マスタの標準LTが反映されない。
        //$converter->nullBlankToValue("lead_time", 0);

        for ($i = 0; $i < GEN_ITEM_ORDER_COUNT; $i++) {
            $converter->nullBlankToValue("default_lot_unit_{$i}", 0);
            $converter->nullBlankToValue("default_order_price_{$i}", 0);
        }
        for ($i = 0; $i < GEN_ITEM_PROCESS_COUNT; $i++) {
            $converter->nullBlankToValue("process_lt_{$i}", 0);
        }
        $converter->nullBlankToValue("quantity_per_carton", 1);

        // この直前に新規登録されたレコードで「この品目の構成を登録する」が指定されていた場合の処理。
        // 同じレコードを編集モードで開くよう、item_idを設定する。
        if (isset($form['bomEdit']) && (!isset($form['gen_validError']) || !$form['gen_validError'])) {
            $form['item_id'] = $gen_db->getSequence("item_master_item_id_seq");
        }
    }

    function setQueryParam(&$form)
    {
        $this->keyColumn = 'item_id';

        $this->selectQuery = "
            select
                -- end_itemの置き換えを行っているので * は使えない
                item_master.item_id
                ,item_master.item_code
                ,item_master.item_name
                ,item_master.order_class
                ,item_master.lead_time
                ,item_master.safety_lead_time
                ,item_master.item_group_id
                ,item_master.item_group_id_2
                ,item_master.item_group_id_3
                ,item_master.stock_price
                ,item_master.safety_stock
                ,item_master.received_object
                ,item_master.maker_name
                ,item_master.spec
                ,item_master.without_mrp
                ,item_master.use_by_days
                ,item_master.lot_header
                ,item_master.comment
                ,item_master.comment_2
                ,item_master.comment_3
                ,item_master.comment_4
                ,item_master.comment_5
                ,item_master.llc
                ,item_master.default_selling_price
                ,item_master.default_selling_price_2
                ,item_master.default_selling_price_3
                ,item_master.selling_price_limit_qty_1
                ,item_master.selling_price_limit_qty_2
                ,item_master.tax_class
                ,item_master.tax_rate
                ,case when end_item then 'true' else '' end as end_item
                ,case when dummy_item then 'true' else '' end as dummy_item
                ,item_master.drawing_file_oid
                ,item_master.drawing_file_name
                ,item_master.measure
                ,item_master.payout_price
                ,item_master.rack_no
                ,item_master.quantity_per_carton
                ,item_master.default_location_id
                ,item_master.default_location_id_2
                ,item_master.default_location_id_3
                ,item_master.dropdown_flag

                ,t0.*
                ,t1.*
                ,t2.*

                ,coalesce(item_master.record_update_date, item_master.record_create_date) as gen_last_update
                ,coalesce(item_master.record_updater, item_master.record_creator) as gen_last_updater
            from
                item_master
                left join (
                    select
                        item_id as iid0
                        ,max(case when classification in ('in','manufacturing') then item_in_out_date end) as last_in_date
                        ,max(case when classification in ('out','payout','use','delivery') then item_in_out_date end) as last_out_date
                    from
                        item_in_out
                    group by
                        item_id
                    ) as t0 on item_master.item_id = t0.iid0
                left join (
                    select
                        item_id as iid1
                        ";
                        // 手配先（item_order_master）
                        for ($i = 0; $i < GEN_ITEM_ORDER_COUNT; $i++) {
                            $this->selectQuery .= "
                                ,max(case when line_number = {$i} then order_user_id else null end) as order_user_id_{$i}

                                ,max(case when line_number = {$i} then default_order_price else null end) as default_order_price_{$i}
                                ,max(case when line_number = {$i} then default_order_price_2 else null end) as default_order_price_2_{$i}
                                ,max(case when line_number = {$i} then default_order_price_3 else null end) as default_order_price_3_{$i}

                                ,max(case when line_number = {$i} then order_price_limit_qty_1 else null end) as order_price_limit_qty_1_{$i}
                                ,max(case when line_number = {$i} then order_price_limit_qty_2 else null end) as order_price_limit_qty_2_{$i}

                                ,max(case when line_number = {$i} then default_lot_unit else null end) as default_lot_unit_{$i}
                                ,max(case when line_number = {$i} then default_lot_unit_2 else null end) as default_lot_unit_2_{$i}

                                ,max(case when line_number = {$i} then item_sub_code else null end) as item_sub_code_{$i}
                                ,max(case when line_number = {$i} then partner_class else null end) as partner_class_{$i}
                                ,max(case when line_number = {$i} then order_measure else null end) as order_measure_{$i}
                                ,max(case when line_number = {$i} then multiple_of_order_measure else null end) as multiple_of_order_measure_{$i}
                            ";
                        }
                        $this->selectQuery .= "
            from
                item_order_master
            group by
                item_id
            ) as t1
            on item_master.item_id = t1.iid1
            left join (
                select
                    item_id as iid2
                    ";
                    // 工程（item_process_master）
                    for ($i = 0; $i < GEN_ITEM_PROCESS_COUNT; $i++) {
                        $this->selectQuery .= "
                            ,max(case when machining_sequence = {$i} then process_id else null end) as process_id_{$i}
                            ,max(case when machining_sequence = {$i} then default_work_minute else null end) as default_work_minute_{$i}
                            ,max(case when machining_sequence = {$i} then pcs_per_day else null end) as pcs_per_day_{$i}
                            ,max(case when machining_sequence = {$i} then charge_price else null end) as charge_price_{$i}
                            ,max(case when machining_sequence = {$i} then overhead_cost else null end) as overhead_cost_{$i}
                            ,max(case when machining_sequence = {$i} then process_lt else null end) as process_lt_{$i}
                            ,max(case when machining_sequence = {$i} then subcontract_partner_id else null end) as subcontract_partner_id_{$i}
                            ,max(case when machining_sequence = {$i} then subcontract_unit_price else null end) as subcontract_unit_price_{$i}
                            ,max(case when machining_sequence = {$i} then process_remarks_1 else null end) as process_remarks_1_{$i}
                            ,max(case when machining_sequence = {$i} then process_remarks_2 else null end) as process_remarks_2_{$i}
                            ,max(case when machining_sequence = {$i} then process_remarks_3 else null end) as process_remarks_3_{$i}
                        ";
                    }
                    $this->selectQuery .= "
                        from
                            item_process_master
                        group by
                            item_id
                    ) as t2
                    on item_master.item_id = t2.iid2
            [Where]
                -- for excel
            order by
                item_code
        ";
    }

    function setViewParam(&$form)
    {
        global $gen_db;

        $this->modelName = "Master_Item_Model";

        $form['gen_pageTitle'] = _g('品目マスタ');
        $form['gen_entryAction'] = "Master_Item_Entry";
        $form['gen_listAction'] = "Master_Item_List";
        $form['gen_onLoad_noEscape'] = "onLoad()";
        $form['gen_pageHelp'] = _g("ダミー品目");

        $isExistChild = false;
        if (isset($form['item_id']) && !isset($form['gen_record_copy'])) {
            $query = " select item_id from bom_master where item_id ";
            if (isset($form['gen_multi_edit'])) {
                $query .= "in ({$form['item_id']})";
            } else {
                $query .= "= '{$form['item_id']}'";
            }
            $isExistChild = $gen_db->existRecord($query);
        }

        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");

        $form['gen_javascript_noEscape'] = "
            // onLoad
            function onLoad() {
                onOrderClassChange();
                onAllOrderUserIdChange();
                onAllPartnerClassChange();
                onItemCodeChange();
                calcTotalProcess()
                onTaxClassChange(true)
            " .
                // ここで処理するのは、readonlyによってlabelの内容を変えないようにするため
                (@$form["gen_readonly"] == 'true' ? "
                    for (i=0; i<" . GEN_ITEM_ORDER_COUNT . "; i++) {
                        $('#changeDefButton'+i).attr('disabled', 'disabled');
                    }
                    for (i=0; i<" . GEN_ITEM_PROCESS_COUNT . "; i++) {
                        $('#processControl'+i).css('display', 'none');
                    }
                " : "") . "

                // この直前に新規登録されたレコードで「この品目の構成を登録する」が指定されていた場合の処理。
                " . (isset($form['bomEdit']) && (!isset($form['gen_validError']) || !$form['gen_validError']) ? "
                    window.open('index.php?action=Master_Bom_List&is_item_master=true&parent_item_id=" . h($form['item_id']) . "');
                " : "") . "
            }

            // ********** ヘッダ **********

            // 構成表マスタリンク
            function goBom() {
                " . (isset($form['item_id']) ? "
                    // 編集モード
                    window.open('index.php?action=Master_Bom_List&is_item_master=true&parent_item_id=" . h($form['item_id']) . "');
                " : "
                    // 新規モード
                    if (!confirm('" . _g("構成登録の前に、まずこの品目を登録する必要があります。登録後、新しいウィンドウ（タブ）で構成表マスタ画面を開きます。登録してもよろしいですか？") . "')) {
                        return;
                    }
                    // Master_Item_EditのConverterに引き渡すフラグ
                    $('#form1').append(\"<input type='hidden' name='bomEdit' value='true'>\");
                    // ダイレクトマスタ登録のための処理。
                    // 通常の新規モードでの構成表マスタオープンは 登録後にこの画面をもう一度開いて行うのだが（このクラスの前のほうに処理がある）、
                    // ダイレクトマスタ登録の場合は登録後に品目マスタ画面を閉じてしまうので、その処理が行えない。
                    // そこで overlapmodalclose.tplに特殊なフラグを渡し、そこで構成表マスタオープンを行う。
                    " . (isset($form['gen_overlapFrame']) ? "$('#form1').append(\"<input type='hidden' name='bomWindowOpenWhenOverlapClose' value='true'>\");" : "") . "
                    gen_onSubmit();
                ") . "
            }

            // 品目コード
            function onItemCodeChange() {
                var code = $('#item_code').val();
                if (code.length > 30) code = code.substr(0,30) + '...';
                var name = $('#item_name').val();
                if (name.length > 30) name = name.substr(0,30) + '...';
                if (code == '' & name == '') return;
                var msg = '  ( ' + code + ' : ' + name + ' )';
                $('#gen_titlebar').text(msg);   // html()とは異なり、サニタイジングが行われる
            }

            // 管理区分
            // 製番品目でも安全在庫数を設定できる（在庫推移リスト画面の表示用）
            function onOrderClassChange() {
                if ($('#order_class').val() == 0) {
                    gen.ui.alterDisabled($('#without_mrp'), true);
                } else {
                    gen.ui.alterDisabled($('#without_mrp'), false);
                }
            }

            // 手配区分一括設定
            function onAllPartnerClassChange() {
                for (i=0; i<" . GEN_ITEM_ORDER_COUNT . "; i++) {
                    onPartnerClassChange(i, true);
                }
            }

            // 手配区分
            function onPartnerClassChange(number, isAll) {
                var pc = $('#partner_class_' + number).val();
                if ((pc == '0' || pc == '1') && " . ($isExistChild ? "true" : "false") . " && !isAll && number==0) {
                    alert('" . _g("構成表マスタで子品目が登録されています。標準手配区分を「発注」もしくは「外注(支給なし)」に変更すると、所要量計算・原価計算等において子品目の情報は反映されなくなります。") . "');
                }

                if (pc == '3' || pc == 'null') {     // 内製か「なし」なら
                    $('#order_user_id_' + number + '_show').val('');
                    $('#order_user_id_' + number + '_sub').val('');
                    gen.ui.alterDisabled($('#order_user_id_' + number + '_show'), true);
                    gen.ui.alterDisabled($('#order_user_id_' + number + '_sub'), true);
                    gen.ui.alterDisabled($('#order_user_id_' + number + '_dropdown'), true);
                    gen.ui.alterDisabled($('#default_order_price_' + number), true);
                    gen.ui.alterDisabled($('#default_order_price_2_' + number), true);
                    gen.ui.alterDisabled($('#default_order_price_3_' + number), true);
                    gen.ui.alterDisabled($('#order_price_limit_qty_1_' + number), true);
                    gen.ui.alterDisabled($('#order_price_limit_qty_2_' + number), true);
                    gen.ui.alterDisabled($('#item_sub_code_' + number), true);
                    gen.ui.alterDisabled($('#order_measure_' + number), true);
                    gen.ui.alterDisabled($('#multiple_of_order_measure_' + number), true);
                } else {
                    gen.ui.alterDisabled($('#order_user_id_' + number + '_show'), false);
                    gen.ui.alterDisabled($('#order_user_id_' + number + '_sub'), false);
                    gen.ui.alterDisabled($('#order_user_id_' + number + '_dropdown'), false);
                    gen.ui.alterDisabled($('#default_order_price_' + number), false);
                    gen.ui.alterDisabled($('#default_order_price_2_' + number), false);
                    gen.ui.alterDisabled($('#default_order_price_3_' + number), false);
                    gen.ui.alterDisabled($('#order_price_limit_qty_1_' + number), false);
                    gen.ui.alterDisabled($('#order_price_limit_qty_2_' + number), false);
                    gen.ui.alterDisabled($('#item_sub_code_' + number), false);
                    gen.ui.alterDisabled($('#order_measure_' + number), false);
                    gen.ui.alterDisabled($('#multiple_of_order_measure_' + number), false);
                }
            }

            // 手配先一括設定
            function onAllOrderUserIdChange() {
                for (i=0; i<" . GEN_ITEM_ORDER_COUNT . "; i++) {
                    onOrderUserIdChange(i);
                }
            }

            // 手配先
            function onOrderUserIdChange(number) {
                //if (number != 0) return;
                var lteElm = $('#lead_time');
                var oui = $('#order_user_id_' + number).val();
                if (oui!='' && oui!='0') {
                    gen.ajax.connect('Master_Item_AjaxLeadTime', {partner_id : oui},
                        function(j) {
                            if (j.default_lead_time == '' || j.default_lead_time == null)
                                j.default_lead_time = 0;
                            if (number==0 && lteElm.val()=='' && gen.util.isNumeric(j.default_lead_time)) {
                                lteElm.val(j.default_lead_time);
                                lteElm.trigger('change');   // クライアントバリデーションエラーの消去
                            }
                            $('#currency_name_' + number).val(j.currency_name);
                        });
                } else {
                    $('#currency_name_' + number).val('');
                }
                changeMeasureLabel(i);
            }

            // ********** 詳細項目 Tab **********

            // 課税区分
            function onTaxClassChange(isInit) {
            	// 課税区分で税率を制御する
                if ($('#tax_class').val()=='0') {
                    // 課税
                    gen.ui.alterDisabled($('#tax_rate'), false);
                    $('#tax_rate')
                        .css('background-color','#ffffff')
                        .css('color','#000000');
                    if (!isInit) {
                        $('#tax_rate').val('');
                    }
                } else {
                    // 非課税
                    gen.ui.alterDisabled($('#tax_rate'), true);
                    $('#tax_rate')
                        .val(0)
                        .css('background-color','#cccccc');
                }
            }

            // ********** 所要量計算 Tab **********

            // リードタイム
            function onLTChange() {
            	// 標準工程があればそちらに反映する
                if ($('#process_id_0').val()=='0') {
                    $('#process_lt_0').val($('#lead_time').val());
                }
            }

            // ********** 発注・外注 Tab **********

            // 単位ラベル表示
            function changeMeasureLabel(no) {
                showMeasure('default_lot_unit_' + no, no, false);
                showMeasure('default_lot_unit_2_' + no, no, false);
                showMeasure('order_price_limit_qty_1_' + no, no, false);
                showMeasure('order_price_limit_qty_2_' + no, no, false);
                showMeasure('default_order_price_' + no, no, true);
                showMeasure('default_order_price_2_' + no, no, true);
                showMeasure('default_order_price_3_' + no, no, true);
            }

            function showMeasure(parentId, no, isPrice) {
                var measure = $('#measure').val();
                var orderMeasure = $('#order_measure_' + no).val();
                var mul = $('#multiple_of_order_measure_' + no).val();

                var labelId = parentId + '_label';
                if ($('#' + labelId)!=null) {
                    $('#' + labelId).remove();
                }
                var elm = document.createElement('span');
                elm.id = labelId;
                elm.innerHTML = '&nbsp;&nbsp;&nbsp;' + (isPrice ? '/&nbsp;' : '') + measure
                    + getSubMeasure(parentId, orderMeasure, mul, isPrice);
                $('#' + parentId).after(elm);
            }

            function getSubMeasure(parentId, orderMeasure, mul, isPrice) {
                var label = '';
                if (gen.util.isNumeric(mul) && mul != '0') {
                    var val = $('#' + parentId).val();
                    if (gen.util.isNumeric(val)) {
                        if (isPrice) {
                            var after = (parseFloat(val) * parseFloat(mul));
                        } else {
                            var after = (parseFloat(val) / parseFloat(mul));
                        }
                        after = Math.round(after * 1000) / 1000;
                        label = '&nbsp;&nbsp;（' + after + '&nbsp;' + (isPrice ? '/&nbsp;' : '');
                        //label = '&nbsp;&nbsp;（' + (isPrice ? '{$keyCurrency}' : '') + after + '&nbsp;' + (isPrice ? '/&nbsp;' : '');
                        label += (orderMeasure=='' ? 'pcs' : orderMeasure) + '）';
                    }
                }
                return label;
            }

            // 代替手配先と標準手配先を入れ替える
            function itemOrderSetDefault(no) {
                if ($('#partner_class_'+no).val()=='null') {
                    alert('" . _g("手配区分が設定されていません。") . "');
                    return;
                }
                if (!confirm('" . _g("この手配先を標準手配先と入れ替えます。よろしいですか？") . "')) return;

                swapProp('partner_class',0,no);
                swapProp('order_user_id',0,no);
                swapProp('order_user_id',0,no,'_show');
                swapProp('order_user_id',0,no,'_sub');
                swapProp('item_sub_code',0,no);
                swapProp('order_measure',0,no);
                swapProp('multiple_of_order_measure',0,no);
                swapProp('default_order_price',0,no);
                swapProp('default_order_price_2',0,no);
                swapProp('default_order_price_3',0,no);
                swapProp('order_price_limit_qty_1',0,no);
                swapProp('order_price_limit_qty_2',0,no);

                onAllPartnerClassChange();
                onAllOrderUserIdChange();
            }

            // ********** 工程 Tab **********

            // 工程
            function onProcessChange(number) {
                var id = $('#process_id_' + number).val();
                var lteElm = $('#process_lt_' + number);
                if (id!='' && (lteElm.val()=='' || lteElm.val()=='0')) {
                    gen.ajax.connect('Master_Item_AjaxLeadTime', {process_id : id},
                        function(j) {
                            if (gen.util.isNumeric(j.default_lead_time))
                                lteElm.val(j.default_lead_time);
                            lteElm.trigger('onchange');
                        });
                }
                calcTotalProcess();
            }

            // 工程リードタイム
            function onProcessLTChange() {
            	// 工程LTの合計を全体LTに反映
                var ltSum = 0;
                var lt = 0;
                $('[id^=process_lt_]').each(function(){
                    if (this.id.substr(-6)=='_error') return true;	// continue
                    lt = this.value;
                    if (gen.util.isNumeric(lt)) {
                    	ltSum += parseFloat(lt);
                    } else {
                    	// ひとつでもLT非数値の工程があれば、全体LTは空欄（可変LT）とする
    			nArr = this.id.split('_');
                    	no = nArr[nArr.length-1];
                    	pid = $('#process_id_'+no).val();
                    	if (pid!='' && pid!='0') {
                            $('#lead_time').val('');
                            ltSum = '';
                            return false;	// break
    			}
                    }
                });
                if (ltSum!='') $('#lead_time').val(ltSum);
                calcTotalProcess();
            }

            // 工程タブ合計値の計算
            function calcTotalProcess() {
                var wm = 0;
                var ca = 0;
                var cc = 0;
                var lt = 0;
                $('[id^=default_work_minute_]').each(function(){
                    no = this.id.substr(20);

                    // 工程が選択されているものだけ合計する
                    if ($('#process_id_'+no).val() != '') {
                        min = this.value;
                        if (!gen.util.isNumeric(min)) min = 0;
                        wm = gen.util.decCalc(wm,min,'+');

                        price = $('#charge_price_'+no).val();
                        if (!gen.util.isNumeric(price)) price = 0;
                        ca = gen.util.decCalc(ca,gen.util.decCalc(min,price,'*'),'+');

                        subcost = $('#subcontract_unit_price_'+no).val();
                        if (!gen.util.isNumeric(subcost)) subcost = 0;

                        cost = $('#overhead_cost_'+no).val();
                        if (!gen.util.isNumeric(cost)) cost = 0;
                        cc = gen.util.decCalc(cc,gen.util.decCalc(gen.util.decCalc(gen.util.decCalc(min,price,'*'),subcost,'+'),cost,'+'),'+');

                        leadtime = $('#process_lt_'+no).val();
                        if (!gen.util.isNumeric(leadtime)) leadtime = 0;
                        lt = gen.util.decCalc(lt,leadtime,'+');
                    }
                });
                $('#total_work_minute').html(gen.util.addFigure(wm));
                $('#total_charge_amount').html(gen.util.addFigure(ca));
                $('#total_charge_amount_cost').html(gen.util.addFigure(cc));
                $('#total_process_lt').html(gen.util.addFigure(lt));
            }

            // 工程操作ボタン
            function upProcess(no) {
                if (!confirm('" . _g("この工程をひとつ前の工程と入れ替えます。よろしいですか？") . "')) return;
                swapProcess(no,no-1);
            }
            function downProcess(no) {
                if (!confirm('" . _g("この工程を次の工程と入れ替えます。よろしいですか？") . "')) return;
                swapProcess(no,no+1);
            }
            function deleteProcess(no) {
                if (!confirm('" . _g("この工程を削除します。よろしいですか？") . "')) return;
                var max = " . GEN_ITEM_PROCESS_COUNT . "-1;
                for (var i=no;i<max;i++) {
                    swapProcess(i,i+1);
                }
                clearProcess(max);
                calcTotalProcess();
            }
            function insertProcess(no) {
                var max = " . GEN_ITEM_PROCESS_COUNT . "-1;
                if ($('#process_id_'+max).val()!='') {
                    alert('これ以上 工程を挿入することはできません。');
                    return;
                }
                if (!confirm('" . _g("工程を挿入します。よろしいですか？") . "')) return;
                for (var i=max;i>no;i--) {
                    swapProcess(i,i-1);
                }
                clearProcess(no);
            }
            function swapProcess(no1, no2) {
                swapProp('process_id', no1, no2);
                swapProp('default_work_minute', no1, no2);
                swapProp('pcs_per_day', no1, no2);
                swapProp('charge_price', no1, no2);
                swapProp('subcontract_partner_id', no1, no2);
                swapProp('subcontract_partner_id', no1, no2, '_show');
                swapProp('subcontract_partner_id', no1, no2, '_sub');
                swapProp('subcontract_unit_price', no1, no2);
                swapProp('process_lt', no1, no2);
                swapProp('overhead_cost', no1, no2);
                swapProp('process_remarks_1', no1, no2);
                swapProp('process_remarks_2', no1, no2);
                swapProp('process_remarks_3', no1, no2);
            }
            function clearProcess(no) {
                $('#process_id_'+no).val('');
                $('#default_work_minute_'+no).val('');
                $('#pcs_per_day_'+no).val('');
                $('#charge_price_'+no).val('');
                $('#subcontract_partner_id_'+no).val('');
                $('#subcontract_partner_id_'+no+'_show').val('');
                $('#subcontract_partner_id_'+no+'_sub').val('');
                $('#subcontract_unit_price_'+no).val('');
                $('#process_lt_'+no).val('');
                $('#overhead_cost_'+no).val('');
                $('#process_remarks_1_'+no).val('');
                $('#process_remarks_2_'+no).val('');
                $('#process_remarks_3_'+no).val('');
            }

            // 上記の処理用
            function swapProp(baseName, no1, no2, after) {
                if (after==undefined) after = '';
                var tmp = $('#'+baseName+'_'+no1+after).val();
                $('#'+baseName+'_'+no1+after).val($('#'+baseName+'_'+no2+after).val());
                $('#'+baseName+'_'+no2+after).val(tmp);

                var no1e = $('#'+baseName+'_'+no1+'_error');
                var no2e = $('#'+baseName+'_'+no2+'_error');
                if (no1e.length!=0) {
                    // errorの入れ替え
                    tmp =no1e.html();
                    no1e.html(no2e.html());
                    no2e.html(tmp);
                    no1e.css('height',no1e.html()=='' ? '0px':'auto');
                    no2e.css('height',no2e.html()=='' ? '0px':'auto');
                }
            }

        ";

        // 構成表マスタへのリンク
        $form['gen_message_noEscape'] = "<a href='javascript:goBom()' style='color:#000000'>" . _g("この品目の構成を登録する") . "</a><br><br>";

        // 構成表マスタに含まれている場合、管理区分の切り替えを禁止する
        $itemClassReadonly = false;
        if (isset($form['item_id']) && !isset($form['gen_record_copy'])) {
            $existBom = false;
            if (isset($form['gen_multi_edit'])) {
                foreach($form['gen_multiEditKeyArray'] as $multiEditKey) {
                    if (Logic_Bom::existBom($multiEditKey)) {
                        $existBom = true;
                        break;
                    }
                }
            } else {
                $existBom = Logic_Bom::existBom($form['item_id']);
            }
            if ($existBom) {
                $form['gen_message_noEscape'] .= "<font color=\"blue\">" . _g("この品目は構成表マスタに登録されているため、管理区分を変更することはできません。") . "</font><br><br>";
                $itemClassReadonly = true;
            }
        }

        // 非表示品目メッセージ
        if (isset($form['end_item']) && $form['end_item'] == 'true') {
            if ($form['gen_message_noEscape'] != "")
                $form['gen_message_noEscape'] .= "<br><br>";
            $form['gen_message_noEscape'] .= "<font color=\"red\"><b>" . _g("この品目は「非表示」です。") . "</b></font>";
        }

        // セレクタ選択肢
        $query = "select item_group_id, item_group_name from item_group_master order by item_group_code";
        $option_item_group_id = $gen_db->getHtmlOptionArray($query, true);

        $query = "select process_id, substr(process_name,0,20) from process_master order by case when process_code='gen_default_process' then '' else process_code end";
        $option_process_id1 = $gen_db->getHtmlOptionArray($query, false);

        $query = "select process_id, substr(process_name,0,20) from process_master where process_id<>0 order by process_code";
        $option_process_id2 = $gen_db->getHtmlOptionArray($query, true);

        $query = "select location_id, location_name from location_master order by location_code;";
        $location_id = $gen_db->getHtmlOptionArray($query, false, array("0" => _g(GEN_DEFAULT_LOCATION_NAME)));

        // process_master にデフォルト工程レコード（「標準工程」。process_id = 0）が存在するかどうかを調べ、
        // 存在しなければ追加する
        $query = "select * from process_master where process_id = 0";
        if (!$gen_db->existRecord($query)) {
            $data = array(
                'process_id' => 0,
                'process_code' => "gen_default_process",
                'process_name' => "(標準工程/default)", // 英訳対応（poファイル未対応）
                'equipment_name' => "",
            );
            $gen_db->insert('process_master', $data);
        }

        // 新規登録の場合、工程1のデフォルトとしてデフォルト工程（「標準工程」。process_id = 0）を設定する。
        if (!isset($form['item_id']) && !isset($form['process_id_0'])) {
            $form['process_id_0'] = "0";
        }

        $form['gen_labelWidth'] = 130;
        $form['gen_dataWidth'] = 170;

        $form['gen_editControlArray'] = array(
            array(
                'label' => _g('品目コード'),
                'type' => 'textbox',
                'name' => 'item_code',
                'value' => @$form['item_code'],
                'readonly' => (@$form['gen_overlapFrame'] == "true" && !isset($form['gen_dropdownNewRecordButton'])), // 拡張DDからのジャンプ登録の場合、コード変更されると動作不具合。ただし拡張DD内新規ボタンを除く
                'ime' => 'off',
                'size' => '15',
                'helpText_noEscape' => _g("入力を省略すると品目名と同じになります。")
            ),
            array(
                'label' => _g('品目名'),
                'type' => 'textbox',
                'name' => 'item_name',
                'value' => @$form['item_name'],
                'onChange_noEscape' => 'onItemCodeChange()',
                'size' => '20', // 各Edit画面の拡張DDのsubSizeがだいたい20になっている
                'helpText_noEscape' => _g("入力を省略すると品目コードと同じになります。")
            ),
            array(
                'label' => _g('最終入庫日'),
                'type' => 'textbox',
                'name' => 'last_in_date',
                'value' => @$form['last_in_date'],
                'readonly' => true,
                'size' => '8'
            ),
            array(
                'label' => _g('最終出庫日'),
                'type' => 'textbox',
                'name' => 'last_out_date',
                'value' => @$form['last_out_date'],
                'readonly' => true,
                'size' => '8'
            ),
            array(
                'label' => _g('管理区分'),
                'type' => 'select',
                'name' => 'order_class',
                'options' => Gen_Option::getOrderClass('options'),
                'selected' => @$form['order_class'],
                'onChange_noEscape' => 'onOrderClassChange()',
                'readonly' => $itemClassReadonly,
                'helpText_noEscape' => _g("進捗管理・原価管理が必要な品目は製番管理、またロット別在庫管理・消費期限管理が必要な品目はロット管理とし、それ以外の品目はMRP管理とすることをお勧めします。") . "<br><br>"
                    . "●" . _g("製番") . "<br>"
                    . _g(" ○ 工程進捗管理・原価管理ができる（受注別進捗状況画面・原価リストで表示対象になる）") . "<br>"
                    . _g(" × 基本的に在庫を持てない。（製番品目で在庫を持つような運用をすると煩雑になる）") . "<br><br>"
                    . _g(" × オーダー（発注・製造指示）が製番ごとに別になるため、手配や製造の効率がよくない。") . "<br>"
                    . _g(" × 中間品の在庫引当ができない。") . "<br><br>"
                    . "●" . _g("MRP") . "<br>"
                    . _g(" ○ オーダー（発注・製造指示）が品目ごとにまとめられるため、手配や製造の効率がよい。") . "<br>"
                    . _g(" ○ 中間品も在庫引当できる。") . "<br>"
                    . _g(" × 工程進捗管理・原価管理ができない。") . "<br><br>"
                    . "●" . _g("ロット（オプション）") . "<br>"
                    . _g(" ○ ロット別在庫管理・消費期限管理ができる。（製造実績・注文受入ごとにロット番号がつき、在庫がロット別に分かれる。受注に対してロットを引き当てることができる）") . "<br>"
                    . _g(" × ロット別在庫管理をしない場合は、これを選ぶ理由がない。") . "<br><br>"
                    . _g("また、原価リスト画面のページヒントにも参考となる情報があります。"),
            ),
            array(
                // 13iまでは「終息」だったが、15iで「非表示」に名称変更。ag.cgi?page=ProjectDocView&pPID=1574&pBID=195413
                'label' => _g('非表示'),
                'type' => 'checkbox',
                'name' => 'end_item',
                'onvalue' => 'true', // trueのときの値。デフォルト値ではない
                'value' => @$form['end_item'],
                'helpText_noEscape' => _g('このチェックをオンにすると、在庫リスト・棚卸登録画面に表示されなくなります。また各画面の品目選択ドロップダウンにも表示されなくなります（品目コードを手入力することはできます）。'),
            ),
            array(
                'label' => _g("手配区分"),
                'type' => 'select',
                'name' => "partner_class_0",
                'options' => Gen_Option::getPartnerClass('options'),
                // グレードがSiの場合、「発注」をデフォルトとする。ag.cgi?page=ProjectDocView&ppid=1516&pbid=184807
                'selected' => isset($form["partner_class_0"]) ? $form["partner_class_0"] : (GEN_GRADE == "Si" ? "0" : ""),
                'onChange_noEscape' => "onPartnerClassChange(0,false)",
                'helpText_noEscape' => _g("この品目の手配方法を指定します。") . "<br><br>"
                    . "●" . _g("内製") . " ： " . _g("自社で製造する品目です。所要量計算結果（またはメニュー[生産管理]-[製造指示登録]）から「製造指示書」を発行します。着手時・完成時には[生産管理]-[実績登録]で登録を行います。完成登録時に在庫計上されます。構成表マスタで子品目を登録しておくと、所要量計算時に必要数が計算されて手配され、実績登録時に在庫から引き落とされます。") . "<BR><BR>"
                    . "●" . _g("発注") . " ： " . _g("他社から購入する品目です。所要量計算結果（またはメニュー[購買管理]-[注文書]）から「注文書」を発行します。受入（入庫）時には[購買管理]-[注文受入登録]で登録を行います。受入登録時に在庫計上と買掛計上されます。子品目を登録しても無意味です。") . "<BR><BR>"
                    . "●" . _g("外注(支給なし)") . " ： " . _g("他社に製造委託する品目です。所要量計算結果（またはメニュー[購買管理]-[外製指示書]）から「外製指示書」を発行します。受入（入庫）時には[購買管理]-[外製受入登録登録]で登録を行います。受入登録時に在庫計上と買掛計上されます。子品目を登録しても無意味です。") . "<BR><BR>"
                    . "●" . _g("外注(支給あり)") . " ： " . _g("「外注(支給なし)」と同じですが、部材の支給を行う場合はこちらを選択します。構成表マスタで子品目を登録しておくと、所要量計算時に必要数が計算されて手配され、[メンテナンス]-[自社情報]-[外製支給のタイミング]で指定されたタイミングで在庫から引き落とされます。")
            ),
            array(
                'label' => _g("標準手配先"),
                'type' => 'dropdown',
                'name' => "order_user_id_0",
                'value' => @$form["order_user_id_0"],
                'size' => '12',
                'subSize' => '20',
                'dropdownCategory' => 'partner',
                'onChange_noEscape' => "onOrderUserIdChange(0)",
                'helpText_noEscape' => _g("標準の手配先（この品目の購入・外製先）を指定します。所要量計算において、この取引先が購入・外製先として設定されます。「手配区分」が「内製」の場合は無効です。")
            ),
            array(
                'type' => 'literal',
                'denyMove' => true,
            ),
            array(
                'type' => 'literal',
                'denyMove' => true,
            ),
            // ****** ここから [詳細項目]Tab ******
            array(
                'label' => "",
                'type' => 'tab',
                'tabId' => 'tab_item_detail',
                'tabLabel' => _g("詳細項目"),
                'denyMove' => true,
            ),
            array(
                'label' => _g('品目グループ1'),
                'type' => 'select',
                'name' => 'item_group_id',
                'options' => $option_item_group_id,
                'selected' => @$form['item_group_id'],
            ),
            array(
                'label' => _g('品目グループ2'),
                'type' => 'select',
                'name' => 'item_group_id_2',
                'options' => $option_item_group_id,
                'selected' => @$form['item_group_id_2'],
            ),
            array(
                'label' => _g('品目グループ3'),
                'type' => 'select',
                'name' => 'item_group_id_3',
                'options' => $option_item_group_id,
                'selected' => @$form['item_group_id_3'],
            ),
            array(
                'type' => 'literal',
                //'denyMove' => true,
            ),
            array(
                'type' => 'literal',
                //'denyMove' => true,
            ),
            array(
                'type' => 'literal',
                //'denyMove' => true,
            ),
            array(
                'label' => _g('標準販売単価1'),
                'type' => 'textbox',
                'name' => 'default_selling_price',
                'value' => @$form['default_selling_price'],
                'size' => '8',
                'ime' => 'off',
                'helpText_noEscape' => _g("受注登録画面で受注単価のデフォルト値として使用されます。") . '<br><br>'
                    . _g("販売数が「販売単価1適用数」を超えているときは標準販売単価2が使用されます。") . '<br>'
                    . _g("ただし得意先販売単価マスタで販売単価が設定されている場合は、その価格が優先して使用されます。") . '<br>'
                    . _g("また取引先マスタ「掛率」が設定されている場合、この標準販売単価に掛率をかけた値がデフォルト販売単価となります。") . '<br><br>'
                    . "●" . _g("販売価格決定の優先順位（高い順）") . '<br>'
                    . _g("(1) 得意先販売価格マスタ") . '<br>'
                    . _g("(2) 取引先マスタ「掛率」") . '<br>'
                    . _g("(3) 掛率グループマスタ「掛率」") . '<br>'
                    . _g("(4) 品目マスタ「標準販売単価(1-3)」") . '<br><br>'
                    . _g("外貨建てで販売する品目の場合、その取引通貨での単価を入力してください。ひとつの品目を複数の取引通貨で販売する場合、ここでは最もよく使用する取引通貨での単価を入力しておき、それ以外の取引通貨で販売する得意先について個別に得意先販売単価マスタを登録してください。"),
            ),
            array(
                'label' => _g('販売単価1適用数'),
                'type' => 'textbox',
                'name' => 'selling_price_limit_qty_1',
                'value' => @$form['selling_price_limit_qty_1'],
                'size' => '8',
                'ime' => 'off',
                'helpText_noEscape' => _g("「標準販売単価1」が適用される購入数です。") . "<br>"
                    . _g("販売数がここで指定した数量以下の場合に、標準販売単価1が適用されます。") . "<br>"
                    . _g("この数量を超える場合は、標準販売単価2が適用されます。") . "<br>"
                    . _g("詳しくは「標準販売単価1」のチップヘルプをご覧ください。"),
            ),
            array(
                'label' => _g('標準販売単価2'),
                'type' => 'textbox',
                'name' => 'default_selling_price_2',
                'value' => @$form['default_selling_price_2'],
                'size' => '8',
                'ime' => 'off',
                'helpText_noEscape' => _g("販売数が「販売単価1適用数」を超えたときに適用される標準販売単価です。") . "<br>"
                    . _g("詳しくは「標準販売単価1」のチップヘルプをご覧ください。"),
            ),
            array(
                'label' => _g('販売単価2適用数'),
                'type' => 'textbox',
                'name' => 'selling_price_limit_qty_2',
                'value' => @$form['selling_price_limit_qty_2'],
                'size' => '8',
                'ime' => 'off',
                'helpText_noEscape' => _g("「標準販売単価2」が適用される購入数です。") . "<br>"
                    . _g("販売数がここで指定した数量以下の場合に、標準販売単価2が適用されます。") . "<br>"
                    . _g("この数量を超える場合は、標準販売単価2が適用されます。") . "<br>"
                    . _g("詳しくは「標準販売単価1」のチップヘルプをご覧ください。"),
            ),
            array(
                'label' => _g('標準販売単価3'),
                'type' => 'textbox',
                'name' => 'default_selling_price_3',
                'value' => @$form['default_selling_price_3'],
                'size' => '8',
                'ime' => 'off',
                'helpText_noEscape' => _g("販売数が「販売単価2適用数」を超えたときに適用される標準販売単価です。") . "<br>"
                    . _g("詳しくは「標準販売単価1」のチップヘルプをご覧ください。"),
            ),
            array(
                'type' => 'literal',
                //'denyMove' => true,
            ),
            array(
                'type' => 'literal',
                //'denyMove' => true,
            ),
            array(
                'type' => 'literal',
                //'denyMove' => true,
            ),
            array(
                'label' => _g('在庫評価単価') . '(' . $keyCurrency . ')',
                'type' => 'textbox',
                'name' => 'stock_price',
                'value' => @$form['stock_price'],
                'ime' => 'off',
                'size' => '8',
                'helpText_noEscape' => _g("在庫リストの金額計算、および標準原価計算（販売原価、見積原価、原価表の在庫使用分の金額など）に使用されます。") . '<br>'
                    . _g("購入品については、在庫リストの「評価単価更新」を実行するとこの評価単価が自動的に更新されます。") . '<br>'
                    . _g("製造品は自動更新されませんので、手動で更新する必要があります。"),
            ),
            array(
                'label' => _g('支給単価') . '(' . $keyCurrency . ')',
                'type' => 'textbox',
                'name' => 'payout_price',
                'value' => @$form['payout_price'],
                'size' => '8',
                'ime' => 'off',
                'helpText_noEscape' => _g("この品目が外注先に支給されるとき、単価として設定されます。無償支給の場合は0を設定してください。支給登録画面に反映されます。"),
            ),
            array(
                'label' => _g('課税区分'),
                'type' => 'select',
                'name' => 'tax_class',
                'options' => array('0' => _g('課税'), '1' => _g('非課税')),
                'selected' => @$form['tax_class'],
                'onChange_noEscape' => "onTaxClassChange(false)",
                'helpText_noEscape' => _g("この品目を購入あるいは販売する場合の課税区分を指定します。受注や注文書の画面に表示されるほか、注文書・納品書・請求書の税計算に反映されます。") . "<br>"
                    . _g("なお、外貨建ての受注、注文（取引先マスタで使用取引通貨が基軸通貨以外になっている場合）は、この設定にかかわらず常に課税対象外となります。"),
            ),
            array(
                'label' => _g('税率'),
                'type' => 'textbox',
                'name' => 'tax_rate',
                'value' => @$form['tax_rate'],
                'size' => '8',
                'ime' => 'off',
                'helpText_noEscape' => _g("この品目を購入あるいは販売する場合の税率（デフォルト値）を指定します。") . "<br>"
                    . _g("納品登録・受入登録・外製受入登録登録については、この項目を省略すると消費税率マスタの税率がデフォルト値として表示されます。"),
            ),
            array(
                'type' => 'literal',
                //'denyMove' => true,
            ),
            array(
                'type' => 'literal',
                //'denyMove' => true,
            ),
            array(
                'label_noEscape' => _g('管理単位') . '<br>' . _g('(個, kg, m 等)'),
                'type' => 'textbox',
                'name' => 'measure',
                'value' => @$form['measure'],
                'size' => '8',
                'onChange_noEscape' => 'onAllOrderUserIdChange()',
                'helpText_noEscape' => _g("「個」「g」「m」など、在庫管理の単位を指定してください。省略も可能です。指定すると現在庫リスト等に反映されます。"),
            ),
            array(
                'label' => _g('受注対象'),
                'type' => 'select',
                'name' => 'received_object',
                'options' => array('0' => _g('受注対象'), '1' => _g('対象外')),
                'selected' => @$form['received_object'],
                'helpText_noEscape' => _g("「受注対象」にすると、受注登録画面において品目の選択肢に表示され、受注登録が行えるようになります。"),
            ),
            array(
                'label' => _g('メーカー'),
                'type' => 'textbox',
                'name' => 'maker_name',
                'value' => @$form['maker_name'],
                'size' => '12',
                'ime' => 'on',
                'helpText_noEscape' => _g("製造メーカー等の情報を入力します。登録しなくても特に問題はありません。"),
            ),
            array(
                'label' => _g('仕様'),
                'type' => 'textbox',
                'name' => 'spec',
                'value' => @$form['spec'],
                'size' => '12',
                'helpText_noEscape' => _g("製品仕様等の情報を入力します。登録しなくても特に問題はありません。"),
            ),
            array(
                'label' => _g('棚番'),
                'type' => 'textbox',
                'name' => 'rack_no',
                'value' => @$form['rack_no'],
                'size' => '12',
                'helpText_noEscape' => _g("この品目が保管されている倉庫の名称や棚の番号などを入力します。棚卸登録画面に表示されます。また、製造指示書の子品目欄に印刷されます（ピッキング作業用）。棚番管理をしない場合、登録する必要はありません。"),
            ),
            array(
                'label' => _g('入数'),
                'type' => 'textbox',
                'name' => 'quantity_per_carton',
                'value' => @$form['quantity_per_carton'],
                'size' => '8',
                'ime' => 'off',
            ),
            array(
                'type' => 'literal',
                //'denyMove' => true,
            ),
            array(
                'type' => 'literal',
                //'denyMove' => true,
            ),
            array(
                'label' => _g('標準ﾛｹｰｼｮﾝ（受入）'),
                'type' => 'select',
                'name' => 'default_location_id',
                'options' => $location_id,
                'selected' => @$form['default_location_id'],
                'helpText_noEscape' => _g("この品目を購入したときの標準受入ロケーション（つまり注文受入画面や外製受入登録画面におけるデフォルトロケーション）を指定します。") . '<br>'
                    . _g("その都度、注文受入画面や外製受入登録画面で指定することもできますが、標準の受入ロケーションが決まっている場合は、ここで指定しておくと便利です。"),
            ),
            array(
                'label' => _g('標準ﾛｹｰｼｮﾝ（使用）'),
                'type' => 'select',
                'name' => 'default_location_id_2',
                'options' => $location_id,
                'selected' => @$form['default_location_id_2'],
                'helpText_noEscape' => _g("この品目が他の品目の製造に子品目として使用されたり、外製先に支給される際の（標準の）出庫元ロケーションを指定します。具体的には次のようになります。") . "<br><br>"
                    . "●" . _g("内製に使用される場合") . " ： " . _g("製造実績登録画面での子品目出庫ロケーションに「各部材の標準ロケ」が指定された場合に、このロケーションから出庫されます。") . "<br><br>"
                    . "●" . _g("外製先に支給される場合") . " ： " . _g("所要量計算の結果から外製オーダー発行した場合、および外製登録画面での子品目支給元ロケーションに「各部材の標準ロケ」が指定された場合に、このロケーションから出庫されます。"),
            ),
            array(
                'label' => _g('標準ﾛｹｰｼｮﾝ（完成）'),
                'type' => 'select',
                'name' => 'default_location_id_3',
                'options' => $location_id,
                'selected' => @$form['default_location_id_3'],
                'helpText_noEscape' => _g("この品目を製造したときの標準入庫ロケーション（つまり製造実績登録画面での製品入庫デフォルトロケーション）、および納品の際の標準出庫ロケーション（つまり納品登録画面での出庫デフォルトロケーション）を指定します。") . '<br>'
                    . _g("そのつど実績登録画面や納品登録画面で指定することもできますが、標準のロケーションが決まっている場合は、ここで指定しておくと便利です。"),
            ),
            array(
                'type' => 'literal',
                //'denyMove' => true,
            ),
            array(
                'type' => 'literal',
                //'denyMove' => true,
            ),
            array(
                'type' => 'literal',
                //'denyMove' => true,
            ),
            array(
                'label' => _g('ダミー品目'),
                'type' => 'checkbox',
                'name' => 'dummy_item',
                'onvalue' => 'true', // trueのときの値。デフォルト値ではない
                'value' => @$form['dummy_item'],
                'helpText_noEscape' => _g('ダミー品目とは、在庫管理および所要量計算の対象とならない品目です。') . '<br><br>'
                    . _g('受注登録のための「送料」「値引き」等の品目やセット販売品目、構成登録のための仮コードなどをダミー品目に指定すると便利です。') . '<br><br>'
                    . _g('詳細については、品目マスタ画面右上の「ヘルプ」リンクをクリックし、「ダミー品目」で検索してください。')
            ),
            array(
                'type' => 'literal',
                //'denyMove' => true,
            ),
            array(
                'type' => 'literal',
                //'denyMove' => true,
            ),
            array(
                'type' => 'literal',
                //'denyMove' => true,
            ),
            array(
                'label' => _g('消費期限日数'),
                'type' => 'textbox',
                'name' => 'use_by_days',
                'value' => @$form['use_by_days'],
                'size' => '8',
                'ime' => 'off',
                'helpText_noEscape' => _g('ロット品目の消費期限管理を行いたい場合に使用する項目です。') . '<br><br>'
                    . _g('この項目を設定すると、注文受入や製造実績登録の際に「消費期限」の項目が自動計算されて設定されるようになります。') . '<br><br>'
                    . _g('受入日・製造日 当日を消費期限として設定したい場合は「0」、翌日を設定したい場合は「1」のように設定します。')
            ),
            array(
                'label' => _g('ロット頭文字'),
                'type' => 'textbox',
                'name' => 'lot_header',
                'value' => @$form['lot_header'],
                'size' => '8',
                'ime' => 'off',
                'helpText_noEscape' => _g('ロット品目の注文受入や製造実績登録を行うと「ロット番号」が自動設定されますが、その際のロット番号の頭文字をここで指定できます。')
            ),

            array(
                'type' => 'literal',
                //'denyMove' => true,
            ),
            array(
                'type' => 'literal',
                //'denyMove' => true,
            ),
            array(
                'label' => _g('品目備考1'),
                'type' => 'textbox',
                'name' => 'comment',
                'value' => @$form['comment'],
                'ime' => 'on',
                'size' => '20'
            ),
            array(
                'label' => _g('品目備考2'),
                'type' => 'textbox',
                'name' => 'comment_2',
                'value' => @$form['comment_2'],
                'ime' => 'on',
                'size' => '20'
            ),
            array(
                'label' => _g('品目備考3'),
                'type' => 'textbox',
                'name' => 'comment_3',
                'value' => @$form['comment_3'],
                'ime' => 'on',
                'size' => '20'
            ),
            array(
                'label' => _g('品目備考4'),
                'type' => 'textbox',
                'name' => 'comment_4',
                'value' => @$form['comment_4'],
                'ime' => 'on',
                'size' => '20'
            ),
            array(
                'label' => _g('品目備考5'),
                'type' => 'textbox',
                'name' => 'comment_5',
                'value' => @$form['comment_5'],
                'ime' => 'on',
                'size' => '20'
            ),
            // ****** ここから [所要量計算]Tab ******
            array(
                'label' => "",
                'type' => 'tab',
                'tabId' => 'tab_mrp',
                'tabLabel' => _g("所要量計算"),
                'denyMove' => true,
            ),
            array(
                'label_noEscape' => _g('所要量計算に含める（製番品目を除く）'),
                'type' => 'select',
                'name' => 'without_mrp',
                'options' => array('0' => _g('計算に含める'), '1' => _g('計算に含めない')),
                'selected' => @$form['without_mrp'],
                'helpText_noEscape' => _g("「計算に含めない」にすると所要量計算から除外されます。手動で手配を行う場合はそのように指定してください。") . "<br><br>"
                    . _g("管理区分が「製番」の品目は対象外です。"),
            ),
            array(
                'label' => _g('安全在庫数'),
                'type' => 'textbox',
                'name' => 'safety_stock',
                'value' => @$form['safety_stock'],
                'size' => '8',
                'ime' => 'off',
                'helpText_noEscape' => _g("余裕分として、常に在庫として持っておきたい数量を指定します。") . "<br>"
                    . _g("MRP品目の場合、所要量計算において、この数量分の在庫が常に残るように手配がかかります。余裕在庫を持つ必要がなければ0を指定してください。") . "<br>"
                    . _g("製番品目の場合は自動的に手配がかかるわけではありませんが、設定しておくと在庫推移リスト画面の表示に反映されます。"),
            ),
            array(
                'label' => _g('リードタイム') . '(' . _g('日') . ')',
                'type' => 'textbox',
                'name' => 'lead_time',
                'value' => @$form['lead_time'],
                'onChange_noEscape' => 'onLTChange()',
                'size' => '8',
                'ime' => 'off',
                'helpText_noEscape' => _g("製造着手から完成まで（内製の場合）、あるいは発注から納品まで（注文の場合）の日数を登録します。") . "<br>"
                    . _g("この数値は、所要量計算において着手日や発注日を決めるために使用されます。") . "<br><br>"
                    . "<b>" . _g("標準手配先が「内製」の場合") . "：</b><br>"
                    . _g("この製品の製造着手から完成までの日数を指定してください。1日以内に完成する場合は0を指定してください。") . "<br><br>"
                    . _g("ただし工程管理を行う場合（「工程」タブで工程の情報を登録する場合）は、ここを空欄にしてください。工程リードタイムから自動計算されます。") . "<br>"
                    . _g("すべての工程のリードタイムが指定されている場合、その合計値が品目のリードタイムとなります。一部、あるいはすべての工程リードタイムが空欄である場合、品目のリードタイムも空欄（自動計算）となります。") . "<br>"
                    . _g("空欄にせず数値を指定した場合は、その数値が工程リードタイムの合計と一致している必要があります。") . "<br>"
                    . "<b>" . _g("標準手配先が「内製」以外の場合") . "：</b><br>"
                    . _g("この製品の発注から納品までの日数を指定してください。所要量計算において、この値をもとに発注日が決定されます。")
            ),
            array(
                'label' => _g('安全リードタイム') . '(' . _g('日') . ')',
                'type' => 'textbox',
                'name' => 'safety_lead_time',
                'value' => @$form['safety_lead_time'],
                'size' => '8',
                'ime' => 'off',
                'helpText_noEscape' => _g("製造した品目の完成（内製の場合）あるいは注文した品目の納品（注文の場合）が、その品目を必要とする日の何日前になるようにするかを指定します。"
                    . "つまり、入庫から必要日までの余裕日数を指定します。"),
            ),
            array(
                'label' => _g("最低ロット数"),
                'type' => 'textbox',
                'name' => "default_lot_unit_0",
                'value' => @$form["default_lot_unit_0"],
                'onChange_noEscape' => "changeMeasureLabel(0)",
                'size' => '5',
                'ime' => 'off',
                'helpText_noEscape' => _g("所要量計算におけるオーダー（注文・製造指示）の最低数です。オーダー数は、この数値以上になるよう切り上げて丸められます。たとえば最低100個以上は発注する必要があるなど、オーダーの最低数が決まっている場合に使用します。") . "<br>"
                    . _g("管理単位あたりの数量です（手配単位あたりではありません）。") . "<br><br>"
                    . "※<b>" . _g("最低ロット数と手配ロット数の違い") . "</b>：<br>"
                    . _g("最低ロット数はオーダーの最小数、手配ロット数はオーダーの単位（まとめ）数です。") . "<br>"
                    . _g("例えば最低ロット数が50、手配ロット数が10の場合、所要量計算によるオーダー数は50以上10単位（50, 60, 70, ・・・）になります。"),
            ),
            array(
                'label' => _g("手配ロット数"),
                'type' => 'textbox',
                'name' => "default_lot_unit_2_0",
                'value' => @$form["default_lot_unit_2_0"],
                'onChange_noEscape' => "changeMeasureLabel(0)",
                'size' => '5',
                'ime' => 'off',
                'helpText_noEscape' => _g("所要量計算におけるオーダー（注文・製造指示）の単位数です。オーダー数の「最低ロット数」を超えた部分は、この数値の倍数になるよう切り上げて丸められます。たとえば100個単位で発注したいなど、オーダーの単位数が決まっている場合に使用します。端数のオーダーが出ても構わない場合は0にしてください。0を設定した場合、丸めは行われません。") . "<br>"
                    . _g("管理単位あたりの数量です（手配単位あたりではありません）。") . "<br><br>"
                    . "※" . _g("最低ロット数との違いについては、最低ロット数のチップヘルプをご覧ください。"),
            ),
            // ****** ここから [発注・外注]Tab ******
            array(
                'label' => "",
                'type' => 'tab',
                'tabId' => 'tab_item_order',
                'tabLabel' => _g("発注・外注"),
                'denyMove' => true,
            ),
        );

        for ($i = 0; $i < GEN_ITEM_ORDER_COUNT; $i++) {
            $form['gen_editControlArray'][] = array(
                'label_noEscape' => "<span style='height:20px;font-size:14px;color:blue;border-left:solid 5px blue'>&nbsp;&nbsp;" . ($i == 0 ? _g("標準手配先") : _g("代替手配先") . $i) . "</span>" . ($i == 0 ? "" : "&nbsp;&nbsp;<input id='changeDefButton{$i}' type=\"button\" class=\"gen-button\" value='" . _g('標準手配先にする') . "' onclick='itemOrderSetDefault($i)'></nobr>"),
                'type' => 'literal',
                'denyMove' => true,
            );
            $form['gen_editControlArray'][] = array(
                'type' => 'literal',
                'denyMove' => true,
            );

            if ($i != 0) {
                $form['gen_editControlArray'][] = array(
                    'label' => _g("手配区分") . ($i),
                    'type' => 'select',
                    'name' => "partner_class_{$i}",
                    'options' => Gen_Option::getPartnerClass('options-non'),
                    'selected' => @$form["partner_class_{$i}"],
                    'onChange_noEscape' => "onPartnerClassChange({$i},false)",
                );
                $form['gen_editControlArray'][] = array(
                    'label' => _g("代替手配先") . ($i),
                    'type' => 'dropdown',
                    'name' => "order_user_id_{$i}",
                    'value' => @$form["order_user_id_{$i}"],
                    'size' => '12',
                    'subSize' => '18',
                    'dropdownCategory' => 'partner',
                    'onChange_noEscape' => "onOrderUserIdChange({$i})",
                );
            }

            $form['gen_editControlArray'][] = array(
                'label' => _g("メーカー型番"),
                'type' => 'textbox',
                'name' => "item_sub_code_{$i}",
                'value' => @$form["item_sub_code_{$i}"],
                'size' => '12',
                'helpText_noEscape' => ($i == 0 ? _g("メーカー型番がある場合は登録してください。この型番は注文書に記載されます。手配先が「内製」の場合は指定できません。") : ""),
            );
            $form['gen_editControlArray'][] = array(
                'type' => 'literal',
                'denyMove' => true,
            );

            $form['gen_editControlArray'][] = array(
                'label_noEscape' => _g("手配単位"),
                'type' => 'textbox',
                'name' => "order_measure_{$i}",
                'value' => @$form["order_measure_{$i}"],
                'size' => '5',
                'onChange_noEscape' => "changeMeasureLabel({$i})",
                'helpText_noEscape' => ($i == 0 ? _g("「個」「kg」「m」など、オーダー（発注・外注。内製には適用されません）の際の単位を指定してください。省略も可能です。指定すると注文書に反映されます。手配先が「内製」の場合は指定できません。") : ""),
            );

            $form['gen_editControlArray'][] = array(
                'label' => _g("手配単位倍数"),
                'type' => 'textbox',
                'name' => "multiple_of_order_measure_{$i}",
                'value' => @$form["multiple_of_order_measure_{$i}"],
                'size' => '5',
                'onChange_noEscape' => "changeMeasureLabel({$i})",
                'ime' => 'off',
                'helpText_noEscape' => ($i == 0 ? _g("「手配単位」が、「詳細項目」タブにある「管理単位」の何倍にあたるかを指定します。たとえば「手配単位」が「kg」、「管理単位」が「g」の場合、手配倍数は1000を登録します。省略すると1とみなされます。注文書に反映されます。") . "<br>" . _g("手配先が「内製」の場合は指定できません。") : ""),
            );

            $form['gen_editControlArray'][] = array(
                'label' => _g("購買取引通貨"),
                'type' => 'textbox',
                'name' => "currency_name_{$i}",
                'value' => '',
                'size' => '5',
                'readonly' => 'true',
                'helpText_noEscape' => ($i == 0 ? _g("取引先マスタで設定した取引通貨が表示されます。「購入単価」はこの取引通貨で設定してください。") : ""),
            );
            $form['gen_editControlArray'][] = array(
                'type' => 'literal',
                'denyMove' => true,
            );

            $form['gen_editControlArray'][] = array(
                'label' => _g("購入単価1"),
                'type' => 'textbox',
                'name' => "default_order_price_{$i}",
                'value' => @$form["default_order_price_{$i}"],
                'onChange_noEscape' => "changeMeasureLabel({$i})",
                'size' => '5',
                'ime' => 'off',
                'helpText_noEscape' => ($i == 0 ? _g("この手配先に対する標準の購入単価を登録します。所要量計算からの注文登録において、この値が発注単価として使用されます（あとで個別に変更することもできます）。また、注文書登録画面において、この値が発注単価のデフォルト値として表示されます。") . "<br>" . _g("管理単位あたりの単価です（手配単位あたりではありません）。手配先が「内製」の場合は指定できません。") : ""),
            );
            $form['gen_editControlArray'][] = array(
                'label' => _g("購入単価1適用数"),
                'type' => 'textbox',
                'name' => "order_price_limit_qty_1_{$i}",
                'value' => @$form["order_price_limit_qty_1_{$i}"],
                'onChange_noEscape' => "changeMeasureLabel({$i})",
                'size' => '5',
                'ime' => 'off',
                'helpText_noEscape' => ($i == 0 ? _g("「購入単価1」が適用される購入数です。") . "<br>" . _g("購入数がここで指定した数量以下の場合に、購入単価1が適用されます。") . "<br>" . _g("この数量を超える場合は、購入単価2が適用されます。") : ""),
            );
            $form['gen_editControlArray'][] = array(
                'label' => _g("購入単価2"),
                'type' => 'textbox',
                'name' => "default_order_price_2_{$i}",
                'value' => @$form["default_order_price_2_{$i}"],
                'onChange_noEscape' => "changeMeasureLabel({$i})",
                'size' => '5',
                'ime' => 'off',
                'helpText_noEscape' => ($i == 0 ? _g("購入数量が「購入単価1適用数」を超えたときに適用される単価です。詳しくは「購入単価1」のチップヘルプを参照してください。") : ""),
            );
            $form['gen_editControlArray'][] = array(
                'label' => _g("購入単価2適用数"),
                'type' => 'textbox',
                'name' => "order_price_limit_qty_2_{$i}",
                'value' => @$form["order_price_limit_qty_2_{$i}"],
                'onChange_noEscape' => "changeMeasureLabel({$i})",
                'size' => '5',
                'ime' => 'off',
                'helpText_noEscape' => ($i == 0 ? _g("「購入単価2」が適用される購入数です。") . "<br>" . _g("購入数がここで指定した数量以下の場合に、購入単価2が適用されます。") . "<br>" . _g("この数量を超える場合は、購入単価3が適用されます。") : ""),
            );
            $form['gen_editControlArray'][] = array(
                'label' => _g("購入単価3"),
                'type' => 'textbox',
                'name' => "default_order_price_3_{$i}",
                'value' => @$form["default_order_price_3_{$i}"],
                'onChange_noEscape' => "changeMeasureLabel({$i})",
                'size' => '5',
                'ime' => 'off',
                'helpText_noEscape' => ($i == 0 ? _g("購入数量が「購入単価2適用数」を超えたときに適用される単価です。詳しくは「購入単価1」のチップヘルプを参照してください。") : ""),
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
            $form['gen_editControlArray'][] = array(
                'type' => 'literal',
                'denyMove' => true,
            );
            $form['gen_editControlArray'][] = array(
                'type' => 'literal',
                'denyMove' => true,
            );
        }

        // ****** ここから [工程]Tab ******

        $form['gen_editControlArray'][] = array(
            'label' => "",
            'type' => 'tab',
            'tabId' => 'tab_item_process',
            'tabLabel' => _g("工程"),
            'denyMove' => true,
        );
        $form['gen_editControlArray'][] = array(
            'label_noEscape' => "<table align=center style='table-layout:fixed;'><tr><td width='140px'>" . _g("標準加工時間") . "： <span id='total_work_minute'>0</span> " . _g("分") . "</td>"
                . "<td width='20px'></td>"
                . "<td width='140px'>" . _g("標準工賃") . ": {$keyCurrency} <span id='total_charge_amount'>0</span></td>"
                . "<td width='20px'></td>"
                . "<td width='260px'>" . _g("標準工賃＋外製単価＋固定経費") . ": {$keyCurrency} <span id='total_charge_amount_cost'>0</span></td>"
                . "<td width='20px'></td>"
                . "<td width='140px'>" . _g("合計リードタイム") . ": <span id='total_process_lt'>0</span> " . _g("日") . "</td>"
                . "</tr></table>",
            'type' => 'literal',
            'style' => "text-align:center;",
            'width' => 200,
            'colspan' => 2,
            'denyMove' => true,
        );

        for ($i = 0; $i < GEN_ITEM_PROCESS_COUNT; $i++) {
            // 工程の挿入/削除/入れ替えボタン
            $controlButton =
                    "<span id='processControl{$i}'>" .
                    "<img src='img/space.gif' style='width:70px'>" .
                    "<a href=\"javascript:deleteProcess({$i})\" tabindex=-1><img class='imgContainer sprite-cross' src='img/space.gif' border='0' title='" . _g("削除") . "'></a>";
            if ($i < GEN_ITEM_PROCESS_COUNT - 1) {
                $controlButton .=
                        "<img src='img/space.gif' style='width:10px'>" .
                        "<a href=\"javascript:insertProcess({$i})\" tabindex=-1><img class='imgContainer sprite-plus' src='img/space.gif' border='0' title='" . _g("挿入") . "'></a>";
            }
            if ($i > 0) {
                $controlButton .=
                        "<img src='img/space.gif' style='width:10px'>" .
                        "<a href=\"javascript:upProcess({$i})\" tabindex=-1><img class='imgContainer sprite-arrow-090' src='img/space.gif' border='0' title='" . _g("上へ移動") . "'/></a>";
            }
            if ($i < GEN_ITEM_PROCESS_COUNT - 1) {
                $controlButton .=
                        "<img src='img/space.gif' style='width:10px'>" .
                        "<a href=\"javascript:downProcess({$i})\" tabindex=-1><img class='imgContainer sprite-arrow-270' src='img/space.gif' border='0' title='" . _g("下へ移動") . "' /></a>";
            }
            $controlButton .= "</span>";

            $form['gen_editControlArray'][] = array(
                'label_noEscape' => _g("工程") . ($i + 1) . $controlButton,
                'type' => 'section',
                'style' => 'width:300px;', // IE7対策
                'denyMove' => true,
            );
            $form['gen_editControlArray'][] = array(
                'type' => 'literal',
                'denyMove' => true,
            );
            $form['gen_editControlArray'][] = array(
                'label' => _g('工程') . ($i + 1),
                'type' => 'select',
                'name' => "process_id_{$i}",
                'options' => ($i == 0 ? $option_process_id1 : $option_process_id2),
                'selected' => @$form["process_id_{$i}"],
                'onChange_noEscape' => "onProcessChange({$i})",
            );
            $form['gen_editControlArray'][] = array(
                'label' => _g("標準加工時間(分)"),
                'type' => 'textbox',
                'name' => "default_work_minute_{$i}",
                'value' => @$form["default_work_minute_{$i}"],
                'size' => '8',
                'ime' => 'off',
                'onChange_noEscape' => 'calcTotalProcess()',
                'helpText_noEscape' => ($i == 0 ? _g("この工程で、この品目を1個（管理単位）製造あるいは加工するのに要する平均時間を、分単位で指定します。") . "<br>"
                    . _g("この数値は、見積書や原価リストにおいて標準原価の計算に使用されます。（この数値に、「工賃」を掛けた金額が、この品目の1個あたりの標準工賃になります。）") . "<br>"
                    . _g("また、実績の一括登録画面において、1個あたりの加工時間として使用されます。") . "<br>"
                    . _g("見積書や原価リスト、実績一括登録を使用しない場合は、登録を省略（0を登録）してもかまいません。") . "<br>"
                    . _g("また、外製の場合は0を登録してください。") : ""),
            );
            $form['gen_editControlArray'][] = array(
                'label' => _g("製造能力(1日あたり)"),
                'type' => 'textbox',
                'name' => "pcs_per_day_{$i}",
                'value' => @$form["pcs_per_day_{$i}"],
                'size' => '8',
                'ime' => 'off',
                'helpText_noEscape' => ($i == 0 ? _g("この品目を1日に製造できる数量を入力します。") . '<br>'
                    . _g("この数値は、工程別負荷状況画面で負荷率の計算に使用されます。工程別負荷状況画面を使用しない場合は、登録を省略（0を登録）してもかまいません。") . '<br>'
                    . _g("また、外製の場合は0を登録してください。") : ""),
            );
            $form['gen_editControlArray'][] = array(
                'label' => _g("工賃(1分あたり)"),
                'type' => 'textbox',
                'name' => "charge_price_{$i}",
                'value' => @$form["charge_price_{$i}"],
                'size' => '8',
                'ime' => 'off',
                'onChange_noEscape' => 'calcTotalProcess()',
                'helpText_noEscape' => ($i == 0 ? _g("作業1分あたりの工賃を設定します。原価計算（原価リストおよび見積登録画面）で使用されます。") . '<br>'
                    . _g("外製の場合は0を登録してください。") : ""),
            );
            $form['gen_editControlArray'][] = array(
                'label' => _g('外製先'),
                'type' => 'dropdown',
                'name' => "subcontract_partner_id_{$i}",
                'value' => @$form["subcontract_partner_id_{$i}"],
                'size' => '12',
                'subSize' => '18',
                'dropdownCategory' => 'partner',
                'helpText_noEscape' => _g("この工程が外製工程である場合、外製先を指定します。外製先は取引先マスタで「サプライヤー」として指定されている必要があります。") . '<br>'
                    . _g("外製工程は、製造指示の登録時に同時に外製指示書が登録されます。受入登録は外製受入登録画面で行います。") . '<br><br>'
                    . _g("内製の場合は空欄（「なし」）にしてください。"),
            );
            $form['gen_editControlArray'][] = array(
                'label' => sprintf(_g('外製単価(%s)'), $keyCurrency),
                'type' => 'textbox',
                'name' => "subcontract_unit_price_{$i}",
                'value' => @$form["subcontract_unit_price_{$i}"],
                'size' => '8',
                'ime' => 'off',
                'onChange_noEscape' => 'calcTotalProcess()',
                'helpText_noEscape' => ($i == 0 ? _g("この工程が外製工程である場合、外製の単価を指定します。") . '<br>'
                    . _g("外製指示書の発注単価になります。") . '<br>'
                    . _g("また、原価計算にも反映されます。原価の計算式については「固定経費」のチップヘルプをご覧ください。") : ""),
            );
            $form['gen_editControlArray'][] = array(
                'label' => _g('工程リードタイム') . '(' . _g('日') . ')',
                'type' => 'textbox',
                'name' => "process_lt_{$i}",
                'value' => @$form["process_lt_{$i}"],
                'size' => '8',
                'ime' => 'off',
                'onChange_noEscape' => 'onProcessLTChange()',
                'helpText_noEscape' => ($i == 0 ? _g("この工程のリードタイム（所要日数）を設定します。この値をもとに工程納期が設定されます。") . '<br><br>'
                    . _g("入力を省略すると、オーダー数 ÷ 製造能力 － 1 で計算されます。（-1されるのは、工程に関してはすべて安全リードタイム0、つまり前工程の納期日と後工程の着手日が重なるとみなされるためです。）") : ""),
            );
            $form['gen_editControlArray'][] = array(
                // 13iまでは「間接費」だったが15iから「固定経費」に名称変更。ag.cgi?page=ProjectDocView&pid=1574&did=193291
                'label' => sprintf(_g('固定経費(%s)'), $keyCurrency),
                'type' => 'textbox',
                'name' => "overhead_cost_{$i}",
                'value' => @$form["overhead_cost_{$i}"],
                'size' => '8',
                'ime' => 'off',
                'onChange_noEscape' => 'calcTotalProcess()',
                'helpText_noEscape' => ($i == 0 ? _g("固定経費（この品目をひとつ製造するのにかかるコスト。ただし材料費や実績登録画面で登録する製造経費を除く）を、製造数1あたりの金額で入力します。") . '<br>'
                    . _g("原価計算（原価リストおよび見積登録画面）で使用されます。") . '<br>'
                    . _g("原価 = 材料費 +（外製単価 × 外製受入登録画面「受入数」）+（品目マスタ「工賃」× 実績登録画面「製造時間（分）」）+ 実績登録画面「製造経費1-3」 +（品目マスタ「固定経費」× 実績登録画面「製造数」）") : ""),
            );
            $form['gen_editControlArray'][] = array(
                'label' => _g("工程メモ1"),
                'type' => 'textbox',
                'name' => "process_remarks_1_{$i}",
                'value' => @$form["process_remarks_1_{$i}"],
                'size' => '15',
                'helpText_noEscape' => _g("製造指示書に記載されます。また、この工程が外製工程である場合は、外製指示書にも記載されます。"),
            );
            $form['gen_editControlArray'][] = array(
                'label' => _g("工程メモ2"),
                'type' => 'textbox',
                'name' => "process_remarks_2_{$i}",
                'value' => @$form["process_remarks_2_{$i}"],
                'size' => '15',
                'helpText_noEscape' => _g("製造指示書に記載されます。また、この工程が外製工程である場合は、外製指示書にも記載されます。"),
            );
            $form['gen_editControlArray'][] = array(
                'label' => _g("工程メモ3"),
                'type' => 'textbox',
                'name' => "process_remarks_3_{$i}",
                'value' => @$form["process_remarks_3_{$i}"],
                'size' => '15',
                'helpText_noEscape' => _g("製造指示書に記載されます。また、この工程が外製工程である場合は、外製指示書にも記載されます。"),
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
        }

        // ******** ここから [画像]Tab *********

        $form['gen_editControlArray'][] = array(
            'label' => "",
            'type' => 'tab',
            'tabId' => 'tab_item_picture',
            'tabLabel' => _g('画像'),
            'denyMove' => true,
        );

        if (is_numeric(@$form['item_id']) && !isset($form['gen_record_copy'])) {
            $query = "select image_file_name from item_master where item_id = '{$form['item_id']}'";
            $imageFileName  = $gen_db->queryOneValue($query);
            $picture = "
                <div style='height:10px'></div>
                <div style='width:800px; text-align:center; font-weight:normal; background:#ffffcc'>
                <div style='height:10px'></div>

                <script>gen.imageUpload.init('{$imageFileName}','itemimage',{$form['item_id']})</script>

                "._g("JPG, GIF, PNG 画像を登録できます。画像のサイズは自動的に調整されます。") . "<br>
                "._g("（画像サイズが800×1000ピクセルを超える場合、あらかじめ画像編集ソフトで縮小しておくときれいな画像になります。）") ."
                <div style='height:10px'></div>
                </div>
                <div style='height:20px'></div>
            ";
        } else {
            $picture = _g("登録後、いったん一覧画面へ戻ってから明細表示すると画像の登録を行えるようになります。");
        }
        $form['gen_editControlArray'][] = array(
            'label_noEscape' => $picture,
            'type' => 'literal',
            'labelWidth' => '500',
            'colspan' => 2,
            'denyMove' => true,
        );

        // ******** Tab End *********
        $form['gen_editControlArray'][] = array(
            'type' => 'tabEnd',
            'denyMove' => true,
        );
    }

}
