<?php

// テストクラス共通処理。先頭に書くこと
require_once dirname(__FILE__) . '/../TestCommon.class.php';

require_once dirname(__FILE__) . '/../../Components/Math.class.php';

class PaymentListReport3Test extends PHPUnit_Framework_TestCase {

    protected $object;

    protected function setUp() {
        $this->object = new Logic_Delivery;

        global $gen_db;
        $gen_db->begin();         // コミットはしないこと
    }

    protected function tearDown() {
        global $gen_db;
        $gen_db->rollback();
    }

    public function testReceivablListReport3() {
        var_dump('--- 1月 ---');
        self::_paymentListReport3TestCore(1000.1, 1000, 1000.3, 1000, -1000, 1000);
        var_dump('--- 2月 ---');
        self::_paymentListReport3TestCore(1000.3, 1000.1, 1000.1, 1000.1, -1000.1, 1000.001);
        var_dump('--- 6月 ---');
        self::_paymentListReport3TestCore(1000.003, 1000.001, 1000.001, 1000.001, -1000.001, -1000);
        var_dump('--- 9月 ---');
        self::_paymentListReport3TestCore(1000.002, 1000.009, 1000.009, 1000.009, -1000.001, -1000.001);
    }

    // さまざまなパターンにおいて、受入書や請求書の数値が期待値（計算値）どおりになるかどうかをチェックする。
    private function _paymentListReport3TestCore($orderPrice11, $orderPrice12, $orderPrice21, $orderPrice31, $orderPrice41, $paymentAmount) {
        $testNo = 1;
        $taxRate = (Logic_Tax::getTaxRate(date('Y-m-d')) / 100);
        
        $broughtForward = 10000;        // 期間前からの繰越額

        for($currencyMode=0; $currencyMode<=1; $currencyMode++) {
            switch($currencyMode) {
                case 0: $currency = null; $currencyStr = "JPY"; break;   // 基軸通貨
                case 1: $currency = 1; $currencyStr = "USD"; break;      // 外貨
            }
            for($yenMode=0; $yenMode<=1; $yenMode++) {
                switch($yenMode) {
                    case 0: $yenModeStr = "No conv"; break;   // 取引通貨別
                    case 1: $yenModeStr = "conv"; break;      // 基軸通貨換算
                }
                for($precisionMode=0; $precisionMode<=1; $precisionMode++) {
                    switch($precisionMode) {
                        case 0: $precision = 0; $precisionStr = "precision 0"; break;     // 小数点以下0桁
                        case 1: $precision = 2; $precisionStr = "precision 2"; break;     // 小数点以下2桁
                    }
                    for($roundMode=0; $roundMode<=2; $roundMode++) {
                        switch($roundMode) {
                            case 0: $round = 'round'; $roundStr = "round"; break;     // 四捨五入
                            case 1: $round = 'floor'; $roundStr = "floor"; break;     // 切り捨て
                            case 2: $round = 'ceil'; $roundStr = "ceil"; break;      // 切り上げ
                        }
                        // 支払の外貨の円換算値は登録時に丸められている
                        if ($currency == 1 && $yenMode == 1) {
                            $paymentRounded = Gen_Math::round($paymentAmount, $round, $precision);
                        } else {
                            $paymentRounded = $paymentAmount;
                        }
                        for($taxClassMode=0; $taxClassMode<=1; $taxClassMode++) {
                            switch($taxClassMode) {
                                case 0: $taxClass = 0; $taxClassStr = "tax"; break;     // 課税
                                case 1: $taxClass = 1; $taxClassStr = "no tax"; break;     // 非課税
                            }
                            // 期待値を算出する

                            //  ※金額は課税区分（請求書単位・受入書単位など）にかかわらず、常に受入明細行ごとに丸められる
                            $am11 = Gen_Math::round($orderPrice11, $round, $precision);
                            $am12 = Gen_Math::round($orderPrice12, $round, $precision);
                            $am21 = Gen_Math::round($orderPrice21, $round, $precision);
                            $am31 = Gen_Math::round($orderPrice31, $round, $precision);
                            $am41 = Gen_Math::round($orderPrice41, $round, $precision);
                            $paymentTotal = $am11 + $am12 + $am21 + $am31 + $am41;
                            if ($taxClass==0 && $currency == null) {
                                $tax = Gen_Math::round($am11 * $taxRate, $round, $precision)
                                    + Gen_Math::round($am12 * $taxRate, $round, $precision)
                                    + Gen_Math::round($am21 * $taxRate, $round, $precision)
                                    + Gen_Math::round($am31 * $taxRate, $round, $precision)
                                    + Gen_Math::round($am41 * $taxRate, $round, $precision);
                            } else {
                                $tax = 0;
                            }

                            var_dump("No: {$testNo}, {$currencyStr}, {$yenModeStr}, {$precisionStr}, {$roundStr}, {$taxClassStr}");

                            self::_paymentListReport3TestCore2(
                                    $testNo++,
                                    $precision,
                                    $round,
                                    $taxClass,
                                    $currency,
                                    $yenMode,

                                    $orderPrice11,
                                    $orderPrice12,
                                    $orderPrice21,
                                    $orderPrice31,
                                    $orderPrice41,
                                    $paymentAmount,

                                    // 以下、期待値
                                    $broughtForward,    // 繰越額
                                    $paymentTotal,       // 合計仕支払額
                                    $tax,               // 合計消費税額
                                    $paymentRounded,          // 合計支払額
                                    $broughtForward + $paymentTotal + $tax - $paymentRounded    // 買掛残高
                             );
                        }
                    }
                }
            }
        }
    }

