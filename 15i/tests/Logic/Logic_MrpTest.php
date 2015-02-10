<?php

require_once dirname(__FILE__) . '/../TestCommon.class.php';
require_once dirname(__FILE__) . '/../../Logic/Mrp.class.php';

require_once(dirname(__FILE__) . "/../../Logic/Stock.class.php");
require_once(dirname(__FILE__) . "/../../Logic/Inout.class.php");
require_once(dirname(__FILE__) . "/../../Logic/SystemDate.class.php");
require_once(dirname(__FILE__) . "/../../Logic/SeibanChange.class.php");
require_once(dirname(__FILE__) . "/../../Logic/Bom.class.php");
require_once(dirname(__FILE__) . "/../../Logic/Reserve.class.php");
require_once(dirname(__FILE__) . "/../../Logic/Received.class.php");
require_once(dirname(__FILE__) . "/../../Logic/Achievement.class.php");
require_once(dirname(__FILE__) . "/../../Logic/Plan.class.php");
require_once(dirname(__FILE__) . "/../../Logic/Stock.class.php");
require_once(dirname(__FILE__) . "/../../Logic/Received.class.php");

class Logic_MrpTest extends PHPUnit_Framework_TestCase
{

    protected $object;

    protected function setUp()
    {
        $this->object = new Logic_Mrp;

        global $gen_db;
        $gen_db->begin();

        ini_set('date.timezone', 'Asia/Tokyo');

        $_SESSION['user_name'] = "test";

        // 準備 ● customer_master
        $query = "insert into customer_master (customer_no, customer_name, classification," .
                "zip, address1, address2, tel, fax, e_mail, person_in_charge, delivery_port," .
                "monthly_limit_date) values " .
                "('test_customer_no', 'test_customer_name', 0," .
                "'', '', '', '', '', '', '', ''," .
                "31)";
        $gen_db->query($query);
        $this->customer_id = $gen_db->getSequence("customer_master_customer_id_seq");

        // 準備 ● location_master
        $data = array(
            'location_code' => 'test_location_code',
            'location_name' => 'test_location_name',
            'customer_id' => $this->customer_id,
        );
        $gen_db->insert('location_master', $data);
        $this->location_id = $gen_db->getSequence("location_master_location_id_seq");

        // 準備 ● process_master
        $data = array(
            'process_code' => 'test_process_code',
            'process_name' => 'test_process_name',
            'equipment_name' => 'test_equipment_name',
                //'charge_price' => 100,
        );
        $gen_db->insert('process_master', $data);
        $this->process_id = $gen_db->getSequence("process_master_process_id_seq");

        // 準備 ● item_group_master
        $data = array(
            'item_group_code' => 'test_item_group_code',
            'item_group_name' => 'test_item_group_name',
        );
        $gen_db->insert('item_group_master', $data);
        $this->item_group_id = $gen_db->getSequence("item_group_master_item_group_id_seq");

        // 準備 ● トラン・品目マスタ全削除（コミットしないので大丈夫）
        $query = "delete from received_detail";
        $gen_db->query($query);
        $query = "delete from use_plan";
        $gen_db->query($query);
        $query = "delete from achievement";
        $gen_db->query($query);
        $query = "delete from accepted";
        $gen_db->query($query);
        $query = "delete from seiban_change";
        $gen_db->query($query);
        $query = "delete from item_in_out";
        $gen_db->query($query);
        $query = "delete from bom_master";
        $gen_db->query($query);
        $query = "delete from item_order_master";
        $gen_db->query($query);
        $query = "delete from item_master";
        $gen_db->query($query);
    }

    protected function tearDown()
    {
        global $gen_db;
        $gen_db->rollback();
    }

    var $customer_id;
    var $location_id;
    var $process_id;
    var $item_group_id;
    var $item_id;
    var $item_code;
    var $item_name;
    var $item_price;
    var $item_sub_code;
    var $order_header_id;
    var $order_detail_id;
    var $mrpFirst = true;
    var $bomFirst = true;

    // ******* 実行するテストのメソッド名のコメントアウトを取り除く ******
    //
    // ●一括実行。
    public function testCommon()
    {
        // ******* MRP ******
        var_dump("*** case_normal0");
        $this->case_normal0();    // ●MRPの基本パターン
        var_dump("*** case_change1");
        $this->case_change1();    // ●200708 改善1 受注残（納期が過ぎたが引当も納品もされていない受注）を考慮
        var_dump("*** case_change2");
        $this->case_change2();    // ●200708 改善2 計画を「需要計画」ではなく「生産(手配)計画」とみなすように変更
        var_dump("*** case_normal1");
        $this->case_normal1();    // ●MRP除外が効くことの確認
        var_dump("*** case_normal2");
        $this->case_normal2();    // ●同じ品目がツリーの複数個所に出てくるパターン。LTも考慮
        var_dump("*** case_normal3");
        $this->case_normal3();    // ●発注単位が効くことを確認
        var_dump("*** case_normal4");
        $this->case_normal4();    // ●小数点以下の数量を扱える
        // 2008専用
        //var_dump("*** case_2008_1"); $this->case_2008_1();      // ●引当横取り問題
        // 2009専用
        var_dump("*** case_2009_1");
        $this->case_2009_1();      // ●手配まるめ数（ロット単位）を２段階に
        // 2010専用
        var_dump("*** case_2010_1");
        $this->case_2010_1();      // ●可変リードタイム

        // ダミー品目関連
        var_dump("*** case_dummy_1");
        $this->case_dummy_1();

        // ******* Seiban ******

        var_dump("*** case_seiban_normal0");
        $this->case_seiban_normal0();  // ●製番引当の基本。引当されると子品目のオーダーも出ない
        var_dump("*** case_seiban_normal1");
        $this->case_seiban_normal1();  // ●製番在庫が一部既存のケース。子品目まで正確にオーダー差し引きされるか
        var_dump("*** case_seiban_normal2");
        $this->case_seiban_normal2();  // ●製番オーダーが一部発行済みのケース。子品目はオーダー差し引きされない
        var_dump("*** case_seiban_normal3");
        $this->case_seiban_normal3();  // ●ロケ考慮パターン。
        var_dump("*** case_seiban_normal4");
        $this->case_seiban_normal4();  // ●ロケ考慮パターン2。ロケごとの在庫数が異なる場合
        var_dump("*** case_seiban_normal5");
        $this->case_seiban_normal5();  // ●引当横取り問題が発生しないことの確認
        var_dump("*** case_seiban_normal6");
        $this->case_seiban_normal6();  // ●同じ品目がツリーの複数個所に出てくるパターン。LTも考慮
        var_dump("*** case_seiban_normal7");
        $this->case_seiban_normal7();  // ●MRP除外の設定は無効
        var_dump("*** case_seiban_normal8");
        $this->case_seiban_normal8();  // ●発注単位が効くことを確認
        var_dump("*** case_seiban_normal9");
        $this->case_seiban_normal9();  // ●小数点以下の数量が扱える

        var_dump("*** case_seiban_cybouz1");
        $this->case_seiban_cybouz1();
        var_dump("*** case_seiban_cybouz2");
        $this->case_seiban_cybouz2();
        var_dump("*** case_seiban_cybouz3");
        $this->case_seiban_cybouz3();
        var_dump("*** case_seiban_cybouz5");
        $this->case_seiban_cybouz5();
        var_dump("*** case_seiban_cybouz6");
        $this->case_seiban_cybouz6();
        var_dump("*** case_seiban_cybouz7");
        $this->case_seiban_cybouz7();
        var_dump("*** case_seiban_cybouz8");
        $this->case_seiban_cybouz8();

        var_dump("*** case_seiban_fujisawa1");
        $this->case_seiban_fujisawa1();
        var_dump("*** case_seiban_fujisawa2");
        $this->case_seiban_fujisawa2();
        var_dump("*** case_seiban_fujisawa3");
        $this->case_seiban_fujisawa3();
        var_dump("*** case_seiban_fujisawa4");
        $this->case_seiban_fujisawa4();

        // 2009専用
        var_dump("*** case_seiban_2009_1");
        $this->case_seiban_2009_1();
        // 2010専用
        var_dump("*** case_seiban_2010_1");
        $this->case_seiban_2010_1();
        var_dump("*** case_seiban_2010_2");
        $this->case_seiban_2010_2();
    }

    // ************************
    //  MRP tests
    // ************************
    //
    // ●MRPの基本パターン
    public function case_normal0()
    {
        // LT=1, SLT=1
        $itemId1 = $this->registItem("test_mrp_normal01", 1, 1);
        $itemId2 = $this->registItem("test_mrp_normal02", 1, 1);
        $itemId3 = $this->registItem("test_mrp_normal03", 1, 1);

        $this->registBom($itemId1, $itemId2, 1);
        $this->registBom($itemId2, $itemId3, 1);

        $nextMonth1 = date('Y-m-01', strtotime('+1 month'));
        $nextMonth10 = date('Y-m-10', strtotime('+1 month'));

        // 受注20個（翌月10日納期）
        $this->registReceived($itemId1, "normal0", 20, date('Y-m-d'), $nextMonth10);

        // 子品目 10個入庫（翌月1日）
        $this->registInout($itemId2, "in", $nextMonth1, 10);

        // MRP
        $this->doMrp($nextMonth10);

        // 各品目の結果が出るべき日（LT(第2引数)と休日を考慮）
        $item1date = $this->getAdjustDeadline($nextMonth10, 0, 1);  // 親品目の場合、納期はSLTのみ反映
        $item2date = $this->getAdjustDeadline($item1date, 1, 1);
        $item3date = $this->getAdjustDeadline($item2date, 1, 1);

        // 結果確認：親品目は受注どおりオーダー
        $this->checkMrpResult($itemId1, $item1date, "20");
        $this->checkMrpResult($itemId1, "", "20");  // 指定日以外に余分なオーダーがないことを確認
        // 結果確認：子品目はオーダー10個
        $this->checkMrpResult($itemId2, $item2date, "10");
        $this->checkMrpResult($itemId2, "", "10");  // 指定日以外に余分なオーダーがないことを確認
        // 結果確認：孫品目はオーダー10個
        $this->checkMrpResult($itemId3, $item3date, "10");
        $this->checkMrpResult($itemId3, "", "10");  // 指定日以外に余分なオーダーがないことを確認
    }

    // ●MRP除外が効くことの確認
    public function case_normal1()
    {
        // 親だけMRP除外に設定
        $itemId1 = $this->registItem("test_mrp_normal11", 0, 0, true);
        $itemId2 = $this->registItem("test_mrp_normal12", 0, 0, false);

        $this->registBom($itemId1, $itemId2, 1);

        $nextMonth1 = date('Y-m-01', strtotime('+1 month'));
        $nextMonth10 = date('Y-m-10', strtotime('+1 month'));

        // 受注20個（翌月1日納期）
        $this->registReceived($itemId1, "normal1", 20, date('Y-m-d'), $nextMonth10);

        // MRP
        $this->doMrp($nextMonth1);

        // 結果確認：MRP除外なので親子ともオーダーは出ない
        $this->checkMrpResult($itemId1, "", "0");
        $this->checkMrpResult($itemId2, "", "0");
    }

    // ●同じ品目がツリーの複数個所に出てくるパターン。LTも考慮
    public function case_normal2()
    {
        // LT=1, SLT=1
        $itemId1 = $this->registItem("test_mrp_normal21", 1, 1);
        $itemId2 = $this->registItem("test_mrp_normal22", 1, 1);
        $itemId3 = $this->registItem("test_mrp_normal23", 1, 1);

        // item1
        //  + item2 (×1)
        //  + item3 (×2)
        //     + item2 (×1)
        $this->registBom($itemId1, $itemId2, 1);
        $this->registBom($itemId1, $itemId3, 2);
        $this->registBom($itemId3, $itemId2, 1);

        $nextMonth1 = date('Y-m-01', strtotime('+1 month'));
        $nextMonth10 = date('Y-m-10', strtotime('+1 month'));

        // 受注10個（翌月10日納期）
        $this->registReceived($itemId1, "normal2", 10, date('Y-m-d'), $nextMonth10);

        // item2  5個入庫（翌月1日）
        $this->registInout($itemId2, "in", $nextMonth1, 5);

        // MRP
        $this->doMrp($nextMonth10);

        // 各品目の結果が出るべき日（LT(第2引数)と休日を考慮）
        $item1date = $this->getAdjustDeadline($nextMonth10, 0, 1);  // 親品目の場合、納期はSLTのみ反映
        $item21date = $this->getAdjustDeadline($item1date, 1, 1);
        $item3date = $this->getAdjustDeadline($item1date, 1, 1);
        $item22date = $this->getAdjustDeadline($item3date, 1, 1);

        // 結果確認：親品目は受注どおりオーダー
        $this->checkMrpResult($itemId1, $item1date, "10");
        $this->checkMrpResult($itemId1, "", "10");  // 指定日以外に余分なオーダーがないことを確認

        // 結果確認：item2（item1の下の分）
        $this->checkMrpResult($itemId2, $item21date, "10");

        // 結果確認：item2（item3の下の分。10×2個だが上の分より納期が早いため既存在庫の5個が使われる）
        $this->checkMrpResult($itemId2, $item22date, "15");
        $this->checkMrpResult($itemId2, "", "25");  // 指定日以外に余分なオーダーがないことを確認

        // 結果確認：item3
        $this->checkMrpResult($itemId3, $item3date, "20");
        $this->checkMrpResult($itemId3, "", "20");  // 指定日以外に余分なオーダーがないことを確認
    }

    // ●発注単位が効くことを確認
    public function case_normal3()
    {
        global $gen_db;

        $itemId1 = $this->registItem("test_mrp_normal31", 0, 0);

        // 発注単位を100にする
        $query = "update item_order_master set default_lot_unit=100 where item_id='{$itemId1}' and line_number=0";
        $gen_db->query($query);

        $nextMonth1 = date('Y-m-01', strtotime('+1 month'));

        // 受注5個
        $this->registReceived($itemId1, "normal3", 5, date('Y-m-d'), $nextMonth1);

        // MRP
        $this->doMrp($nextMonth1);

        // 結果確認：発注単位に丸めたオーダーが出る
        $this->checkMrpResult($itemId1, "", "100");
    }

