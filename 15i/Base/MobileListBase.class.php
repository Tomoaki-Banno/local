<?php

define("SEARCH_FIELD_PREFIX", "gen_search_");

// 抽象クラス（abstract）。インスタンスを生成できない。
//   PHP4のときは「abstract」をはずすこと

abstract class Base_MobileListBase
{
    var $searchConditionDefault;
    var $selectQuery;
    var $orderby;
    var $orderbyDefault;
    var $pageRecordCount;
    var $pageRecordCountUnit;
    var $selecterOptions;
    var $tpl;


    //************************************************
    // 抽象メソッド
    //************************************************
    // 子クラスで必ず実装しなければならない。
    //   PHP4の時はコメントアウトすること
    abstract function setSearchCondition(&$form);
    abstract function beforeLogic(&$form);
    abstract function setQueryParam(&$form);
    abstract function setViewParam(&$form);



    //************************************************
    // メイン
    //************************************************

    function execute(&$form)
    {
        global $gen_db;

        //------------------------------------------------------
        //  表示条件関連の設定（子クラスで実装）
        //------------------------------------------------------
        $this->setSearchCondition($form);

        //------------------------------------------------------
        //  表示条件コントロールのデフォルト値
        //------------------------------------------------------
        foreach ($form['gen_searchControlArray'] as &$ctl) {
            if (!isset($ctl['type'])) {
                $ctl['type'] = "textbox";
            }
        }
        unset($ctl);

        //------------------------------------------------------
        //  sessionから$formに表示条件を復元、またはsessionに$formの表示条件を格納する。
        //------------------------------------------------------
        //    また暗黙的に、「orderby」「page」も格納/復元される。（OrderBy処理とページングで使用）
        $this->searchConditonSession($form, $form['gen_searchControlArray']);

        //------------------------------------------------------
        //  user_id と action の取得
        //------------------------------------------------------
        $user_id = Gen_Auth::getCurrentUserId();
        $action = get_class($this);
        if (isset($form['gen_pageMode'])) $action .= "_" . $form['gen_pageMode'];
        $form['gen_actionWithPageMode'] = $action;    // list.tplで使用。gen_script.jsのgen_pin()のコメントを参照

        //------------------------------------------------------
        //  ピンどめされたデフォルト表示条件の読み出し
        //------------------------------------------------------
        $form['gen_pins'] = array();
        if (!isset($form['gen_searchConditionClear'])) {
            $colInfoJson = $gen_db->queryOneValue("select pin_info from page_info where user_id = '{$user_id}' and action = '{$action}'");

            // 登録の際に「\」が「￥」に自動変換されているので、ここで元に戻す必要がある。
            if (($colInfoObj = json_decode(str_replace("￥","\\",$colInfoJson))) != null) {
                foreach ($colInfoObj as $key=>$val) {
                    if (!isset($form[$key])) {    // 表示条件がユーザーにより指定された場合は読み出ししない
                        $form[$key] = $val;

                        // 日付パターンがピンどめされていた場合
                        if (substr($key, 0, 16)=='gen_datePattern_') {
                            // 選択肢を追加・削除・変更するときは、function.gen_search_control と gen_calendar.js も変更が必要（'datePattern'で検索）
                            switch ($val) {
                            case '0':	// なし
                                    $from = ''; $to = ''; break;
                            case '1':	// 今日
                                    $from = date('Y-m-d');
                                    $to = $from;
                                    break;
                            case '2':	// 昨日
                                    $from = date('Y-m-d', strtotime('-1 day'));
                                    $to = $from;
                                    break;
                            case '3':	// 今週
                                    $from = date('Y-m-d', strtotime('-'. date('w') . ' day'));
                                    $to = date('Y-m-d', strtotime($from . ' +6 days'));
                                    break;
                            case '4':	// 先週
                                    $from = date('Y-m-d', strtotime('-'. ((int)date('w')+7) . ' day'));
                                    $to = date('Y-m-d', strtotime($from . ' +6 days'));
                                    break;
                            case '5':	// 今月
                                    $from = date('Y-m-1');
                                    $to = date('Y-m-t');
                                    break;
                            case '6':	// 先月
                                    $from = date('Y-m-1', strtotime('-1 month'));
                                    $to = date('Y-m-t', strtotime('-1 month'));
                                    break;
                            case '7':	// 今年
                                    $from = date('Y-1-1');
                                    $to = date('Y-12-31');
                                    break;
                            case '8':	// 昨年
                                    $from = date('Y-1-1', strtotime('-1 year'));
                                    $to = date('Y-12-31', strtotime('-1 year'));
                                    break;
                            case '9':	// 明日
                                    $from = date('Y-m-d', strtotime('+1 day'));
                                    $to = $from;
                                    break;
                            case '10':	// 来週
                                    $from = date('Y-m-d', strtotime('-'. ((int)date('w')-7) . ' day'));
                                    $to = date('Y-m-d', strtotime($from . ' +6 days'));
                                    break;
                            case '11':	// 来月
                                    $from = date('Y-m-1', strtotime('+1 month'));
                                    $to = date('Y-m-t', strtotime('+1 month'));
                                    break;
                            case '12':	// 来年
                                    $from = date('Y-1-1', strtotime('+1 year'));
                                    $to = date('Y-12-31', strtotime('+1 year'));
                                    break;
                            case '13':    // 今年度
                            case '14':    // 昨年度
                            case '15':    // 来年度
                                    $startMonth = (isset($_SESSION['gen_setting_company']->starting_month_of_accounting_period) ? 
                                        $_SESSION['gen_setting_company']->starting_month_of_accounting_period : 1);
                                    $year = date('Y');
                                    $nowMonth = date('m');
                                    if ($val == '13') {         // 今年度
                                        if ($startMonth > $nowMonth) {
                                            --$year;
                                        }
                                    } else if ($val == '14') {  // 昨年度
                                        if ($startMonth <= $nowMonth) {
                                            --$year;
                                        } else {
                                            $year -= 2;
                                        }
                                    } else {                    // 来年度
                                        if ($startMonth <= $nowMonth) {
                                            ++$year;
                                        }
                                    }
                                    if ($startMonth < 10) {
                                        $startMonth = "0{$startMonth}";
                                    }
                                    $from = "{$year}-{$startMonth}-01"; 
                                    $to = date('Y-m-d', strtotime($from . ' +1 year -1 day'));
                                    break;
                            case '16':    // 今日以前
                                    $from = "";
                                    $to = date('Y-m-d');
                                    break;
                            default:	// なし
                                    $from = ''; $to = ''; break;
                            }
                            $name = substr($key, 16);
                            if ($from!='' && !isset($form[$name.'_from'])) $form[$name.'_from'] = $from;
                            if ($to!='' && !isset($form[$name.'_to'])) $form[$name.'_to'] = $to;
                        }
                    }
                    $form['gen_pins'][] = $key;
                }
            }
        }

        //------------------------------------------------------
        // 表示条件関連の設定2（$form['gen_searchControlArray'] の name と value）
        //------------------------------------------------------
        // 07iでは $form['gen_searchControlArray'] の name と value は子クラス側で設定していたが、
        // 08iでは ここで自動設定するようにした。
        foreach ($form['gen_searchControlArray'] as &$ctl) {
            if (isset($ctl["field"])) { // literal は field がないので何も処理しない
                $type = $ctl["type"];
                $name = SEARCH_FIELD_PREFIX . $ctl["field"];
                $ctl["name"] = $name;
                if ($type == "dateFromTo" || $type == "dateTimeFromTo") {
                    $ctl["valueFrom"] = @$form[$name . "_from"];
                    $ctl["valueTo"] = @$form[$name . "_to"];

                    // 日付パターンがピンどめされていた場合
                    if (isset($form["gen_datePattern_$name"])) {
                        $ctl["datePattern"] = $form["gen_datePattern_$name"];
                    }
                } else if ($type == "yearMonth") {
                    $ctl["valueYear"] = @$form[$name . "_Year"];
                    $ctl["valueMonth"] = @$form[$name . "_Month"];
                } else if ($type == "select") {
                    $ctl["selected"] = @$form[$name];
                } else {
                    $ctl["value"] = @$form[$name];
                }
            }
        }
        unset($ctl);

        //------------------------------------------------------
        // 表示条件で日付項目に不正値が指定されたときの処理
        //------------------------------------------------------
        // 日付項目に不正値が指定された場合、検索値を消去する
        // （デフォルト値があれば、この次の処理で設定される）
        foreach ($form['gen_searchControlArray'] as &$ctl) {
            if ($ctl['type'] == "dateFromTo") {
                $name1 = SEARCH_FIELD_PREFIX . $ctl["field"] . "_from";
                if (isset($form[$name1])) {
                    if ($form[$name1]!='' && !Gen_String::isDateString($form[$name1])) {
                        unset($form[$name1]);
                    }
                }
                $name2 = SEARCH_FIELD_PREFIX . $ctl["field"] . "_to";
                if (isset($form[$name2])) {
                    if ($form[$name2]!='' && !Gen_String::isDateString($form[$name2])) {
                        unset($form[$name2]);
                    }
                }
                if (isset($form[$name1]) && isset($form[$name2]) && $form[$name1]!='' && $form[$name2]!='') {
                    if (strtotime($form[$name1]) > strtotime($form[$name2])) {
                        $temp = $form[$name1];
                        $form[$name1] = $form[$name2];
                        $form[$name2] = $temp;
                    }
                }
            }
            if ($ctl['type'] == "calendar") {
                $name1 = SEARCH_FIELD_PREFIX . $ctl["field"];
                if (isset($form[$name1])) {
                    if (!Gen_String::isDateString($form[$name1])) {
                        unset($form[$name1]);
                    }
                }
            }
        }
        unset($ctl);

        //------------------------------------------------------
        //  表示条件デフォルト値の設定
        //------------------------------------------------------
        if (!isset($form['gen_searchConditionClear'])) {
            foreach ($form['gen_searchControlArray'] as &$ctl) {
                if (isset($ctl["default"]) && !isset($form[$ctl["name"]])) {
                    $form[$ctl["name"]] = $ctl["default"];
                    $ctl["value"] = $ctl["default"];
                    if ($ctl["type"] == "select") {
                        $ctl["selected"] = $ctl["default"];
                    }
                }
                if (isset($ctl["defaultFrom"]) && !isset($form[$ctl["name"] . "_from"]) && !isset($form["gen_datePattern_" . $ctl["name"]])) {
                    $form[$ctl["name"] . "_from"] = $ctl["defaultFrom"];
                    $ctl["valueFrom"] = $ctl["defaultFrom"];
                }
                if (isset($ctl["defaultTo"]) && !isset($form[$ctl["name"] . "_to"]) && !isset($form["gen_datePattern_" . $ctl["name"]])) {
                    $form[$ctl["name"] . "_to"] = $ctl["defaultTo"];
                    $ctl["valueTo"] = $ctl["defaultTo"];
                }
                if (isset($ctl["defaultYear"]) && !isset($form[$ctl["name"] . "_Year"])) {
                    $form[$ctl["name"] . "_Year"] = $ctl["defaultYear"];
                    $ctl["valueYear"] = $ctl["defaultYear"];
                }
                if (isset($ctl["defaultMonth"]) && !isset($form[$ctl["name"] . "_Month"])) {
                    $form[$ctl["name"] . "_Month"] = $ctl["defaultMonth"];
                    $ctl["valueMonth"] = $ctl["defaultMonth"];
                }
            }
            unset($ctl);
        }

        //------------------------------------------------------
        //　表示条件の変換（子クラスで実装）
        //------------------------------------------------------
        // 表示条件に対する Convert（不正値の変換など）はこのメソッド内で行う。
        // 以前は Converter を使用していたが、Converter はセッション値やピン留め値に適用されない
        // （それらの復元の前に実行される）ため、このメソッドをもうけた。
        if (method_exists($this, 'convertSearchCondition')) {
            $converter = new Gen_Converter($form);
            $this->convertSearchCondition($converter, $form);
        }

        //------------------------------------------------------
        //  クエリ前ロジックの実行（子クラスで実装）
        //------------------------------------------------------
        $this->beforeLogic($form);

        //------------------------------------------------------
        //  クエリ用パラメータをクラス変数に設定（子クラスで実装）
        //------------------------------------------------------
        $this->setQueryParam($form);

        //------------------------------------------------------
        //  表示関係のパラメータをクラス変数に設定（子クラスで実装）
        //------------------------------------------------------
        $this->setViewParam($form);

        //------------------------------------------------------
        // リロードチェック
        //------------------------------------------------------
        $isReload = false;
        $this->listReloadCheck($form['gen_page_request_id'], $isReload);

        //------------------------------------------------------
        // 列/表示条件情報の読み出し
        //------------------------------------------------------
        // カラムモード・ページモードつきのaction
        $actionWithColumnMode = $action;
        if (isset($form['gen_columnMode'])) $actionWithColumnMode .= "_" . $form['gen_columnMode'];
        if (isset($form['gen_pageMode'])) $actionWithColumnMode .= "_" . $form['gen_pageMode'];
        $form['gen_actionWithColumnMode'] = $actionWithColumnMode;    // list.tplで使用。gen_colwidth.jsのsaveColwidth()のコメントを参照

        // ページモードつきのaction
        $actionWithPageMode = $action;
        if (isset($form['gen_pageMode'])) $actionWithPageMode .= "_" . $form['gen_pageMode'];
        $form['gen_actionWithPageMode'] = $actionWithPageMode;    // list.tplで使用。gen_colwidth.jsのsaveColwidth()のコメントを参照

        $searchColInfoArr = $this->loadSearchColumns($form, $user_id, $actionWithPageMode, $isReload);

        //------------------------------------------------------
        //  OrderBy情報の取得と保存
        //------------------------------------------------------
        // Mobile : ソートセレクタ
        $form['gen_orderbyOptions'] = array();
        foreach($form['gen_columnArray'] as $row) {
            if (@$row['sortLabel'] != "" && $row['field'] != "") {
                $form['gen_orderbyOptions'][$row['field']] = $row['sortLabel'];
            }
        }

        $orderbyColumn = $this->getOrderByColumn($form, $user_id, $action);
        if ($this->orderbyDefault != "") {
            $orderbyColumn .= ($orderbyColumn == "" ? "": ",") . $this->orderbyDefault;
        }
        $orderbyStr = "order by " . $orderbyColumn;
        // 表示条件のソート項目セット用。Mobile版では1カラム目のみ対象
        $orderbyOneArr = explode(",", $orderbyColumn);
        $orderbyOne = $orderbyOneArr[0];
        $form['gen_search_orderby'] = str_replace(" desc", "", $orderbyOne);
        $form['gen_search_orderby_desc'] = (strpos($orderbyOne, " desc") ? "true" : "");

        //------------------------------------------------------
        //  データ取得SQLの組み立て
        //------------------------------------------------------
        $whereStr = $this->getSearchCondition($form, $form['gen_searchControlArray']);

        $this->selectQuery = str_replace('[Where]', $whereStr, $this->selectQuery);
        $this->selectQuery = str_replace('[Orderby]', $orderbyStr, $this->selectQuery);

//echo($this->selectQuery);
//var_dump($gen_db->getArray($this->selectQuery));

        //------------------------------------------------------
        //  1ページの表示件数の決定
        //------------------------------------------------------
        // Mobile版とりあえず
        $pageRecordCount = "";
        //$pageRecordCount = $this->getPageCount($form);

        //------------------------------------------------------
        //  ページャー処理 & データの取得
        //------------------------------------------------------

        // Pagerで必要
        foreach ($form['gen_columnArray'] as &$ctl) {
            // type
            if (!isset($ctl['type'])) {
                $ctl['type'] = "data";
            }
        }
        unset($ctl);

        // pageが数字でなければ1ページ目とみなされる
        $page = "";
        if (isset($form[SEARCH_FIELD_PREFIX . 'page'])) {
            $page = $form[SEARCH_FIELD_PREFIX . 'page'];
        }

        // Mobile版独自（PC版ではPager内で定義されている）
        if (!Gen_String::isNumeric($page)) $page = 1;
        if (!Gen_String::isNumeric($pageRecordCount)) $pageRecordCount = 50;

        $pager = new Gen_Pager($this->selectQuery, $form['gen_columnArray'], "sum", $pageRecordCount, $page, $orderbyStr);
        $form['gen_data'] = $pager->getData();
        $form['gen_isLastPage'] = $pager->isLastPage();
        $form['gen_totalCount'] = $pager->getTotalCount();

        // Mobile版独自
        $form['gen_sumData'] = array();
        if ($form['gen_data'] && count($form['gen_data']) > 0) {
            foreach($form['gen_data'][0] as $name => $val) {
                if (substr($name, 0, 14) == "gen_aggregate_") {
                    $form['gen_sumData'][substr($name, 14)] = $val;
                }
            }
        }
        // Gen_Pagerを旧方式に戻すときは上の数行をコメントアウトし、下の1行を有効にする
        // $form['gen_sumData'] = $pager->getAggregateData();

        // Mobile版独自ナビゲータ
        $form['gen_showPage'] = $page;
        $form['gen_prevPage'] = $page - 1;
        $form['gen_nextPage'] = $page + 1;
        $form['gen_perPage'] = $pageRecordCount;
        $form['gen_lastPage'] = ((int)ceil($pager->getTotalCount() / $pageRecordCount));
        if ($form['gen_showPage'] > $form['gen_lastPage']) $form['gen_showPage'] = $form['gen_lastPage'];
     if (1==0) {
        // 先頭へ | 前の○件
        if ($showPage > 1) {
            // リンク有効
            $form['gen_nav_noEscape'] .=
                "<font color='#000000'>" .
                "<a href='index.php?action=" . h($form['gen_listAction']) . "&gen_search_page=1&gen_restore_search_condition=true'>" . _g("先頭へ") . "</a><br>" .
                "<a href='index.php?action=" . h($form['gen_listAction']) . "&gen_search_page=".($showPage-1)."&gen_restore_search_condition=true'>&lt;&lt; " . sprintf(_g("前の%s件"), $perPage) . "</a></font>";
        } else {
            // リンク無効（すでに先頭ページ）
            $this->nav .=
                "<font color='#cccccc'>" . _g("先頭へ") . "<br>" .
                "&lt;&lt; " . sprintf(_g("前の%s件"),$perPage) . "</font>";
        }

        // セパレータ
        $this->nav .= "&nbsp;&nbsp;&nbsp;[page $showPage / $lastPage]&nbsp;&nbsp;&nbsp;";

        // 次の○件 | 最後へ
        if ($showPage < $lastPage) {
            // リンク有効
            $this->nav .=
                "<font color='#000000'>" .
                "<a href='javascript:listUpdate({gen_search_page:" . ($showPage + 1) . "},false)'>" . sprintf(_g("次の%s件"),$perPage) . " &gt;&gt;</a><br>" .
                "<a href='javascript:listUpdate({gen_search_page:" . $lastPage . "},false)'>" . _g("最後へ") . "</a></font>";
        } else {
            // リンク無効（すでに最後のページ）
            $this->nav .=
                "<font color='#cccccc'>" .
                sprintf(_g("次の%s件"),$perPage) . " &gt;&gt;<br>" .
                _g("最後へ") . "</font>";
        }
    }
        //------------------------------------------------------
        //  コンボ選択肢の設定
        //------------------------------------------------------
        if (isset($this->selecterOptions)) {
            foreach ($this->selecterOptions as $optionName => $optionArray) {
                $form[$optionName] = $optionArray;
            }
        }

        //------------------------------------------------------
        //  表示条件 一致モードの状態復帰
        //------------------------------------------------------
        for ($cnt=0; $cnt<count(@$form['gen_searchControlArray']); $cnt++) {
            //  「gen_search_match_mode_」はsmarty_function_gen_search_controlで定義
            if (isset($form['gen_searchControlArray'][$cnt]["name"])) {
                $name = "gen_search_match_mode_" . $form['gen_searchControlArray'][$cnt]["name"];
                if (isset($form[$name])) {
                    $form['gen_searchControlArray'][$cnt]["matchMode"] = $form[$name];
                }
            }
        }

        //------------------------------------------------------
        //  javascript
        //------------------------------------------------------
        // 表示高速化（ソースの軽量化）およびセキュリティ確保のため、
        // JavaScriptソース中のコメントおよび空白のみの行を削除する。

        if (isset($form['gen_javascript_noEscape']))
            $form['gen_javascript_noEscape'] = Gen_String::cutCommentAndBlankLine($form['gen_javascript_noEscape']);

        //------------------------------------------------------
        //  リスト表示ログ（モバイル版）
        //------------------------------------------------------
        $mobile = ($form['gen_iPad'] ? " (". _g("iPad") .")" : ($form['gen_iPhone'] ? " (". _g("iPhone") .")" : ""));
        Gen_Log::dataAccessLog($form['gen_pageTitle'] . " (" . _g("モバイル") . ")", _g("表示") . $mobile, "");

        //------------------------------------------------------
        //  リスト表示ログ（モバイル版）
        //------------------------------------------------------
        $mobile = ($form['gen_iPad'] ? " (". _g("iPad") .")" : ($form['gen_iPhone'] ? " (". _g("iPhone") .")" : ""));
        Gen_Log::dataAccessLog($form['gen_pageTitle'] . " (" . _g("モバイル") . ")", _g("表示") . $mobile, "");
        
        //------------------------------------------------------
        //  適用テンプレートの指定
        //------------------------------------------------------
        if (isset($this->tpl)) {
            return $this->tpl;
        } else {
            return 'mobile/list.tpl';
        }
    }



