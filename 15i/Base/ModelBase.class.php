<?php

// 抽象クラス（abstract）。インスタンスを生成できない。

abstract class Base_ModelBase
{

    var $listAction;
    var $modelName;
    var $headerArray;
    var $detailArray;
    var $lineCount;
    var $log1;
    var $log2;
    private $columns;
    private $keyColumn;
    private $detailKeyColumn;

    //************************************************
    // 抽象メソッド
    //************************************************
    // 子クラスで必ず実装しなければならない。

    abstract protected function _getKeyColumn();

    abstract protected function _setDefault(&$param, $entryMode);

    abstract protected function _getColumns();

    abstract protected function _regist(&$param, $isFirstRegist);

    //************************************************
    // Constructor
    //************************************************

    final function __construct()
    {
        global $form;
        
        $this->keyColumn = $this->_getKeyColumn();
        if (is_array($this->keyColumn)) {
            $this->detailKeyColumn = $this->keyColumn[1];
            $this->keyColumn = $this->keyColumn[0];
        }
        $this->columns = $this->_getColumns();
        $this->_columnPattern();
        
        // カスタム項目
        if (isset($form['gen_customColumnArray'])) {
            foreach($form['gen_customColumnArray'] as $customCol => $customArr) {
                $customMode = $customArr[0];
                $customName = $customArr[1];
                switch ($customMode) {
                    case 0: // 文字
                        break;
                    case 1: // 数値
                        $this->columns[] = array(
                            "column" => "gen_custom_" . $customCol,
                            "convert" => array(
                                array(
                                    "cat" => "strToNum",
                                ),
                            ),
                            "skipValidatePHP" => "$1==''", // ブランクOK。=== にするとクイック登録でエラー
                            "skipValidateJS" => "$1===''",
                            "validate" => array(
                                array(
                                    "cat" => "numeric",
                                    "skipHasError" => true,
                                    "msg" => sprintf(_g("%sには数値を入力してください。") ,$customName),
                                ),
                            ),
                        );
                        break;
                    case 2: // 日付
                        $this->columns[] = array(
                            "column" => "gen_custom_" . $customCol,
                            "skipValidatePHP" => "$1==''", // ブランクOK。=== にするとクイック登録でエラー
                            "skipValidateJS" => "$1===''",
                            // 以前はsystemDateOrLaterのチェックを行っていたが廃止。ag.cgi?page=ProjectDocView&pid=1574&did=231616
                            "validate" => array(
                                array(
                                    "cat" => "blankOrDateString",
                                    "msg" => sprintf(_g("%sには日付を入力してください。") ,$customName),
                                ),
                            ),
                        );
                        break;
                }
            }
        }
    }

    //************************************************
    // Public Method
    //************************************************

    final function isNew($form)
    {
        if ($this->keyColumn === true || $this->keyColumn === false) {
            return $this->keyColumn;
        }
        return !isset($form[$this->keyColumn]) || $form[$this->keyColumn] == "";
    }

    final function setDefault(&$param, $entryMode)
    {
        $this->_setDefault($param, $entryMode);
    }

    final function convert($converter, $suffix = "")
    {
        // カラムレベル
        foreach ($this->columns as $col) {
            if (!isset($col['convert']))
                continue;

            $column = $col['column'] . $suffix;

            // コンバータレベル
            foreach ($col['convert'] as $c) {
                if (isset($c['param'])) {
                    if (is_array($c['param'])) {
                        array_unshift($c['param'], $column);
                        call_user_func_array(array($converter, $c['cat']), $c['param']);
                    } else {
                        $converter->$c['cat']($column, $c['param']);
                    }
                } else {
                    $converter->$c['cat']($column);
                }
            }
        }
    }