    // ●小数点以下の数量を扱える
    public function case_normal4()
    {
        global $gen_db;

        $itemId1 = $this->registItem("test_mrp_normal41", 0, 0);

        $nextMonth1 = date('Y-m-01', strtotime('+1 month'));

        // 発注単位を0にする
        $query = "update item_order_master set default_lot_unit=0 where item_id='{$itemId1}' and line_number=0";
        $gen_db->query($query);

        // 受注0.0001個 × 3
        $this->registReceived($itemId1, "normal41", 0.0001, date('Y-m-d'), $nextMonth1);
        $this->registReceived($itemId1, "normal42", 0.0001, date('Y-m-d'), $nextMonth1);
        $this->registReceived($itemId1, "normal43", 0.0001, date('Y-m-d'), $nextMonth1);

        // フリー在庫を0.0001個つくる
        $this->makeStock($itemId1, 0.0001);

        // MRP
        $this->doMrp($nextMonth1);

        // 結果確認：オーダーは0.0002（0.0001 × 3 - 0.0001）
        $this->checkMrpResult($itemId1, "", "0.0002");
    }

    // ●200708 改善1 受注残（納期が過ぎたが引当も納品もされていない受注）を考慮
    //  ※ホントは「納品済み・完了フラグオン・予約」の受注が含まれないこともテストしたほうがいい
    public function case_change1()
    {
        $itemId1 = $this->registItem("test_mrp_change1", 0, 0);
        $itemId2 = $this->registItem("test_mrp_change2", 0, 0);

        // 受注10個（納期は昨日 - 納期遅れ）
        $this->registReceived($itemId1, "item1", 10, date('Y-m-d', strtotime("-2 day")), date('Y-m-d', strtotime("-1 day")));
        // 受注10個、引当10個（納期は昨日 - 納期遅れ）
        $this->registInout($itemId2, "in", date('Y-m-d', strtotime("-2 day")), 10);
        $this->registReceived($itemId2, "item2", 10, date('Y-m-d', strtotime("-2 day")), date('Y-m-d', strtotime("-1 day")), 10);

        // MRP
        $this->doMrp(date('Y-m-d', strtotime("+2 day")));

        // 結果確認： 納期遅れ分もオーダーが出る
        $this->checkMrpResult($itemId1, date('Y-m-d', strtotime("+1 day")), "10");
        $this->checkMrpResult($itemId1, "", "10");  // 指定日以外に余分なオーダーがないことを確認
        // 結果確認： 引当済みならオーダーは出ない
        $this->checkMrpResult($itemId2, "", "0");
    }

    // ●200708 改善2 計画を「需要計画」ではなく「生産(手配)計画」とみなすように変更
    public function case_change2()
    {
        $itemId1 = $this->registItem("test_mrp_change1", 0, 0);
        $itemId2 = $this->registItem("test_mrp_change2", 0, 0);
        $itemId3 = $this->registItem("test_mrp_change3", 0, 0);
        $itemId4 = $this->registItem("test_mrp_change4", 1, 1);     // LTを設定
        $itemId41 = $this->registItem("test_mrp_change41", 1, 1);   // LTを設定

        // ***** パターン1 （在庫があっても計画通りのオーダーが出る） *****
        // フリー在庫を10個つくる
        $this->registInout($itemId1, "in", date('Y-m-d', strtotime("-2 day")), 10);
        // 計画10個（納期は明日）
        $this->registPlan($itemId1, "item_c21", 10, date('Y-m-d', strtotime("+1 day")));

        // ***** パターン2 （計画分は勝手に受注に使われる）*****
        // 計画10個（納期は明日）
        $this->registPlan($itemId2, "item_c221", 10, date('Y-m-d', strtotime("+1 day")));
        // 受注10個（納期は2日後）
        $this->registReceived($itemId2, "item_c222", 10, date('Y-m-d'), date('Y-m-d', strtotime("+2 day")));

        // ***** パターン3 （再MRPしたとき、オーダー発行済み分は出ない）*****
        // 計画10個（納期は明日）
        $this->registPlan($itemId3, "item_c23", 10, date('Y-m-d', strtotime("+1 day")));

        // ***** パターン4 （計画と同日に受注ベースの既存オーダーがあっても、それとは別に計画分オーダーが出る）*****
        // 受注100個（納期は3日後）　※ item4はLT設定済み。LTがあっても問題なく動作することを確認
        $this->registReceived($itemId4, "item_c241", 100, date('Y-m-d'), date('Y-m-d', strtotime("+3 day")));
        // 受注5個（納期は4日後） - 別の日のオーダーは影響しないことを確認するため
        $this->registReceived($itemId4, "item_c242", 5, date('Y-m-d'), date('Y-m-d', strtotime("+4 day")));
        // 別品目の受注10個（納期は3日後）- 別品目のオーダーは影響しないことを確認するため
        $this->registReceived($itemId41, "item_c243", 10, date('Y-m-d'), date('Y-m-d', strtotime("+3 day")));


        // ●MRP
        $this->doMrp(date('Y-m-d', strtotime("+5 day")));

        // ***** パターン1 *****
        // 結果確認： 従来ならフリー在庫があるのでオーダーは出なかったが、修正後は出る（在庫にかかわらず計画がそのままオーダー）
        $this->checkMrpResult($itemId1, date('Y-m-d', strtotime("+1 day")), "10");
        $this->checkMrpResult($itemId1, "", "10");  // 指定日以外に余分なオーダーがないことを確認

        // ***** パターン2 *****
        // 結果確認： 従来なら計画と受注それぞれ10のオーダーが出ていたが、修正後は計画の10のみ（受注にはその10が使用される）
        $this->checkMrpResult($itemId2, "", "10");  // 指定日以外に余分なオーダーがないことを確認

        // ***** パターン3 *****
        // 結果確認： この段階ではふつうにオーダーが出る
        $this->checkMrpResult($itemId3, date('Y-m-d', strtotime("+1 day")), "10");

        // （パターン3・4用）結果オーダーを発行（製造指示書）
        Logic_Order::mrpToOrder(1);

        // （パターン4用）計画150個（納期は3日後）
        $this->registPlan($itemId4, "item_c25", 150, date('Y-m-d', strtotime("+3 day")));

        // ●もっかいMRP
        $this->doMrp(date('Y-m-d', strtotime("+5 day")));

        // ***** パターン3 - 再MRP後 *****
        // 結果確認： いったん指示書が発行された計画について、再度オーダーが出ていないことを確認
        $this->checkMrpResult($itemId3, "", "0");

        // ***** パターン4 - 再MRP後 *****
        // 結果確認： 同日に受注ベースの既存オーダーがあっても、それとは別に計画分のオーダーが出ていることを確認
        //    最初のMRPで受注分の100のオーダーが出ていても、計画どおりのオーダーが出る（150）
        //    また別の日のオーダーや、同日の別品目のオーダーが影響しないことも確認
        $this->checkMrpResult($itemId4, "", "150");
    }

    // ●引当横取り問題（2008）
    // 2007で存在していた問題。
    // 受注引当していても、引当日より前に需要があるとそちらで使用されてしまい、
    // 引当日にあらためてオーダーが出る。これでは引当の意味がない。
    // 修正案でも次の問題が。
    // 計算開始日より後の入庫に対して引当が行われていた場合、計算開始日の時点で
    // 引当数分のオーダーが出てしまう。
    // 例：在庫0、4/2に100入庫、4/3の受注に100を引当　で 4/1にMRPをまわすと、
    // 4/1に100のオーダーが出てしまう。
    public function case_2008_1()
    {
        $itemId1 = $this->registItem("test_mrp_2008_01", 0, 0);

        $nextMonth3 = date('Y-m-03', strtotime('+1 month'));
        $nextMonth4 = date('Y-m-04', strtotime('+1 month'));

        // 10個入庫（今日）
        // ※ 2008で、受注引当は本日時点で在庫がないと行えないようになった。
        //      したがって今日付けで入庫とする必要がある。
        $this->registInout($itemId1, "in", date('Y-m-d'), 10);

        // 受注10個（翌月4日納期）10個引当
        $this->registReceived($itemId1, "2008_01", 10, date('Y-m-d'), $nextMonth4, 10);

        // 飛び込みの受注10個（翌月3日納期）
        $this->registReceived($itemId1, "2008_02", 10, date('Y-m-d'), $nextMonth3);

        // MRP
        $this->doMrp($nextMonth4);

        // 結果確認：3日にオーダーが出る（4日でも、計算開始日でもなく）
        $this->checkMrpResult($itemId1, $nextMonth3, "10");
        $this->checkMrpResult($itemId1, "", "10");  // 指定日以外に余分なオーダーがないことを確認
    }

    // ●手配まるめ数（ロット単位）を２段階に（2009）
    public function case_2009_1()
    {
        $nextMonth1 = date('Y-m-01', strtotime('+1 month'));

        // まるめ単位1が有効なパターン
        $itemId1 = $this->registItem("test_mrp_2009_01", 0, 0);
        $this->setLotUnit($itemId1, 10, 10, 0);     // まるめ単位1, まるめ単位1の上限, まるめ単位2
        $this->registReceived($itemId1, "2009_01", 9, $nextMonth1, $nextMonth1, 0);
        $answer1 = 10;

        // まるめ単位2が有効なパターン（まるめ単位1の上限までは、まるめ単位1を適用）
        $itemId2 = $this->registItem("test_mrp_2009_02", 0, 0);
        $this->setLotUnit($itemId2, 40, 80, 9);     // まるめ単位1, まるめ単位1の上限, まるめ単位2
        $this->registReceived($itemId2, "2009_02", 81, $nextMonth1, $nextMonth1, 0);
        $answer2 = 89;

        // まるめなし
        $itemId3 = $this->registItem("test_mrp_2009_03", 0, 0);
        $this->setLotUnit($itemId3, 0, 0, 0);     // まるめ単位1, まるめ単位1の上限, まるめ単位2
        $this->registReceived($itemId3, "2009_03", 0.5, $nextMonth1, $nextMonth1, 0);
        $answer3 = 0.5;

        // まるめ単位2のみ、まるめなし
        $itemId4 = $this->registItem("test_mrp_2009_04", 0, 0);
        $this->setLotUnit($itemId4, 10, 30, 0);     // まるめ単位1, まるめ単位1の上限, まるめ単位2
        $this->registReceived($itemId4, "2009_04", 31, $nextMonth1, $nextMonth1, 0);
        $answer4 = 31;

        // 子品目へのまるめ引継ぎ
        $itemId5p = $this->registItem("test_mrp_2009_05p", 0, 0);
        $itemId5 = $this->registItem("test_mrp_2009_05", 0, 0);
        $this->registBom($itemId5p, $itemId5, 1);
        $this->setLotUnit($itemId5p, 10, 10, 20);     // 親：受注11に対し、まるめで30になる（最初の10 + 残りの1が丸まって20）
        $this->setLotUnit($itemId5, 1, 20, 50);       // 子：従属需要30に対し、まるめで70になる（最初の20 + 残りの10が丸まって50）
        $this->registReceived($itemId5p, "2009_05", 11, $nextMonth1, $nextMonth1, 0);
        $answer5 = 70;

        // まるめ1上限およびまるめ単位2が未設定
        $itemId6 = $this->registItem("test_mrp_2009_06", 0, 0);
        $this->setLotUnit($itemId6, 10, "null", "null");       // まるめ単位1, まるめ単位1の上限, まるめ単位2
        $this->registReceived($itemId6, "2009_06", 11, $nextMonth1, $nextMonth1, 0);
        $answer6 = 20;

        // MRP
        $this->doMrp($nextMonth1);

        // 結果確認
        $this->checkMrpResult($itemId1, $nextMonth1, $answer1);
        $this->checkMrpResult($itemId2, $nextMonth1, $answer2);
        $this->checkMrpResult($itemId3, $nextMonth1, $answer3);
        $this->checkMrpResult($itemId4, $nextMonth1, $answer4);
        $this->checkMrpResult($itemId5, $nextMonth1, $answer5);
        $this->checkMrpResult($itemId6, $nextMonth1, $answer6);
    }

    // ●可変リードタイム（2010）
    public function case_2010_1()
    {
        global $gen_db;

        $deadline = date('Y-m-d', strtotime('+10 days'));

        // 受注300、LT:空欄、工程1LT:空欄、工程1製造能力:100、工程2LT:1（可変LTの工程と固定LTの工程が混在）
        $itemId1 = $this->registItem("test_mrp_2010_1_nolt", null, 0);
        $query = "insert into item_process_master(item_id, process_id, machining_sequence, default_work_minute, pcs_per_day, charge_price, process_lt)
         values ($itemId1, 0, 0, 0, 100, 0, null)";
        $gen_db->query($query);
        $query = "insert into item_process_master(item_id, process_id, machining_sequence, default_work_minute, pcs_per_day, charge_price, process_lt)
         values ($itemId1, 0, 1, 0, 0, 0, 1)";
        $gen_db->query($query);
        $childItemId1 = $this->registItem("test_mrp_2010_1_nolt_child", 0, 0);
        $this->registBom($itemId1, $childItemId1, 1);
        $this->registReceived($itemId1, "2010_1_nolt", 300, $deadline, $deadline, 0);

        // 受注300、LT:2（LT固定）
        $itemId2 = $this->registItem("test_mrp_2010_1_lt", 2, 0);
        $childItemId2 = $this->registItem("test_mrp_2010_1_lt_child", 0, 0);
        $this->registBom($itemId2, $childItemId2, 1);
        $this->registReceived($itemId2, "2010_1_lt", 300, $deadline, $deadline, 0);

        // 受注300、LT:空欄、工程1LT:空欄、工程1製造能力:1、工程2LT:1（可変LTの工程と固定LTの工程が混在、かなりLTが大きくなるパターン）
        $itemId3 = $this->registItem("test_mrp_2010_1_longlt", null, 0);
        $query = "insert into item_process_master(item_id, process_id, machining_sequence, default_work_minute, pcs_per_day, charge_price, process_lt)
         values ($itemId3, 0, 0, 0, 1, 0, null)";
        $gen_db->query($query);
        $query = "insert into item_process_master(item_id, process_id, machining_sequence, default_work_minute, pcs_per_day, charge_price, process_lt)
         values ($itemId3, 0, 1, 0, 0, 0, 1)";
        $gen_db->query($query);
        $childItemId3 = $this->registItem("test_mrp_2010_1_longlt_child", 0, 0);
        $this->registBom($itemId3, $childItemId3, 1);
        $this->registReceived($itemId3, "2010_1_longlt", 300, $deadline, $deadline, 0);

        // MRP
        $this->doMrp($deadline);

        // 結果確認
        $this->checkMrpResult($childItemId1, $this->getAdjustDeadline($deadline, 3, 0), 300);    // LT=3： (300(受注) ÷ 100(工程1製造能力)) -1 + 1(工程2LT)
        $this->checkMrpResult($childItemId2, $this->getAdjustDeadline($deadline, 2, 0), 300);    // LT=2： 固定LT
        $this->checkMrpResult($childItemId3, date('Y-m-d', strtotime('+1 day')), 300);          // アラーム
    }
    