    //************************************************
    // 表示条件session関連
    //************************************************

    // sessionから表示条件を復元、またはsessionに表示条件を格納する。

    // リクエスト引数（$form）にパラメータgen_restore_search_conditionが・・
    //    セットされているとき
    //        表示条件session　→　$form
    //        （$formに該当パラメータがあるときはsessionを無視）
    //    セットされていないとき
    //        $form　→　表示条件session
    //        （$formに該当パラメータがないときはsessionをクリア）

    // 第一引数Arrayに格納されたフィールドのみが表示条件とみなされる。
    // また暗黙的に、「orderby」「page」も格納/復元される。（これらはOrderBy処理とページングで使用）
    // ⇒2009ではpageのみ。orderbyは別の仕組みで扱うようになったため
    // session名は「クラス名_フィールド名」となる（例：Master_Item_List_Item_Code）

    function searchConditonSession(&$form, $searchConditionArray)
    {

        // 暗黙的にsession格納/復元対象となるデータ。
        $fieldName = "page";
        $sessionName = get_class($this) . '_' . $fieldName;
        $this->sessionOperation($form, $sessionName, SEARCH_FIELD_PREFIX . $fieldName);

        foreach ($searchConditionArray as $field) {
            if (!isset($field['field'])) continue;  // literalはfieldがないのでなにもしない

            $fieldName = $field['field'];
            $searchFieldName = SEARCH_FIELD_PREFIX . $fieldName;
            $type = $field['type'];

            if ($type == "dateFromTo" || $type == "dateTimeFromTo") {
                // 検索TypeがdateFromToのとき。ひとつのフィールドにつき2つの条件
                //（[フィールド名]_from と [フィールド名]_to）を扱う。
                $sessionNameFrom = get_class($this) . '_' . $fieldName . '_from';
                $fieldNameFrom = $searchFieldName . '_from';
                $this->sessionOperation($form, $sessionNameFrom, $fieldNameFrom);

                $sessionNameTo = get_class($this) . '_' . $fieldName . '_to';
                $fieldNameTo = $searchFieldName . '_to';
                $this->sessionOperation($form, $sessionNameTo, $fieldNameTo);
            } else if ($type == "yearMonth") {
                $sessionNameYear = get_class($this) . '_' . $fieldName . '_Year';
                $fieldNameYear= $searchFieldName . '_Year';
                $this->sessionOperation($form, $sessionNameYear, $fieldNameYear);

                $sessionNameMonth = get_class($this) . '_' . $fieldName . '_Month';
                $fieldNameMonth = $searchFieldName . '_Month';
                $this->sessionOperation($form, $sessionNameMonth, $fieldNameMonth);
            } else {
                // 一般の表示条件
                $sessionName = get_class($this) . '_' . $fieldName;
                $this->sessionOperation($form, $sessionName, $searchFieldName);

                if ($type == "textbox") {
                    $sessionName = get_class($this) . '_match_mode_' . $fieldName;
                    $fieldName = SEARCH_FIELD_PREFIX . 'match_mode_' . SEARCH_FIELD_PREFIX . $fieldName;
                    $this->sessionOperation($form, $sessionName, $fieldName);
                }
            }
        }
    }

