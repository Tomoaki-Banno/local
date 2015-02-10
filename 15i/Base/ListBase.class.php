<?php

define("SEARCH_FIELD_PREFIX", "gen_search_");

// 抽象クラス（abstract）。インスタンスを生成できない。
//   PHP4のときは「abstract」をはずすこと

abstract class Base_ListBase
{

    var $searchConditionDefault;
    var $selectQuery;
    var $orderby;
    var $orderbyDefault;
    var $pageRecordCount;
    var $pageRecordCountUnit;
    var $selecterOptions;
    var $tpl;
    var $denyCustomColumn = false;
    var $isDetailMode = true;

    var $listBaseProcArr = array();


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
    // 処理順序チェッカ
    //************************************************

    function listBaseChecker($procName, $beforeProcs, $afterProcs)
    {
        if (1==0) return;

        if ($beforeProcs != "") {
            $beforeProcArr = explode(",", $beforeProcs);
            foreach ($beforeProcArr as $proc) {
                $p = trim($proc);
                if (!in_array($p, $this->listBaseProcArr))  {
                    throw new Exception("{$procName} は {$p} より後に配置する必要があります。");
                }
            }
        }
        if ($afterProcs != "") {
            $afterProcArr = explode(",", $afterProcs);
            foreach ($afterProcArr as $proc) {
                $p = trim($proc);
                if (in_array($p, $this->listBaseProcArr))  {
                    throw new Exception("{$procName} は {$p} より前に配置する必要があります。");
                }
            }
        }
        $this->listBaseProcArr[] = $procName;
    }

    //************************************************
    // メイン
    //************************************************

