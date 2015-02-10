<?php

require_once("Model.class.php");

class Partner_Subcontract_Edit extends Base_EditBase
{

    function convert($converter, &$form)
    {
        $converter->nullToValue('order_date', date('Y-m-d'));
        $converter->nullBlankToValue('payout_lot_id', 0);
    }

    function validate($validator, &$form)
    {
        $validator->blankOrNumeric('order_header_id', _g('order_header_idが正しくありません。'));

        return 'action:Partner_Subcontract_List';        // if error
    }

    // データ取得のための設定
    function setQueryParam(&$form)
    {
        global $gen_db;

        $this->keyColumn = 'order_header_id';

        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");
        $this->selectQuery = "
            select
                order_header.*
                ,t_detail.*
                ,item_master.*
                ,coalesce(order_header.record_update_date, order_header.record_create_date) as gen_last_update
                ,coalesce(order_header.record_updater, order_header.record_creator) as gen_last_updater
            from
                order_header
                inner join (
                    select
                        order_header_id as ohid
                        ,order_detail_id
                        ,line_no
                        ,order_no
                        ,seiban
                        ,item_id
                        ,item_code
                        ,item_name
                        ,item_price
                        ,case when order_detail.foreign_currency_id is null then item_price else order_detail.foreign_currency_item_price end as item_price
                        ,case when currency_name is null then '{$keyCurrency}' else currency_name end as currency_name
                        ,item_sub_code
                        ,order_detail_quantity
                        ,order_detail_dead_line
                        ,accepted_quantity
                        ,order_detail_completed
                        ,alarm_flag
                        ,payout_location_id
                        ,payout_lot_id
                        ,plan_date
                        ,plan_qty
                        ,order_measure
                        ,multiple_of_order_measure
                        ,tax_class
                        ,subcontract_order_process_no
                        ,subcontract_parent_order_no
                        ,subcontract_process_name
                        ,subcontract_process_remarks_1
                        ,subcontract_process_remarks_2
                        ,subcontract_process_remarks_3
                        ,subcontract_ship_to
                        ,remarks
                    from
                        order_detail
                        left join currency_master on order_detail.foreign_currency_id = currency_master.currency_id
                    ) as t_detail on order_header.order_header_id = t_detail.ohid
                inner join item_master on t_detail.item_id = item_master.item_id
            [Where]
        ";

        // データロック対象外
        $query = "select unlock_object_4 from company_master";
        $unlock = $gen_db->queryOneValue($query);
        if ($unlock != "1") {
            // データロックの判断基準となるフィールドを指定
            $form["gen_buyDateLockFieldArray"] = array("order_date", "order_detail_dead_line");
        }
    }

