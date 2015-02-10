<?php

require_once("Model.class.php");

class Master_Company_Edit extends Base_EditBase
{

    function convert($converter)
    {
        $converter->nullBlankToValue('max_lc', 30);
        $converter->nullBlankToValue('company_id', 1);
    }

    function setQueryParam(&$form)
    {
        $this->keyColumn = 'company_id';
        $this->selectQuery = "
            select
                *
                ,case when excel_cell_join then 'true' else '' end as excel_cell_join
                ,case when excel_color then 'true' else '' end as excel_color
                ,coalesce(record_update_date, record_create_date) as gen_last_update
                ,coalesce(record_updater, record_creator) as gen_last_updater
            from
                company_master
        ";
    }

    function setViewParam(&$form)
    {
        $this->modelName = "Master_Company_Model";

        $form['gen_pageTitle'] = _g('自社情報');
        $form['gen_entryAction'] = "Master_Company_Entry";
        $form['gen_listAction'] = "Master_Company_Edit";
        $form['gen_pageHelp'] = _g("自社情報");

        $form['gen_onLoad_noEscape'] = "onLoad()";
        $urlAndUser = h("http" . (GEN_HTTPS_PROTOCOL === false ? "" : "s") . ":!!" . $_SERVER['SERVER_NAME'] . "/" . basename(ROOT_DIR) . "/index.php[user]" . $_SESSION['user_name']);
        $form['gen_javascript_noEscape'] = "
            function onLoad() {
                //$('#gen_contents').append('<center>" . _g("設定用QRコード") . "<br><br></center>').qrcode({width:120,height:120,text:\"{$urlAndUser}\".replace('!','/')}).append('<div style=\"height:50px\"></div>');
                " . (isset($form['afterEntry']) ? "alert('" . _g("登録しました。") . "');" : "") . "
            }
        ";

        // 自社ロゴ登録
        global $gen_db;
        $query = "select image_file_name from company_master";
        $imageFileName  = $gen_db->queryOneValue($query);
        $form['gen_message_noEscape'] = "
            <script type=\"text/javascript\" src=\"scripts/jquery.qrcode/jquery.qrcode.min.js\"></script>
            
            <div style='height:10px'></div> 
            <div style='width:600px; text-align:center; font-weight:normal; background:#ffffcc'>
            <div style='height:10px'></div>

            <script>gen.imageUpload.init('" . h($imageFileName) . "','companylogo','')</script>

            "._g("JPG, GIF, PNG 画像を登録できます。画像のサイズは自動的に調整されます。") . "<br>
            "._g("（あらかじめ画像編集ソフトで縦が40ピクセルになるよう編集しておくときれいな画像になります。）") ."
            <div style='height:10px'></div>
            </div>
            <div style='height:20px'></div>
        ";
            
        $options_month = array();
        for ($month = 1; $month <= 12; $month++) {
            $options_month[$month] = $month;
        }
                
        $form['gen_editControlArray'] = array(
            array(
                'label' => _g('会社名'),
                'type' => 'textbox',
                'name' => 'company_name',
                'value' => @$form['company_name'],
                'size' => '22',
                'ime' => 'on',
                'require' => true
            ),
            array(
                'label' => _g('郵便番号'),
                'type' => 'textbox',
                'name' => 'zip',
                'value' => @$form['zip'],
                'ime' => 'off',
                'size' => '10'
            ),
            array(
                'label' => _g('会社名（英語表記）'),
                'type' => 'textbox',
                'name' => 'company_name_en',
                'value' => @$form['company_name_en'],
                'size' => '22',
                'ime' => 'off',
                'helpText_noEscape' => _g('英文注文書に表示されます。') . '<br>' . _g('英文注文書を使用しない場合、入力する必要はありません。') . '<br><br>' . _g('※英文注文書は次の2つの条件にあてはまるときに発行されます。') . '<br>' . _g('・注文書テンプレートで「注文書（日英切替）」を選択している。') . '<br>' . _g('・対象発注先の取引先マスタの「注文書区分」が「英語」になっている。'),
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
                'size' => '22'
            ),
            array(
                'label' => _g('住所2（ビル名等）'),
                'type' => 'textbox',
                'name' => 'address2',
                'value' => @$form['address2'],
                'ime' => 'on',
                'size' => '22'
            ),
            array(
                'label' => _g('住所1（英語表記）'),
                'type' => 'textbox',
                'name' => 'address1_en',
                'value' => @$form['address1_en'],
                'ime' => 'off',
                'size' => '22',
                'helpText_noEscape' => _g('英文注文書に表示されます。') . '<br>' . _g('英文注文書を使用しない場合、入力する必要はありません。') . '<br><br>' . _g('※英文注文書については、この画面の「会社名（英語表記）」の項目のチップヘルプを参照してください。'),
            ),
            array(
                'label' => _g('住所2（英語表記）'),
                'type' => 'textbox',
                'name' => 'address2_en',
                'value' => @$form['address2_en'],
                'ime' => 'off',
                'size' => '22',
                'helpText_noEscape' => _g('英文注文書に表示されます。') . '<br>' . _g('英文注文書を使用しない場合、入力する必要はありません。') . '<br><br>' . _g('※英文注文書については、この画面の「会社名（英語表記）」の項目のチップヘルプを参照してください。'),
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
                'label' => _g('取引銀行'),
                'type' => 'textbox',
                'name' => 'main_bank',
                'value' => @$form['main_bank'],
                'size' => '22',
                'ime' => 'on',
                'helpText_noEscape' => _g("請求書に反映されます。"),
            ),
            array(
                'label' => _g('口座番号'),
                'type' => 'textbox',
                'name' => 'bank_account',
                'value' => @$form['bank_account'],
                'size' => '22',
                'ime' => 'off',
                'helpText_noEscape' => _g("請求書に反映されます。"),
            ),
            array(
                'type' => 'literal',
            ),
            array(
                'type' => 'literal',
            ),
            array(
                'label' => _g('年度開始月'),
                'type' => 'select',
                'name' => 'starting_month_of_accounting_period',
                'selected' => @$_SESSION['gen_setting_company']->starting_month_of_accounting_period,
                'options' => $options_month,
                'require' => true,
                'size' => '6',
                'helpText_noEscape' => _g("年度の開始月を指定します。") . "<br><br>"
                . _g("この値は、各画面の日付範囲入力ボックスで「年度」を指定した場合の日付セット動作に使用されます。"),
            ),
            array(
                'label' => _g('データロック基準日'),
                'type' => 'textbox',
                'name' => 'monthly_dealing_date',
                'value' => @$form['monthly_dealing_date'],
                'size' => '10',
                'readonly' => true,
                'helpText_noEscape' => _g("各画面において、これより前の日付のデータを登録・更新することはできないようロックされます。この日付は[メンテナンス]-[データロック処理]で変更できます。"),
            ),
            array(
                'label' => _g('基軸通貨'),
                'type' => 'textbox',
                'name' => 'key_currency',
                'value' => @$form['key_currency'],
                'require' => true,
                'size' => '6',
                'helpText_noEscape' => _g("主に使用する取引通貨を設定します。取引通貨記号1文字か、半角3文字で入力してください。（例：「￥」「EUR」）") . "<br><br>"
                . _g("ここで指定した取引通貨は通貨マスタに登録する必要はありません。") . "<br><br>"
                . _g("基軸通貨での売買に対しては自動的に消費税の計算が行われます。消費税率マスタに、基軸通貨に適用する消費税率を登録してください。（消費税がない場合は0%を登録してください。）") . "<br><br>"
                . _g("複数の取引通貨を使用する場合は、ここで基軸となる取引通貨を登録しておき、その他の取引通貨を通貨マスタとレートマスタに登録してください。"),
            ),
            array(
                'type' => 'literal',
            ),
            array(
                'label' => _g('パスワードの最低文字数'),
                'type' => 'textbox',
                'name' => 'password_minimum_length',
                'value' => @$form['password_minimum_length'],
                'require' => true,
                'size' => '10',
                'ime' => 'off',
                'helpText_noEscape' => _g("各ユーザーのログインパスワードの最低文字数を設定します。" .
                        "「0」にすると文字数のチェックは行われなくなります。" .
                        "御社のセキュリティポリシーに従って設定してください。") . "<br>" .
                        _g("最低でも6文字以上、できれば8文字以上にすることをおすすめします。"),
            ),
            array(
                'label' => _g('パスワードの有効期間(日)'),
                'type' => 'textbox',
                'name' => 'password_valid_until',
                'value' => @$form['password_valid_until'],
                'require' => true,
                'size' => '10',
                'ime' => 'off',
                'helpText_noEscape' => _g("各ユーザーのログインパスワードの有効期間（日数）を設定します。" .
                        "有効期間を過ぎると、ユーザーはパスワードの変更を求められます。") . "<br>" .
                        _g("「0」にすると無期限になります。") . "<br>" .
                        _g("御社のセキュリティポリシーに従って設定してください。"),
            ),
            array(
                'label' => _g('ログイン失敗回数の上限'),
                'type' => 'textbox',
                'name' => 'account_lockout_threshold',
                'value' => @$form['account_lockout_threshold'],
                'require' => true,
                'size' => '10',
                'ime' => 'off',
                'helpText_noEscape' => _g("ユーザーがログインに連続して失敗できる回数を設定します。") . "<br>" .
                        _g("パスワードの誤りによるログイン失敗がここで指定した回数続くと、ユーザーアカウントがロックされて使用できなくなります。") . "<br>" .
                        _g("ただし「ﾛｸﾞｲﾝ失敗回数のﾘｾｯﾄ」で設定した時間が経過するか、ログインに成功すれば失敗の回数はリセットされます。") . "<br>" .
                        _g("「0」にするとアカウントのロックは行われません。") . "<br>" .
                        _g("御社のセキュリティポリシーに従って設定してください。") . "<br>" .
                        _g("ロックされたユーザーアカウントは、[メンテナンス]-[ユーザーの登録・編集]の「アカウントのロックアウト」のチェックを外すことで解除することができます。"),
            ),
            array(
                'label' => _g('ﾛｸﾞｲﾝ失敗回数のﾘｾｯﾄ(分)'),
                'type' => 'textbox',
                'name' => 'account_lockout_reset_minute',
                'value' => @$form['account_lockout_reset_minute'],
                'require' => true,
                'size' => '10',
                'ime' => 'off',
                'helpText_noEscape' => _g("ユーザーのログイン失敗回数がリセットされるまでの時間（分）を設定します。") . "<br>" .
                        _g("ユーザーが連続してログインに失敗するとその回数が記録され、それが「アカウントロックまでの回数」に達するとアカウントがロックされますが、ここで設定した時間が経過すると失敗回数がリセットされます。") . "<br>" .
                        _g("「0」にすると失敗回数のリセットは行われません。") . "<br>" .
                        _g("御社のセキュリティポリシーに従って設定してください。"),
            ),
            array(
                'label' => _g('在庫評価法'),
                'type' => 'select',
                'name' => 'stock_price_assessment',
                'selected' => @$form['stock_price_assessment'],
                'options' => array('0' => _g("最終仕入原価法"), '1' => _g("総平均法"), '2' => _g("標準原価法")),
                'helpText_noEscape' => _g("在庫評価法を指定します。") . "<br>" . _g("「標準原価法」は更新画面を使用せず、品目マスタに在庫評価単価を手入力する方法です。") . "<br>" . _g("「最終仕入原価法」「総平均法」を選択した場合、棚卸後などのタイミングで在庫評価単価の更新処理（[資材管理]-[在庫リスト]内にリンクがあります）を行う必要があります。詳細については更新処理画面の説明を参照してください。"),
            ),
            array(
                'label' => _g('在庫評価端数処理'),
                'type' => 'select',
                'name' => 'assessment_rounding',
                'selected' => @$form['assessment_rounding'],
                'options' => array('round' => _g('四捨五入'), 'floor' => _g('切捨'), 'ceil' => _g('切上')),
                'helpText_noEscape' => _g("在庫評価計算の端数処理を指定します。") . "<br>" . _g("在庫評価法が「総平均法」の時に適用されます。"),
            ),
            array(
                'label' => _g('在庫評価の小数点以下桁数'),
                'type' => 'textbox',
                'name' => 'assessment_precision',
                'value' => @$form['assessment_precision'],
                'require' => true,
                'size' => '10',
                'ime' => 'off',
                'helpText_noEscape' => _g("在庫評価計算の小数点以下桁数を指定します。") . "<br>" . _g("在庫評価法が「総平均法」の時に適用されます。"),
            ),
            array(
                'label' => _g('外製支給のタイミング'),
                'type' => 'select',
                'name' => 'payout_timing',
                'selected' => @$form['payout_timing'],
                'options' => array('0' => _g("発注時"), '1' => _g("受入時")),
                'helpText_noEscape' => _g("外製指示書における、子品目の支給（引落）のタイミングを指定します。") . "<br>" . _g("●発注時： 外製指示の発行日に在庫から引き落とされます。") . "<br>" . _g("●受入時： 外製指示を登録した時点で使用予定が登録され（有効在庫に影響します）、外製受入登録を行った日に在庫から引き落とされます。") . "<br><br>" . _g("なお、外製指示の対象となる発注先（サプライヤー）にサプライヤーロケーションが登録されていると、この設定は無視されます。その場合、外製指示の発行日にサプライヤーロケに在庫移動し、外製受入登録を行った日にサプライヤーロケから引き落とされます。サプライヤーロケの有無は、ロケーションマスタで確認できます。"),
            ),
            array(
                'label' => _g('売上計上基準'),
                'type' => 'select',
                'name' => 'receivable_report_timing',
                'selected' => @$form['receivable_report_timing'],
                'options' => array('0' => _g("納品日"), '1' => _g("検収日")),
                'helpText_noEscape' => _g("請求書の発行と売掛管理の基準となる日付（どの時点で売上とみなされるか）を選択します。請求書、販売レポートなどに影響します。"),
            ),
            array(
                'label' => _g('仕入計上基準'),
                'type' => 'select',
                'name' => 'payment_report_timing',
                'selected' => @$form['payment_report_timing'],
                'options' => array('0' => _g("受入日"), '1' => _g("検収日")),
                'helpText_noEscape' => _g("買掛管理の基準となる日付（どの時点で仕入とみなされるか）を選択します。買掛残高表、支払予定表、購買レポートなどに影響します。"),
            ),
            array(
                'label' => _g('Excelセル結合'),
                'type' => 'checkbox',
                'name' => 'excel_cell_join',
                'onvalue' => 'true', // trueのときの値。デフォルト値ではない
                'value' => @$form['excel_cell_join'],
                'helpText_noEscape' => _g('このチェックをオンにすると、各画面のエクセル出力においてセルの結合が行われます。セルの結合を行いたくない場合はチェックを外してください。'),
            ),
            array(
                'label' => _g('Excelセル着色'),
                'type' => 'checkbox',
                'name' => 'excel_color',
                'onvalue' => 'true', // trueのときの値。デフォルト値ではない
                'value' => @$form['excel_color'],
                'helpText_noEscape' => _g('このチェックをオンにすると、各画面のエクセル出力においてセルの着色が行われます。セルの着色を行いたくない場合はチェックを外してください。'),
            ),
            array(
                'label' => _g('Excel日付出力形式'),
                'type' => 'select',
                'name' => 'excel_date_type',
                'selected' => @$form['excel_date_type'],
                'options' => array('0' => date('Y-m-d'), '1' => date('Y/n/j')),
            ),
            array(
                'label' => _g('自社情報備考'),
                'type' => 'textbox',
                'name' => 'remarks',
                'value' => @$form['remarks'],
                'ime' => 'on',
                'size' => '20',
                'helpText_noEscape' => _g('メモ用です。この画面以外の場所に表示されることはありません。'),
            ),
            // チップヘルプ表示用のスペース
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
                'type' => 'literal',
            ),
        );
    }

}