    function execute(&$form)
    {
        global $gen_db;

        //------------------------------------------------------
        //  user_id と action の取得
        //------------------------------------------------------
        $this->listBaseChecker("getUserId", "", "");

        $userId = Gen_Auth::getCurrentUserId();
        $action = get_class($this);

        //------------------------------------------------------
        //  リロードチェック
        //------------------------------------------------------
        $this->listBaseChecker("reloadCheck", "", "");

        $isReload = $this->_listReloadCheck(@$form['gen_page_request_id']);
        $form['gen_page_request_id'] = $this->_getPageRequestId();

        //------------------------------------------------------
        //  表示条件： パラメータの取得
        //------------------------------------------------------
        $this->listBaseChecker("sc_set", "", "");

        $this->setSearchCondition($form);

        //------------------------------------------------------
        //  ページモードの処理
        //------------------------------------------------------
        // gen_pageMode は setSearchCondition内で設定されるため、sc_setの後である必要がある。
        $this->listBaseChecker("pageMode", "sc_set, getUserId", "sc_nameValue");

        $actionWithPageMode = $action;
        if (isset($form['gen_pageMode']))
            $actionWithPageMode .= "_" . $form['gen_pageMode'];
        $form['gen_actionWithPageMode'] = $actionWithPageMode;    // list.tplで使用。gen_colwidth.jsのsaveColwidth()のコメントを参照

        //------------------------------------------------------
        //  カスタム項目が使えないページ
        //------------------------------------------------------
        $this->listBaseChecker("denyCustom", "", "sc_custom");

        if ($this->denyCustomColumn) {
            unset($form['gen_customColumnTable']);
            unset($form['gen_customColumnArray']);
            unset($form['gen_customColumnClassGroup']);
        }

        //------------------------------------------------------
        //  表示条件： カスタム項目コントロールの追加
        //------------------------------------------------------
        $this->listBaseChecker("sc_custom", "sc_set", "");

        if (isset($form['gen_customColumnArray'])) {
            $isDetailArr = array();
            if (isset($_SESSION['gen_setting_company']->customcolumnisdetail)) {
                $isDetailArr = $_SESSION['gen_setting_company']->customcolumnisdetail;
                if (is_object($isDetailArr)) {
                    $isDetailArr = get_object_vars($isDetailArr);
                }
            }
            foreach($form['gen_customColumnArray'] as $customCol => $customArr) {
                $customMode = $customArr[0];
                $customName = $customArr[1];
                $customColumnName = $customArr[2];
                list($type, $options) = Logic_CustomColumn::getCustomElementTypeAndOptions($customColumnName, $customMode);
                if (isset($options)) {
                    $options = array("" => "("._g("すべて").")") + $options;
                }
                if (!isset($isDetailArr[$customArr[2]]) || !$isDetailArr[$customArr[2]]) {
                    $table = $form['gen_customColumnTable'];
                } else {
                    $table = $form['gen_customColumnDetailTable'];
                }

                $form['gen_searchControlArray'][] =
                    array(
                        'label' => $customName,
                        'field' => $table . '___' . $customCol,
                        'type' => $type,
                        'options' => (isset($options) ? $options : null),
                    );
            }
        }

        //------------------------------------------------------
        //  表示条件： コントロールパラメータのデフォルト値
        //------------------------------------------------------
        $this->listBaseChecker("sc_controlDefault", "sc_set, sc_custom", "");

        foreach ($form['gen_searchControlArray'] as &$ctl) {
            if (!isset($ctl['type'])) {
                $ctl['type'] = "textbox";
            }
            if (!isset($ctl['ime'])) {
                $ctl['ime'] = "off";
            }
            if (!isset($ctl['size'])) {
                $size = "100";
                switch ($ctl['type']) {
                    case "dateFromTo":
                        $size = "80";
                        break;
                    case "calendar":
                        $size = "80";
                        break;
                }
                $ctl['size'] = $size;
            }
        }
        unset($ctl);

        //------------------------------------------------------
        //  表示条件： ピンどめされた表示条件の読み出し
        //------------------------------------------------------
        // 日付パターンがピンどめされていたときの日付の復元処理は後のほうで行う。
        $this->listBaseChecker("sc_pin", "getUserId", "");

        $form['gen_pins'] = array();
        // gen_restore_search_condition がセットされているときはピンどめ条件を読み出さない。
        //  同パラメータによる読み出し処理は $form に値がセットされているときは働かないため、
        //  ピンどめ条件のほうが優先されてしまう。
        //  そうするとピンどめ条件を変更してページングした際に不自然な動きとなる。
        //  ag.cgi?page=ProjectDocView&ppid=1574&pbid=195175
        $isReadPinCondition = (!isset($form['gen_restore_search_condition']) && !isset($form['gen_searchConditionClear']));

        $colInfoJson = $gen_db->queryOneValue("select pin_info from page_info where user_id = '{$userId}' and action = '{$actionWithPageMode}'");

        // 登録の際に「\」が「￥」に自動変換されているので、ここで元に戻す必要がある。
        if (($colInfoObj = json_decode(str_replace("￥", "\\", $colInfoJson))) != null) {
            foreach ($colInfoObj as $key => $val) {
                if ($isReadPinCondition && !isset($form[$key])) {    // 表示条件がユーザーにより指定された場合は読み出ししない
                    $form[$key] = $val;
                }
                $form['gen_pins'][] = $key;
            }
        }

        //------------------------------------------------------
        //  表示条件： 表示条件パターン
        //------------------------------------------------------
        // ピンよりも後ろ（ピンより「表示条件パターン」読み出し値が優先）、日付パターン復元や不正値対処より前である必要がある。
        $this->listBaseChecker("sc_pattern", "getUserId, reloadCheck, sc_controlDefault, sc_pin", "sc_default");

        $readCondData = null;
        $form['gen_savedSearchConditionModeForOrderby'] = false;
        $form['gen_savedSearchConditionRead'] = false;
        $form['gen_searchConditionRestoreScript_noEscape'] = "";
        if (!isset($form['gen_noSavedSearchCondition']) || !$form['gen_noSavedSearchCondition']) {
            // 後のorderby処理用に、現在が表示条件パターンモードであるかどうかを判断しておく。
            //  ここより後で判断すると、同モードではないときに表示条件パターン保存処理したときにも同モードであると
            //　判断されてしまう。（保存処理内でモードを切り替えているため）
            //　orderby処理に関してはそれでは都合が悪い。
            $form['gen_savedSearchConditionModeForOrderby'] = isset($form[SEARCH_FIELD_PREFIX . 'gen_savedSearchCondition']) && is_numeric($form[SEARCH_FIELD_PREFIX . 'gen_savedSearchCondition'])
                // この後半の条件は、表示条件パターンモードでページングしたときのため
                || (isset($form['gen_restore_search_condition']) && isset($_SESSION['gen_savedOrderBy']) && !isset($form['gen_savedSearchConditionUpdateFlag']));

            // 保存されている「表示条件パターン」の読み出し
            //  登録の際に「\」が「￥」に自動変換されているので、ここで元に戻す必要がある。
            $condJson = $gen_db->queryOneValue("select saved_search_condition_info from page_info where user_id = '{$userId}' and action = '{$action}'");
            $condObj = json_decode(str_replace("￥", "\\", $condJson), true);

            // 「表示条件パターン」の保存処理
            if (isset($form[SEARCH_FIELD_PREFIX . 'gen_savedSearchConditionName']) && $form[SEARCH_FIELD_PREFIX . 'gen_savedSearchConditionName'] != "" && !$isReload) {
                $nextId = 0;
                if ($condObj == null) {
                    $condObj = array();
                } else {
                    foreach ($condObj as $id => $data) {
                        if ($id >= $nextId)
                            $nextId = $id + 1;
                        if ($data['label'] == $form[SEARCH_FIELD_PREFIX . 'gen_savedSearchConditionName']) {
                            // 上書き
                            unset($condObj[$id]);
                            $nextId = $id;
                            break;
                        }
                    }
                }
                $form[SEARCH_FIELD_PREFIX . 'gen_savedSearchCondition'] = $nextId;

                // 実際の保存処理は orderby処理、およびクロス集計カラム処理のあとで行う。（「表示条件パターンの保存」）
                $searchConditionSaveObj = $condObj;
            }

            // 「表示条件パターン」の削除処理
            if (isset($form[SEARCH_FIELD_PREFIX . 'gen_deleteSavedSearchConditionName']) && $form[SEARCH_FIELD_PREFIX . 'gen_deleteSavedSearchConditionName'] != "" && !$isReload) {
                if ($condObj != null) {
                    $deleteNameExist = false;
                    foreach ($condObj as $id => $data) {
                        if ($data['label'] == $form[SEARCH_FIELD_PREFIX . 'gen_deleteSavedSearchConditionName']) {
                            unset($condObj[$id]);
                            $deleteNameExist = true;
                            break;
                        }
                    }
                    if ($deleteNameExist) {
                        $saveJson = json_encode($condObj);

                        // 登録の際、自動的に「\」が「￥」に変換されることに注意。
                        $key = array("user_id" => $userId, "action" => $action);
                        $data = array(
                            "saved_search_condition_info" => $saveJson,
                        );
                        $gen_db->updateOrInsert('page_info', $key, $data);
                    }
                }
            }

            // 「表示条件パターン」セレクタの選択肢を作成
            //  　表示順：「なし」⇒ 保存されているパターン　⇒　いま保存したパターン　⇒　プリセット
            //  ちなみに、「なし」の場合もなにかキーをセットしておかないと、いずれかのパターンから「なし」に変更したときに、
            //  ピンどめされたパターンのほうが優先されてしまう。
            $condOptions = array("nothing" => "(" . _g("なし") . ")");
            //    保存されているパターン
            if ($condObj != null) {
                foreach ($condObj as $id => $data) {
                    $condOptions[$id] = $data['label'];
                }
            }
            //    いま保存したパターン
            if (isset($searchConditionSaveObj)) {
                // 保存時はいま保存したパターンを選択肢に追加。
                $condOptions[$nextId] = $form[SEARCH_FIELD_PREFIX . 'gen_savedSearchConditionName'];
            }
            // 　　プリセット表示条件パターン
            if (isset($form['gen_savedSearchConditionPreset'])) {
                $presetId = 1000;
                foreach ($form['gen_savedSearchConditionPreset'] as $key => $data) {
                    $condOptions[$presetId++] = $key;
                }
            }

            // 「表示条件パターン」の読み出し処理
            //      リスト再表示時のパターンの読み出しは gen_savedSearchConditionUpdateFlag がセットされているとき、
            //      つまり表示条件パターンセレクタが変更されたときのみ。
            //      単に「表示条件パターンがセットされていたら読み出す」ということにすると、
            //      表示条件パターンをいったん読みだしたあとで一部の表示条件を変更したときに、
            //      変更した表示条件が保存されている表示条件で上書きされてしまう。
            //      ちなみに上記は再表示時のみ。画面全体のロードの場合は常に読み出さないと、表示条件パターンがピンどめ
            //      されていた場合に対応できない。
            //      また、表示条件パターン「なし」が選択されたときは、すべてをリセットするためJSで画面自体をリロードしている。
            //      （list.tpl 冒頭の$('#gen_search_gen_savedSearchCondition').change() を参照）
            if ((!isset($form['gen_tableload']) || (isset($form['gen_savedSearchConditionUpdateFlag']) && $form['gen_savedSearchConditionUpdateFlag']))
                    && isset($form[SEARCH_FIELD_PREFIX . 'gen_savedSearchCondition']) && is_numeric($form[SEARCH_FIELD_PREFIX . 'gen_savedSearchCondition'])) {
                $form['gen_savedSearchConditionRead'] = true;
                $form['gen_searchConditionRestoreByScript'] = 't';      // JSで表示条件コントロールの値をセット

                // 読み出す表示条件パターンを決定
                $savedSearchCondition = $form[SEARCH_FIELD_PREFIX . 'gen_savedSearchCondition'];
                if ($savedSearchCondition >= 1000) {
                    // プリセット表示条件パターン
                    $presetId = 1000;
                    foreach ($form['gen_savedSearchConditionPreset'] as $presetData) {
                        if ($savedSearchCondition == $presetId) {
                            $readCondData = $presetData['data'];
                            if (isset($presetData['orderby'])) {
                                $form['gen_saved_orderby'] = $presetData['orderby'];
                            }
                            break;
                        } else {
                            $presetId++;
                        }
                    }
                } else if (isset($condObj[$savedSearchCondition])) {
                    // 保存された表示条件パターン
                    $readCondData = $condObj[$savedSearchCondition]['data'];
                    if (isset($condObj[$savedSearchCondition]['orderby'])) {
                        $form['gen_saved_orderby'] = $condObj[$savedSearchCondition]['orderby'];
                    }
                }

                // 表示条件パターンの読み出し
                if ($readCondData) {
                    // フォームで指定されている値はすべてクリア
                    foreach ($form['gen_searchControlArray'] as $ctl) {
                        if (!isset($ctl['field'])) {
                            continue;
                        }
                        $field = $ctl['field'];
                        if (get_class($this) == "Stock_Inout_List" && $field == "classification") {
                            continue;
                        }
                        unset($form[SEARCH_FIELD_PREFIX . $field]);
                        unset($form[SEARCH_FIELD_PREFIX . 'match_mode_' . SEARCH_FIELD_PREFIX . $field]);
                        unset($form['gen_datePattern_' . SEARCH_FIELD_PREFIX . $field]);
                        unset($form['gen_strPattern_' . SEARCH_FIELD_PREFIX . $field]);
                        unset($form[SEARCH_FIELD_PREFIX . $field . '_from']);
                        unset($form[SEARCH_FIELD_PREFIX . $field . '_to']);
                    }

                    // 読みだした条件が表示条件SESSIONに保存されるようにするため、gen_restore_search_condition
                    // をクリアしておく。
                    // これがないと、表示条件読み出し ⇒ ソートで一部の条件がクリアされてしまうことがある。
                    // ag.cgi?page=ProjectDocView&pid=1574&did=208767
                    unset($form['gen_restore_search_condition']);

                    // $form値の復元
                    $valueArr = array();
                    $matchModeArr = array();
                    $datePatternArr = array();
                    $strPatternArr = array();
                    foreach ($readCondData as $data) {
                        if (isset($data['v'])) {
                            if ($data['f'] != 'gen_savedSearchCondition') {
                                $colName = SEARCH_FIELD_PREFIX . $data['f'];
                                $form[$colName] = $data['v'];
                                $valueArr[$data['f']] = $data['v'];
                            }
                        }
                        if (isset($data['m'])) {
                            $colName = "gen_search_match_mode_" . SEARCH_FIELD_PREFIX . $data['f'];
                            $form[$colName] = $data['m'];
                            $matchModeArr[$data['f']] = $data['m'];
                        }
                        if (isset($data['dp'])) {
                            $colName = "gen_datePattern_" . SEARCH_FIELD_PREFIX . $data['f'];
                            $form[$colName] = $data['dp'];
                            $datePatternArr[$data['f']] = $data['dp'];
                        }
                        if (isset($data['sp'])) {
                            $colName = "gen_strPattern_" . SEARCH_FIELD_PREFIX . $data['f'];
                            $form[$colName] = $data['sp'];
                            $strPatternArr[$data['f']] = $data['sp'];
                        }
                    }
                }
            }

            array_unshift($form['gen_searchControlArray'],  // 先頭に追加
                array(
                    'label_noEscape' => _g('表示条件パターン') . " " .
                        "<img src='img/disk-black.png' style='vertical-align: middle; cursor:pointer;' title='" . _g("現在の表示条件をパターンとして保存") . "' onclick='gen.list.saveSearchCondition()'>" .
                        "&nbsp;&nbsp;<img class='imgContainer sprite-close' src='img/space.gif' style='vertical-align: middle; cursor:pointer;' title='"._g("現在のパターンを削除")."' onclick='gen.list.deleteSavedSearchCondition()'>",
                    'type' => 'select',
                    'field' => 'gen_savedSearchCondition',
                    'options' => $condOptions,
                    'nosql' => true,
                    'introId' => 'gen_intro_savedSearchCondition',  // gen_intro.js
                ),
                array(
                    'type' => 'separater',
                    'nosql' => true,
                    'visible' => false,    // 表示条件選択ダイアログに出さないための設定
                )
            );
        }

        //------------------------------------------------------
        //  表示条件： 日付パターンの展開
        //------------------------------------------------------
        // 日付パターンがピンどめ、または表示条件パターンで復元されていた場合の処理。

        // 以前は上のほうの「ピンどめされたデフォルト表示条件の読み出し」の中で行なっていたが、表示条件パターンの実装にともないここへ移動。
        // 表示条件パターン読み出しの後で処理する必要があるため。
        $this->listBaseChecker("sc_datePattern", "sc_pin, sc_pattern", "");

        foreach($form as $key => $val) {
            if (substr($key, 0, 16) == 'gen_datePattern_') {
                // 選択肢を追加・削除・変更するときは、function.gen_search_control と gen_calendar.js も変更が必要（'datePattern'で検索）
                switch ($val) {
                case '0':    // なし
                        $from = '';
                        $to = '';
                        break;
                case '1':    // 今日
                        $from = date('Y-m-d');
                        $to = $from;
                        break;
                case '2':    // 昨日
                        $from = date('Y-m-d', strtotime('-1 day'));
                        $to = $from;
                        break;
                case '3':    // 今週
                        $from = date('Y-m-d', strtotime('-'. date('w') . ' day'));
                        $to = date('Y-m-d', strtotime($from . ' +6 days'));
                        break;
                case '4':    // 先週
                        $from = date('Y-m-d', strtotime('-'. ((int)date('w')+7) . ' day'));
                        $to = date('Y-m-d', strtotime($from . ' +6 days'));
                        break;
                case '5':    // 今月
                        $from = date('Y-m-01');
                        $to = date('Y-m-t');
                        break;
                case '6':    // 先月
                        $from = date('Y-m-01', strtotime('-1 month'));
                        $to = date('Y-m-t', strtotime('-1 month'));
                        break;
                case '7':    // 今年
                        $from = date('Y-1-1');
                        $to = date('Y-12-31');
                        break;
                case '8':    // 昨年
                        $from = date('Y-1-1', strtotime('-1 year'));
                        $to = date('Y-12-31', strtotime('-1 year'));
                        break;
                case '9':    // 明日
                        $from = date('Y-m-d', strtotime('+1 day'));
                        $to = $from;
                        break;
                case '10':    // 来週
                        $from = date('Y-m-d', strtotime('-'. ((int)date('w')-7) . ' day'));
                        $to = date('Y-m-d', strtotime($from . ' +6 days'));
                        break;
                case '11':    // 来月
                        $from = date('Y-m-01', strtotime('+1 month'));
                        $to = date('Y-m-t', strtotime('+1 month'));
                        break;
                case '12':    // 来年
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
                default:    // なし
                        $from = ''; $to = ''; break;
                }
                $name = substr($key, 16);
                if (!isset($form["{$name}_from"])) {
                    $form["{$name}_from"] = $from;
                }
                if (!isset($form["{$name}_to"])) {
                    $form["{$name}_to"] = $to;
                }
            }
        }

        //------------------------------------------------------
        //  表示条件： セッション処理
        //------------------------------------------------------
        //　sessionから$formに表示条件を復元（すでに$formに値がある場合を除く）、またはsessionに$formの表示条件を格納する。
        //    また暗黙的に「page」も格納/復元される。（ページングで使用）
        //  　表示条件パターン読み出しより後にしないと、表示条件パターン有効でページングやエクセル出力したときにうまくいかない
        $this->listBaseChecker("sc_session", "sc_pin, sc_pattern, sc_datePattern, sc_set, sc_custom", "");

        $this->searchConditonSession($form, $form['gen_searchControlArray']);

        //------------------------------------------------------
        //  表示条件： 日付項目に不正値が指定されたときの処理
        //------------------------------------------------------
        // 日付項目に不正値が指定された場合、検索値を消去する
        // （デフォルト値があれば、この次の処理で設定される）
        $this->listBaseChecker("sc_dateCheck", "sc_session, sc_pin, sc_pattern", "");

        foreach ($form['gen_searchControlArray'] as &$ctl) {
            if ($ctl['type'] == "dateFromTo") {
                $name1 = SEARCH_FIELD_PREFIX . $ctl["field"] . "_from";
                if (isset($form[$name1])) {
                    if ($form[$name1] != '' && !Gen_String::isDateString($form[$name1])) {
                        unset($form[$name1]);
                    }
                }
                $name2 = SEARCH_FIELD_PREFIX . $ctl["field"] . "_to";
                if (isset($form[$name2])) {
                    if ($form[$name2] != '' && !Gen_String::isDateString($form[$name2])) {
                        unset($form[$name2]);
                    }
                }
                if (isset($form[$name1]) && isset($form[$name2]) && $form[$name1] != '' && $form[$name2] != '') {
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
        //  表示条件： デフォルト値の設定（defaultパラメータ）
        //------------------------------------------------------
        // ユーザーが設定した検索値が不正だった場合、上の「不正値が指定されたときの処理」で消去され、
        // ここでデフォルト値がセットされる。
        $this->listBaseChecker("sc_default", "sc_session, sc_pin, sc_pattern, sc_dateCheck", "");

        if (!isset($form['gen_searchConditionClear'])) {
            foreach ($form['gen_searchControlArray'] as &$ctl) {
                if (isset($ctl["field"])) { // literal は field がないので何も処理しない
                    $name = SEARCH_FIELD_PREFIX . $ctl["field"];
                    if (isset($ctl["default"]) && !isset($form[$name])) {
                        $form[$name] = $ctl["default"];
                        //$ctl["value"] = $ctl["default"];
                        //if ($ctl["type"] == "select") {
                        //    $ctl["selected"] = $ctl["default"];
                        //}
                    }
                    if (isset($ctl["defaultFrom"]) && !isset($form[$name . "_from"]) && !isset($form["gen_datePattern_" . $name])) {
                        $form[$name . "_from"] = $ctl["defaultFrom"];
                        //$ctl["valueFrom"] = $ctl["defaultFrom"];
                    }
                    if (isset($ctl["defaultTo"]) && !isset($form[$name . "_to"]) && !isset($form["gen_datePattern_" . $name])) {
                        $form[$name . "_to"] = $ctl["defaultTo"];
                        //$ctl["valueTo"] = $ctl["defaultTo"];
                    }
                    if (isset($ctl["defaultStrFrom"]) && !isset($form[$name . "_from"]) && !isset($form["gen_strPattern_" . $name])) {
                        $form[$name . "_from"] = $ctl["defaultStrFrom"];
                        //$ctl["valueFrom"] = $ctl["defaultStrFrom"];
                    }
                    if (isset($ctl["defaultStrTo"]) && !isset($form[$name . "_to"]) && !isset($form["gen_strPattern_" . $name])) {
                        $form[$name . "_to"] = $ctl["defaultStrTo"];
                        //$ctl["valueTo"] = $ctl["defaultStrTo"];
                    }
                    if (isset($ctl["defaultYear"]) && !isset($form[$name . "_Year"])) {
                        $form[$name . "_Year"] = $ctl["defaultYear"];
                        //$ctl["valueYear"] = $ctl["defaultYear"];
                    }
                    if (isset($ctl["defaultMonth"]) && !isset($form[$name . "_Month"])) {
                        $form[$name . "_Month"] = $ctl["defaultMonth"];
                        //$ctl["valueMonth"] = $ctl["defaultMonth"];
                    }
                }
            }
            unset($ctl);
        }

        //------------------------------------------------------
        //  表示条件： 表示条件コントロールの name と value の設定
        //------------------------------------------------------
        $this->listBaseChecker("sc_nameValue", "sc_session, sc_pin, sc_pattern, sc_default, pageMode", "");

        $searchJavascript = "";
        foreach ($form['gen_searchControlArray'] as &$ctl) {
            if (isset($ctl["field"])) { // literal は field がないので何も処理しない
                $type = $ctl["type"];
                $name = SEARCH_FIELD_PREFIX . $ctl["field"];
                $ctl["name"] = $name;
                if ($type == "dateFromTo" || $type == "dateTimeFromTo" || $type == "numFromTo") {
                    $ctl["valueFrom"] = (isset($form["{$name}_from"]) ? $form["{$name}_from"] : "");
                    $ctl["valueTo"] = (isset($form["{$name}_to"]) ? $form["{$name}_to"] : "");
                    // 日付パターンがピンどめされていた場合
                    if (isset($form["gen_datePattern_{$name}"])) {
                        $ctl["datePattern"] = $form["gen_datePattern_{$name}"];
                    }
                } else if ($type == "strFromTo") {
                    $ctl["valueFrom"] = @$form["{$name}_from"];
                    $ctl["valueTo"] = @$form["{$name}_to"];
                    // 文字列範囲パターンがピンどめされていた場合
                    if (isset($form["gen_strPattern_{$name}"])) {
                        $ctl["strPattern"] = $form["gen_strPattern_{$name}"];
                    }
                    $searchJavascript .= ";gen.list.strPatternChange('{$name}','{$form['gen_actionWithPageMode']}')";
                } else if ($type == "yearMonth") {
                    $ctl["valueYear"] = @$form["{$name}_Year"];
                    $ctl["valueMonth"] = @$form["{$name}_Month"];
                } else if ($type == "select") {
                    $ctl["selected"] = (isset($form[$name]) ? $form[$name] : "");
                } else {
                    $ctl["value"] = (isset($form[$name]) ? $form[$name] : "");
                }
            }
        }
        unset($ctl);

        //------------------------------------------------------
        //　表示条件： 表示条件の変換（子クラスで実装）
        //------------------------------------------------------
        // 表示条件に対する Convert（不正値の変換など）はこのメソッド内で行う。
        // 以前は Converter を使用していたが、Converter はセッション値やピン留め値に適用されない
        // （それらの復元の前に実行される）ため、このメソッドをもうけた。
        $this->listBaseChecker("sc_convert", "sc_session, sc_pin, sc_pattern, sc_default", "");

        if (method_exists($this, 'convertSearchCondition')) {
            $converter = new Gen_Converter($form);
            $this->convertSearchCondition($converter, $form);
        }

        //------------------------------------------------------
        //  表示条件： 表示条件情報の読み出し
        //------------------------------------------------------
        $this->listBaseChecker("sc_readInfo", "getUserId, pageMode, reloadCheck", "");

        $searchColInfoArr = $this->loadSearchColumns(isset($form['gen_searchColumnReset']), $userId, $actionWithPageMode, $isReload);

        //------------------------------------------------------
        //  表示条件： コントロールの表示非表示設定と並べ替え
        //------------------------------------------------------
        $this->listBaseChecker("sc_sort", "getUserId, sc_readInfo, pageMode, reloadCheck", "");

        $this->sortColumns(true, $form, $searchColInfoArr, $userId, $actionWithPageMode, $isReload);

        //------------------------------------------------------
        //  表示条件： 拡張Dropdownの処理（表示値設定と使用フラグ立て）
        //------------------------------------------------------
        // 拡張Dropdownを使用しているフィールドについて、子クラス側では
        // ['value']（例：品目ID）しか設定されていないが、tplでの表示時には
        // showtext（例：品目コード） と subtext（例：品目名）が必要。
        // ここで取得して変数に格納する。

        for ($i = 0; $i < count($form['gen_searchControlArray']); $i++) {
            if (@$form['gen_searchControlArray'][$i]['type'] == 'dropdown') {
                // 表示値設定
                $ddRes = Logic_Dropdown::getDropdownText($form['gen_searchControlArray'][$i]['dropdownCategory'], $form['gen_searchControlArray'][$i]['value']);
                $form['gen_searchControlArray'][$i]['dropdownShowtext'] = $ddRes['showtext'];
                $form['gen_searchControlArray'][$i]['dropdownSubtext'] = $ddRes['subtext'];
                $form['gen_searchControlArray'][$i]['dropdownHasSubtext'] = $ddRes['hasSubtext'];
            }
        }

        //------------------------------------------------------
        //  表示条件： 一致モードの状態復帰
        //------------------------------------------------------
        $this->listBaseChecker("sc_matchMode", "sc_session, sc_pin, sc_pattern, sc_default, sc_convert", "");

        for ($cnt = 0; $cnt < count(@$form['gen_searchControlArray']); $cnt++) {
            //  「gen_search_match_mode_」はsmarty_function_gen_search_controlで定義
            if (isset($form['gen_searchControlArray'][$cnt]["name"])) {
                $name = "gen_search_match_mode_" . $form['gen_searchControlArray'][$cnt]["name"];
                if (isset($form[$name])) {
                    $form['gen_searchControlArray'][$cnt]["matchMode"] = $form[$name];
                }
            }
        }

        //------------------------------------------------------
        //  表示条件： クイック検索関連
        //------------------------------------------------------
        $this->listBaseChecker("sc_specialSearch", "sc_pin, pageMode", "");

        $form['gen_special_search_isOn'] = in_array("gen_special_search", $form['gen_pins']);

        //------------------------------------------------------
        //　表示条件： 設定完了
        //------------------------------------------------------
        // この時点で、POST値、ピン、表示条件パターン、セッション、デフォルト値など、表示条件値に
        // かかわるすべての処理が終了している。
        $this->listBaseChecker("sc_done", "sc_session, sc_pin, sc_pattern, sc_default, sc_convert", "");

        //------------------------------------------------------
        //  クエリ前ロジックの実行
        //------------------------------------------------------
        $this->listBaseChecker("beforeLogic", "sc_done", "");

        $this->beforeLogic($form);

        //------------------------------------------------------
        //  クエリ用パラメータの取得
        //------------------------------------------------------
        $this->listBaseChecker("setQueryParam", "beforeLogic, sc_done", "");

        $this->setQueryParam($form);

        //------------------------------------------------------
        //  表示関係パラメータの取得
        //------------------------------------------------------
        $this->listBaseChecker("setViewParam", "setQueryParam, sc_done", "");

        $this->setViewParam($form);

        // iPadは固定列なし。
        //  固定列があると縦スクロールしたときの動きがぎこちなくなるし、スクロール部分の横幅が狭くなってしまうことが多いので。
        if ($form['gen_iPad']) {
            if (isset($form['gen_fixColumnArray'])) {
                $form['gen_columnArray'] = array_merge($form['gen_fixColumnArray'], $form['gen_columnArray']);
                unset($form['gen_fixColumnArray']);
            }
        }

        //------------------------------------------------------
        //  カラムモードの処理
        //------------------------------------------------------
        // ページモード（gen_pageMode）の処理は上のほうで行なっている
        $this->listBaseChecker("columnMode", "setViewParam, pageMode", "");   // gen_columnMode は setViewParam内で設定される

        // カラムモード・ページモードつきのaction
        $actionWithColumnMode = $action;
        if (isset($form['gen_columnMode']))
            $actionWithColumnMode .= "_" . $form['gen_columnMode'];
        if (isset($form['gen_pageMode']))
            $actionWithColumnMode .= "_" . $form['gen_pageMode'];
        $form['gen_actionWithColumnMode'] = $actionWithColumnMode;    // list.tplで使用。gen_colwidth.jsのsaveColwidth()のコメントを参照

        //------------------------------------------------------
        //  columnArray の存在判断
        //------------------------------------------------------
        $this->listBaseChecker("column_exist", "setViewParam", "");

        $existColumn = (isset($form['gen_columnArray']));
        if (!$existColumn && isset($form['gen_fixColumnArray'])) {
            throw new Exception("gen_fixColumnArray が存在しているのに、gen_columnArray がありません。");
        }

        if ($existColumn) {
            //------------------------------------------------------
            //  最終表示日時の読み出し（Newアイコン用）
            //------------------------------------------------------
            $this->listBaseChecker("column_newIcon", "getUserId, setQueryParam, setViewParam", "newIconRecord");   // 最終表示日時の記録は、読み出しより後に行う必要がある

            $query = "select last_show_time from page_info where user_id = '{$userId}' and action = '{$form['action']}'";
            $lastShowTime = $gen_db->queryOneValue($query);

            // New アイコン表示列
            // 本来は New と Up を区別したかったが、トラン系画面では gen_record_create_date が正確ではない
            // （更新時にいったん削除して再登録している場合がある）ことがあるので、どちらも New とした。
            if ($lastShowTime && strpos($this->selectQuery, 'gen_record_update_date') !== false && strpos($this->selectQuery, 'gen_record_updater') !== false) {
                $colArrName = "gen_columnArray";
                if (isset($form['gen_fixColumnArray'])) {
                    $colArrName = "gen_fixColumnArray";
                }
                array_unshift(
                    $form[$colArrName],
                    array(
                        'label' => _g("New"),
                        'type' => 'literal',
                        'literal_noEscape' => "<img src='img/arrow.png' class='gen_cell_img'>",
                        'literal2_noEscape' => "<img src='img/paint-brush--arrow.png' class='gen_cell_img'>",
                        'width' => 40,
                        'align' => 'center',
                        'showCondition' => "strtotime('[gen_record_update_date]')>=" . strtotime($lastShowTime),
                        'literal2Condition' => "'[gen_record_updater]'=='" . (isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '') . "'",
                        'editType' => 'none',
                    )
                );
            }

            //------------------------------------------------------
            //  カスタム項目2（select文の修正、リスト項目の追加）
            //------------------------------------------------------
            // 表示条件の追加はもっと前のほう、CSV項目の追加は後のほうで行なっている
            $this->listBaseChecker("column_custom", "sc_custom, setQueryParam, setViewParam", "column_default");

            // 集計関数が必要かどうかを判断。やや強引な方法だが・・。
            // このフラグは後の、添付ファイル表示の部分でも使用している
            $fromPos = stripos($this->selectQuery, 'from');
            $selectList = substr($this->selectQuery, 0, $fromPos);
            $needAgg = (stripos($selectList, 'sum(') !== false || stripos($selectList, 'max(') !== false);

            $isDetailArr = array();
            if (isset($_SESSION['gen_setting_company']->customcolumnisdetail)) {
                $isDetailArr = $_SESSION['gen_setting_company']->customcolumnisdetail;
                if (is_object($isDetailArr)) {
                    $isDetailArr = get_object_vars($isDetailArr);
                }
            }

            if (isset($form['gen_customColumnArray'])) {
                $selectPos = stripos($this->selectQuery, 'select') + 6;
                $table = $form['gen_customColumnTable'];
                $originalQuery = $this->selectQuery;
                $this->selectQuery = substr($originalQuery, 0, $selectPos) . " ";
                $customKeyColumn = Logic_CustomColumn::getCustomColumnKeyByTableName($form['gen_customColumnTable']);

                foreach($form['gen_customColumnArray'] as $customCol => $customArr) {
                    $isDetailCustomColumn = isset($isDetailArr[$customArr[2]]) && $isDetailArr[$customArr[2]];
                    if (!$this->isDetailMode && $isDetailCustomColumn) {
                        // 明細項目非表示モードのとき、明細カスタム項目は含めない
                        continue;
                    }
                    $customMode = $customArr[0];
                    $customName = $customArr[1];
                    $customColumnName = $customArr[2];
                    switch ($customMode) {
                        case 0: $type = "data"; break;   // 文字
                        case 1: $type = "numeric"; break;   // 数値
                        case 2: $type = "date"; break;  // 日付
                    }

                    list($editType, $editOptions) = Logic_CustomColumn::getCustomElementTypeAndOptions($customColumnName, $customMode);
                    switch ($editType) {
                        case "textbox":
                        case "calendar":
                            $editType = "text"; break;
                    }
                    // select listでワイルドカード指定されていたときのため、エイリアスをつけておく
                    if ($needAgg) {
                        if ($type == "numeric" && $this->isDetailMode) {
                            $this->selectQuery .= "sum";
                        } else {
                            $this->selectQuery .= "max";
                        }
                        $this->selectQuery .= "(";
                    }
                    if (!isset($isDetailArr[$customArr[2]]) || !$isDetailArr[$customArr[2]]) {
                        $table = $form['gen_customColumnTable'];
                    } else {
                        $table = $form['gen_customColumnDetailTable'];
                    }

                    $this->selectQuery .= "{$table}.{$customCol}";
                    if ($needAgg) {
                        $this->selectQuery .= ")";
                    }
                    $this->selectQuery .= " as gen_custom_{$customCol},";

                    $form["gen_columnArray"][] =
                        array(
                            'label' => $customName,
                            'field' => "gen_custom_{$customCol}",
                            'type' => $type,
                            // たとえば受注ヘッダに数値のカスタム項目を作成したとき、明細表示するとどの明細行にも同じ数値が表示され、
                            // 合計がおかしくなる。そのため、ヘッダのカスタム項目は sameCellJoin しておく必要がある。
                            // （一方、明細カスタム項目は sameCellJoin しない。sameCellJoinするとクロス集計の「値」になれないため）
                            // parentColumn は Logic_CustomColumn でテーブルごとに設定されているキー項目を使用する。
                            'sameCellJoin' => !$isDetailCustomColumn,
                            'parentColumn' => $customKeyColumn,
                            'editType' => $editType,
                            // 一見、editOptionsには「なし」という選択肢があったほうがいいように思えるが、「なし」が必要なときは
                            // カスタム項目設定で空文字選択肢を追加してもらえばいい。
                            // Edit画面のほうもそのような考え方になっている。
                            'editOptions' => (isset($editOptions) ? $editOptions : null),
                        );
                }
                $this->selectQuery .= substr($originalQuery, $selectPos + 1);
            }

            // 関連テーブルのカスタム項目
            if (isset($this->customColumnTables) && is_array($this->customColumnTables)) {
                $selectPos = stripos($this->selectQuery, 'select') + 6;
                $originalQuery = $this->selectQuery;
                $this->selectQuery = substr($originalQuery, 0, $selectPos) . " ";
                foreach($this->customColumnTables as $customTable) {
                    // この画面のメインテーブルのカスタム項目はすでに処理されている
                    if (isset($form['gen_customColumnArray']) && $customTable[0] == $table) {
                        continue;
                    }
                    $alias = ($customTable[1] == "" ? $customTable[0] : $customTable[1]);
                    $arr = Logic_CustomColumn::getCustomColumnParamByTableName2($customTable[0], $customTable[2]);
                    if (!$arr) {
                        throw new Exception('$this->customColumnTables に指定されている' . $customTable[0] . 'は Logic_CustomColumn に登録されていません。');
                    }
                    foreach($arr[1] as $customCol => $customArr) {
                        if (!isset($isDetailArr[$customArr[2]]) || !$isDetailArr[$customArr[2]]) {
                            $table = $alias;
                        } else {
                            // 関連テーブルの明細カスタム項目は、customColumnTablesの第5パラメータがtrueのときのみ追加する。
                            $find = false;
                            foreach($this->customColumnTables as $customTable2) {
                                if ($customTable2[0] == $arr[2]) {
                                    $find = true;
                                    break;
                                }
                            }
                            if (count($customTable) <= 4 || !$customTable[4]) {
                                continue;
                            }
                            $table = $arr[2];   // 明細テーブル
                        }
                        $customName = $customArr[1];
                        $typeName = substr($customCol, 7, 4);
                        switch ($typeName) {
                            case "text": $type = "data"; break;
                            case "nume": $type = "numeric"; break;
                            case "date": $type = "date"; break;
                        }
                        if ($needAgg) {
                            if ($type == "numeric") {
                                $this->selectQuery .= "sum";
                            } else {
                                $this->selectQuery .= "max";
                            }
                            $this->selectQuery .= "(";
                        }
                        $this->selectQuery .= "{$table}.{$customCol}";
                        if ($needAgg) {
                            $this->selectQuery .= ")";
                        }
                        $this->selectQuery .= " as gen_custom_{$table}_{$customCol},";

                        $form["gen_columnArray"][] =
                            array(
                                'label' => $customName,
                                'field' => "gen_custom_{$table}_{$customCol}",
                                'type' => $type,
                                // sameCellJoin が必要な理由については、上のほうにある
                                // この画面のカスタム項目を追加する処理の部分のコメントを参照。
                                'sameCellJoin' => true,
                                'parentColumn' => $customTable[3],
                                'hide' => true,
                            );
                    }
                }
                $this->selectQuery .= substr($originalQuery, $selectPos + 1);
            }

            //------------------------------------------------------
            //  添付ファイル・トークボード表示（select文の修正、リスト項目の追加）
            //------------------------------------------------------
            // 表示条件の追加はもっと前のほう、CSV項目の追加は後のほうで行なっている
            $this->listBaseChecker("column_upload_file", "setQueryParam, setViewParam", "column_default");

            if (isset($form['gen_idFieldForUpdateFile'])) {
                // SQLの修正
                // カラムリスト
                $selectPos = stripos($this->selectQuery, 'select') + 6;
                $originalQuery = $this->selectQuery;
                $this->selectQuery = substr($originalQuery, 0, $selectPos) . " ";

                if ($needAgg) {
                    $this->selectQuery .= "max(";
                }
                $this->selectQuery .= "case when t_upload_file_info.record_id is null then '' else '" . _g("有") . "' end";
                if ($needAgg) {
                    $this->selectQuery .= ")";
                }
                $this->selectQuery .= " as gen_upload_file,";

                if ($needAgg) {
                    $this->selectQuery .= "max(";
                }
                $this->selectQuery .= "chat_header.chat_header_id";
                if ($needAgg) {
                    $this->selectQuery .= ")";
                }
                $this->selectQuery .= " as gen_chat_header_id,";

                $this->selectQuery .= substr($originalQuery, $selectPos + 1);

                // from部
                $wherePos = stripos($this->selectQuery, '[Where]') - 1;
                $originalQuery = $this->selectQuery;
                $this->selectQuery = substr($originalQuery, 0, $wherePos);
                $this->selectQuery .= " left join (select record_id from upload_file_info where action_group = '{$form['gen_action_group']}' group by record_id) as t_upload_file_info on t_upload_file_info.record_id = {$form['gen_idFieldForUpdateFile']} ";
                $this->selectQuery .= " left join chat_header on chat_header.action_group = '{$form['gen_action_group']}' and chat_header.record_id = {$form['gen_idFieldForUpdateFile']} ";
                $this->selectQuery .= substr($originalQuery, $wherePos + 1);

                // カラムリスト
                $form["gen_columnArray"][] =
                    array(
                        'label' => _g("ファイル"),
                        'field' => "gen_upload_file",
                        'type' => "data",
                        'width' => 60,
                        'align' => 'center',
                        'editType'=>'none',
                        'hide' => true,
                    );
                $form["gen_columnArray"][] =
                    array(
                        'label' => _g("トークボード"),
                        'field' => "gen_record_chat",
                        'type' => "literal",
                        'literal_noEscape' => "<img src='img/header/header_chat.png' class='gen_cell_img'>",
                        'width' => 60,
                        'align' => 'center',
                        'link' => "javascript:gen.chat.init('d', '[gen_chat_header_id]', '', '', '', '', '')",
                        'showCondition'=>"'[gen_chat_header_id]'!=''",
                        'hide' => true,
                    );
            }

            //------------------------------------------------------
            //  登録日時・登録者列
            //------------------------------------------------------
            $this->listBaseChecker("column_recordCreate", "setQueryParam, setViewParam", "column_default");

            if (strpos($this->selectQuery, 'gen_record_create_date') !== false) {
                $form["gen_columnArray"][] =
                    array(
                        'label' => _g('登録日時'),
                        'field' => 'gen_record_create_date',
                        'editType'=>'none',
                        'hide' => true,
                    );
            }
            if (strpos($this->selectQuery, 'gen_record_creater') !== false) {
                $form["gen_columnArray"][] =
                    array(
                        'label' => _g('登録者'),
                        'field' => 'gen_record_creater',
                        'editType'=>'none',
                        'hide' => true,
                    );
            }
            if (strpos($this->selectQuery, 'gen_record_update_date') !== false) {
                $form["gen_columnArray"][] =
                    array(
                        'label' => _g('最終更新日時'),
                        'field' => 'gen_record_update_date',
                        'type' => 'datetime',
                        'hide' => true,
                        'editType'=>'none',
                        'helpText_noEscape' => _g('日本時間で表示されます。'),
                    );
            }
            if (strpos($this->selectQuery, 'gen_record_updater') !== false) {
                $form["gen_columnArray"][] =
                    array(
                        'label' => _g('最終更新者'),
                        'field' => 'gen_record_updater',
                        'editType'=>'none',
                        'hide' => true,
                    );
            }

            //------------------------------------------------------
            //  列Arrayのデフォルト値
            //------------------------------------------------------
            $this->listBaseChecker("column_default", "setViewParam", "");

            for ($i = 1; $i <= 2; $i++) {
                $arrName = ($i == 1 ? 'gen_fixColumnArray' : 'gen_columnArray');

                if (isset($form[$arrName])) {
                    foreach ($form[$arrName] as &$ctl) {
                        // type
                        if (!isset($ctl['type'])) {
                            $ctl['type'] = "data";
                        }
                        // field
                        //  fieldを設定しているのは、sameCellJoinしたときに表示が乱れるのを避けるため
                        if (!isset($ctl['field']) && ($ctl['type'] == "label" || $ctl['type'] == "checkbox" || $ctl['type'] == "literal")) {
                            $ctl['field'] = $ctl['label'];
                        }
                        // width
                        if (!isset($ctl['width'])) {
                            $width = 150;   // data
                            switch ($ctl['type']) {
                                case "edit":
                                    $width = 40;
                                    break;
                                case "copy":
                                    $width = 40;
                                    break;
                                case "delete":
                                    $width = 40;
                                    break;
                                case "delete_check":
                                    $width = 42;
                                    break;
                                case "checkbox":
                                    $width = 42;
                                    break;
                                case "numeric":
                                    $width = 80;
                                    break;
                                case "date":
                                    $width = 80;
                                    break;
                            }
                            $ctl['width'] = $width;
                        }
                        // align
                        if (!isset($ctl['align'])) {
                            $align = "left";   // data
                            switch ($ctl['type']) {
                                case "edit":
                                    $align = "center";
                                    break;
                                case "copy":
                                    $align = "center";
                                    break;
                                case "delete":
                                    $align = "center";
                                    break;
                                case "delete_check":
                                    $align = "center";
                                    break;
                                case "checkbox":
                                    $align = "center";
                                    break;
                                case "numeric":
                                    $align = "right";
                                    break;
                                case "date":
                                    $align = "center";
                                    break;
                            }
                            $ctl['align'] = $align;
                        }
                        // editType
                        if (isset($form['gen_directEditEnable']) && $form['gen_directEditEnable'] == "true"
                                && isset($ctl['field']) && !isset($ctl['editType'])) {
                            $ctl['editType'] = "text";
                        }
                    }
                    unset($ctl);
                }
            }

            //------------------------------------------------------
            // 列情報の読み出し
            //------------------------------------------------------
            $this->listBaseChecker("column_readInfo", "getUserId, setViewParam, sc_done, columnMode, column_exist, reloadCheck", "");

            $colInfoArr = $this->loadColumns($form, $userId, $actionWithColumnMode, $isReload);


            //------------------------------------------------------
            //  列の並べ替え
            //------------------------------------------------------
            $this->listBaseChecker("column_sort", "getUserId, setViewParam, columnMode, reloadCheck, column_readInfo", "");

            $this->sortColumns(false, $form, $colInfoArr, $userId, $actionWithColumnMode, $isReload);

        } // existColumn

        //------------------------------------------------------
        //  ここまでで columnArray が完成（クロス集計を除く）
        //------------------------------------------------------
        $this->listBaseChecker("column_complete_without_cross", "columnMode, column_exist" . ($existColumn ? ", column_newIcon, column_custom, column_recordCreate, column_default, column_readInfo, column_sort" : ""), "");

        //------------------------------------------------------
        //  クロス集計関連の$form値をセッションから復元する
        //------------------------------------------------------
        //　同じ処理を後でも行っているが、ここではセッション ⇒ $form を行い、後ではその逆を行っている。
        //　前者はクロス集計関連の処理の前に行い、後者は後に行う必要がある。
        self::searchConditonSessionCross($form);

        //------------------------------------------------------
        //  クロス集計モードの判断
        //------------------------------------------------------
        $this->listBaseChecker("cross_isCross", "sc_done", "");

        $isCrossTableMode = (isset($form[SEARCH_FIELD_PREFIX . 'gen_crossTableHorizontal'])
                && isset($form[SEARCH_FIELD_PREFIX . 'gen_crossTableVertical'])
                && isset($form[SEARCH_FIELD_PREFIX . 'gen_crossTableValue'])
                && $form[SEARCH_FIELD_PREFIX . 'gen_crossTableHorizontal'] != ""
                && $form[SEARCH_FIELD_PREFIX . 'gen_crossTableVertical'] != ""
                && $form[SEARCH_FIELD_PREFIX . 'gen_crossTableValue'] != "");

        //------------------------------------------------------
        //  OrderBy情報の取得と保存
        //------------------------------------------------------
        $this->listBaseChecker("orderby", "getUserId, setQueryParam, setViewParam, columnMode, cross_isCross", "");

        // ソートリセットが指定されていたときの処理
        if (isset($form['gen_sortReset'])) {
            $query = "update page_info set orderby = '' where user_id = '{$userId}' and action = '{$actionWithColumnMode}'";
            $gen_db->query($query);
        }

        // ソート情報の取得と保存
        if ($isCrossTableMode) {
            // クロス集計モードのorderby処理は後で行う。ここではデフォルト項目のみ指定しておく
            $orderbyArr = $this->_orderByStrToArray($this->orderbyDefault, true);
        } else {
            $orderbyArr = $this->getOrderByArray($form, $this->orderbyDefault, $userId, $actionWithColumnMode);
            $form['gen_orderby'] = $orderbyArr;     // listプラグインに引渡し
        }

        //------------------------------------------------------
        //  SQL組み立て: where と orderby の取得
        //------------------------------------------------------
        // column_sort の後でなければならない。column_sort 処理の中で filter の読み出しを行い、それをここのgetSearchConditionで使用しているため
        $this->listBaseChecker("sql_where_orderby", "sc_done, setQueryParam, orderby" . ($existColumn ? ", column_sort" : ""), "");

        $whereStr = $this->getSearchCondition($form, $form['gen_searchControlArray']);
        $orderbyStr = $this->makeOrderBy($orderbyArr);

        //------------------------------------------------------
        //  SQL組み立て: CSVインポートしたデータのみモード
        //------------------------------------------------------
        $this->listBaseChecker("sql_importOnly", "setQueryParam, sql_where_orderby", "");

        $importWhere = "";
        if (isset($form['gen_importDataShowMode']) && $form['gen_importDataShowMode']=="1"
                && isset($_SESSION['gen_csvImportBeginTime']) && isset($_SESSION['gen_csvImportEndTime'])) {
            $whereStr = "";
            $importWhere =
                " and gen_record_update_date >= '".$_SESSION['gen_csvImportBeginTime']."'"
                ." and gen_record_update_date <= '".$_SESSION['gen_csvImportEndTime']."'"
                ." and gen_record_updater = '".$_SESSION['user_name']."'";
        }

        //------------------------------------------------------
        //  SQL組み立て: フィルタ
        //------------------------------------------------------
        $this->listBaseChecker("sql_filter", "setViewParam, setQueryParam, sql_where_orderby", "");

        $filterWhere = "";
        $form['gen_filterColumn'] = array();
        if (isset($form['gen_fixColumnArray'])) {
            foreach ($form['gen_fixColumnArray'] as $key => $col) {
                if (isset($col['field']) && isset($col['filter']) && $col['filter'] != "") {
                    $res = self::_filterToWhere($col['field'], $col['filter'], isset($col['zeroToBlank']) && $col['zeroToBlank']);
                    $filterWhere .= $res[0];
                    $form['gen_fixColumnArray'][$key]['filterText'] = $res[1];
                    $form['gen_filterColumn'][] = $col['label'] . " : " . $res[1];
                }
            }
        }
        if (isset($form['gen_columnArray'])) {
            foreach ($form['gen_columnArray'] as $key => $col) {
                if (isset($col['field']) && isset($col['filter']) && $col['filter'] != "") {
                    $res = self::_filterToWhere($col['field'], $col['filter'], isset($col['zeroToBlank']) && $col['zeroToBlank']);
                    $filterWhere .= $res[0];
                    $form['gen_columnArray'][$key]['filterText'] = $res[1];
                    $form['gen_filterColumn'][] = $col['label'] . " : " . $res[1];
                }
            }
        }

        //------------------------------------------------------
        //  SQL組み立て: クイック検索
        //------------------------------------------------------
        $this->listBaseChecker("sql_specialSearch", "setViewParam, setQueryParam, sql_where_orderby", "");

        $specialSearchWhere = "";
        if (isset($form['gen_special_search']) && $form['gen_special_search'] != "") {
            if (isset($form['gen_fixColumnArray'])) {
                $columnArray = array_merge($form['gen_fixColumnArray'], $form['gen_columnArray']);
            } else {
                $columnArray = $form['gen_columnArray'];
            }
            $specialSearch = str_replace("　", " ", $form['gen_special_search']);
            $specialSearchArr = explode(" ", $specialSearch);
            foreach($specialSearchArr as $specialSearchOne) {
                $specialSearchWhere .= self::_getSpecialSearchCondition($specialSearchOne, $columnArray);
            }
        }

        //------------------------------------------------------
        //  SQL組み立て: インポートデータのみモード・フィルタ・クイック検索共通処理
        //------------------------------------------------------
        $this->listBaseChecker("sql_import_filter_special", "sql_importOnly, sql_filter, sql_specialSearch", "");

        if ($importWhere != "" || $filterWhere != "" || $specialSearchWhere != "") {
            // filter か special search が存在する場合はSQLをラップSELECT で囲む。
            // そうしないと ... as xxx といったエイリアス付きのカラムがあったときにうまくいかない
            $orderByPos = strrpos($this->selectQuery, "[Orderby]");
            if ($orderByPos === false) {
                $orderByPos = strlen($this->selectQuery) + 1;
            }

            $this->selectQuery = "select * from (" . substr($this->selectQuery, 0, $orderByPos) . ") as t_gen_wrap where 1=1 {$importWhere} {$filterWhere} {$specialSearchWhere} [Orderby]";

            // order by はラップSELECTの外側に移動するので、order by のカラムにテーブル名がついているとうまくいかない。
            // そのためテーブル名をはずす。ラップSELECTの外ならテーブル名がなくても大丈夫なはず（重複名カラムがないかぎり）。
            if ($orderbyStr != "") {
                $arr = explode(",", $orderbyStr);
                $newOrderbyStr = "";
                foreach ($arr as $col) {
                    if (substr($col,0,8)=="order by") {
                        $col = substr($col,8);
                    } else {
                        $newOrderbyStr .= ",";
                    }
                    $pos = strpos($col, ".");
                    if ($pos) {
                        $newOrderbyStr .= substr($col, $pos+1);
                    } else {
                        $newOrderbyStr .= $col;
                    }
                }
                $orderbyStr = "order by {$newOrderbyStr}";
            }
        }

        //------------------------------------------------------
        //  SQL組み立て: where と orderby を SQL に反映
        //------------------------------------------------------
        $this->listBaseChecker("sql_whereSQL", "sql_where_orderby, sql_import_filter_special", "");

        $this->selectQuery = str_replace('[Where]', $whereStr, $this->selectQuery);
        $this->selectQuery = str_replace('[Orderby]', $orderbyStr, $this->selectQuery);

        //------------------------------------------------------
        //  ここまでで SQL が完成（クロス集計を除く）
        //------------------------------------------------------
        $this->listBaseChecker("sql_complete_without_cross", "sql_whereSQL", "");

        //------------------------------------------------------
        //  帳票モード（非チェックボックス方式）の処理
        //------------------------------------------------------
        $this->listBaseChecker("report_print", "sql_complete_without_cross", "");

        // 非チェックボックス方式の帳票（表示条件に合致するレコードをすべて印刷。
        // action=XXX_XXX_List&gen_report=XXX_XXX_Report として印刷）の場合、
        // Listクラスでデータを取得し、gen_temp_for_report テーブルに挿入した上でReportクラスを呼び出す。
        if (isset($form['gen_reportAction'])) {
            $gen_db->createTempTable("gen_temp_for_report", $this->selectQuery, true);
            $reportAction = $form['gen_reportAction'];
            require_once(Gen_File::safetyPathForAction($reportAction));
            $actionClass = new $reportAction;
            $res = $actionClass->execute($form);
            if (isset($form['gen_unitTestMode'])) {
                return $res;
            }
            return "simple.tpl";
        }

        //------------------------------------------------------
        //  CSV： パラメータの取得
        //------------------------------------------------------
        $this->listBaseChecker("csv_set", "beforeLogic, sc_done", "");

        if (method_exists($this,"setCsvParam")) {
            $this->setCsvParam($form);
        }

        if (isset($form['gen_csvMode'])) {
            //------------------------------------------------------
            //  CSV： カスタム項目
            //------------------------------------------------------
            $this->listBaseChecker("csv_custom", "csv_set", "");

            if (isset($form['gen_customColumnArray'])) {
                $table = $form['gen_customColumnTable'];
                foreach($form['gen_customColumnArray'] as $customCol => $customArr) {
                    $customName = $customArr[1];
                    $form["gen_csvArray"][] =
                        array(
                            'label' => $customName,
                            'field' => "gen_custom_{$customCol}",
                        );
                }
            }

            if ($form['gen_csvMode'] == "Export") {
                //------------------------------------------------------
                //  CSV： エクスポートモード
                //------------------------------------------------------
                // クロス集計モードの場合はSQLが書き換わってしまうため、クロス集計より前に実行する。
                $this->listBaseChecker("csv_export", "csv_set, csv_custom, sql_complete_without_cross", "cross_exec");

                $titleArr = array();
                $fieldArr = array();
                foreach ($form['gen_csvArray'] as $col) {
                    $titleArr[] = $col['label'] . (isset($col['addLabel']) ? $col['addLabel'] : "");
                    $fieldArr[] = (isset($col['exportField']) ? $col['exportField'] : $col['field']);
                }
                $csvTitle = join(",", $titleArr) . "\n";
                $csvField = join(",", $fieldArr);

                $csvQuery = "select {$csvField} from ($this->selectQuery) as gen_csv";

                $filename = tempnam(GEN_TEMP_DIR, "");

                $offset = 1;
                if (isset($form['gen_csvOffset']) && Gen_String::isNumeric($form['gen_csvOffset'])) {
                    $offset = $form['gen_csvOffset'];
                }
                Gen_Csv::CsvExport($filename, $csvTitle, $csvQuery, $offset);

                Gen_Download::DownloadFile($filename, (isset($form['gen_pageTitle']) ? $form['gen_pageTitle'] . '_' : $form['gen_action_group']) . date('Ymd_Hi') . ".csv");

                // DownloadFile() 内で exit() している

            } else {
                //------------------------------------------------------
                //  CSV： インポートモード
                //------------------------------------------------------
                // インポート画面の表示。必要なパラメータはsetCsvParam()で取得済み
                $this->listBaseChecker("csv_import", "csv_set, csv_custom", "");

                // ファイルがアップロードされた場合。
                // ファイルのセキュリティチェックとアクセス権チェック
                if (($form['gen_readonly'] != "true") && is_uploaded_file(@$_FILES['uploadFile']['tmp_name']) && $_FILES['uploadFile']['size'] > 0) {
                    // ファイル名チェック（機種依存文字の検出）
                    $fileNameError = Gen_String::checkSjisDependencyChar($_FILES['uploadFile']['name']);
                    if ($fileNameError != -1) {
                        $form['msg'] = _g("ファイル名に機種依存文字が含まれているため登録できません。");
                        if ($fileNameError >= 0)
                            $form['msg'] .= sprintf(_g("（%s文字目）"), $fileNameError);
                            $obj = array(
                                'msg' => $form['msg'],
                                'success' => false,
                                'reqId' => $form['gen_csv_page_request_id'],
                            );
                            $form['response_noEscape'] = json_encode($obj);
                            // HTMLエスケープはクライアント側で
                            return 'simple.tpl';
                    }

                    // リロードチェック
                    $key = array_search($form['gen_csv_page_request_id'], $_SESSION['gen_csv_page_request_id']);
                    // リクエストID正常
                    if ($key !== false) {
                        // インポート処理実行
                        require_once(Gen_File::safetyPathForAction($form['gen_action_group'] . "Model"));
                        $csvBeginTime = date('Y-m-d H:i:s');
                        $allowUpdate = (isset($form['allowUpdate']) && $form['allowUpdate'] == "true");
                        $resArr = Gen_Csv::CsvImportForModel($_FILES['uploadFile']['tmp_name']
                            , $form['gen_action_group'] . "Model"
                            , $form['gen_csvArray']
                            , $allowUpdate
                            , isset($form['classification']) ? $form['classification'] : "");
                        $csvEndTime = date('Y-m-d H:i:s');
                        $form['success'] = $resArr[0];
                        $successCount = $resArr[1];
                        $form['msg'] = $resArr[2];

                        // インポート成功
                        // （失敗の時はリロードするためIDを保持する。）
                        if ($form['success'])
                            unset($_SESSION['gen_csv_page_request_id'][$key]);  // CookieからIDを削除

                        // データアクセスログ
                        $fileName = $gen_db->quoteParam(Gen_String::cutSjisDependencyChar($_FILES['uploadFile']['name']));
                        $msg = ($form['success'] ? sprintf(_g("成功（%s件）"), $successCount) : _g("エラー")) . " [" . _g("ファイル名") . "] " . $fileName;
                        Gen_Log::dataAccessLog($form['gen_importLabel'], _g("CSVインポート"), $msg);

                        // 通知メール（成功時のみ）
                        if ($form['success'] && isset($form['gen_csvAlertMail_id'])) {
                            $title = $form['gen_csvAlertMail_title'];
                            $body = $form['gen_csvAlertMail_body']
                                    ."\n\n[" . _g("登録日時") . "] ".date('Y-m-d H:i:s') . "\n"
                                    ."[" . _g("登録者") . "] ".$_SESSION['user_name'] . "\n"
                                    ."[" . _g("件数")."] " . $successCount . _g("件");
                            Gen_Mail::sendAlertMail($form['gen_csvAlertMail_id'], $title, $body);
                        }
                    } else {
                        // リクエストID異常
                        $form['msg'] = _g("システムエラーです。");
                        $form['success'] = false;
                    }
                } else {
                    // readonlyの場合など。（一応クライアント側でチェックしているが、それをすり抜けてきた場合）
                    $form['msg'] = _g("システムエラーです。");
                    $form['success'] = false;
                }

                // ページリクエストIDの発行処理
                global $_SESSION;
                $reqId = sha1(uniqid(rand(), true));
                $_SESSION['gen_csv_page_request_id'][] = $reqId;
                $form['gen_csv_page_request_id'] = $reqId;

                $obj = array(
                    'msg' => $form['msg'],
                    'success' => $form['success'],
                    'reqId' => $reqId,
                );
                $form['response_noEscape'] = json_encode($obj);
                if ($form['success']) {
                    // 最終インポート時刻。この値とインポートユーザーをキーに、CSVインポートされたレコードを特定する
                    // （本来はレコード自体にフラグをたてたほうがいいが、なるべくスキーマ変更を避けるために簡易な方法をとった）
                    $_SESSION['gen_csvImportBeginTime'] = $csvBeginTime;
                    $_SESSION['gen_csvImportEndTime'] = $csvEndTime;
                }
                // HTMLエスケープはクライアント側で
                return 'simple.tpl';
            }
        }

        if (isset($form['gen_importDataShowMode'])
                && isset($_SESSION['gen_csvImportBeginTime']) && isset($_SESSION['gen_csvImportEndTime'])) {
            $form['gen_rowColorCondition']['#ccccff'] =
                "'[gen_record_update_date]'>='".$_SESSION['gen_csvImportBeginTime']."'"
                ." && '[gen_record_update_date]'<='".$_SESSION['gen_csvImportEndTime']."'"
                ." && '[gen_record_updater]'=='".$_SESSION['user_name']."'";
        }

        global $_SESSION;
        $reqId = sha1(uniqid(rand(), true));
        $_SESSION['gen_csv_page_request_id'][] = $reqId;
        $form['gen_csv_page_request_id'] = $reqId;

        //------------------------------------------------------
        //  CSV： URLパラメータ
        //------------------------------------------------------
        $this->listBaseChecker("csv_url", "csv_set", "");

        if (method_exists($this, "setCsvParam") && (!isset($form['gen_noCsv']) || !$form['gen_noCsv'])) {
            $actionName = get_class($this);
            $addParam = isset($form['gen_csvAddParam_noEscape']) ? $form['gen_csvAddParam_noEscape'] : "";
           if (isset($form['gen_noCsvImport']) && $form['gen_noCsvImport'] == "true") {
                $form['gen_csvImportAction_noEscape'] = '';
            } else {
                $form['gen_csvImportAction_noEscape'] = h($actionName) . "&gen_csvMode=Import&gen_restore_search_condition=true{$addParam}";
            }
            if (isset($form['gen_noCsvExport']) && $form['gen_noCsvExport'] == "true") {
                $form['gen_csvExportAction_noEscape'] = '';
            } else {
                $form['gen_csvExportAction_noEscape'] = h($actionName) . "&gen_csvMode=Export&gen_restore_search_condition=true{$addParam}";
            }
            $form['gen_importMax'] = GEN_CSV_IMPORT_MAX_COUNT;
            $form['gen_max_upload_file_size'] = GEN_MAX_UPLOAD_FILE_SIZE;
            $form['gen_importFromEncoding'] = GEN_CSV_IMPORT_FROM_ENCODING;
        }

        //------------------------------------------------------
        //  このあとの各処理のためにカラムリストを作成
        //------------------------------------------------------
        $this->listBaseChecker("columnArray", "column_complete_without_cross, columnMode", "");

        $columnArray =
            (isset($form['gen_fixColumnArray']) ?
                array_merge($form['gen_fixColumnArray'], $form['gen_columnArray']) :
                (isset($form['gen_columnArray']) ? $form['gen_columnArray'] : null)
            );


        if ($existColumn) {
            //------------------------------------------------------
            //  クロス集計： 表示条件の追加
            //------------------------------------------------------
            // クロス集計の表示条件は、列関連の情報を取得してからでないと設定できない。
            $this->listBaseChecker("cross_searchCondition", "columnArray", "");

            // 表示条件にクロス集計指定を追加
            $vertOptions = array("" => "("._g("なし").")", "gen_all" => "("._g("すべて").")");
            $horizOptions = array("" => "("._g("なし").")", "gen_all" => "("._g("すべて").")");
            $valueOptionsNum = array("" => "("._g("なし").")");
            $valueOptionsNotNum = array();

            foreach ($columnArray as $col) {
                if (isset($col['type']) && isset($col['field']) && $col['field'] != "" && isset($col['label']) && (!isset($col['visible']) || $col['visible'])) {
                    switch($col['type']) {
                        case "data":
                        case "numeric":
                            $horizOptions[$col['field']] = $col['label'];
                            $vertOptions[$col['field']] = $col['label'];
                            break;
                        case "date":
                        case "datetime":
                            $horizOptions[$col['field']."_year"] = $col['label']."("._g("年").")";
                            $horizOptions[$col['field']."_month"] = $col['label']."("._g("月").")";
                            $horizOptions[$col['field']."_day"] = $col['label']."("._g("日").")";
                            $vertOptions[$col['field']."_year"] = $col['label']."("._g("年").")";
                            $vertOptions[$col['field']."_month"] = $col['label']."("._g("月").")";
                            $vertOptions[$col['field']."_day"] = $col['label']."("._g("日").")";
                            break;
                    }
                    // クロス集計（値）の選択肢の設定。
                    //  ・fieldが'<'で始まるカラムは除外。
                    //      画像を表示するカラムなので。
                    //  ・sameCellJoin なカラムは除外。
                    //  　　sameCellJoin するようなカラムだと正確な値が出せないことがあるため。典型的な例としては原価リストの「合計原価」。
                    //  ・typeがliteralのカラムは除外。
                    //      クロス集計対象になるのは不自然な上、SQLエラーになることが多いため。例：各画面のNewアイコン、受注画面の進捗リンクなど
                    if (isset($col['field']) && substr($col['field'],0,1) != "<" && (!isset($col['sameCellJoin']) || !$col['sameCellJoin'])
                            && (!isset($col['type']) || $col['type'] != 'literal')) {
                        if ($col['type']=="numeric") {
                            $valueOptionsNum[$col['field']] = $col['label'];
                        } else if ($col['type']!="checkbox") {
                            $valueOptionsNotNum[$col['field']] = $col['label'];
                        }
                    }
                }
            }
            $valueOptions = array_merge($valueOptionsNum, $valueOptionsNotNum);

            // 表示条件の追加
            $methodOptions = array(
                "sum" => _g("合計"),
                "max" => _g("最大"),
                "min" => _g("最小"),
                "avg" => _g("平均"),
                "count" => _g("データの数"),
                "count_dist" => _g("データの数（重複を除く）"),
            );
            $form['gen_searchControlArray'][] =
                array(
                    'type' => 'separater',
                    'nosql' => true,
                    'visible' => false,    // 表示条件選択ダイアログに出さないための設定
                );
            $form['gen_searchControlArray'][] =
                array(
                    'label_noEscape' => _g('クロス集計（横軸）') . " " .
                        "<img src='img/arrow-switch.png' style='vertical-align: middle; cursor:pointer;' title='" . _g("横軸と縦軸を入れ替える") . "' onclick='gen.list.swapCrossAxis()'>",
                    'type' => 'select',
                    'field' => 'gen_crossTableHorizontal',
                    'name' => SEARCH_FIELD_PREFIX . 'gen_crossTableHorizontal',
                    'options' => $horizOptions,
                    'selected' => (isset($form[SEARCH_FIELD_PREFIX . 'gen_crossTableHorizontal']) ? $form[SEARCH_FIELD_PREFIX . 'gen_crossTableHorizontal'] : ""),
                    'nosql' => true,
                    'visible' => false,    // 表示条件選択ダイアログに出さないための設定
                    'introId' => 'gen_intro_crossTableHorizontal',  // gen_intro.js
                );
            $form['gen_searchControlArray'][] =
                array(
                    'label' => _g('クロス集計（縦軸）'),
                    'type' => 'select',
                    'field' => 'gen_crossTableVertical',
                    'name' => SEARCH_FIELD_PREFIX . 'gen_crossTableVertical',
                    'options' => $vertOptions,
                    'selected' => (isset($form[SEARCH_FIELD_PREFIX . 'gen_crossTableVertical']) ? $form[SEARCH_FIELD_PREFIX . 'gen_crossTableVertical'] : ""),
                    'nosql' => true,
                    'visible' => false,    // 表示条件選択ダイアログに出さないための設定
                );
            $form['gen_searchControlArray'][] =
                array(
                    'label' => _g('クロス集計（値）'),
                    'type' => 'select',
                    'field' => 'gen_crossTableValue',
                    'name' => SEARCH_FIELD_PREFIX . 'gen_crossTableValue',
                    'options' => $valueOptions,
                    'selected' => (isset($form[SEARCH_FIELD_PREFIX . 'gen_crossTableValue']) ? $form[SEARCH_FIELD_PREFIX . 'gen_crossTableValue'] : ""),
                    'nosql' => true,
                    'visible' => false,    // 表示条件選択ダイアログに出さないための設定
                );
            $form['gen_searchControlArray'][] =
                array(
                    'label' => _g('クロス集計の方法'),
                    'type' => 'select',
                    'field' => 'gen_crossTableMethod',
                    'name' => SEARCH_FIELD_PREFIX . 'gen_crossTableMethod',
                    'options' => $methodOptions,
                    'selected' => (isset($form[SEARCH_FIELD_PREFIX . 'gen_crossTableMethod']) ? $form[SEARCH_FIELD_PREFIX . 'gen_crossTableMethod'] : ""),
                    'nosql' => true,
                    'visible' => false,    // 表示条件選択ダイアログに出さないための設定
                );
                // クロス集計グラフの追加は後の方で行なっている

            if (isset($form['gen_tableload'])) {
                // F1再表示時、クロス集計関連の選択肢を再設定する。
                //　表示条件の変更によりカラムが変わり（明細表示の変更など）、選択肢も変更しなければならない場合があるため。
                $form['gen_crossTableHorizOptions'] = $horizOptions;
                $form['gen_crossTableVertOptions'] = $vertOptions;
                $form['gen_crossTableValueOptions'] = $valueOptions;
            }

            //------------------------------------------------------
            //  クロス集計： 実行
            //------------------------------------------------------
            // クロス集計モードの場合、いったん完成したSQLをここで上書きする形になる。
            // そのため、sql_complete より後で処理する必要がある。
            $this->listBaseChecker("cross_exec", "sql_complete_without_cross, sc_done, column_complete_without_cross, cross_searchCondition, cross_isCross, columnArray", "");

            unset($form['gen_crossTableShow']);

            if ($isCrossTableMode) {
                $horizColumn = $form[SEARCH_FIELD_PREFIX . 'gen_crossTableHorizontal'];
                $vertColumn = $form[SEARCH_FIELD_PREFIX . 'gen_crossTableVertical'];
                $valueColumn = $form[SEARCH_FIELD_PREFIX . 'gen_crossTableValue'];

                // 日付
                $hDateMode = "";
                if (substr($horizColumn,-5) == "_year") {
                    $horizColumn = substr($horizColumn, 0, strlen($horizColumn)-5);
                    $hDateMode = 2;
                } else if (substr($horizColumn,-6) == "_month") {
                    $horizColumn = substr($horizColumn, 0, strlen($horizColumn)-6);
                    $hDateMode = 1;
                } else if (substr($horizColumn,-4) == "_day") {
                    $horizColumn = substr($horizColumn, 0, strlen($horizColumn)-4);
                    $hDateMode = 0;
                }
                $vDateMode = "";
                if (substr($vertColumn,-5) == "_year") {
                    $vertColumn = substr($vertColumn, 0, strlen($vertColumn)-5);
                    $vDateMode = 2;
                } else if (substr($vertColumn,-6) == "_month") {
                    $vertColumn = substr($vertColumn, 0, strlen($vertColumn)-6);
                    $vDateMode = 1;
                } else if (substr($vertColumn,-4) == "_day") {
                    $vertColumn = substr($vertColumn, 0, strlen($vertColumn)-4);
                    $vDateMode = 0;
                }

                // 列情報を取得
                $colHoriz = "";
                $colVert = "";
                $colValue = "";
                foreach ($columnArray as $col) {
                    if (isset($col['field']) && $col['field'] == $horizColumn) {
                        $colHoriz = $col;
                    }
                    if (isset($col['field']) && $col['field'] == $vertColumn) {
                        $colVert = $col;
                    }
                    if (isset($col['field']) && $col['field'] == $valueColumn) {
                        $colValue = $col;
                    }
                    if ($colHoriz != "" && $colVert != "" && $colValue != "")
                        break;
                }

                // パラメータチェック
                $isOK = true;
                $method = @$form[SEARCH_FIELD_PREFIX . 'gen_crossTableMethod'];
                if (($horizColumn != "gen_all" && $colHoriz == ""))  {
                    $form['gen_cross_message'] = _g("クロス集計（横軸）の指定が正しくありません。");
                    $isOK = false;
                } else if (($vertColumn != "gen_all" && $colVert == ""))  {
                    $form['gen_cross_message'] = _g("クロス集計（縦軸）の指定が正しくありません。");
                    $isOK = false;
                } else if ($colValue == "")  {
                    $form['gen_cross_message'] = _g("クロス集計（値）の指定が正しくありません。");
                    $isOK = false;
                } else if ($vertColumn == $horizColumn && $vDateMode == $hDateMode) {
                    $form['gen_cross_message'] = _g("クロス集計の横軸と縦軸に同じ項目を指定することはできません。");
                    $isOK = false;
                } else if ($colValue['type'] != "numeric" && $method != "count" && $method != "count_dist" ) {
                    $form['gen_cross_message'] = _g("クロス集計（値）に数値以外の項目を指定した場合、クロス集計の方法には「データの数」か「データの数（重複を除く）」を指定する必要があります。");
                    $isOK = false;
                } else if (!array_key_exists($form[SEARCH_FIELD_PREFIX . 'gen_crossTableHorizontal'], $horizOptions)
                        || !array_key_exists($form[SEARCH_FIELD_PREFIX . 'gen_crossTableVertical'], $vertOptions)
                        || !array_key_exists($form[SEARCH_FIELD_PREFIX . 'gen_crossTableValue'], $valueOptions)) {
                    // column_arrayにないカラムが指定されている場合はそのまま実行できないので、エラーとする。
                    // 表示条件（明細モード等）により列の内容が変化する場合にそのような状況が起こりうる。
                    // 再表示時にクロス集計の選択肢を再設定しているが、例えば明細モードにしかないカラムをクロスに指定した状態で
                    // ヘッダモードに切り替えた場合には上記の状況になる。
                    $form['gen_cross_message'] = _g("このモードでは指定された条件でのクロス集計を実行できません。");
                    $isOK = false;
                } else if (isset($colValue['sameCellJoin']) && $colValue['sameCellJoin']) {
                    // sameCellJoinな列を値列に指定することはできない。（正しく集計できない）
                    // 表示条件の選択肢の設定時にsameCellJoin列は除外しているが、モード切替のときなどに指定できてしまうことがあるので、
                    // ここでもチェックしておく。
                    $form['gen_cross_message'] = _g("現在の表示モードでは、指定された「クロス集計（値）」の項目は使用できません。");
                    $isOK = false;
                }

                if ($horizColumn == "gen_all")
                    $horizColumn = "'"._g('すべて')."'";
                if ($vertColumn == "gen_all")
                    $vertColumn = "'"._g('すべて')."'";

                if (!$isOK) {
                    // 表示条件エラーの場合、リスト表示に時間がかかるのを避けるためデータ0件になるようにする。
                    $this->selectQuery = "select * from ({$this->selectQuery}) as t_cross_error where 1=0";
                } else {
                    // 日付型
                    $orgHorizColumn = $horizColumn;
                    if ($hDateMode !== "" && ($colHoriz['type'] == "date" || $colHoriz['type'] == "datetime")) {
                        switch ($hDateMode) {
                            case 1: // 月
                                if ($horizColumn == $vertColumn && $vDateMode == 2) {
                                    // もう一方の軸が同じ項目の「年」だった場合。昨対モード
                                    $horizColumn = "to_char(date_trunc('month',{$horizColumn}),'MM')";
                                } else {
                                    $horizColumn = "to_char(date_trunc('month',{$horizColumn}),'YYYY-MM')";
                                }
                                break;
                            case 2: // 年
                                $horizColumn = "to_char(date_trunc('year',{$horizColumn}),'YYYY')";
                                break;
                            default: // 日
                                if ($horizColumn == $vertColumn && ($vDateMode == 1 || $vDateMode == 2)) {
                                    // もう一方の軸が同じ項目の「年」または「月」だった場合。昨対モード
                                    $horizColumn = "to_char(date_trunc('day',{$horizColumn}),'DD')";
                                } else {
                                    $horizColumn = "to_char(date_trunc('day',{$horizColumn}),'YYYY-MM-DD')";
                                }
                        }
                    }
                    if ($vDateMode !== "" && ($colVert['type'] == "date" || $colVert['type'] == "datetime")) {
                        switch ($vDateMode) {
                            case 1: // 月
                                if ($orgHorizColumn == $vertColumn && $hDateMode == 2) {
                                    // もう一方の軸が同じ項目の「年」だった場合。昨対モード
                                    $vertColumn = "to_char(date_trunc('month',{$vertColumn}),'MM')";
                                } else {
                                    $vertColumn = "to_char(date_trunc('month',{$vertColumn}),'YYYY-MM')";
                                }
                                break;
                            case 2: // 年
                                $vertColumn = "to_char(date_trunc('year',{$vertColumn}),'YYYY')";
                                break;
                            default: // 日
                                if ($orgHorizColumn == $vertColumn && ($hDateMode == 1 || $hDateMode == 2)) {
                                    // もう一方の軸が同じ項目の「年」または「月」だった場合。昨対モード
                                    $vertColumn = "to_char(date_trunc('day',{$vertColumn}),'DD')";
                                } else {
                                    $vertColumn = "to_char(date_trunc('day',{$vertColumn}),'YYYY-MM-DD')";
                                }
                        }
                    }

                    // SQL
                    $maxColCount = 50;
                    $horizQuery = "select cast({$horizColumn} as text) as horizcol from ({$this->selectQuery}) as t_gen_cross group by cast({$horizColumn} as text) order by cast({$horizColumn} as text)";
                    $horizArr = $gen_db->getArray($horizQuery);
                    if (!$horizArr) $horizArr = array();    // データなし

                    $crossQuery = "select cast({$vertColumn} as text) as gen_cross_key "; // castしないとgroupbyでエラーになる場合がある。Pagerで、gen_cross_key は sum集計しないようにしている
                    if ($method != "max" && $method != "min" && $method != "avg" && $method != "count" && $method != "count_dist") $method = "sum";
                    $cnt = 1;
                    foreach ($horizArr as $row) {
                        if ($method == "count_dist") {
                            $crossQuery .= ",count(distinct ";
                        } else {
                            $crossQuery .= ",{$method}(";
                        }
                        if ($row['horizcol'] == "") {
                            $crossQuery .= "case when coalesce(cast({$horizColumn} as text),'') = '' then {$valueColumn} end) as field{$cnt}";
                        } else {
                            $crossQuery .= "case when cast({$horizColumn} as text) = '{$row['horizcol']}' then {$valueColumn} end) as field{$cnt}";
                        }
                        if (++$cnt>$maxColCount) break;
                    }

                    // column arrayの書き換え
                    if ($colVert == "") {
                        $colVert = array(
                            'type' => "data",
                            'width' => '80',
                        );
                    }
                    $colVert['field'] = "gen_cross_key";
                    $colVert['hide'] = false;
                    $colVert['visible'] = true;
                    unset($colVert['link']);    // クロス集計ではリンクは無効
                    unset($colVert['helpText_noEscape']);
                    unset($colVert['colorCondition']);

                    if (count($horizArr)==0) {
                        // データなし
                        unset($form['gen_fixColumnArray']);
                        $form['gen_columnArray'] = array($colVert);
                    } else {
                        if ($form['gen_iPad']) {
                            $form['gen_columnArray'] = array($colVert);
                        } else {
                            $form['gen_fixColumnArray'] = array($colVert);
                            $form['gen_columnArray'] = array();
                        }
                        $cnt = 1;
                        $chartOptionsArr = array();
                        foreach ($horizArr as $row) {
                            $vCol = $colValue;
                            $vCol['field'] = "field{$cnt}";
                            $vCol['label'] = $row['horizcol'];
                            $vCol['hide'] = false;
                            $vCol['visible'] = true;
                            $vCol['sameCellJoin'] = false;
                            unset($vCol['link']);   // クロス集計ではリンクを無効に
                            unset($vCol['helpText_noEscape']);
                            unset($vCol['colorCondition']);
                            $form['gen_columnArray'][] = $vCol;
                            $chartOptionsArr[$row['horizcol']] = $row['horizcol'];
                            if (++$cnt>$maxColCount) break;
                        }
                    }

                    // orderby
                    $crossOrderbyDefault = "gen_cross_key";
                    $crossOrderbyArr = $this->getOrderByArray($form, $crossOrderbyDefault, $userId, $actionWithColumnMode);
                    $form['gen_orderby'] = $crossOrderbyArr;
                    $orderbyStr = $this->makeOrderBy($crossOrderbyArr); // $orderbyStrは後でも使う
                    $crossQuery .= " from ({$this->selectQuery}) as t_gen_cross group by cast({$vertColumn} as text) {$orderbyStr}";   // orderbyは必須。レコードカウントSQL組立の際、最後のorderbyの位置を頼るため
                    $this->selectQuery = $crossQuery;

                    $form['gen_crossTableShow'] = true;

                    // ほとんどの場合、クロス集計モードでは行の色付けは無意味
                    unset($form['gen_rowColorCondition']);

                    // column arrayの内容が変わったので再作成
                    $columnArray =
                        (isset($form['gen_fixColumnArray']) ?
                            array_merge($form['gen_fixColumnArray'], $form['gen_columnArray']) :
                            (isset($form['gen_columnArray']) ? $form['gen_columnArray'] : null)
                        );
                }
            }   // isCrossTableMode

            //------------------------------------------------------
            //  クロス集計： グラフ
            //------------------------------------------------------
            // $chartOptionsArr は上の cross_exec の中で取得している
            $this->listBaseChecker("cross_chart", "sc_done, cross_exec", "");

            $chartOptions = array("gen_nothing" => "("._g("なし").")");
            if (isset($chartOptionsArr)) {
                // 単純にarray_merge すると、$chartOptionsArr の key が数値だったときに keyが書き換わってしまう
                foreach ($chartOptionsArr as $key => $val) {
                    $chartOptions[$key] = $val;
                }
            }
            if (isset($form['gen_tableload'])) {
                // F1再表示時、クロス集計関連の選択肢を再設定する。
                $form['gen_crossTableChartOptions'] = $chartOptions;
            }

            $form['gen_searchControlArray'][] =
                array(
                    'label' => _g('クロス集計グラフ'),
                    'type' => 'select',
                    // select は表示時（function.gen_search_control）に選択肢文字数が制限されるが、
                    // この項目については listtable.tpl 冒頭のJSで選択肢が再設定されるため、その文字数制限が効かない。
                    // そこでこの設定をしている。
                    'style' => 'max-width:220px',
                    'field' => 'gen_crossTableChart',
                    'name' => SEARCH_FIELD_PREFIX . 'gen_crossTableChart',
                    'options' => $chartOptions,
                    'selected' => (isset($form[SEARCH_FIELD_PREFIX . 'gen_crossTableChart']) ? $form[SEARCH_FIELD_PREFIX . 'gen_crossTableChart'] : "gen_nothing"),
                    'nosql' => true,
                    'visible' => false,    // 表示条件選択ダイアログに出さないための設定
                );

            //------------------------------------------------------
            //  クロス集計関連の$form値をセッションに保存する
            //------------------------------------------------------
            // 上のほうにある「クロス集計関連の$form値をセッションから復元する」のコメントを参照。
            self::searchConditonSessionCross($form);

            //------------------------------------------------------
            //  ここまでで columnArray が完成（クロス集計を含む）
            //------------------------------------------------------
            $this->listBaseChecker("column_complete_with_cross", "columnMode, column_exist" . ($existColumn ? ", column_newIcon, cross_exec" : ""), "");

            //------------------------------------------------------
            //  ここまでで SQL が完成（クロス集計を含む）
            //------------------------------------------------------
            $this->listBaseChecker("sql_complete_with_cross", "sql_whereSQL" . ($isCrossTableMode ? ", cross_exec" : ""), "");
//echo($this->selectQuery);
//var_dump($gen_db->getArray($this->selectQuery));
//file_put_contents("query.txt", $this->selectQuery);

            //------------------------------------------------------
            //  表示条件パターンの保存
            //------------------------------------------------------
            // orderby情報の取得、およびクロス集計カラム追加を行なってからでないと保存できないので、ここで行う。
            $this->listBaseChecker("savedSearchConditionOrderby", "sc_pattern, sql_complete_with_cross" . ($isCrossTableMode ? ", cross_exec" : ""), "");

            if (isset($searchConditionSaveObj)) {

                $condData = array();
                foreach ($form['gen_searchControlArray'] as $ctl) {
                    if (isset($ctl['field'])) {
                        if ($ctl['type'] == "dateFromTo" || $ctl['type'] == "dateTimeFromTo") {
                            if (isset($form['gen_datePattern_' . SEARCH_FIELD_PREFIX . $ctl['field']]) && $form['gen_datePattern_' . SEARCH_FIELD_PREFIX . $ctl['field']] != -1) {
                                $condData[] = array(
                                    "f" => $ctl['field'],   // field
                                    "dp" => $form['gen_datePattern_' . SEARCH_FIELD_PREFIX . $ctl['field']],  // 日付範囲パターン
                                );
                            } else {    // パターンが保存された場合、個々の項目は保存しない
                                if (isset($form[SEARCH_FIELD_PREFIX . $ctl['field'] . '_from'])) {
                                    $condData[] = array(
                                        "f" => $ctl['field'] . '_from',   // field
                                        "v" => $form[SEARCH_FIELD_PREFIX . $ctl['field'] . '_from'],  // value
                                    );
                                }
                                if (isset($form[SEARCH_FIELD_PREFIX . $ctl['field'] . '_to'])) {
                                    $condData[] = array(
                                        "f" => $ctl['field'] . '_to',   // field
                                        "v" => $form[SEARCH_FIELD_PREFIX . $ctl['field'] . '_to'],  // value
                                    );
                                }
                            }
                        } else if ($ctl['type'] == "strFromTo") {
                            if (isset($form[SEARCH_FIELD_PREFIX . $ctl['field'] . '_from'])) {
                                $condData[] = array(
                                    "f" => $ctl['field'] . '_from',   // field
                                    "v" => $form[SEARCH_FIELD_PREFIX . $ctl['field'] . '_from'],  // value
                                );
                            }
                            if (isset($form[SEARCH_FIELD_PREFIX . $ctl['field'] . '_to'])) {
                                $condData[] = array(
                                    "f" => $ctl['field'] . '_to',   // field
                                    "v" => $form[SEARCH_FIELD_PREFIX . $ctl['field'] . '_to'],  // value
                                );
                            }
                            if (isset($form['gen_strPattern_' . SEARCH_FIELD_PREFIX . $ctl['field']])) {
                                $condData[] = array(
                                    "f" => $ctl['field'],   // field
                                    "sp" => $form['gen_strPattern_' . SEARCH_FIELD_PREFIX . $ctl['field']],  // 文字範囲パターン
                                );
                            }
                        } else if ($ctl['type'] == "yearMonth") {
                            if (isset($form[SEARCH_FIELD_PREFIX . $ctl['field'] . '_Year'])) {
                                $condData[] = array(
                                    "f" => $ctl['field'] . '_Year',   // field
                                    "v" => $form[SEARCH_FIELD_PREFIX . $ctl['field'] . '_Year'],  // value
                                );
                            }
                            if (isset($form[SEARCH_FIELD_PREFIX . $ctl['field'] . '_Month'])) {
                                $condData[] = array(
                                    "f" => $ctl['field'] . '_Month',   // field
                                    "v" => $form[SEARCH_FIELD_PREFIX . $ctl['field'] . '_Month'],  // value
                                );
                            }
                        } else {
                            if (isset($form[SEARCH_FIELD_PREFIX . $ctl['field']])) {
                                $arr = array(
                                    "f" => $ctl['field'],   // field
                                    "v" => $form[SEARCH_FIELD_PREFIX . $ctl['field']],  // value
                                );
                                if (isset($form['gen_search_match_mode_' . SEARCH_FIELD_PREFIX . $ctl['field']])) {
                                    $arr["m"] =$form['gen_search_match_mode_' . SEARCH_FIELD_PREFIX . $ctl['field']];   // matchmode
                                }
                                $condData[] = $arr;
                            }
                        }
                    }
                }

                // 配列の先頭に追加したいが、連想配列の場合は array_unshift ではうまくいかない。
                // そこで一旦逆順にした配列に要素を追加し、また逆順にする。
                $searchConditionSaveObj = array_reverse($searchConditionSaveObj, true);
                $searchConditionSaveObj[$form[SEARCH_FIELD_PREFIX . 'gen_savedSearchCondition']] =
                    array(
                        "label" => $form[SEARCH_FIELD_PREFIX . 'gen_savedSearchConditionName'],
                        "data" => $condData,
                        "orderby" => $this->makeOrderBy($form['gen_orderby']),
                    );
                $searchConditionSaveObj = array_reverse($searchConditionSaveObj, true);

                $saveJson = json_encode($searchConditionSaveObj);

                // 登録の際、自動的に「\」が「￥」に変換されることに注意。
                $key = array("user_id" => $userId, "action" => $action);
                $data = array(
                    "saved_search_condition_info" => $saveJson,
                );
                $gen_db->updateOrInsert('page_info', $key, $data);
            }


            //------------------------------------------------------
            //  小計基準
            //------------------------------------------------------
            $this->listBaseChecker("subsum", "getUserId, columnMode, cross_exec, column_complete_without_cross", "");

            if (isset($form['gen_crossTableShow']) && $form['gen_crossTableShow']) {
                // クロス集計時は小計処理を行わない
                $form['gen_subSumCriteria'] = null;
                $form['gen_subSumCriteriaDateType'] = null;
            } else {
                if (isset($form['gen_subSumCriteriaColNum']) && is_numeric($form['gen_subSumCriteriaColNum'])) {
                    $subSumCriteria = "";
                    $subSumCriteriaDateType = null;
                    if (!isset($form['gen_subSumCriteriaClear'])) {
                        $colArr = ($form['gen_subSumCriteriaColNum'] < 1000 ? $form['gen_fixColumnArray'] : $form['gen_columnArray']);
                        foreach ($colArr as $col) {
                            if ($col['gen_num'] == $form['gen_subSumCriteriaColNum']) {
                                $subSumCriteria = $col['field'];
                                if (isset($form['gen_subSumCriteriaDateType']) && is_numeric($form['gen_subSumCriteriaDateType'])) $subSumCriteriaDateType = $form['gen_subSumCriteriaDateType'];
                                break;
                            }
                        }
                    }
                    $dateType = (is_numeric($subSumCriteriaDateType) ? $subSumCriteriaDateType : "null");
                    $query = "update page_info set subsum_criteria = '{$subSumCriteria}', subsum_criteria_datetype = {$dateType} where user_id = '{$userId}' and action = '{$actionWithColumnMode}'";
                    $gen_db->query($query);
                    $form['gen_subSumCriteria'] = $subSumCriteria;
                    $form['gen_subSumCriteriaDateType'] = $subSumCriteriaDateType;
                } else {
                    $query = "select subsum_criteria, subsum_criteria_datetype from page_info where user_id = '{$userId}' and action = '{$actionWithColumnMode}'";
                    $obj = $gen_db->queryOneRowObject($query);
                    if ($obj) {
                        $form['gen_subSumCriteria'] = $obj->subsum_criteria;
                        $form['gen_subSumCriteriaDateType'] = $obj->subsum_criteria_datetype;
                    }
                }
            }
        }   // existColumn

        //------------------------------------------------------
        //  Excel： 編集用Excelダウンロード
        //------------------------------------------------------
        // 本来は接続先シートのURL部分を該当URLに書き換えたファイルをダウンロードさせたいが、
        // PHPExcelではコントロール類を含んだファイルを正常に書き換えできない。
        // そこで仕方なく、ファイル名にURLを含め、Excel VBAで書き換えるという苦肉の策をとった。
        $this->listBaseChecker("excel_edit", "", "");

        if (isset($form['gen_editExcelMode'])) {
            $excelPath = ROOT_DIR . "Download/Gen_Excel_15i.xlsm";
            $url = "http" . (GEN_HTTPS_PROTOCOL === false ? "" : "s") . "://" . $_SERVER['SERVER_NAME'] . "/" . basename(ROOT_DIR) . "/";
            $url = str_replace(":", "：", $url);
            $url = str_replace("/", "／", $url);
            $filename = "Gen_Excel_15i___{$url}___.xlsm";

            Gen_Download::DownloadFile($excelPath, $filename, false);

            return;
        }

        //------------------------------------------------------
        //  Excel： Excel出力モード
        //------------------------------------------------------
        $this->listBaseChecker("excel_output", ($existColumn ? "sql_complete_with_cross, column_complete_with_cross, subsum" : ""), "");

        if (isset($form['gen_excelMode'])) {
            set_time_limit(300);

            if (isset($form['gen_excelQuery'])) {
                $excelQuery0 = $form['gen_excelQuery'];
                $excelQuery1 = str_replace('[Where]', $whereStr, $excelQuery0);
                $excelQuery = str_replace('[Orderby]', $orderbyStr, $excelQuery1);
            } else {
                $excelQuery = $this->selectQuery;
            }
            $offset = 0;
            if (isset($form['gen_csvOffset']) && Gen_String::isNumeric($form['gen_csvOffset'])) {
                $offset = $form['gen_csvOffset'] - 1;
            }
            $excelQuery .= " offset $offset limit " . GEN_EXCEL_EXPORT_MAX_COUNT;
            $excelTitle = (isset($form['gen_excelTitle']) ? $form['gen_excelTitle'] : $form['gen_pageTitle']);
            $excelColArray =
                (isset($form['gen_excelColArray']) ?
                    $form['gen_excelColArray'] :
                    (is_array(@$form['gen_fixColumnArray']) ?
                        array_merge($form['gen_fixColumnArray'], $form['gen_columnArray']) :
                        $form['gen_columnArray']
                    )
                );
            $excelShowArray = (isset($form['gen_excelShowArray']) ? $form['gen_excelShowArray'] : "");
            $excelDetailRow = (isset($form['gen_excelDetailRow']) ? $form['gen_excelDetailRow'] : 2);

            if (isset($form['gen_excelTitleColumn']) && isset($form['gen_excelSheetKeyColumn'])) {
                // 複数シート
                $form['gen_subSumCriteria'] = $obj->subsum_criteria;
                $form['gen_subSumCriteriaDateType'] = $obj->subsum_criteria_datetype;
                Gen_Excel::sqlToExcel($excelQuery, $excelTitle, $excelColArray,
                    $excelShowArray, $excelDetailRow, @$form['gen_rowColorCondition'], $form['gen_subSumCriteria'], $form['gen_subSumCriteriaDateType'],
                    $form['gen_excelTitleColumn'], $form['gen_excelSheetKeyColumn']);
            } else {
                // シングルシート
                Gen_Excel::sqlToExcel($excelQuery, $excelTitle, $excelColArray,
                    $excelShowArray, $excelDetailRow, @$form['gen_rowColorCondition'], $form['gen_subSumCriteria'], $form['gen_subSumCriteriaDateType']);
            }

            $form['gen_restore_search_condition'] = 'true';
            if (isset($this->tpl)) {
                return $this->tpl;
            } else {
                return 'simple.tpl';
            }
        }

        //------------------------------------------------------
        //  Excel： URLパラメータ
        //------------------------------------------------------
        $this->listBaseChecker("excel_url", "getUserId", "");

        if (@$form['gen_excel'] == "true") {
            $form['gen_excelAction'] = "{$action}&gen_excelMode&gen_restore_search_condition=true";
            if (isset($form['gen_excelParam_noEscape'])) {
                $form['gen_excelAction'] .= "&" . $form['gen_excelParam_noEscape'];
            }
        }
        if (@$form['gen_editExcel'] == "true") {
            $form['gen_editExcelAction'] = "{$action}&gen_editExcelMode";
        }

        //------------------------------------------------------
        //  1ページの表示件数の決定
        //------------------------------------------------------
        $this->listBaseChecker("getPageCount", "setQueryParam", "");

        $pageRecordCount = $this->getPageCount($form);

        //------------------------------------------------------
        //  集計行関連
        //------------------------------------------------------
        $this->listBaseChecker("aggregate", "", "");

        // 集計値の計算はPager内でおこなう。
        if (isset($form['gen_aggregateType'])) {
            // 集計タイプがユーザーによって指定された場合
            $aggregateType = $form['gen_aggregateType'];
            $_SESSION['gen_setting_user']->aggregateType = $aggregateType;
            Gen_Setting::saveSetting();
        } else {
            if (isset($_SESSION['gen_setting_user']->aggregateType)) {
                $aggregateType = $_SESSION['gen_setting_user']->aggregateType;
            } else {
                $aggregateType = "sum"; // デフォルト
            }
        }
        $form['gen_aggregateType'] = $aggregateType;    // この値はページ側で使用することがある（例：Stock_StockInput_List）
        $isNotShowAggregate = $aggregateType == "nothing" || (isset($form['gen_notShowAggregate']) && $form['gen_notShowAggregate'] == "true");
        if ($isNotShowAggregate)
            $aggregateType = ""; // 集計行非表示のときは、Pager内で集計行データを計算しないようにする

        //------------------------------------------------------
        //  gen_app SQL加工
        //------------------------------------------------------
        if ($_SESSION['gen_app'] && isset($form['cols'])) {
            $appColArr = explode(",", $form['cols']);
            foreach($appColArr as $appCol) {
                if ($appCol != $form['gen_idField']) {
                    $find = false;
                    foreach($columnArray as $col) {
                        if (isset($col['field']) && $col['field'] == $appCol) {
                            $find = true;
                            break;
                        }
                    }
                    if (!$find) {
                        die('bad col ' . $appCol);
                    }
                }
            }
            $this->selectQuery = "SELECT {$form['cols']} from ({$this->selectQuery}) as t_gen_app";
            $aggregateType = "";
        }

        //------------------------------------------------------
        //  ページャー処理 & データの取得
        //------------------------------------------------------
        $this->listBaseChecker("pager", ($existColumn ? "sql_complete_with_cross, column_complete_with_cross," : "") . " getPageCount, aggregate, columnArray", "");

        // pageが数字でなければ1ページ目とみなされる
        $page = "";
        if (isset($form[SEARCH_FIELD_PREFIX . 'page'])) {
            $page = $form[SEARCH_FIELD_PREFIX . 'page'];
        }

        $pager = new Gen_Pager($this->selectQuery, $columnArray, $aggregateType, $pageRecordCount, $page, $orderbyStr);
        $form['gen_data'] = $pager->getData();
        $form['gen_nav_noEscape'] = $pager->getNavigator();
        $form['gen_page'] = $page;
        $form['gen_isLastPage'] = $pager->isLastPage();
        $form['gen_totalCount'] = $pager->getTotalCount();

        //------------------------------------------------------
        //  gen_app
        //------------------------------------------------------
        if ($_SESSION['gen_app']) {
            $form['response_noEscape'] = json_encode($form['gen_data']);
            return 'simple.tpl';
        }

        //------------------------------------------------------
        //  クロス集計グラフ
        //------------------------------------------------------
        $this->listBaseChecker("cross_chartShow", "pager, columnArray" . ($isCrossTableMode ? ", cross_chart" : ""), "");

        if (isset($form['gen_crossTableShow']) && $form['gen_data'] && isset($form[SEARCH_FIELD_PREFIX . 'gen_crossTableChart']) && $form[SEARCH_FIELD_PREFIX . 'gen_crossTableChart'] != "gen_nothing") {
            $chartDataCol = null;
            foreach ($columnArray as $col) {
                if ((isset($col['label']) && ($col['label'] == $form[SEARCH_FIELD_PREFIX . 'gen_crossTableChart']))
                        // 既定ロケはクロスでのlabelとしてはnullになるが、クロス集計グラフの選択肢としては空欄になる。ag.cgi?page=ProjectDocView&pid=1574&did=195166
                        || ($col['label'] == null && $form[SEARCH_FIELD_PREFIX . 'gen_crossTableChart'] == "")) {
                    $chartDataCol = $col['field'];
                    break;
                }
            }
            if ($chartDataCol !== null) {
                $form['gen_useChart'] = 'true';
                $form['gen_chartType'] = 'bar_line';    // pie / area / line / bar / bar_line
                $form['gen_chartWidth'] = '650';
                $form['gen_chartHeight'] = '150';
                $form['gen_chartAppendKey'] = 'false';   // 凡例表示。邪魔なので非表示とした
                $data = array_slice($form['gen_data'], 0, GEN_CHART_HORIZ_MAX);
                $chartData = array();
                $chartData[0] = array(
                    "gen_key" => "",
                    "field1" => $form[SEARCH_FIELD_PREFIX . 'gen_crossTableChart'],
                );
                foreach ($data as $row) {
                    $row = array($row["gen_cross_key"], $row[$chartDataCol]);
                    $chartData[] = $row;
                }
                $form['gen_chartData'] = $chartData;
            }
        }

        //------------------------------------------------------
        //  javascript
        //------------------------------------------------------
        // 表示高速化（ソースの軽量化）およびセキュリティ確保のため、
        // JavaScriptソース中のコメントおよび空白のみの行を削除する。
        $this->listBaseChecker("javascript", "setViewParam", "");

        if (isset($form['gen_javascript_noEscape'])) {
            $form['gen_javascript_noEscape'] = Gen_String::cutCommentAndBlankLine($form['gen_javascript_noEscape']);
            // 検索条件のjavascriptを追加
            $form['gen_javascript_noEscape'] .= $searchJavascript;
        } else {
            // 検索条件のjavascript
            $form['gen_javascript_noEscape'] = $searchJavascript;
        }

        //------------------------------------------------------
        //  Listテーブル表示関連
        //------------------------------------------------------
        $this->listBaseChecker("listtable", ($existColumn ? "column_complete_with_cross" : ""), "");

        $form['gen_existFixTable'] = (count(@$form['gen_fixColumnArray']) != 0);
        $form['gen_existScrollTable'] = (count(@$form['gen_columnArray']) != 0);

        // ----------  幅の計算  ----------

        // 幅が設定されていない場合、列リストから計算する
        if (!isset($form['gen_fixWidth']) && is_array(@$form['gen_fixColumnArray'])) {
            $form['gen_fixWidth'] = 1;    // これでちょうどいい
            foreach ($form['gen_fixColumnArray'] as $col) {
                // visibleはページ側で指定されるプロパティ（モードによる列の切り替えなど）、hideはフレームワーク側で制御されるプロパティ
                if (is_numeric($col['width']) && (!isset($col['visible']) || $col['visible']) && (!isset($col['hide']) || !$col['hide'])) {
                    // 「+1」はborderの分
                    $form['gen_fixWidth'] += $col['width'] + 1;
                }
            }
        }

        // リストは（列が少なくても）常にリサイズで決定された幅になるようにした。
        $settingWidth = @$_SESSION['gen_setting_user']->listTableWidth;
        $form['gen_totalWidth'] = (is_numeric($settingWidth) ? $settingWidth : 950);
        $form['gen_scrollShowWidth'] = $form['gen_totalWidth'] - @$form['gen_fixWidth'];

        // ----------  高さの計算  ----------

        // 表のデータ部分の高さを決める（基準以上の高さならスクロール）
        //   高さではなく下位置を管理するようにした。下位置はsettingで管理。
        //   リストは（データが少なくても）常にリサイズで決定された位置までの高さになるようにした。
        //   なお、gen_script.js の gen.listDataTable.prototype.init() でリストの最低高さを設定していることに注意。
        $settingBottom = @$_SESSION['gen_setting_user']->listTableBottom;
        $form['gen_listBottom'] = (is_numeric($settingBottom) ? $settingBottom : 600);    // 表の下位置。必要に応じて調整

        // 行の高さのデフォルト
        if (!is_numeric(@$form['gen_titleRowHeight'])) {
            $form['gen_titleRowHeight'] = 40;   // 削除chkを考慮し 30⇒40
        }
        if (!is_numeric(@$form['gen_aggregateRowHeight'])) {
            $form['gen_aggregateRowHeight'] = ($isNotShowAggregate ? 0 : 20);
        }
        $form['gen_titleAggregateSectionHeight'] = $form['gen_titleRowHeight'] + $form['gen_aggregateRowHeight'] + ($form['gen_aggregateRowHeight'] == 0 ? 2 : 3);
        if ($form['gen_iPad']) {
            $form['gen_dataRowHeight'] = 30;
        } else if (!is_numeric(@$form['gen_dataRowHeight'])) {
            $form['gen_dataRowHeight'] = 20;
        }

        // 合計カラム数
        $form['gen_columnCount'] = count(@$form['gen_fixColumnArray']) + count(@$form['gen_columnArray']);

        //------------------------------------------------------
        //  折り返して表示（wrapOn）列が存在するかどうか
        //------------------------------------------------------
        //  gen_script.js で、行高の調節処理をするかどうかの判断に使用
        $this->listBaseChecker("column_wrapon", ($existColumn ? "column_complete_with_cross" : ""), "");

        $form['gen_existWrapOn'] = false;
        if ($existColumn) {
            for ($i = 1; $i <= 2; $i++) {
                $arrName = ($i == 1 ? 'gen_fixColumnArray' : 'gen_columnArray');
                if (isset($form[$arrName])) {
                    foreach ($form[$arrName] as $ctl) {
                        if (isset($ctl['wrapOn']) && $ctl['wrapOn']) {
                            $form['gen_existWrapOn'] = true;
                            break 2;
                        }
                    }
                }
            }
        }

        //------------------------------------------------------
        //  最終表示日時の記録（Newアイコン用）
        //------------------------------------------------------
        $this->listBaseChecker("newIconRecord", "getUserId" . ($existColumn ? ", column_newIcon": ""), "");

        $key = array("user_id" => $userId, "action" => $form['action'] );
        $data = array(
            'last_show_time' => date('Y-m-d H:i:s'),
        );
        $gen_db->updateOrInsert('page_info', $key, $data);

        //------------------------------------------------------
        //  仮登録トークボードスレッドの削除
        //------------------------------------------------------
        // 仮IDのスレッドが残っている場合（新規レコードに対してスレッドを作成したものの
        // レコード登録せずキャンセルした場合など）、それを削除しておく。
        // 別ユーザーの仮IDスレッドは削除しない。現在登録中であるかもしれないので。
        // なぜこの処理をListBaseでやっているかについては、EditBaseのトークボード部分の「新規レコード」
        // のコメントを参照。
        $this->listBaseChecker("recordChatDelete", "getUserId" . ($existColumn ? ", column_newIcon": ""), "");

        $query = "select chat_header_id from chat_header
            where temp_user_id = '{$userId}' and record_id = '-999'";
        $tempChatHeaderArr = $gen_db->getArray($query);
        if ($tempChatHeaderArr) {
            foreach($tempChatHeaderArr as $row) {
                Logic_Chat::deleteChat($row["chat_header_id"]);
            }
        }


        //------------------------------------------------------
        //  tableload以外のときのみ
        //------------------------------------------------------
        if (!isset($form['gen_tableload'])) {

            //------------------------------------------------------
            //  拡張Dropdownの処理
            //------------------------------------------------------
            $this->listBaseChecker("dropdown", "pager, columnArray " . ($existColumn ? ",column_complete_with_cross" : ""), "");

            // 拡張Dropdownを使用しているカラムについて、子クラス側では['field']（例：品目ID）しか設定されていないが、
            // tplでの表示時にはshowtext（例：品目コード） と subtext（例：品目名）が必要。
            // ここで取得して変数に格納する。
            if (isset($columnArray) && $form['gen_data']) {
                $arrCnt = count($form['gen_data']);
                if ($arrCnt > 0) {
                    foreach ($columnArray as $ctl) {
                        if (@$ctl['type'] == "dropdown" && isset($ctl['field'])) {
                            for ($i = 0; $i < $arrCnt; $i++) {
                                // 表示値設定
                                $ddRes = Logic_Dropdown::getDropdownText(
                                    $ctl['dropdownCategory'],
                                    $form['gen_data'][$i][$ctl['field']]
                                    );
                                $form['gen_data'][$i]['dropdownShowtext'] = $ddRes['showtext'];
                                $form['gen_data'][$i]['dropdownSubtext'] = $ddRes['subtext'];
                                $form['gen_data'][$i]['dropdownHasSubtext'] = $ddRes['hasSubtext'];
                            }
                        }
                    }
                }
            }

            //------------------------------------------------------
            //  Intro（List初回アクセス時のみ）
            //------------------------------------------------------
            if ($userId != -1) {
                $query = "select show_first_intro from user_master where user_id = '{$userId}' and not coalesce(show_first_intro, false)";
                if ($gen_db->existRecord($query)) {
                    $form['gen_showIntro'] = true;
                    $query = "update user_master set show_first_intro = true where user_id = '{$userId}'";
                    $gen_db->query($query);
                }
            }
        }   // Non-Table Mode

        //------------------------------------------------------
        //  適用テンプレートの指定
        //------------------------------------------------------
        if (isset($this->tpl)) {
            return $this->tpl;
        } else {
            if (isset($form['gen_tableload']))
                return 'listtable.tpl';
            else
                return 'list.tpl';
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

    // $searchConditionArrayに格納されたカラムのみが表示条件とみなされる。
    // また暗黙的に、「orderby」「page」も格納/復元される。（これらはOrderBy処理とページングで使用）
    // ⇒2009ではpageのみ。orderbyは別の仕組みで扱うようになったため
    // session名は「クラス名_フィールド名」となる（例：Master_Item_List_Item_Code）

    function searchConditonSession(&$form, $searchConditionArray)
    {

        // 暗黙的にsession格納/復元対象となるデータ。
        $fieldName = "page";
        $sessionName = get_class($this) . '_' . $fieldName;
        $this->sessionOperation($form, $sessionName, SEARCH_FIELD_PREFIX . $fieldName);

        $fieldName = "gen_special_search";
        $sessionName = get_class($this) . '_' . $fieldName;
        $this->sessionOperation($form, $sessionName, $fieldName);

        foreach ($searchConditionArray as $field) {
            if (!isset($field['field']))
                continue;  // literalはfieldがないのでなにもしない

            $fieldName = $field['field'];
            $searchFieldName = SEARCH_FIELD_PREFIX . $fieldName;
            $type = $field['type'];

            if ($type == "dateFromTo" || $type == "dateTimeFromTo" || $type == "numFromTo" || $type == "strFromTo") {
                // 検索TypeがdateFromToのとき。ひとつのフィールドにつき2つの条件
                //（[フィールド名]_from と [フィールド名]_to）を扱う。
                $sessionNameFrom = get_class($this) . '_' . $fieldName . '_from';
                $fieldNameFrom = $searchFieldName . '_from';
                $this->sessionOperation($form, $sessionNameFrom, $fieldNameFrom);

                $sessionNameTo = get_class($this) . '_' . $fieldName . '_to';
                $fieldNameTo = $searchFieldName . '_to';
                $this->sessionOperation($form, $sessionNameTo, $fieldNameTo);

                if ($type == "strFromTo") {
                    $sessionNamePattern = get_class($this) . '_gen_strPattern_' . $fieldName;
                    $fieldNamePattern = 'gen_strPattern_' . $searchFieldName;
                    $this->sessionOperation($form, $sessionNamePattern, $fieldNamePattern);
                }
            } else if ($type == "yearMonth") {
                $sessionNameYear = get_class($this) . '_' . $fieldName . '_Year';
                $fieldNameYear = $searchFieldName . '_Year';
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

    // 上記のクロス集計項目用。
    //  一般の表示条件とは処理すべきタイミングが異なるので別にしている。
    function searchConditonSessionCross(&$form)
    {
        // クロス集計項目
        $fieldName = "gen_crossTableHorizontal";
        $sessionName = get_class($this) . '_' . $fieldName;
        $this->sessionOperation($form, $sessionName, SEARCH_FIELD_PREFIX . $fieldName);

        $fieldName = "gen_crossTableVertical";
        $sessionName = get_class($this) . '_' . $fieldName;
        $this->sessionOperation($form, $sessionName, SEARCH_FIELD_PREFIX . $fieldName);

        $fieldName = "gen_crossTableValue";
        $sessionName = get_class($this) . '_' . $fieldName;
        $this->sessionOperation($form, $sessionName, SEARCH_FIELD_PREFIX . $fieldName);

        $fieldName = "gen_crossTableMethod";
        $sessionName = get_class($this) . '_' . $fieldName;
        $this->sessionOperation($form, $sessionName, SEARCH_FIELD_PREFIX . $fieldName);

        $fieldName = "gen_crossTableChart";
        $sessionName = get_class($this) . '_' . $fieldName;
        $this->sessionOperation($form, $sessionName, SEARCH_FIELD_PREFIX . $fieldName);
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
            // 値が空のときにsessionに保存されない現象に対処するため、「gen_nothing」という特殊な値を保存するようにした
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

    function getSearchCondition($form, $searchConditionArray)
    {

        $where = "where 1=1 ";    // 1=1 はダミー（常にand始まりにできるようにするため）

        foreach ($searchConditionArray as $field) {
            if (!isset($field['field']))
                continue;  // literalはfieldがないのでなにもしない

            // setSearchCondition() の 検索fieldは、item_master___item_code という形でテーブル名指定することができる。（アンダースコア3つで区切る）
            //   テーブル名とカラム名の区切り文字に何を使用するかは難しい。エレメントのIDとして使用したり（jQueryのセレクタのメタ文字は避けたほうがよい）、
            //   URLに含めても問題ない文字でなければならない。
            //   また、ドットは使えない。PHPで register_global が有効な環境では、POSTパラメータにドットが含まれていると「_」に変換されてしまうため。
            //   ちなみに13iまで使用していた「#」は上記の条件に適わないのでやめた（13iまでは拡張DDでテーブル名指定に対応していなかったので、一応動作していた）
            $fieldName = str_replace("___", ".", $field['field']);
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
                        if (!$isNoSql)
                            $where .= " and {$fieldName} >= '{$form[$searchFieldFromName]}'";
                    }
                }

                $searchFieldToName = $searchFieldName . "_to";
                if (isset($form[$searchFieldToName])) {
                    if (Gen_String::isDateString($form[$searchFieldToName])) {
                        if (!$isNoSql)
                            $where .= " and {$fieldName} <= '{$form[$searchFieldToName]}'";
                    }
                }

            // dateTimeFromTo
            } else if ($type == 'dateTimeFromTo') {
                $searchFieldFromName = $searchFieldName . "_from";
                if (isset($form[$searchFieldFromName])) {
                    if (Gen_String::isDateTimeString($form[$searchFieldFromName])) {
                        if (!$isNoSql)
                            $where .= " and {$fieldName} >= '{$form[$searchFieldFromName]}'";
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
                            $where .= " and {$fieldName} <= '" . $form[$searchFieldToName] . $timeStr . "'";
                        }
                    }
                }

            // numFromTo
            } else if ($type == 'numFromTo') {
                $searchFieldFromName = $searchFieldName . "_from";
                if (isset($form[$searchFieldFromName])) {
                    if (Gen_String::isNumeric($form[$searchFieldFromName])) {
                        if (!$isNoSql)
                            $where .= " and {$fieldName} >= '{$form[$searchFieldFromName]}'";
                    }
                }

                $searchFieldToName = $searchFieldName . "_to";
                if (isset($form[$searchFieldToName])) {
                    if (Gen_String::isNumeric($form[$searchFieldToName])) {
                        if (!$isNoSql)
                            $where .= " and {$fieldName} <= '{$form[$searchFieldToName]}'";
                    }
                }

            // strFromTo
            } else if ($type == 'strFromTo') {
                $searchPatternName = "gen_strPattern_{$searchFieldName}";
                // 範囲検索（初期表示は値が設定されていないため最上段の範囲検索とする）
                $searchFieldFromName = "{$searchFieldName}_from";
                if (!isset($form[$searchPatternName]) || $form[$searchPatternName]=="-1") {
                    if (isset($form[$searchFieldFromName]) && $form[$searchFieldFromName]!="") {
                        if (!$isNoSql)
                            $where .= " and lower({$fieldName}) >= '" . strtolower($form[$searchFieldFromName]) . "'";
                    }

                    $searchFieldToName = "{$searchFieldName}_to";
                    if (isset($form[$searchFieldToName]) && $form[$searchFieldToName]!="") {
                        if (!$isNoSql)
                            $where .= " and lower({$fieldName}) <= '" . strtolower($form[$searchFieldToName]) . "'";
                    }
                // それ以外
                } else {
                    if (!$isNoSql && isset($form[$searchFieldFromName]) && $form[$searchFieldFromName]!="") {
                        $arr = array($fieldName);
                        // field2 は textboxのときのみ有効な特殊パラメータ。複数のカラムを対象とした検索をおこないたい時に使用する。
                        if (isset($field['field2'])) {
                            $arr[] = str_replace("___", ".", $field['field2']);
                        }
                        // エスケープ
                        //    like では「_」「%」がワイルドカードとして扱われる
                        $searchStr = str_replace('%', '\\\\%', str_replace('_', '\\\\_', $form["{$searchFieldName}_from"]));
                        $where .= self::_getMultiWordMultiPatternWhere($arr, $searchStr, @$form[$searchPatternName]);
                    }
                }

            // yearMonth
            } else if ($type == 'yearMonth') {
                $searchFieldYearName = $searchFieldName . "_Year";
                if (isset($form[$searchFieldYearName])) {
                    if (is_numeric($form[$searchFieldYearName])) {
                        if (!$isNoSql)
                            $where .= " and {$fieldName}_year = '{$form[$searchFieldYearName]}'";
                    }
                }

                $searchFieldMonthName = $searchFieldName . "_Month";
                if (isset($form[$searchFieldMonthName])) {
                    if (is_numeric($form[$searchFieldMonthName])) {
                        if (!$isNoSql)
                            $where .= " and {$fieldName}_month = '{$form[$searchFieldMonthName]}'";
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
                                $where .= " and ({$fieldName} = '{$st}' or {$fieldName}_2 = '{$st}' or {$fieldName}_3 = '{$st}')";
                            } elseif ($fieldName == "customer_group_id" || substr($fieldName, -18) == ".customer_group_id") {
                                $st = $form[$searchFieldName];
                                $where .= " and ({$fieldName}_1 = '{$st}' or {$fieldName}_2 = '{$st}' or {$fieldName}_3 = '{$st}')";
                            } else {
                                $where .= " and {$fieldName} = '{$form[$searchFieldName]}'";
                            }
                        }
                        break;
                    case 'calendar':
                        // 常に完全一致
                        if (Gen_String::isDateString($form[$searchFieldName])) {
                            if (!$isNoSql) $where .= " and {$fieldName} = '{$form[$searchFieldName]}'";
                        }
                        break;
                    case 'textbox':
                        if (!$isNoSql) {
                            if (isset($field['fieldArray']) && is_array($field['fieldArray'])) {
                                // エスケープ
                                //    like では「_」「%」がワイルドカードとして扱われる
                                $searchStr = str_replace('%', '\\\\%', str_replace('_', '\\\\_', $form[$searchFieldName]));
                                $where .= self::_getMultiWordMultiPatternWhere($field['fieldArray'], $searchStr, @$form["gen_search_match_mode_" . $searchFieldName]);
                            } else {
                                $arr = array($fieldName);
                                // field2 は textboxのときのみ有効な特殊パラメータ。複数のカラムを対象とした検索をおこないたい時に使用する。
                                if (isset($field['field2'])) {
                                    $arr[] = str_replace("___", ".", $field['field2']);
                                }
                                // エスケープ
                                //    like では「_」「%」がワイルドカードとして扱われる
                                $searchStr = str_replace('%', '\\\\%', str_replace('_', '\\\\_', $form[$searchFieldName]));
                                $where .= self::_getMultiWordMultiPatternWhere($arr, $searchStr, @$form["gen_search_match_mode_" . $searchFieldName]);
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

    private function _getSpecialSearchCondition($specialSearch, $columnArray)
    {
        // エスケープ
        //    like では「_」「%」がワイルドカードとして扱われる
        $searchStr = str_replace('%', '\\\\%', str_replace('_', '\\\\_', $specialSearch));

        $where = "and (1=0";

        foreach ($columnArray as $col) {
            // visibleはモードによる列の切り替え制御などに使用されるので、チェックしておく必要がある。
            if (!($col['type'] == 'data' || $col['type'] == 'numeric')
                    || (isset($col['visible']) && !$col['visible'])
                    || $col['field'] == "") {
                continue;
            }

            // リテラルにバックスラッシュ(\)が含まれる場合、リテラルの前に'E'をつけておかないとpostgresのWARNINGが出る
            $where .= " or cast({$col['field']} as text) ilike E'%{$searchStr}%'";
        }
        $where .= ")";

        return $where;
    }

   private function _filterToWhere($field, $filter, $isZeroToBlank)
   {
        $arr = explode(':::', $filter);

        switch($arr[0]) {
            case "data":
                list($type, $search1, $match1, $bool, $search2, $match2) = $arr;
                if ($match1 < 3 && $match2 < 3) {
                    $field = "cast({$field} as text)";
                } else {
                    // 「と一致」「を含まない」「で始まらない」「で終わらない」「空欄」「空欄以外」の場合、
                    // nullを空文字とみなして処理する（そうしないとnullがヒットせず不自然）
                    $field = "coalesce(cast({$field} as text),'')";
                }

                $filter1 = "";
                $filter2 = "";
                $text1 = "";
                $text2 = "";
                if (($search1!="" && $match1!="") || $match1=="98" || $match1=="99") {
                    $conv = self::_filterMatchConvertForData($match1, $search1);
                    $match1 = $conv[0];
                    $filter1 = $field . ' ' . $match1;
                    $text1 = $conv[1];
                }
                if (($search2!="" && $match2!="") || $match2=="98" || $match2=="99") {
                    $conv = self::_filterMatchConvertForData($match2, $search2);
                    $match2 = $conv[0];
                    $filter2 .= $field . ' ' . $match2;
                    $text2 = $conv[1];
                }
                $filter = $filter1;
                if ($filter1 != "" && $filter2 != "")
                    $filter .= ($bool=='or' ? " OR " : " AND ");
                $filter .= $filter2;

                $text = $text1;
                $text .= $text2;
                if ($filter1 != "" && $filter2 != "")
                    $text = "({$text1})" . ($bool=='or' ? " OR " : " AND ") . "({$text2})";
                break;

            case "numeric":
                list($type, $search1, $match1, $bool, $search2, $match2) = $arr;

                $filter1 = "";
                $filter2 = "";
                $text1 = "";
                $text2 = "";
                if ((Gen_String::isNumeric($search1) && $match1!="") || $match1=="98" || $match1=="99") {
                    $conv = self::_filterMatchConvertForNum($match1, $search1);
                    if ($isZeroToBlank && ($match1=="98" || $match1=="99")) {
                        $filter1 = 'case when ' . $field . ' = 0 then null else ' . $field . ' end ' . $conv[0];
                    } else {
                        $filter1 = $field . ' ' . $conv[0];
                    }
                    $text1 = $conv[1];
                }
                if ((Gen_String::isNumeric($search2) && $match2!="") || $match2=="98" || $match2=="99") {
                    $conv = self::_filterMatchConvertForNum($match2, $search2);
                    if ($isZeroToBlank && ($match2=="98" || $match2=="99")) {
                        $filter1 = 'case when ' . $field . ' = 0 then null else ' . $field . ' end ' . $conv[0];
                    } else {
                        $filter2 .= $field . ' ' . $conv[0];
                    }
                    $text2 = $conv[1];
                }
                $filter = $filter1;
                if ($filter1 != "" && $filter2 != "")
                    $filter .= ($bool=='or' ? " OR " : " AND ");
                $filter .= $filter2;

                $text = $text1;
                $text .= $text2;
                if ($filter1 != "" && $filter2 != "")
                    $text = "({$text1})" . ($bool=='or' ? " OR " : " AND ") . "({$text2})";
                break;

            case "date":
            case "datetime":
                list($type, $from, $to) = $arr;

                $filter = "";
                $text = "";
                if ($from == "98") {
                    // 空欄
                    $filter = "{$field} is null";
                    $text = "(" . _g("空欄") . ")";
                } else if ($from == "99") {
                    // 空欄以外
                    $filter = "{$field} is not null";
                    $text = "(" . _g("空欄以外") . ")";
                } else {
                    if ($from != "") {
                        $filter = "{$field} >= '{$from}'";
                        $text = sprintf(_g("%s から"), $from);
                    }
                    if ($to != "") {
                        // getSearchCondition() の時刻処理部のコメント参照
                        $timeStr = "";
                        if (strpos($to, ":")===FALSE) // 時刻が含まれていない場合
                            $timeStr = " 23:59:59.9999";
                        if ($filter != "")
                            $filter .= " and ";
                        $filter .= "{$field} <= '{$to}{$timeStr}'";
                        $text .= sprintf(_g("%s まで"), $to);
                    }
                }
                break;

            default:
                return array("", "");
        }

        if ($filter == "") {
            return array("", "");
        } else {
            return array(" and ({$filter})", $text);
        }
    }

    private function _filterMatchConvertForData($matchNum, $search) {
        $match = "";
        switch ($matchNum) {
            case "1":   // で始まる
                $match = "like '{$search}%'";
                $text = _g("で始まる");
                break;
            case "2":   // で終わる
                $match = "like '%{$search}'";
                $text = _g("で終わる");
                break;
            case "3":   // と一致
                $match = "= '{$search}'";
                $text = _g("と一致");
                break;
            case "4":   // を含まない
                $match = "not like '%{$search}%'";
                $text = _g("を含まない");
                break;
            case "5":   // で始まらない
                $match = "not like '{$search}%'";
                $text = _g("で始まらない");
                break;
            case "6":   // で終わらない
                $match = "not like '%{$search}'";
                $text = _g("で終わらない");
                break;
            case "98":   // 空欄
                $match = "= ''";    // coalesce済み
                $text = "(" . _g("空欄") . ")";
                break;
            case "99":   // 空欄以外
                $match = "<> ''";    // coalesce済み
                $text = "(" . _g("空欄以外") . ")";
                break;
            default:    // を含む
                $match = "like '%{$search}%'";
                $text = _g("を含む");
                break;
        }
        return array($match, $search . ' ' .$text);
    }

    private function _filterMatchConvertForNum($matchNum, $search) {
        $match = "";
        switch ($matchNum) {
            case "1":   // 以下
                $match = "<= '{$search}'";
                $text = _g("以下");
                break;
            case "2":   // と等しい
                $match = "= '{$search}'";
                $text = _g("と等しい");
                break;
            case "3":   // と等しくない
                $match = "<> '{$search}'";
                $text = _g("と等しくない");
                break;
            case "98":   // 空欄
                $match = "is null";
                $text = "(" . _g("空欄") . ")";
                break;
            case "99":   // 空欄以外
                $match = "is not null";
                $text = "(" . _g("空欄以外") . ")";
                break;
            default:    // 以上
                $match = ">= '{$search}'";
                $text = _g("以上");
                break;
        }
        return array($match, $search . ' ' .$text);
    }


    // SQLのWHERE部の組み立て補助
    private function _getMultiWordMultiPatternWhere($colArr, $search, $pattern)
    {
        // 「を含む」「を含まない」の場合のみ、半角スペースをandとみなす
        //  ag.cgi?page=ProjectDocView&pid=1516&did=202477
        if ($pattern == "1" || $pattern == "2" || $pattern == "3" || $pattern == "5" || $pattern == "6" || $pattern == "9") {
            $searchArr = array($search);
        } else {
            $search = str_replace('　', ' ', $search);    // 全角スペースを半角に
            $searchArr = explode(' ', $search);
        }

        $res = '';

        foreach ($searchArr as $word) {
            $res .= ' and (';
            $isFirst = true;
            foreach ($colArr as $col) {
                if ($isFirst) {
                    $isFirst = false;
                } else {
                    if ($pattern == "4" || $pattern == "5" || $pattern == "6" || $pattern == "9") {
                        $res .= ' and ';
                    } else {
                        $res .= ' or ';
                    }
                }
                // リテラルにバックスラッシュ(\)が含まれる場合、リテラルの前に'E'をつけておかないとpostgresのWARNINGが出る
                switch ($pattern) {
                    case "1":   // 前方一致
                        $res .= "cast({$col} as text) ilike E'{$word}%'";
                        break;
                    case "2":   // 後方一致
                        $res .= "cast({$col} as text) ilike E'%{$word}'";
                        break;
                    case "3":   // 完全一致
                        $res .= "cast({$col} as text) ilike E'{$word}'";
                        break;
                    case "4":   // 含まない
                        $res .= "coalesce(cast({$col} as text),'') not ilike E'%{$word}%'";
                        break;
                    case "5":   // で始まらない
                        $res .= "coalesce(cast({$col} as text),'') not ilike E'{$word}%'";
                        break;
                    case "6":   // で終わらない
                        $res .= "coalesce(cast({$col} as text),'') not ilike E'%{$word}'";
                        break;
                    case "9":   // 正規表現　-> 現在未使用。不正なパターンを指定されたときSQLエラーになる問題の対処が難しいため
                        $res .= "cast({$col} as text) ~* '{$word}'";
                        break;
                    default:   // を含む
                        $res .= "cast({$col} as text) ilike E'%{$word}%'";
                }
            }
            $res .= ')';
        }
        return $res;
    }

    //************************************************
    // Order By 関連
    //************************************************

    // order by文字列を、カラムごとに配列に格納する。
    private function _orderByStrToArray($str, $isDefault = false)
    {
        if ($str == "")
            return array();

        $arr = explode(",", str_replace("order by ", "", strtolower($str)));
        $resArr = array();
        foreach ($arr as $row) {
            $resArr[] = array(
                "column" => trim(str_replace(" desc", "", $row)),
                "isDesc" => strpos($row, " desc") !== FALSE,
                "isDefault" => $isDefault
            );
        }
        return $resArr;
    }

    // OrderBy情報の作成。
    //  下記の要素をもとに、OrderBy情報（配列）を作成する。（優先順位順）
    //      ・POSTされたソートカラム指定 （$postColumn。ユーザーが列見出しをクリックして並べ替えを指定したときにPOSTされる）
    //      ・表示条件パターンから読み出されたソートカラム情報
    //      ・既存のソートカラム情報 （page_infoテーブルから読み出す）
    //      ・ページのデフォルトソートカラム （$defaultColumn。listクラス内のコードで指定される）
    //  ここで作成されたOrderBy情報は、SQLのorder by句の組み立てや、smartyのlistプラグインでの
    //  ソートマークの表示に使用される。
    function getOrderByArray($form, $defaultColumn, $userId, $action)
    {
        global $gen_db;

        $postColumn = (isset($form['gen_search_orderby']) ? $form['gen_search_orderby'] : "");
        $deleteColumn = (isset($form['gen_orderby_delete']) ? $form['gen_orderby_delete'] : "");

        if ($form['gen_savedSearchConditionModeForOrderby']) {
            // 表示条件パターンモード
            //  表示条件パターンセレクタが変更されたとき、パターンのorderbyが読み出される。
            //　表示条件パターンを指定したまま再表示したときは読み出されない。
            //　表示条件パターンモードにおけるソート指定はあくまで一時的なもので、パターンが変わったり
            //　通常モードに戻ったときはソートをリセットする必要がある。そのため表示条件パターンモード
            //　でのソートはDBではなくセッション変数に保存する。（保存処理はこのfunctionの後のほうで行っている）
            if ($form['gen_savedSearchConditionRead']) {
                // 表示条件パターンセレクタが変更されたとき
                $orderby = "";
                if (isset($form['gen_saved_orderby'])) {
                    $orderby = $form['gen_saved_orderby'];
                }
            } else {
                // 表示条件パターンを再表示したとき
                $orderby = isset($_SESSION['gen_savedOrderBy']) ? $_SESSION['gen_savedOrderBy'] : "";
            }
        } else {
            // 通常モード
            // 既存のソートカラムを読み出し
            $query = "select orderby from page_info where user_id = '{$userId}' and action = '{$action}'";
            $orderby = $gen_db->queryOneValue($query);
            // 表示条件パターンモード用のorderbyは消去しておく
            unset($_SESSION['gen_savedOrderBy']);
        }
        $resArr = $this->_orderByStrToArray($orderby);

        // POSTされたソートカラム指定
        if ($postColumn != "") {
            // Postされたソートカラムが既存ソートカラムに含まれていた場合、既存ソートカラムを削除する。
            $postCol = trim(str_replace(" desc", "", strtolower($postColumn)));
            foreach ($resArr as $key => $row) {
                if ($row['column'] == $postCol) {
                    unset($resArr[$key]);
                }
            }
            // Postされたソートカラムを追加する。
            $arr = $this->_orderByStrToArray($postColumn);
            $resArr[] = $arr[0];
        }

        // ソートカラム削除指定
        if ($deleteColumn != "") {
            // 指定されたソートカラムを削除する。
            foreach($resArr as $key => $row) {
                if ($row['column'] == $deleteColumn) {
                    unset($resArr[$key]);
                }
            }
        }

        // ソートカラムが列リストに含まれていなければ、そのソートカラムを削除する。
        //  ユーザーにより並べ替え指定されている列が、プログラム変更やモード変更によってSQLから削除された場合に
        //  エラーになるのを防ぐ。
        if (count($resArr) > 0 && isset($form['gen_columnArray'])) {
            foreach ($resArr as $key => $row) {
                if (isset($form['gen_fixColumnArray'])) {
                    $arr = array_merge($form['gen_fixColumnArray'], $form['gen_columnArray']);
                } else {
                    $arr = $form['gen_columnArray'];
                }
                $obExist = false;
                if (is_array($arr)) {
                    foreach ($arr as $key2 => $col) {
                        if (@$arr[$key2]['field'] == $row['column']) {
                            $obExist = true;
                            break;
                        }
                    }
                }
                if (!$obExist) {
                    unset($resArr[$key]);
                }
            }
        }

        // ソートカラムを保存する。
        //  デフォルトソートカラム（次のセクションで設定）は保存しないことに注意。
        //  デフォルトソートカラムは常にorder byの最後に来る必要があるため。
        //  保存してしまうと次のソートPost時に、Postカラムよりデフォルトカラムが優先されてしまう。
        if ($form['gen_savedSearchConditionModeForOrderby']) {
            // 表示条件パターンモード
            //　DBではなくセッション変数に保存する理由については、このfunctionの最初のほうの
            //　コメントを参照。
            $_SESSION['gen_savedOrderBy'] = $orderby;
        } else {
            // 通常モード
            $data = array(
                'orderby' => $this->makeOrderBy($resArr),
            );
            $key = array("user_id" => $userId, "action" => $action);
            $gen_db->updateOrInsert('page_info', $key, $data);
        }

        // ページのデフォルトソートカラム
        if ($defaultColumn != "") {
            // デフォルトソートカラムが既存ソートカラム・Postされたソートカラムに含まれていた場合、デフォルトソートカラムを削除する。
            $defaultArr = $this->_orderByStrToArray($defaultColumn, true);
            foreach ($resArr as $row) {
                foreach($defaultArr as $key => $defaultRow) {
                    if ($row['column'] == $defaultRow['column']) {
                        unset($defaultArr[$key]);
                    }
                }
            }
            // デフォルトソートカラムを追加する。
            $resArr = array_merge($resArr, $defaultArr);
        }

        // 強制ソート（$form['gen_search_orderby'] とは異なり、保存されたソートよりも優先される）
        if (isset($form['gen_search_orderby_force'])) {
            $forceArr = $this->_orderByStrToArray($form['gen_search_orderby_force']);
            if ($forceArr && count($forceArr) > 0) {
                $resArr = array_merge($forceArr, $resArr);
            }
        }

        return $resArr;
    }

    // OrderBy句の組み立て。
    function makeOrderBy($orderbyArr)
    {
        if (count($orderbyArr) == 0)
            return "";

        $res = "";
        foreach ($orderbyArr as $row) {
            if ($res != "")
                $res .= ",";
            $res .= $row['column'] . ($row['isDesc'] ? " desc" : "");
        }
        return "order by " . $res;
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
                $pageRecordCount = (int) (ceil((float)$pageRecordCount / 3) * 3);
            }
        }

        return $pageRecordCount;
    }



    //************************************************
    // リロードチェック
    //************************************************
    private function _listReloadCheck($reqId)
    {
        // リロードチェック。
        //  リロードにより、列の入れ替え処理やリセット処理が再実行されてしまうのを防ぐ。
        //  ページリクエストIDが渡されていないときはチェックしない。
        //  チェックとしては甘いが、この処理は単にリロードされたときの2重処理を防ぐだけで、セキュリティ上の意味は無いのでこれでよしとする。
        $isReload = false;
        if (isset($reqId) && $reqId != "") {
            $isReload = (!Gen_Reload::reloadCheck($reqId));
        }

        return $isReload;
    }

    private function _getPageRequestId()
    {
        // 次回のページリクエストIDの発行処理
        global $_SESSION;
        $reqId = sha1(uniqid(rand(), true));
        $_SESSION['gen_page_request_id'][] = $reqId;

        return $reqId;
    }



    //************************************************
    // 列情報の読み出し
    //************************************************
    function loadColumns(&$form, $userId, $action, $isReload)
    {
        global $gen_db;

        // 列のリセット処理
        if (isset($form['gen_columnReset']) && !$isReload) {
            // 列のリセット
            $gen_db->query("delete from column_info where user_id = '{$userId}' and action = '{$action}'");
            $colInfoJson = "";
        }

        // 列情報とソート情報の読み出し
        $query = "select * from column_info where user_id = '{$userId}' and action = '{$action}'";
        $colInfoArr = $gen_db->getArray($query);

        return $colInfoArr;
    }

    // 表示条件の読み出し
    function loadSearchColumns($isReset, $userId, $action, $isReload)
    {
        global $gen_db;

        // 表示条件のリセット処理
        if ($isReset && !$isReload) {
            // 列のリセット
            $gen_db->query("delete from search_column_info where user_id = '{$userId}' and action = '{$action}'");
        }

        // 表示条件情報の読み出し
        $query = "select * from search_column_info where user_id = '{$userId}' and action = '{$action}'";
        $colInfoArr = $gen_db->getArray($query);

        return $colInfoArr;
    }


    //************************************************
    // 列情報の並べ替え
    //************************************************
    function sortColumns($isSearch, &$form, &$colInfoArr, $userId, $action, $isReload)
    {
        global $gen_db;

        if ($isSearch) {
            // 表示条件の並べ替え
            $table = 'search_column_info';
            $columnArray = &$form['gen_searchControlArray'];
        } else {
            // 列の並べ替え
            $table = 'column_info';
            if (isset($form['gen_fixColumnArray'])) {
                $fixColumnArray = &$form['gen_fixColumnArray'];
            }
            $columnArray = &$form['gen_columnArray'];
        }

        // ------------------------------------
        //  columnArray へのキーの追加
        // ------------------------------------
        // 後の処理のため、columnArray にキーを追加しておく
        // 　2010iでは $arrnoをキーとしていたが、それだとカスタマイズで列を追加削除したり、
        // 　モード切替（$form['gen_columnMode']）以外で動的に列が増減する場合（例えば日付列）
        // 　に、本来とは異なった列に列設定が適用されてしまうケースがあった。
        //   （表示されるべき列が非表示になってしまったりした）
        // 　そのため12iでは、2009iの方式（label + field をdbサニタイズしてキーとする）に戻した。
        //   これだと label + field が全く同じ列があったときに同じ設定が適用されてしまう、labelを変更しただけで
        //   列設定がリセットされてしまう、という問題があるが、10iのときの問題よりは小さいと判断した。
        //   根本的に解決するには columnArrayにそのつどプログラマがユニークIDを振るしかないが、それは面倒だろう。
        if (isset($fixColumnArray)) {
            foreach ($fixColumnArray as $arrno=>$col) {
                $label = (isset($fixColumnArray[$arrno]['label']) ? $fixColumnArray[$arrno]['label'] : "") . (isset($fixColumnArray[$arrno]['label_noEscape']) ? $fixColumnArray[$arrno]['label_noEscape'] : "");
                $fixColumnArray[$arrno]['gen_key'] = $gen_db->quoteParam(urlencode($label . (isset($fixColumnArray[$arrno]['field']) ? $fixColumnArray[$arrno]['field'] : "")));
            }
        }
        foreach ($columnArray as $arrno=>$col) {
            $label = (isset($columnArray[$arrno]['label']) ? $columnArray[$arrno]['label'] : "") . (isset($columnArray[$arrno]['label_noEscape']) ? $columnArray[$arrno]['label_noEscape'] : "");
            $columnArray[$arrno]['gen_key'] = $gen_db->quoteParam(urlencode($label . @$columnArray[$arrno]['field']));
        }

        // ------------------------------------
        //  列情報（column_info）の新規作成
        // ------------------------------------
        // 列情報がない場合、新規作成する
        //   列の入れ替えがない場合でも、Ajaxによる列幅等変更処理（Config_Setting_AjaxListColInfo）のために作成しておく必要がある
        if (!is_array($colInfoArr)) {
            $colInfoArr = array();

            // 列番号はfunction.gen_data_listのカラムidと同じ方式、つまりfixが0-、columnが1000-
            if (isset($fixColumnArray)) {
                $colNum = 0;
                foreach ($fixColumnArray as $arrno => $col) {
                    $key = array("user_id" => $userId, "action" => $action, "column_key" => $col['gen_key']);
                    $data = array(
                        "column_key" => $col['gen_key'],    // keyだが$colInfoArrのために必要
                        "column_number" => $colNum,
                        "column_width" => $col['width'],
                        "column_hide" => (isset($col['hide']) && $col['hide'] ? true : null),
                        "column_keta" => (isset($col['keta']) ? $col['keta'] : GEN_DECIMAL_POINT_LIST),
                        "column_kanma" => (isset($col['kanma']) ? $col['kanma'] : 1),   // デフォルトはカンマあり
                        "column_align" => (@$col['align']=="center" ? 1 : (@$col['align']=="right" ? 2 : 0)),
                        "column_bgcolor" => (isset($col['bgcolor']) ? $col['bgcolor'] : ""),
                        "column_filter" => (isset($col['filter']) ? $col['filter'] : ""),
                        "column_wrapon" => (isset($col['wrapOn']) && $col['wrapOn'] ? 1 : 0),
                    );
                    $gen_db->updateOrInsert($table, $key, $data);
                    $colInfoArr[] = $data;
                    $colNum++;
                }
            }
            $colNum = 1000;
            foreach ($columnArray as $arrno => $col) {
                $key = array("user_id" => $userId, "action" => $action, "column_key" => $col['gen_key']);
                if ($isSearch) {
                    $data = array(
                        "column_key" => $col['gen_key'],    // keyだが$colInfoArrのために必要
                        "column_number" => $colNum,
                        "column_hide" => (isset($col['hide']) && $col['hide'] ? true : null),
                    );

                } else {
                    $data = array(
                        "column_key" => $col['gen_key'],    // keyだが$colInfoArrのために必要
                        "column_number" => $colNum,
                        "column_width" => $col['width'],
                        "column_hide" => (isset($col['hide']) && $col['hide'] ? true : null),
                        "column_keta" => (isset($col['keta']) ? $col['keta'] : GEN_DECIMAL_POINT_LIST),
                        "column_kanma" => (isset($col['kanma']) ? $col['kanma'] : 1),   // デフォルトはカンマあり
                        "column_align" => (@$col['align'] == "center" ? 1 : (@$col['align'] == "right" ? 2 : 0)),
                        "column_bgcolor" => (isset($col['bgcolor']) ? $col['bgcolor'] : ""),
                        "column_filter" => (isset($col['filter']) ? $col['filter'] : ""),
                        "column_wrapon" => (isset($col['wrapOn']) && $col['wrapOn'] ? 1 : 0),
                    );
                }
                $gen_db->updateOrInsert($table, $key, $data);
                $colInfoArr[] = $data;
                $colNum++;
            }
        }

        // ------------------------------------
        //  D&Dによる列の入れ替え処理
        // ------------------------------------
        // D&Dによる列の入れ替え指示を受け取っている場合
        if (!$isSearch && isset($form['gen_dd_num']) && is_numeric($form['gen_dd_num']) && isset($form['gen_ddtarget_num']) && is_numeric($form['gen_ddtarget_num']) && !$isReload) {
            // ドロップターゲットが現在位置より右側なら、ターゲット（新位置）をひとつ左にずらす
            // （現在位置より左側なら、ターゲットはそのまま。これで挿入位置は常にドロップ列の左側となる）
            // ただし固定⇒スクロール列の場合はこの調整を行わない
            if ($form['gen_dd_num'] < $form['gen_ddtarget_num']
                    && (($form['gen_dd_num'] < 1000 && $form['gen_ddtarget_num'] < 1000)
                    || ($form['gen_dd_num'] >= 1000 && $form['gen_ddtarget_num'] >= 1000)))
                $form['gen_ddtarget_num']--;

            // 列入れ替え処理（列情報の更新）
            foreach ($colInfoArr as &$colInfo) {
                $num = $colInfo['column_number'];
                if ($form['gen_dd_num'] == $num) {
                    // 移動列
                    $colInfo['column_number'] = $form['gen_ddtarget_num'];
                } else {
                    if (($form['gen_dd_num'] < 1000 && $num < 1000) || $form['gen_dd_num'] >= 1000 && $num >= 1000) {
                        if ($form['gen_dd_num'] < $colInfo['column_number']) {
                            --$colInfo['column_number'];
                        }
                    }
                    if (($form['gen_ddtarget_num'] < 1000 && $num < 1000) || $form['gen_ddtarget_num'] >= 1000 && $num >= 1000) {
                        if ($form['gen_ddtarget_num'] <= $colInfo['column_number']) {
                            ++$colInfo['column_number'];
                        }
                    }
                }
                // DB更新
                if ($num != $colInfo['column_number']) {
                    $key = array("user_id" => $userId, "action" => $action, "column_key" => $colInfo['column_key']);
                    $data = array(
                        "column_number" => $colInfo['column_number'],
                    );
                    $gen_db->updateOrInsert($table, $key, $data);
                }
            }
            unset($colInfo);
        }

        // ------------------------------------
        //  列情報（column_info）の更新
        // ------------------------------------
        //  Listクラスのプログラムが書き換えられたりモードが切り替えられたりして、列の追加や削除があった場合、
        //  column_info もそれにあわせて更新する。
        //  D&Dによる列入れ替え処理よりも後に行う必要がある（列入れ替え指示は旧番号で送られてきているはずなので）

        //　列追加処理
        for ($i = 1; $i <= 2; $i++) {
            if ($i == 1) {
                $arr = (isset($fixColumnArray) ? $fixColumnArray : "");
            } else {
                $arr = $columnArray;
            }
            if (is_array($arr)) {
                foreach ($arr as $arrno => $col) {
                    $isExist = false;
                    foreach ($colInfoArr as $colInfo) {
                        if ($colInfo['column_key'] == $col['gen_key']) {
                            $isExist = true;
                            break;
                        }
                    }

                    if (!$isExist) {
                        // col_infoの列番号をずらす
                        foreach ($colInfoArr as &$colInfo) {
                            $num = $colInfo['column_number'];
                            if (($i == 1 && $arrno <= $num && $num < 1000)
                                    || ($i == 2 && ($arrno+1000) <= $num)) {
                                 $colInfo['column_number']++;
                            }
                        }
                        unset($colInfo);

                        $data = array(
                            'column_number' => 'noquote:column_number+1',
                        );
                        $where = "user_id = {$userId} and action = '{$action}' and " .
                            ($i==1 ? "{$arrno} <= column_number and column_number < 1000"
                             : ($arrno+1000) . " <= column_number");
                        $gen_db->update($table, $data, $where);

                        // col_infoに列追加
                        if ($isSearch) {
                            $data = array(
                                "column_key" => $col['gen_key'],
                                "column_number" => ($i == 1 ? $arrno : $arrno + 1000),
                                "column_hide" => (isset($col['hide']) && $col['hide'] ? true : null),
                            );
                        } else {
                            $data = array(
                                "column_key" => $col['gen_key'],
                                "column_number" => ($i == 1 ? $arrno : $arrno + 1000),
                                "column_width" => $col['width'],
                                "column_hide" => (isset($col['hide']) && $col['hide'] ? true : null),
                                "column_keta" => (isset($col['keta']) ? $col['keta'] : GEN_DECIMAL_POINT_LIST),
                                "column_kanma" => (isset($col['kanma']) ? $col['kanma'] : 1),    // デフォルトはカンマあり
                                "column_align" => (@$col['align'] == "center" ? 1 : (@$col['align'] == "right" ? 2 : 0)),
                                "column_bgcolor" => (isset($col['bgcolor']) ? $col['bgcolor'] : ""),
                                "column_filter" => (isset($col['filter']) ? $col['filter'] : ""),
                                "column_wrapon" => (isset($col['wrapOn']) && $col['wrapOn'] ? 1 : 0),
                            );
                        }
                        $colInfoArr[] = $data;

                        $key = array("user_id" => $userId, "action" => $action, "column_key" => $col['gen_key']);
                        $gen_db->updateOrInsert($table, $key, $data);
                    }
                }
            }
        }

        // 列削除処理
        $delKeyArr = array();
        foreach ($colInfoArr as $colInfo) {
            $isExist = false;
            $key = $colInfo['column_key'];
            if (isset($fixColumnArray)) {
                foreach ($fixColumnArray as $arrno => $col) {
                    if ($key == $col['gen_key']) {
                        $isExist = true;
                        break;
                    }
                }
            }
            if (!$isExist) {
                foreach ($columnArray as $arrno => $col) {
                    if ($key == $col['gen_key']) {
                        $isExist = true;
                        break;
                    }
                }
            }
            if (!$isExist) {
                $delKeyArr[] = $key;
            }
        }
        if (count($delKeyArr) > 0) {
            foreach ($delKeyArr as $delKey) {
                foreach ($colInfoArr as $colInfoArrKey => $colInfo) {
                    $key = $colInfo['column_key'];
                    $num = $colInfo['column_number'];

                    if ($key == $delKey) {
                        foreach ($colInfoArr as &$colInfo2) {
                            $num2 = $colInfo2['column_number'];
                            if ($num <= $num2 && (($num < 1000 && $num2 < 1000) || ($num >= 1000 && $num2 >= 1000))) {
                                $colInfo2['column_number'] = ($num2 - 1);
                            }
                        }
                        unset($colInfoArr[$colInfoArrKey]);
                        unset($colInfo2);

                        // DB更新
                        $data = array(
                            'column_number' => 'noquote:column_number-1',
                        );
                        $where = "user_id = {$userId} and action = '{$action}' and " .
                            "{$num} <= column_number and (({$num} < 1000 and column_number < 1000) or ({$num} >= 1000 and column_number >= 1000))";
                        $gen_db->update($table, $data, $where);

                        $query = "delete from {$table} where user_id = '{$userId}' and action = '{$action}' and column_key = '{$key}'";
                        $gen_db->query($query);
                    }
                }
            }
        }

        // ------------------------------------
        //  denyMoveの処理
        // ------------------------------------
        // denyMove列は必ず指定された位置に配置する必要がある。
        // D&Dや列更新処理で移動してしまった場合、元の位置に戻す。
        for ($i = 1; $i <= 2; $i++) {
            if ($i == 1) {
                $arr = (isset($fixColumnArray) ? $fixColumnArray : "");
                $colNum = 0;
            } else {
                $arr = $columnArray;
                $colNum = 1000;
            }
            if (is_array($arr)) {
                foreach ($arr as $arrno => $col) {
                    if (isset($col['denyMove']) && $col['denyMove']) {
                        foreach($colInfoArr as $colInfoArrKey => $colInfo) {
                            if ($colInfo['column_key'] == $col['gen_key']) {
                                if ($colInfo['column_number'] != $colNum) {
                                    if ($colInfo['column_number'] < $colNum) {
                                        foreach($colInfoArr as &$colInfo2) {
                                            if ($colInfo2['column_number'] > $colInfo['column_number'] && $colInfo2['column_number'] <= $colNum) {
                                                --$colInfo2['column_number'];
                                            }
                                        }
                                        $data = array(
                                            'column_number' => 'noquote:column_number-1',
                                        );
                                        $where = "user_id = {$userId} and action = '{$action}' and " .
                                            "column_number > {$colInfo['column_number']} and column_number <= {$colNum}";
                                        $gen_db->update($table, $data, $where);
                                    } else {
                                        foreach($colInfoArr as &$colInfo2) {
                                            if ($colInfo2['column_number'] < $colInfo['column_number'] && $colInfo2['column_number'] >= $colNum) {
                                                ++$colInfo2['column_number'];
                                            }
                                        }
                                        $data = array(
                                            'column_number' => 'noquote:column_number+1',
                                        );
                                        $where = "user_id = {$userId} and action = '{$action}' and " .
                                            "column_number < {$colInfo['column_number']} and column_number >= {$colNum}";
                                        $gen_db->update($table, $data, $where);
                                    }
                                    unset($colInfo2);
                                    $colInfoArr[$colInfoArrKey]['column_number'] = $colNum;
                                }
                                break;
                            }
                        }
                    }
                    ++$colNum;
                }
            }
        }

        // ------------------------------------
        //  列の並べ替え
        // ------------------------------------
        // 列の並べ替えの準備 （各列に列番号を追加し、列幅等を設定。並べ替えのキーとなる配列を作成。）
        foreach ($colInfoArr as &$colInfo) {
            if (isset($colInfo['column_align'])) {
                $align = $colInfo['column_align'];
                $colInfo['column_align'] = ($align == "0" ? "left" : ($align == "1" ? "center" : "right"));
            }
        }
        unset($colInfo);
        if (isset($fixColumnArray)) {
            $gen_num_fix = array();
            foreach ($fixColumnArray as $arrno => $col) {
                foreach ($colInfoArr as $colInfo) {
                    if ($colInfo['column_key'] == $col['gen_key']) {
                        $num = $colInfo['column_number'];
                        if ($num < 1000) {
                            $fixColumnArray[$arrno]['gen_num'] = $num;
                            $fixColumnArray[$arrno]['width'] = $colInfo['column_width'];
                            $fixColumnArray[$arrno]['hide'] = ($colInfo['column_hide'] === true || $colInfo['column_hide'] === 't');
                            $fixColumnArray[$arrno]['keta'] = $colInfo['column_keta'];
                            $fixColumnArray[$arrno]['kanma'] = $colInfo['column_kanma'];
                            $fixColumnArray[$arrno]['align'] = $colInfo['column_align'];
                            $fixColumnArray[$arrno]['bgcolor'] = $colInfo['column_bgcolor'];
                            $fixColumnArray[$arrno]['filter'] = $colInfo['column_filter'];
                            $fixColumnArray[$arrno]['wrapOn'] = (isset($colInfo['column_wrapon']) && $colInfo['column_wrapon'] == "1");
                            $gen_num_fix[] = $num;
                        } else {
                            // fixColumn から column へ移動
                            $columnArray[] = $col;
                            unset($fixColumnArray[$arrno]);
                        }
                        break;
                    }
                }
            }
        }

        $gen_num_col = array();
        foreach ($columnArray as $arrno => $col) {
            foreach ($colInfoArr as $colInfo) {
                if ($colInfo['column_key'] == $col['gen_key']) {
                    $num = $colInfo['column_number'];
                    if ($num >= 1000) {
                        $columnArray[$arrno]['gen_num'] = $num;
                        $columnArray[$arrno]['hide'] = ($colInfo['column_hide'] === true || $colInfo['column_hide'] === 't');
                        if (!$isSearch) {
                            $columnArray[$arrno]['width'] = $colInfo['column_width'];
                            $columnArray[$arrno]['keta'] = $colInfo['column_keta'];
                            $columnArray[$arrno]['kanma'] = $colInfo['column_kanma'];
                            $columnArray[$arrno]['align'] = $colInfo['column_align'];
                            $columnArray[$arrno]['bgcolor'] = $colInfo['column_bgcolor'];
                            $columnArray[$arrno]['filter'] = $colInfo['column_filter'];
                            $columnArray[$arrno]['wrapOn'] = (isset($colInfo['column_wrapon']) && $colInfo['column_wrapon'] == "1");
                        }
                        $gen_num_col[] = $num;
                    } else {
                        // column から fixColumnへ移動
                        $col['gen_num'] = $num;
                        $col['hide'] = ($colInfo['column_hide'] === true || $colInfo['column_hide']==='t');
                        if (!$isSearch) {
                            $col['width'] = $colInfo['column_width'];
                            $col['keta'] = $colInfo['column_keta'];
                            $col['kanma'] = $colInfo['column_kanma'];
                            $col['align'] = $colInfo['column_align'];
                            $col['bgcolor'] = $colInfo['column_bgcolor'];
                            $col['filter'] = $colInfo['column_filter'];
                            $col['wrapOn'] = (isset($colInfo['column_wrapon']) && $colInfo['column_wrapon'] == "1");
                        }
                        $fixColumnArray[] = $col;
                        $gen_num_fix[] = $num;
                        unset($columnArray[$arrno]);
                    }
                }
            }
        }

        // 列の並べ替え
        if (isset($fixColumnArray)) {
            array_multisort($gen_num_fix, SORT_ASC, SORT_NUMERIC, $fixColumnArray);
        }
        array_multisort($gen_num_col, SORT_ASC, SORT_NUMERIC, $columnArray);
    }
}
