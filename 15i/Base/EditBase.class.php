<?php

// 抽象クラス（abstract）。インスタンスを生成できない。
//   PHP4のときは「abstract」を外すこと

abstract class Base_EditBase
{

    var $keyColumn;
    var $selectQuery;
    var $modelName;

    //************************************************
    // 抽象メソッド
    //************************************************
    // 子クラスで必ず実装しなければならない。
    //   PHP4の時はコメントアウトすること
    abstract function setQueryParam(&$form);

    abstract function setViewParam(&$form);

    //************************************************
    // メイン
    //************************************************

    function execute(&$form)
    {
        global $gen_db;

        //------------------------------------------------------
        //  クエリ用パラメータをクラス変数に設定（子クラスで実装）
        //------------------------------------------------------

        $this->setQueryParam($form);

        //------------------------------------------------------
        //  Excel モード
        //------------------------------------------------------

        if (isset($_REQUEST['gen_excel']) && $_REQUEST['gen_excel'] == "true") {
            $form['response_noEscape'] = $this->_excelOperation($form);
            return 'simple.tpl';
        }

        //------------------------------------------------------
        //  キーカラムの設定とモード判断
        //------------------------------------------------------
        // キー値が指定されているとき（つまり修正/コピーモード）は、該当レコードのデータを読み出して$formに追加する。
        // 同時に、修正モードのときはキーカラムをGET形式にしたものを準備する。
        // キー値が$formに含まれていない場合は、新規モードとみなされる。

        $isNew = true;

        if ($this->keyColumn != "") {
            // 後半の条件（空文字チェック）は、新規登録時、EditListでの行番号変更時のPostで、キーカラムがPostされる
            // （値は空文字）ことへの対応。
            if (isset($form[$this->keyColumn]) && $form[$this->keyColumn] != "") {
                // キーがセットされているとき（修正 or コピーモード）
                $isNew = false;
                $form['gen_keyParamForUrl'] = "";
                if (!isset($form['gen_record_copy'])) {
                    // 修正モード
                    // キーカラムをGETパラメータ形式で$formに格納する（Edit画面でPost URLを書き出す際に使われる）。
                    $form['gen_keyParamForUrl'] .= "&" . h($this->keyColumn . "=" . $form[$this->keyColumn]);

                    // ちなみに、コピーモードではキーを渡さない。
                    // データの中に含まれるキーカラムについては、このあとのsetViewParam()後に消去している。
                    // ちなみにコピーモードでEditListがある場合、ここでキーを渡さないだけでなく、明細行のキーも
                    // 埋めこまないようにしなければならない。その処理はsmarty_function_gen_edit_controlの
                    // EditList書き出し部で行っている。
                }
            }
        }

        //------------------------------------------------------
        // action関連
        //------------------------------------------------------
        
        // キーパラメータつきaction　（各種設定変更後（表示項目・リセット・EditList等）の画面再表示時に使用）
        //  例：　新規モード：　Master_Item_Edit
        //  　　　修正モード：　Master_Item_Edit&item_id=1
        $form['gen_editActionWithKey'] = h($form['action'])
                . (isset($form['gen_keyParamForUrl']) ? $form['gen_keyParamForUrl'] : "")   // この値はエスケープ済み
                . (isset($form['gen_multi_edit']) ? "&gen_multi_edit" : "")
                // 入出庫（Stock_Inout_Edit）のときは特別に classification をつける
                . ($form['action'] == "Stock_Inout_Edit" && isset($form['classification']) ? "&classification=" . h($form['classification']) : "");
        
        // ページモードつきaction　（各種設定（ピン・表示項目等）のDB保存キーとして使用）
        //  例：　Stock_Inout_List_in
        //  ※キーパラメータはつかない
        //  ※ページモード（gen_pageMode）はEditクラス側で設定。主に Stock_Inout の classification
        $form['gen_actionWithPageMode_noEscape'] = $form['action']
                . (isset($form['gen_pageMode']) ? "_" . $form['gen_pageMode'] : "");

        //------------------------------------------------------
        // ピンどめされたデフォルト値の読み出し
        //------------------------------------------------------

        if ($isNew) {    // 新規モードのみ
            $user_id = Gen_Auth::getCurrentUserId();
            $form['gen_pins'] = array();
            $colInfoJson = $gen_db->queryOneValue("select pin_info from page_info where user_id = '{$user_id}' and action = '{$form['gen_actionWithPageMode_noEscape']}'");

            // 登録の際に「\」が「￥」に自動変換されているので、ここで元に戻す必要がある。
            if (($colInfoObj = json_decode(str_replace("￥", "\\", $colInfoJson))) != null) {
                foreach ($colInfoObj as $key => $val) {
                    // 既存の値（連続登録でキープされている値）は上書きする。
                    $form[$key] = $val;
                    $form['gen_pins'][] = $key;
                }
            }
        }
        $form['gen_isNew'] = $isNew;

        //------------------------------------------------------
        //  カスタム項目1
        //------------------------------------------------------
        // select文の修正。
        $this->selectQuery = self::_addCustomColumnToSelectQuery($form, $this->selectQuery);

        //------------------------------------------------------
        //  データの取得
        //------------------------------------------------------

        if (isset($form['gen_multi_edit'])) {
            // 次の2つはtplにhiddenで埋め込む。
            // POST URLに付加するほうが簡単に思えるが、その方法だとvalid error時が面倒。
            $form['gen_multiEditAction'] = $form['action'];
            $form['gen_multiEditKey'] = $form[$this->keyColumn];

            // キーの取得
            $form['gen_multiEditKeyArray'] = explode(',', $form['gen_multiEditKey']);

            $cols = "";
            foreach($form['gen_multiEditKeyArray'] as $row) {
                if ($cols != "") $cols .= ",";
                $cols .= "'{$row}'";
            }
            $form['gen_multiEditKeyString'] = $cols;
        }

        if (!$isNew && !isset($form['gen_editReload'])) {    // 修正 or コピーモード
            // 修正モードなしの画面（例：barcodeEdit）では selectQuery を空欄にできるが、修正モードがある画面では指定必須
            if (trim($this->selectQuery) == "") {
                throw new Exception("修正モードがある画面では、必ず selectQuery を指定する必要があります。");
            }
            if (isset($form['gen_multi_edit'])) {
                $whereStrForMulti = $this->makeWhere($form, $this->keyColumn);
            }

            if (!isset($form['gen_validError']) || !$form['gen_validError']) {    // validエラーでリダイレクトされてきたときは読み出さない
                // データ取得SQLの組み立て
                $whereStr = $this->makeWhere($form, $this->keyColumn);
                $query = str_replace('[Where]', $whereStr, $this->selectQuery);

                // データ取得
                $res = $gen_db->getArray($query);
                if ($res === false) {
                    // データなしのとき。新規登録モードに変更（他ユーザーにより削除済みの場合や、修正モードで明細行の最後の1行を削除した直後など）
                    $form['gen_isNew'] = true;
                    $isNew = true;
                    // ドロップダウン等のSQLでエラーが発生するのを防ぐため、キー項目を消しておく
                    unset($form[$this->keyColumn]);
                } else {
                    // データあり
                    if (isset($form['gen_multi_edit'])) {
                        // 値の再読み出し。レコードによって値が異なる項目は [multi] とする。
                        reset($res[0]);
                        $multiEditQuery = "select '{$form[$this->keyColumn]}' as gen_multi_key ";
                        $fieldArr = array();
                        foreach ($res[0] as $field => $value) {
                            $multiEditQuery .= ",";
                            $multiEditQuery .= "case when count(distinct coalesce(cast({$field} as text),'')) <= 1 then '{$value}' else '[multi]' end as {$field}";
                            $fieldArr[] = $field;
                        }
                        $multiEditQuery .= " from ({$this->selectQuery}) as t_gen_multi";
                        $multiEditQuery = str_replace('[Where]', $whereStrForMulti, $multiEditQuery);

                        $res = $gen_db->getArray($multiEditQuery);
                        foreach ($res[0] as $field => $value) {
                            if ($field != $this->keyColumn) $form[$field] = $value;
                        }
                    } else {
                        foreach ($res[0] as $field => $value) { // 1レコード目だけを結果とする（1次元配列になる）
                            $form[$field] = $value;
                        }
                    }
                }
                // ちなみに $form += $res[0]; という書き方をするとキーが既存だったときに
                // 同一項目が複数存在することになってしまう
            }
        }

        //------------------------------------------------------
        //  モード表示
        //------------------------------------------------------

        $form['gen_edit_mode_border_color'] = '#A0A0A0';
        if (isset($form['gen_record_copy'])) {
            $form['gen_edit_mode_label'] = _g('コピー');
            $form['gen_edit_mode_sub_background_color'] = '#b6eae4';
            $form['gen_edit_mode_sub_color'] = '#000000';
            $form['gen_last_update'] = '';
            $form['gen_last_updater'] = '';
        } else {
            if ($isNew) {
                $form['gen_edit_mode_label'] = _g('新規');
                $form['gen_edit_mode_sub_background_color'] = '#f0f0f0';
                $form['gen_edit_mode_sub_color'] = '#000000';
            } else {
                $form['gen_edit_mode_label'] = _g('修正');
                $form['gen_edit_mode_sub_background_color'] = '#f0f0f0';
                $form['gen_edit_mode_sub_color'] = '#000000';
            }
        }

        $form['gen_restore_search_condition'] = 'true';

        //------------------------------------------------------
        //  ロック基準日によるロック（修正 or コピーモード）
        //------------------------------------------------------
        //  データ取得より後に。しかしsetViewParamでgen_readonlyを使用しているクラスがあるので、
        //  setViewParamよりは前に行う必要がある
        //  ロック日のデータもコピーする可能性があるため、コピーモードはロックしない
        if ((is_array(@$form["gen_dateLockFieldArray"])
                || is_array(@$form["gen_salesDateLockFieldArray"])
                || is_array(@$form["gen_buyDateLockFieldArray"])) && !$isNew && !isset($form['gen_record_copy'])) { // 修正
            // validエラーによる差し戻しの際はチェックしない。
            // 日付をロック日以前に変更 → validエラーで差し戻し → 変更後日付をもとにロックされてしまい修正ができない、という事態を避けるため。
            // validエラーが発生した（=登録ボタンが押せた）ということは、元データはロック日より後だったということなので、
            // ノーチェックでも問題ない。
            // また、赤伝モードのときもチェックしない。赤伝は、ロック済みデータに対しても発行可能とする必要があるため
            if ((!isset($form['gen_validError']) || !$form['gen_validError']) && !isset($form['gen_redMode'])) {
                // 過去データロック
                if (is_array(@$form["gen_dateLockFieldArray"])) {
                    $lockFieldArray = $form["gen_dateLockFieldArray"];
                    $cat = 0;
                }
                // 販売データロック
                if (is_array(@$form["gen_salesDateLockFieldArray"])) {
                    $lockFieldArray = $form["gen_salesDateLockFieldArray"];
                    $cat = 1;
                }
                // 購買データロック
                if (is_array(@$form["gen_buyDateLockFieldArray"])) {
                    $lockFieldArray = $form["gen_buyDateLockFieldArray"];
                    $cat = 2;
                }
                foreach ($lockFieldArray as $lockField) {
                    if (Gen_String::isDateString(@$form[$lockField])) {
                        $lock_date = Logic_SystemDate::getStartDate($cat);
                        if (strtotime($form[$lockField]) < $lock_date) {
                            $form["gen_readonly"] = "true";
                            if (strlen(@$form["gen_readonlyMessage"]) == 0) {
                                $form["gen_readonlyMessage"] = sprintf(_g("このデータの日付がデータロック基準日（%s）より前であるため、データを更新できません。"), date('Y-m-d', $lock_date));
                            }
                        }
                    }
                }
            }
        }

        //------------------------------------------------------
        //  表示関係の値をクラス変数に設定（子クラスで実装）
        //------------------------------------------------------

        $this->setViewParam($form);

        //------------------------------------------------------
        //  ラベルの長さのデフォルト値
        //------------------------------------------------------
        // ワード変換機能によりラベルの長さが想定以上になる可能性があるため、
        // ラベル長のデフォルト値を設定しておく。

        if (!isset($form['gen_labelWidth'])) {
// とりあえず無制限にしてみる
//            $form['gen_labelWidth'] = 110;
        }

        //------------------------------------------------------
        // カスタム項目2
        //------------------------------------------------------
        //  editControlArrayにカスタム項目を追加

        self::_insertCustomColumn($form);

        $user_id = Gen_Auth::getCurrentUserId();

        //--------------------------------
        //  トークボード
        //--------------------------------
        $isAttachableGroup = Logic_EditGroup::isAttachableGroup($form['gen_action_group']);
$disableTalkBoard = ($_SERVER['SERVER_NAME']!="127.0.0.1" && $_SERVER['SERVER_NAME']!="gw.genesiss.jp" && $_SERVER['SERVER_NAME']!="219.94.237.237");
        if ($isAttachableGroup && !isset($form['gen_noChatShow']) && !$disableTalkBoard) {
            $literal = '';
            $needCreateScript = false;
            $isEditMode = isset($form[$this->keyColumn]) && $form[$this->keyColumn] != '' && !isset($form['gen_record_copy']);

            if (isset($form['gen_multi_edit'])) {
                // 一括編集モード
                $literal = _g("一括編集時は使用できません");
            } else if (isset($_SESSION['user_customer_id']) && $_SESSION['user_customer_id'] != '-1') {
                // EDIユーザー
                $literal = _g("使用できません");
            } else if ($isEditMode || (isset($form['gen_validError']) && $form['gen_validError'])) {
                // 修正モード or 新規モードのvalid error時
                $chatRecordId = ($isEditMode ? $form[$this->keyColumn] : -999);

                $query = "select chat_header_id from chat_header
                    where action_group = '{$form['gen_action_group']}' and record_id = '{$chatRecordId}'" . ($isEditMode ? "" : " and temp_user_id = '{$user_id}'");
                $chatHeaderId = $gen_db->queryOneValue($query);
                if ($chatHeaderId) {
                    // 未読件数
                    $query = "
                    select
                        coalesce(count(*),0) as total
                        ,coalesce(max(unread),0) as unread
                    from
                        chat_header
                        inner join chat_detail on chat_header.chat_header_id = chat_detail.chat_header_id
                        left join
                            (select
                                chat_detail.chat_header_id
                                ,count(*) as unread
                             from
                                chat_user
                                inner join chat_detail on chat_user.chat_header_id = chat_detail.chat_header_id
                                    and coalesce(chat_user.readed_chat_detail_id,-1) < chat_detail.chat_detail_id
                                    and chat_detail.user_id <> '{$user_id}'
                             where
                                chat_detail.chat_header_id = '{$chatHeaderId}'
                                and chat_user.user_id = '{$user_id}'
                             group by
                                chat_detail.chat_header_id
                            ) as t_unread
                            on  chat_header.chat_header_id = t_unread.chat_header_id
                    where
                        chat_header.chat_header_id = '{$chatHeaderId}'
                    ";
                    $chatUnreadObj = $gen_db->queryOneRowObject($query);
                    $literal = "<a href=\"javascript:parent.gen.chat.init('d', {$chatHeaderId}, '', '', '', '', '')\">" . sprintf(_g("%1\$s件（未読 %2\$s件）"),$chatUnreadObj->total ,$chatUnreadObj->unread) . "</a>";
                } else {
                    $needCreateScript = true;
                }
            } else {
                // 新規モード
                $chatRecordId = -999;      // 仮IDでアップロード。レコード登録時にIDを書き換える(ModelBase)
                $needCreateScript = true;

                // ちなみに添付ファイルの場合はこのタイミングで以前の仮登録状態の不要ファイル（新規レコードに対して作成したものの
                // レコード登録せずキャンセルした場合など）を清掃しているが、トークボードの場合はユーザーに見えてしまうため、
                // それでは遅い。そのため ListBase で削除処理を行っている。
            }
            if ($needCreateScript) {
                // スレッドタイトルは、ページタイトル + 乱数。確率的には既存スレッドのタイトルと重なる可能性はあるが、まあよしとする
                $chatTitle = (isset($form['gen_pageTitle']) ? $form['gen_pageTitle'] : "") . " " . mt_rand(1000000, 9999999);
                $literal = "<input type='button' value='" . _g("スレッド作成") . "' onclick=\"parent.gen.chat.createRecordChat('" . h($form['gen_action_group']) . "','" . h($chatRecordId) . "',{$user_id},'{$chatTitle}')\">";
            }

            // トークボード項目の作成
            $tbControl =
                array(
                    'label' => _g("トークボード"),
                    'type' => 'div',
                    'name' => "gen_record_chat_link",
                    'style' => 'line-height:25px',
                    'literal_noEscape' => $literal,
                    'helpText_noEscape' => _g("このレコードに関連付けられた専用スレッドを作成（作成済みの場合は表示）します。"),
                );

            // トークボード項目の挿入
            $insertPos = -1;
            foreach ($form["gen_editControlArray"] as $key => $ctl) {
                // tab, table, editlistが存在する場合、その手前に挿入する。
                if ($ctl['type'] == 'tab' || $ctl['type'] == 'table' || $ctl['type'] == 'list') {
                    $insertPos = $key;
                    break;
                }
            }
            if ($insertPos == -1) {
                $insertPos = count($form["gen_editControlArray"]);
            }
            array_splice($form["gen_editControlArray"], $insertPos, 0, array($tbControl));
        }

        //------------------------------------------------------
        // コントロール情報の読み出し
        //------------------------------------------------------

        $colInfoArr = $this->loadControls($form, $user_id, $form['gen_actionWithPageMode_noEscape']);

        //------------------------------------------------------
        // コントロールの並べ替え
        //------------------------------------------------------
        $this->sortControls($form, $colInfoArr, $user_id, $form['gen_actionWithPageMode_noEscape']);

        //------------------------------------------------------
        //  コピーモードではキーカラムコントロールのvalueを消去する。
        //------------------------------------------------------
        //  コピーモードではEntryにキーカラムを渡さないようにする必要がある。
        //  これは基本的にはこの上のほうで行っている　$form['gen_keyParamForUrl']の
        //  組み立て処理において、キーカラムをPOST URLに含めないことで対応している。
        //  しかしページによってはキーカラムの内容をそのまま画面に表示するため、データから読み出し
        //  ている場合が あり、（納品書画面で delivery_detail_id を納品書画面として表示して
        //  いるのがその典型例）、そのような場合にはキーカラムがPOSTされてしまう。
        //  それを避けるため、ここで消去処理を行う。
        //  ちなみにsetViewParam()の前に$formの内容を消去してもよいように思えるかもしれないが、
        //  そうするとEditListがあるページにおいて、このあとのEditListのデータ読出し処理で問題が起きる。
        //  ちなみにコピーモードでEditListがある場合、ここでページキーを消すだけでなく、明細行のキーも
        //  埋めこまないようにしなければならない。その処理はsmarty_function_gen_edit_controlの
        //  EditList書き出し部で行っている。

        if (isset($form['gen_record_copy'])) {
            foreach ($form['gen_editControlArray'] as &$ctl) {
                if (isset($ctl['name']) && $ctl['name'] === $this->keyColumn) {
                    $ctl['value'] = "";
                }
            }
        }

        //------------------------------------------------------
        // オーバーラップフレームの場合、専用パラメータをEntryに引き渡す
        //------------------------------------------------------

        if (isset($form['gen_entryAction'])) {
            $form['gen_entryAction'] .= (@$form['gen_overlapFrame'] == "true" ? "&gen_overlapFrame=true" : "") 
                . (@$form['gen_overlapCodeCol'] != "" ? "&gen_overlapCodeCol=" . $form['gen_overlapCodeCol'] : "")
                . (isset($form['gen_dropdownNewRecordButton']) ? "&gen_dropdownNewRecordButton" : "");
        }

        //------------------------------------------------------
        // EditListの処理
        //------------------------------------------------------
        // リスト行数変更
        if (isset($form['gen_editListId']) && $form['gen_editListId'] != ""
                && isset($form['gen_editListNumber']) && is_numeric($form['gen_editListNumber'])) {

            $numberOfListKey = $form['action'] . "_" . $form['gen_editListId'] . "_count";
            $_SESSION['gen_setting_user']->$numberOfListKey = $form['gen_editListNumber'];
            Gen_Setting::saveSetting();
        }

        // 明細カスタム項目フラグ
        if (isset($_SESSION['gen_setting_company']->customcolumnisdetail)) {
            $isDetailArr = $_SESSION['gen_setting_company']->customcolumnisdetail;
            if (is_object($isDetailArr)) {
                $isDetailArr = get_object_vars($isDetailArr);
            }
        }

        // データ取得
        $existEditList = false;
        if (isset($form['gen_editControlArray'])) {
            foreach ($form['gen_editControlArray'] as &$ctl) {
                // ----- EditList -----
                if (@$ctl['type'] == "list") {
                    $existEditList = true;

                    // データを配列に取得
                    $ctl['data'] = "";
                    if ($ctl['query'] != "") {
                        // カスタム項目
                        if (isset($form['gen_customColumnArray']) && isset($form['gen_customColumnDetailTable'])) {
                            $table = $form['gen_customColumnDetailTable'];
                            $selectPos = stripos($ctl['query'], 'select') + 6;
                            $originalQuery = $ctl['query'];
                            $ctl['query'] = substr($originalQuery, 0, $selectPos) . " ";
                            foreach($form['gen_customColumnArray'] as $customCol => $customArr) {
                                // $isDetailArrは上のほうで取得済み
                                if (isset($isDetailArr[$customArr[2]]) && $isDetailArr[$customArr[2]]) {
                                    // select listでワイルドカード指定されていたときのため、エイリアスをつけておく
                                    $ctl['query'] .= "{$table}.{$customCol} as gen_custom_{$customCol},";
                                }
                            }
                            $ctl['query'] .= substr($originalQuery, $selectPos + 1);
                        }
                        
                        // データ取得
                        $ctl['data'] = $gen_db->getArray($ctl['query']);

                        // multiEdit
                        if (isset($form['gen_multi_edit'])) {
                            // 値の読み出し。レコードによって値が異なる項目は [multi] とする。
                            reset($ctl['data'][0]);
                            $multiEditQuery = "select 1";
                            foreach ($ctl['data'][0] as $field => $value) {
                                $multiEditQuery .= ",";
                                $multiEditQuery .= "case when count(distinct coalesce(cast({$field} as text),'')) <= 1 then '{$value}' else '[multi]' end as {$field}";
                            }
                            $multiEditQuery .= " from ({$ctl['query']}) as t_gen_multi";

                            $ctl['data'] = $gen_db->getArray($multiEditQuery);
                        }
                        
                        // gen_editReload（項目の並び替え/追加削除や明細リストの行数変更など）のときはQueryで取得したデータではなく、POSTされた値を使用する。
                        if (isset($form['gen_editReload'])) {
                            $ctl['dataForReadOnlyCondition'] = $ctl['data'];
                            $ctl['data'] = "";
                        }
                    }

                    // リスト行数の決定（Settingより読み出し）
                    $numberOfListKey = $form['action'] . "_" . $ctl['listId'] . "_count";
                    if (isset($_SESSION['gen_setting_user']->$numberOfListKey)) {
                        $ctl['lineCount'] = $_SESSION['gen_setting_user']->$numberOfListKey;
                    } else {
                        // デフォルトは5行
                        $ctl['lineCount'] = 5;
                    }
                    // データが存在するとき（編集モード）は、データ行数を最小値とする。
                    if (count($ctl['data']) > $ctl['lineCount']) {
                        $ctl['lineCount'] = count($ctl['data']);
                    }

                    // キー項目の$formの値をdata配列に埋め戻し（エラー・項目変更・項目リセット・行数変更 時用）
                    $colName = $ctl['keyColumn'];
                    $colNameLen = strlen($colName);
                    foreach ($form as $key => $val) {
                        if (substr($key, 0, $colNameLen) == $colName && is_numeric($lineNo = substr($key, $colNameLen + 1))) {
                            // 値をdata配列に埋め戻す
                            if ($val != "") {
                                $ctl['data'][$lineNo - 1][$colName] = $val;
                                if (isset($form['gen_editReload'])) {
                                    $ctl['dataForReadOnlyCondition'][$lineNo - 1][$colName] = $val;
                                }
                            }
                        }
                    }

                    // リストコントロールごとの処理
                    $existDetail = isset($form['gen_customEditListControlArray']);
                    for ($loop=1; $loop<=($existDetail ? 2 : 1); $loop++) {
                        $loopCtl = ($loop==1 ? $ctl['controls'] : $form['gen_customEditListControlArray']);
                        foreach ($loopCtl as &$listCtl) {
                            // $formの値をdata配列に埋め戻し（エラー・項目変更・項目リセット・行数変更 時用）
                            if (isset($listCtl['name'])) {
                                if ($listCtl['type'] == "div") {
                                    $colName = "div_value_{$listCtl['name']}";
                                    $colNameParent = $listCtl['name'];
                                } else {
                                    $colName = $listCtl['name'];
                                }
                                $colNameLen = strlen($colName);
                                foreach ($form as $key => $val) {
                                    if (substr($key, 0, $colNameLen) == $colName && is_numeric($lineNo = substr($key, $colNameLen + 1))) {
                                        // セレクタについて、値がセレクタのデフォルト値（optionsの先頭）である場合、値なしとみなす。
                                        // 未入力の行が入力済みとみなされる（行数を減らしたのに行数が減らない）のを避けるため。
                                        if ($listCtl['type'] == "select") {
                                            list($optKey, $optVal) = each($listCtl['options']);
                                            if ($val == $optKey) {
                                                $val = "";
                                            }
                                            reset($listCtl['options']);
                                        }
                                        // divはhiddenの値をdivタグへ埋め戻す
                                        if ($listCtl['type'] == "div" && $val != "") {
                                            $ctl['data'][$lineNo - 1][$colNameParent] = $val;
                                            if (isset($form['gen_editReload'])) {
                                                $ctl['dataForReadOnlyCondition'][$lineNo - 1][$colNameParent] = $val;
                                            }
                                        }
                                        // 値をdata配列に埋め戻す
                                        if ($val != "") {
                                            $ctl['data'][$lineNo - 1][$colName] = $val;
                                            if (isset($form['gen_editReload'])) {
                                                $ctl['dataForReadOnlyCondition'][$lineNo - 1][$colName] = $val;
                                            }
                                        } else {
                                            // 先頭列が未入力の時でも行にデータが存在するかをチェックする。
                                            // （先頭列が参照用のDDである場合などにチェックする必要がある。）
                                            // データの存在が確認されるのは checkColumn で指定された列。
                                            if (isset($listCtl['checkColumn'])) {
                                                $checkColumn = @$form["{$listCtl['checkColumn']}_{$lineNo}"];
                                                if (isset($checkColumn) && $checkColumn != "") {
                                                    $ctl['data'][$lineNo - 1][$colName] = "";
                                                    if (isset($form['gen_editReload'])) {
                                                        $ctl['dataForReadOnlyCondition'][$lineNo - 1][$colName] = "";
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            // Dropdownのテキスト・サブテキストを取得して、data配列に格納しておく
                            if ($listCtl['type'] == "dropdown") {
                                // hasSubtextの設定（データの有無を問わず）
                                $dropdownHasSubtext = false;
                                $ddRes = Logic_Dropdown::getDropdownText($listCtl['dropdownCategory'], "");
                                $listCtl['dropdownHasSubtext'] = $ddRes['hasSubtext'];

                                // テキスト・サブテキストの設定（データがある行のみ）
                                if (is_array($ctl['data'])) {
                                    foreach ($ctl['data'] as &$dataRow) {
                                        if (isset($dataRow[$listCtl['name']])) {
                                            if ($dataRow[$listCtl['name']]=="[multi]") {
                                                $dataRow[$listCtl['name'] . '_dropdownShowtext'] = "[multi]";
                                                $dataRow[$listCtl['name'] . '_dropdownSubtext'] =  "[multi]";
                                                $dataRow[$listCtl['name'] . '_dropdownHasSubtext'] =  false;
                                            } else {
                                                $dropdownShowtext = "";
                                                $dropdownSubtext = "";
                                                $dropdownHasSubtext = false;

                                                // 表示値設定
                                                $ddRes = Logic_Dropdown::getDropdownText($listCtl['dropdownCategory'], $dataRow[$listCtl['name']]);

                                                $dataRow[$listCtl['name'] . '_dropdownShowtext'] = $ddRes['showtext'];
                                                $dataRow[$listCtl['name'] . '_dropdownSubtext'] = $ddRes['subtext'];
                                                $dataRow[$listCtl['name'] . '_dropdownHasSubtext'] = $ddRes['hasSubtext'];
                                            }
                                        }
                                    }
                                }
                                if (isset($form['gen_editReload'])) {
                                    if (isset($ctl['dataForReadOnlyCondition'])) {
                                        foreach ($ctl['dataForReadOnlyCondition'] as &$dataRow) {
                                            if (isset($dataRow[$listCtl['name']])) {
                                                if ($dataRow[$listCtl['name']]=="[multi]") {
                                                    $dataRow[$listCtl['name'] . '_dropdownShowtext'] = "[multi]";
                                                    $dataRow[$listCtl['name'] . '_dropdownSubtext'] =  "[multi]";
                                                    $dataRow[$listCtl['name'] . '_dropdownHasSubtext'] =  false;
                                                } else {
                                                    $dropdownShowtext = "";
                                                    $dropdownSubtext = "";
                                                    $dropdownHasSubtext = false;

                                                    // 表示値設定
                                                    $ddRes = Logic_Dropdown::getDropdownText($listCtl['dropdownCategory'], $dataRow[$listCtl['name']]);

                                                    $dataRow[$listCtl['name'] . '_dropdownShowtext'] = $ddRes['showtext'];
                                                    $dataRow[$listCtl['name'] . '_dropdownSubtext'] = $ddRes['subtext'];
                                                    $dataRow[$listCtl['name'] . '_dropdownHasSubtext'] = $ddRes['hasSubtext'];
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                // ----- EditTable -----
                if (@$ctl['type'] == "table") {
                    if (@$ctl['tableCount'] != 0) {
                        // テーブルコントロールごとの処理
                        foreach ($ctl['controls'] as &$tableCtl) {
                            if (isset($tableCtl['name']) && @$tableCtl['hidePin'] != true) {
                                for ($i = 1; $i <= $ctl['tableCount']; $i++) {
                                    $ctl["{$tableCtl['name']}_{$i}"] = @$form["{$tableCtl['name']}_{$i}"];
                                }
                            }
                            // Dropdownのテキスト・サブテキストを取得して、data配列に格納しておく
                            if (isset($tableCtl['name']) && $tableCtl['type'] == "dropdown") {
                                for ($i = 1; $i <= $ctl['tableCount']; $i++) {
                                    $name = "{$tableCtl['name']}_{$i}";
                                    $tableCtl["{$name}_dropdownShowtext"] = @$form["{$name}_show"];
                                    $tableCtl["{$name}_dropdownSubtext"] = @$form["{$name}_sub"];
                                }
                            }
                        }
                    } else {
                        // テーブルコントロールごとの処理
                        foreach ($ctl['controls'] as &$tableCtl) {
                            // Dropdownのテキスト・サブテキストを取得して、data配列に格納しておく
                            if ($tableCtl['type'] == "dropdown") {
                                // テキスト・サブテキストの設定（データがある行のみ）
                                if (isset($tableCtl['value'])) {
                                    $dropdownShowtext = "";
                                    $dropdownSubtext = "";
                                    $dropdownHasSubtext = false;
                                    // 表示値設定
                                    $ddRes = Logic_Dropdown::getDropdownText($tableCtl['dropdownCategory'], $tableCtl['value']);
                                    $tableCtl[$tableCtl['name'] . '_dropdownShowtext'] = $ddRes['showtext'];
                                    $tableCtl[$tableCtl['name'] . '_dropdownSubtext'] = $ddRes['subtext'];
                                    $tableCtl[$tableCtl['name'] . '_dropdownHasSubtext'] = $ddRes['hasSubtext'];
                                }
                            }
                        }
                    }
                }
            }
        }

        unset($listCtl);
        unset($tableCtl);
        unset($ctl);

        //------------------------------------------------------
        // クライアントバリデーションの処理
        //------------------------------------------------------
        if ($this->modelName != "") {
            // クライアントバリデーション配列を作成してtplへ引き渡す。
            // tpl冒頭のJS部にcheck functionsとして書き出されるほか、
            // plugin（gen_edit_control）へ渡され、各コントロールのonChangeでcheck functionを
            // 呼び出すように設定される。
            $model = new $this->modelName();

            // コントロールの配列を作る
            $ctlArr = array();
            $listCtlArr = array();
            if (isset($form['gen_editControlArray'])) {
                foreach ($form['gen_editControlArray'] as &$ctl) {  // 上のEditList処理部で参照処理しているので、$ctlに「&」が必要
                    if (@$ctl['type'] == "list") {
                        foreach ($ctl['controls'] as $listCtl) {
                            if (isset($listCtl['name'])) {
                                // 値はEditListの行数。クライアントバリデーションスクリプトの書き出しに必要。
                                // EditListが複数存在したときのため、コントロールごとに設定する。
                                $listCtlArr[$listCtl['name']] = $ctl['lineCount'];
                            }
                        }
                        // EditList内のカスタム項目は別途処理する必要がある
                        if (isset($form['gen_customEditListControlArray'])) {
                            foreach ($form['gen_customEditListControlArray'] as $listCtl) {
                                if (isset($listCtl['name'])) {
                                    $listCtlArr[$listCtl['name']] = $ctl['lineCount'];
                                }
                            }
                        }
                    } else {
                        if (isset($ctl['name'])) {
                            // 不自然な形だが、上のlistのケースに合わせてある
                            $ctlArr[$ctl['name']] = 0;
                        }
                    }
                }
            }
            // クライアントバリデーション配列を作成
            if (count($ctlArr) > 0) {
                $form['gen_clientValidArr'] = $model->getClientValid($ctlArr, false, "");
            }
            if (count($listCtlArr) > 0) {
                $form['gen_clientValidArr'] = array_merge($form['gen_clientValidArr'], $model->getClientValid($listCtlArr, true, ""));
            }
            unset($model);
//unset($form['gen_clientValidArr']);
//d($form['gen_clientValidArr']);
        }

        //------------------------------------------------------
        //  拡張Dropdownの処理（表示値設定と使用フラグ立て）
        //------------------------------------------------------
        // 拡張Dropdownを使用しているフィールドについて、子クラス側では
        // ['value']（例：品目ID）しか設定されていないが、tplでの表示時には
        // showtext（例：品目コード） と subtext（例：品目名）が必要。
        // ここで取得して変数に格納する。

        $arrCnt = count($form['gen_editControlArray']);
        for ($i = 0; $i < $arrCnt; $i++) {
            if (@$form['gen_editControlArray'][$i]['type'] == 'dropdown') {
                if ($form['gen_editControlArray'][$i]['value']=="[multi]") {
                    $form['gen_editControlArray'][$i]['dropdownShowtext'] = "[multi]";
                    $form['gen_editControlArray'][$i]['dropdownSubtext'] =  "[multi]";
                    $form['gen_editControlArray'][$i]['dropdownHasSubtext'] =  "[multi]";
                } else {
                    $dropdownShowtext = "";
                    $dropdownSubtext = "";
                    $dropdownHasSubtext = false;

                    // 表示値設定
                    $ddRes = Logic_Dropdown::getDropdownText($form['gen_editControlArray'][$i]['dropdownCategory'], $form['gen_editControlArray'][$i]['value']);
                    $form['gen_editControlArray'][$i]['dropdownShowtext'] = $ddRes['showtext'];
                    $form['gen_editControlArray'][$i]['dropdownSubtext'] = $ddRes['subtext'];
                    $form['gen_editControlArray'][$i]['dropdownHasSubtext'] = $ddRes['hasSubtext'];
                }
            }
        }

        //------------------------------------------------------
        // 最初にフォーカスを当てるエレメントを指定
        //------------------------------------------------------
        //  エレメントが指定されていない場合、最初の必須入力（require）エレメントを選択。
        if (isset($form['gen_editControlArray']) && (!isset($form['gen_focus_element_id']) || $form['gen_focus_element_id'] == "")) {
            $firstElement = false;
            foreach ($form['gen_editControlArray'] as $field) {
                if ((!isset($field['readonly']) || !$field['readonly']) && isset($field['name'])) {
                    if (!$firstElement) {
                        $firstElement = $field['name'];
                        if ($field['type'] == "dropdown") {
                            $firstElement .= "_show";
                        }
                    }
                    if (isset($field['require']) && $field['require'] && (!isset($field['readonly']) || !$field['readonly'])) {
                        $form['gen_focus_element_id'] = $field['name'];
                        if ($field['type'] == "dropdown") {
                            $form['gen_focus_element_id'] .= "_show";
                        }
                        break;
                    }
                }
            }
            // 必須入力エレメントがひとつもなかった場合は、最初のエレメント
            if (!isset($form['gen_focus_element_id']) || $form['gen_focus_element_id'] == "") {
                $form['gen_focus_element_id'] = $firstElement;
            }
        }

        //------------------------------------------------------
        // javascript
        //------------------------------------------------------
        // 表示高速化（ソースの軽量化）およびセキュリティ確保のため、
        // JavaScriptソース中のコメントおよび空白のみの行、行頭行末のブランクを削除する。

        if (isset($form['gen_javascript_noEscape'])) {
            $form['gen_javascript_noEscape'] = Gen_String::cutCommentAndBlankLine($form['gen_javascript_noEscape']);
        }

        //------------------------------------------------------
        // 帳票関連
        //------------------------------------------------------
        // gen_reportArray が設定されている場合、次の2つのボタンを表示する。

        if (isset($form['gen_reportArray'])) {
            //  「登録して印刷」ボタン：　新規モードと編集モード。登録後に印刷がおこなわれる。
            //      このパラメータは editmodal.tpl のPOSTで Entryクラスに渡される。
            //      印刷処理は次の場所でおこなわれる（EntryBase経由）。
            //          新規モードのとき：　次画面の editmodal.tplのonload
            //          修正モードのとき：   modalclose.tplを経由してgen_modal.js
            $form['gen_reportParamForEntry_noEscape'] =
                    "&gen_reportAction=" . $form['gen_reportArray']['action'] .
                    "&gen_reportParam=" . $form['gen_reportArray']['param'] .
                    "&gen_reportKeyColumn=" . $this->keyColumn .
                    "&gen_reportSeq=" . $form['gen_reportArray']['seq'];


            //  「帳票を印刷」ボタン：　　編集モードのみ。いま表示しているデータを印刷する。
            if (isset($form[$this->keyColumn]) && $form[$this->keyColumn] != '' && !isset($form['gen_record_copy'])) {
                // このパラメータは、editmodal.tpl のボタン表示部で参照される。
                // ボタンのonClickでこのactionがlocation.hrefされることにより、印刷がおこなわれる。
                $param = str_replace('[id]', $form[$this->keyColumn], $form['gen_reportArray']['param']);
                $form['gen_reportAction'] = $form['gen_reportArray']['action'] . "&" . $param;
            }
        }

        //--------------------------------
        //  添付ファイル
        //--------------------------------
        if ($isAttachableGroup) {
            $isEditMode = isset($form[$this->keyColumn]) && $form[$this->keyColumn] != '' && !isset($form['gen_record_copy']);

            if (isset($form['gen_multi_edit'])) {
                // 一括編集モード
            } else if ($isEditMode) {
                // 修正モード
                $form['gen_fileUploadRecordId'] = $form[$this->keyColumn];

                $query = "select file_name, original_file_name from upload_file_info
                    where action_group = '{$form['gen_action_group']}' and record_id = '{$form['gen_fileUploadRecordId']}'";
                $form['gen_uploadFiles'] = $gen_db->getArray($query);

                // DBに記録されているファイルが実際には存在しなかった場合、そのファイルのデータを削除しておく
                // （ファイルを削除後、DBをバックアップから復元するとこのような状況が発生する）
                if ($form['gen_uploadFiles']) {
                    $storage = new Gen_Storage("Files");
                    foreach ($form['gen_uploadFiles'] as $key => $row) {
                        if (!$storage->exist($row['file_name'])) {
                            unset($form['gen_uploadFiles'][$key]);
                            $name = $gen_db->quoteParam($row['file_name']);
                            $query = "delete from upload_file_info where file_name = '{$name}'";
                            $gen_db->query($query);
                        }
                    }
                }
            } else {
                // 新規モード
                $form['gen_fileUploadRecordId'] = -999;      // 仮IDでアップロード。レコード登録時にIDを書き換える(ModelBase)

                if (isset($form['gen_validError']) && $form['gen_validError']) {
                    // valid error時。仮ファイルを復元
                    $query = "select file_name, original_file_name from upload_file_info
                        where action_group = '{$form['gen_action_group']}' and temp_upload_user_id = '{$user_id}' and record_id = '-999'";
                    $form['gen_uploadFiles'] = $gen_db->getArray($query);

                } else {
                    // 仮IDのアップロードファイルが残っている場合（新規レコードに対してファイルをアップロードしたものの
                    // レコード登録せずキャンセルした場合など）、それを削除しておく。
                    // 別ユーザーの仮IDファイルは削除しない。現在登録中であるかもしれないので。
                    $query = "select file_name from upload_file_info
                        where temp_upload_user_id = '{$user_id}' and record_id = '-999'";
                    $tempUploadFileArr = $gen_db->getArray($query);
                    if ($tempUploadFileArr) {
                        $storage = new Gen_Storage("Files");
                        foreach($tempUploadFileArr as $tempUploadFile) {
                            $storage->delete($tempUploadFile['file_name']);
                        }
                        $query = "delete from upload_file_info where temp_upload_user_id = '{$user_id}' and record_id = '-999'";
                        $gen_db->query($query);
                    }
                }
            }
        }
 
        //------------------------------------------------------
        //  gen_app
        //------------------------------------------------------

        if ($_SESSION['gen_app']) {
            $appSectionArr = array();
            $appColArr = array();
            $appSectionNameArr = array();
            $appSectionName = _g("項目");
            foreach ($form['gen_editControlArray'] as $key => $col) {
                if (isset($col["type"]) && $col["type"] == "list") {
                    // list
                    if (count($appColArr) > 0) {
                        $appSectionArr[] = $appColArr;
                        $appSectionNameArr[] = $appSectionName;
                        $appColArr = array();
                    }
                    
                    foreach ($col["data"] as $listDataKey => $listDataRow) {
                        $appSectionName = sprintf(_g("明細 %s行目"), ($listDataKey + 1));
                        foreach ($col["controls"] as $listKey => $listRow) {
                            if (isset($listRow["name"])) {
                                if (isset($col["data"][$listDataKey][$listRow["name"]])) {
                                    $appColArr[] = array(
                                        "label" => $listRow["label"],
                                        "type" => $listRow["type"],
                                        "name" => $listRow["name"],
                                        "value" => $form['gen_editControlArray'][$key]["data"][$listDataKey][$listRow["name"]],
                                        );
                                }
                            }
                        }
                        if (count($appColArr) > 0) {
                            $appSectionArr[] = $appColArr;
                            $appSectionNameArr[] = $appSectionName;
                            $appColArr = array();
                        }
                    }
                } else if (isset($col["type"]) && ($col["type"] == "tab" || $col["type"] == "section")) {
                    if (count($appColArr) > 0) {
                        $appSectionArr[] = $appColArr;
                        $appSectionNameArr[] = $appSectionName;
                        $appColArr = array();
                    }
                    $appSectionName = ($col["type"] == "tab" ? $col["tabLabel"] : $col["label"]);
                } else if (!isset($col["label"]) || $col["label"] == "") {
                    continue;
                } else {
                    if (isset($col["type"]) && $col["type"] == "select") {
                        if (isset($col['selected']) && isset($col['options'])) {
                            if (isset($col["options"][$col["selected"]])) {
                                $col["value"] = $col["options"][$col["selected"]];
                            } else {
                                $col["value"] = $col['options'][0];
                            }
                        }
                    } else if (isset($col["type"]) && $col["type"] == "select_hour_minute") {
                        if (isset($col['hourSelected']) && is_numeric($col['hourSelected']) && isset($col['minuteSelected']) && is_numeric($col['minuteSelected'])) {
                            $col["value"] = $col["hourSelected"] . ":" . $col['minuteSelected'];
                        }
                    } else if (isset($col["type"]) && $col["type"] == "dropdown") {
                        $value = "";
                        if (isset($col['dropdownShowtext'])) {
                            $value = $col['dropdownShowtext'];
                        }
                        if (isset($col['dropdownSubtext']) && $col['dropdownSubtext'] != "") {
                            $value .= " : " . $col['dropdownSubtext'];
                        }
                        $col["value"] = $value;
                    }
                    foreach ($col as $colKey => $colVal) {
                        if ($colKey != "label" && $colKey != "type" && $colKey != "name" && $colKey != "value" && $colKey != "options") {
                            unset($col[$colKey]);
                        }
                    }
                    $appColArr[] = $col;
                }
            }
            if (count($appColArr) > 0) {
                $appSectionArr[] = $appColArr;
                $appSectionNameArr[] = $appSectionName;
            }
            
            // 品目画像（品目マスタのみ）
            if ($form['action'] == "Master_Item_Edit" && Gen_String::isNumeric($form['item_id'])) {
                $query = "select image_file_name from item_master where item_id = '{$form['item_id']}'";
                $imageFileName  = $gen_db->queryOneValue($query);
                if ($imageFileName) {
                    $itemImageArr = array("file_name" => $imageFileName, "original_file_name" => _g("品目画像"), "itemimage" => true);
                    if (isset($form['gen_uploadFiles']) && is_array($form['gen_uploadFiles'])) {
                        array_unshift($form['gen_uploadFiles'], $itemImageArr);
                    } else {
                        $form['gen_uploadFiles'] = array($itemImageArr);
                    }
                }
            }
            //d($appSectionNameArr, $appSectionArr);
            //d($form['gen_uploadFiles']);
            $form['response_noEscape'] = json_encode(array("sections" => $appSectionNameArr, "data" => $appSectionArr, "files" => @$form['gen_uploadFiles']));
            return 'simple.tpl';
        }

        //--------------------------------
        //  適用テンプレートの指定
        //--------------------------------

        if (isset($this->tpl)) {
            return $this->tpl;
        } else {
            // モーダル表示
            return "editmodal.tpl";
        }
    }

    //************************************************
    // SQL組み立て関連
    //************************************************
    // Where句の組み立て。
    //    第2引数 $fieldArray の内容が検索対象フィールドとして使用される。
    //    $fieldArray は、[フィールド名] => [タイプ] という形の連想配列。タイプは下記のコード参照。
    //    ただし $fieldArray にあっても、検索条件（$form['フィールド名']）がセットされていなければ
    //    検索対象として扱われない。
    //    少なくとも「where 1=1 」という文字列は返るので、ここで得られた文字列に「 and ・・」という
    //    形でwhere条件を追加してもよい。

    function makeWhere($form, $column)
    {
        $where = "where 1=1 ";    // 1=1 はダミー（常にand始まりにできるようにするため）
        if ($column != "") {
            if (isset($form[$column]) && $form[$column] != "") {
                if (isset($form['gen_multi_edit'])) {
                    $where .= " and {$column} in ({$form['gen_multiEditKeyString']})";
                } else {
                    $where .= " and $column = '" . $form[$column] . "'";
                }
            }
        }
        return $where;
    }

    // SQLにカスタム項目カラムを追加。

    private function _addCustomColumnToSelectQuery($form, $query)
    {
        if (isset($form['gen_customColumnArray'])) {
            $table = $form['gen_customColumnTable'];
            $selectPos = stripos($query, 'select') + 6;
            $originalQuery = $query;
            $query = substr($originalQuery, 0, $selectPos) . " ";
            $isDetailArr = array();
            if (isset($_SESSION['gen_setting_company']->customcolumnisdetail)) {
                $isDetailArr = $_SESSION['gen_setting_company']->customcolumnisdetail;
                if (is_object($isDetailArr)) {
                    $isDetailArr = get_object_vars($isDetailArr);
                }
            }
            foreach($form['gen_customColumnArray'] as $customCol => $customArr) {
                if (!isset($isDetailArr[$customArr[2]]) || !$isDetailArr[$customArr[2]]) {
                    // select listでワイルドカード指定されていたときのため、エイリアスをつけておく
                    $query .= "{$table}.{$customCol} as gen_custom_{$customCol},";
                }
            }
            $query .= substr($originalQuery, $selectPos + 1);
        }

        return $query;
    }

    //************************************************
    // コントロール関連
    //************************************************

    // コントロール情報の読み出し

    function loadControls(&$form, $user_id, $action)
    {
        global $gen_db;

        // コントロール並び順のリセット処理
        if (isset($form['gen_columnReset'])) {
            // コントロール並び順のリセット
            $gen_db->query("delete from control_info where user_id = '{$user_id}' and action = '{$action}'");
        }

        // コントロール並び順情報の読み出し
        $query = "select * from control_info where user_id = '{$user_id}' and action = '{$action}'";
        $colInfoArr = $gen_db->getArray($query);

        return $colInfoArr;
    }

    // コントロール情報の並べ替え

    function sortControls(&$form, &$colInfoArr, $user_id, $action)
    {
        global $gen_db;

        // ------------------------------------
        //  editControlArray へのキーの追加
        // ------------------------------------
        // 後の処理のため、editControlArray にキーを追加しておく
        //  2010iでは $arrnoをキーとしていたが、12iでlabel + name をdbサニタイズしてキーとする方式に変更。
        //  詳細は ListBase の sortColumns() の冒頭コメントを参照。
        $keyArr = array();
        foreach ($form['gen_editControlArray'] as $arrno => $col) {
            if ($col['type'] == 'list') {
                // Listの場合。listIdをキーとする
                $keySource = 'list_' . $form['gen_editControlArray'][$arrno]['listId'];
            } else {
                // 一般コントロールの場合
                $keySource = @$form['gen_editControlArray'][$arrno]['label'] . @$form['gen_editControlArray'][$arrno]['name'];
                // labelもnameもない場合は arrayのハッシュをとる。
                //   arrayが全く同じ場合はハッシュも同じになるが、その場合は最後に「1」をつけてユニークになるようにする。
                //   13iまでは array内の位置（$arrno）をキーにしていたが、それだと control array が変化したときに異なる種類の
                //   コントロールの位置が入れ替わってしまい、とくに tab がある場合などにレイアウトが崩れやすくなっていた。
                //   今回の方法であれば、少なくとも異なる type のコントロールが入れ替わることはなくなる。
                if ($keySource == "") {
                    $keySource = sha1(serialize($col));
                    while (in_array($keySource, $keyArr)) {
                        $keySource .= "1";
                    }
                    $keyArr[] = $keySource;
                }
            }
            $form['gen_editControlArray'][$arrno]['gen_key'] = $gen_db->quoteParam(urlencode($keySource));
        }

        // ------------------------------------
        //  コントロール情報（control_info）の新規作成
        // ------------------------------------
        // コントロール情報がない場合、新規作成する
        if (!is_array($colInfoArr)) {
            $colInfoArr = array();

            $colNum = 0;
            foreach ($form['gen_editControlArray'] as $arrno => $col) {
                $key = array("user_id" => $user_id, "action" => $action, "control_key" => $col['gen_key']);
                $data = array(
                    "control_key" => $col['gen_key'], // keyだが$controlInfoArrのために必要
                    "control_number" => $colNum,
                    "control_hide" => (isset($col['hide']) && $col['hide'] ? true : null),
                );
                $gen_db->updateOrInsert('control_info', $key, $data);
                $colInfoArr[] = $data;
                $colNum++;
            }
        }

        // ------------------------------------
        //  D&Dによる列の入れ替え処理
        // ------------------------------------
        // D&Dによるコントロールの入れ替え指示を受け取っている場合
        if (isset($form['gen_dd_num']) && is_numeric($form['gen_dd_num']) && isset($form['gen_ddtarget_num']) && is_numeric($form['gen_ddtarget_num'])) {
            // コントロール入れ替え処理（colInfoの更新）
            foreach ($colInfoArr as &$colInfo) {
                $num = $colInfo['control_number'];
                if ($form['gen_dd_num'] == $num) {
                    // 移動コントロール
                    $colInfo['control_number'] = $form['gen_ddtarget_num'];
                } else {
                    if ($form['gen_dd_num'] < $colInfo['control_number']) {
                        --$colInfo['control_number'];
                    }
                    if ($form['gen_ddtarget_num'] <= $colInfo['control_number']) {
                        ++$colInfo['control_number'];
                    }
                }
                // DB更新
                if ($num != $colInfo['control_number']) {
                    $key = array("user_id" => $user_id, "action" => $action, "control_key" => $colInfo['control_key']);
                    $data = array(
                        "control_number" => $colInfo['control_number'],
                    );
                    $gen_db->updateOrInsert('control_info', $key, $data);
                }
            }
            unset($colInfo);
        }

        // ------------------------------------
        //  コントロール情報（control_info）の更新
        // ------------------------------------
        //  Editクラスのプログラムが書き換えやカスタム項目設定により、コントロールの追加や削除があった場合、control_info もそれにあわせて更新する。
        //  D&Dによる列入れ替え処理よりも後に行う必要がある（列入れ替え指示は旧番号で送られてきているはずなので）

        //　追加処理
        foreach ($form['gen_editControlArray'] as $arrno => $col) {
            $isExist = false;
            foreach ($colInfoArr as $colInfo) {
                if ($colInfo['control_key'] == $col['gen_key']) {
                    $isExist = true;
                    break;
                }
            }

            if (!$isExist) {
                // col_infoのコントロール番号をずらす
                foreach ($colInfoArr as &$colInfo) {
                    if ($arrno <= $colInfo['control_number']) {
                        $colInfo['control_number']++;
                    }
                }
                unset($colInfo);

                $data = array(
                    'control_number' => 'noquote:control_number+1',
                );
                $where = "user_id = {$user_id} and action = '{$action}' and {$arrno} <= control_number";
                $gen_db->update('control_info', $data, $where);

                // col_infoにコントロール追加
                $data = array(
                    "control_key" => $col['gen_key'],
                    "control_number" => $arrno,
                    "control_hide" => (isset($col['hide']) && $col['hide'] ? true : null),
                );
                $colInfoArr[] = $data;

                $key = array("user_id" => $user_id, "action" => $action, "control_key" => $col['gen_key']);
                $gen_db->updateOrInsert('control_info', $key, $data);
            }
        }

        // 削除処理
        $delKeyArr = array();
        foreach ($colInfoArr as $colInfo) {
            $isExist = false;
            $key = $colInfo['control_key'];
            foreach ($form['gen_editControlArray'] as $arrno => $col) {
                if ($key == $col['gen_key']) {
                    $isExist = true;
                    break;
                }
            }
            if (!$isExist) {
                $delKeyArr[] = $key;
            }
        }
        if (count($delKeyArr) > 0) {
            foreach ($delKeyArr as $delKey) {
                foreach ($colInfoArr as $colInfoArrKey => $colInfo) {
                    $key = $colInfo['control_key'];
                    $num = $colInfo['control_number'];

                    if ($key == $delKey) {
                        foreach ($colInfoArr as &$colInfo2) {
                            $num2 = $colInfo2['control_number'];
                            if ($num <= $num2) {
                                $colInfo2['control_number'] = ($num2 - 1);
                            }
                        }
                        unset($colInfoArr[$colInfoArrKey]);
                        unset($colInfo2);

                        // DB更新
                        $data = array(
                            'control_number' => 'noquote:control_number-1',
                        );
                        $where = "user_id = {$user_id} and action = '{$action}' and " .
                                "$num <= control_number";
                        $gen_db->update('control_info', $data, $where);

                        $query = "delete from control_info where user_id = '{$user_id}' and action = '{$action}' and control_key='{$key}'";
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
        $colNum = 0;
        foreach ($form['gen_editControlArray'] as $arrno => $col) {
            if (isset($col['denyMove']) && $col['denyMove']) {
                foreach($colInfoArr as $colInfoArrKey => $colInfo) {
                    if ($colInfo['control_key'] == $col['gen_key']) {
                        if ($colInfo['control_number'] != $colNum) {
                            if ($colInfo['control_number'] < $colNum) {
                                foreach($colInfoArr as &$colInfo2) {
                                    if ($colInfo2['control_number'] > $colInfo['control_number'] && $colInfo2['control_number'] <= $colNum) {
                                        --$colInfo2['control_number'];
                                    }
                                }
                                $data = array(
                                    'control_number' => 'noquote:control_number-1',
                                );
                                $where = "user_id = '{$user_id}' and action = '{$action}' and " .
                                    "control_number > {$colInfo['control_number']} and control_number <= {$colNum}";
                                $gen_db->update('control_info', $data, $where);
                            } else {
                                foreach($colInfoArr as &$colInfo2) {
                                    if ($colInfo2['control_number'] < $colInfo['control_number'] && $colInfo2['control_number'] >= $colNum) {
                                        ++$colInfo2['control_number'];
                                    }
                                }
                                $data = array(
                                    'control_number' => 'noquote:control_number+1',
                                );
                                $where = "user_id = '{$user_id}' and action = '{$action}' and " .
                                    "control_number < {$colInfo['control_number']} and control_number >= {$colNum}";
                                $gen_db->update('control_info', $data, $where);
                            }
                            unset($colInfo2);
                            $colInfoArr[$colInfoArrKey]['control_number'] = $colNum;
                        }
                        break;
                    }
                }
            }
            ++$colNum;
        }

        // ------------------------------------
        //  コントロールの並べ替え
        // ------------------------------------
        // コントロールの並べ替えの準備 （各列にコントロール番号を追加。並べ替えのキーとなる配列を作成。）
        unset($colInfo);
        $gen_num_col = array();
        foreach ($form['gen_editControlArray'] as $arrno => $col) {
            foreach ($colInfoArr as $colInfo) {
                if ($colInfo['control_key'] == $col['gen_key']) {
                    $num = $colInfo['control_number'];
                    $form['gen_editControlArray'][$arrno]['gen_num'] = $num;
                    $form['gen_editControlArray'][$arrno]['hide'] = ($colInfo['control_hide'] === true || $colInfo['control_hide'] === 't');
                    if ($colInfo['control_hide'] === true || $colInfo['control_hide'] === 't')
                        $form['gen_editControlArray'][$arrno]['tabindex'] = "-1";
                    $gen_num_col[] = $num;
                }
            }
        }

        // コントロールの並べ替え
        array_multisort($gen_num_col, SORT_ASC, SORT_NUMERIC, $form['gen_editControlArray']);
    }

    // カスタム項目コントロールの挿入
    private function _insertCustomColumn(&$form)
    {
        if (isset($form['gen_customColumnArray'])) {
            self::_insertCustomColumnSub($form, false);
            if (isset($form['gen_customColumnDetailTable'])) {
                self::_insertCustomColumnSub($form, true);
            }
        }
    }

    private function _insertCustomColumnSub(&$form, $isDetail)
    {
        $customControlArr = array();

        // 明細フラグの取得
        if (isset($_SESSION['gen_setting_company']->customcolumnisdetail)) {
            $isDetailArr = $_SESSION['gen_setting_company']->customcolumnisdetail;
            if (is_object($isDetailArr)) {
                $isDetailArr = get_object_vars($isDetailArr);
            }
        }

        // カスタム項目コントロールの作成
        foreach($form['gen_customColumnArray'] as $customCol => $customArr) {
            $thisColumnIsDetail = isset($isDetailArr[$customArr[2]]) && $isDetailArr[$customArr[2]];

            if (($isDetail && $thisColumnIsDetail) || (!$isDetail && !$thisColumnIsDetail)) {
                $customMode = $customArr[0];
                $customName = $customArr[1];
                $customColumnName = $customArr[2];
                list($type, $options) = Logic_CustomColumn::getCustomElementTypeAndOptions($customColumnName, $customMode);

                $size = ($isDetail ? '8' : '10');
                if ($customMode == 0) {   // 文字型のときはサイズを大きく
                    $size = ($isDetail ? '15' : '20');
                }

                $customControl =
                    array(
                        'label' => $customName,
                        'type' => $type,
                        'name' => "gen_custom_{$customCol}",
                        'size' => $size,
                        'options' => (isset($options) ? $options : null),
                    );
                if ($isDetail) {
                    if ($type === "calendar") {
                        $customControl['hideSubButton'] = true;
                    }
                } else {
                    if (isset($form["gen_custom_{$customCol}"])) {
                        $customControl['value'] = $form["gen_custom_{$customCol}"];
                        $customControl['selected'] = $form["gen_custom_{$customCol}"];
                    }
                }
                $customControlArr[] = $customControl;

            }
        }

        // カスタム項目の挿入
        if ($isDetail) {
            $form['gen_customEditListControlArray'] = $customControlArr;
        } else {
            $insertPos = -1;
            foreach ($form["gen_editControlArray"] as $key => $ctl) {
                // tab, table, editlistが存在する場合、その手前にカスタム項目を挿入する。
                if ($ctl['type'] == 'tab' || $ctl['type'] == 'table' || $ctl['type'] == 'list') {
                    $insertPos = $key;
                    break;
                }
            }
            if ($insertPos == -1) {
                $insertPos = count($form["gen_editControlArray"]);
            }
            array_splice($form["gen_editControlArray"], $insertPos, 0, $customControlArr);
        }
    }

    //************************************************
    // Excel関連
    //************************************************

    private function _excelOperation($form)
    {
        $this->setViewParam($form);

        // 項目リストを作る。
        // このリストの順序（Edit画面のコントロールの並びと同じ）が、エクセル上での列の順序になる。
        // 先頭にID列を作る
        $controlArr = array();
        $controlArr[] = array(
            "name" => $this->keyColumn,
            "label" => "ID",
            "type" => "id",
            "dropdownCategory" => null,
            "options" => null,
            "require" => true,
            "helpText_noEscape" => null,
        );
        self::_insertCustomColumn($form);

        foreach ($form['gen_editControlArray'] as $col) {
            if (!isset($col['name']) || (isset($col['readonly']) && $col['readonly']))
                continue;
            $controlArr[] = array(
                "name" => $col['name'],
                "label" => (isset($col['label']) ? $col['label'] : $col['label_noEscape']),
                "type" => $col['type'],
                "dropdownCategory" => @$col['dropdownCategory'],
                "options" => @$col['options'],
                "require" => @$col['require'],
                "helpText_noEscape" => @$col['helpText_noEscape'],
            );
        }

        if (isset($_REQUEST['gen_mode']) && $_REQUEST['gen_mode'] == "write") {
            return $this->_excelWrite($controlArr, $form);
        } else {
            return $this->_excelRead($controlArr, $form);
        }
    }

    // エクセルへ送る読み取りデータを準備
    private function _excelRead($controlArr, $form)
    {
        global $gen_db;

        $data = "";
        $dataCountPerPage = 100;    // 1ページの件数（1回の通信でこの件数分ずつ送信する）

        if (!Gen_String::isNumeric($form['offset']))
            return;
        if (!Gen_String::isNumeric($form['limit']))
            return;

        $page = 1;
        if (isset($form['page']) && is_numeric($form['page']))
            $page = $form['page'];

        if ($page == 1) {
            // タイトル行
            foreach ($controlArr as $col) {
                // htmlタグは削除
                $data .= strip_tags($col['label']) . "[xx]";
            }
            $data .= "[yy]";

            // タイプ行（コントロールタイプ;必須項目フラグ;チップヘルプ）
            foreach ($controlArr as $col) {
                // コントロールタイプ
                switch ($col['type']) {
                    case "dropdown":
                        // 拡張DDの場合、タイプの後ろに dropdownCategoryを追加
                        $data .= $col['type'];
                        $data .= "[sep]" . $col['dropdownCategory'];
                        break;
                    case "select":
                        // セレクタの場合、タイプの後ろに選択肢を追加
                        $data .= $col['type'];
                        foreach ($col['options'] as $key => $val) {
                            $data .= "[sep]" . $val;
                        }
                        break;
                }
                $data .= ";";
                // 必須項目フラグ
                if (isset($col['require']) && $col['require']) {
                    $data .= "1";   // 必須
                }
                $data .= ";";
                // チップヘルプ
                if (isset($col['helpText_noEscape'])) {
                    $data .= strip_tags(str_replace("<BR>", "\n", str_replace("<br>", "\n", $col['helpText_noEscape'])));
                }
                $data .= "[xx]";
            }
            $data .= "[yy]";

            // オプション行
            foreach ($controlArr as $col) {
                switch ($col['type']) {
                    case "dropdown":
                    case "select":
                        $data .= _g("ダブルクリックで選択");
                        break;
                }
                $data .= "[xx]";
            }
            $data .= "[yy]";
        }

        // 絞り込みの処理
        $where = "";
        if (isset($form['searchColumn']) && is_numeric($form['searchColumn']) && $form['searchColumn'] >= 0 && isset($form['search'])) {
            $col = $controlArr[$form['searchColumn']];
            $colData = $form['search'];
            $likeMode = isset($form['searchMethod']) && $form['searchMethod'] == "1";
            $whereOption = '';
            switch ($col['type']) {
                case "dropdown":
                    // 拡張DD
                    //  表示値をidに変換
                    if ($likeMode) {
                        $res = Logic_Dropdown::getDropdownData(
                            $col['dropdownCategory'],   //    $category         カテゴリ
                            @$col['dropdownParam'],     //    $param            パラメータ。カテゴリによっては必要
                            0,                          //    $offset           何件目から表示するか
                            "",                         //    $source_control   拡張DropdownテキストボックスコントロールのID
                            $colData,                   //    $search           検索条件
                            "",                         //    $matchBox         検索マッチボックス
                            "",                         //    $selecterSearch   絞り込みセレクタの値
                            true                        //    $selecterSearch2  絞り込みセレクタ2の値
                        );
                        if (is_array($res['data']) && count($res['data']) > 0) {
                            $idCsv = '';
                            foreach ($res['data'] as $row) {
                                $id = $row['id'];
                                if ($idCsv != '')
                                    $idCsv .=",";
                                $idCsv .= "'{$id}'";
                            }
                            if ($idCsv == '') {
                                $whereOption = " = 'zzzzzzzzzz'";
                            } else {
                                $whereOption = " in ({$idCsv}) ";
                            }
                        } else {
                            $whereOption = " = 'zzzzzzzzz'";
                        }
                    } else {
                        Logic_Dropdown::dropdownCodeToId($col['dropdownCategory'], $colData, null, '', false, $id, $idConvert, $dummy1, $dummy2, $dummy3);
                        $colData = $id;
                        $whereOption = " = '{$colData}' ";
                    }
                    break;
                case "select":
                    // セレクタ
                    //  表示値をidに変換
                    if ($likeMode) {
                        $idCsv = '';
                        foreach ($col['options'] as $optkey => $optval) {
                            if (strpos($optval, $colData) !== FALSE) {
                                if ($idCsv != '')
                                    $idCsv .=",";
                                $idCsv .= "'{$optkey}'";
                            }
                        }
                        if ($idCsv == '') {
                            $whereOption = " ='' and 1=0";
                        } else {
                            $whereOption = " in ({$idCsv}) ";
                        }
                    } else {
                        if (($key = array_search($colData, $col['options'])) !== FALSE) {
                            $colData = $key;
                        }
                        $whereOption = " = '{$colData}' ";
                    }
                    break;
                default:
                    // 上記以外
                    // 未指定のときは完全一致とする。部分一致だとかなり遅くなることがある（例：品目マスタ）
                    if ($likeMode) {
                        // エスケープ
                        //    like では「_」「%」がワイルドカードとして扱われる
                        $word = str_replace('%', '\\\\%', str_replace('_', '\\\\_', $colData));
                        $whereOption = " like '%{$word}%' ";
                    } else {
                        $whereOption = " = '{$colData}' ";
                    }
            }
            $where .= " where cast({$col['name']} as text) {$whereOption}";
        }

        // キー指定がない画面（例：barcodeEdit）は、エクセルで扱うことができない。
        if ($this->keyColumn == "" || $this->selectQuery == "") {
            return "keyColumn もしくは selectQuery が指定されていません。キー指定がない画面（例：barcodeEdit）は、エクセルで扱うことができません。";
        }

        // データ取得
        // id列を追加
        $query = trim(strtolower($this->selectQuery));
        if (substr($query, 0, 6) != "select") {
            return "SQLの先頭がselectではありません。";
        }
        // SQL組立
        $query = self::_addCustomColumnToSelectQuery($form, $query);
        $query = "select " . $this->keyColumn . " as gen_key, * from (" . str_replace('[where]', '', $query) . ") as t_gen_excel_select {$where}";
        // offset処理とlimitチェック
        $offset = $form['offset'] - 1 + ($page - 1) * $dataCountPerPage;
        $limit = $dataCountPerPage;
        if ($offset + $limit > $form['offset'] - 1 + $form['limit'])
            $limit = $form['offset'] - 1 + $form['limit'] - $offset;
        if ($limit < 0)
            $limit = 0;
        $query .= " offset {$offset} limit {$limit}";
        $resource = $gen_db->query($query);

        // データ行
        while ($row = $gen_db->fetchObject($resource, null)) {
            foreach ($controlArr as $col) {
                if (isset($row->$col['name'])) {
                    $colData = $row->$col['name'];
                    switch ($col['type']) {
                        case "dropdown":
                            // 拡張DDの場合は、idをshowtextに変換
                            if ($col['type'] == 'dropdown') {
                                $res = Logic_Dropdown::getDropdownText($col['dropdownCategory'], $colData);
                                $colData = $res['showtext'];
                            }
                            break;
                        case "select":
                            // セレクタの場合は、idを表示値に変換
                            if ($colData !== null && isset($col['options'][$colData])) {
                                $colData = $col['options'][$colData];
                            } else {
                                // セレクタの選択肢にない値が登録されている場合は、とりあえず最初の選択肢の値を（画面での動きにあわせて）
                                $colData = array_shift($col['options']);
                            }
                            break;
                    }
                } else {
                    // controlArrayにはあるがSQLにない項目（Ajaxで読み出す項目など）
                    $colData = '';
                }
                $data .= $colData . "[xx]";
            }
            $data .= "[yy]";
        }

        return 'success:' . $data;
    }

    //　エクセルから送られてきたデータを登録
    private function _excelWrite($controlArr, $form)
    {
        global $gen_db;

        // Excelから書きこむ際は、事前に取得したトークンを添付しなければならない。
        // これはCSRF対策のため。攻撃者が登録用URLをログイン状態のユーザーに踏ませることにより、
        // 登録処理が行えてしまう脆弱性を避ける。
        if (isset($form['gen_getToken'])) {
            return self::_createExcelWriteToken();
        } else {
            if (!self::_checkExcelWriteToken(@$form['gen_excelWriteToken'])) {
                return 'Token error';
            }
        }

        if ($form['gen_readonly'] == 'true') {
            return 'Permission Error';
        }

        $arr = explode("[yy]", $form['data']);
        $totalCount = 0;
        $errorCount = 0;
        $errorMsg = "";

        $gen_db->begin();
        foreach ($arr as $row) {
            if ($row == "")
                continue;

            $colarr = explode("[xx]", $row);
            $index = 0;
            $param = array();
            foreach ($controlArr as $col) {
                $colData = $colarr[$index++];
                switch ($col['type']) {
                    case "dropdown":
                        // 拡張DDの場合は、表示値をidに変換
                        Logic_Dropdown::dropdownCodeToId($col['dropdownCategory'], $colData, null, '', false, $id, $idConvert, $dummy1, $dummy2, $dummy3);
                        $colData = $id;
                        break;
                    case "select":
                        // セレクタの場合は、表示値をidに変換
                        if (($key = array_search($colData, $col['options'])) !== FALSE) {
                            $colData = $key;
                        } else {
                            // セレクタの選択肢にない値が登録されている場合はそのまま（表示値でもID値でも登録できるように）
                        }
                        break;
                }
                // ちなみに、機種依存文字はエクセル側でチェック済み
                $param[$col['name']] = $colData;
            }

            // model
            $model = new $this->modelName();
            $model->setDefault($param, "excel");
            if (method_exists($model, "beforeLogic")) {
                $model->beforeLogic($param);
            }

            // convert
            $converter = new Gen_Converter($param);
            $model->convert($converter);

            // validate
            $validator = new Gen_Validator($param);
            $model->validate($validator, $param);

            if ($validator->hasError()) {
                foreach ($validator->errorList as $errNum => $errMsg) {
                    // エラー項目名を列番号に変換
                    $errColNum = "";
                    foreach ($controlArr as $colNum => $col) {
                        if ($col['name'] == $validator->errorParamList[$errNum]) {
                            $errColNum = $colNum;
                        }
                    }
                    $errorMsg .= $totalCount . "[xx]" . $errColNum . "[xx]" . $errMsg . "[yy]";
                }
                $errorCount++;
            } else {
                // regist
                $model->regist($param);
            }
            unset($model);
            unset($converter);
            unset($validator);

            $totalCount++;
        }

        // 1行でもエラーになったら全体を失敗させる
        if ($errorCount > 0) {
            $gen_db->rollback();
            return $errorMsg;
        } else {
            $gen_db->commit();
            return 'success:';
        }
    }

    // エクセル登録用のワンタイムトークンを発行
    private function _createExcelWriteToken()
    {
        return ($_SESSION["excel_write_token"] = Gen_Auth::_makeRandomString(10));
    }

    // エクセル登録用のワンタイムトークンのチェック
    private function _checkExcelWriteToken($token)
    {
        return ($_SESSION["excel_write_token"] === $token);
    }

}
