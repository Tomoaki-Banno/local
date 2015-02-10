<?php

require_once("Model.class.php");

class Manufacturing_Estimate_Edit extends Base_EditBase
{

    function convert($converter, &$form)
    {
        global $gen_db;

        $converter->nullToValue('estimate_date', date('Y-m-d'));

        // List明細モードから来た場合は header_id が指定されていない。detail_id から header_id を取得する。
        if (is_numeric(@$form['estimate_detail_id'])) {
            $query = "select estimate_header_id from estimate_detail where estimate_detail_id = '{$form['estimate_detail_id']}'";
            $tranId = $gen_db->queryOneValue($query);
            $converter->nullBlankToValue('estimate_header_id', $tranId);
        }
    }

    function validate($validator, &$form)
    {
        $validator->blankOrNumeric('estimate_header_id', _g('estimate_header_idが正しくありません。'));

        return 'action:Manufacturing_Estimate_List';        // if error
    }

    // データ取得のための設定
    function setQueryParam(&$form)
    {
        $this->keyColumn = 'estimate_header_id';

        $this->selectQuery = "
            select
                *
                ,coalesce(estimate_header.record_update_date, estimate_header.record_create_date) as gen_last_update
                ,coalesce(estimate_header.record_updater, estimate_header.record_creator) as gen_last_updater
            from
                estimate_header
            [Where]
        ";

        // データロックの判断基準となるフィールドを指定
        $form["gen_salesDateLockFieldArray"] = array("estimate_date");
    }