    // ●納期遅れになったダミー品目の受注が所要量計算に加味されない不具合
    //  ag.cgi?page=ProjectDocView&pPID=1516&pBID=178129
    //  15iで解消
    public function case_dummy_1() {
        global $gen_db;
        
        // ダミー品目をつくる
        $itemId_dummy = $this->registItem("test_dummy_1", 0, 0);
        $gen_db->query("update item_master set dummy_item = true where item_id = '{$itemId_dummy}'");
        
        // 子品目
        $itemId_child = $this->registItem("test_dummy_1", 0, 0);
        $this->registBom($itemId_dummy, $itemId_child, 1);
        
        // ダミー品目に受注（納期遅れ）
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $this->registReceived($itemId_dummy, "", 10, $yesterday, $yesterday);
        
        // 所要量計算
        $this->doMrp(date('Y-m-d', strtotime('+1 day')));

        // 結果確認： 子品目のオーダーが出る
        $this->checkMrpResult($itemId_child, "", "10");
    }

    // ************************
    //  SEIBAN tests
    // ************************
    //
    // ●製番引当の基本パターン。引当されると子品目のオーダーも出ない
    public function case_seiban_normal0()
    {
        $itemId1 = $this->seiban_registItem("test_seiban_normal1", 0, 0);
        $itemId2 = $this->seiban_registItem("test_seiban_normal2", 0, 0);
        $itemId3 = $this->seiban_registItem("test_seiban_normal3", 0, 0);

        $this->seiban_registBom($itemId1, $itemId2, 1);
        $this->seiban_registBom($itemId2, $itemId3, 1);

        // 受注20個（翌月1日納期）
        $date = date('Y-m-01', strtotime('+1 month'));
        $this->seiban_registReceived($itemId1, "seiban_normal0", 20, date('Y-m-d'), $date);

        // 子品目のフリー製番在庫を10個つくる
        $this->seiban_makeSeibanStock($itemId2, "", 10);

        // MRP
        $this->seiban_doMrp($date);

        // 結果確認：親品目は受注どおりオーダー（引当ではないことも確認）
        $this->seiban_checkMrpResult($itemId1, "seiban_normal0", "", true, true, "0");
        $this->seiban_checkMrpResult($itemId1, "seiban_normal0", "", true, false, "20");

        // 結果確認：子品目は製番引当10個、オーダー10個
        $this->seiban_checkMrpResult($itemId2, "seiban_normal0", "", true, true, "10");
        $this->seiban_checkMrpResult($itemId2, "seiban_normal0", "", true, false, "10");

        // 結果確認：孫品目はオーダー10個
        $this->seiban_checkMrpResult($itemId3, "seiban_normal0", "", true, true, "0");
        $this->seiban_checkMrpResult($itemId3, "seiban_normal0", "", true, false, "10");
    }

    // ●製番在庫が一部既存のケース。子品目まで正確にオーダー差し引きされるか
    public function case_seiban_normal1()
    {
        $itemId1 = $this->seiban_registItem("test_seiban_normal1", 0, 0);
        $itemId2 = $this->seiban_registItem("test_seiban_normal2", 0, 0);
        $itemId3 = $this->seiban_registItem("test_seiban_normal3", 0, 0);

        $this->seiban_registBom($itemId1, $itemId2, 1);
        $this->seiban_registBom($itemId2, $itemId3, 1);

        // 受注20個（翌月1日納期）
        $date = date('Y-m-01', strtotime('+1 month'));  // 翌月10日
        $this->seiban_registReceived($itemId1, "seiban_normal1", 20, date('Y-m-d'), $date);

        // 子品目の製番在庫を10個つくる
        $this->seiban_makeSeibanStock($itemId2, "seiban_normal1", 10);

        // MRP
        $this->seiban_doMrp($date);

        // 結果確認：親品目は受注どおりオーダー（引当ではないことも確認）
        $this->seiban_checkMrpResult($itemId1, "seiban_normal1", "", true, true, "0");
        $this->seiban_checkMrpResult($itemId1, "seiban_normal1", "", true, false, "20");

        // 結果確認：子品目は製番在庫が既存のため、その分オーダーが少なく出る
        $this->seiban_checkMrpResult($itemId2, "seiban_normal1", "", true, true, "0");
        $this->seiban_checkMrpResult($itemId2, "seiban_normal1", "", true, false, "10");

        // 結果確認：孫品目も、オーダーが少なく出る
        $this->seiban_checkMrpResult($itemId3, "seiban_normal1", "", true, true, "0");
        $this->seiban_checkMrpResult($itemId3, "seiban_normal1", "", true, false, "10");
    }

    // ●製番オーダーが一部発行済みのケース。子品目はオーダー差し引きされない
    public function case_seiban_normal2()
    {
        $itemId1 = $this->seiban_registItem("test_seiban_normal21", 0, 0);
        $itemId2 = $this->seiban_registItem("test_seiban_normal22", 0, 0);
        $itemId3 = $this->seiban_registItem("test_seiban_normal23", 0, 0);

        // 親品目に受注20個（翌月1日納期）
        $date = date('Y-m-01', strtotime('+1 month'));  // 翌月10日
        $this->seiban_registReceived($itemId1, "seiban_normal2", 20, date('Y-m-d'), $date);

        // MRP
        $this->seiban_doMrp($date);

        // 結果オーダーを発行（親品目の製造指示書）
        Logic_Order::mrpToOrder(1);

        // 子品目・孫品目を作る
        $this->seiban_registBom($itemId1, $itemId2, 1);
        $this->seiban_registBom($itemId2, $itemId3, 1);

        // もっかいMRP
        $this->seiban_doMrp($date);

        // 結果確認：親品目はオーダーが出ない
        $this->seiban_checkMrpResult($itemId1, "", "", false, false, "0");

        // 結果確認：子品目はオーダーどおり出る
        $this->seiban_checkMrpResult($itemId2, "seiban_normal2", "", true, false, "20");

        // 結果確認：孫品目もオーダーどおり出る
        $this->seiban_checkMrpResult($itemId3, "seiban_normal2", "", true, false, "20");
    }

    // ●ロケ考慮パターン。フリー在庫がロケ別にある場合、ロケ別に引当が出る
    public function case_seiban_normal3()
    {
        $itemId1 = $this->seiban_registItem("test_seiban_normal31", 0, 0);
        $itemId2 = $this->seiban_registItem("test_seiban_normal32", 0, 0);

        $this->seiban_registBom($itemId1, $itemId2, 1);

        $partnerId1 = $this->seiban_registCustomer("test_seiban_cust31", true);

        $locId1 = $this->seiban_registLocation("test_seiban_loc31");
        $locId_partner = $this->seiban_registLocation("test_seiban_loc32", $partnerId1);  // Pロケ

        // 子品目のフリー製番在庫を各ロケに10個ずつつくる
        $this->seiban_makeSeibanStock($itemId2, "", 10);  // 規定ロケ
        $this->seiban_makeSeibanStock($itemId2, "", 10, $locId1);  // ロケ1
        $this->seiban_makeSeibanStock($itemId2, "", 10, $locId_partner);  // Pロケ

        // 受注30個（翌月5日納期）
        $date = date('Y-m-5', strtotime('+1 month'));
        $received_seiban = "seiban_normal3";
        $this->seiban_registReceived($itemId1, $received_seiban, 30, date('Y-m-d'), $date);

        // MRP
        $this->seiban_doMrp($date);

        // 自動引当
        Logic_SeibanChange::mrpToSeibanChange();

        // 結果確認：親品目は受注どおりオーダー
        $this->seiban_checkMrpResult($itemId1, $received_seiban, "", true, true, "0");    // 引当
        $this->seiban_checkMrpResult($itemId1, $received_seiban, "", true, false, "30");  // オーダー

        // 結果確認：子品目は規定ロケから10、ロケ1から10の引当。Pロケ分は引当しない
        $this->seiban_checkMrpResult($itemId2, $received_seiban, "", true, true, "20");   // 引当
        $this->seiban_checkMrpResult($itemId2, $received_seiban, "", true, false, "10");  // オーダー

        $this->seiban_checkSeibanChange($itemId2, 0, "", $received_seiban, 10);
        $this->seiban_checkSeibanChange($itemId2, $locId1, "", $received_seiban, 10);
        $this->seiban_checkSeibanChange($itemId2, $locId_partner, "", $received_seiban, 0);
    }

    // ●ロケ考慮パターン2。ロケごとの在庫数が異なる場合
    public function case_seiban_normal4()
    {
        $itemId1 = $this->seiban_registItem("test_seiban_normal41", 0, 0);

        $locId1 = $this->seiban_registLocation("test_seiban_loc41");
        $locId2 = $this->seiban_registLocation("test_seiban_loc42");

        // フリー製番在庫をつくる
        $this->seiban_makeSeibanStock($itemId1, "", 10, $locId1);  // ロケ1
        $this->seiban_makeSeibanStock($itemId1, "", 100, $locId2);  // ロケ2

        // 受注30個（翌月5日納期）
        $date = date('Y-m-5', strtotime('+1 month'));
        $received_seiban = "normal4";
        $this->seiban_registReceived($itemId1, $received_seiban, 30, date('Y-m-d'), $date);

        // MRP
        $this->seiban_doMrp($date);

        // 自動引当
        Logic_SeibanChange::mrpToSeibanChange();

        // 結果確認：ロケの振り分けはともかく、トータルで30の引当が出る
        $this->seiban_checkMrpResult($itemId1, $received_seiban, "", true, true, "30");   // 引当
        $this->seiban_checkMrpResult($itemId1, $received_seiban, "", true, false, "0");  // オーダー
    }

    // ●引当横取り問題が発生しないことの確認
    public function case_seiban_normal5()
    {
        $itemId1 = $this->seiban_registItem("test_seiban_p1", 0, 0);

        // フリー製番在庫を10個つくる
        $this->seiban_makeSeibanStock($itemId1, "", 10);

        // 受注10個（翌月10日納期）
        $date = date('Y-m-10', strtotime('+1 month'));
        $this->seiban_registReceived($itemId1, "seiban_normal51", 10, date('Y-m-d'), $date);

        // MRP
        $this->seiban_doMrp($date);

        // 自動引当
        Logic_SeibanChange::mrpToSeibanChange();

        $this->seiban_checkMrpResult($itemId1, "seiban_normal51", "", true, true, "10");

        // 受注10個（翌月5日納期）
        $date = date('Y-m-5', strtotime('+1 month'));
        $this->seiban_registReceived($itemId1, "seiban_normal52", 10, date('Y-m-d'), $date);

        // MRP
        $this->seiban_doMrp($date);

        // 結果確認：あとの受注に再度引当が出ていたらおかしい
        $this->seiban_checkMrpResult($itemId1, "seiban_normal52", "", true, true, "");
        $this->seiban_checkMrpResult($itemId1, "seiban_normal52", "", true, false, "10");
    }

    // ●同じ品目がツリーの複数個所に出てくるパターン。LTも考慮
    public function case_seiban_normal6()
    {
        // LT=1, SLT=1
        $itemId1 = $this->seiban_registItem("test_mrp_normal61", 1, 1);
        $itemId2 = $this->seiban_registItem("test_mrp_normal62", 1, 1);
        $itemId3 = $this->seiban_registItem("test_mrp_normal63", 1, 1);

        // item1
        //  + item2 (×1)
        //  + item3 (×2)
        //     + item2 (×1)
        $this->seiban_registBom($itemId1, $itemId2, 1);
        $this->seiban_registBom($itemId1, $itemId3, 2);
        $this->seiban_registBom($itemId3, $itemId2, 1);

        $nextMonth1 = date('Y-m-01', strtotime('+1 month'));
        $nextMonth10 = date('Y-m-10', strtotime('+1 month'));

        // 受注10個（翌月10日納期）
        $this->seiban_registReceived($itemId1, "seiban_normal6", 10, date('Y-m-d'), $nextMonth10);

        // item2  5個入庫（翌月1日）
        $this->seiban_registInout($itemId2, "in", $nextMonth1, 5);

        // MRP
        $this->seiban_doMrp($nextMonth10);

        // 各品目の結果が出るべき日（LT(第2引数)と休日を考慮）
        $item1date = $this->seiban_getAdjustDeadline($nextMonth10, 0, 1);  // 親品目の場合、納期はSLTのみ反映
        $item21date = $this->seiban_getAdjustDeadline($item1date, 1, 1);
        $item3date = $this->seiban_getAdjustDeadline($item1date, 1, 1);
        $item22date = $this->seiban_getAdjustDeadline($item3date, 1, 1);

        // 結果確認：親品目は受注どおりオーダー
        $this->seiban_checkMrpResult($itemId1, "", $item1date, false, false, "10");
        $this->seiban_checkMrpResult($itemId1, "", "", false, false, "10");  // 指定日以外に余分なオーダーがないことを確認
        // 結果確認：item2（item1の下の分）
        // ※以前はここで10のオーダーが出ていた。既存在庫の5個はここではなく、より納期の早いほう（この下で結果確認している
        //   item3の下のitem2のほう）に使われていた。
        //   つまり既存在庫は、より納期が早いほうのオーダーの消しこみに使われる仕様だった。
        //   しかし現行のプログラムでは、納期の早いほうではなく、階層が上のオーダーが先に消しこまれる。
        //   それでここは10ではなく5が出る。
        //   以前の動作のほうが妥当だったが、case_cybouz7 のバグ修正の際にここを変更しなければどうしても
        //   解決できず、こうなってしまった。
        $this->seiban_checkMrpResult($itemId2, "", $item21date, false, false, "5");

        // 結果確認：item2（item3の下の分。10×2個だが上の分より納期が早いため既存在庫の5個が使われる）
        $this->seiban_checkMrpResult($itemId2, "", $nextMonth1, true, true, "5");  // 入庫日に引当
        // ※以前は15だった。この上の長文コメント参照。
        $this->seiban_checkMrpResult($itemId2, "", $item22date, true, false, "20");
        $this->seiban_checkMrpResult($itemId2, "", "", false, false, "30");  // 指定日以外に余分なオーダーがないことを確認
        // 結果確認：item3
        $this->seiban_checkMrpResult($itemId3, "", $item3date, false, false, "20");
        $this->seiban_checkMrpResult($itemId3, "", "", false, false, "20");  // 指定日以外に余分なオーダーがないことを確認
    }

