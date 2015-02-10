<?php

require_once("Model.class.php");

class Master_Customer_Edit extends Base_EditBase
{

    function convert($converter, &$form)
    {
        $converter->nullBlankToValue("precision", 0);
        $converter->nullBlankToValue("bill_pattern", 1);
    }

    function setQueryParam(&$form)
    {
        $this->keyColumn = 'customer_id';
        $this->selectQuery = "
            select
                -- end_customerの置き換えを行っているので * は使えない
                customer_master.customer_id
                ,customer_master.customer_no
                ,customer_master.customer_name
                ,customer_master.classification
                ,case when end_customer then 'true' else '' end as end_customer
                ,customer_master.zip
                ,customer_master.address1
                ,customer_master.address2
                ,customer_master.tel
                ,customer_master.fax
                ,customer_master.e_mail
                ,customer_master.person_in_charge
                ,customer_master.customer_group_id_1
                ,customer_master.customer_group_id_2
                ,customer_master.customer_group_id_3
                ,customer_master.remarks
                ,customer_master.remarks_2
                ,customer_master.remarks_3
                ,customer_master.remarks_4
                ,customer_master.remarks_5
                ,customer_master.rounding
                ,customer_master.precision
                ,customer_master.inspection_lead_time
                ,customer_master.currency_id
                ,customer_master.report_language
                ,customer_master.dropdown_flag
                ,customer_master.bill_pattern
                ,customer_master.monthly_limit_date
                ,customer_master.tax_category
                ,customer_master.price_percent
                ,customer_master.price_percent_group_id
                ,customer_master.opening_balance
                ,customer_master.opening_date
                ,customer_master.credit_line
                ,customer_master.receivable_cycle1
                ,customer_master.receivable_cycle2_month
                ,customer_master.receivable_cycle2_day
                ,customer_master.default_lead_time
                ,customer_master.delivery_port
                ,customer_master.payment_opening_balance
                ,customer_master.payment_opening_date
                ,customer_master.payment_cycle1
                ,customer_master.payment_cycle2_month
                ,customer_master.payment_cycle2_day
                ,customer_master.bill_customer_id
                ,customer_master.template_delivery
                ,customer_master.template_bill
                ,customer_master.template_partner_order
                ,customer_master.template_subcontract
                ,case when classification = 1 then last_order_date else last_received_date end as last_trade_date

                ,coalesce(customer_master.record_update_date, customer_master.record_create_date) as gen_last_update
                ,coalesce(customer_master.record_updater, customer_master.record_creator) as gen_last_updater
            from
                customer_master
                left join (select customer_id as cid, max(received_date) as last_received_date from received_header group by customer_id) as t_rec on customer_master.customer_id = t_rec.cid
                left join (select partner_id as cid, max(order_date) as last_order_date from order_header group by partner_id) as t_ord on customer_master.customer_id = t_ord.cid
            [Where]
            	-- for excel
            	order by customer_master.customer_no
        ";
    }

