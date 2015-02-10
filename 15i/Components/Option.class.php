<?php

/**
 * Gen_Option
 *
 * @author T.Banno
 * @copyright 2012 e-commode
 */

/**
 * オプション配列を集めたクラス
 *
 * @author T.Banno
 */
class Gen_Option
{

    //*******************************************
    //  管理区分
    //*******************************************
    static function getOrderClass($type)
    {
        $defaultArr = array(
            1 => _g('MRP'),
            0 => _g('製番'),
        );
        if (GEN_LOT_MANAGEMENT) {
            $defaultArr[2] = _g('ロット');
        }
        switch ($type) {
            case "options":
                $array = $defaultArr;
                break;
            case "list":
            case "list-query":
                $array = $defaultArr;
                if ($type == "list-query")
                    $array = self::getCaseConstruction($array);
                break;
            case "search":
                $array = array(null => _g('(すべて)')) + $defaultArr;
                break;
            case "model":
                $array = self::getModelArray($defaultArr);
                break;
            default:
                $array = array();
                break;
        }
        return $array;
    }

    //*******************************************
    //  手配区分
    //*******************************************
    static function getPartnerClass($type)
    {
        switch ($type) {
            case "options":
                $array = array(3 => _g('内製'), 0 => _g('発注'), 1 => _g('外注(支給なし)'), 2 => _g('外注(支給あり)'));
                break;
            case "options-non":
                $array = array('' => _g('(なし)'), 3 => _g('内製'), 0 => _g('発注'), 1 => _g('外注(支給なし)'), 2 => _g('外注(支給あり)'));
                break;
            case "search":
                $array = array(null => _g('(すべて)'), 3 => _g('内製'), 0 => _g('発注'), 1 => _g('外注(支給なし)'), 2 => _g('外注(支給あり)'));
                break;
            case "search-progress":
                $array = array(null => _g('(すべて)'), '0' => _g('内製'), '1' => _g('注文'), '2' => _g('外製'));
                break;
            case "search-cost":
                $array = array(null => _g('(すべて)'), '0' => _g('内製'), 1 => _g('外注(支給なし)'), 2 => _g('外注(支給あり)'));
                break;
            case "list":
            case "list-query":
                $array = array(3 => _g('内製'), 0 => _g('発注'), 1 => _g('外注(支給なし)'), 2 => _g('外注(支給あり)'));
                if ($type == "list-query")
                    $array = self::getCaseConstruction($array);
                break;
            case "model":
                $array = array(0, 3);
                break;
            default:
                $array = array();
                break;
        }
        return $array;
    }

    //*******************************************
    //  締日グループ
    //*******************************************
    static function getMonthlyLimit($type)
    {
        $defaultArr = array(
            '31' => _g('末'),
            '25' => '25',
            '20' => '20',
            '15' => '15',
            '10' => '10',
            '5' => '5',
        );
        switch ($type) {
            case "options":
                $array = $defaultArr;
                break;
            case "list":
            case "list-query":
                $array = $defaultArr;
                if ($type == "list-query")
                    $array = self::getCaseConstruction($array);
                break;
            case "search":
                $array = array(null => _g('(すべて)')) + $defaultArr;
                break;
            case "model":
                $array = self::getModelArray($defaultArr);
                break;
            default:
                $array = array();
                break;
        }
        return $array;
    }

    //*******************************************
    //  請求パターン
    //*******************************************
    static function getBillPattern($type)
    {
        switch ($type) {
            case "options":
                $array = array('0' => _g('締め（残高表示なし）'), '1' => _g('締め（残高表示あり）'), '2' => _g('都度'));
                break;
            case "options-0":
                $array = array('0' => _g('締め（残高表示なし）'));
                break;
            case "options-1":
                $array = array('0' => _g('締め（残高表示なし）'), '1' => _g('締め（残高表示あり）'));
                break;
            case "options-2":
                $array = array('2' => _g('都度'));
                break;
            case "search":
                $array = array(null => _g('(すべて)'), '0' => _g('締め（残高表示なし）'), '1' => _g('締め（残高表示あり）'), '2' => _g('都度'));
                break;
            case "search-bill":
                $array = array(null => _g('(すべて)'), '0' => _g('締め（残高表示なし）'), '1' => _g('締め（残高表示あり）'));
                break;
            case "list":
            case "list-query":
                $array = array('0' => _g('締め（残高表示なし）'), '1' => _g('締め（残高表示あり）'), '2' => _g('都度'));
                if ($type == "list-query")
                    $array = self::getCaseConstruction($array);
                break;
            case "model":
                $array = array(0, 1, 2);
                break;
            default:
                $array = array();
                break;
        }
        return $array;
    }