    // Column配列を元に、サーバー側バリデーションをおこなう。
    final function validate($validator, $form, $suffix = "")
    {
        global $gen_db;

        // カラムレベル
        foreach ($this->columns as $col) {
            if (!isset($col['validate']))
                continue;

            // skipValidateの処理（カラムレベル）
            if (isset($col['skipValidatePHP'])) {
                if (self::_replaceEval("return ({$col['skipValidatePHP']});", $col['column'], $form, $suffix))
                    continue;
            }

            // チェックするカラム
            $columnWithSuffix = $col['column'] . $suffix;

            // lockNumberの処理。
            // ユニークであることが要求されるカラムに対し、複数クライアントが同じ値を同時に登録しようとした場合の競合を回避する。
            // 該当カラムに別トランザクションが同じ値を登録しようとした場合、ここで足止めされる。くわしくは Logic_NumberTable::lockNumberのコメント参照。
            // オーダー番号のように「ユーザー指定できるが全体としてユニークでなければならない」値にはこの処理が必要。
            // また、当然ながらこの処理を行った場合はValidatorで番号の重複チェックが必要。
            // また、この処理を行った場合は、登録処理全体の終了時に必ずunlockNumber()が必要だが、その処理はこのModelBase
            // のregist()の中で行われる（各Modelで行う必要はない）
            if (isset($col['lockNumber']) && $col['lockNumber'] && isset($form[$columnWithSuffix])) {
                $lockVal = $form[$columnWithSuffix];
                $lockName = $col['column'];  // $suffixはつけないことに注意
                if (!isset($gen_db->lockNumberArray[$lockName])) {
                    $gen_db->lockNumberArray[$lockName] = array();
                }
                // 同じ番号は再ロックしないようにする（複数回登録はありえないはずだが一応チェック）
                if (!in_array($lockVal, $gen_db->lockNumberArray[$lockName])) {
                    //　ロック処理
                    Logic_NumberTable::lockNumber($lockName, $lockVal);
                    $gen_db->lockNumberArray[$lockName][] = $lockVal;
                }
            }

            // バリデーションレベル
            foreach ($col['validate'] as $v) {
                if (isset($v['skipHasError']) && $v['skipHasError'] && $validator->hasError())
                    continue;

                // skipValidateの処理（バリデーションレベル）
                if (isset($v['skipValidatePHP'])) {
                    if (self::_replaceEval("return (" . $v['skipValidatePHP'] . ");", $col['column'], $form, $suffix))
                        continue;
                }

                if ($v['cat'] == "eval") {
                    $evalStr = $v['evalPHP'];
                    if ($evalStr == "")
                        throw new Exception("{$col['column'][0]} のevalPHPが指定されていません。");
                    if (!self::_replaceEval($evalStr, $col['column'], $form, $suffix)) {
                        $validator->raiseError($v['msg']);
                    }
                } else {
                    if (isset($v['param'])) {
                        if (is_array($v['param'])) {
                            $arr = $v['param'];
                            // existRecord系のSQL内のダブルブラケット置換。
                            // validator内でもおこなっているが、行番号（suffix）の問題があるのでここで行う。
                            foreach ($arr as $no => $row) {
                                if (!is_array($arr[$no]))
                                    $arr[$no] = Gen_String::bracketToForm($arr[$no], $form, $suffix);
                            }
                            array_unshift($arr, $columnWithSuffix, $v['msg']);
                            call_user_func_array(array($validator, $v['cat']), $arr);
                        } else {
                            // existRecord系のSQL内のダブルブラケット置換。
                            // validator内でもおこなっているが、行番号（suffix）の問題があるのでここで行う。
                            $v['param'] = Gen_String::bracketToForm($v['param'], $form, $suffix);
                            $validator->$v['cat']($columnWithSuffix, $v['msg'], $v['param']);
                        }
                    } else {
                        $validator->$v['cat']($columnWithSuffix, $v['msg']);
                    }
                }
//var_dump($v['cat'].":".$columnWithSuffix.":".$form[$columnWithSuffix].":".$v['param']);
            }
        }
    }