    // 上記の処理用function。session変数ひとつを処理する。
    function sessionOperation(&$form, $sessionName, $fieldName)
    {

        if (isset($form['gen_restore_search_condition'])) {

            // 表示条件を復元するとき。sessionから表示条件を読み取ってformにセットする。
            // ただしformに値があるときはsessionは無視する
            if (isset($_SESSION[$sessionName])) {
                if (!isset($form[$fieldName])) {
                    //  値が空のときにsessionに保存されない現象に対処するため、「gen_nothing」という特殊な値を保存するようにした
                    if ($_SESSION[$sessionName] == "gen_nothing") {
                        $form[$fieldName] = "";
                    } else {
                        $form[$fieldName] = $_SESSION[$sessionName];
                    }
                }
            }
        } else {
            // 表示条件を復元しないとき。sessionにある表示条件をクリア
            unset($_SESSION[$sessionName]);
        }
        // sessionに表示条件（formパラメータ）を保存
        if (isset($form[$fieldName])) {
            // 2007-09-25 change this section
            //  値が空のときにsessionに保存されない現象に対処するため、「gen_nothing」という特殊な値を保存するようにした
            if ($form[$fieldName] == "") {
                $_SESSION[$sessionName] = "gen_nothing";
            } else {
                $_SESSION[$sessionName] = $form[$fieldName];
            }
        } else {
            unset($_SESSION[$sessionName]);
        }

    }