    // $testNo
    // $precision   取引先マスタ　小数点以下の桁数
    // $round       取引先マスタ　端数処理  空欄: なし, round: 四捨五入, floor: 切捨, ceil: 切上
    // $taxClass    品目マスタ　　課税区分　0:課税、1:非課税
    // $rate        外貨レート（基軸通貨の場合はnull）
    // $yenMode     基軸通貨換算モード　0:取引通貨別、1:基軸通貨換算
    // $orderPrice11  発注1件目1行目　単価
    // $orderPrice12  発注1件目2行目　単価
    // $orderPrice21  発注2件目1行目　単価
    // $orderPrice31  発注3件目1行目　単価
    // $orderPrice41  発注4件目1行目　単価
    // $paymentAmount    支払
    // $answer1     期待値：繰越額
    // $answer2     期待値：合計仕支払額
    // $answer3     期待値：合計消費税額
    // $answer4     期待値：合計支払額
    // $answer5     期待値：買掛残高
    private function _paymentListReport3TestCore2($testNo, $precision, $round, $taxClass, $rate, $yenMode, 
            $orderPrice11, $orderPrice12, $orderPrice21, $orderPrice31, $orderPrice41, $paymentAmount,
            $answer1, $answer2, $answer3, $answer4, $answer5
            ) {
        global $gen_db;

        $acceptedDate = date('Y-m-d');

//if ($testNo != 1) return;

        // 品目
        $itemId = TestCommon::makeItem(array('tax_class'=>$taxClass));

        // 外貨
        if ($rate == null) {
            $currencyId = null;
        } else {
            $currencyId = TestCommon::makeCurrencyAndRate(array(), '1970-01-01', 1);
        }

        // 取引先
        $custId = TestCommon::makeCustomer(array('classification'=>1, 'rounding'=>$round, 'precision'=>$precision, 'currency_id' => $currencyId));
        $custNo = $gen_db->queryOneValue("select customer_no from customer_master where customer_id = '$custId'");
        
        // 繰越額（期間前の買掛残高初期値として登録）
        $beforeDate = date('Y-m-d', strtotime($acceptedDate . ' -1 month'));
        $query = "update customer_master set payment_opening_balance = {$answer1}, payment_opening_date = '{$beforeDate}' where customer_id = '{$custId}'";
        $gen_db->query($query);

        // ********** 発注 ⇒ 受入 ⇒ 請求 データ作成 **********

        // 発注1件目。同時に受入を登録
        $orderHeaderId_1 = TestCommon::makeOrder(array(
            // 明細1行目:
            array(
                'partner_id' => $custId
                ,'order_date' => $acceptedDate
                ,'order_detail_dead_line' => $acceptedDate
                ,'item_id' => $itemId
                ,'order_detail_quantity' => 1
                ,'item_price' => $orderPrice11
                ,'accepted_regist' => 'true'
                ),
            // 明細2行目
            array(
                'partner_id' => $custId
                ,'order_date' => $acceptedDate
                ,'order_detail_dead_line' => $acceptedDate
                ,'item_id' => $itemId
                ,'order_detail_quantity' => 1
                ,'item_price' => $orderPrice12
                ,'accepted_regist' => 'true'
                ),
            ));

        // 発注2件目。同時に受入を登録
        $orderHeaderId_2 = TestCommon::makeOrder(array(
            'partner_id' => $custId
            ,'order_date' => $acceptedDate
            ,'order_detail_dead_line' => $acceptedDate
            ,'item_id' => $itemId
            ,'order_detail_quantity' => 1
            ,'item_price' => $orderPrice21
            ,'accepted_regist' => 'true'
        ));

        // 発注3件目。同時に受入を登録
        $orderHeaderId_3 = TestCommon::makeOrder(array(
            'partner_id' => $custId
            ,'order_date' => $acceptedDate
            ,'order_detail_dead_line' => $acceptedDate
            ,'item_id' => $itemId
            ,'order_detail_quantity' => 1
            ,'item_price' => $orderPrice31
            ,'accepted_regist' => 'true'
        ));

        // 発注4件目。同時に受入を登録
        $orderHeaderId_4 = TestCommon::makeOrder(array(
            'partner_id' => $custId
            ,'order_date' => $acceptedDate
            ,'order_detail_dead_line' => $acceptedDate
            ,'item_id' => $itemId
            ,'order_detail_quantity' => 1
            ,'item_price' => $orderPrice41
            ,'accepted_regist' => 'true'
        ));
        
        // 支払
        $foreignCurrencyRate = null;
        $foreignCurrencyAmount = null;
        if ($currencyId != null) {
            $foreignCurrencyRate = $rate;
            $foreignCurrencyAmount = $paymentAmount;
            // 入力された値はまるめないが、円換算時にはまるめを行う
            $paymentAmount = Logic_Customer::round(Gen_Math::mul($paymentAmount, $foreignCurrencyRate), $custId);
        }
        $data = array(
            'payment_date' => $acceptedDate,
            'customer_id' => $custId,
            'foreign_currency_id' => $currencyId,
            'foreign_currency_rate' => $foreignCurrencyRate,
            'way_of_payment' => 0,
            'amount' => $paymentAmount,
            'foreign_currency_amount' => $foreignCurrencyAmount,
            'remarks' => "",
        );
        $gen_db->insert("payment", $data);
        
        // ***** 仕入先元帳 帳票発行（結果を temp_test テーブルに取得） *****
        
        $action = "Partner_PaymentList_List";        
        require_once(Gen_File::safetyPathForAction($action));
        $actionClass = new $action;
        $form['gen_iPad'] = false;
        $form['action'] = $action;
        $form['gen_reportAction'] = "Partner_PaymentList_Report3";
        $form['gen_unitTestMode'] = true;
        
        $form['gen_searchConditionClear'] = true; // ピンを無視
        $form['gen_search_close_date_from'] = date("Y-m-1", strtotime($acceptedDate));
        $form['gen_search_close_date_to'] = date("Y-m-t", strtotime($acceptedDate));
        $form['gen_search_temp_payment___customer_no'] = $custNo;
        $form['gen_search_match_mode_gen_search_temp_payment___customer_no'] = "3"; // 3:「と一致」
        $form['gen_search_foreign_currency_mode'] = $yenMode;   // array('0' => _g("取引通貨別"), '1' => sprintf(_g("%s換算"), $keyCurrency)),
        
        $query = $actionClass->execute($form);
        $gen_db->createTempTable("temp_test", $query, true);

        // ***** 結果確認 *****
        // ヘッダ
        $arr = $gen_db->getArray("select 仕入先元帳_繰越額, 仕入先元帳_期間中仕入額, 仕入先元帳_期間中消費税額, 仕入先元帳_期間中支払額, 仕入先元帳_買掛金残高 from temp_test limit 1");
//var_dump($arr);        
        $this->assertEquals((string)$answer1, $arr[0]['仕入先元帳_繰越額']);
        $this->assertEquals((string)$answer2, $arr[0]['仕入先元帳_期間中仕入額']);
        $this->assertEquals((string)$answer3, $arr[0]['仕入先元帳_期間中消費税額']);
        $this->assertEquals((string)$answer4, $arr[0]['仕入先元帳_期間中支払額']);
        $this->assertEquals((string)$answer5, $arr[0]['仕入先元帳_買掛金残高']);
        
        // 明細（合計）
        $arr = $gen_db->getArray("select coalesce(sum(detail_仕入先元帳_金額),0) as 金額, coalesce(sum(detail_仕入先元帳_消費税額),0) as 消費税, coalesce(sum(detail_仕入先元帳_支払額),0) as 支払 from temp_test");
        $this->assertEquals((string)$answer2, $arr[0]['金額']);
        $this->assertEquals((string)$answer3, $arr[0]['消費税']);
        $this->assertEquals((string)$answer4, $arr[0]['支払']);
        
        // 明細残高の最終
        $arr = $gen_db->getArray("select detail_仕入先元帳_明細残高 as 残高 from temp_test");
        $this->assertEquals((string)$answer5, $arr[count($arr)-1]['残高']);
    }

}