    // 指定されたControl配列とこのModelのColumn配列を元に、クライアントバリデーション用のスクリプト配列を作成する。
    final function getClientValid($controls, $isInList, $suffix = "")
    {
        // 効率化のため、converter/validatorは必要なときだけ作成するようにする
        $converter = null;
        $validator = null;
        $clientValidArr = array();

        // バリデーションスクリプト作成処理
        // カラムレベルループ
        foreach ($this->columns as $col) {
            if (!isset($col['validate']))
                continue;

            // 引数として渡された$controls配列の中にキーがあるカラムだけが処理対象となる。
            if (!isset($controls[$col['column']]))
                continue;

            $columnWithSuffix = $col['column'] . $suffix;
            $checkScript = "";

            // バリデーションレベルループ
            foreach ($col['validate'] as $v) {
                if (!isset($v['cat'])) {
                    throw new Exception($col['column'] . "の validate に cat が指定されていません。");
                }

                // バリデータ existRecordは未実装。実装したらこのチェックをはずす
                if ($v['cat'] == "existRecord" || $v['cat'] == "notExistRecord")
                    continue;
                $v['msg'] = htmlspecialchars($v['msg'], ENT_QUOTES);

                $validScript = "";
                if ($v['cat'] == "systemDateOrLater" || $v['cat'] == "salesLockDateOrLater" || $v['cat'] == "buyLockDateOrLater") {
                    // これらのバリデータはエラーメッセージをここで組み立てなければならないので、
                    // 特別扱いする
                    switch ($v['cat']) {
                        case "salesLockDateOrLater":
                            $sysDate = date('Y-m-d', Logic_SystemDate::getSalesLockDate());
                            break;
                        case "buyLockDateOrLater":
                            $sysDate = date('Y-m-d', Logic_SystemDate::getBuyLockDate());
                            break;
                        default:
                            $sysDate = date('Y-m-d', Logic_SystemDate::getStartDate());
                            break;
                    }
                    $validScript .= "var msg='';if(!gen.date.isDate(val)){msg='" . sprintf(_g("%sが正しくありません。"), $v['msg'])
                            . "'}else if(gen.date.parseDateStr(val)<gen.date.parseDateStr('$sysDate')){msg='"
                            . sprintf(_g("%sに指定された日付はデータがロックされています。"), $v['msg'])
                            . sprintf(_g("%s以降の日付を指定してください。"), $sysDate) . "'};res=(msg=='');";
                    $msg = "msg";
                } else {
                    // validatorは必要になってから作成する
                    if (!isset($validator)) {
                        $validator = new Gen_Validator($form);
                    }
                    if ($v['cat'] == "eval") {
                        if (isset($v['evalJS'])) {
                            $validScript = self::_replaceForJS($v['evalJS'], $col['column'], $isInList, $suffix);
                        }
                    } else {
                        $method = $v['cat'] . "_JS";
                        if (!method_exists($validator, $method)) {
                            throw new Exception($col['column'] . "に指定されているバリデーション '$method' は正しくありません。");
                        }
                        if (isset($v['param'])) {
                            if (is_array($v['param'])) {
                                $validScript = call_user_func_array(array($validator, $method), $v['param']);
                            } else {
                                $validScript = $validator->$method($v['param']);
                            }
                        } else {
                            $validScript = $validator->$method();
                        }
                    }
                    $msg = "'{$v['msg']}'";
                }

                // バリデーションスクリプトひとつを書き出す
                if ($validScript != "") {

                    // skipValidateの処理（バリデーションレベル）
                    if (isset($v['skipValidateJS'])) {
                        $check = self::_replaceForJS($v['skipValidateJS'], $col['column'], $isInList, $suffix);
                        $validScript = "if({$check}){res=true}else{{$validScript}}";
                    }

                    // バリデーションエラー表示。
                    $validScript .= ";gen.edit.showError(res,'{$columnWithSuffix}" . ($isInList ? "_'+lineNo" : "'") . ",{$msg});";
                    // ひとつの項目に複数のバリデーションがあるとき、エラーになった時点でチェックをストップし、それ以降の
                    //  バリデーションは行わない。エラーメッセージのスペースの問題と、次以降がセーフだったときに
                    //  エラーメッセージが消されてしまう問題を避けるため
                    $validScript .= "if(!res)return false;";

                    $checkScript .= $validScript;
                }
            }   // バリデーションレベルループ End

            // 1項目（カラム）分のバリデーションスクリプトを書き出す
            if ($checkScript != "") {
                // skipValidateの処理（カラムレベル）
                if (isset($col['skipValidateJS'])) {
                    $check = self::_replaceForJS($col['skipValidateJS'], $col['column'], $isInList, $suffix);
                    $checkScript = "if({$check}){gen.edit.showError(true,'{$columnWithSuffix}" . ($isInList ? "_'+lineNo" : "'") . ");}else{{$checkScript}}";
                }

                // 値取得、convertの処理を挿入。
                // 1項目に複数のバリデーションがあるとき、これらは最初の1回だけでいい
                if (!isset($clientValidArr[$columnWithSuffix]['script'])) {
                    $clientValidArr[$columnWithSuffix]['script'] = "";

                    // convert処理を挿入
                    $convScript = "";
                    if (isset($col['convert'])) {

                        // converterを作成する
                        if (!isset($converter)) {
                            $converter = new Gen_Converter($form);
                        }
                        foreach ($col['convert'] as $c) {
                            $method = $c['cat'] . "_JS";
                            if (isset($c['param'])) {
                                if (is_array($c['param'])) {
                                    $convScript .= call_user_func_array(array($converter, $method), $c['param']);
                                } else {
                                    $convScript .= $converter->$method($c['param']);
                                }
                            } else {
                                $convScript .= $converter->$method();
                            }
                            if ($convScript != "")
                                $convScript .=";";
                        }
                    }
                    $checkScript = $convScript . $checkScript;

                    // 値取得処理を挿入
                    $checkScript = "val=$('#{$columnWithSuffix}" . ($isInList ? "_'+lineNo" : "'") . ").val();if(val=='[multi]')return true;{$checkScript}";
                }

                $clientValidArr[$columnWithSuffix]['script'] .= $checkScript;
                // EditListの行数を格納。カラムごとに格納するのはなんだか不自然に思えるが、EditListが複数存在した場合を考えてこのようにしてある。
                $clientValidArr[$columnWithSuffix]['listCount'] = $controls[$col['column']];
            }

            // dependentColumn（バリデーションをトリガする別カラム）の処理
            if (isset($col['dependentColumn'])) {
                if (!is_array($col['dependentColumn'])) {
                    $col['dependentColumn'] = array($col['dependentColumn']);
                }

                foreach ($col['dependentColumn'] as $dc) {
                    $dcWithSuffix = $dc . $suffix;
                    if (!isset($clientValidArr[$dcWithSuffix]['dependentColumn'])) {
                        $clientValidArr[$dcWithSuffix]['dependentColumn'] = array();
                    }
                    $clientValidArr[$dcWithSuffix]['dependentColumn'][] = $columnWithSuffix;
                }
            }
        }   // カラムレベルループ End

        // 1カラム内のすべてのバリデーションがセーフだったときに、trueを返す処理
        foreach ($clientValidArr as &$cv) {
            if (isset($cv['script']))   // dependのみのカラムはscriptが存在しない
                $cv['script'] .= "return true;";
        }
//var_dump($clientValidArr);
//return array();
        return $clientValidArr;
    }

