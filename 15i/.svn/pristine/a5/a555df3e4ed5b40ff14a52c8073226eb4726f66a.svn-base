<?php

// テストクラス共通処理。先頭に書くこと
require_once dirname(__FILE__) . '/../TestCommon.class.php';
require_once dirname(__FILE__) . '/../../Logic/Bill.class.php';

class Logic_BillTest extends PHPUnit_Framework_TestCase
{

    protected $object;

    protected function setUp()
    {
        $this->object = new Logic_Bill;

        global $gen_db;
        $gen_db->begin();         // コミットはしないこと
    }

    protected function tearDown()
    {
        global $gen_db;
        $gen_db->rollback();
    }

    // ********** 請求書発行テスト **********
    //
    // 請求書テスト1
    // 税計算単位（請求書単位 / 納品書単位 / 納品明細単位）を反映した税金計算
    public function testMakeBillData_1()
    {
        global $gen_db; // TestCommonで準備される

        // 品目
        $itemId = TestCommon::makeItem(array('tax_rate' => 5));

        $deliveryDate = date('Y-m-d');

        // 0: 請求書単位 / 1: 納品書単位 / 2: 納品明細単位
        for ($taxCategory = 0; $taxCategory <= 2; $taxCategory++) {

            // 得意先（四捨五入で整数丸め、締め請求）
            $custId = TestCommon::makeCustomer(array('tax_category' => $taxCategory, 'rounding' => 'round', 'precision' => 0, 'bill_pattern' => 1));

            // 受注
            $recHeaderId = TestCommon::makeReceived(array(
                        'customer_id' => $custId
                        , 'received_date' => $deliveryDate
                        , 'dead_line' => $deliveryDate
                        , 'item_id' => $itemId
                        , 'received_quantity' => 10
            ));
            $recDetailId = $gen_db->queryOneValue("select received_detail_id from received_detail where received_header_id='{$recHeaderId}'");
            $recHeaderId_2 = TestCommon::makeReceived(array(
                        'customer_id' => $custId
                        , 'received_date' => $deliveryDate
                        , 'dead_line' => $deliveryDate
                        , 'item_id' => $itemId
                        , 'received_quantity' => 10
            ));
            $recDetailId_2 = $gen_db->queryOneValue("select received_detail_id from received_detail where received_header_id='{$recHeaderId_2}'");

            // 納品 \53 × 2行 × 2件
            for ($i = 1; $i <= 2; $i++) {
                TestCommon::makeDelivery(array(
                    // 明細1行目
                    array(
                        'received_detail_id' => $recDetailId
                        , 'delivery_date' => $deliveryDate
                        , 'inspection_date' => $deliveryDate
                        , 'delivery_quantity' => 1
                        , 'delivery_price' => 53
                    ),
                    // 明細2行目
                    array(
                        'received_detail_id' => $recDetailId_2
                        , 'delivery_date' => $deliveryDate
                        , 'inspection_date' => $deliveryDate
                        , 'delivery_quantity' => 1
                        , 'delivery_price' => 53
                    )
                ));
            }

            // 請求
            $this->object->makeBillData(0, $deliveryDate, 0, array($custId), null);

            // 結果確認
            $query = "select tax_amount from bill_header where customer_id = '{$custId}'";
            $obj = $gen_db->queryOneRowObject($query);

            //  納品 \53 × 2行 を 2件登録して請求書発行（四捨五入）
            //  税率 5%（品目作成時に指定している）
            //	請求書単位：　	税額 \11	 1枚\11
            //	納品書単位：　	税額 \10	 1枚\5 × 2
            //	納品明細単位：　 税額 \12	 1行\3 × 4

            switch ($taxCategory) {
                case 0: $answer = 11;
                    break;
                case 1: $answer = 10;
                    break;
                case 2: $answer = 12;
                    break;
            }

            $this->assertEquals($answer, $obj->tax_amount);
        }
    }