    // ●MRP除外の設定は無効
    public function case_seiban_normal7()
    {
        // MRP除外に設定
        $itemId1 = $this->seiban_registItem("test_mrp_normal71", 0, 0, true);
        $itemId2 = $this->seiban_registItem("test_mrp_normal72", 0, 0, true);

        $this->seiban_registBom($itemId1, $itemId2, 1);

        $nextMonth1 = date('Y-m-01', strtotime('+1 month'));

        // 受注20個（翌月1日納期）
        $this->seiban_registReceived($itemId1, "seiban_normal7", 20, date('Y-m-d'), $nextMonth1);

        // MRP
        $this->seiban_doMrp($nextMonth1);

        // 結果確認：MRP除外でもオーダーは出る
        $this->seiban_checkMrpResult($itemId1, "", "", false, false, "20");
        $this->seiban_checkMrpResult($itemId2, "", "", false, false, "20");
    }

    // ●発注単位が効くことを確認
    public function case_seiban_normal8()
    {
        global $gen_db;

        $itemId1 = $this->seiban_registItem("test_mrp_normal81", 0, 0);

        // 発注単位を100にする
        $query = "update item_order_master set default_lot_unit=100 where item_id='$itemId1' and line_number=0";
        $gen_db->query($query);

        $nextMonth1 = date('Y-m-01', strtotime('+1 month'));

        // 受注5個
        $this->seiban_registReceived($itemId1, "seiban_normal8", 5, date('Y-m-d'), $nextMonth1);

        // MRP
        $this->seiban_doMrp($nextMonth1);

        // 結果確認：発注単位に丸めたオーダーが出る
        $this->seiban_checkMrpResult($itemId1, "", "", false, false, "100");
    }

    // ●小数点以下の数量を扱える
    public function case_seiban_normal9()
    {
        global $gen_db;

        $itemId1 = $this->seiban_registItem("test_mrp_normal91", 0, 0);

        $nextMonth1 = date('Y-m-01', strtotime('+1 month'));

        // 発注単位を0にする
        $query = "update item_order_master set default_lot_unit=0 where item_id='$itemId1' and line_number=0";
        $gen_db->query($query);

        // 受注0.0001個 × 3
        $this->seiban_registReceived($itemId1, "seiban_normal91", 0.0001, date('Y-m-d'), $nextMonth1);
        $this->seiban_registReceived($itemId1, "seiban_normal92", 0.0001, date('Y-m-d'), $nextMonth1);
        $this->seiban_registReceived($itemId1, "seiban_normal93", 0.0001, date('Y-m-d'), $nextMonth1);

        // フリー製番在庫を0.0001個つくる
        $this->seiban_makeSeibanStock($itemId1, "", 0.0001);

        // MRP
        $this->seiban_doMrp($nextMonth1);

        // 結果確認：オーダーは0.0002（0.0001 × 3 - 0.0001）、引当は0.0001
        $this->seiban_checkMrpResult($itemId1, "", "", true, false, "0.0002");
        $this->seiban_checkMrpResult($itemId1, "", "", true, true, "0.0001");
    }

    // 実装が難しいので見送った
    //        // ●2008i 受注残（納期が過ぎたが引当も納品もされていない受注）を考慮
    //        //  ※ホントは「納品済み・完了フラグオン・予約」の受注が含まれないこともテストしたほうがいい
    //        public function case_seiban_2008_1()
    //        {
    //            $itemId1 = $this->seiban_registItem("test_2008_1_1", 0, 0);
    //            $itemId2 = $this->seiban_registItem("test_2008_1_2", 0, 0);
    //
    //            // 受注10個（納期は昨日 - 納期遅れ）
    //            $this->seiban_registReceived($itemId1, "2008_1_1", 10, date('Y-m-d', strtotime("-2 day")), date('Y-m-d', strtotime("-1 day")));
    //            // 受注10個、引当10個（納期は昨日 - 納期遅れ）
    //            $this->seiban_registInout($itemId2, "in", date('Y-m-d', strtotime("-2 day")), 10);
    //            $this->seiban_registReceived($itemId2, "2008_1_2", 10, date('Y-m-d', strtotime("-2 day")), date('Y-m-d', strtotime("-1 day")) ,10);
    //
    //            // MRP
    //            $this->seiban_doMrp(date('Y-m-d', strtotime("+2 day")));
    //
    //            // 結果確認： 納期遅れ分もオーダーが出る
    //            $this->seiban_checkMrpResult($itemId1, "", date('Y-m-d', strtotime("+1 day")), false, false, "10");
    //            $this->seiban_checkMrpResult($itemId1, "", "", false, false, "10");  // 指定日以外に余分なオーダーがないことを確認
    //            // 結果確認： 引当済みならオーダーは出ない
    //            $this->seiban_checkMrpResult($itemId2, "", "", false, false, "0");
    //        }
    //
    // ******** 以下、過去にサイボウズで報告された問題パターン *******
    //
    // ●「070530　自動引当機能追加後の所要量計算」
    //        　ばいく(内製)
    //        　　｜?たいや(発注)(フリー在庫：80個)
    //        (ばいく：たいや＝１：１)
    //
    //        まず、5/30に10個受注(納期：6/6、製番：100)します。MRP計算後、指示データ＆自動引当します。この時、たいやの在庫は70個になります。
    //
    //        次に、同日5/30に80個受注(納期：7/11、製番：200)します。MRP計算後、結果を見てみると、先に引当てたはずの製番100のたいやが、再度引当対象になっています。
    //        また、製番200に関しては、たいやの在庫が70個あるのに、引当数：50個、発注数：30個となってしまいます。
    //        ※その後のバグ修正で、再引当はなくなったが、フリーが70あるのに60しか引き当てない現象が発生するようになった。
    public function case_seiban_cybouz1()
    {
        $itemId_p = $this->seiban_registItem("test_seiban_cybouz11", 0, 0);
        $itemId_c = $this->seiban_registItem("test_seiban_cybouz12", 0, 0);

        $this->seiban_registBom($itemId_p, $itemId_c, 1);

        // 子品目の製番フリー在庫を80個つくる
        $this->seiban_makeSeibanStock($itemId_c, "", 80);

        // 親品目の受注10個（翌月10日納期）
        $date = date('Y-m-10', strtotime('+1 month'));  // 翌月10日
        $this->seiban_registReceived($itemId_p, "seiban_s1", 10, date('Y-m-d'), $date);

        // MRP
        $this->seiban_doMrp($date);

        // 親品目：指示書発行（10）
        Logic_Order::mrpToOrder(1);

        // 子品目：自動引当（10）
        Logic_SeibanChange::mrpToSeibanChange();

        // 受注80個（翌々月10日納期）
        $date = date('Y-m-10', strtotime('+2 month'));
        $this->seiban_registReceived($itemId_p, "seiban_s2", 80, date('Y-m-d'), $date);

        // MRP
        $this->seiban_doMrp($date);

        // 結果確認：前回引当した分はもう出ていないはず
        $this->seiban_checkMrpResult($itemId_c, "seiban_s1", "", false, false, "0");

        // 結果確認：2どめの受注の分は引当70、注文10
        $this->seiban_checkMrpResult($itemId_c, "seiban_s2", "", true, true, "70");
        $this->seiban_checkMrpResult($itemId_c, "seiban_s2", "", true, false, "10");
    }

    // ●「070531　自動引当機能追加後の所要量計算２」
    //    　ex.A     B　　(A・B共に製番管理)
    //         |-a   |-b
    //         |-c   |-c
    //      この時、その品目(例ではc)をフリー在庫として10個あるとします。
    //      そして、Aを5個受注、Bを10個受注(Aより納期は後)します。
    //      MRP計算すると、A分としてcが5個引当てられ、B分としてcが10個引当てられてしまいます。
    //　  ・受注日と納品日がまたがること。
    //　  ・MRP計算の対象日を納期が後の日付にすること。
    //　  ・リードタイム・安全リードタイムを０にすること。
    public function case_seiban_cybouz2()
    {
        $itemId_A = $this->seiban_registItem("test_seiban_cybouz21", 0, 0);
        $itemId_a = $this->seiban_registItem("test_seiban_cybouz22", 0, 0);
        $itemId_B = $this->seiban_registItem("test_seiban_cybouz23", 0, 0);
        $itemId_b = $this->seiban_registItem("test_seiban_cybouz24", 0, 0);
        $itemId_common = $this->seiban_registItem("test_seiban_cybouz25", 0, 0);

        $this->seiban_registBom($itemId_A, $itemId_a, 1);
        $this->seiban_registBom($itemId_A, $itemId_common, 1);
        $this->seiban_registBom($itemId_B, $itemId_b, 1);
        $this->seiban_registBom($itemId_B, $itemId_common, 1);

        // 製番フリー在庫を10個つくる
        $this->seiban_makeSeibanStock($itemId_common, "", 10);

        // Aを5個受注
        $date = date('Y-m-01', strtotime('+1 month'));  // 翌月1日
        $this->seiban_registReceived($itemId_A, "seiban_c21", 5, date('Y-m-d'), $date);

        // MRP
        $this->seiban_doMrp($date);

        // 結果確認：Aの子品目cは5個引当
        $this->seiban_checkMrpResult($itemId_common, "seiban_c21", "", true, true, "5");
        $this->seiban_checkMrpResult($itemId_common, "seiban_c21", "", true, false, "0");

        // 指示書発行
        Logic_Order::mrpToOrder(1);

        // 自動引当
        Logic_SeibanChange::mrpToSeibanChange();

        // Bを10個受注(Aより納期は後)
        $date = date('Y-m-10', strtotime('+1 month'));  // 翌月10日
        $this->seiban_registReceived($itemId_B, "seiban_c22", 10, date('Y-m-d'), $date);

        // MRP
        $this->seiban_doMrp($date);

        // 結果確認：Bの子品目cは5個引当、5個オーダー
        $this->seiban_checkMrpResult($itemId_common, "seiban_c22", "", true, true, "5");
        $this->seiban_checkMrpResult($itemId_common, "seiban_c22", "", true, false, "5");
    }

    // ●「070531　自動製番引当機能追加後の所要量計算３」 LTあり
    //        構成内にある品目が複数回出てくるケースです。
    //        　ex.A(製番管理)
    //             |--a
    //             |  |-c
    //             |--b
    //             |  |-c
    //             |-c　　　　　　(すべてリードタイムは１です)
    //        cをフリー在庫として10個持っているとします。
    //        Aを5個受注しMRP計算すると、まずaとbの子品目c10個が引当てられます。その後Aの子品目cが5個引き
    //        当てられてしまいます。
    //        すべてデータを作成してcの在庫数を見てみると、やはり数が足りないので-5(Aの子品目としての引き
    //        当て分)となっています。
    public function case_seiban_cybouz3()
    {
        $itemId_A = $this->seiban_registItem("test_seiban_cybouz31", 1, 0);
        $itemId_a = $this->seiban_registItem("test_seiban_cybouz32", 1, 0);
        $itemId_b = $this->seiban_registItem("test_seiban_cybouz33", 1, 0);
        $itemId_c = $this->seiban_registItem("test_seiban_cybouz34", 1, 0);

        $this->seiban_registBom($itemId_A, $itemId_a, 1);
        $this->seiban_registBom($itemId_a, $itemId_c, 1);
        $this->seiban_registBom($itemId_A, $itemId_b, 1);
        $this->seiban_registBom($itemId_b, $itemId_c, 1);
        $this->seiban_registBom($itemId_A, $itemId_c, 1);

        // 製番フリー在庫を10個つくる
        $this->seiban_makeSeibanStock($itemId_c, "", 10);

        // Aを5個受注
        $date = date('Y-m-10', strtotime('+1 month'));  // 翌月10日
        $this->seiban_registReceived($itemId_A, "seiban_c31", 5, date('Y-m-d'), $date);

        // MRP
        $this->seiban_doMrp($date);

        // 結果確認：cは合計10個引当、5個オーダー
        $this->seiban_checkMrpResult($itemId_c, "seiban_c31", "", true, true, "10");
        $this->seiban_checkMrpResult($itemId_c, "seiban_c31", "", true, false, "5");
    }