    function setViewParam(&$form)
    {
        global $gen_db;

        $this->modelName = "Master_Customer_Model";

        $form['gen_pageTitle'] = _g('取引先マスタ');
        $form['gen_entryAction'] = "Master_Customer_Entry" . (@$form['gen_overlapFrame'] == "true" ? "&gen_overlapFrame=true" : "");
        $form['gen_listAction'] = "Master_Customer_List";
        $form['gen_onLoad_noEscape'] = "onClassChange();";
        $form['gen_beforeEntryScript_noEscape'] = "onEntry()";
        $form['gen_pageHelp'] = _g("得意先");

        $form['gen_javascript_noEscape'] = "

            // 登録前処理（請求条件チェック）
            function onEntry() {
                // 得意先以外・新規登録・非表示のときはノーチェック
                if ($('#classification').val()!='0') {
                    document.forms[0].submit();
                    return;
                }

                gen.edit.submitDisabled();

                var p = new Object();
                p.customer_id = '" . (isset($form['customer_id']) && !isset($form['gen_record_copy']) ? $form['customer_id'] : "null") . "';
                p.rounding = $('#rounding').val();
                p.precision = $('#precision').val();
                p.tax_category = $('#tax_category').val();
                p.bill_pattern = $('#bill_pattern').val();

                gen.ajax.connect('Master_Customer_AjaxBillAlarm', p,
                    function(j) {
                        if (j.alert == '1') {
                            if (!confirm('" . _g('未請求納品データの請求条件と登録する請求条件が異なります。このまま登録してもよろしいですか？') . "')) {
                                gen.edit.submitEnabled('" . h($form['gen_readonly']) . "');
                                return;
                            }
                        }
                        document.forms[0].submit();
                    });
            }

            function onClassChange() {
                var cl = $('#classification').val();
                var s = '';
                gen.ui.alterDisabled($('#default_lead_time'), cl!='1');
            }

            function onZipChange() {
                var val = $('#address1').val();
                if (val != '') {
                    return;
                }
                var adr = gen.zip.toAddress($('#zip').val(),
                function(adr) {
                    if (adr) {
                        $('#address1').val(adr);
                    }
                });
            }
        ";

        $form['gen_message_noEscape'] = "";
        // 非表示取引先メッセージ
        if (isset($form['end_customer']) && $form['end_customer'] == 'true') {
            $form['gen_message_noEscape'] = "<font color=\"red\"><b>" . _g("この取引先は非表示です。") . "</b></font>";
        }

        // 一括編集（multiEdit）対応
        if (isset($form['customer_id']))
            $customerIdArr = explode(",", $form['customer_id']);

        // 構成表マスタに含まれている場合、管理区分の切り替えを禁止する
        $currencyIdReadonly = false;
        if (isset($form['customer_id']) && !isset($form['gen_record_copy'])) {
            foreach($customerIdArr as $customerId) {
                if (Logic_Order::existOrder($customerId)) {
                    $form['gen_message_noEscape'] .= "<br><font color=\"blue\">" . _g("この取引先はオーダーが登録されているため、取引通貨を変更することはできません。") . "</font>";
                    $currencyIdReadonly = true;
                    break;
                }
                if (Logic_Received::existReceived($customerId)) {
                    $form['gen_message_noEscape'] .= "<br><font color=\"blue\">" . _g("この取引先は受注が登録されているため、取引通貨を変更することはできません。") . "</font>";
                    $currencyIdReadonly = true;
                    break;
                }
            }
        }

        // 請求先として指定されている場合、請求先の登録を禁止する
        $billCustomerReadonly = false;
        if (isset($form['customer_id']) && !isset($form['gen_record_copy'])) {
            $query = "select customer_id from customer_master where bill_customer_id in ({$form['customer_id']})";  // multiEdit対応
            if ($gen_db->existRecord($query))
                $billCustomerReadonly = true;
        }

        // 得意先に対する請求書が発行されている時は、売掛基準の変更を禁止する。
        $isAccount = false;
        if (isset($form['customer_id']) && !isset($form['gen_record_copy'])) {
            $idArr = explode(",", $form['customer_id']);
            foreach($idArr as $custId) {
                if (Logic_Bill::existBill($custId)) {
                    $form['gen_message_noEscape'] .= "<br><font color=\"blue\">" . _g("この取引先は請求書が発行されているため、売掛初期値を変更することはできません。") . "</font>";
                    $isAccount = true;
                }
            }
        }

        if ($form['gen_message_noEscape'] != '')
            $form['gen_message_noEscape'] .= "<br><br>";

        // セレクタ選択肢
        $query = "select price_percent_group_id, price_percent_group_name from price_percent_group_master order by price_percent_group_name";
        $option_price_percent_group = $gen_db->getHtmlOptionArray($query, true);

        $query = "select customer_group_id, customer_group_name from customer_group_master order by customer_group_code";
        $option_customer_group_id = $gen_db->getHtmlOptionArray($query, true);

        // 帳票テンプレート
        for ($i=0; $i<=3; $i++) {
            switch($i) {
                case 0: $cat = "Delivery"; break;
                case 1: $cat = "Bill"; break;
                case 2: $cat = "PartnerOrder"; break;
                case 3: $cat = "PartnerSubcontract"; break;
            }
            $info = Gen_PDF::getTemplateInfo($cat);
            $templates[$i] = array("" => "(" . _g("標準") . ")");
            foreach($info[2] as $infoOne) {
                $templates[$i][$infoOne['file']] = $infoOne['file'] . ($infoOne['comment'] == "" ? "" : " (" . $infoOne['comment'] . ")");
            }
        }

        $keyCurrency = $gen_db->queryOneValue("select key_currency from company_master");

        $form['gen_editControlArray'] = array(
            array(
                'label' => _g('取引先コード'),
                'type' => 'textbox',
                'name' => 'customer_no',
                'value' => @$form['customer_no'],
                'require' => true,
                'ime' => 'off',
                'readonly' => (@$form['gen_overlapFrame'] == "true" && !isset($form['gen_dropdownNewRecordButton'])), // 拡張DDからのジャンプ登録の場合、コード変更されると動作不具合。ただし拡張DD内新規ボタンを除く
                'size' => '12'
            ),
            array(
                'label' => _g('取引先名'),
                'type' => 'textbox',
                'name' => 'customer_name',
                'value' => @$form['customer_name'],
                'require' => true,
                'ime' => 'on',
                'size' => '20', // 各Edit画面の拡張DDのsubSizeがだいたい20になっている
            ),
            array(
                'label' => _g('区分'),
                'type' => 'select',
                'name' => 'classification',
                'options' => array(0 => _g('得意先'), 1 => _g('サプライヤー'), 2 => _g('発送先')),
                'selected' => @$form['classification'],
                'helpText_noEscape' => _g('この取引先が受注先である場合は「得意先」、発注先である場合は「サプライヤー」を指定してください。発送先を登録する場合（受注先と発送先が異なる場合）は「発送先」を指定してください。'),
                'onChange_noEscape' => 'onClassChange()',
            ),
            array(
                'type' => 'literal',
            ),
            array(
                'label' => _g('最終取引日'),
                'type' => 'textbox',
                'name' => 'last_trade_date',
                'value' => (isset($form['customer_id']) && !isset($form['gen_record_copy']) ? @$form['last_trade_date'] : ''),
                'readonly' => true,
                'size' => '10',
                'helpText_noEscape' => _g('得意先・発送先の場合は最後の受注日、サプライヤーの場合は最後の注文日が表示されます。'),
            ),
            array(
                'label' => _g('非表示'),
                'type' => 'checkbox',
                'name' => 'end_customer',
                'onvalue' => 'true', // trueのときの値。デフォルト値ではない
                'value' => @$form['end_customer'],
                'helpText_noEscape' => _g('このチェックをオンにすると、各画面の取引先（得意先・サプライヤー）選択ドロップダウンに表示されなくなります（コードを手入力することはできます）。'),
            ),
            array(
                'label' => _g('取引先グループ1'),
                'type' => 'select',
                'name' => 'customer_group_id_1',
                'options' => $option_customer_group_id,
                'selected' => @$form['customer_group_id_1'],
            ),
            array(
                'label' => _g('取引先グループ2'),
                'type' => 'select',
                'name' => 'customer_group_id_2',
                'options' => $option_customer_group_id,
                'selected' => @$form['customer_group_id_2'],
            ),
            array(
                'label' => _g('取引先グループ3'),
                'type' => 'select',
                'name' => 'customer_group_id_3',
                'options' => $option_customer_group_id,
                'selected' => @$form['customer_group_id_3'],
            ),
            array(
                'type' => 'literal',
            ),
            array(
                'type' => 'literal',
            ),
            array(
                'type' => 'literal',
            ),
            array(
                'label' => _g('郵便番号'),
                'type' => 'textbox',
                'name' => 'zip',
                'value' => @$form['zip'],
                'ime' => 'off',
                'size' => '10',
                'onChange_noEscape' => 'onZipChange()',
                'helpText_noEscape' => _g("郵便番号を入力すると住所が自動入力されます。")
            ),
            array(
                'type' => 'literal',
            ),
            array(
                'label' => _g('住所1'),
                'type' => 'textbox',
                'name' => 'address1',
                'value' => @$form['address1'],
                'ime' => 'on',
                'size' => '25'
            ),
            array(
                'label' => _g('住所2'),
                'type' => 'textbox',
                'name' => 'address2',
                'value' => @$form['address2'],
                'ime' => 'on',
                'size' => '20'
            ),
            array(
                'label' => _g('TEL'),
                'type' => 'textbox',
                'name' => 'tel',
                'value' => @$form['tel'],
                'ime' => 'off',
                'size' => '10'
            ),
            array(
                'label' => _g('FAX'),
                'type' => 'textbox',
                'name' => 'fax',
                'value' => @$form['fax'],
                'ime' => 'off',
                'size' => '10'
            ),
            array(
                'label' => _g('メールアドレス'),
                'type' => 'textbox',
                'name' => 'e_mail',
                'value' => @$form['e_mail'],
                'ime' => 'off',
                'size' => '20'
            ),
            array(
                'label' => _g('担当者'),
                'type' => 'textbox',
                'name' => 'person_in_charge',
                'value' => @$form['person_in_charge'],
                'ime' => 'on',
                'size' => '15'
            ),
            array(
                'label' => _g('取引先備考1'),
                'type' => 'textbox',
                'name' => 'remarks',
                'value' => @$form['remarks'],
                'ime' => 'on',
                'size' => '25'
            ),
            array(
                'label' => _g('取引先備考2'),
                'type' => 'textbox',
                'name' => 'remarks_2',
                'value' => @$form['remarks_2'],
                'ime' => 'on',
                'size' => '25'
            ),
            array(
                'label' => _g('取引先備考3'),
                'type' => 'textbox',
                'name' => 'remarks_3',
                'value' => @$form['remarks_3'],
                'ime' => 'on',
                'size' => '25'
            ),
            array(
                'label' => _g('取引先備考4'),
                'type' => 'textbox',
                'name' => 'remarks_4',
                'value' => @$form['remarks_4'],
                'ime' => 'on',
                'size' => '25'
            ),
            array(
                'label' => _g('取引先備考5'),
                'type' => 'textbox',
                'name' => 'remarks_5',
                'value' => @$form['remarks_5'],
                'ime' => 'on',
                'size' => '25'
            ),
            array(
                'type' => 'literal',
            ),
            array(
                'type' => 'literal',
            ),
            array(
                'type' => 'literal',
            ),
            array(
                'label' => _g('端数処理'),
                'type' => 'select',
                'name' => 'rounding',
                'options' => array('round' => _g('四捨五入'), 'floor' => _g('切捨'), 'ceil' => _g('切上')),
                'selected' => @$form['rounding'],
                'helpText_noEscape' => "<b>●" . _g("得意先") . "：</b><br>" . _g("納品登録・請求書発行において、ここで指定した方法で金額の端数処理（丸め）が行われます。") . '<br>' .
                _g("また、入金登録画面で外貨金額を入力した場合の基軸通貨金額に対しても処理が行われます。") . '<br>' .
                _g("納品額・売上金額は納品明細行ごと、消費税額は「税計算単位」で指定した単位ごとに端数処理されます。") . '<br><br>' .
                "<b>●" . _g("サプライヤー") . "：</b><br>" . _g("注文登録・外製指示登録・注文受入登録・外製受入登録・支払登録において、ここで指定した方法で金額の端数処理（丸め）が行われます。") . "<br>" .
                _g("受入は受入明細単位ごとに端数処理されます。") . '<br><br>' .
                _g("※この項目を変更しても、既存のデータには反映されません。既存データに変更を反映するには、該当データを登録画面で再登録してください。"),
            ),
            array(
                'label' => _g('金額の小数点以下桁数'),
                'type' => 'textbox',
                'name' => 'precision',
                'value' => @$form['precision'],
                'ime' => 'off',
                'size' => '5',
                'require' => true,
                'helpText_noEscape' => _g('「端数処理」を適用して金額の丸めをおこなうとき、小数点以下何桁までで丸めるかを指定します。') .
                "<br><br>" . _g("※リスト画面ではリストの表示設定が優先されるため、この桁数で表示されない場合があります。リストの該当項目の列見出し上で右クリックし、表示桁数を設定してください。") .
                "<br><br>" . _g("※また帳票においては、帳票テンプレートの設定が優先されます。該当するテンプレートの「セルの書式設定」で表示桁数を調整してください。") .
                "<br><br>" . _g("※この項目を変更しても、既存のデータには反映されません。既存データに変更を反映するには、該当データを登録画面で再登録してください。"),
            ),
            array(
                'label' => _g('取引通貨'),
                'type' => 'select',
                'name' => 'currency_id',
                'options' => $gen_db->getHtmlOptionArray("select null as currency_id, '{$keyCurrency}' as currency_name, '' as for_order union all select currency_id, currency_name, currency_name as for_order from currency_master order by for_order", false),
                'selected' => @$form['currency_id'],
                'readonly' => $currencyIdReadonly,
                'helpText_noEscape' => _g('この取引先との取引に使用する取引通貨を指定します。通貨マスタで登録した取引通貨が選択肢に出てきます。') . "<br>" . _g('品目マスタの単価や受注・納品・注文書の金額は、ここで指定した取引通貨で設定することになります。'),
            ),
            array(
                'label' => _g('帳票言語区分'),
                'type' => 'select',
                'name' => 'report_language',
                'options' => array('0' => _g('日本語'), '1' => _g('英語')),
                'selected' => @$form['report_language'],
                'helpText_noEscape' => _g('この取引先に発行する帳票（得意先：納品書/請求書、サプライヤー：注文書）の言語を指定します。ただしこの指定が有効になるのは、帳票テンプレート設定画面で「日英切替」と表示されているテンプレートを選択した場合のみです。それ以外のテンプレートでは常に日本語で表示されます。'),
            ),
            array(
                'label' => _g('検収リードタイム'),
                'type' => 'textbox',
                'name' => 'inspection_lead_time',
                'value' => @$form['inspection_lead_time'],
                'ime' => 'off',
                'size' => '8',
                'helpText_noEscape' => _g("納品日・受入日から検収日までのリードタイムを指定します。") .
                        "<br>" . _g("この項目を指定すると、各登録画面において検収日が自動設定されるようになります。取引先ごとに検収までの標準日数が決まっている場合、この項目を設定しておくと便利です。検収日を登録しない場合はこの項目を空欄にしてください。") .
                        "<br><br><b>●" . _g("得意先") . "：</b><br>" . _g("納品登録画面において、「納品日 + 検収リードタイム」が検収日として自動設定されます。") .
                        "<br><br><b>●" . _g("サプライヤー") . "：</b><br>" . _g("注文受入・外製受入登録画面において、「受入日 + 検収リードタイム」が検収日として自動設定されます。")
            ),
            // 締日グループは、以前は得意先だけに適用されていたが、12iの途中からサプライヤーにも適用されるようになった。
            // ag.cgi?page=ProjectDocView&pid=1574&did=218569
            array(
                'label' => _g('締日グループ'),
                'type' => 'select',
                'name' => 'monthly_limit_date',
                'options' => Gen_Option::getMonthlyLimit('options'),
                'selected' => @$form['monthly_limit_date'],
                'helpText_noEscape' => _g("請求（得意先の場合）や支払（サプライヤーの場合）の締日を設定します。") .
                        "<br><br><b>●" . _g("得意先") . "：</b><br>" . _g("請求書発行時に、このグループで対象取引先を絞りこむことができます。（絞込みの条件になるだけです。締日が過ぎた取引先の請求書が自動的に出てくるというわけではありません。）") .
                        "<br><br><b>●" . _g("サプライヤー") . "：</b><br>" . _g("注文受入・外製受入登録画面の「支払予定日」のデフォルト値を決定するのに使用されます。詳細はこの画面の「支払サイクル」のチップヘルプをご覧ください。")
            ),
            array(
                'type' => 'literal',
            ),
            array(
                'type' => 'literal',
            ),
            array(
                'label' => _g("得意先のみ"),
                'type' => 'section',
            ),
            array(
                'type' => 'literal',
            ),
            array(
                'label' => _g('税計算単位'),
                'type' => 'select',
                'name' => 'tax_category',
                'options' => array('0' => _g('請求書単位'), '1' => _g('納品書単位'), '2' => _g('納品明細単位')),
                'selected' => @$form['tax_category'],
                'helpText_noEscape' => _g("消費税を計算する単位を設定します。") .
                "<br>●" . _g("請求書単位：") .
                "<br>　　<b>" . _g("税計算方法") . _g("：") . "</b> " . _g("請求書全体の売上合計に対し税率をかけて消費税を計算する。") .
                "<br>　　<b>" . _g("請求書") . _g("：") . "</b> " . _g("合計欄に消費税を表示する。明細欄には表示しない。") .
                "<br>　　<b>" . _g("納品書") . _g("：") . "</b> " . _g("消費税を表示しない") .
                "<br><br>●" . _g("納品書単位") . _g("：") . "" .
                "<br>　　<b>" . _g("税計算方法") . _g("：") . "</b> " . _g("納品書の売上合計に対し税率をかけて消費税を計算する。") .
                "<br>　　<b>" . _g("請求書") . _g("：") . "</b> " . _g("合計欄に消費税を表示する。明細欄には表示しない。") .
                "<br>　　<b>" . _g("納品書") . _g("：") . "</b> " . _g("合計欄に消費税を表示する（「納品書 + 請求書 + 受領書」タイプのみ）。") .
                "<br><br>●" . _g("納品明細単位") . _g("：") . "" .
                "<br>　　<b>" . _g("税計算方法") . _g("：") . "</b> " . _g("納品明細ごとに消費税を計算する。") .
                "<br>　　<b>" . _g("請求書") . _g("：") . "</b> " . _g("合計欄と明細欄に消費税を表示する。") .
                "<br>　　<b>" . _g("納品書") . _g("：") . "</b> " . _g("合計欄に消費税を表示する（「納品書 + 請求書 + 受領書」タイプのみ）。"),
            ),
            array(
                'label' => _g('請求先'),
                'type' => 'dropdown',
                'name' => 'bill_customer_id',
                'value' => @$form['bill_customer_id'],
                'size' => '8',
                'subSize' => '15',
                // 15iでは当初、請求先に指定できるのを締め請求の取引先に限定していたが、それでは不便との指摘があり、
                // 13iと同じく都度請求の取引先も指定できるようにした。
                'dropdownCategory' => 'customer',
                //'dropdownCategory' => 'customer_bill_close',
                // 自分自身が請求先の選択肢として出てこないようにする
                'dropdownParam' => (isset($form['customer_id']) && $form['customer_id'] != "" && !isset($form['gen_record_copy']) ? $form['customer_id'] : ""),
                'helpText_noEscape' => _g("受注（販売）先と請求先が異なる場合のみ指定してください。") . "<br>"
                    . _g("請求先を指定すると、この取引先に対する売上は指定された請求先に請求されます。") . "<br><br>"
                    . _g("この取引先と請求先の設定が異なる場合、「掛率」「掛率グループ」だけはこの取引先の設定が優先されますが、それ以外の項目（請求パターン、締日、売掛残高、回収サイクル等）は請求先の設定が優先されます。"),
            ),
            array(
                'label' => _g('掛率（％）'),
                'type' => 'textbox',
                'name' => 'price_percent',
                'value' => @$form['price_percent'],
                'helpText_noEscape' => _g("販売単価に対する掛率を設定します。") . '<br>'
                    . _g("品目マスタ「標準販売単価」にこの掛率をかけた金額が、受注登録画面でのデフォルトの受注単価になります。") . '<br>'
                    . _g("ただし得意先販売価格マスタが登録されている場合は、その価格が優先して使用されます。") . '<br><br>'
                    . _g("●販売価格決定の優先順位（高い順）") . '<br>'
                    . _g("(1) 得意先販売価格マスタ") . '<br>'
                    . _g("(2) 取引先マスタ「掛率」") . '<br>'
                    . _g("(3) 掛率グループマスタ「掛率」") . '<br>'
                    . _g("(4) 品目マスタ「標準販売単価」"),
                'ime' => 'off',
                'size' => '8',
            ),
            array(
                'label' => _g('掛率グループ'),
                'type' => 'select',
                'name' => 'price_percent_group_id',
                'options' => $option_price_percent_group,
                'selected' => @$form['price_percent_group_id'],
                'helpText_noEscape' => _g("この項目を設定すると、受注登録画面でのデフォルトの受注単価に対し、掛率グループマスタの掛率が適用されます。") . '<br><br>'
                . _g("ただし得意先販売価格マスタや、この画面の「掛率」が設定されている場合、そちらが優先されます。") . '<br><br>'
                . _g("販売価格決定の詳細については、この画面の「掛率」のチップヘルプをご覧ください。"),
            ),
            array(
                'label' => _g('請求パターン'),
                'type' => 'select',
                'name' => 'bill_pattern',
                'options' => Gen_Option::getBillPattern(($isAccount && isset($form['bill_pattern']) && !isset($form['gen_record_copy']) ? "options-{$form['bill_pattern']}" : 'options')),
                'selected' => @$form['bill_pattern'],
                'helpText_noEscape' => '<b>●' . _g("締め（残高表示なし）") . '</b>' . _g("：") . _g("複数の納品書番号の売上を期間で締めて請求書発行します。") .
                _g("請求書に請求残高が表示されず、締日以前の日付でも入金登録ができます。") . '<br><br>'
                . '<b>●' . _g("締め（残高表示あり）") . '</b>：' . _g("複数の納品書番号の売上を期間で締めて請求書発行します。") .
                _g("請求書に請求残高が表示され、締日以前の日付では入金登録ができません。") . '<br><br>'
                . '<b>●' . _g("都度") . '</b>' . _g("：") . _g("納品書番号ごとに請求書発行します。") .
                _g("請求書に請求残高が表示されず、請求書ごとに入金消込ができます。")
            ),
            array(
                'label' => _g('与信限度額'),
                'type' => 'textbox',
                'name' => 'credit_line',
                'value' => @$form['credit_line'],
                'helpText_noEscape' => _g("この取引先に対する与信限度額を入力します。省略可能です。") . '<br><br>'
                . _g("入力すると受注登録の画面に表示されます。また、受注登録時に受注額 + 売掛残高が限度額をオーバーしている場合に警告が表示されます。（CSV登録の場合は表示されません。）") . '<br><br>'
                . _g("「取引通貨」の項目で指定された通貨の金額を入力してください。"),
                'ime' => 'off',
                'size' => '8',
            ),
            array(
                'label' => _g('売掛残高初期値'),
                'type' => 'textbox',
                'name' => 'opening_balance',
                'value' => @$form['opening_balance'],
                'helpText_noEscape' => _g("締め請求の請求書における、繰越残高の初期値です。") . '<br><br>'
                . _g("請求先が指定されている場合は無意味です（請求先の残高が使用されます）。") . '<br><br>'
                . _g("「取引通貨」の項目で指定された通貨の金額を入力してください。"),
                'ime' => 'off',
                'size' => '8',
                'readonly' => $isAccount,
            ),
            array(
                'label' => _g('売掛基準日'),
                'type' => 'calendar',
                'name' => 'opening_date',
                'value' => @$form['opening_date'],
                'helpText_noEscape' => _g("「売掛残高初期値」を適用する日付です。"),
                'size' => '8',
                'ime' => 'off',
                'isCalendar' => true,
                'readonly' => $isAccount,
            ),
            array(
                'label' => _g('回収サイクル1（x日後）'),
                'type' => 'textbox',
                'name' => 'receivable_cycle1',
                'value' => @$form['receivable_cycle1'],
                'ime' => 'off',
                'size' => '8',
                'afterLabel_noEscape' => _g('日後に回収'),
                'helpText_noEscape' => _g("請求書発行時に、ここで設定したサイクルに基づいて回収予定日（入金予定日）が決定されます。") . '<br>'
                . _g("各請求の回収予定日は請求書リスト画面で確認でき、「回収予定表」に反映されます。") . '<br><br>'
                . _g("月ごとの日数の違いの影響を受けないよう、1ヶ月を30日として計算されます。例えば3/20締めで回収サイクル30日の場合、回収予定日は4/20となります（4/19ではありません）。") . '<br>'
                . _g("回収サイクル1,2とも登録を省略した場合、請求書の回収予定日が空欄になり、回収予定表にデータが反映されなくなります。"),
            ),
            array(
                'label' => _g('回収サイクル2（xヶ月後）'),
                'type' => 'textbox',
                'name' => 'receivable_cycle2_month',
                'value' => @$form['receivable_cycle2_month'],
                'ime' => 'off',
                'size' => '8',
                'afterLabel_noEscape' => _g('ヶ月後の'),
                'helpText_noEscape' => _g("「回収サイクル2（x日）」と組み合わせて、「xヶ月後のx日」という形で指定します。") . '<br>'
                . _g("回収サイクル1が指定されている場合、この項目は無視されます。") . '<br><br>'
                . _g("回収サイクルの意味については、回収サイクル1のチップヘルプをご覧ください。"),
            ),
            array(
                'type' => 'literal',
            ),
            array(
                'label' => _g('回収サイクル2（x日）'),
                'type' => 'textbox',
                'name' => 'receivable_cycle2_day',
                'value' => @$form['receivable_cycle2_day'],
                'helpText_noEscape' => _g("「回収サイクル2（xヶ月後）」と組み合わせて、「xヶ月後のx日」という形で指定します。「31」は月末日をあらわします。") . '<br>'
                . _g("回収サイクル1が指定されている場合、この項目は無視されます。") . '<br><br>'
                . _g("回収サイクルの意味については、回収サイクル1のチップヘルプをご覧ください。"),
                'ime' => 'off',
                'size' => '8',
                'afterLabel_noEscape' => _g('日に回収'),
            ),
            array(
                'label' => _g('帳票（納品書）'),
                'type' => 'select',
                'name' => 'template_delivery',
                'options' => $templates[0],
                'selected' => @$form['template_delivery'],
                'helpText_noEscape' => _g("納品書の帳票テンプレートを指定します。この指定は帳票テンプレート設定ダイアログの指定より優先されます。") .
                _g("「標準」が選択されている場合は、帳票テンプレート設定ダイアログで指定された帳票が使用されます。"),
            ),
            array(
                'label' => _g('帳票（請求書）'),
                'type' => 'select',
                'name' => 'template_bill',
                'options' => $templates[1],
                'selected' => @$form['template_bill'],
                'helpText_noEscape' => _g("請求書の帳票テンプレートを指定します。この指定は帳票テンプレート設定ダイアログの指定より優先されます。") .
                _g("「標準」が選択されている場合は、帳票テンプレート設定ダイアログで指定された帳票が使用されます。"),
            ),
            array(
                'type' => 'literal',
            ),
            array(
                'type' => 'literal',
            ),
            array(
                'label' => _g("サプライヤーのみ"),
                'type' => 'section',
            ),
            array(
                'type' => 'literal',
            ),
            array(
                'label' => _g('標準リードタイム'),
                'type' => 'textbox',
                'name' => 'default_lead_time',
                'value' => @$form['default_lead_time'],
                'size' => '8',
                'ime' => 'off',
                'helpText_noEscape' => _g("品目マスタの登録時に、リードタイムのデフォルト値として使用されます。"),
            ),
            array(
                'label' => _g('納入場所'),
                'type' => 'textbox',
                'name' => 'delivery_port',
                'value' => @$form['delivery_port'],
                'helpText_noEscape' => _g('メモ用です。'),
                'ime' => 'on',
                'size' => '15'
            ),
            array(
                'label' => _g('買掛残高初期値'),
                'type' => 'textbox',
                'name' => 'payment_opening_balance',
                'value' => @$form['payment_opening_balance'],
                'helpText_noEscape' => _g("買掛管理における、繰越残高の初期値です。"),
                'ime' => 'off',
                'size' => '8',
            ),
            array(
                'label' => _g('買掛基準日'),
                'type' => 'calendar',
                'name' => 'payment_opening_date',
                'value' => @$form['payment_opening_date'],
                'helpText_noEscape' => _g("「買掛残高初期値」を適用する日付です。"),
                'size' => '8',
                'ime' => 'off',
                'isCalendar' => true,
            ),
            array(
                'label' => _g('支払サイクル1（x日後）'),
                'type' => 'textbox',
                'name' => 'payment_cycle1',
                'value' => @$form['payment_cycle1'],
                'ime' => 'off',
                'size' => '8',
                'afterLabel_noEscape' => _g('日後に支払'),
                'helpText_noEscape' => _g("受入登録/外製受入登録画面の「支払予定日」のデフォルト値を決定するのに使用されます。") . '<br><br>'
                . _g("この取引先マスタ画面の「締日グループ」により各受入データの支払締日が内部的に決定され、その締日から指定日数が経過した日がデフォルトの「支払予定日」となります。") . '<br><br>'
                . _g("各受入データの支払予定日は受入登録画面で確認でき、「支払予定表」に反映されます。") . '<br><br>'
                . _g("月ごとの日数の違いの影響を受けないよう、1ヶ月を30日として計算されます。例えば7/31締めで支払サイクル30日の場合、支払予定日は8/31となります（8/30ではありません）。") . '<br><br>'
                . _g("支払サイクル1,2とも登録を省略した場合、「支払予定日」はデフォルトで空欄になります。"),
            ),
            array(
                'label' => _g('支払サイクル2（xヶ月後）'),
                'type' => 'textbox',
                'name' => 'payment_cycle2_month',
                'value' => @$form['payment_cycle2_month'],
                'ime' => 'off',
                'size' => '8',
                'afterLabel_noEscape' => _g('ヶ月後の'),
                'helpText_noEscape' => _g("「支払サイクル2（x日）」と組み合わせて、「xヶ月後のx日」という形で指定します。") . '<br>'
                . _g("支払サイクル1が指定されている場合、この項目は無視されます。") . '<br><br>'
                . _g("支払サイクルの意味については、支払サイクル1のチップヘルプをご覧ください。"),
            ),
            array(
                'type' => 'literal',
            ),
            array(
                'label' => _g('支払サイクル2（x日）'),
                'type' => 'textbox',
                'name' => 'payment_cycle2_day',
                'value' => @$form['payment_cycle2_day'],
                'helpText_noEscape' => _g("「支払サイクル2（xヶ月後）」と組み合わせて、「xヶ月後のx日」という形で指定します。「31」は月末日をあらわします。") . '<br>'
                . _g("支払サイクル1が指定されている場合、この項目は無視されます。") . '<br><br>'
                . _g("支払サイクルの意味については、支払サイクル1のチップヘルプをご覧ください。"),
                'ime' => 'off',
                'size' => '8',
                'afterLabel_noEscape' => _g('日に支払'),
            ),
            array(
                'label' => _g('帳票（注文書）'),
                'type' => 'select',
                'name' => 'template_partner_order',
                'options' => $templates[2],
                'selected' => @$form['template_partner_order'],
                'helpText_noEscape' => _g("注文書の帳票テンプレートを指定します。この指定は帳票テンプレート設定ダイアログの指定より優先されます。") .
                _g("「標準」が選択されている場合は、帳票テンプレート設定ダイアログで指定された帳票が使用されます。"),
            ),
            array(
                'label' => _g('帳票（外製指示書）'),
                'type' => 'select',
                'name' => 'template_subcontract',
                'options' => $templates[3],
                'selected' => @$form['template_subcontract'],
                'helpText_noEscape' => _g("外製指示書の帳票テンプレートを指定します。この指定は帳票テンプレート設定ダイアログの指定より優先されます。") .
                _g("「標準」が選択されている場合は、帳票テンプレート設定ダイアログで指定された帳票が使用されます。"),
            ),
        );
    }

}