    //************************************************
    // 表示条件関連(Where)
    //************************************************

    // 表示条件をSQLのWhere文字列として取得する。

    //  少なくとも「where 1=1 」という文字列は返るので、ここで得られた文字列に
    // 「 and ・・」という形でwhere条件を追加してもよい。

    //  第2引数 $fieldArray の内容が検索対象フィールドとして使用される。
    //  $fieldArray は、[フィールド名] => [タイプ] という形の連想配列。タイプは下記のコード参照。
    //  ただし $fieldArray にあっても、表示条件（$form['フィールド名']）がセットされていなければ
    //  検索対象として扱われない。

    function getSearchCondition(&$form, $searchConditionArray)
    {

        $where = "where 1=1 ";    // 1=1 はダミー（常にand始まりにできるようにするため）

        foreach ($searchConditionArray as $field) {
            if (!isset($field['field'])) continue;  // literalはfieldがないのでなにもしない

            //   setSearchCondition() の 検索fieldにテーブル名を含めることができるようにした。
            //   たとえばreceived_detailテーブルのremarksを検索対象にしたい場合、従来は「remarks」としか指定
            //   できなかったため、SQL内の複数テーブルにremarksがあった場合にambigになってしまっていた。
            //   この変更により、「received_detail#remarks」という形でテーブル名つきの指定ができるようになった。
            $fieldName = str_replace("#", ".", $field['field']);

            $searchFieldName = SEARCH_FIELD_PREFIX . $field['field'];
            $type = $field['type'];

            // nosqlの処理
            $isNoSql = false;
            if (isset($field["nosql"]) && @$field["nosql"]) {
                $isNoSql = true;
            }

            // dateFromTo
            if ($type == 'dateFromTo') {
                $searchFieldFromName = $searchFieldName . "_from";
                if (isset($form[$searchFieldFromName])) {
                    if (Gen_String::isDateString($form[$searchFieldFromName])) {
                        if (!$isNoSql) $where .= " and $fieldName >= '" . $form[$searchFieldFromName] . "'";
                    }
                }

                $searchFieldToName = $searchFieldName . "_to";
                if (isset($form[$searchFieldToName])) {
                    if (Gen_String::isDateString($form[$searchFieldToName])) {
                        if (!$isNoSql) $where .= " and $fieldName <= '" . $form[$searchFieldToName] . "'";
                    }
                }

            // dateTimeFromTo
            } else if ($type == 'dateTimeFromTo') {
                $searchFieldFromName = $searchFieldName . "_from";
                if (isset($form[$searchFieldFromName])) {
                    if (Gen_String::isDateTimeString($form[$searchFieldFromName])) {
                        if (!$isNoSql) $where .= " and $fieldName >= '" . $form[$searchFieldFromName] . "'";
                    }
                }

                $searchFieldToName = $searchFieldName . "_to";
                if (isset($form[$searchFieldToName])) {
                    if (Gen_String::isDateTimeString($form[$searchFieldToName])) {
                        //  終了日時に時刻が含まれていない場合、終了日いっぱいまで含めるように変更。
                        //  たとえば終了日が1/1と指定された場合、従来は「 <= '2008-01-01'」のようにしていたが、
                        //  これだと「1/1の0:00まで」と解釈されてしまい、1/1が含まれず不自然。
                        //  このような場合、「 <= '2008-01-01 23:59:59.9999'」とするようにした。
                        //  ※ ちなみに・・「 < '2008-01-02'」 のほうが自然だが、この形だとurlParamの指定に困る
                        $timeStr = "";
                        if (strpos($form[$searchFieldToName], ":")===FALSE) // 時刻が含まれていない場合
                            $timeStr = " 23:59:59.9999";
                        if (!$isNoSql) {
                            $where .= " and $fieldName <= '" . $form[$searchFieldToName] . $timeStr . "'";
                        }
                    }
                }

            // yearMonth
            } else if ($type == 'yearMonth') {
                $searchFieldYearName = $searchFieldName . "_Year";
                if (isset($form[$searchFieldYearName])) {
                    if (is_numeric($form[$searchFieldYearName])) {
                        if (!$isNoSql) $where .= " and $fieldName" . "_year = '" . $form[$searchFieldYearName] . "'";
                    }
                }

                $searchFieldMonthName = $searchFieldName . "_Month";
                if (isset($form[$searchFieldMonthName])) {
                    if (is_numeric($form[$searchFieldMonthName])) {
                        if (!$isNoSql) $where .= " and $fieldName" . "_month = '" . $form[$searchFieldMonthName] . "'";
                    }
                }

            // それ以外
            } else if (isset($form[$searchFieldName])) {

                if ($form[$searchFieldName] != "" && $form[$searchFieldName] != 'null') {
                    switch ($type) {
                    case 'select':
                    case 'dropdown':
                    case 'ajaxdropdown':
                    case 'hidden':
                    case 'popup':
                        // 常に完全一致
                        if (!$isNoSql) {
                            if ($fieldName == "item_group_id" || substr($fieldName,-14) == ".item_group_id") {
                                $st = $form[$searchFieldName];
                                $where .= " and ($fieldName = '$st' or {$fieldName}_2 = '$st' or {$fieldName}_3 = '$st')";
                            } else {
                                $where .= " and $fieldName = '" . $form[$searchFieldName] . "'";
                            }
                        }
                        break;
                    case 'calendar':
                        // 常に完全一致
                        if (Gen_String::isDateString($form[$searchFieldName])) {
                            if (!$isNoSql) $where .= " and $fieldName = '" . $form[$searchFieldName] . "'";
                        }
                        break;
                    case 'textbox':
                        if (!$isNoSql) {
                            $fieldName = "cast($fieldName as text)";
                            // field2 は textboxのときのみ有効な特殊パラメータ。複数のカラムを対象とした検索をおこないたい時に使用する。
                            $fieldName2 = "";
                            if (isset($field['field2'])) {
                                $fieldName2 = str_replace("#", ".", $field['field2']);
                                $fieldName2 = "cast($fieldName2 as text)";
                            }

                            // エスケープ
                            //    like では「_」「%」がワイルドカードとして扱われる
                            $searchStr = str_replace('%', '\\\\%', str_replace('_', '\\\\_', $form[$searchFieldName]));

                            // 「gen_search_match_mode_」はsmarty_function_gen_search_controlで定義
                            switch(@$form["gen_search_match_mode_" . $searchFieldName]) {
                            case "1":   // 前方一致
                                $where .= " and ($fieldName ilike '{$searchStr}%'";
                                if ($fieldName2 != '') $where .= " or $fieldName2 ilike '{$searchStr}%'";
                                $where .= ")";
                                break;
                            case "2":   // 後方一致
                                $where .= " and ($fieldName ilike '%{$searchStr}'";
                                if ($fieldName2 != '') $where .= " or $fieldName2 ilike '%{$searchStr}'";
                                $where .= ")";
                                break;
                            case "3":   // 完全一致
                                $where .= " and ($fieldName ilike '{$searchStr}'";
                                if ($fieldName2 != '') $where .= " or $fieldName2 ilike '{$searchStr}'";
                                $where .= ")";
                                break;
                            case "4":   // 含まない
                                $where .= " and ($fieldName not ilike '%{$searchStr}%'";
                                if ($fieldName2 != '') $where .= " and $fieldName2 not ilike '%{$searchStr}%'";
                                $where .= ")";
                                break;
                            case "5":   // で始まらない
                                $where .= " and ($fieldName not ilike '{$searchStr}%'";
                                if ($fieldName2 != '') $where .= " and $fieldName2 not ilike '{$searchStr}%'";
                                $where .= ")";
                                break;
                            case "6":   // で終わらない
                                $where .= " and ($fieldName not ilike '%{$searchStr}'";
                                if ($fieldName2 != '') $where .= " and $fieldName2 not ilike '%{$searchStr}'";
                                $where .= ")";
                                break;
                            case "9":   // 正規表現　-> 現在未使用。不正なパターンを指定されたときSQLエラーになる問題の対処が難しいため
                                $where .= " and ($fieldName ~* '{$searchStr}'";
                                if ($fieldName2 != '') $where .= " and $fieldName2 ~* '{$searchStr}'";
                                $where .= ")";
                                break;
                            default:    // 部分一致（デフォルト）
                                // スペース区切りによるAND検索にも対応
                                $arr = array($fieldName);
                                if ($fieldName2 != '') $arr[] = $fieldName2;
                                $where .= self::_getMultiWordWhere($arr, $searchStr);
                                break;
                            }
                        }
                        break;

                    default:    // literal, nosql
                        break;

                    }
                }
            }
        }
        return $where;
    }