    // ●「070601　自動製番引当機能追加後の所要量計算４」
    //        外製(支給あり)の子品目がフリー製番在庫としてあるケースです。
    //        　ex.A(製番管理)
    //             |--a(外製(支給あり))
    //                |-c
    //        cをフリー在庫として10個持っているとします。
    //        5月にAを10個受注します(納期は6月)。MRP計算後、各種処理(自動引当等)を行ないます。
    //
    //        ここで、cが払い出される流れを書いておきます。(合っているかはわかりませんが・・・。)
    //        　１．まず、[フリー製番在庫]から[製番付・規定ロケ]に引当てられます。
    //　　　     この時、在庫リストの明細を見ると、[フリー製番在庫]は製番引当出庫、[製番付・規定ロケ]は製
    //            番引当入庫として処理されたことが分かります。
    //        　２．次に、[製番付・規定ロケ]から[製番付・外注ロケ]に払い出されます。
    //　　　     この時、在庫リストの明細を見ると、[製番付・規定ロケ]は支給、[製番付・外注ロケ]は入庫とし
    //            て処理されたことが分かります。
    //            こうした流れで支給は処理されているようです。
    //
    //        で、問題はここからです。(前置きが長くなりました・・・。)
    //        6月なので、月次処理を行ないました。
    //        在庫リストを見ると、cに関するもので[フリー製番在庫]のレコードのみ残って、後は削除されました。
    //        [製番付・外注ロケ]はaを受け入れた時に作成されますが、[製番付・規定ロケ]は作成されません。⇒[ロケあり]が作成されない
    //        Aを納品する前にMRP計算すると、cに対して注文がかかってしまいます。たぶん支給分だと思います。
    //        Aを納品した後なら注文はかかりません。
    public function case_seiban_cybouz4()
    {
        global $gen_db;

        $itemId_A = $this->seiban_registItem("test_seiban_cybouz41", 1, 0);
        $itemId_a = $this->seiban_registItem("test_seiban_cybouz42", 1, 0);
        $itemId_c = $this->seiban_registItem("test_seiban_cybouz43", 1, 0);

        $this->seiban_registBom($itemId_A, $itemId_a, 1);
        $this->seiban_registBom($itemId_a, $itemId_c, 1);

        // cの製番フリー在庫を10個つくる
        $this->seiban_makeSeibanStock($itemId_c, "", 10);

        // 親品目の受注10個（翌月10日納期）
        $date = date('Y-m-10', strtotime('+1 month'));  // 翌月10日
        $this->seiban_registReceived($itemId_A, "seiban_c41", 10, date('Y-m-d'), $date);

        // MRP
        $this->seiban_doMrp($date);

        // 指示書発行（A,a 10）
        Logic_Order::mrpToOrder(1);

        // 自動引当（c 10）
        Logic_SeibanChange::mrpToSeibanChange();

        // 月次処理
        $start_date = Logic_SystemDate::getStartDateString();
        $end_date = Logic_SystemDate::getEndDateString();
        $this_year = date('Y', strtotime($start_date));
        $this_month = date('m', strtotime($start_date));
        $next_start_date = date('Y-m-d', mktime(0, 0, 0, $this_month + 1, 1, $this_year));    // 翌月1日。13月は翌年1月に換算される
        $next_end_date = date('Y-m-d', mktime(0, 0, 0, $this_month + 2, 0, $this_year));      // 翌月末。0日は前月末日に換算される
        Logic_Stock::monthly($this_year, $this_month, $gen_db);
        Logic_Inout::monthly($start_date, $end_date, $gen_db);
        Logic_Plan::monthly($this_year, $this_month, $gen_db);
        Logic_Monthly::monthlyCompany($next_start_date, $next_end_date, $gen_db);
        Logic_Stock::monthlyAddSeibanStock($start_date, $end_date, $gen_db);    // 現在庫計算も
        Logic_Reserve::monthly($gen_db);

        // もういちどMRP
        //   開始日を翌月2日にする（mrpは処理月の2日以降である必要がある）
        $start = date('Y-m-d', strtotime($next_start_date) + (3600 * 24));
        $this->seiban_doMrp($date, $start);

        // 結果確認：前回引当した分はもうオーダーも引当も出ていないはず
        $this->seiban_checkMrpResult($itemId_c, "seiban_c41", "", true, true, "0");
        $this->seiban_checkMrpResult($itemId_c, "seiban_c41", "", true, false, "0");
    }

    // ●「070608　自動製番引当機能」
    //        フリー製番在庫を持っている親製品に受注が入ったとします。その時、
    //        １．親フリー分＞受注数　かつ　子品目のフリー在庫なし　⇒受注数＞引当数にする
    //        ２．   〃　 ＜　〃　　かつ　　　　　　〃
    //        ３．   〃　 ＞　〃　　かつ　子品目のフリー在庫あり　⇒受注数＞引当数にする
    //        ４．   〃　 ＜　〃　　かつ　　　　　　〃
    //
    //        １．残りの受注数を引当てようとする。⇒意図的に残りは製造したいときは？？
    //        ２．フリー分がないのに残りの受注分を引当てようとする。
    //        ３．残りの受注分だけでなく、その子品目も引当てようとする。
    //        ４．フリー分がないのに残りの受注分を引当てようと、さらに、子品目も引当てようとする。
    public function case_seiban_cybouz5()
    {
        $itemId1p = $this->seiban_registItem("test_seiban_cybouz51", 0, 0);
        $itemId1c = $this->seiban_registItem("test_seiban_cybouz52", 0, 0);
        $itemId2p = $this->seiban_registItem("test_seiban_cybouz53", 0, 0);
        $itemId2c = $this->seiban_registItem("test_seiban_cybouz54", 0, 0);
        $itemId3p = $this->seiban_registItem("test_seiban_cybouz55", 0, 0);
        $itemId3c = $this->seiban_registItem("test_seiban_cybouz56", 0, 0);
        $itemId4p = $this->seiban_registItem("test_seiban_cybouz57", 0, 0);
        $itemId4c = $this->seiban_registItem("test_seiban_cybouz58", 0, 0);

        $this->seiban_registBom($itemId1p, $itemId1c, 1);
        $this->seiban_registBom($itemId2p, $itemId2c, 1);
        $this->seiban_registBom($itemId3p, $itemId3c, 1);
        $this->seiban_registBom($itemId4p, $itemId4c, 1);

        // ****** パターン1 *******
        // １．親フリー分＞受注数　かつ　子品目のフリー在庫なし　⇒受注数＞引当数にする
        //
        // 親の製番フリー在庫を20個つくる
        $this->seiban_makeSeibanStock($itemId1p, "", 20);

        // 親を10個受注、5個引当　⇒　結果：親は5個引当、子はなし
        $date = date('Y-m-2', strtotime('+1 month'));  // 翌月2日
        $this->seiban_registReceived($itemId1p, "seiban_c51", 10, date('Y-m-d'), $date, 5);

        // ****** パターン2 *******
        // ２．親フリー分＜受注数 かつ　子品目のフリー在庫なし
        //
        // 親の製番フリー在庫を5個つくる
        $this->seiban_makeSeibanStock($itemId2p, "", 5);

        // 親を10個受注、5個引当　⇒　結果：親は5個オーダー、子は5個オーダー
        $this->seiban_registReceived($itemId2p, "seiban_c52", 10, date('Y-m-d'), $date, 5);

        // ****** パターン3 *******
        // ３． 親フリー分 ＞　受注数　　かつ　子品目のフリー在庫あり　⇒受注数＞引当数にする
        //
        // 親の製番フリー在庫を20個、子も20個つくる
        $this->seiban_makeSeibanStock($itemId3p, "", 20);
        $this->seiban_makeSeibanStock($itemId3c, "", 20);

        // 親を10個受注、5個引当　⇒　結果：親は5個引当、子はなし
        $this->seiban_registReceived($itemId3p, "seiban_c53", 10, date('Y-m-d'), $date, 5);

        // ****** パターン4 *******
        // ４．   親フリー分　 ＜　受注数　　かつ　子品目のフリー在庫あり
        //
        // 親の製番フリー在庫を5個、子も5個つくる
        $this->seiban_makeSeibanStock($itemId4p, "", 5);
        $this->seiban_makeSeibanStock($itemId4c, "", 5);

        // 親を10個受注、5個引当　⇒　結果：親は5個オーダー、子は5個引当
        $this->seiban_registReceived($itemId4p, "seiban_c54", 10, date('Y-m-d'), $date, 5);


        // MRP
        $this->seiban_doMrp($date);

        // 結果確認1：親は5個引当、子はなし
        //  １．不具合：残りの受注数を引当てようとする。⇒意図的に残りは製造したいときは？？
        $this->seiban_checkMrpResult($itemId1p, "seiban_c51", "", true, true, "5");
        $this->seiban_checkMrpResult($itemId1p, "seiban_c51", "", true, false, "0");
        $this->seiban_checkMrpResult($itemId1c, "seiban_c51", "", true, true, "0");
        $this->seiban_checkMrpResult($itemId1c, "seiban_c51", "", true, false, "0");

        // 結果確認2：親は5個オーダー、子は5個オーダー
        //  ２．不具合：フリー分がないのに残りの受注分を引当てようとする。
        $this->seiban_checkMrpResult($itemId2p, "seiban_c52", "", true, true, "0");
        $this->seiban_checkMrpResult($itemId2p, "seiban_c52", "", true, false, "5");
        $this->seiban_checkMrpResult($itemId2c, "seiban_c52", "", true, true, "0");
        $this->seiban_checkMrpResult($itemId2c, "seiban_c52", "", true, false, "5");

        // 結果確認3：親は5個引当、子はなし
        //  ３．不具合：残りの受注分だけでなく、その子品目も引当てようとする。
        $this->seiban_checkMrpResult($itemId3p, "seiban_c53", "", true, true, "5");
        $this->seiban_checkMrpResult($itemId3p, "seiban_c53", "", true, false, "0");
        $this->seiban_checkMrpResult($itemId3c, "seiban_c53", "", true, true, "0");
        $this->seiban_checkMrpResult($itemId3c, "seiban_c53", "", true, false, "0");

        // 結果確認4：親は5個オーダー、子は5個引当
        //  ４．不具合：フリー分がないのに残りの受注分を引当てようと、さらに、子品目も引当てようとする。
        $this->seiban_checkMrpResult($itemId4p, "seiban_c54", "", true, true, "0");
        $this->seiban_checkMrpResult($itemId4p, "seiban_c54", "", true, false, "5");
        $this->seiban_checkMrpResult($itemId4c, "seiban_c54", "", true, true, "5");
        $this->seiban_checkMrpResult($itemId4c, "seiban_c54", "", true, false, "0");
    }

    // ●「070612　自動引当機能２」 LTあり
    //        A(内製)
    //        |-a(外製支給あり)
    //        | |-b(購買品)
    //        |
    //        |-b(購買品)　　　　　　　aとb共にフリー在庫が2個ずつある
    //
    //        Aを1個受注します。所要量計算をすると、aの自動引当が1個・bの自動引当が2個されます。
    //        aとbがひとつずつあればAを製造できるのに、aの支給としてのbも引当てられてしまいます。
    //        計算上しょうがない部分かな、と思うので、運用面でカバーすることかと思います。その際は、bの引当数を1個に修正します。
    public function case_seiban_cybouz6()
    {
        $itemId_A = $this->seiban_registItem("test_seiban_cybouz61", 1, 0);
        $itemId_a = $this->seiban_registItem("test_seiban_cybouz62", 1, 0);
        $itemId_b = $this->seiban_registItem("test_seiban_cybouz63", 1, 0);

        $this->seiban_registBom($itemId_A, $itemId_a, 1);
        $this->seiban_registBom($itemId_a, $itemId_b, 1);
        $this->seiban_registBom($itemId_A, $itemId_b, 1);

        // a,bの製番フリー在庫を2個つくる
        $this->seiban_makeSeibanStock($itemId_a, "", 2);
        $this->seiban_makeSeibanStock($itemId_b, "", 2);

        // Aを1個受注
        $date = date('Y-m-10', strtotime('+1 month'));  // 翌月10日
        $this->seiban_registReceived($itemId_A, "seiban_c61", 1, date('Y-m-d'), $date);

        // MRP
        $this->seiban_doMrp($date);

        // 結果確認：aは1個引当、bも1個引当
        $this->seiban_checkMrpResult($itemId_a, "seiban_c61", "", true, true, "1");
        $this->seiban_checkMrpResult($itemId_a, "seiban_c61", "", true, false, "0");
        $this->seiban_checkMrpResult($itemId_b, "seiban_c61", "", true, true, "1");
        $this->seiban_checkMrpResult($itemId_b, "seiban_c61", "", true, false, "0");
    }

    // ●「070906　自動製番引当　手配まるめ数によるバグ? 」
    //    <構成>
    //     製品A
    //       |-部材a(手配単位:100)　　　員数(A:a=1:1)
    //
    //    製品Aを10個受注し、所要量計算すると製品Aの指示が10個、部材aの発注が100個でます。納品まで登録していき、最後に部材aの90個をフリー製番にしておきます。
    //    その後、製品Aを5個受注し、所要量計算すると製品Aの指示が5個、部材aの製番引当が90個、部材aの発注が10個という結果が表示されています。本来なら、製番引当は5個で発注はでないはずです。
    //    これは、製番引当90個+発注10個=手配単位の100個、ということではないでしょうか？実際に、手配単位を10個に変更してでやってみると、発注はかかりませんが、10個の製番引当が表示されます。
    //
    //  ⇒　上記の点も含め、次のような問題がある。
    //     (1) 必要数ではなく発注単位で引当されてしまう ⇒ 上記で指摘されたケース
    //          例：　必要数が10のとき引当は10でいいはずだが、発注単位が100なら100の引当（もしくはオーダー）がかかってしまう
    //     (2) 既存の製番在庫が必要数を満たすだけ存在しても、発注単位より少ないと、オーダーがかかってしまう
    //          例：　必要数が10、既存製番在庫が10のとき、オーダーも引当も不要のはずだが、発注単位が100なら90のオーダーもしくは引当がかかってしまう
    //      ※上記と同様のことは、発行済みオーダーについても言える。
    //          発行済みオーダーが必要数を満たすだけ存在しても、発注単位より少ないと、再オーダーがかかってしまう
    //          例：　必要数が10、発行済みオーダーが10のとき、オーダーも引当も不要のはずだが、発注単位が100なら90のオーダーもしくは引当がかかってしまう
    //        ただしこの点の対処はロジック的に難しく、現行プログラムでは考慮されていないため、ここでのテストも行わない。
    //        発行済みオーダーについては基本的には手配丸めした数量になっているはずなので、あまり問題はないと思われる。
    //        問題になるのは発行後に手修正で数量を減らした場合などだろう。
    public function case_seiban_cybouz7()
    {
        global $gen_db;

        $itemId1 = $this->seiban_registItem("test_mrp_cybouz71", 0, 0);
        $itemId2 = $this->seiban_registItem("test_mrp_cybouz72", 0, 0);

        // 発注単位を100にする
        $query = "update item_order_master set default_lot_unit=100 where item_id='$itemId1' and line_number=0";
        $gen_db->query($query);
        $query = "update item_order_master set default_lot_unit=100 where item_id='$itemId2' and line_number=0";
        $gen_db->query($query);

        // 受注10個
        $nextMonth1 = date('Y-m-01', strtotime('+1 month'));
        $this->seiban_registReceived($itemId1, "seiban_cybouz71", 10, date('Y-m-d'), $nextMonth1);
        $this->seiban_registReceived($itemId2, "seiban_cybouz72", 10, date('Y-m-d'), $nextMonth1);

        // (1)用：フリー製番在庫を100個つくる
        $this->seiban_makeSeibanStock($itemId1, "", 100);
        // (2)用：製番在庫を10個つくる
        $this->seiban_makeSeibanStock($itemId2, "seiban_cybouz72", 10);

        // MRP
        $this->seiban_doMrp($nextMonth1);

        // 結果確認(1)：引当は10（発注単位にかかわらず必要数分だけ引当）
        $this->seiban_checkMrpResult($itemId1, "", "", true, true, "10");
        // 結果確認(2)：オーダー・引当なし（製番在庫が必要数を満たすだけあるので、発注単位にかかわらずオーダーなし）
        $this->seiban_checkMrpResult($itemId2, "", "", false, false, "0");
    }

