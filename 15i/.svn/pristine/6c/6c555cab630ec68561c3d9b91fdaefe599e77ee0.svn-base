<?php

require_once("Model.class.php");

class Partner_SubcontractAccepted_Edit extends Base_EditBase
{

    function convert($converter, &$form)
    {
        $converter->nullBlankToValue('accepted_date', date("Y-m-d"));
    }

    // 既存データSQL実行前に処理
    function setQueryParam(&$form)
    {
        global $gen_db;

        $this->keyColumn = 'accepted_id';
        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");
        $this->selectQuery = "
            select
                accepted.accepted_id
                ,MAX(accepted.order_detail_id) as order_detail_id
                ,MAX(lot_no) as lot_no
                ,MAX(use_by) as use_by
                ,MAX(item_code) as item_code
                ,MAX(item_name) as item_name
                ,MAX(order_header.order_date) as order_date
                ,SUM(order_detail.order_detail_quantity) as order_detail_quantity
                ,MAX(order_detail.order_detail_dead_line) as order_detail_dead_line
                ,MAX(case when order_detail_completed then 'true' else '' end) as order_detail_completed
                ,MAX(accepted.order_seiban) as order_seiban
                ,MAX(accepted.stock_seiban) as stock_seiban
                ,MAX(accepted.accepted_date) as accepted_date
                ,MAX(accepted.inspection_date) as inspection_date
                ,MAX(accepted.accepted_quantity) as accepted_quantity
                ,MAX(accepted.foreign_currency_rate) as foreign_currency_rate
                ,MAX(case when order_detail.foreign_currency_id is null then accepted.accepted_price else accepted.foreign_currency_accepted_price end) as accepted_price
                ,MAX(accepted.tax_rate) as tax_rate
                ,MAX(case when currency_name is null then '{$keyCurrency}' else currency_name end) as currency_name
                ,MAX(customer_master.customer_no) as customer_no
                ,MAX(customer_master.customer_name) as customer_name
                ,MAX(accepted.remarks) as remarks
                ,MAX(accepted.location_id) as location_id
                ,MAX(accepted.payment_date) as payment_date
                ,MAX(subcontract_parent_order_no) as subcontract_parent_order_no
                ,MAX(subcontract_process_name) as subcontract_process_name
                ,MAX(subcontract_process_remarks_1) as subcontract_process_remarks_1
                ,MAX(subcontract_process_remarks_2) as subcontract_process_remarks_2
                ,MAX(subcontract_process_remarks_3) as subcontract_process_remarks_3
                ,MAX(subcontract_ship_to) as subcontract_ship_to

                ,MAX(coalesce(accepted.record_update_date, accepted.record_create_date)) as gen_last_update
                ,MAX(coalesce(accepted.record_updater, accepted.record_creator)) as gen_last_updater
            from
                accepted
                inner join order_detail on accepted.order_detail_id = order_detail.order_detail_id
                left join order_header on order_detail.order_header_id = order_header.order_header_id
                left join customer_master on order_header.partner_id = customer_master.customer_id
                left join currency_master on order_detail.foreign_currency_id = currency_master.currency_id
            [Where]
            group by
                accepted.accepted_id
        ";

        // データロックの判断基準となるフィールドを指定
        $form["gen_buyDateLockFieldArray"] = array("accepted_date");
    }