    // 請求書テスト2
    // 取引先ごとの丸め方法の適用
    public function testMakeBillData_2()
    {
        global $gen_db; // TestCommonで準備される

        // 品目
        $itemId = TestCommon::makeItem(array('tax_rate' => 5));

        $price = 123.456789;
        for ($i = 0; $i <= 2; $i++) {
            // 取引先ごとの丸め方法や小数点以下桁数が金額計算に適用されていることを確認する
            // ヘッダの今回売上と税額のみチェック
            //  税率 5%（品目作成時に指定している）
            switch ($i) {
                case 0: $round = 'floor';
                    $prec = 4;
                    $answer_amount = 123.4567;
                    $answer_tax = 6.1728;
                    break;
                case 1: $round = 'round';
                    $prec = 3;
                    $answer_amount = 123.457;
                    $answer_tax = 6.173;
                    break;
                case 2: $round = 'ceil';
                    $prec = 2;
                    $answer_amount = 123.46;
                    $answer_tax = 6.18;
                    break;
            }

            // 得意先
            $custId = TestCommon::makeCustomer(array('rounding' => $round, 'precision' => $prec, 'bill_pattern' => 1));

            // 受注。同時に納品を登録
            $deliveryDate = date('Y-m-d');
            $recId = TestCommon::makeReceived(array(
                'customer_id' => $custId
                , 'received_date' => $deliveryDate
                , 'dead_line' => $deliveryDate
                , 'item_id' => $itemId
                , 'received_quantity' => 1
                , 'product_price' => $price
                , 'delivery_regist' => 'true'));

            // 請求
            $this->object->makeBillData(0, $deliveryDate, 0, array($custId), null);

            // 結果確認
            $query = "select sales_amount, tax_amount, bill_amount from bill_header where customer_id = '{$custId}'";
            $obj = $gen_db->queryOneRowObject($query);

            $this->assertEquals($answer_amount, $obj->sales_amount);
            $this->assertEquals($answer_tax, $obj->tax_amount);
        }
    }

    // 請求書テスト3
    // 締め請求における売掛残高初期値の反映と請求残高の繰越
    public function testMakeBillData_3()
    {
        global $gen_db; // TestCommonで準備される

        // 品目
        $itemId = TestCommon::makeItem(array('tax_rate' => 5));

        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $tommorow = date('Y-m-d', strtotime('+1 day'));

        // 得意先
        $custId = TestCommon::makeCustomer(array('opening_balance' => 123.4567, 'opening_date' => $yesterday, 'rounding' => 'round', 'precision' => 2, 'bill_pattern' => 1));

        // 受注（本日）。同時に納品を登録
        $recId = TestCommon::makeReceived(array(
                    'customer_id' => $custId
                    , 'received_date' => $today
                    , 'dead_line' => $today
                    , 'item_id' => $itemId
                    , 'received_quantity' => 1
                    , 'product_price' => 100.1111
                    , 'delivery_regist' => 'true'));

        // 請求（本日締め）
        $this->object->makeBillData(0, $today, 0, array($custId), null);

        // 結果確認
        $query = "select before_amount, paying_in, sales_amount, tax_amount, bill_amount from bill_header where customer_id = '{$custId}'";
        $obj = $gen_db->queryOneRowObject($query);

        // 売掛残高初期値(123.4567) + 今回売上(100.11) + 税(5.01)
        //  税率 5%（品目作成時に指定している）
        $this->assertEquals(228.5767, $obj->bill_amount);   // 繰越
        // 以前はこちらが正解だったが、13i rev 20130730 で仕様が変わり、売掛残高初期値には丸めが効かなくなった。
        //   ag.cgi?page=ProjectDocView&pid=1516&did=180371
//        $this->assertEquals(228.58, $obj->bill_amount);   // 繰越

        // 受注（明日）。同時に納品を登録
        $recId = TestCommon::makeReceived(array(
                    'customer_id' => $custId
                    , 'received_date' => $tommorow
                    , 'dead_line' => $tommorow
                    , 'item_id' => $itemId
                    , 'received_quantity' => 1
                    , 'product_price' => 100.1111
                    , 'delivery_regist' => 'true'));

        // 請求（明日締め）
        $this->object->makeBillData(0, $tommorow, 0, array($custId), null);

        // 結果確認
        $query = "select before_amount, sales_amount, tax_amount, bill_amount from bill_header where customer_id = '{$custId}' and close_date='{$tommorow}'";
        $obj = $gen_db->queryOneRowObject($query);

        // 前回金額が繰り越される
        $this->assertEquals(228.5767, $obj->before_amount);
        // 以前はこちらが正解だったが、13i rev 20130730 で仕様が変わり、売掛残高初期値には丸めが効かなくなった。
        //   ag.cgi?page=ProjectDocView&pid=1516&did=180371
        // $this->assertEquals(228.58, $obj->before_amount);
        $this->assertEquals(100.11, $obj->sales_amount);
    }

// テスト
//  マルチ取引通貨・回収サイクル・残高繰越

    public function testGetLastCloseDateByCustomerId()
    {
        global $gen_db; // TestCommonで準備される
    }

}