    // ●20111014 オキナ電子からの報告
    //   https://gw.genesiss.jp/cgi-bin/e-commode/ag.cgi?page=ProjectDocView&pid=1228&did=125962
    //   2010iで発生した不具合。（rev.20111014で修正）
    //   受注品目に対する自動製番引当において、納期の早い受注ではなく、先に登録された受注が優先されてしまう。
    public function case_seiban_cybouz8()
    {
        $itemId1 = $this->seiban_registItem("test_mrp_cybouz81", 0, 0);

        // 受注10個
        // 先に納期の遅い方（翌月10日）を登録し、そのあとで納期の早い方（翌月1日）を登録
        $nextMonth1 = date('Y-m-01', strtotime('+1 month'));
        $nextMonth10 = date('Y-m-10', strtotime('+1 month'));
        $this->seiban_registReceived($itemId1, "seiban_cybouz81", 10, date('Y-m-d'), $nextMonth10);
        $this->seiban_registReceived($itemId1, "seiban_cybouz82", 10, date('Y-m-d'), $nextMonth1);

        // フリー製番在庫を10個つくる
        $this->seiban_makeSeibanStock($itemId1, "", 10);

        // MRP
        $this->seiban_doMrp($nextMonth10);

        // 結果確認：納期の遅い方はオーダーが出る
        $this->seiban_checkMrpResult($itemId1, "seiban_cybouz81", "", true, false, "10");
        // 結果確認：納期の早い方は引当が出る
        $this->seiban_checkMrpResult($itemId1, "seiban_cybouz82", "", true, true, "10");
    }

    // ●2008/06/19 藤沢産業からの指摘1
    // http://211.125.169.227/cgi-bin/e-commode/ag.cgi?page=ProjectDocView&pid=843&did=49938&cp=plv&tp=t
    //    【条件】
    //    ・製番品目で、手配丸め数が設定されており、LTが1以上
    //    ・手配丸め数より少ない数量の受注が入った
    //    ・フリー在庫が存在しないか、受注数より少ない
    //    【処理】
    //    ・所要量計算を行い、結果として出た製番引当オーダーと注文(or製造)オーダーの両方を発行
    //    ・フリー在庫数を増やし、所要量計算を再度実行
    //    【結果】
    //    ・次回のオーダー数がおかしくなる

    public function case_seiban_fujisawa1()
    {
        global $gen_db;

        $itemId1 = $this->seiban_registItem("test_seiban_fujisawa11", 0, 0);

        // 手配丸め数を10にする
        $query = "update item_order_master set default_lot_unit=10 where item_id='$itemId1' and line_number=0";
        $gen_db->query($query);

        // LTを2にする
        $query = "update item_master set lead_time=2 where item_id='$itemId1'";
        $gen_db->query($query);

        // 受注4個
        $nextMonth1 = date('Y-m-01', strtotime('+1 month'));
        $this->seiban_registReceived($itemId1, "seiban_fujisawa11", 4, date('Y-m-d'), $nextMonth1);

        // MRP - オーダー4（手配丸めにより10）
        $this->seiban_doMrp($nextMonth1);

        // オーダー発行（自動引当（ないはずだが）も行う）
        Logic_Order::mrpToOrder(1);
        Logic_SeibanChange::mrpToSeibanChange();

        // フリー製番在庫を追加
        $this->seiban_makeSeibanStock($itemId1, "", 3);

        // もっかいMRP
        $this->seiban_doMrp($nextMonth1);

        // 結果確認 - オーダー済みなので新規オーダーはでないはず
        $this->seiban_checkMrpResult($itemId1, "seiban_fujisawa11", "", false, false, "0");
    }

    // ●2008/06/19 藤沢産業からの指摘2
    //  上のテストから派生して見つかった不具合。
    //  上記と条件は同じで、以下のような処理を行った場合にオーダーが正しくない。
    //    ・所要量計算を行い、結果として出た製番自動引当オーダーと注文(or製造)オーダーのうち後者だけを発行
    //      ※ここで製番自動引当が発生していないと不具合は出ない
    //    ・所要量計算を再度実行

    public function case_seiban_fujisawa2()
    {
        global $gen_db;

        $itemId1 = $this->seiban_registItem("test_seiban_fujisawa21", 0, 0);

        // 手配丸めを10にする
        $query = "update item_order_master set default_lot_unit=10 where item_id='$itemId1' and line_number=0";
        $gen_db->query($query);

        // LTを2にする
        $query = "update item_master set lead_time=2 where item_id='$itemId1'";
        $gen_db->query($query);

        // 受注4個
        $nextMonth1 = date('Y-m-01', strtotime('+1 month'));
        $this->seiban_registReceived($itemId1, "seiban_fujisawa21", 4, date('Y-m-d'), $nextMonth1);

        // フリー製番在庫を3個つくる
        $this->seiban_makeSeibanStock($itemId1, "", 3);

        // MRP - 引当3、オーダー1（手配丸めにより10）
        $this->seiban_doMrp($nextMonth1);

        // オーダー発行（自動引当は行わない）
        Logic_Order::mrpToOrder(1);

        // もっかいMRP
        $this->seiban_doMrp($nextMonth1);

        // 結果確認 - すでにオーダー10発行済みなので、新規オーダーはでないはず
        $this->seiban_checkMrpResult($itemId1, "seiban_fujisawa21", "", false, false, "0");
    }

    // ●2008/09/19 藤沢産業からの指摘3
    // http://211.125.169.227/cgi-bin/e-commode/ag.cgi?page=ProjectDocView&pid=843&did=55271&cp=plv&tp=t
    //    【条件】
    //    ・製番品目で、1回目の計算で製番引当オーダーが出て引当実行。
    //    ・引当でできた製番在庫を親品目の実績か支給で出庫する。
    //    ・さらにフリー在庫がある状態でもう一度計算を実行する。
    //    【症状】
    //    ・同じ製番でもう一度製番引当オーダーが出てしまう。

    public function case_seiban_fujisawa3()
    {
        // テスト対象は子品目のほう（$itemId_c）。親品目は実績引落を発生させる用。
        $itemId_p = $this->seiban_registItem("test_seiban_fujisawa31", 0, 0);
        $itemId_c = $this->seiban_registItem("test_seiban_fujisawa32", 0, 0);

        $this->seiban_registBom($itemId_p, $itemId_c, 1);

        // 子品目のフリー製番在庫2個（item1のみ。製番引当を発生させるため）
        $this->seiban_makeSeibanStock($itemId_c, "", 2);

        // 受注1個
        $nextMonth1 = date('Y-m-01', strtotime('+1 month'));
        $this->seiban_registReceived($itemId_p, "seiban_fujisawa3", 1, date('Y-m-d'), $nextMonth1);

        // MRP
        $this->seiban_doMrp($nextMonth1);

        // オーダー発行。親は製造指示1、子は製番引当1
        Logic_Order::mrpToOrder(1);
        Logic_SeibanChange::mrpToSeibanChange();

        // 実績登録して子品目製番在庫を引き落とし
        $this->seiban_registAchievement($itemId_p, date('Y-m-d'));

        // もっかいMRP
        $this->seiban_doMrp($nextMonth1);

        // 結果確認 - オーダー済みなので新規オーダーはでないはず
        $this->seiban_checkMrpResult($itemId_c, "seiban_fujisawa3", "", false, false, "0");
    }

    // ●2008/11/17 藤沢産業からの指摘4
    // http://211.125.169.227/cgi-bin/e-commode/ag.cgi?page=MailView&ets=ts.1226648743&mEID=5057&tp=t
    //    【条件】
    //    ・ひとつの製品の下に同じ製番品目が複数回出てくるような構成を組む。
    //     所要量計算で、その品目に注文or製造オーダーと、製番引当オーダーの両方が出て、オーダー発行する。
    //    ・さらにもう一度計算を実行する。
    //    【症状】
    //    ・同じ製番でもう一度オーダーが出てしまう。

    public function case_seiban_fujisawa4()
    {
        $itemId_p = $this->seiban_registItem("test_seiban_fujisawa41", 0, 0);
        $itemId_m1 = $this->seiban_registItem("test_seiban_fujisawa42", 0, 0);
        $itemId_m2 = $this->seiban_registItem("test_seiban_fujisawa43", 0, 0);
        $itemId_c = $this->seiban_registItem("test_seiban_fujisawa44", 0, 0);      // テスト対象品目

        $this->seiban_registBom($itemId_p, $itemId_m1, 1);
        $this->seiban_registBom($itemId_p, $itemId_m2, 1);
        $this->seiban_registBom($itemId_m1, $itemId_c, 1);
        $this->seiban_registBom($itemId_m2, $itemId_c, 1);

        // フリー製番在庫2個（製番引当を発生させるため）
        $this->seiban_makeSeibanStock($itemId_c, "", 2);

        // 親受注4個 （テスト品目オーダー8個）
        $nextMonth1 = date('Y-m-01', strtotime('+1 month'));
        $this->seiban_registReceived($itemId_p, "seiban_fujisawa4", 4, date('Y-m-d'), $nextMonth1);

        // MRP
        $this->seiban_doMrp($nextMonth1);

        // オーダー発行。製造指示6、引当2
        Logic_Order::mrpToOrder(1);
        Logic_SeibanChange::mrpToSeibanChange();
        $this->seiban_checkMrpResult($itemId_c, "seiban_fujisawa4", "", false, false, "8");

        // もっかいMRP
        $this->seiban_doMrp($nextMonth1);

        // 結果確認 - オーダー済みなので新規オーダーはでないはず
        $this->seiban_checkMrpResult($itemId_c, "seiban_fujisawa4", "", false, false, "0");
    }

    // ●手配まるめ数（ロット単位）を２段階に（2009）
    public function case_seiban_2009_1()
    {
        $nextMonth1 = date('Y-m-01', strtotime('+1 month'));

        // まるめ単位1が有効なパターン
        $itemId1 = $this->seiban_registItem("test_seiban_2009_01", 0, 0);
        $this->seiban_setLotUnit($itemId1, 10, 10, 0);     // まるめ単位1, まるめ単位1の上限, まるめ単位2
        $this->seiban_registReceived($itemId1, "seiban_2009_01", 9, $nextMonth1, $nextMonth1, false);
        $answer1 = 10;

        // まるめ単位2が有効なパターン（まるめ単位1の上限までは、まるめ単位1を適用）
        $itemId2 = $this->seiban_registItem("test_seiban_2009_02", 0, 0);
        $this->seiban_setLotUnit($itemId2, 40, 80, 9);     // まるめ単位1, まるめ単位1の上限, まるめ単位2
        $this->seiban_registReceived($itemId2, "seiban_2009_02", 81, $nextMonth1, $nextMonth1, false);
        $answer2 = 89;

        // まるめなし
        $itemId3 = $this->seiban_registItem("test_seiban_2009_03", 0, 0);
        $this->seiban_setLotUnit($itemId3, 0, 0, 0);     // まるめ単位1, まるめ単位1の上限, まるめ単位2
        $this->seiban_registReceived($itemId3, "seiban_2009_03", 0.5, $nextMonth1, $nextMonth1, false);
        $answer3 = 0.5;

        // まるめ単位2のみ、まるめなし
        $itemId4 = $this->seiban_registItem("test_seiban_2009_04", 0, 0);
        $this->seiban_setLotUnit($itemId4, 10, 30, 0);     // まるめ単位1, まるめ単位1の上限, まるめ単位2
        $this->seiban_registReceived($itemId4, "seiban_2009_04", 31, $nextMonth1, $nextMonth1, false);
        $answer4 = 31;

        // 子品目へのまるめ引継ぎ
        $itemId5p = $this->seiban_registItem("test_seiban_2009_05p", 0, 0);
        $itemId5 = $this->seiban_registItem("test_mrp_2009_05", 0, 0);
        $this->seiban_registBom($itemId5p, $itemId5, 1);
        $this->seiban_setLotUnit($itemId5p, 10, 10, 20);     // 親：受注11に対し、まるめで30になる（最初の10 + 残りの1が丸まって20）
        $this->seiban_setLotUnit($itemId5, 1, 20, 50);       // 子：従属需要30に対し、まるめで70になる（最初の20 + 残りの10が丸まって50）
        $this->seiban_registReceived($itemId5p, "seiban_2009_05", 11, $nextMonth1, $nextMonth1, false);
        $answer5 = 70;

        // まるめ1上限およびまるめ単位2が未設定
        $itemId6 = $this->seiban_registItem("test_seiban_2009_06", 0, 0);
        $this->seiban_setLotUnit($itemId6, 10, '', '');       // まるめ単位1, まるめ単位1の上限, まるめ単位2
        $this->seiban_registReceived($itemId6, "seiban_2009_06", 11, $nextMonth1, $nextMonth1, false);
        $answer6 = 20;

        // MRP
        $this->seiban_doMrp($nextMonth1);

        // 結果確認
        $this->seiban_checkMrpResult($itemId1, null, $nextMonth1, false, false, $answer1);
        $this->seiban_checkMrpResult($itemId2, null, $nextMonth1, false, false, $answer2);
        $this->seiban_checkMrpResult($itemId3, null, $nextMonth1, false, false, $answer3);
        $this->seiban_checkMrpResult($itemId4, null, $nextMonth1, false, false, $answer4);
        $this->seiban_checkMrpResult($itemId5, null, $nextMonth1, false, false, $answer5);
        $this->seiban_checkMrpResult($itemId6, null, $nextMonth1, false, false, $answer6);
    }