    final function regist(&$param, $isFirstRegist = true)
    {
        global $gen_db, $form;

        $keyVal = $this->_regist($param, $isFirstRegist);
        if (is_array($keyVal)) {
            $detailKeyVal = $keyVal[1];
            $keyVal = $keyVal[0];
        }
        
        if ($this->keyColumn && $keyVal) {
            // カスタム項目
            if (isset($form['gen_customColumnTable'])) {
                if (isset($_SESSION['gen_setting_company']->customcolumnisdetail)) {
                    $isDetailArr = $_SESSION['gen_setting_company']->customcolumnisdetail;
                    if (is_object($isDetailArr)) {
                        $isDetailArr = get_object_vars($isDetailArr);
                    }
                }
                $existDetail = isset($form['gen_customColumnDetailTable']) && $form['gen_customColumnDetailTable'] !== false;
                if ($existDetail) {
                    if (!isset($this->detailKeyColumn)) {
                        throw new Exception("明細項目がある場合、Modelクラスの _getKeyColumn() の戻り値をarrayにする必要があります。");
                    }
                    if (!isset($detailKeyVal)) {
                        throw new Exception("明細項目がある場合、Modelクラスの _regist() の戻り値をarrayにする必要があります。");
                    }
                }
                for ($j=1;$j<=($existDetail ? 2 : 1);$j++) {
                    $table = ($j==1 ? $form['gen_customColumnTable'] : $form['gen_customColumnDetailTable']);
                    $query = "update {$table} set ";
                    for ($i=1; $i<=GEN_CUSTOM_COLUMN_COUNT; $i++) {
                        $text = "null";
                        if (isset($param["gen_custom_custom_text_{$i}"]) && $param["gen_custom_custom_text_{$i}"] != '') { 
                            if (isset($form['gen_customColumnArray']["custom_text_{$i}"])) {
                                $detailKey = $form['gen_customColumnArray']["custom_text_{$i}"][2];
                                $isDetail = isset($isDetailArr[$detailKey]) && $isDetailArr[$detailKey];
                                if (($j==1 && !$isDetail) || ($j==2 && $isDetail)) {
                                    $text = "'{$param["gen_custom_custom_text_{$i}"]}'";
                                }
                            }
                        }
                        $date = "null";
                        if (isset($param["gen_custom_custom_date_{$i}"]) && $param["gen_custom_custom_date_{$i}"] != '') { 
                            if (isset($form['gen_customColumnArray']["custom_date_{$i}"])) {
                                $detailKey = $form['gen_customColumnArray']["custom_date_{$i}"][2];
                                $isDetail = isset($isDetailArr[$detailKey]) && $isDetailArr[$detailKey];
                                if (($j==1 && !$isDetail) || ($j==2 && $isDetail)) {
                                    $date = "'{$param["gen_custom_custom_date_{$i}"]}'";
                                }
                            }
                        }
                        $num = "null";
                        if (isset($param["gen_custom_custom_numeric_{$i}"]) && $param["gen_custom_custom_numeric_{$i}"] != '') { 
                            if (isset($form['gen_customColumnArray']["custom_numeric_{$i}"])) {
                                $detailKey = $form['gen_customColumnArray']["custom_numeric_{$i}"][2];
                                $isDetail = isset($isDetailArr[$detailKey]) && $isDetailArr[$detailKey];
                                if (($j==1 && !$isDetail) || ($j==2 && $isDetail)) {
                                    $num = "'{$param["gen_custom_custom_numeric_{$i}"]}'";
                                }
                            }
                        }

                        if ($i > 1) {
                            $query .= ",";
                        }
                        $query .= "custom_text_{$i} = {$text}, custom_date_{$i} = {$date}, custom_numeric_{$i} = {$num}";
                    }
                    if ($j==1) {
                        $query .= " where {$this->keyColumn} = '{$keyVal}'";
                    } else {
                        $query .= " where {$this->detailKeyColumn} = '{$detailKeyVal}'";
                    }
                    $gen_db->query($query);
                }
            }
            
            // 新規登録の場合、添付ファイルやスレッドが仮登録されている可能性がある。（EditBaseの「添付ファイル」参照）
            // 仮登録IDをレコードIDに書き換えておく。
            if ($this->isNew($form)) {
                $userId = Gen_Auth::getCurrentUserId();
                $data = array("record_id" => $keyVal, "temp_upload_user_id" => null);
                $where = "temp_upload_user_id = '{$userId}' and record_id = '-999'";   // 今回登録分以外の仮ファイルはEditBaseで削除済み
                $gen_db->update("upload_file_info", $data, $where);

                $data = array("record_id" => $keyVal, "temp_user_id" => null);
                $where = "temp_user_id = '{$userId}' and record_id = '-999'";   // 今回登録分以外の仮ファイルはListBaseで削除済み
                $gen_db->update("chat_header", $data, $where);
            }
        }

        // unlockNumberの処理
        // くわしくは validate の lockNumber処理のコメントを参照。
        if (isset($gen_db->lockNumberArray) && count($gen_db->lockNumberArray) > 0) {
            foreach ($gen_db->lockNumberArray as $lockName => $lockArray) {
                foreach ($lockArray as $no => $lockVal) {
                    Logic_NumberTable::unlockNumber($lockName, $lockVal);
                    unset($gen_db->lockNumberArray[$lockName][$no]);
                }
            }
        }
    }