    // SQLのWHERE部の組み立て補助
    // Logic_Dropdownにあるものとまったく同じ
    private function _getMultiWordWhere($colArr, $search)
    {
        $search = str_replace('　', ' ', $search);    // 全角スペースを半角に
        $searchArr = explode(' ', $search);
        $res = '';
        foreach($searchArr as $word) {
            $res .= ' and (';
            $isFirst = true;
            foreach($colArr as $col) {
                if ($isFirst) {
                    $isFirst = false;
                } else {
                    $res .= ' or ';
                }
                $res .= "cast({$col} as text) ilike '%{$word}%'";
            }
            $res .= ')';
        }
        return $res;
    }


    //************************************************
    // Order By 関連
    //************************************************

    // Mobileで大幅に変更
    // Order By 対象カラムの取得
    //      ・POSTされたソートカラム指定 （$postColumn）
    //      ・既存のソートカラム情報 （page_infoテーブルから読み出す）
    function getOrderByColumn($form, $userId, $action)
    {
        global $gen_db;

        $sortColumn = "";

        // POSTされたソートカラム指定
        $sortColumn = @$form['gen_search_orderby'];
        if (@$form['gen_search_orderby_desc']=='true') $sortColumn .= " desc";

        // 既存のソートカラム
        if ($sortColumn == "") {
            $query = "select orderby from page_info where user_id = '{$userId}' and action = '{$action}'";
            $sortColumn = $gen_db->queryOneValue($query);
        }

        // ソートカラムが列リストに含まれていなければ、ソートカラムを削除する。
        //  ユーザーにより並べ替え指定されている列が、プログラム変更によってSQLから削除された場合に
        //  エラーになるのを防ぐ。
        if ($sortColumn != "") {
            $arr = $form['gen_columnArray'];
            $pureSortColumn = trim(str_replace(" desc", "", $sortColumn));
            $obExist = false;
            if (is_array($arr)) {
                foreach ($arr as $key=>$col) {
                    if (@$arr[$key]['field'] == $pureSortColumn) {
                        $obExist = true;
                        break;
                    }
                }
            }
            if (!$obExist) {
                $sortColumn = "";
            }
        }

        // ソートカラムpage_infoテーブルに保存
        //  デフォルトソートカラム（次のセクションで設定）は保存しないことに注意。
        //  デフォルトソートカラムは常にorder byの最後に来る必要があるため。
        //  保存してしまうと次のソートPost時に、Postカラムよりデフォルトカラムが優先されてしまう。
        $data = array(
            'orderby' => $sortColumn,
        );
        $key = array("user_id" => $userId, "action" => $action);
        $gen_db->updateOrInsert('page_info', $key, $data);

        return $sortColumn;
    }