    // ●可変リードタイム（2010）
    public function case_seiban_2010_1()
    {
        global $gen_db;

        $deadline = date('Y-m-d', strtotime('+10 days'));

        // 受注300、LT:空欄、工程1LT:空欄、工程1製造能力:100、工程2LT:1（可変LTの工程と固定LTの工程が混在）
        $itemId1 = $this->seiban_registItem("test_mrp_seiban_2010_1_nolt", null, 0);
        $query = "insert into item_process_master(item_id, process_id, machining_sequence, default_work_minute, pcs_per_day, charge_price, process_lt)
         values ($itemId1, 0, 0, 0, 100, 0, null)";
        $gen_db->query($query);
        $query = "insert into item_process_master(item_id, process_id, machining_sequence, default_work_minute, pcs_per_day, charge_price, process_lt)
         values ($itemId1, 0, 1, 0, 0, 0, 1)";
        $gen_db->query($query);
        $childItemId1 = $this->seiban_registItem("test_mrp_seiban_2010_1_nolt_child", 0, 0);
        $this->seiban_registBom($itemId1, $childItemId1, 1);
        $this->seiban_registReceived($itemId1, "seiban_2010_1_nolt", 300, $deadline, $deadline, 0);

        // 受注300、LT:2（LT固定）
        $itemId2 = $this->seiban_registItem("test_mrp_2010_1_lt", 2, 0);
        $childItemId2 = $this->seiban_registItem("test_mrp_2010_1_lt_child", 0, 0);
        $this->seiban_registBom($itemId2, $childItemId2, 1);
        $this->seiban_registReceived($itemId2, "seiban_2010_1_lt", 300, $deadline, $deadline, 0);

        // 受注300、LT:空欄、工程1LT:空欄、工程1製造能力:1、工程2LT:1（可変LTの工程と固定LTの工程が混在、かなりLTが大きくなるパターン）
        $itemId3 = $this->seiban_registItem("test_mrp_seiban_2010_1_longlt", null, 0);
        $query = "insert into item_process_master(item_id, process_id, machining_sequence, default_work_minute, pcs_per_day, charge_price, process_lt)
         values ($itemId3, 0, 0, 0, 1, 0, null)";
        $gen_db->query($query);
        $query = "insert into item_process_master(item_id, process_id, machining_sequence, default_work_minute, pcs_per_day, charge_price, process_lt)
         values ($itemId3, 0, 1, 0, 0, 0, 1)";
        $gen_db->query($query);
        $childItemId3 = $this->seiban_registItem("test_mrp_seiban_2010_1_longlt_child", 0, 0);
        $this->seiban_registBom($itemId3, $childItemId3, 1);
        $this->seiban_registReceived($itemId3, "seiban_2010_1_longlt", 300, $deadline, $deadline, 0);

        // MRP
        $this->seiban_doMrp($deadline);

        // 結果確認
        $this->seiban_checkMrpResult($childItemId1, null, $this->seiban_getAdjustDeadline($deadline, 3, 0), false, false, 300);    // LT=3： (300(受注) ÷ 100(工程1製造能力)) -1 + 1(工程2LT)
        $this->seiban_checkMrpResult($childItemId2, null, $this->seiban_getAdjustDeadline($deadline, 2, 0), false, false, 300);    // LT=2： 固定LT
        $this->seiban_checkMrpResult($childItemId3, null, date('Y-m-d', strtotime('+1 days')), false, false, 300);         // アラーム
    }

    // ●所要量計算結果で、製番品目のオーダーを手動変更したときの動き（2010）
    //  上記の操作を行うと、計画テーブルに受注製番がついた計画が立つ。
    //  その状態で再度所要量計算を行ったときに、受注より計画が優先される
    //  （受注納期から計算した本来の日ではなく、計画日にオーダーが出る）ことを確認する。
    public function case_seiban_2010_2()
    {
        $itemId_p = $this->seiban_registItem("test_seiban_2010_1_1", 0, 0);
        $itemId_c = $this->seiban_registItem("test_seiban_2010_1_2", 1, 0);    // LT=1

        $this->seiban_registBom($itemId_p, $itemId_c, 1);

        $nextMonth1 = date('Y-m-01', strtotime('+1 month'));
        $nextMonth2 = date('Y-m-02', strtotime('+1 month'));
        $nextMonth3 = date('Y-m-03', strtotime('+1 month'));
        $nextMonth4 = date('Y-m-04', strtotime('+1 month'));

        // 親受注10個 （翌月4日）
        $this->seiban_registReceived($itemId_p, "seiban_2010_1", 10, date('Y-m-d'), $nextMonth4);

        // 受注製番つき計画を作成　（所要量計算結果画面で手動でオーダー日を前倒しした状態をシミュレーション）
        //  受注品目：　受注の前日に5、2日前に5
        $this->seiban_registPlan($itemId_p, "seiban_2010_1", 5, $nextMonth3);
        $this->seiban_registPlan($itemId_p, "seiban_2010_1", 5, $nextMonth2);
        //  子品目：        受注の2日前に7、3日前に3
        $this->seiban_registPlan($itemId_c, "seiban_2010_1", 7, $nextMonth2);
        $this->seiban_registPlan($itemId_c, "seiban_2010_1", 3, $nextMonth1);

        // MRP
        $this->seiban_doMrp($nextMonth4);

        // 結果確認 - 本来の日ではなく、計画日にオーダーが出る
        // 親品目
        $this->seiban_checkMrpResult($itemId_p, "seiban_2010_1", "", false, false, "10");    // トータルは受注数のまま
        $this->seiban_checkMrpResult($itemId_p, "seiban_2010_1", $nextMonth3, false, false, "5");
        $this->seiban_checkMrpResult($itemId_p, "seiban_2010_1", $nextMonth2, false, false, "5");
        // 子品目
        $this->seiban_checkMrpResult($itemId_c, "seiban_2010_1", "", false, false, "10");    // トータルは必要数のまま
        $this->seiban_checkMrpResult($itemId_c, "seiban_2010_1", $nextMonth2, false, false, "7");
        $this->seiban_checkMrpResult($itemId_c, "seiban_2010_1", $nextMonth1, false, false, "3");
    }
 


    // ************************
    //  functions for MRP
    // ************************

    public function testDelete()
    {
        global $gen_db;

        $gen_db->rollback();
    }

    function registItem($codeName, $lt, $slt, $withoutMrp = false)
    {
        global $gen_db;

        $data = array(
            'item_code' => $codeName,
            'item_name' => $codeName,
            'order_class' => '1', // 管理区分　0:製番　1:MRP　2:ロット*/
            'lead_time' => $lt,
            'safety_lead_time' => $slt,
            'item_group_id' => $this->item_group_id, // 品目G id
            'stock_price' => 100,
            'safety_stock' => 0,
            'received_object' => 0, //受注対象　0:受注対象 1:非対象 */
            'maker_name' => 'test_maker',
            'spec' => 'test_spec',
            'without_mrp' => ($withoutMrp ? 1 : 0), // MRP除外　0:含める   1:除外 */
            'comment' => 'test_comment',
            'comment_2' => 'test_comment2',
            'comment_3' => 'test_comment3',
            'comment_4' => 'test_comment4',
            'comment_5' => 'test_comment5',
            'default_selling_price' => 200, // 標準販売単価
                //'process_id' => $this->process_id,     // 工程id
                //'default_work_minute' => 5,         // 標準加工時間
                //'pcs_per_day' => 10,                // 製造能力（1日あたり）
        );
        $gen_db->insert('item_master', $data);
        $id = $gen_db->getSequence("item_master_item_id_seq");

        // 内製
        $data = array(
            'item_id' => $id,
            'line_number' => 0,
            'order_user_id' => 0,
            'default_order_price' => 0,
            'default_lot_unit' => 1,
            'item_sub_code' => "",
            'partner_class' => 3,
        );
        $gen_db->insert('item_order_master', $data);

        return $id;
    }

    function registBom($id1, $id2, $qty)
    {
        global $gen_db;

        if ($this->bomFirst) {
            $this->bomFirst = false;
        } else {
            $query = "drop table temp_bom_calc";
            $gen_db->query($query);
        }

        $data = array(
            'item_id' => $id1,
            'child_item_id' => $id2,
            'quantity' => $qty,
        );
        $gen_db->insert('bom_master', $data);
        Logic_Bom::calcLLC();
    }

    function registLocation($codeName, $customerId = "null")
    {
        global $gen_db;

        $data = array(
            'location_code' => $codeName,
            'location_name' => $codeName,
            'customer_id' => $customerId,
        );

        $gen_db->insert('location_master', $data);
        return $gen_db->getSequence("location_master_location_id_seq");
    }

    function registCustomer($codeName, $isPartner) {
        global $gen_db;

        $data = array(
            'customer_no' => $codeName,
            'customer_name' => $codeName,
            'classification' => ($isPartner ? 1 : 0),
            'monthly_limit_date' => 31,
            'zip' => "",
            'address1' => "",
            'address2' => "",
            'tel' => "",
            'fax' => "",
            'e_mail' => "",
            'person_in_charge' => "",
            'delivery_port' => ""
        );

        $gen_db->insert('customer_master', $data);
        return $gen_db->getSequence("customer_master_customer_id_seq");
    }

    function makeStock($itemId, $qty, $locationId = 0)
    {
        global $gen_db;

        $query = "select coalesce(min(inventory_date),cast('1970-01-01' as date)) from inventory where item_id = '{$itemId}'";
        $date = $gen_db->queryOneValue($query);

        Logic_Inout::entryInout($date, $itemId, "", $locationId, '', $qty, 0, "in", "", "");
    }

    function registInout($itemId, $class, $date, $qty, $locationId = 0)
    {
        Logic_Inout::entryInout($date, $itemId, "", $locationId, '', $qty, 0, "in", "", "");
    }

    function doMrp($end_date, $start_date = false)
    {
        $this->existDrop("temp_mrp_day_table");
        $this->existDrop("temp_plan_list");
        $this->existDrop("temp_seiban");
        $this->existDrop("temp_usable_stock");
        $this->existDrop("temp_seiban_stock");
        $this->existDrop("temp_seiban_order");
        $this->existDrop("temp_mrp_depend_demand");
        // for 2008
        $this->existDrop("temp_inventory_date");
        // for 2010
        $this->existDrop("temp_received_seiban_plan");

        if ($start_date == false)
            $start_date = date('Y-m-d', time() + (3600 * 24));
        $mrp = new Logic_MRP();
        $mrp->mrpMain($start_date, $end_date, false, false, "test", GEN_MRP_DAYS);
    }

    function existDrop($table)
    {
        global $gen_db;

        $query = "select * from pg_catalog.pg_tables where tablename='$table'";
        if ($gen_db->existRecord($query)) {
            $query = "drop table $table";
            $gen_db->query($query);
        }
    }

    // 受注の登録
    function registReceived($itemId, $seiban, $qty, $date, $deadLine, $reserved = null)
    {
        global $gen_db;

        $recNum = Logic_Received::getReceivedNumber("");

        $arr = array(
            'received_number' => $recNum,
            'customer_id' => $this->customer_id,
            'received_date' => $date,
            'guarantee_grade' => 0,
            'remarks_header' => '',
            'delivery_customer_id' => $this->customer_id,
        );

        $gen_db->insert('received_header', $arr);
        $tranId = $gen_db->getSequence("received_header_received_header_id_seq");

        $arr = array(
            'received_header_id' => $tranId,
            'item_id' => $itemId,
            'received_quantity' => $qty,
            'product_price' => 0,
            'dead_line' => $deadLine,
            'remarks' => "",
            'seiban' => $seiban,
        );

        $gen_db->insert('received_detail', $arr);
        $detailId = $gen_db->getSequence("received_detail_received_detail_id_seq");

        // 引当数の登録/更新
        if ($reserved != null)
            Logic_Reserve::updateReserveQuantity($detailId, $itemId, $deadLine, $reserved);

        // 受注品目がダミー品目だった場合、子品目使用予約を登録
        Logic_Received::entryUsePlanForDummy($detailId);

        return $detailId;
    }

    // 計画の登録
    function registPlan($itemId, $seiban, $qty, $date)
    {
        global $gen_db;

        $year = date('Y', strtotime($date));
        $month = date('m', strtotime($date));

        $query = "select * from plan where plan_year='{$year}' and plan_month='{$month}' and item_id='{$itemId}' and classification=0";
        if ($gen_db->existRecord($query)) {
            $data = array("day$day" => $qty);
            $where = "plan_year='{$year}' and plan_month='{$month}' and item_id='{$itemId}' and classification=0";
            $gen_db->update("plan", $data, $where);
        } else {
            $arr = array(
                'plan_year' => date('Y', strtotime($date)),
                'plan_month' => date('m', strtotime($date)),
                'seiban' => $seiban,
                'item_id' => $itemId,
                'classification' => 0,
                'plan_quantity' => $qty,
                'remarks' => '',
            );
            for ($i = 1; $i <= 31; $i++) {
                $arr["day{$i}"] = 0;
            }
            $day = (int) date('d', strtotime($date));
            $arr["day{$day}"] = $qty;
            $gen_db->insert('plan', $arr);
        }

        return $gen_db->getSequence("plan_plan_id_seq");
    }