    final function detailDelete($detailId)
    {
        $this->_detailDelete($detailId);
    }
    
    final function lineDelete($lineNo)
    {
        $this->_lineDelete($lineNo);
    }

    //************************************************
    // Protected Method
    //************************************************
    // コード->id変換の補助 （setDefault()用）
    protected function _codeToId(&$param, $codeCol, $idCol, $code, $id, $table)
    {
        global $gen_db;

        if (!isset($param[$idCol]) && isset($param[$codeCol]) && $param[$codeCol] != "") {
            if ($code == "")
                $code = $codeCol;
            if ($id == "")
                $id = $idCol;
            $query = "select {$id} from {$table} where {$code} ='{$param[$codeCol]}'";
            $param[$idCol] = $gen_db->queryOneValue($query);
            if ($param[$idCol] === false)
                $param[$idCol] = "-99999999"; // valid errr
        }
    }

    //************************************************
    // Private Method
    //************************************************
    // columns配列のプリセットパターンの処理
    private function _columnPattern()
    {
        foreach ($this->columns as $num => $col) {
            if (!isset($col['column']))
                throw new Exception("columns配列 {$num}番目にプロパティ column が設定されていません。");

            if (!isset($col['pattern']))
                continue;

            switch ($col['pattern']) {

                // ******** データの種類別のパターン ********

                case "id":  // id
                    $name = (isset($col['label']) ? $col['label'] : "id");
                    $this->columns[$num] = array(
                        "column" => $this->columns[$num]["column"],
                        "skipValidatePHP" => "$1==''",
                        "skipValidateJS" => "true", // 画面上には存在しない項目なのでノーチェック
                        "validate" => array(
                            array(
                                "cat" => "blankOrNumeric",
                                "msg" => sprintf(_g("%sが正しくありません。"), $name)
                            ),
                        ),
                    );
                    break;

                case "numeric":  // 数値 （null ok）
                case "integer":  // 整数 （null ok）
                    $name = (isset($col['label']) ? $col['label'] : "数値");
                    $this->columns[$num] = array(
                        "column" => $this->columns[$num]["column"],
                        "convert" => array(
                            array(
                                "cat" => "strToNum",
                            ),
                            array(
                                "cat" => "blankToNull",
                            ),
                        ),
                        "skipValidatePHP" => "$1===null",
                        "skipValidateJS" => "$1===null",
                        "validate" => array(
                            array(
                                "cat" => "numeric",
                                "msg" => sprintf(_g('%sが正しくありません。'), $name)
                            ),
                        ),
                    );

                    if ($col['pattern'] == "integer") {
                        $this->columns[$num]["validate"][] =
                                array(
                                    "cat" => "integer",
                                    "skipHasError" => true,
                                    "msg" => sprintf(_g('%sには整数を指定してください。'), $name),
                        );
                    }
                    break;

                case "bool":  // Bool値 （画面ではチェックボックスにするような値）
                    $this->columns[$num] = array(
                        "column" => $this->columns[$num]["column"],
                        "convert" => array(
                            array(
                                "cat" => "selectStrToValue", // CSV用
                                "param" => array(array('1'), 'true'),
                            ),
                            array(
                                "cat" => "notSelectStrToValue",
                                "param" => array(array('true', 'false'), 'false'),
                            ),
                        ),
                    );
                    break;

                case "nullToBlank":  // ブランクOKで、チェック不要なテキスト
                    $this->columns[$num] = array(
                        "column" => $this->columns[$num]["column"],
                        "convert" => array(
                            array(
                                "cat" => "nullBlankToValue",
                                "param" => ''
                            ),
                        ),
                    );
                    break;

                // ******** マスタ系のパターン ********

                case "item_id": // 品目 （null ok）
                case "item_id_required": // 品目 （必須）
                    $this->columns[$num] = self::_patternOfMaster($this->columns[$num]["column"], (isset($col['label']) ? $col['label'] : _g("品目")), "select item_id from item_master where item_id = $1", $col['pattern'] == "item_id_required");
                    break;

                case "customer_id": // 取引先 （null ok）
                case "customer_id_required": // 取引先 （必須）
                    $this->columns[$num] = self::_patternOfMaster($this->columns[$num]["column"], (isset($col['label']) ? $col['label'] : _g("取引先")), "select customer_id from customer_master where customer_id = $1"
                                    . (isset($this->columns[$num]["addwhere"]) ? " and " . $this->columns[$num]["addwhere"] : ""), $col['pattern'] == "customer_id_required");
                    break;

                case "item_group_id": // 品目グループ （null ok）
                case "item_group_required": // 品目グループ （必須）
                    $this->columns[$num] = self::_patternOfMaster($this->columns[$num]["column"], (isset($col['label']) ? $col['label'] : _g("品目グループ")), "select item_group_id from item_group_master where item_group_id = $1", $col['pattern'] == "item_group_id_required");
                    break;

                case "customer_group_id": // 取引先グループ （null ok）
                case "customer_group_required": // 取引先グループ （必須）
                    $this->columns[$num] = self::_patternOfMaster($this->columns[$num]["column"], (isset($col['label']) ? $col['label'] : _g("取引先グループ")), "select customer_group_id from customer_group_master where customer_group_id = $1", $col['pattern'] == "customer_group_id_required");
                    break;

                case "location_id": // ロケーション （null ok）
                case "location_id_required": // ロケーション （必須）
                    $this->columns[$num] = self::_patternOfMaster($this->columns[$num]["column"], (isset($col['label']) ? $col['label'] : _g("ロケーション")), "select location_id from location_master where location_id = $1", $col['pattern'] == "location_id_required");

                    if ($col['pattern'] == "location_id_required") {
                        $this->columns[$num]["skipValidatePHP"] = "$1=='0'||$1=='-1'";
                        $this->columns[$num]["skipValidateJS"] = "$1=='0'||$1=='-1'";
                    } else {
                        $this->columns[$num]["skipValidatePHP"] = "$1=='0'||$1=='-1'||$1===null";
                        $this->columns[$num]["skipValidateJS"] = "$1=='0'||$1=='-1'||$1===null";
                    }
                    break;

                case "lot_id": // ロット （null ok）
                case "lot_id_required": // ロット （必須）
                    $this->columns[$num] = self::_patternOfMaster($this->columns[$num]["column"], (isset($col['label']) ? $col['label'] : _g("ロット")), "select lot_id from lot_master where lot_id = $1", $col['pattern'] == "lot_id_required");
                    break;

                case "process_id": // 工程 （null ok）
                case "process_id_required": // 工程 （必須）
                    $this->columns[$num] = self::_patternOfMaster($this->columns[$num]["column"], (isset($col['label']) ? $col['label'] : _g("工程")), "select process_id from process_master where process_id = $1", $col['pattern'] == "process_id_required");
                    break;

                case "section_id": // 部門 （null ok）
                case "section_id_required": // 部門 （必須）
                    $this->columns[$num] = self::_patternOfMaster($this->columns[$num]["column"], (isset($col['label']) ? $col['label'] : _g("部門")), "select section_id from section_master where section_id = $1", $col['pattern'] == "section_id_required");
                    break;

                case "worker_id": // 従業員 （null ok）
                case "worker_id_required": // 従業員 （必須）
                    $this->columns[$num] = self::_patternOfMaster($this->columns[$num]["column"], (isset($col['label']) ? $col['label'] : _g("従業員")), "select worker_id from worker_master where worker_id = $1", $col['pattern'] == "worker_id_required");
                    break;

                case "equip_id": // 設備 （null ok）
                case "equip_id_required": // 設備 （必須）
                    $this->columns[$num] = self::_patternOfMaster($this->columns[$num]["column"], (isset($col['label']) ? $col['label'] : _g("設備")), "select equip_id from equip_master where equip_id = $1", $col['pattern'] == "equip_id_required");
                    break;

                case "waster_id": // 不適合理由 （null ok）
                case "waster_id_required": // 不適合理由（必須）
                    $this->columns[$num] = self::_patternOfMaster($this->columns[$num]["column"], (isset($col['label']) ? $col['label'] : _g("不適合理由")), "select waster_id from waster_master where waster_id = $1", $col['pattern'] == "waster_id_required");
                    break;

                case "price_percent_group_id": // 掛率グループ （null ok）
                case "price_percent_group_id_required": // 掛率グループ（必須）
                    $this->columns[$num] = self::_patternOfMaster($this->columns[$num]["column"], (isset($col['label']) ? $col['label'] : _g("不適合理由")), "select price_percent_group_id from price_percent_group_master where price_percent_group_id = $1", $col['pattern'] == "price_percent_group_id_required");
                    break;

                case "estimate_header_id": // 見積ヘッダ （null ok）
                case "estimate_header_id_required": // 見積ヘッダ （必須）
                    $this->columns[$num] = self::_patternOfMaster($this->columns[$num]["column"], (isset($col['label']) ? $col['label'] : _g("見積")), "select estimate_header_id from estimate_header where estimate_header_id = $1", $col['pattern'] == "estimate_header_id_required");
                    break;


                // ******** その他の項目のパターン ********

                case "order_detail_id":             // オーダー番号　（null ok）
                case "order_detail_id_required":    // オーダー番号　（必須）
                    $name = (isset($col['label']) ? $col['label'] : _g("オーダー番号"));
                    if ($col['pattern'] == "estimate_header_id_required") {
                        // 必須の場合
                        $this->columns[$num] = array(
                            "column" => $this->columns[$num]["column"],
                            "validate" => array(
                                array(
                                    "cat" => "numeric",
                                    "msg" => sprintf(_g("%sが正しくありません。"), $name),
                                ),
                                array(
                                    "cat" => "existRecord",
                                    "msg" => sprintf(_g("%sが存在しません。"), $name),
                                    "skipHasError" => true,
                                    "param" => "select order_detail_id from order_detail inner join order_header " .
                                    " on order_detail.order_header_id = order_header.order_header_id " .
                                    " where order_detail_id = $1"
                                    . (isset($this->columns[$num]["addwhere"]) ?
                                            " and " . $this->columns[$num]["addwhere"] : ""),
                                ),
                            ),
                        );
                    } else {
                        // ブランクOKの場合
                        $this->columns[$num] = array(
                            "column" => $this->columns[$num]["column"],
                            "convert" => array(
                                array(
                                    "cat" => "blankToNull",
                                ),
                            ),
                            "skipValidatePHP" => "$1===null",
                            "skipValidateJS" => "$1===null",
                            "validate" => array(
                                array(
                                    "cat" => "numeric",
                                    "msg" => sprintf(_g("%sが正しくありません。"), $name),
                                ),
                                array(
                                    "cat" => "existRecord",
                                    "msg" => sprintf(_g("%sが存在しません。"), $name),
                                    "skipHasError" => true,
                                    "param" => "select order_detail_id from order_detail inner join order_header " .
                                    " on order_detail.order_header_id = order_header.order_header_id " .
                                    " where order_detail_id = $1"
                                    . (isset($this->columns[$num]["addwhere"]) ?
                                            " and " . $this->columns[$num]["addwhere"] : ""),
                                ),
                            ),
                        );
                    }
                    break;

                default:
                    throw new Exception("columnArray の " . @$this->columns[$num]["column"] . "で、不正な pattern が指定されています。");
            }
        }
    }