    // 既存データSQL実行後に処理
    // 画面表示のための設定
    function setViewParam(&$form)
    {
        global $gen_db;

        $this->modelName = "Partner_SubcontractAccepted_Model";

        $form['gen_pageTitle'] = _g("外製受入登録");
        $form['gen_entryAction'] = "Partner_SubcontractAccepted_Entry";
        $form['gen_listAction'] = "Partner_SubcontractAccepted_List";
        $form['gen_onLoad_noEscape'] = "onOrderNoChange(true)";
        $form['gen_pageHelp'] = _g("外注した製品の受入");
        
        // 新規モードではPOST URLに非ロックフラグを追加する。
        //  オーダー番号の項目は編集（or コピー）モードではロックされるようになっており、
        //  その際のモード判断は isset($form['order_detail_id']) で行っている。
        //  しかしそれだけだと、新規モードでオーダー番号を選択した状態で「リセット」リンクを
        //  クリックした時に誤動作する。（リセット時に order_detail_id が POSTされるため。）
        //  それで、新規モードの場合はオーダー番号の非ロックフラグをたてるようにする。
        if (!isset($form['order_detail_id'])) {
            $form['gen_editActionWithKey'] .= "&noLock";
        }

        $form['gen_javascript_noEscape'] = "
            // オーダー変更イベント（ページロード時にも実行）。Ajaxリクエストを行う
            function onOrderNoChange(isPageLoad) {
                $('#order_detail_id_show').select();
                $('#item_code').val('');
                $('#item_name').val('');
                $('#order_date').val('');
                $('#order_detail_quantity').val('');
                $('#order_detail_dead_line').val('');
                $('#order_seiban').val('');
                //$('#stock_seiban').val('');
                $('#currency_name').val('');
                var orderDetailId = $('#order_detail_id').val();
                if (!gen.util.isNumeric(orderDetailId)) return;
                // Ajaxリクエストが終了するまで、登録ボタンを押せないようにする（ただしオーダー変更直後は押せてしまう・・）
                gen.edit.submitDisabled();
                gen.ajax.connect('Partner_SubcontractAccepted_AjaxOrderParam', {order_detail_id : orderDetailId},
                    function(j) {
                        if (j != null) {
                            // バーコード入力対応
                            $('#order_detail_id_show').select();
                            // オーダー種類不正の判定
                            if (j.classification!='2') {
                               if (j.classification=='0') {
                                   alert('" . _g("指定された番号は製造指示書のオーダー番号です。製造指示書の完成登録は[実績登録]画面で行ってください。") . "');
                               } else {
                                   alert('" . _g("指定された番号は注文書のオーダー番号です。注文書の受入登録は[注文受入]画面で行ってください。") . "');
                               }
                               gen.edit.submitDisabled();
                               return;
                            }
                            $('#item_code').val(j.item_code);
                            $('#item_name').val(j.item_name);
                            $('#order_date').val(j.order_date);
                            $('#order_detail_quantity').val(gen_round(j.order_detail_quantity));
                            $('#order_detail_dead_line').val(j.order_detail_dead_line);
                            $('#order_seiban').val(j.seiban);
                            //$('#stock_seiban').val(j.stock_seiban);
                            $('#currency_name').val(j.currency_name);
                            " . (!isset($form['accepted_id']) ? "
                            	$('#accepted_quantity').val(gen_round(j.remained_quantity));
                            	$('#accepted_price').val(j.price);
                            	if (gen.util.isNumeric(j.default_location_id))
                                	$('#location_id').val(j.default_location_id);
                            " : "") . "
                            var elm = document.createElement('span');
                            elm.id = 'accepted_quantity_label';
                            if (j.order_measure != '' && j.order_measure != null) {
                                elm.innerHTML = '&nbsp;&nbsp;' + gen.util.escape(j.order_measure) + '&nbsp;&nbsp;';
                            } else {
                                elm.innerHTML = '';
                            }
                            if ($('#accepted_quantity_label') != null) 
                                $('#accepted_quantity_label').remove();
                            $('#accepted_quantity').after(elm);
                            $('#customer_no').val(j.customer_no);
                            $('#customer_name').val(j.customer_name);
                            $('#subcontract_parent_order_no').val(j.subcontract_parent_order_no);
                            $('#subcontract_process_name').val(j.subcontract_process_name);
                            $('#subcontract_process_remarks_1').val(j.subcontract_process_remarks_1);
                            $('#subcontract_process_remarks_2').val(j.subcontract_process_remarks_2);
                            $('#subcontract_process_remarks_3').val(j.subcontract_process_remarks_3);
                            $('#subcontract_ship_to').val(j.subcontract_ship_to);
                            " . (!isset($form['accepted_id']) ? "
                                if ($('#remarks').val() == '' && j.remarks_header != null) // IEではnullのとき空欄ではなく「null」と表示されてしまうのでチェック
                                    $('#remarks').val(j.remarks_header);
                            " : "") . "
                            calcAmount();
                        }
                        // バーコード入力対応
                        $('#accepted_quantity').select();
                        gen.edit.submitEnabled('{$form['gen_readonly']}');
                        if (!isPageLoad) {
                            // 検収日の設定
                            setInspectionDate();
                            // レート設定
                            setCurrencyRate();
                            // 税率設定
                            setTaxRate();
                        }
                    });
            }

            // デフォルト検収日のセット
            function setInspectionDate() {
                var odid = $('#order_detail_id').val();
                if (odid == '') return;
                var accDate = $('#accepted_date').val();
                if (accDate == '') return;

                var p = {order_detail_id: odid, accepted_date: accDate};

                gen.ajax.connect('Partner_SubcontractAccepted_AjaxInspectionDate', p,
                    function(j) {
                        if (j.status == 'success') {
                            $('#inspection_date').val(j.inspection_date);
                        }
                    });
            }

            // レート設定
            function setCurrencyRate() {
                var odid = $('#order_detail_id').val();
                if (odid == '') return;
                var accDate = $('#accepted_date').val();
                var insDate = $('#inspection_date').val();

                var p = {
                    order_detail_id: odid,
                    accepted_date: accDate,
                    inspection_date: insDate
                };

                gen.ajax.connect('Partner_SubcontractAccepted_AjaxCurrencyRateParam', p,
                    function(j) {
                        if (j.status == 'success') {
                            $('#foreign_currency_rate').val(j.foreign_currency_rate);
                        } else {
                            gen.ui.alterDisabled($('#foreign_currency_rate'), true);
                            $('#foreign_currency_rate').css('background-color','#cccccc');
                            $('#foreign_currency_rate').val('');
                        }
                    });
            }

            // 受入金額設定
            function calcAmount() {
                var qty = $('#accepted_quantity').val();
                var price = $('#accepted_price').val();
                $('#accepted_amount').val('');
                if (!gen.util.isNumeric(qty)) return;
                if (!gen.util.isNumeric(price)) return;
                $('#accepted_amount').val(gen.util.decCalc(qty,price,'*'));
            }

            // 税率設定
            function setTaxRate() {
                var odid = $('#order_detail_id').val();
                if (odid == '') return;
                var accDate = $('#accepted_date').val();
                var insDate = $('#inspection_date').val();

                var p = {
                    order_detail_id: odid,
                    accepted_date: accDate,
                    inspection_date: insDate
                };

                gen.ajax.connect('Partner_SubcontractAccepted_AjaxTaxRateParam', p,
                    function(j) {
                        if (j.status == 'success' && j.is_currency == false) {
                            $('#tax_rate').val(j.tax_rate);
                        } else {
                            $('#tax_rate').val('');
                        }
                        gen.ui.alterDisabled($('#tax_rate'), j.is_currency);
                    });
            }
        ";

        // 赤伝発行
        if (is_numeric(@$form['accepted_id']) && !isset($form['gen_redMode']) && $form['accepted_quantity'] > 0) {
            $form['gen_message_noEscape'] = "<a href='javascript:onRedClick()'>" . _g("赤伝票を登録する") . "</a>";
            $form['gen_javascript_noEscape'] .= "
                function onRedClick() {
                    var p = '&accepted_id={$form['accepted_id']}';
                    p += '&gen_redMode=true';
                    p += '&gen_record_copy=true';
                    location.href='index.php?action=Partner_SubcontractAccepted_Edit' + p;
                }
            ";
        }
        if (isset($form['gen_redMode'])) {
            $form['accepted_quantity'] = $form['accepted_quantity'] * -1;
            $form['accepted_date'] = date('Y-m-d');
            if (isset($form['inspection_date']) && Gen_String::isDateString($form['inspection_date']))
                $form['inspection_date'] = date('Y-m-d');
            $form['payment_date'] = '';
            $form['order_detail_completed'] = false;
            $form['gen_entryAction'] .= "&gen_redMode";    // 登録後、Listへ戻るため
        }

        $query = "select location_id, location_name from location_master order by location_code";
        $option_location_group = $gen_db->getHtmlOptionArray($query, false, array("0" => _g(GEN_DEFAULT_LOCATION_NAME)));

        $form['gen_editControlArray'] = array(
            array(
                'label' => _g('オーダー番号'),
                'type' => 'dropdown',
                'name' => 'order_detail_id',
                'value' => @$form['order_detail_id'],
                'dropdownCategory' => 'order_no_subcontract',
                'onChange_noEscape' => "onOrderNoChange(false)",
                'readonly' => (isset($form['order_detail_id']) && !($form['order_detail_id'] == '') && !isset($form['noLock'])),
                // ピンは廃止。この項目にピンを打つと新規モードでもreadonlyとなってしまい、ピンがはずせなくなるため。
                'hidePin' => true,
                'require' => true,
                'size' => '12',
                // ドロップダウンボタンにフォーカスが当たるのを回避（バーコード入力の際に邪魔。テキストボックスにはフォーカスが当たる）
                'tabindex' => '-1',
                'helpText_noEscape' => _g('受入対象となる注文書のオーダー番号を指定してください。')
            ),
            array(
                'label' => _g('ロット番号'),
                'type' => 'textbox',
                'name' => 'lot_no',
                'value' => @$form['lot_no'],
                'size' => '12',
                'helpText_noEscape' => _g('購買ロット番号を入力します。この番号を製造実績登録画面や納品登録画面で入力することで、製造や納品と使用部材ロットを結びつけることができ、トレーサビリティを実現できます。') . '<br>' .
                _g('ロット管理（トレーサビリティ）を必要としない場合は、入力の必要はありません。')
            ),
            array(
                'label' => _g('受入数'),
                'type' => 'textbox',
                'name' => 'accepted_quantity',
                'value' => @$form['accepted_quantity'],
                'onChange_noEscape' => "calcAmount()",
                'require' => true,
                'ime' => 'off',
                'size' => '10',
            ),
            array(
                'label' => _g('受入単価'),
                'type' => 'textbox',
                'name' => 'accepted_price',
                'value' => @$form['accepted_price'],
                'onChange_noEscape' => "calcAmount()",
                'ime' => 'off',
                'require' => true,
                'size' => '10',
            ),
            array(
                'label' => _g('受入金額'),
                'type' => 'textbox',
                'name' => 'accepted_amount',
                'value' => @$form['accepted_amount'],
                'ime' => 'off',
                'readonly' => true,
                'size' => '10',
            ),
            array(
                'label' => _g('税率'),
                'type' => 'textbox',
                'name' => 'tax_rate',
                'value' => @$form['tax_rate'],
                'ime' => 'off',
                'size' => '10',
            ),
            array(
                'label' => _g('受入日'),
                'type' => 'calendar',
                'name' => 'accepted_date',
                'value' => @$form['accepted_date'],
                'onChange_noEscape' => 'setInspectionDate()',
                'ime' => 'off',
                'require' => true,
                'helpText_noEscape' => _g("入荷日を入力します。") . "<br>"
                    . _g("この日付で在庫に計上されます。") . "<br>"
                    . _g("また、自社情報マスタの「仕入計上基準」が「受入日」の場合、この日付で買掛計上されます（買掛残高表等に反映されます）。"),
                'size' => '8',
            ),
            array(
                'label' => _g('レート'),
                'type' => 'textbox',
                'name' => 'foreign_currency_rate',
                'value' => @$form['foreign_currency_rate'],
                'ime' => 'off',
                'size' => '10',
                'helpText_noEscape' => _g('外貨取引の場合、適用するレートを入力します。') . '<br>' . _g('「受入日」を基準とした[為替レートマスタ]の適用レートが自動で表示されます。 ') . '<br>' .
                _g('ただし、[自社情報]の「仕入計上基準」が“検収日”の場合、「検収日」の入力があった時点で「検収日」を基準とした[為替レートマスタ]の適用レートが表示されます。') . '<br>' .
                _g('「受入日」あるいは「検収日」の日付を変更すると、「レート」の表示も更新されますのでご注意ください。 '),
            ),
            array(
                'label' => _g('検収日'),
                'type' => 'calendar',
                'name' => 'inspection_date',
                'value' => @$form['inspection_date'],
                'onChange_noEscape' => 'setCurrencyRate();setTaxRate()',
                'size' => '8',
                'helpText_noEscape' => _g("検収日を入力します。") . "<br>"
                    . _g("自社情報マスタの「仕入計上基準」が「検収日」の場合、この日付で買掛計上されます（買掛残高表等に反映されます）。") . '<br><br>'
                    . _g('取引先マスタの「検収リードタイム」が設定されている場合、受入日に検収リードタイムを足した日付（休日も考慮します）が自動設定されます。'),
            ),
            array(
                'label' => _g('支払予定日'),
                'type' => 'calendar',
                'name' => 'payment_date',
                'value' => @$form['payment_date'],
                'ime' => 'off',
                'size' => '8',
                'helpText_noEscape' => _g("この受入に対する支払の予定日を入力します。") . "<br>"
                    . _g("回収予定表等に反映されます。") . "<br>"
                    . _g("入力を省略すると、取引先マスタ「支払サイクル」から計算された日付が自動的に設定されます。"),
            ),
            array(
                'label' => _g('入庫ロケーション'),
                'type' => 'select',
                'name' => 'location_id',
                'options' => $option_location_group,
                'selected' => @$form['location_id'],
                'helpText_noEscape' => _g('受入品目を入庫したロケーションを指定します。オーダー番号を指定すると、自動的にデフォルト値が設定されます（品目マスタ「標準ロケーション（受入）」）。'),
            ),
            // 完了フラグ。ここでオンにしなくても、計画数 >= 受入数 ならDB登録時に自動的にオンになる
            // 計画数未達でも完了とみなしたいときは手動でオンにする
            array(
                'label' => _g('完了'),
                'type' => 'checkbox',
                'name' => 'order_detail_completed',
                'onvalue' => 'true', // trueのときの値。デフォルト値ではない
                'value' => @$form['order_detail_completed'],
                'helpText_noEscape' => _g("このチェックをオンにすると、受入数が発注数に満たなくても、受入が完了したものとみなされます。") . "<br>"
                    . _g("受入数が発注数を上回れば登録時に自動的にオンになります。"),
            ),
            array(
                'label'=>_g('消費期限'),
                'type'=>'calendar',
                'name'=>'use_by',
                'value'=>@$form['use_by'],
                'size'=>'8',
                'helpText_noEscape'=>_g('空欄にすると、製造日 + 消費期限日数（品目マスタ）が自動的に設定されます。')
            ),
            array(
                'label' => _g('外製受入備考'),
                'type' => 'textbox',
                'name' => 'remarks',
                'value' => @$form['remarks'],
                'ime' => 'on',
                'size' => '15',
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
                'type' => 'literal',
                'denyMove' => true,
            ),
            array(
                'type' => 'literal',
                'denyMove' => true,
            ),
            array(
                'label' => _g('発注先コード'),
                'type' => 'textbox',
                'name' => 'customer_no',
                'value' => @$form['customer_no'],
                'size' => '15',
                'readonly' => true,
            ),
            array(
                'label' => _g('発注先名'),
                'type' => 'textbox',
                'name' => 'customer_name',
                'value' => @$form['customer_name'],
                'size' => '15',
                'readonly' => true,
            ),
            array(
                'label' => _g('発行日'),
                'type' => 'textbox',
                'name' => 'order_date',
                'value' => @$form['order_date'],
                'size' => '8',
                'readonly' => true,
            ),
            array(
                'label' => _g('外注納期'),
                'type' => 'textbox',
                'name' => 'order_detail_dead_line',
                'value' => @$form['order_detail_dead_line'],
                'size' => '8',
                'readonly' => true,
            ),
            array(
                'label' => _g('品目コード'),
                'type' => 'textbox',
                'name' => 'item_code',
                'value' => @$form['item_code'],
                'size' => '20',
                'readonly' => true,
            ),
            // 以前は「製番（オーダー）」と「製番（計画）」に分かれていたが、計画登録で製番品目の登録ができなくなった
            // ため、両者が異なることはなくなった。ag.cgi?page=ProjectDocView&pid=1574&did=227601
            array(
                'label' => _g('製番'),
                'type' => 'textbox',
                'name' => 'order_seiban',
                'value' => @$form['order_seiban'],
                'size' => '8',
                'readonly' => true,
            ),
            array(
                'label' => _g('品目名'),
                'type' => 'textbox',
                'name' => 'item_name',
                'value' => @$form['item_name'],
                'size' => '20',
                'readonly' => true,
            ),
            array(
                'label' => _g('発注数'),
                'type' => 'textbox',
                'name' => 'order_detail_quantity',
                'value' => @$form['order_detail_quantity'],
                'size' => '8',
                'readonly' => true,
            ),
            array(
                'label' => _g('取引通貨'),
                'type' => 'textbox',
                'name' => 'currency_name',
                'value' => @$form['currency_name'],
                'size' => '8',
                'readonly' => true,
                'helpText_noEscape' => _g('発注先の取引通貨です（取引先マスタで指定）。受入単価はこの取引通貨で入力してください。')
            ),
            array(
                'label' => _g('親オーダー番号'),
                'type' => 'textbox',
                'name' => 'subcontract_parent_order_no',
                'value' => @$form['subcontract_parent_order_no'],
                'size' => '15',
                'readonly' => true,
                'helpText_noEscape' => _g('外製指示書が製造指示書の外製工程として発行された場合に、その製造指示書のオーダー番号を表示します。')
            ),
            array(
                'label' => _g('工程名'),
                'type' => 'textbox',
                'name' => 'subcontract_process_name',
                'value' => @$form['subcontract_process_name'],
                'size' => '15',
                'readonly' => true,
                'helpText_noEscape' => _g('外製指示書が製造指示書の外製工程として発行された場合に、その工程名を表示します。')
            ),
            array(
                'label' => _g('工程メモ1'),
                'type' => 'textbox',
                'name' => 'subcontract_process_remarks_1',
                'value' => @$form['subcontract_process_remarks_1'],
                'size' => '15',
                'readonly' => true,
            ),
            array(
                'label' => _g('工程メモ2'),
                'type' => 'textbox',
                'name' => 'subcontract_process_remarks_2',
                'value' => @$form['subcontract_process_remarks_2'],
                'size' => '15',
                'readonly' => true,
            ),
            array(
                'label' => _g('工程メモ3'),
                'type' => 'textbox',
                'name' => 'subcontract_process_remarks_3',
                'value' => @$form['subcontract_process_remarks_3'],
                'size' => '15',
                'readonly' => true,
            ),
            array(
                'label' => _g('発送先'),
                'type' => 'textbox',
                'name' => 'subcontract_ship_to',
                'value' => @$form['subcontract_ship_to'],
                'size' => '15',
                'readonly' => true,
            ),
        );
    }

}