    // 表示のための設定
    function setViewParam(&$form)
    {
        global $gen_db;

        $this->modelName = "Manufacturing_Estimate_Model";

        $form['gen_pageTitle'] = _g("見積登録");
        $form['gen_entryAction'] = "Manufacturing_Estimate_Entry";
        $form['gen_listAction'] = "Manufacturing_Estimate_List";
        $form['gen_onLoad_noEscape'] = "onLoad();";
        $form['gen_beforeEntryScript_noEscape'] = "beforeEntry()";
        $form['gen_pageHelp'] = _g("見積");

        // これを設定すると、「登録して印刷」ボタンと「帳票を印刷」ボタン（編集モードのみ）が表示される。
        $form['gen_reportArray'] = array(
            'action' => "Manufacturing_Estimate_Report",
            'param' => "check_[id]",
            'seq' => "estimate_header_estimate_header_id_seq",
        );

        $isReload = (isset($form['gen_validError']) && $form['gen_validError']) || isset($form['gen_editReload']);

        $form['gen_javascript_noEscape'] = "
            // ページロード
            function onLoad() {
                " . (isset($form['gen_record_copy']) && !isset($form['estimate_header_id']) ? "
                // コピーモードの登録後は各行の内容をリセットする。不要な情報が残ってしまうのを避けるため
                $('[id^=line_no]').each(function(){
                    lineNo = this.innerHTML;
                    onItemIdChange(lineNo);
                });
                " : "") . "
                " . ($isReload ? "
                // リロード（新規登録モードでのバリデーションエラーによる差し戻し、および項目変更・並べ替え・項目リセット・明細行数変更）
                // およびコピーモードのときのみ、各行の再計算を行う。
                // ちなみに通常時（リロード以外の場合）はSQLによってそれらが取得されるのでこれを実行する必要はない。
                // また、EditListに関しては修正モードのエラー時にもSQL取得される。
                $('[id^=line_no]').each(function(){
                    lineNo = this.innerHTML;
                    if ($('#quantity_'+lineNo).val()!='') {
                        calcMargin(lineNo);
                    }
                });
                " : "") . "
                onCustomerIdChange(true);
                calcTotalAmount();
            }

            // 得意先が変わったら得意先名を表示する。
            // また、単価を更新する。
            // 品目が選択されているすべての明細行に対してAjax処理がおこなわれるので重い処理だが、
            // 通常は先に得意先を選択してから各品目を入力していくので、それほど問題は大きくないと思う。
            function onCustomerIdChange(isEdit) {
                if (!gen.util.isNumeric(custId = $('#customer_id').val())) {
                    $('#currency_name').val('');
                    $('#credit_line').val('');
                    return;
                }

                gen.ajax.connect('Manufacturing_Estimate_AjaxCustomerParam', {customerId : custId},
                    function(j) {
                        if (!isEdit) {
                            $('#customer_name').val(j.customer_name);
                            $('#person_in_charge').val(j.person_in_charge);
                            $('#customer_zip').val(j.zip);
                            $('#customer_address1').val(j.address1);
                            $('#customer_address2').val(j.address2);
                            $('#customer_tel').val(j.tel);
                            $('#customer_fax').val(j.fax);
                        }
                        $('#currency_name').val(j.currency_name);
                        $('#credit_line').val(gen.util.addFigure(j.credit_line));
                        $('#receivable_balance').val(gen.util.addFigure(j.receivable_balance));
                    });

                // 編集時は終了
                if (isEdit) return;

                // 単価の再計算
                // EditListのすべての行に対する処理（idがline_noで始まるすべてのエレメント、つまり行番号divをたどる）
                isFirst = true;
                $('[id^=line_no]').each(function(){
                    lineNo = this.innerHTML;
                    if (isFirst && gen.util.isNumeric($('#sale_price_'+lineNo).val())) {
                        if (!window.confirm('" . _g("得意先を変更すると、品目の見積単価が変更される場合があります。見積単価を上書きしてもよろしいですか？　（上書きしてもよい場合は[OK]、現在入力されている単価を維持する場合は[キャンセル]）") . "')) {
                            return false;   // break
                        }
                        isFirst = false;
                    }
                    updatePrice(lineNo, false);
                });
            }

            // 品目が変わったらAjaxで品目名・見積単価・販売原価・単位・課税区分を取得して設定
            function onItemIdChange(lineNo) {
                $('#item_code_'+lineNo).val('');
                $('#item_name_'+lineNo).val('');
                $('#sale_price_'+lineNo).val('');
                $('#base_cost_'+lineNo).val('');
                $('#stock_quantity_'+lineNo).html('');
                $('#measure_'+lineNo).val('');
                $('#tax_class_'+lineNo).val(0);
                if (!gen.util.isNumeric(itemId = $('#item_id_'+lineNo).val())) {
                    calcMargin(lineNo);
                    calcTotalAmount();
                    return;
                }

                var p = {itemId : itemId, customerId : $('#customer_id').val(), qty : $('#quantity_'+lineNo).val()};
                gen.ajax.connect('Manufacturing_Estimate_AjaxItemParam', p,
                    function(j) {
                        if (j.status=='success') {
                            $('#item_code_'+lineNo).val(j.item_code);
                            $('#item_name_'+lineNo).val(j.item_name).focus();   // focusはplaceholder消去のため
                            $('#sale_price_'+lineNo).val(gen_round(j.sale_price));
                            $('#base_cost_'+lineNo).val(gen_round(j.base_cost));
                            $('#stock_quantity_'+lineNo).text(gen.util.addFigure(j.stock));
                            $('#measure_'+lineNo).val(j.measure);
                            $('#tax_class_'+lineNo).val(j.tax_class);
                        }
                        calcMargin(lineNo);
                        calcTotalAmount();
                    });
            }

            // 単価を更新
            function updatePrice(lineNo, needConfirm) {
                if (!gen.util.isNumeric(itemId = $('#item_id_'+lineNo).val())) {
                    calcMargin(lineNo);
                    calcTotalAmount();
                    return;
                }
                var p = {itemId : itemId, customerId : $('#customer_id').val(), qty : $('#quantity_'+lineNo).val()};

                gen.ajax.connect('Manufacturing_Estimate_AjaxItemParam', p,
                    function(j) {
                        var price = gen_round(j.sale_price);
                        var elm = $('#sale_price_'+lineNo);
                        if (needConfirm && elm.val() != '' && price != elm.val()) {
                            if (window.confirm('" . _g("見積単価をマスタ単価によって上書きしてもよろしいですか？（上書きしてもよい場合は[OK]、現在入力されている単価を維持する場合は[キャンセル]）") . "')) {
                                elm.val(price);
                            }
                        } else {
                            elm.val(price);
                        }
                        // 合計の再計算
                        calcMargin(lineNo);
                        calcTotalAmount();
                    });
            }

            // 単価変更イベント
            function onPriceChange(lineNo) {
                calcMargin(lineNo);
                calcTotalAmount();
            }

            // 販売原価変更イベント
            function onBaseCostChange(lineNo) {
                calcMargin(lineNo);
                calcTotalAmount();
            }

            // 粗利と販売原価の計算
            function calcMargin(lineNo) {
                var baseCost = gen.util.delFigure($('#base_cost_'+lineNo).val());
                var salePrice = $('#sale_price_'+lineNo).val();
                var quantity = $('#quantity_'+lineNo).val();
                if (!gen.util.isNumeric(baseCost)) baseCost = 0;
                if (!gen.util.isNumeric(salePrice)) salePrice = 0;
                if (!gen.util.isNumeric(quantity)) quantity = 0;
                $('#amount_'+lineNo).html(gen.util.addFigure(gen.util.decCalc(salePrice, quantity, '*')));
                $('#calc_base_cost_'+lineNo).html(gen.util.addFigure(gen.util.decCalc(baseCost, quantity, '*')));
                $('#gross_margin_'+lineNo).html(gen.util.addFigure(gen.util.decCalc(salePrice, baseCost, '-')));
                $('#calc_gross_margin_'+lineNo).html(gen.util.addFigure(gen.util.decCalc(gen.util.decCalc(salePrice, baseCost, '-'), quantity, '*')));
            }

            // 合計金額の計算
            function calcTotalAmount() {
               $('#total_amount').val(gen.util.addFigure(calcTotal('amount')));
               $('#total_basecost').val(gen.util.addFigure(calcTotal('calc_base_cost')));
               $('#total_margin').val(gen.util.addFigure(calcTotal('calc_gross_margin')));
            }

            // 合計計算sub
            function calcTotal(prefix) {
               var total = 0;
               $('[id^='+prefix+'_]').each(function(){
                    var v = gen.util.delFigure(this.innerHTML);
                    if (gen.util.isNumeric(v)) {
                        total = gen.util.decCalc(total, v, '+');
                    }
               });
               return total;
            }

            // 担当者を選んだらその所属部門を部門のデフォルトとして設定
            function onWorkerChange() {
                var wid = $('#worker_id').val();
                if (wid == 'null') return;
                gen.ajax.connect('Manufacturing_Received_AjaxWorkerParam', {worker_id : wid},
                    function(j) {
                        document.getElementById('section_id').value = j.section_id;
                    });
            }

            // 登録前処理
            function beforeEntry() {
                // 与信限度額チェック
                var custId = $('#customer_id').val();
                var cl = gen.util.delFigure($('#credit_line').val());
                var ta = gen.util.delFigure($('#total_amount').val());
                if (gen.util.isNumeric(custId) && gen.util.isNumeric(cl)) {
                    gen.ajax.connect('Manufacturing_Estimate_AjaxCustomerParam', {customerId : custId},
                        function(j) {
                            if (gen.util.decCalc(j.receivable_balance, ta, '+') > cl) {
                                if (!window.confirm('" . _g("売掛残高と今回見積額の合計が与信限度額をオーバーしていますが、このまま登録してもよろしいですか？") . "')) {
                                    return;
                                }
                            }
                            document.forms[0].submit();
                        });
                } else {
                    document.forms[0].submit();
                }
            }
        ";

        $form['gen_message_noEscape'] = "";

        // コピーモードでは見積番号と日付を消す
        if (isset($form['gen_record_copy'])) {
            unset($form['estimate_number']);
            unset($form['estimate_date']);
        }

        // temp_stock に本日時点の有効在庫数（現在庫リストにおける本日付の有効在庫数と一致）を取得
        //  ・製番品目については、フリー製番在庫のみ。
        //  ・全ロケ・ロット合計。Pロケは排除。
        //  ・引当分は将来分も含めて差し引く。
        if (isset($form['estimate_header_id']) && is_numeric($form['estimate_header_id'])) {
            $query = "select coalesce(item_id,-99999) from estimate_detail where estimate_header_id = '{$form['estimate_header_id']}'";
            $itemArr = $gen_db->getArray($query);
            Logic_Stock::createTempStockTable(date('Y-m-d'), $itemArr[0], '', "sum", "sum", true, false, true);
        }

        $query = "select section_id, section_name from section_master order by section_code";
        $option_section = $gen_db->getHtmlOptionArray($query, true);

        $form['gen_editControlArray'] = array(
            array(
                'label' => _g('見積番号'),
                'type' => 'textbox',
                'name' => 'estimate_number',
                'value' => @$form['estimate_number'],
                'size' => '10',
                'helpText_noEscape' => _g('空欄にすると自動的に採番されます。')
            ),
            array(
                'label' => _g('発行日'),
                'type' => 'calendar',
                'name' => 'estimate_date',
                'value' => @$form['estimate_date'],
                'size' => '10',
                'isCalendar' => true,
                'require' => true,
            ),
            array(
                'label' => _g('得意先'),
                'type' => 'dropdown',
                'name' => 'customer_id',
                'value' => @$form['customer_id'],
                'size' => '11',
                'subSize' => '20',
                'dropdownCategory' => 'customer',
                'onChange_noEscape' => 'onCustomerIdChange(false)',
                'helpText_noEscape' => _g('得意先を指定します。指定できるのは取引先マスタで区分を「得意先」に指定した取引先のみです。') . '<br>' .
                _g('この項目の登録は必須ではありません。取引先マスタに登録されていない得意先に対して見積を発行する場合、このドロップダウンは空欄にし、「得意先名」欄に直接入力することもできます。') . '<br>' .
                _g('ただし、この項目を指定しておくことにより、見積を受注に転記したときに得意先も自動転記されます。') . '<br>' .
                _g('取引先マスタ未登録の場合は転記できません。'),
            ),
            array(
                'label' => _g('得意先名'),
                'type' => 'textbox',
                'name' => 'customer_name',
                'value' => @$form['customer_name'],
                'require' => true,
                'ime' => 'on',
                'size' => '20',
                'helpText_noEscape' => _g('見積書に表示する得意先名を指定します。ここで指定する得意先は、マスタに登録されている必要はありません。')
            ),
            array(
                'label' => _g('客先担当者名'),
                'type' => 'textbox',
                'name' => 'person_in_charge',
                'value' => @$form['person_in_charge'],
                'ime' => 'on',
                'size' => '20',
                'helpText_noEscape' => _g('見積書に記載されます。必要なければ空欄にしてください。')
            ),
            array(
                'label' => _g('郵便番号'),
                'type' => 'textbox',
                'name' => 'customer_zip',
                'value' => @$form['customer_zip'],
                'ime' => 'off',
                'size' => '10',
                'helpText_noEscape' => _g('見積書に記載されます。必要なければ空欄にしてください。')
            ),
            array(
                'label' => _g('得意先住所1'),
                'type' => 'textbox',
                'name' => 'customer_address1',
                'value' => @$form['customer_address1'],
                'ime' => 'on',
                'size' => '20',
                'helpText_noEscape' => _g('見積書に記載されます。必要なければ空欄にしてください。')
            ),
            array(
                'label' => _g('得意先住所2'),
                'type' => 'textbox',
                'name' => 'customer_address2',
                'value' => @$form['customer_address2'],
                'ime' => 'on',
                'size' => '20',
                'helpText_noEscape' => _g('見積書に記載されます。必要なければ空欄にしてください。')
            ),
            array(
                'label' => _g('得意先TEL'),
                'type' => 'textbox',
                'name' => 'customer_tel',
                'value' => @$form['customer_tel'],
                'ime' => 'off',
                'size' => '15',
                'helpText_noEscape' => _g('見積書に記載されます。必要なければ空欄にしてください。')
            ),
            array(
                'label' => _g('得意先FAX'),
                'type' => 'textbox',
                'name' => 'customer_fax',
                'value' => @$form['customer_fax'],
                'ime' => 'off',
                'size' => '15',
                'helpText_noEscape' => _g('見積書に記載されます。必要なければ空欄にしてください。')
            ),
            array(
                'label' => _g('担当者(自社)'),
                'type' => 'dropdown',
                'dropdownCategory' => 'worker',
                'name' => 'worker_id',
                'value' => @$form['worker_id'],
                'size' => '11',
                'subSize' => '20',
                'onChange_noEscape' => 'onWorkerChange()',
                'helpText_noEscape' => _g('見積書に記載されます。必要なければ空欄にしてください。') . '<br>' .
                _g('受注登録画面の「自社担当者」へ転記されます。')
            ),
            array(
                'label' => _g('部門(自社)'),
                'type' => 'select',
                'name' => 'section_id',
                'options' => $option_section,
                'selected' => @$form['section_id'],
            ),
            array(
                'label' => _g('ランク'),
                'type' => 'select',
                'name' => 'estimate_rank',
                'options' => Gen_Option::getEstimateRank('options'),
                'selected' => @$form['estimate_rank'],
                'helpText_noEscape' => _g('見積書の重要度を設定できます。')
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
                'label' => _g('件名'),
                'type' => 'textbox',
                'name' => 'subject',
                'value' => @$form['subject'],
                'ime' => 'on',
                'size' => '20',
                'helpText_noEscape' => _g('ここに入力した内容は見積書に記載されます。必要なければ空欄にしてください。') . '<br>' .
                _g('「受注へ転記」の場合、受注登録画面の「受注備考2」へ転記されます。')
            ),
            array(
                'label' => _g('受渡期日'),
                'type' => 'textbox',
                'name' => 'delivery_date',
                'value' => @$form['delivery_date'],
                'ime' => 'on',
                'size' => '20',
                'helpText_noEscape' => _g('ここに入力した内容は見積書に記載されます。必要なければ空欄にしてください。')
            ),
            array(
                'label' => _g('受渡場所'),
                'type' => 'textbox',
                'name' => 'delivery_place',
                'value' => @$form['delivery_place'],
                'ime' => 'on',
                'size' => '20',
                'helpText_noEscape' => _g('ここに入力した内容は見積書に記載されます。必要なければ空欄にしてください。') . '<br>' .
                _g('「受注へ転記」の場合、受注登録画面の「受注備考3」へ転記されます。')
            ),
            array(
                'label' => _g('お支払条件'),
                'type' => 'textbox',
                'name' => 'mode_of_dealing',
                'value' => @$form['mode_of_dealing'],
                'ime' => 'on',
                'size' => '20',
                'helpText_noEscape' => _g('ここに入力した内容は見積書に記載されます。必要なければ空欄にしてください。')
            ),
            array(
                'label' => _g('有効期限'),
                'type' => 'textbox',
                'name' => 'expire_date',
                'value' => @$form['expire_date'],
                'size' => '20',
                'helpText_noEscape' => _g('ここに入力した内容は見積書に記載されます。必要なければ空欄にしてください。')
            ),
            array(
                'label' => _g('見積備考'),
                'type' => 'textbox',
                'name' => 'remarks',
                'value' => @$form['remarks'],
                'size' => '20',
                'helpText_noEscape' => _g('ここに入力した内容は見積書に記載されます。必要なければ空欄にしてください。') . '<br>' .
                _g('「受注へ転記」の場合、受注登録画面の「受注備考1」へ転記されます。')
            ),
            array(
                'label' => _g("取引通貨"),
                'type' => 'textbox',
                'name' => "currency_name",
                'value' => '',
                'size' => '5',
                'readonly' => 'true',
                'helpText_noEscape' => _g("取引先マスタで設定した取引通貨が表示されます。「見積単価」はこの取引通貨で設定してください。"),
            ),
            array(
                'label' => _g('与信限度額'),
                'type' => 'textbox',
                'name' => 'credit_line',
                'value' => '',
                'size' => '9',
                'style' => 'text-align:right',
                'readonly' => true,
                'helpText_noEscape' => _g("この得意先の与信限度額が表示されます。") . '<br>'
                . _g("与信限度額は、取引先マスタで設定することができます。") . '<br>'
                . _g("この得意先に請求先が設定されているときは、請求先の与信限度額が表示されます。"),
            ),
            array(
                'label' => _g('合計金額'),
                'type' => 'textbox',
                'name' => 'total_amount',
                'value' => '',
                'size' => '9',
                'style' => 'text-align:right',
                'readonly' => true,
            ),
            array(
                'label' => _g('合計粗利'),
                'type' => 'textbox',
                'name' => 'total_margin',
                'value' => '',
                'size' => '9',
                'style' => 'text-align:right',
                'readonly' => true,
            ),
            array(
                'label' => _g('合計販売原価'),
                'type' => 'textbox',
                'name' => 'total_basecost',
                'value' => '',
                'size' => '9',
                'style' => 'text-align:right',
                'readonly' => true,
            ),
            array(
                'label' => _g('売掛残高'),
                'type' => 'textbox',
                'name' => 'receivable_balance',
                'value' => '',
                'size' => '9',
                'style' => 'text-align:right',
                'readonly' => true,
                'helpText_noEscape' => _g("この得意先に対する売掛残高（受注ベース）が表示されます。この受注の金額は含みません。") . '<br><br>'
                . _g("この得意先に請求先が設定されているときは、請求先の売掛残高が表示されます。") . '<br><br>'
                . _g("「売掛残高表」を受注ベースで発行したときの売掛残高と同じです（詳しくは売掛残高表画面のページヒントをご覧ください）。"),
            ),
            array(
                'type' => 'literal',
                'denyMove' => true,
            ),

            // ********** List **********
            array(
                'type' => "list",
                'listId' => 'list1', // リストのID。ページ内に複数リストがある場合、ユニークになるようにすること
                'rowCount' => 2, // 1セルに格納するコントロールの数（1セルの行数）
                'keyColumn' => 'estimate_detail_id', // 明細行のキーとなるカラム
                'query' => // Listデータを取得するSQL。 EditBaseで実行され、結果配列が'data'という名前で格納される
                isset($form['estimate_header_id']) && is_numeric($form['estimate_header_id']) ? "
                        select
                            estimate_detail.*
                            ,COALESCE(available_stock_quantity,0) as stock_quantity
                            ,case when foreign_currency_id is null then sale_price else foreign_currency_sale_price end as sale_price
                            ,case when foreign_currency_id is null then base_cost else foreign_currency_base_cost end as base_cost
                            ,quantity * (case when foreign_currency_id is null then sale_price else foreign_currency_sale_price end) as amount
                            ,quantity * (case when foreign_currency_id is null then base_cost else foreign_currency_base_cost end) as calc_base_cost
                            ,(case when foreign_currency_id is null then sale_price else foreign_currency_sale_price end)
                              - (case when foreign_currency_id is null then base_cost else foreign_currency_base_cost end) as gross_margin
                            ,quantity * ((case when foreign_currency_id is null then sale_price else foreign_currency_sale_price end)
                              - (case when foreign_currency_id is null then base_cost else foreign_currency_base_cost end)) as calc_gross_margin
                            ,estimate_detail.remarks as remarks_detail
                            ,estimate_detail.remarks_2 as remarks_detail_2

                        from
                            estimate_detail
                            left join temp_stock on estimate_detail.item_id = temp_stock.item_id

                        where
                            estimate_header_id = '{$form['estimate_header_id']}'
                        order by
                            line_no
                        " : "",
                'controls' => array(
                    array(
                        'label' => _g('品目選択'),
                        'type' => 'dropdown',
                        'name' => 'item_id',
                        'checkColumn' => 'item_name',
                        'dropdownCategory' => 'item_received_nosubtext',
                        'onChange_noEscape' => 'onItemIdChange([gen_line_no])',
                        'size' => '9',
                        'helpText_noEscape' => _g("見積品目を選択します。ドロップダウンに表示されるのは品目マスタで「受注対象」になっている品目のみです。") . "<br>" . _g("品目マスタにない品目（例：消費税・値引き）を見積に含める場合、ここから選択せず、右の列に品目コードと品目名を直接入力してください。（その場合、在庫数や販売原単価は表示されません。）"),
                    ),
                    array(
                        'type' => 'literal',
                    ),
                    array(
                        'label' => _g('品目コード'),
                        'type' => 'textbox',
                        'name' => 'item_code',
                        'size' => '16',
                        'width' => '16',
                        'helpText_noEscape' => _g("マスタにある品目を登録する場合、左の「品目選択」で品目を選択してください。") . "<br><br>" . _g("マスタにない品目を登録することもできます。その場合は、直接品目コードを入力してください。（直接入力の場合、在庫数や販売原単価は表示されません。）省略も可能です。"),
                    ),
                    array(
                        'label' => _g('品目名'),
                        'type' => 'textbox',
                        'name' => 'item_name',
                        'require' => true,
                        'size' => '16',
                        'helpText_noEscape' => _g("マスタにある品目を登録する場合、左の「品目選択」で品目を選択してください。") . "<br><br>" . _g("マスタにない品目を登録することもできます。その場合は、直接品目名を入力してください。（直接入力の場合、在庫数や販売原単価は表示されません。）"),
                        'placeholder' => _g('品目名'),
                    ),
                    array(
                        'label' => _g('数量'),
                        'type' => 'textbox',
                        'name' => 'quantity',
                        'ime' => 'off',
                        'size' => '6',
                        'style' => "text-align:right",
                        'require' => true,
                        'onChange_noEscape' => 'updatePrice([gen_line_no],true)',
                        'helpText_noEscape' => _g("見積数量を入力してください。"),
                    ),
                    array(
                        'label' => _g('有効在庫数'), // 本日時点の有効在庫。取得条件はAjaxItemParam参照
                        'type' => 'div',
                        'name' => 'stock_quantity',
                        'size' => '6',
                        'style' => "text-align:right",
                        'numberFormat' => '', // 桁区切り
                        'helpText_noEscape' => _g('本日時点の有効在庫数です。サプライヤーロケ分は含みません。'),
                    ),
                    array(
                        'label' => _g('単位'),
                        'type' => 'textbox',
                        'name' => 'measure',
                        'size' => '6',
                        'helpText_noEscape' => _g("単位（個・枚など）を入力してください。見積書に表示されます。必要ない場合は空欄にしてください。"),
                    ),
                    array(
                        'label' => _g('課税区分'),
                        'type' => 'select',
                        'name' => 'tax_class',
                        'options' => array('0' => _g('課税'), '1' => _g('非課税')),
                        'helpText_noEscape' => _g('「課税」を選択すると「金額 × 消費税率マスタの税率」で税額が計算され、見積書に反映されます。「非課税」を選択すると税額が計算されません。') . "<br><br>" . _g('非課税品目のほか、内税品目の場合も「非課税」を選択してください。') . "<br>" . _g('デフォルトでは品目マスタ「課税区分」が表示されます。'),
                    ),
                    array(
                        'label' => _g('見積単価'),
                        'type' => 'textbox',
                        'name' => 'sale_price',
                        'ime' => 'off',
                        'size' => '8',
                        'style' => 'text-align:right',
                        'require' => true,
                        'onChange_noEscape' => 'onPriceChange([gen_line_no])',
                        'helpText_noEscape' => _g("見積単価を入力してください。") . "<br><br>" . _g("見積書で外税表記したい場合は税抜単価を入力し、課税区分を「課税」にしてください。") . "<br>" . _g("内税表記したい場合は税込単価を入力し、課税区分を「非課税」にしてください。") . "<br>" . _g("非課税品目の場合は単価を入力し、課税区分を「非課税」にしてください。"),
                    ),
                    array(
                        'label' => _g('金額'),
                        'type' => 'div',
                        'name' => 'amount',
                        'size' => '8',
                        'numberFormat' => '', // 桁区切り
                        'style' => "text-align:right",
                        'helpText_noEscape' => _g('「見積単価  × 数量」で計算されます。'),
                    ),
                    array(
                        'label' => _g('販売原単価'),
                        'type' => 'textbox',
                        'name' => 'base_cost',
                        'ime' => 'off',
                        'size' => '8',
                        'style' => "text-align:right",
                        'onChange_noEscape' => 'onBaseCostChange([gen_line_no])',
                        'helpText_noEscape' => _g("この製品の販売原価（単価）を指定します。この金額は見積書には記載されません。") . '<br><br>' .
                        _g("「品目選択」で品目を選択した場合は、自動的に計算して表示されます。") . '<br>' .
                        _g("販売原価は以下のように計算されます。") . '<br><br>' .
                        "●<b>" . _g("製造品（標準手配先が「内製」の品目）") . "：</b>" . _g("品目マスタ「標準加工時間(分)」 * 品目マスタ「工賃(\/分)」") . '<br><br>' .
                        "●<b>" . _g("購入品（標準手配先が「内製」以外の品目）") . "：</b>" . _g("品目マスタの在庫評価単価") . '<br><br>' .
                        _g("標準手配先が「内製」「外注(支給あり)」で、なおかつ構成表マスタで子品目が登録されている場合、見積品目を構成展開し、子品目の販売原価が合計されます。") . '<br><br>' .
                        _g("外貨得意先の場合、外貨ベースとなります。"),
                    ),
                    array(
                        'label' => _g('販売原価'),
                        'type' => 'div',
                        'name' => 'calc_base_cost',
                        'size' => '8',
                        'numberFormat' => '', // 桁区切り
                        'style' => "text-align:right",
                        'helpText_noEscape' => _g('「販売原単価  × 数量」で計算されます。'),
                    ),
                    array(
                        'label' => _g('単品粗利'),
                        'type' => 'div',
                        'name' => 'gross_margin',
                        'size' => '8',
                        'style' => "text-align:right",
                        'numberFormat' => '', // 桁区切り
                        'helpText_noEscape' => _g('「見積単価 - 販売原単価」で計算されます。この金額は見積書には記載されません。'),
                    ),
                    array(
                        'label' => _g('粗利'),
                        'type' => 'div',
                        'name' => 'calc_gross_margin',
                        'style' => "text-align:right",
                        'numberFormat' => '', // 桁区切り
                        'helpText_noEscape' => _g('「単品粗利 × 数量」で計算されます。この金額は見積書には記載されません。'),
                    ),
                    array(
                        'label' => _g('見積明細備考1'),
                        'type' => 'textbox',
                        'name' => 'remarks_detail',
                        'size' => '12',
                        'focusZoom' => array('left', 400, 25),     // フォーカス時のサイズ拡張(方向,width,height)
                        'helpText_noEscape' => _g("見積書帳票に表示されます。"),
                    ),
                    array(
                        'label' => _g('見積明細備考2'),
                        'type' => 'textbox',
                        'name' => 'remarks_detail_2',
                        'size' => '12',
                        'focusZoom' => array('left', 400, 25),
                        'helpText_noEscape' => _g("見積書帳票に表示されませんが、帳票テンプレートを変更することにより表示されるようにすることができます。"),
                    ),
                ),
            ),
        );
    }

}