    //　マスタ共通パターン（数値id）
    private function _patternOfMaster($column, $name, $query, $isRequired)
    {
        if ($isRequired) {
            // 必須の場合
            $arr = array(
                "column" => $column,
                "validate" => array(
                    array(
                        "cat" => "numeric",
                        "msg" => sprintf(_g("%sが正しくありません。"), $name),
                    ),
                    array(
                        "cat" => "existRecord",
                        "msg" => sprintf(_g("%sがマスタに登録されていません。"), $name),
                        "skipHasError" => true,
                        "param" => $query
                    ),
                ),
            );
        } else {
            // ブランクOKの場合
            $arr = array(
                "column" => $column,
                "convert" => array(
                    array(
                        "cat" => "blankToNull",
                    ),
                ),
                "skipValidatePHP" => "$1===null",
                "skipValidateJS" => "$1===null",
                "validate" => array(
                    array(
                        "cat" => "numeric",
                        "msg" => sprintf(_g("%sが正しくありません。"), $name),
                    ),
                    array(
                        "cat" => "existRecord",
                        "msg" => sprintf(_g("%sがマスタに登録されていません。"), $name),
                        "skipHasError" => true,
                        "skipValidatePHP" => "$1==='0'", // 0は特別扱い
                        "param" => $query
                    ),
                ),
            );
        }

        return $arr;
    }