    function checkMrpResult($itemId, $finishDate, $trueRes)
    {
        global $gen_db;

        $query = "select sum(arrangement_quantity) from mrp where 1=1";
        if (is_numeric($itemId))
            $query .= " and item_id = '{$itemId}'";
        if ($finishDate != "")
            $query .= " and arrangement_finish_date = '{$finishDate}'";

        $res = $gen_db->queryOneValue($query);

        $msg = $trueRes . ":" . $res;
        if ($trueRes == "0") {
            $this->assertTrue($res == "0" || $res == false);
            if (!($res == "0" || $res == false))
                $msg .= "   ***** Failure!!! *****";
        } else {
            $this->assertTrue($res == $trueRes);
            if ($res != $trueRes)
                $msg .= "   ***** Failure!!! *****";
        }
        var_dump($msg);
    }

    // 休日とLTを考慮して調整した納期を返す
    function getAdjustDeadline($dateStr, $lt, $slt)
    {
        $date = strtotime($dateStr);
        for ($i = 1; $i <= ($lt + $slt); $i++) {
            $date -= (3600 * 24);
            $date = strtotime($this->getNotHoliday(date('Y-m-d', $date)));
        }
        if ($date < strtotime("+1 day")) {
            $date = strtotime("+1 day");
        }

        return date('Y-m-d', $date);
    }

    // 与えられた日以前で、休日ではない最後の日を探して返す
    function getNotHoliday($dateStr)
    {
        global $gen_db;

        $date = strtotime($dateStr);
        while (true) {
            $query = "select * from holiday_master where holiday = '" . date('Y-m-d', $date) . "'";
            if (!$gen_db->existRecord($query))
                return date('Y-m-d', $date);
            $date -= (3600 * 24);
        }
    }

    // まるめ単位（ロット単位）の設定
    function setLotUnit($itemId, $lotUnit, $lotUnitLimit, $lotUnit2)
    {
        global $gen_db;

        $query = "update item_order_master " .
                " set default_lot_unit = {$lotUnit}, default_lot_unit_limit = {$lotUnitLimit},  default_lot_unit_2 = {$lotUnit2} " .
                " where item_id = {$itemId}";
        $gen_db->query($query);
    }

    // ************************
    //  functions for SEIBAN
    // ************************
    //
    // 製番品目登録
    function seiban_registItem($codeName, $lt, $slt, $withoutMrp = false)
    {
        global $gen_db;

        $data = array(
            'item_code' => $codeName,
            'item_name' => $codeName,
            'order_class' => '0', // 管理区分　0:製番　1:MRP　2:ロット */
            'lead_time' => $lt,
            'safety_lead_time' => $slt,
            'item_group_id' => $this->item_group_id, // 品目G id
            'stock_price' => 100,
            'safety_stock' => 100,
            'received_object' => 0, //受注対象　0:受注対象 1:非対象 */
            'maker_name' => 'test_maker',
            'spec' => 'test_spec',
            'without_mrp' => ($withoutMrp ? 1 : 0), // MRP除外　0:含める   1:除外 */
            'comment' => 'test_comment',
            'comment_2' => 'test_comment2',
            'comment_3' => 'test_comment3',
            'comment_4' => 'test_comment4',
            'comment_5' => 'test_comment5',
            'default_selling_price' => 200, // 標準販売単価
            //'process_id' => $this->process_id,     // 工程id
            //'default_work_minute' => 5,         // 標準加工時間
            //'pcs_per_day' => 10,                // 製造能力（1日あたり）
        );
        $gen_db->insert('item_master', $data);
        $id = $gen_db->getSequence("item_master_item_id_seq");

        // 内製
        $data = array(
            'item_id' => $id,
            'line_number' => 0,
            'order_user_id' => 0,
            'default_order_price' => 0,
            'default_lot_unit' => 1,
            'item_sub_code' => "",
            'partner_class' => 3,
        );
        $gen_db->insert('item_order_master', $data);

        return $id;
    }

    function seiban_registBom($id1, $id2, $qty)
    {
        global $gen_db;

        if ($this->bomFirst) {
            $this->bomFirst = false;
        } else {
            $query = "drop table temp_bom_calc";
            $gen_db->query($query);
        }

        $data = array(
            'item_id' => $id1,
            'child_item_id' => $id2,
            'quantity' => $qty,
        );
        $gen_db->insert('bom_master', $data);
        Logic_Bom::calcLLC();
    }

    function seiban_registLocation($codeName, $customerId = null)
    {
        global $gen_db;

        $data = array(
            'location_code' => $codeName,
            'location_name' => $codeName,
            'customer_id' => $customerId,
        );

        $gen_db->insert('location_master', $data);
        return $gen_db->getSequence("location_master_location_id_seq");
    }

    function seiban_registCustomer($codeName, $isPartner)
    {
        global $gen_db;

        $data = array(
            'customer_no' => $codeName,
            'customer_name' => $codeName,
            'classification' => ($isPartner ? 1 : 0),
            'monthly_limit_date' => 31,
            'zip' => "",
            'address1' => "",
            'address2' => "",
            'tel' => "",
            'fax' => "",
            'e_mail' => "",
            'person_in_charge' => "",
            'delivery_port' => ""
        );

        $gen_db->insert('customer_master', $data);
        return $gen_db->getSequence("customer_master_customer_id_seq");
    }

    function seiban_makeSeibanStock($itemId, $seiban, $qty, $locationId = 0)
    {
        global $gen_db;

        $query = "select coalesce(min(inventory_date),cast('1970-01-01' as date)) from inventory where item_id = '{$itemId}'";
        $date = $gen_db->queryOneValue($query);

        Logic_Inout::entryInout($date, $itemId, $seiban, $locationId, '', $qty, 0, "in", "", "");
    }

    function seiban_doMrp($end_date, $start_date = false)
    {
        $this->seiban_existDrop("temp_mrp_day_table");
        $this->seiban_existDrop("temp_plan_list");
        $this->seiban_existDrop("temp_seiban");
        $this->seiban_existDrop("temp_usable_stock");
        $this->seiban_existDrop("temp_seiban_stock");
        $this->seiban_existDrop("temp_seiban_order");
        $this->seiban_existDrop("temp_mrp_depend_demand");
        // for 2008
        $this->seiban_existDrop("temp_inventory_date");
        // for 2010
        $this->seiban_existDrop("temp_received_seiban_plan");

        if ($start_date == false)
            $start_date = date('Y-m-d', time() + (3600 * 24));
        $mrp = new Logic_MRP();
        $mrp->mrpMain($start_date, $end_date, false, false, "test", GEN_MRP_DAYS);
    }

    function seiban_existDrop($table)
    {
        global $gen_db;

        $query = "select * from pg_catalog.pg_tables where tablename='{$table}'";
        if ($gen_db->existRecord($query)) {
            $query = "drop table $table";
            $gen_db->query($query);
        }
    }

    function seiban_registReceived($itemId, $seiban, $qty, $date, $deadLine, $reserved = null)
    {
        global $gen_db;

        $recNum = Logic_Received::getReceivedNumber("");

        $arr = array(
            'received_number' => $recNum,
            'customer_id' => $this->customer_id,
            'received_date' => $date,
            'guarantee_grade' => 0,
            'remarks_header' => '',
            'delivery_customer_id' => $this->customer_id,
        );

        $gen_db->insert('received_header', $arr);
        $tranId = $gen_db->getSequence("received_header_received_header_id_seq");

        $arr = array(
            'received_header_id' => $tranId,
            'item_id' => $itemId,
            'received_quantity' => $qty,
            'product_price' => 0,
            'dead_line' => $deadLine,
            'remarks' => "",
            'seiban' => $seiban,
        );

        $gen_db->insert('received_detail', $arr);
        $detailId = $gen_db->getSequence("received_detail_received_detail_id_seq");

        // 引当数の登録/更新
        if ($reserved != null)
            Logic_Reserve::updateReserveQuantity($detailId, $itemId, $deadLine, $reserved);
        
        // 受注品目がダミー品目だった場合、子品目使用予約を登録
        Logic_Received::entryUsePlanForDummy($detailId);

        return $detailId;
    }

    function seiban_registPlan($itemId, $seiban, $qty, $date)
    {
        global $gen_db;

        $year = date('Y', strtotime($date));
        $month = date('m', strtotime($date));
        $day = (int) date('d', strtotime($date));

        $query = "select * from plan where plan_year = $year and plan_month = $month and item_id = $itemId and seiban = '$seiban'";
        if ($gen_db->existRecord($query)) {
            $data = array(
                "day$day" => $qty,
            );
            $where = "plan_year = $year and plan_month = $month and item_id = $itemId and seiban = '$seiban'";
            $gen_db->update('plan', $data, $where);
        } else {
            $arr = array(
                'plan_year' => $year,
                'plan_month' => $month,
                'item_id' => $itemId,
                'seiban' => $seiban,
                'classification' => 0,
                'plan_quantity' => 0, // これはあとで再計算される
                'remarks' => '',
            );
            for ($i = 1; $i <= 31; $i++) {
                $name = "day{$i}";
                $arr["day{$i}"] = ($i == $day ? $qty : 0);
            }

            $gen_db->insert('plan', $arr);
        }
        $planId = $gen_db->getSequence("plan_plan_id_seq");

        return $planId;
    }

    function seiban_checkMrpResult($id, $seiban, $finishDate, $isCheckAlloc, $isAlloc, $trueRes)
    {
        global $gen_db;

        $query = "select sum(arrangement_quantity) from mrp where 1=1";
        if (is_numeric($id))
            $query .= " and item_id = '{$id}'";
        if ($seiban != null)
            $query .= " and seiban = '{$seiban}'";
        if ($isCheckAlloc) {
            if ($isAlloc) {
                $query .= " and order_class = '99'";
            } else {
                $query .= " and order_class <> '99'";
            }
        }
        if ($finishDate != "")
            $query .= " and arrangement_finish_date = '{$finishDate}'";

        $res = $gen_db->queryOneValue($query);

        $msg = $trueRes . ":" . $res;

        if ($trueRes == "0") {
            $this->assertTrue($res == "0" || $res == false);
            if (!($res == "0" || $res == false))
                $msg .= "   ***** Failure!!! *****";
        } else {
            $this->assertTrue($res == $trueRes);
            if ($res != $trueRes)
                $msg .= "   ***** Failure!!! *****";
        }

        var_dump($msg);
    }

    function seiban_checkSeibanChange($itemId, $locationId, $sourceSeiban, $distSeiban, $quantity)
    {
        global $gen_db;

        $query = "select sum(quantity) from seiban_change " .
                " where item_id = '{$itemId}' " .
                " and location_id = '{$locationId}'" .
                " and source_seiban = '{$sourceSeiban}'" .
                " and dist_seiban = '{$distSeiban}'";

        $res = $gen_db->queryOneValue($query);

        var_dump($quantity . ":" . $res);

        if ($quantity == "0") {
            $this->assertTrue($res == "0" || $res == false);
        } else {
            $this->assertTrue($res == $quantity);
        }
    }

    function seiban_registInout($itemId, $class, $date, $qty, $locationId = 0)
    {
        Logic_Inout::entryInout($date, $itemId, "", $locationId, '', $qty, 0, "in", "", "");
    }

    function seiban_registOrder($itemId, $seiban, $qty, $orderDate, $deadLine, $partnerId)
    {
        // 親テーブル
        $orderHeaderId = Logic_Order::entryOrderHeader(0, null, null, $orderDate, $partnerId, '', null, null, null, null);

        // 子テーブル
        return Logic_Order::entryOrderDetail(null, $orderHeaderId, 1, null, $seiban, $itemId, null, null, null, null, $qty, $deadLine, false, $partnerId, 0, "", null, null, null, 1, null, false);
    }

    function seiban_registAchievement($itemId, $achDate)
    {
        global $gen_db;

        $query = "select order_detail_id, order_detail_quantity from order_detail where item_id = {$itemId}";
        $res = $gen_db->getArray($query);
        if (is_array($res)) {
            foreach ($res as $row) {
                Logic_Achievement::entryAchievement(null, $achDate, null, null, $row['order_detail_id'], $row['order_detail_quantity'], "", 0, 0, 0, '', '', 0, true, 0, null, null, null, null, 0, null, null, null, null);
            }
        }
    }

    // 休日とLTを考慮して調整した納期を返す
    function seiban_getAdjustDeadline($dateStr, $lt, $slt)
    {
        $date = strtotime($dateStr);
        for ($i = 1; $i <= ($lt + $slt); $i++) {
            $date -= (3600 * 24);
            $date = strtotime($this->seiban_getNotHoliday(date('Y-m-d', $date)));
        }
        if ($date < strtotime("+1 day")) {
            $date = strtotime("+1 day");
        }

        return date('Y-m-d', $date);
    }

    // 与えられた日以前で、休日ではない最後の日を探して返す
    function seiban_getNotHoliday($dateStr)
    {
        global $gen_db;

        $date = strtotime($dateStr);
        while (true) {
            $query = "select * from holiday_master where holiday = '" . date('Y-m-d', $date) . "'";
            if (!$gen_db->existRecord($query))
                return date('Y-m-d', $date);
            $date -= (3600 * 24);
        }
    }

    // まるめ単位（ロット単位）の設定
    function seiban_setLotUnit($itemId, $lotUnit, $lotUnitLimit, $lotUnit2)
    {
        global $gen_db;

        if ($lotUnitLimit == '')
            $lotUnitLimit = 'null';
        if ($lotUnit2 == '')
            $lotUnit2 = 'null';
        $query = "update item_order_master " .
                " set default_lot_unit = {$lotUnit}, default_lot_unit_limit = {$lotUnitLimit},  default_lot_unit_2 = {$lotUnit2} " .
                " where item_id = {$itemId}";
        $gen_db->query($query);
    }

}