    //************************************************
    // 1ページの表示件数の決定
    //************************************************
    // 関数として独立（Report系のクラスからもこの機能を使用するため）

    function getPageCount($form)
    {
        global $gen_db;

        // 優先順位の高い順に「GETパラメータ（gen_numberOfItems）、session、listクラスで指定された値」
        if (isset($form['gen_numberOfItems'])) {
            if (Gen_String::isNumericEx($form['gen_numberOfItems'], 1, 1000)) {
                $pageRecordCount = $form['gen_numberOfItems'];
                $_SESSION['gen_setting_user']->numberOfItems = $form['gen_numberOfItems'];
                Gen_Setting::saveSetting();
            } else {
                $pageRecordCount = 100;
            }
        } else if (isset($_SESSION['gen_setting_user']->numberOfItems)) {
            $pageRecordCount = $_SESSION['gen_setting_user']->numberOfItems;
        } else {
            if (is_numeric($this->pageRecordCount)) {
                $pageRecordCount = $this->pageRecordCount;
            } else {
                $pageRecordCount = 100;
            }
        }

        // 「pageRecordCountUnit」が指定されていた場合は、その倍数になるよう切り上げるようにした
        if (is_numeric($this->pageRecordCountUnit)) {
            if ($this->pageRecordCountUnit > 0) {
                $pageRecordCount = (int)(ceil((float)$pageRecordCount / 3) * 3);
            }
        }

        return $pageRecordCount;
    }