    // PHPコード内の「$1」と「[[...]]」を$form値に置換してevalし、その結果を返す（サーバーバリデーション用）
    // 評価式は、結果が変数$resに入るように書かれていることが前提。
    private function _replaceEval($str, $column, $form, $suffix)
    {
        // 「$1」は処理中の項目（カラム）の$form値に置き換える。
        $val = @$form[$column . $suffix];
        if ($val === null) {
            $val = "null";
        } else {
            $val = "'$val'";
        }
        $str = str_replace("$1", $val, $str);

        // 「[[..]]」は該当する項目（カラム）の$form値に置き換える。
        $str = Gen_String::bracketToForm($str, $form, $suffix);
        // evalして結果を返す
        return eval($str . ';return $res;');
    }

    // JavaScriptコード内の「$1」と「[[...]]」を値取得コード（$('#...').val()）に置換して返す（クライアントバリデーション用）
    private function _replaceForJS($str, $column, $isInList, $suffix)
    {
        // 「$1」
        // 変数valに置換する。
        // valはあらかじめ取得されている値で、convert処理がおこなわれている。
        $str = str_replace("$1", "val", $str);

        // 「[[..]]」
        $matches = "";
        if (preg_match_all("(\[\[[^\]]*\]\])", $str, $matches) > 0) {
            foreach ($matches[0] as $match) {
                $matchStr = $match;
                $matchStr = str_replace('[[', '', $matchStr);
                $matchStr = str_replace(']]', '', $matchStr);
                $str = str_replace($match, "$('#" . $matchStr . $suffix . ($isInList ? "_'+lineNo" : "'") . ").val()", $str);
            }
        }
        return $str;
    }

}