    //*******************************************
    //  入金/支払種別
    //*******************************************
    static function getWayOfPayment($type)
    {
        $defaultArr = array(
            1 => _g('現金'),
            2 => _g('振込'),
            3 => _g('小切手'),
            4 => _g('手形'),
            5 => _g('相殺'),
            6 => _g('値引'),
            7 => _g('振込手数料'),
            9 => _g('先振込'),
            10 => _g('代引'),
            8 => _g('その他'),
        );
        switch ($type) {
            case "options":
                $array = $defaultArr;
                break;
            case "list":
            case "list-query":
                $array = $defaultArr;
                if ($type == "list-query")
                    $array = self::getCaseConstruction($array);
                break;
            case "search":
                $array =  $array = array(null => _g('(すべて)')) + $defaultArr;;
                break;
            case "receivable":
                // 帳票表示のため poファイル に取り込まない
                $array = array(1 => "期間中現金入金額", 2 => "期間中振込入金額", 3 => "期間中小切手入金額", 4 => "期間中手形入金額",
                    5 => "期間中相殺入金額", 6 => "期間中値引入金額", 7 => "期間中振込手数料入金額", 9 => "期間中先振込入金額", 10 => "期間中代引入金額", 8 => "期間中その他入金額",
                    11 => "入出金種別1", 11 => "入出金種別1", 11 => "入出金種別1",);
                break;
            case "payment":
                // 帳票表示のため poファイル に取り込まない
                $array = array(1 => "期間中現金支払額", 2 => "期間中振込支払額", 3 => "期間中小切手支払額", 4 => "期間中手形支払額",
                    5 => "期間中相殺支払額", 6 => "期間中値引支払額", 7 => "期間中振込手数料支払額", 9 => "期間中先振込支払額", 10 => "期間中代引支払額", 8 => "期間中その他支払額");
                break;
            case "model":
                $array = self::getModelArray($defaultArr);
                break;
            default:
                $array = array();
                break;
        }
        return $array;
    }

    //*******************************************
    //  表示区分
    //    search          : する/しない
    //    search-show     : 表示しない/表示する
    //    search-holizon  : する/しない/横軸に表示
    //*******************************************
    static function getTrueOrFalse($type)
    {
        switch ($type) {
            case "search":
                $array = array('true' => _g('する'), 'false' => _g('しない'));
                break;
            case "search-show":
                $array = array('false' => _g('表示しない'), 'true' => _g('表示する'));
                break;
            case "search-holizon":
                $array = array('true' => _g('する'), 'false' => _g('しない'), 'holizon' => _g('横軸に表示'));
                break;
            case "search-include":
                $array = array('true' => _g('含める'), 'false' => _g('含めない'));
                break;
            default:
                $array = array();
                break;
        }
        return $array;
    }

    //*******************************************
    //  印刷区分
    //*******************************************
    static function getPrinted($type)
    {
        switch ($type) {
            case "search":
                $array = array("0" => _g("(すべて)"), "1" => _g("未印刷のみ"), "2" => _g("印刷済のみ"));
                break;
            default:
                $array = array();
                break;
        }
        return $array;
    }