    // 表示のための設定（Manufacturing_Order_Editに対する差分）
    function setViewParam(&$form)
    {
        global $gen_db;

        $this->modelName = "Partner_Subcontract_Model";

        $form['gen_pageTitle'] = _g("外製指示登録");
        $form['gen_entryAction'] = "Partner_Subcontract_Entry";
        $form['gen_listAction'] = "Partner_Subcontract_List";
        $form['gen_onLoad_noEscape'] = "calcShowParam();onItemIdChange(true);";
        $form['gen_pageHelp'] = _g("外製指示登録");

        // これを設定すると、「登録して印刷」ボタンと「帳票を印刷」ボタン（編集モードのみ）が表示される。
        $form['gen_reportArray'] = array(
            'action' => "Partner_Subcontract_Report",
            'param' => "check_[id]",
            'seq' => "order_header_order_header_id_seq",
        );

        $form['gen_message_noEscape'] = "";

        // 編集モードのみ
        if (isset($form['order_header_id']) && is_numeric($form['order_header_id'])) {
            // すでに実績が登録されている場合は、変更不可とする
            // ここでは全編集禁止としているが、一部変更許可するように変更するとしても、
            // 少なくとも発注先は変更不可とする必要がある（支給が変わる可能性があるので）
            //
            // もし実績登録後も変更可能にすると、以下の問題が発生する：
            // ・子品目を持つ品目に対する製造指示について、指示数より少ない製造数で製造完了扱いとし、
            //   さらにその後に製造指示数を変更した場合に、余分な予約数が登録されてしまう
            // ・子品目を持つ品目に対する製造指示について、実績登録後に製造指示を更新した場合に、予約数が不正になる
            // これらは Logic_Order::entryUsePlan() で使用予約の更新時にいったんUSE_PLANのレコードを
            // 削除した後で、納品済み数の差し引きおよび完了調整の再登録が行われていないため。
            // カスタマイズするならその点を考慮する必要がある。
            if (Logic_Accepted::hasAcceptedByOrderHeaderId($form['order_header_id'])) {
                $form['gen_readonly'] = "true";
                $form['gen_message_noEscape'] .= "<font color=red>" . _g("この外製指示登録に対する受入が登録されているため、内容を変更することはできません。") . "</font><br><br>";
            }

            // 構成変更の確認
            if (Logic_Order::isModifiedBom($form['order_header_id'])) {
                $form['gen_message_noEscape'] .= "<font color=blue>" . _g("前回この製造指示を登録した後で、関連する構成表マスタが変更されています。") . "<br>" .
                        _g("ここで「登録」ボタンを押すと、使用予約数や支給数、実績登録時の引落数に構成表マスタの変更が反映されます。") . "<br>" .
                        _g("「閉じる」をクリックすれば、前回登録時の構成が維持されます。") . "</font><br><br>";
            }
        }

        $form['gen_message_noEscape'] .= "<div id='gen_message_noEscape_payout' style='height:40px'></div>";

        $form['gen_javascript_noEscape'] = "
            // 発注先変更イベント（発注単価等をアップデート）
            function onPartnerIdChange() {
               showPrice(false);
               showPayoutMode();
            }

            // 品目変更イベント
            function onItemIdChange(lotUnitOnly) {
                showPrice(lotUnitOnly);

                var p = {
                    itemId : $('#item_id').val()
                };
                if (!gen.util.isNumeric(p.itemId)) {
                    return;
                }

                gen.ajax.connect('Partner_Subcontract_AjaxItemParam', p, 
                    function(j) {
                        if (j=='') return;

                        // MRP/ロット品目なら製番をロック
                        var s1 = $('#seiban_show');
                        var s2 = $('#seiban_dropdown');
                        var s3 = $('#seiban');
                        if (j.order_class == 0) {
                            s1.css('background-color','#ffffff');
                            gen.ui.enabled(s1);
                            s1.attr('readonly');    // 手入力は禁止
                            s2.removeAttr('disabled');
                        } else {
                            s1.css('background-color','#cccccc');
                            gen.ui.disabled(s1);    // readonlyだとフォーカス喪失後の背景色の問題あり
                            s1.val('');
                            s2.attr('disabled');
                            s3.val('');
                        }
                        // 外注納期デフォルト（LT+安全LT 後の日付。休日考慮）
                        var dlElm =  $('#order_detail_dead_line');
                        if (dlElm.val()=='') {
                            dlElm.val(j.default_dead_line);
                        }

                        showPayoutMode();
                    });
            }

            function onOrderMeasureChange() {
                showOrderMeasure($('#order_measure').val());
            }

            function showPrice(lotUnitOnly) {
                var p = {
                    itemId : $('#item_id').val(),
                    orderUserId : $('#partner_id').val()
                };
                if (isNaN(p.itemId)) return;

                gen.ajax.connect('Partner_Subcontract_AjaxItemParam', p, 
                    function(j) {
                        if (j=='') return;
                        if (!lotUnitOnly) {
                            // 品目と数量が入力済みのときのみ単価取得する
                            if (gen.util.isNumeric($('#order_detail_quantity').val())) {
                                var price = j.default_order_price;
                                var qty = $('#order_detail_quantity').val();
                                if (gen.util.isNumeric(qty)) {
                                    // 単価適用数により発注単価を決定。単価適用数がNullの場合、その発注単価を使用することに注意
                                    if (parseFloat(qty) > parseFloat(nnz(j.order_price_limit_qty_1)) && j.order_price_limit_qty_1 != null) {
                                        price = nnz(j.default_order_price_2);
                                        if (parseFloat(qty) > parseFloat(nnz(j.order_price_limit_qty_2)) && j.order_price_limit_qty_2 != null) {
                                            price = nnz(j.default_order_price_3);
                                        }
                                    }
                                }
                                price = gen_round(price);
                                showElm = $('#item_price_show');
                                if (showElm.val() == '' || showElm.val() == price || window.confirm('" . _g("発注単価を上書きしてもよろしいですか？　（上書きしてもよい場合は[OK]、現在入力されている発注単価を維持する場合は[キャンセル]）") . "')) {
                                    showElm.val(price);
                                    $('#item_price').val(price);
                                }
                            }

                            $('#order_measure').val(j.measure);
                            $('#multiple_of_order_measure').val(j.multiple_of_order_measure);
                            $('#currency_name').val(j.currency_name);
                            calcShowParam();
                        }
                        $('#default_lot_unit').val(j.default_lot_unit);
                        $('#default_lot_unit_2').val(j.default_lot_unit_2);
                        showMeasure(j.measure, j.order_measure);
                    });
            }

            // 支給のされ方を表示
            function showPayoutMode() {
                var p = {
                    itemId : $('#item_id').val(),
                    customerId : $('#partner_id').val(),
                    orderHeaderId : " . (is_numeric(@$form['order_header_id']) ? $form['order_header_id'] : 'null') . "
                };
                if (isNaN(p.itemId) || isNaN(p.customerId)) return;

                gen.ajax.connect('Partner_Subcontract_AjaxPayoutMode', p, 
                    function(j) {
                        if (j=='') 
                            return;
                        var msg = '<table><tr><td>';
                        msg += '<span style=\"color:blue\">" . _g("子品目の支給") . ": ';
                        switch(j.result) {
                            case 0: msg += '" . _g("なし （この品目には子品目がありません）") . "'; break;
                            case 1: msg += '" . _g("なし （品目マスタに発注先が「標準手配先」「代替手配先」として登録されていません。もしくは外製工程です）") . "'; break;
                            case 2: msg += '" . _g("なし （この品目には子品目がないか、もしくは品目マスタの手配区分が「外製（支給あり）」ではないか、外製工程です）") . "'; break;
                            case 3: msg += '" . _g("発行日にサプライヤーロケ「' + gen.util.escape(j.location_name) + '」に在庫移動し、受入日にそのロケーションから在庫引き落としされます。") . "'; break;
                            case 4: msg += '" . _g("発行日に支給（在庫引き落とし）されます。") . "'; break;
                            case 5: msg += '" . _g("受入日に支給（在庫引き落とし）されます。") . "'; break;
                            default: msg = '" . _g("支給方法の取得でエラーが発生しました。") . "'; break;
                        }
                        msg += '</span>';
                        msg += '</td><td>';
                        msg += '<a class=\"gen_chiphelp\" href=\"#\" rel=\"p.helptext_payout_mode\" title=\"" . _g("子品目の支給について") . "\" tabindex=\"-1\"><img class=\"imgContainer sprite-question\" src=\"img/space.gif\" style=\"border:none\"></a>';
                        msg += '<p class=\"helptext_payout_mode\" style=\"display:none;\">';
                        msg += '" . _g("■支給の有無について") . "<br><br>';
                        msg += '" . _g("次の二つの条件が満たされたとき、子品目の支給（在庫引き落とし）が行われます。") . "<br>';
                        msg += '" . _g("・構成表マスタで子品目が登録されている。") . "<br>';
                        msg += '" . _g("・品目マスタに発注先が「標準手配先」もしくは「代替手配先」として登録されており、その手配区分が「外製（支給あり）」である。") . "<br><br>';
                            
                        msg += '" . _g("※ちなみに外製工程（製造指示書の工程の一部として発行された外製指示書）の場合、手配区分が「内製」となるので支給は行われません。") . "';
                        msg += '" . _g("ただしその発注先を手配先としても指定しておき、手配区分を「外製（支給あり）」にすれば支給されます。") . "<br><br>';
                            
                        msg += '" . _g("■支給のタイミングについて") . "<br><br>';
                        msg += '" . _g("支給がどのタイミングで行われるかは、次のような要素で決まります。") . "<br>';
                        msg += '" . _g("・ロケーションマスタでサプライヤーロケーション（サプロケ）が設定されている場合、外製指示書登録時点で在庫がサプロケへと移動し、外製受入登録時点でサプロケから引き落とされます。") . "<br>';
                        msg += '" . _g("・サプロケがない場合、自社情報マスタの「外製支給のタイミング」の設定によって決まります。") . "<br><br>';
                            
                        msg += '" . _g("■その他") . "<br><br>';
                        msg += '" . _g("登録後に各マスタを書き換えても、この外製指示登録の支給の動作が変わることはありません。ただし、この外製指示登録を再登録すれば現時点のマスタにもとづき動作が上書きされます。") . "<br><br>';
                        msg += '" . _g("支給単価は品目マスタに登録されているものが使用されます。支給登録画面で変更できます。") . "</p>';
                        msg += '</td></tr></table>';
                        $('#gen_message_noEscape_payout').html(msg);
                        gen.ui.initChipHelp();
                    });
            }

            function nnz(val) {
                return (gen.util.isNumeric(val) ? val : 0);
            }

            function showMeasure(measure, order_measure) {
                showMeasureSub('order_detail_quantity', measure);
                if (measure != '') 
                    showMeasureSub('item_price', ' / ' + measure);
                showMeasureSub('default_lot_unit', measure);
                showMeasureSub('default_lot_unit_2', measure);
                showOrderMeasure(order_measure);
            }

            function showOrderMeasure(order_measure) {
                if (order_measure != '') 
                    showMeasureSub('show_price', ' / ' + order_measure);
                showMeasureSub('show_quantity', order_measure);
            }

            function showMeasureSub(parentId, label) {
                var labelId = parentId + '_label';
                if ($('#' + labelId)!=null) {
                    $('#' + labelId).remove();
                }
                var elm = document.createElement('span');
                elm.id = labelId;
                elm.style.paddingLeft='10px';
                elm.innerHTML = gen.util.escape(label); // ajax取得した値を innerHTMLに渡す際はescape()が必要
                $('#' + parentId).after(elm);
            }

            // 注文書表示数量と単価の再計算
            function calcShowParam() {
               var qty = $('#order_detail_quantity').val();
               var price = $('#item_price').val();
               var mul = $('#multiple_of_order_measure').val();
               if (!gen.util.isNumeric(mul) || mul==0) mul = 1;
               var showQtyElm = $('#show_quantity');
               var showPriceElm = $('#show_price');
               if (gen.util.isNumeric(qty)) {
                   showQtyElm.val(qty / mul);
               } else {
                   showQtyElm.val('');
               }
               if (gen.util.isNumeric(price)) {
                   showPriceElm.val(price * mul);
               } else {
                   showPriceElm.val('');
               }
            }

            // 従業員を選んだらその所属部門を部門のデフォルトとして設定
            function onWorkerChange() {
                var wid = $('#worker_id').val();
                if (wid == 'null') return;
                gen.ajax.connect('Partner_Subcontract_AjaxWorkerParam', {worker_id : wid}, 
                    function(j) {
                        document.getElementById('section_id').value = j.section_id;
                    });
            }
        ";

        // コピーモードではオーダー番号と製番を消す
        if (isset($form['gen_record_copy'])) {
            unset($form['order_no']);
            // 製番は残すようにした。外製オーダーを複数の発注先に振り分ける目的でコピー機能を使用する場合、
            // 製番もコピーされたほうが便利なため。
            //unset($form['seiban']);
        }

        $query = "select location_id, location_name from location_master order by location_code";
        $option_location_group = $gen_db->getHtmlOptionArray($query, false, array("-1" => _g("(各部材の標準ロケ)"), "0" => _g(GEN_DEFAULT_LOCATION_NAME)));

        $query = "select section_id, section_name from section_master order by section_code";
        $option_section = $gen_db->getHtmlOptionArray($query, true);

        $form['gen_editControlArray'] = array(
            array(
                'label' => _g('オーダー番号'),
                'type' => 'textbox',
                'name' => 'order_no',
                'value' => @$form['order_no'],
                'size' => '15',
                'readonly' => (isset($form['order_header_id']) && !isset($form['gen_record_copy'])),
                'ime' => 'off',
                'hidePin' => true,
                'helpText_noEscape' => _g("自動的に採番されますので、指定する必要はありません。") . "<br>" .
                _g("修正モードではオーダー番号を変更することはできません。"),
            ),
            // 10iから、オーダーに手動で製番を付与したり、振り替えたりできるようになった（製番品目のみ）。
            // 詳細は Partner_Order_Edit の カラムリストの製番の箇所のコメントを参照。
            // 15iでは、完了済の製番も選択できるようになった。ag.cgi?page=ProjectDocView&pid=1574&did=217672
            array(
                'label' => _g('製番'),
                'type' => 'dropdown',
                'name' => 'seiban',
                'value' => @$form['seiban'],
                'size' => '10',
                'dropdownCategory' => 'received_seiban',
                'dropdownShowCondition_noEscape' => "!isNaN([item_id]) && !$('#seiban_show').attr('disabled')",
                'dropdownShowConditionAlert' => _g("製番を指定できるのは、製番品目が指定されている場合のみです。"),
                'readonly' => @$form['order_class'] == '1' || @$form['order_class'] == '2',
                'noWrite' => true,
                'tabindex' => -1,
                'helpText_noEscape' => '<b>' . _g('MRP/ロット品目') . '：</b>' . _g('製番はつきません。') .
                        '<br><br><b>' . _g('製番品目') . '：</b>' . _g('このオーダーが所要量計算の結果として発行されたものであれば、' .
                        'もとになった受注と同じ製番が自動的につきます。') .
                        '<br>' . _g('ドロップダウンで任意の受注製番を指定して、外製指示登録と受注を結びつけることもできます。' .
                        'ドロップダウンで表示されるのは、製番品目の確定受注だけです。'),
            ),
            array(
                'label' => _g('発行日'),
                'type' => 'calendar',
                'name' => 'order_date',
                'value' => @$form['order_date'],
                'size' => '8',
                'isCalendar' => true,
                'require' => true,
                'helpText_noEscape' => _g("帳票に発行日として表示されます。"),
            ),
            array(
                'label' => _g('外注納期'),
                'type' => 'calendar',
                'name' => 'order_detail_dead_line',
                'value' => @$form['order_detail_dead_line'],
                'size' => '8',
                'isCalendar' => true,
                'require' => true,
                'helpText_noEscape' => _g("外注納期を指定します。") . "<br>"
                    . _g("品目を選択した際、この品目のリードタイム（安全リードタイムを含む）とカレンダーマスタを考慮した外注納期が自動的に設定されます。（外注納期が空欄の場合のみ）"),
            ),
            array(
                'label' => _g('発注先'),
                'type' => 'dropdown',
                'name' => 'partner_id',
                'value' => @$form['partner_id'],
                'size' => '12',
                'subSize' => '20',
                'dropdownCategory' => 'partner_for_order',
                'dropdownParam' => '[item_id]',
                'autoCompleteCategory' => 'customer_partner',
                'onChange_noEscape' => 'onPartnerIdChange()',
                'require' => true,
                // 修正モードでは発注先の変更禁止。
                // 発注先を変更されると子品目支給数が変わってくる可能性があるため。
                // ただし外製工程では変更可能とする（外製工程は常に支給なし）
                'readonly' => (isset($form['order_header_id']) && !isset($form['gen_record_copy']) && (!isset($form['subcontract_parent_order_no']) || $form['subcontract_parent_order_no'] === '')),
                'helpText_noEscape' => _g("外製指示書を発行する相手を選択します。") . "<br><br>"
                    . _g("品目マスタで「支給有り」に指定されている場合は、子品目の支給（出庫）処理が行われます。") . "<br><br>"
                    . _g("修正モードでは発注先を変更することはできません。ただし外製工程の場合は変更できます。"),
            ),
            array(
                'label' => _g('数量'),
                'type' => 'textbox',
                'name' => 'order_detail_quantity',
                'value' => @$form['order_detail_quantity'],
                'onChange_noEscape' => 'showPrice()',
                'size' => '8',
                'ime' => 'off',
                'require' => true,
            ),
            array(
                'label' => _g('品目'),
                'type' => 'dropdown',
                'name' => 'item_id',
                'value' => @$form['item_id'],
                'dropdownCategory' => 'item_order_subcontract',
                'autoCompleteCategory' => 'item_order_subcontract',
                'dropdownParam' => '[partner_id]',
                'onChange_noEscape' => 'onItemIdChange(false)',
                'require' => true,
                // 外製工程では修正モードでの品目の変更禁止。
                'readonly' => (isset($form['order_header_id']) && !isset($form['gen_record_copy']) && isset($form['subcontract_parent_order_no'])),
                'size' => '12',
                'subSize' => '20',
            ),
            array(// 単価履歴参照機能のため拡張DD化
                'label' => _g('発注単価'),
                'type' => 'dropdown',
                'name' => 'item_price',
                'value' => @$form['item_price'],
                'size' => '8',
                'dropdownCategory' => 'order_price',
                'dropdownParam' => "[partner_id];[item_id]",
                'dropdownShowCondition_noEscape' => "!isNaN([item_id])",
                'dropdownShowConditionAlert' => _g("先に品目を指定してください。"),
                'require' => true,
                'onChange_noEscape' => "calcShowParam();",
                'helpText_noEscape' => _g('発注単位あたりの単価ではなく、管理単位あたりの単価で指定してください。') . '<br><br>' .
                _g('また、「取引通貨」の項目に表示されている取引通貨で指定してください。'),
            ),
            array(
                'label' => _g('同時に受入を登録'),
                'type' => 'checkbox',
                'name' => 'accepted_regist',
                'onvalue' => 'true', // trueのときの値。デフォルト値ではない
                'value' => @$form['accepted_regist'],
                'helpText_noEscape' => _g('このチェックをオンにすると、外製指示登録と同時に受入も登録されます。')
            ),
            array(
                'type' => 'literal',
                'denyMove' => true,
            ),
            array(
                'label' => _g('取引通貨'),
                'type' => 'textbox',
                'name' => 'currency_name',
                'value' => @$form['currency_name'],
                'size' => '8',
                'readonly' => true,
                'helpText_noEscape' => _g('発注先の取引通貨です（取引先マスタで指定）。単価はこの取引通貨で入力してください。')
            ),
            array(
                'type' => 'literal',
                'denyMove' => true,
            ),
            array(
                'label' => _g('発注単位'),
                'type' => 'textbox',
                'name' => 'order_measure',
                'value' => @$form['order_measure'],
                'size' => '8',
                'onChange_noEscape' => "onOrderMeasureChange()",
                'helpText_noEscape' => _g('「個」「kg」「m」など、発注の単位を外製指示書に記載したい場合、この項目を登録します。'),
            ),
            array(
                'label' => _g('手配単位倍数'),
                'type' => 'textbox',
                'name' => 'multiple_of_order_measure',
                'value' => @$form['multiple_of_order_measure'],
                'size' => '8',
                'onChange_noEscape' => "calcShowParam()",
                'ime' => 'off',
                'helpText_noEscape' => _g('在庫管理単位と発注単位が異なる場合、その倍率を指定します。例えばグラム管理している品目をキログラム単位で発注する場合、1000と登録します。省略すると1になります。')
            ),
            array(
                'label' => _g('表示数量'),
                'type' => 'textbox',
                'name' => 'show_quantity',
                'value' => @$form['show_quantity'],
                'size' => '8',
                'readonly' => true,
                'helpText_noEscape' => _g('実際に外製指示書に表示される数量。「数量 ÷ 手配単位倍数」です。'),
            ),
            array(
                'label' => _g('表示単価'),
                'type' => 'textbox',
                'name' => 'show_price',
                'value' => @$form['show_price'],
                'size' => '8',
                'readonly' => true,
                'helpText_noEscape' => _g('実際に外製指示書に表示される単価。「発注単価 × 手配単位倍数」です。'),
            ),
            array(
                'label' => _g('最低ロット数'),
                'type' => 'textbox',
                'name' => 'default_lot_unit',
                'value' => '',
                'size' => '8',
                'readonly' => true,
                'helpText_noEscape' => _g("品目マスタの「最低ロット数」が表示されます。「0」はまるめないことを表します。") . "<br>"
                    . _g("ここでは参考用に表示されるだけで、この値に基づいて自動的に発注数が調整されることはありません。"),
            ),
            array(
                'label' => _g('手配ロット数'),
                'type' => 'textbox',
                'name' => 'default_lot_unit_2',
                'value' => '',
                'size' => '8',
                'readonly' => true,
                'helpText_noEscape' => _g("品目マスタの「手配ロット数」が表示されます。「0」はまるめないことを表します。") . "<br>"
                    . _g("ここでは参考用に表示されるだけで、この値に基づいて自動的に発注数が調整されることはありません。"),
            ),
            array(
                'label' => _g('担当者(自社)'),
                'type' => 'dropdown',
                'dropdownCategory' => 'worker',
                'name' => 'worker_id',
                'value' => @$form['worker_id'],
                'onChange_noEscape' => 'onWorkerChange()',
                'size' => '12',
                'subSize' => '20',
            ),
            array(
                'label' => _g('部門(自社)'),
                'type' => 'select',
                'name' => 'section_id',
                'options' => $option_section,
                'selected' => @$form['section_id'],
            ),
            array(
                'label' => _g('子品目支給元ロケ'),
                'type' => 'select',
                'name' => 'payout_location_id',
                'options' => $option_location_group,
                'selected' => @$form['payout_location_id'],
                'helpText_noEscape' => _g("支給子品目を出庫するロケーションを指定します。支給がない場合は指定の必要はありません。") . "<br>"
                    . _g("「(各部材の標準ロケ)」を指定すると、各部材の標準ロケーション（品目マスタ「標準ロケーション（使用）」）が支給元ロケーションになります。") . "<br><br>"
                    . _g("支給部材が引き落とされるタイミングは、[メンテナンス] - [自社情報] の「外製支給のタイミング」の設定で決まります。"),
            ),
            array(
                'label' => _g('外製指示備考'),
                'type' => 'textbox',
                'name' => 'remarks_header',
                'value' => @$form['remarks_header'],
                'ime' => 'on',
                'size' => '20'
            ),
            array(
                'type' => 'literal',
                'denyMove' => true,
            ),
            array(
                'type' => 'literal',
                'denyMove' => true,
            ),
            array(
                'label' => _g('親オーダー番号'),
                'type' => 'textbox',
                'name' => 'subcontract_parent_order_no',
                'value' => @$form['subcontract_parent_order_no'],
                'size' => '15',
                'readonly' => true,
                'helpText_noEscape' => _g('この外製指示書が製造指示書の外製工程として発行された場合に、その製造指示書のオーダー番号を表示します。')
            ),
            // 10iではこの項目はなかったが、外製工程オーダーをコピーしたときにこの値が必要になるため追加した。
            // くわしくは Modelの最後の部分のコメントを参照。
            array(
                'label' => _g('親オーダー工程番号'),
                'type' => 'textbox',
                'name' => 'subcontract_order_process_no',
                'value' => @$form['subcontract_order_process_no'],
                'size' => '15',
                'readonly' => true,
                'helpText_noEscape' => _g('この外製指示書が製造指示書の外製工程として発行された場合に、その製造指示書の該当工程の実績登録コード（オーダー番号 + 工程番号）を表示します。')
            ),
            array(
                'label' => _g('工程名'),
                'type' => 'textbox',
                'name' => 'subcontract_process_name',
                'value' => @$form['subcontract_process_name'],
                'size' => '15',
                'readonly' => true,
                'helpText_noEscape' => _g('帳票に印字されます。この外製指示書が製造指示書の外製工程として発行された場合に、その工程名を表示します。')
            ),
            array(
                'label' => _g('工程メモ1'),
                'type' => 'textbox',
                'name' => 'subcontract_process_remarks_1',
                'value' => @$form['subcontract_process_remarks_1'],
                'size' => '15',
                // 製造指示書の外製工程として発行された時のみ入力可能（外製指示書の登録時には入力不可）
                'readonly' => (isset($form['subcontract_order_process_no']) && $form['subcontract_order_process_no'] != "" ? false : true),
                'helpText_noEscape' => _g('帳票に印字されます。この外製指示書が製造指示書の外製工程として発行された場合に、品目マスタの工程タブの「工程メモ」を表示します。編集も可能です。')
            ),
            array(
                'label' => _g('工程メモ2'),
                'type' => 'textbox',
                'name' => 'subcontract_process_remarks_2',
                'value' => @$form['subcontract_process_remarks_2'],
                'size' => '15',
                // 製造指示書の外製工程として発行された時のみ入力可能（外製指示書の登録時には入力不可）
                'readonly' => (isset($form['subcontract_order_process_no']) && $form['subcontract_order_process_no'] != "" ? false : true),
                'helpText_noEscape' => _g('帳票に印字されます。この外製指示書が製造指示書の外製工程として発行された場合に、品目マスタの工程タブの「工程メモ」を表示します。編集も可能です。')
            ),
            array(
                'label' => _g('工程メモ3'),
                'type' => 'textbox',
                'name' => 'subcontract_process_remarks_3',
                'value' => @$form['subcontract_process_remarks_3'],
                'size' => '15',
                // 製造指示書の外製工程として発行された時のみ入力可能（外製指示書の登録時には入力不可）
                'readonly' => (isset($form['subcontract_order_process_no']) && $form['subcontract_order_process_no'] != "" ? false : true),
                'helpText_noEscape' => _g('帳票に印字されます。この外製指示書が製造指示書の外製工程として発行された場合に、品目マスタの工程タブの「工程メモ」を表示します。編集も可能です。')
            ),
            array(
                'label' => _g('発送先'),
                'type' => 'textbox',
                'name' => 'subcontract_ship_to',
                'value' => @$form['subcontract_ship_to'],
                'size' => '15',
                // 製造指示書の外製工程として発行された時のみ入力可能（外製指示書の登録時には入力不可）
                'readonly' => (isset($form['subcontract_order_process_no']) && $form['subcontract_order_process_no'] != "" ? false : true),
                'helpText_noEscape' => _g('帳票に印字されます。この外製指示書が製造指示書の外製工程として発行された場合に、次工程のオーダー先（自社もしくは外製先）を表示します。編集も可能です。')
            ),
        );
    }

}