    //************************************************
    // リロードチェック
    //************************************************
    function listReloadCheck(&$reqId, &$isReload)
    {
        // リロードチェック。
        //  リロードにより、列の入れ替え処理やリセット処理が再実行されてしまうのを防ぐ。
        //  ページリクエストIDが渡されていないときはチェックしない。
        //  チェックとしては甘いが、この処理は単にリロードされたときの2重処理を防ぐだけで、セキュリティ上の意味は無いのでこれでよしとする。
        $isReload = false;
        if (@$reqId != "") {
            $isReload = (!Gen_Reload::reloadCheck($reqId));
        }

        // 次回のページリクエストIDの発行処理
        global $_SESSION;
        $reqId = sha1(uniqid(rand(), true));
        $_SESSION['gen_page_request_id'][] = $reqId;
    }

    // 表示条件の読み出し
    function loadSearchColumns(&$form, $user_id, $action, $isReload)
    {
        global $gen_db;

        // 表示条件のリセット処理
        if (isset($form['gen_searchColumnReset']) && !$isReload) {
            // 列のリセット
            $gen_db->query("delete from search_column_info where user_id = '{$user_id}' and action = '{$action}'");
        }

        // 表示条件情報の読み出し
        $query = "select * from search_column_info where user_id = '{$user_id}' and action = '{$action}'";
        $colInfoArr = $gen_db->getArray($query);

        return $colInfoArr;
    }
}