    //*******************************************
    //  見積ランク
    //*******************************************
    static function getEstimateRank($type)
    {
        switch ($type) {
            case "options":
                $array = array(0 => _g('(なし)'), 1 => _g('A'), 2 => _g('B'), 3 => _g('C'), 4 => _g('D'), 5 => _g('E'));
                break;
            case "search":
                $array = array(null => _g("(すべて)"), 0 => _g('(なし)'), 1 => _g('A'), 2 => _g('B'), 3 => _g('C'), 4 => _g('D'), 5 => _g('E'));
                break;
            case "list":
            case "list-query":
                $array = array(0 => "", 1 => _g('A'), 2 => _g('B'), 3 => _g('C'), 4 => _g('D'), 5 => _g('E'));
                if ($type == "list-query")
                    $array = self::getCaseConstruction($array);
                break;
            case "model":
                $array = array(0, 5);
                break;
            default:
                $array = array();
                break;
        }
        return $array;
    }
    
    //*******************************************
    //  スケジュールの繰り返し
    //*******************************************
    static function getRepeatSchedule($type)
    {
        // 選択肢を変更する際は、ここだけでなく Logic_Schedule のSQL（スケジュール表示部）も変更する必要がある。
        $array = array(''=>_g("(なし)"), '0'=>_g("毎日"), '1'=>_g("休業日以外"), '2'=>_g("毎週"), '3'=>_g("毎月第1"), '4'=>_g("毎月第2"), '5'=>_g("毎月第3"), '6'=>_g("毎月第4"), '7'=>_g("毎月最終"), '8'=>_g("毎月"));
        if ($type == "list-query") {
            array_shift($array);    // 先頭要素を削除
            $array = self::getCaseConstruction($array);
        }
        return $array;
    }

    //*******************************************
    //  日付
    //*******************************************
    static function getDays($type)
    {
        switch ($type) {
            case "list":
                $array = array(
                    1 => _g('1日'), 2 => _g('2日'), 3 => _g('3日'), 4 => _g('4日'), 5 => _g('5日'),
                    6 => _g('6日'), 7 => _g('7日'), 8 => _g('8日'), 9 => _g('9日'), 10 => _g('10日'),
                    11 => _g('11日'), 12 => _g('12日'), 13 => _g('13日'), 14 => _g('14日'), 15 => _g('15日'),
                    16 => _g('16日'), 17 => _g('17日'), 18 => _g('18日'), 19 => _g('19日'), 20 => _g('20日'),
                    21 => _g('21日'), 22 => _g('22日'), 23 => _g('23日'), 24 => _g('24日'), 25 => _g('25日'),
                    26 => _g('26日'), 27 => _g('27日'), 28 => _g('28日'), 29 => _g('29日'), 30 => _g('30日'),
                    31 => _g('31日')
                );
                break;
            default:
                $array = array();
                break;
        }
        return $array;
    }

    //*******************************************
    //  曜日
    //*******************************************
    static function getWeekdays($type)
    {
        $array = array('0'=>_g("日曜日"), '1'=>_g("月曜日"), '2'=>_g("火曜日"), '3'=>_g("水曜日"), '4'=>_g("木曜日"), '5'=>_g("金曜日"), '6'=>_g("土曜日"));
        if ($type == "list-query") {
            $array = self::getCaseConstruction($array);
        }
        return $array;
    }

    //*******************************************
    //  月
    //*******************************************
    static function getMonth($type)
    {
        switch ($type) {
            case "list":
                $array = array(
                    1 => _g('1月'), 2 => _g('2月'), 3 => _g('3月'), 4 => _g('4月'), 5 => _g('5月'),
                    6 => _g('6月'), 7 => _g('7月'), 8 => _g('8月'), 9 => _g('9月'), 10 => _g('10月'),
                    11 => _g('11月'), 12 => _g('12月')
                );
                break;
            default:
                $array = array();
                break;
        }
        return $array;
    }

    //************************************************
    //  case文作成ツール
    //*******************************************
    static function getCaseConstruction($array)
    {
        $query = "";
        foreach ($array as $key => $val) {
            $query .= " when {$key} then '{$val}' ";
        }
        return $query;
    }

    //*******************************************
    //  model配列作成ツール
    //*******************************************
    static function getModelArray($array)
    {
        $modelArr = array();
        foreach ($array as $key => $value) {
            $modelArr[] = $key;
        }
        return $modelArr;
    }

}