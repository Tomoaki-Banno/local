<?php

class Gen_Db
{

    //************************************************
    //    クラス変数
    //************************************************

    var $con = null;
    var $tranCount = 0;
    var $tempTableArray = array();

    //************************************************
    //    接続
    //************************************************

    function connect()
    {
        // pg_hba.conf の設定で、postgresアカウントのローカルからの接続はpw不要になっている
        $this->conStr = "host=" . GEN_POSTGRES_HOST . " port=" . GEN_POSTGRES_PORT . " dbname=" . GEN_DATABASE_NAME . " user=" . GEN_POSTGRES_USER;

        if (!$this->con = pg_connect($this->conStr)) {
            // モード毎にエラー表示を制御
            if (isset($_SESSION['gen_setting_user']->demo_mode) && $_SESSION['gen_setting_user']->demo_mode) {
                trigger_error("Database Connection Error");
            } else {
                echo('<font color="#000000"><b>Database Connection Error<br><br><small><font color="#ff0000">[Genesiss STOP]</font></small><br><br></b></font>');
                throw new Exception();
            }
        }
    }

    function close()
    {
        pg_close($this->con);
    }

    //************************************************
    //    スキーマバージョン（rev. 201102xx 以降）
    //************************************************
    // スキーマバージョンのチェックと自動スキーマ更新。ログイン時に実行される
    function schemaAutoUpdate()
    {
        // データのスキーマバージョンを取得
        $query = "select schema_version from company_master";
        $dbSchemaVer = $this->queryOneValue($query);
        if ($dbSchemaVer === null) {
            $dbSchemaVer = 2011012800;        // スキーマ自動更新機能が導入された一つ前のリビジョン
        } else {
            $dbSchemaVer = (int) $dbSchemaVer;
        }

        // 13iのスキーマバージョン 2014022401 は 15iのスキーマバージョンを追い越してしまっているので、
        // 巻き戻す必要がある。
        if ($dbSchemaVer == 2014022401) {
            $dbSchemaVer = 2013129999;  // この番号を変更するときは _updateSchema() も変更の必要あり
        }

        // スキーマファイルを読み込み
        $schemaFile = dirname(dirname(__FILE__)) . '/gen_schema.yml';
        if (!file_exists($schemaFile)) {
            throw new Exception('gen_schema.yml がありません。');
        }
        $schema = Spyc::YAMLLoad($schemaFile);

        // 現行スキーマバージョンを取得
        ksort($schema, SORT_NUMERIC);    // キー昇順でソート
        end($schema);
        list($schemaVer, $dummy) = each($schema);
        if (!is_numeric($schemaVer)) {
            throw new Exception('gen_schema.yml の schema_version が数字ではありません。');
        }
        reset($schema);

        // 現行スキーマバージョンよりデータのスキーマバージョンが古ければ、更新処理を行う
        if ($dbSchemaVer < $schemaVer) {
            $this->_updateSchema($schema, $dbSchemaVer);
        }
    }

    // スキーマ情報にもとづきスキーマを更新
    private function _updateSchema($schema, $dbSchemaVer)
    {
        $this->begin();
 
        // $schema配列はキー昇順に並び替えられている
        $lastVer = '';
        foreach ($schema as $schemaVer => $sql) {
            if (($schemaVer == 2014010127 || $schemaVer == 2014010129) && $dbSchemaVer == 2013129999) {
                // 13iのスキーマバージョン 2014022401（schemaAutoUpdate()で 2013129999 に変換されている） から
                // 15iへアップグレードする場合、2014010127, 2014010129 の更新はすでに行われているためスキップする必要がある
                continue;
            }
            if ($schemaVer > $dbSchemaVer) {
                $lastVer = $schemaVer;
                if (is_array($sql)) {
                    foreach ($sql as $sqlOne) {
                        $this->query($sqlOne);
                    }
                } else {
                    $this->query($sql);
                }
                Gen_Log::dataAccessLog(_g("スキーマ自動更新"), _g("成功"), 'ver： ' . $schemaVer);
            }
        }
        if ($lastVer != '') {
            $query = "update company_master set schema_version = {$lastVer}";
            $this->query($query);
        }
        
        // ----- 13i以前 ⇒ 15i以降のアップグレードのための処理 -----

        // 13i ⇒ 15iの場合に限り、 新機能のアクセス権をデフォルトで「アクセス可能」にする。
        // ag.cgi?page=ProjectDocView&pid=1574&did=209987
        if ($dbSchemaVer <= 2013129999) {
            $query = "
                insert into permission_master 
                select 
                    user_master.user_id 
                    ,t.column1 as class_name 
                    ,t.column2 as permission 
                    ,'admin' as record_creator 
                    ,CURRENT_TIMESTAMP as record_create_date 
                    ,'DIRECT' as record_create_func 
                from 
                user_master 
                cross join ( 
                    values 
                    ('config_customcolumn',1), 
                    ('config_storage',1), 
                    ('config_uploadfile',1), 
                    ('config_wordconvert',1), 
                    ('config_personalize',1), 
                    ('menu_chat',1), 
                    ('menu_home2',1), 
                    ('menu_maintenance',1), 
                    ('menu_map',1), 
                    ('mobile_chat',1) 
                ) t 
                order by user_id, class_name;                
            ";
            $this->query($query);
        }
        
        // 13i以前は画像ファイルをDBのラージオブジェクトに保存していたが、15i以降ではfiles_dirに
        // ファイルとして保存するようになった。その移行処理を行う。
        $query = "select 1 as is_logo, image_file_oid, image_file_name from company_master where image_file_oid is not null 
            union select 0 as is_logo, image_file_oid, image_file_name from item_master where image_file_oid is not null";
        $oidInfo = $this->getArray($query);
        if ($oidInfo) {
            foreach ($oidInfo as $info) {
                $ext = end(explode('.',$info['image_file_name']));
                $file = tempnam(GEN_TEMP_DIR, "") . "." . $ext;
                $fileName = basename($file);
                pg_lo_export($info['image_file_oid'], $file);
                $cat = ($info['is_logo'] == "1" ? "CompanyLogo" : "ItemImage");
                $storage = new Gen_Storage($cat);
                $storage->put($file);
                $query = "update " . ($info['is_logo'] == "1" ? "company_master" : "item_master") . 
                        " set image_file_oid = null, original_image_file_name = image_file_name, image_file_name = '{$fileName}'
                          where image_file_oid = '{$info['image_file_oid']}'";
                $this->query($query);
            }
        }
        // companySettingに保存されている自社ロゴ情報をクリア
        $companySetting = $this->queryOneValue("select setting from company_master");
        if ($companySetting != "") {
            $companySetting = str_replace("￥", "\\", $companySetting);
            $companyJson = json_decode($companySetting);
            unset($companyJson->companyLogoFile);
            $companySetting = json_encode($companyJson);
            $data = array("setting" => $companySetting);
            $where = "";
            $this->update("company_master", $data, $where);
        }
     
        // バックアップデータベース名の変更（13i ⇒ 15i）
        $storage = new Gen_Storage("BackupData");
        $arr = $storage->listFiles();
        if ($arr) {
            foreach($arr as $file) {
                if (substr($file, 0, 7) == "Gen_13i") {
                    $storage->rename($file, str_replace("Gen_13i", "Gen_15i", $file));
                }
            }
        }
        
        $this->commit();
    }

    //************************************************
    //    プライベート
    //************************************************

    private function _error($query, $errno, $error)
    {
        if (!$this->con) {
            $this->connect();
        }
        $errArr = debug_backtrace(); 
        $func = $this->_getCallFunc($errArr);
        $callStack = "";
        foreach($errArr as $key => $err) {
            if (basename($err['file']) != "Db.class.php") {
                if ($callStack != "") {
                    $callStack .= " <-- ";
                }
                $callStack .= basename($err['file']) . "({$err['line']} {$err['function']})"; 
            }
        }
        
        // エラーを記録
        
        // 13i -> 15i の schema update 中のエラーの場合、error_logがまだ存在していない場合がある。
        $sql = "SELECT relname FROM pg_class WHERE relkind = 'r' AND relname = 'error_log'";
        $res = pg_query($sql);
        if (pg_num_rows($res) > 0) {
            $sql = "
                insert into error_log (
                    error_time
                    ,user_name
                    ,ip
                    ,function_name
                    ,call_stack
                    ,error_no
                    ,error_comment
                    ,error_query
                    ,remarks
                )
                values (
                    '" . date('Y-m-d H:i:s') . "'
                    ,'" . $_SESSION['user_name'] . "'
                    ,'" . Gen_Auth::getRemoteIpAddress() . "'
                    ,'{$func}'
                    ,'{$callStack}'
                    ,'" . h(self::quoteParam($errno)) . "'
                    ,'" . h(self::quoteParam($error)) . "'
                    ,'" . h(self::quoteParam($query)) . "'
                    ,''
                )
            ";
            pg_query($this->con, $sql);

            // エラーidを取得
            $sql = "select currval('error_log_error_id_seq') as currval";
            $res = pg_query($this->con, $sql);
            // PHP4.2以前は pg_numrows
            if (pg_num_rows($res) == 0) {
                $id = "null";
            } else {
                $row = $this->fetchRow($res, 0);
                $id = $row[0];
            }
        }

        // モード毎にエラー表示を制御
        if (isset($_SESSION['gen_setting_user']->demo_mode) && $_SESSION['gen_setting_user']->demo_mode) {
            trigger_error($error . '<br><br>' . $query);
        } else {
            if (GEN_SERVER_INFO_CLASS == 10 || GEN_SERVER_INFO_CLASS == 20 || GEN_SERVER_INFO_CLASS == 40) {
                // 10:製品版 20:体験版 40:公開検証版 でのエラー表示。
                // リスト画面での再表示ボタン押下にも対応するためjavascriptでリダイレクト処理。
                $script = "<script type=\"text/javascript\">";
                $script .= "var url = 'index.php?action=SystemUtility_ShowError_GenesissError&error_id={$id}';";
                $script .= "location.href = url;";
                $script .= "</script>";
                print($script);
                exit();
            } else {
                // 上記以外のエラー表示
                echo('<font color="#000000"><b>' . $errno . ' - ' . $error . '<br><br>' . $query .
                        '<br><br>' . $callStack .
                        '<br><br>' . '<small><font color="#ff0000">[Genesiss STOP]</font></small><br><br></b></font>');
                throw new Exception();
            }
        }
    }
    
    private function _getCallFunc($backtrace)
    {
        foreach($backtrace as $key => $t) {
            if ($t['class'] != "Gen_Db") {
                break;
            }
        }
        return $backtrace[$key]['class'] . $backtrace[$key]['type'] . $backtrace[$key]['function'];
    }

    private function _getSqlValue($value)
    {
        if ($value === null) {
            return 'null ';
        }
        if (substr($value, 0, 8) == "noquote:") {
            return substr($value, 8, strlen($value) - 8);
        }
        switch ((string) $value) {
            case 'now()':
                return 'now() ';
            //case 'null':
            //    return  'null ';
            default:
                return '\'' . $value . '\' ';
        }
        return null;
    }

    //************************************************
    //    サニタイジング
    //************************************************

    function quoteParam($param)
    {
        if ($param === null)
            return null;

        // 「\」⇒「￥」
        // pg_escape_stringでは「\」は「\\」になるが、それよりは「￥」のほうが自然
        $param = str_replace("\\", "￥", $param);
        // pg_escape_stringでは「'」は「''」になるが、それよりは「’」のほうが自然
        $param = str_replace("'", "’", $param);

        return pg_escape_string($param);
    }

    //************************************************
    //    SQL実行
    //************************************************

    function query($query)
    {
        if (!$this->con) {
            $this->connect();
        }
        // SQLをUTFへエンコード（SQL文中のパラメータがUTF以外である可能性があるため）
        // ⇒ autoによる自動判定は失敗することがあるので使わないほうがいい
        //mb_convert_encoding($query, "UTF-8", "auto");
        // 実行とエラー時処理
        // PHP 4.2以前では pg_exec
        $time_start = microtime(true);
        if (!$res = pg_query($this->con, $query)) {
            $this->_error($query, "", pg_errormessage());
        }
        $time_end = microtime(true);
        $execTime = $time_end - $time_start;        
        // スロークエリを記録
        if ($execTime >= 1) {
            // 13i -> 15i の schema update 中のエラーの場合、error_logがまだ存在していない場合がある。
            $sql = "SELECT relname FROM pg_class WHERE relkind = 'r' AND relname = 'error_log'";
            $res_err = pg_query($this->con, $sql);
            if (pg_num_rows($res_err) > 0) {
                // カラム call_stack（15iで追加）は含めないこと。
                // 　call_stackが含められていると、13iからのスキーマアップデートでここを実行した際にエラーになる。
                $sql = "
                    insert into error_log (
                        error_time
                        ,user_name
                        ,ip
                        ,function_name
                        ,error_no
                        ,error_comment
                        ,error_query
                        ,remarks
                    )
                    values (
                        '" . date('Y-m-d H:i:s') . "'
                        /* ExecMrp の場合はセッション変数が取れない  */    
                        ,'" . (isset($_SESSION['user_name']) ? $_SESSION['user_name'] : "") . "'
                        ,'" . (isset($_SERVER["REMOTE_ADDR"]) ? Gen_Auth::getRemoteIpAddress() : "") . "'
                        ,''
                        ,''
                        ,'Slow(' || {$execTime} || ' sec)'
                        ,'" . h(self::quoteParam($query)) . "'
                        ,''
                    )
                ";
                pg_query($this->con, $sql);
            }
        }
        
        // デバッグ用：SQL実行のたびにコールスタックを表示する
//        d($query);
//        $trace = debug_backtrace();        
//        $callStack = "";
//        foreach($trace as $t) {
//            if (basename($t['file']) != "Db.class.php") {
//                if ($callStack != "")
//                    $callStack .= " <-- ";
//                $callStack .= basename($t['file']) . "({$t['line']} {$t['function']})"; 
//            }
//        }
//        d($callStack);

        return $res;
    }

    //************************************************
    //    読み取り系
    //************************************************
    // 全データを配列に格納する
    // fetch_allはPostgresにしかないので、他のDBではforループでfetchArrayを呼び出す
    // などの処理が必要。
    function getArray($query)
    {
        return pg_fetch_all($this->query($query));
    }

    // イテレータで取得
    function getIterator($query)
    {
        return new Gen_Db_ResourceIterator($this->query($query));
    }

    // (1列目 => 2列目) という形の連想配列を返す。
    // HTML の OPTION作成用。
    function getHtmlOptionArray($query, $bAddNothing, $optionArr = null)
    {
        $array = array();
        if ($bAddNothing) {
            // 09iまでは「なし」のときのkeyを「null」にしており、これだと gen_db の insert/updateでそのまま登録できた。
            // しかし拡張DDなら空欄なのにセレクタだとnull、などわかりにくく、
            // しかもCSVやExcel登録で空欄だったときに、いちいち「null」に変換する処理が必要になる。
            // 10iでは「なし」のときのkeyを空欄とし、登録時に空欄をnullに変換するようにした。
            $array[''] = _g('(なし)');
        }

        // 表示の崩れを防ぐため、選択肢の文字数を制限する。
        // <select>にCSSでwidthを指定する方法もあるが、それだとセレクタ自体のサイズは指定できるが、
        // ドロップダウンしたときの選択肢のサイズはコントロールできない。場合によっては選択肢が
        // 画面の右側にはみ出してしまい、スクロールバーが操作できなくなる。
        $maxLen = 30;   // gen_search_control, gen_edit_control と同じ

        if (is_array($optionArr)) {
            foreach ($optionArr as $key => $val) {
                $array[$key] = mb_substr($val, 0, $maxLen, 'UTF-8');
            }
        }

        if (!$res = $this->query($query))
            return $array;

        while ($row = $this->fetchRow($res, null)) {
            $array[$row[0]] = mb_substr($row[1], 0, $maxLen, 'UTF-8');
        }
        return $array;
    }

    // queryとfetch_objectを一度に行い、最初の1行をオブジェクト形式で返す。
    // 結果が1行であることがわかっているときや、最初の1行のみ必要であるときに使う。
    // 「$res->field」
    function queryOneRowObject($query, $noValueString = false)
    {
        $res = $this->query($query);

        if ($this->numRows($res) == 0) {
            return $noValueString;
        }
        return $this->fetchObject($res, 0);
    }

    // 最初の1行の1カラム目を返す。
    function queryOneValue($query, $noValueString = false)
    {
        $res = $this->query($query);

        // PHP4.2以前は pg_numrows
        if (pg_num_rows($res) == 0) {
            return $noValueString;
        }
        $row = $this->fetchRow($res, 0);

        return $row[0];
    }

    // レコードがあるかないかをtrue/falseで返す。
    function existRecord($query)
    {
        $res = $this->query($query);

        // PHP4.2以前は pg_numrows
        if (pg_num_rows($res) == 0) {
            return false;
        }
        return true;
    }

    //************************************************
    //    fetch
    //************************************************
    // 標準fetch方法として、fetch_row、fetch_array、fetch_objectの3つを用意する。
    // この3つは、Postgres関数/MySQL関数/SQL Server関数で共通に用意されているものである。
    // （これ以外のfetch方法は、DBによってあったりなかったりする。ちなみにfetch_assoc(連想配列)
    // も共通にあるが、これはfetch_array(配列)で代用できるので省略した）
    // マニュアルによれば、3つのfetch方法による速度差はほとんどない。

    function fetchRow($res, $row)
    {
        return pg_fetch_row($res, $row);
    }

    function fetchArray($res, $row, $result_type = PGSQL_BOTH)
    {
        return pg_fetch_array($res, $row, $result_type);
    }

    function fetchObject($res, $row)
    {
        return pg_fetch_object($res, $row);
    }

    function numRows($res)
    {
        // PHP4.2以前は pg_numrows
        return pg_num_rows($res);
    }

    //************************************************
    //    更新系
    //************************************************
    // テーブルに配列データをInsertする。
    function insert($table, $data)
    {
        reset($data);
        
        $query = 'insert into ' . $this->quoteParam($table) . ' (';
        while (list($columns, $dummy) = each($data)) {
            $query .= $this->quoteParam($columns) . ', ';
        }
        $query .= "record_creator, record_create_date, record_create_func";

        $query .= ") values (";

        reset($data);
        while (list(, $value) = each($data)) {
            $query .= $this->_getSqlValue($this->quoteParam($value)) . ", ";
        }
        $func = $this->_getCallFunc(debug_backtrace());
        $query .= "'" . @$_SESSION['user_name'] . "', '" . date("Y-m-d H:i:s") . "', '{$func}')";

        return $this->query($query);
    }

    // テーブルに配列データをUpdateする。
    function update($table, $data, $where)
    {
        reset($data);
        $query = 'update ' . $table . ' set ';
        while (list($columns, $value) = each($data)) {
            $query .= $this->quoteParam($columns) . ' = ' . $this->_getSqlValue($this->quoteParam($value)) . ", ";
        }
        $query .= "record_updater = '" . @$_SESSION['user_name'] . "',";
        $query .= "record_update_date ='" . date("Y-m-d H:i:s") . "', ";
        $func = $this->_getCallFunc(debug_backtrace());
        $query .= "record_update_func ='{$func}' ";
        if ($where != '' && $where != null) {
            $query .= " where {$where}";
        }

        return $this->query($query);
    }

    // テーブルに配列データをUpdateかInsertする。
    // key指定がない場合はUpdateせずInsertする（keyが自動生成列のとき用の仕様）
    function updateOrInsert($table, $key, $data)
    {
        if (isset($key)) {
            // key配列からUpdate用where句を作成
            reset($key);
            $where = "";
            while (list($columns, $value) = each($key)) {
                $where .= $columns . ' = ' . $this->_getSqlValue($this->quoteParam($value)) . " and ";
            }
            $where = substr($where, 0, -5);

            // Update
            $res = $this->update($table, $data, $where);

            // 結果を確認、UpdateできていなければInsert
            // PHP 4.2以前では pg_cmdtuples
            if (pg_affected_rows($res) == 0) {
                // Insert（data配列にkey配列を追加して）
                $res = $this->insert($table, array_merge($data, $key));
            }
        } else {
            // key指定がないとき（Insert）
            $res = $this->insert($table, $data);
        }
    }

    //************************************************
    //    シーケンス
    //************************************************
    // 指定されたシーケンスを取得する。
    function getSequence($seqName)
    {
        $query = "select currval('{$seqName}') as currval";
        return $this->queryOneValue($query);
    }

    //************************************************
    //    DBサイズ
    //************************************************
    // 現在接続中のデータベースのサイズ（ディスク領域）を返す。
    function getDatabaseSize()
    {
        $query = "select pg_database_size / 1024 / 1024 from pg_database_size('" . GEN_DATABASE_NAME . "')";
        return $this->queryOneValue($query);
    }

    //************************************************
    //    トランザクション
    //************************************************
    // Postgresでは「BEGIN」「COMMIT」「ROLLBACK」の実行により簡単に
    // トランザクション制御が行える。
    // しかしトランのネストには対応しておらず、ネスト状態になったときは
    // 最初に出てきたCOMMITですべて確定してしまう（つまり内側トランの
    // COMMITで確定となり、外側トランは無意味になる）。
    // また、内側BEGINと外側COMMITの実行時に警告が発生する（エラーには
    // ならないが）。
    // トランがネストしないように気をつけてコーディングすればいいのだが、
    // クラス内で「ココの処理はトランにしたいが、呼び出し側ですでに
    // トラン開始されているかもしれない」という状況がしばしば発生する。
    // そこでここの関数を使用すると、ネストしたときには一番外側のトラン
    // だけが有効となる（内側トランは無視される）。ネストになるかどうかを
    // 気にせずにBEGIN/COMMITできるようになる。

    function begin()
    {
        // トランザクションが開始されていない場合のみ、開始する。
        if ($this->tranCount <= 0) {
            $this->query("BEGIN");
        }
        $this->tranCount++;
    }

    function commit()
    {
        // 一番外側のトランザクションの場合のみ、COMMITする。
        if ($this->tranCount <= 1) {
            $this->query("COMMIT");
            $this->tranCount = 0;
        } else {
            $this->tranCount--;
        }
    }

    function rollback()
    {
        $this->query("ROLLBACK");
        $this->tranCount = 0;
    }

    //************************************************
    //    テンポラリテーブルの作成処理
    //************************************************
    //  1セッション中に同じ名前のテンポラリテーブルを複数回作成する可能性がある場合は、
    //  エラーを避けるために CREATE TEMP TABLE ではなくこのメソッドを使う。
    //  指定されたテーブル名が既存テーブルリスト（$this->tempTableArray）に存在するかどうかを確認し、
    //  なければ CREATE TEMP TABLE、既存なら TRUNCATE & INSERT を行う。
    //  以前は、既存なら DROP していたが、それだと1セッション中に何度も CREATE TEMP TABLE を繰り返す場合に
    //  out of shared memory エラーになる場合があった。
    //  なお、テーブルの既存確認は独自に管理するよりpg_catalogを参照するのが確実に思えるが、
    //  その方法では他セッションのテンポラリテーブルまで見えてしまいうまくいかない。
    //  また既存確認せずとにかくdropしてみる（エラー抑止して）という方法もあるが、それだとMRPでうまくいかない。
    //    $tableName:        テンポラリテーブル名
    //    $query:            $isSelectStyleがtrueの場合は、「CREATE TEMP TABLE xxx AS」もしくは「INSERT INTO xxx」のあとのSELECT文を指定。
    //                        falseの場合は、「CREATE TEMP TABLE xxx」のあとのスキーマ定義（ (id integer, ...）を指定。
    //    $isSelectStyle:    CREATE と同時にSELECT文でデータを投入する場合はtrue, テーブル定義だけを行う場合はfalse
    //    ※同名でスキーマの異なるテーブルを作成するとうまくいかない。同名テーブルは同一スキーマにすること
    function createTempTable($tableName, $query, $isSelectStyle)
    {
        if ($tableName == "")
            return;
        if (in_array($tableName, $this->tempTableArray)) {
            $query = "TRUNCATE TABLE {$tableName};" . ($isSelectStyle ? "INSERT INTO {$tableName} {$query} " : "");
        } else {
            $this->tempTableArray[] = $tableName;
            $query = "CREATE TEMP TABLE {$tableName} " . ($isSelectStyle ? "AS " : "") . $query;
        }
        $this->query($query);
    }

    // PHPUnit用。テンポラリテーブルを全削除する。
    // PHPUnitテストでは、$gen_dbが再作成されてもセッション自体は継続するという状況が起こる。
    // そうするとテンポラリテーブルリスト（$this->tempTableArray）はクリアされるが、テンポラリテーブルは残った状態になり、
    // エラーが発生する。
    // そのため、各テストの終了時（テンポラリテーブルリストがクリアされる前）に、テンポラリテーブルをクリアしておく。
    function clearTempTable()
    {
        foreach ($this->tempTableArray as $tempTable) {
            $this->query("drop table {$tempTable}");
        }
    }

    // 統計情報の更新。
    // テンポラリテーブルが autovacuum の対象にならないため、大きなテンポラリテーブル作成後は統計情報とのずれが発生する。
    // その後のクエリでパフォーマンス劣化が発生するため、このメソッドを使用して統計情報を更新することを推奨する。
    function analyze($tableName = '')
    {
        $this->query("ANALYZE {$tableName}");
    }

    //************************************************
    //    バックアップ/リストア
    //************************************************
    // データベースのバックアップ/リストア処理
    function backup($fileName = "")
    {
        // pg_dump では、パラメータで接続ユーザーは指定できるが、パスワードは指定できない。
        // 実行した時点でパスワードを聞いてくるのだが、system()による実行の場合、それでは困る
        // （ハング状態になってしまう。）
        // 解決方法としては、以下の2つがある。
        // 最初は(2)の方法を使用していたが、なぜか途中からVAIOでうまくいかなくなった（ハング
        // してしまう）ため、(1)の方法を使うように変更した。
        // (1) Postgres\data\pg_hba.conf にて以下のような指定がされていると、ローカルからの
        //     postgresユーザーでの接続はパスワード不要になる。
        //            host    all         postgres    127.0.0.1/32          trust
        // (2) パスワードファイルを作り、そこからのリダイレクトでパスワードを指定する。
        //         // パスワードファイルを作る。
        //        if (file_exists(GEN_TEMP_DIR . "ForGenesissBackup")) {
        //            $fp = fopen(GEN_TEMP_DIR . "ForGenesissBackup", "w");
        //            fseek($fp);                        // ポインタを先頭へ。ファイル作成の場合warningになる
        //        } else {
        //            $fp = fopen(GEN_TEMP_DIR . "ForGenesissBackup", "w");
        //        }
        //        fputs($fp, PASSWORD);
        //        fclose($fp);
        //         if (!$isRestore) {
        //            // バックアップ
        //            // -Wがポイント。これを指定するとパスワード指定が強制される
        //            $command = "\"" . GEN_POSTGRES_BIN_DIR . "pg_dump\" -f " . GEN_TEMP_DIR . $fileName . " -Fc -U " . GEN_POSTGRES_USER . " -h " . GEN_POSTGRES_HOST . " -p " . GEN_POSTGRES_PORT . " -W Genesiss ";
        //            $command .= " < " . GEN_TEMP_DIR . "ForGenesissBackup";
        //            system($command);
        //            ・・・
        //        } else {
        //            // リストア
        //            ・・・
        //            $command = "\"" . GEN_POSTGRES_BIN_DIR . "pg_restore\" -d Genesiss -U " . GEN_POSTGRES_USER . " -h " . GEN_POSTGRES_HOST . " -p " . GEN_POSTGRES_PORT . " -W -c " . $fileName;
        //            $command .= " < " . GEN_TEMP_DIR . "ForGenesissBackup";
        //            system($command);
        //            ・・・
        //        }
        //         //一時ファイルを消す
        //        unlink(GEN_TEMP_DIR . "ForGenesissBackup");

        if ($fileName == "") {
            $filePathName = tempnam(GEN_TEMP_DIR, "");
        } else {
            $filePathName = GEN_TEMP_DIR . $fileName;
        }
        
        // バックアップ
        // 2009でオプションを追加。
        //      「-b」 ラージオブジェクトもバックアップ。PostgreSQL 8.2以降はデフォルト動作。
        //      ※「-o」（oidもバックアップ）は使用しないこと！
        //          リストアしたときにラージオブジェクトのoidが変わってしまうことがあるが、その場合、各テーブルのoid型の値は自動更新される。
        //          しかし-oが指定されているとoid列が更新されないため、画像が扱えなくなる。
        $command = "\"" . GEN_POSTGRES_BIN_DIR . "pg_dump\" -f " . $filePathName . " -b -Fc -U " . GEN_POSTGRES_USER . " -h " . GEN_POSTGRES_HOST . " -p " . GEN_POSTGRES_PORT . " " . GEN_DATABASE_NAME;

        // 非同期実行にしたければ出力をリダイレクトする
        //$command .= " > " . GEN_TEMP_DIR . "result";
        // バックアップ実行
        $result = 1;
        system($command, $result);

        if ($result == 0) {
            // 成功
            return $filePathName;
        } else {
            // 失敗
            return false;
        }
    }
    
    function restore($filePathName)
    {
        // リストア
        // 非同期実行にしたければ出力をリダイレクトする
        //$command .= " > " . GEN_TEMP_DIR . "result";
        // バックアップファイルをテンポラリフォルダに移動
        copy($filePathName, GEN_TEMP_DIR . basename($filePathName));

        // 2009でリストアの方式を変更。
        //  既存DBへのリストアの際には、 dropdb ⇒ createdb ⇒ pg_restore （もしくは dropdb ⇒ pg_restore -C）するのが
        //  通常のやり方。
        //  しかしジェネシスではこの方法はとりにくい。
        //   ・dropdbするにはデータベースへの接続をすべて切断しなければならない。その処理が難しい。
        //   ・dropdbした後でエラーが発生したときのことを考えると怖い。
        //  そのため、08iではdropdbせずに、単純に上書きリストアしていた。
        //  その方法でもリストア自体は成功することが多いが、大抵の場合は終了ステータスがエラーになるので、
        //  リストア自体が失敗するような深刻なエラーとの区別がつかないという問題があった。
        //  （08iではエラーが発生した場合、一応メッセージは出すが正常終了として扱っていた。ユーザーにしてみれば
        //   成功したのか失敗したのかわかりにくかった）
        //  09iではリストアのやり方を変更した。
        //  まずダミーのデータベースを作ってそこへリストアする。この段階でエラーが発生したら失敗とする。
        //  （空のデータベースへのリストアで出るエラーは、バックアップファイル不正などの深刻なエラーなので）
        //  正常にリストアできたら、今度は本番データベースにリストアする。その際に発生したエラーや警告は無視する。
        //  ただし接続が切れているかどうかわからない状態でリストアしている以上、既存オブジェクトが残ってしまう可能性はある
        //  （既存データにはあるがリストアデータにはないテーブルが、リストア後も残ってしまうなど）
        // ダミーデータベースの名前を決める
        $tempDbName = GEN_DATABASE_NAME . "_" . Gen_String::makeRandomString(10);
        $result = 1;

        // ダミーデータベースの作成
        //  -Tは、createdbの際のテンプレートとなるデータベースを指定するスイッチ。
        //  「template0」はPostgresがデフォルトで持っている、まっさらな状態のテンプレート。これを指定しておくことで
        //  オブジェクトコンフリクトなどのエラーが発生する心配がなくなる。
        $command = "\"" . GEN_POSTGRES_BIN_DIR . "createdb\" -T template0 -E UTF-8 -U " . GEN_POSTGRES_USER . " -h " . GEN_POSTGRES_HOST . " -p " . GEN_POSTGRES_PORT . " {$tempDbName}";
        exec($command);     // 「CREATE DATABASE」が出力されるのを防ぐため、systemではなくexecを使用
        // ダミーデータベースへのテストリストア
        //  まっさらなデータベースへのリストアなので、バックアップファイル不正などの深刻な問題がない限り、エラーも警告も出ないはず。
        //  ※ 失敗する可能性があるのは、もとのDBにあるユーザーが読み込み側DBにないとき。pg_logを参照し、必要に応じて createuserする。
        //  -cスイッチをつけるとエラーが発生することがあるので注意
        $command = "\"" . GEN_POSTGRES_BIN_DIR . "pg_restore\" -U " . GEN_POSTGRES_USER . " -h " . GEN_POSTGRES_HOST . " -p " . GEN_POSTGRES_PORT . " -d {$tempDbName} " . GEN_TEMP_DIR . basename($filePathName);
        system($command, $result);

        // ダミーデータベースを消しておく
        $command = "\"" . GEN_POSTGRES_BIN_DIR . "dropdb\" -U " . GEN_POSTGRES_USER . " -h " . GEN_POSTGRES_HOST . " -p " . GEN_POSTGRES_PORT . " {$tempDbName}";
        exec($command);

        // テストリストアが失敗していればここで終了
        if ($result != 0)
            return false;   // 失敗

        // ラージオブジェクトの削除
        //  あらかじめ既存のラージオブジェクトを削除しておかないと、復元失敗することがある
        $this->query("delete from pg_largeobject");

        // 本番データベースへのリストア
        //  ここではエラーや警告が出ても無視する
        $command = "\"" . GEN_POSTGRES_BIN_DIR . "pg_restore\" -c -U " . GEN_POSTGRES_USER . " -h " . GEN_POSTGRES_HOST . " -p " . GEN_POSTGRES_PORT . " -d " . GEN_DATABASE_NAME . " " . GEN_TEMP_DIR . basename($filePathName);
        // 非同期実行にしたければ出力をリダイレクトする
        //$command .= " > " . GEN_TEMP_DIR . "result";

        // リストア実行
        system($command, $result);

        //$this->query("COMMIT;");
        // vacuum
        $this->query("vacuum analyze");

        // テンポラリファイルを削除。ただし元ファイルが最初からテンポラリディレクトリ
        // にあったときは削除しない
        if (strtolower(trim(GEN_TEMP_DIR . basename($filePathName))) != strtolower(trim($filePathName)))
            unlink(GEN_TEMP_DIR . basename($filePathName));

        return true;    // 成功
    }

    //************************************************
    //    sqlファイル実行
    //************************************************
    // sqlファイルの処理
    static function executeQueryFile($filePath, $fileName)
    {
        // PostgreSQLのインストール先のbinディレクトリを指定する。
        $command = "\"" . GEN_POSTGRES_BIN_DIR . "psql\" -f " . $filePath . $fileName . " -U " . GEN_POSTGRES_USER . " -h " . GEN_POSTGRES_HOST . " -p " . GEN_POSTGRES_PORT . " -d " . GEN_DATABASE_NAME;

        // 実行
        $result = 1;
        // execはsystemとは異なり、標準出力に結果を吐き出さない
        exec($command, $dummy, $result);
        if ($result == 0) {
            // 成功
            return true;
        } else {
            // 失敗
            return false;
        }
    }

}

/**
 * クエリ結果リソースのイテレータ
 */
class Gen_Db_ResourceIterator implements Iterator
{

    var $pos = 0;
    var $res = null;
    var $numRows = 0;

    function __construct($res)
    {
        if ($res !== false) {
            $this->res = $res;
            $this->numRows = pg_num_rows($res);
        }
    }

    function current()
    {
        return pg_fetch_assoc($this->res, $this->pos);
    }

    function key()
    {
        return $this->pos;
    }

    function next()
    {
        $this->pos++;
    }

    function rewind()
    {
        $this->pos = 0;
    }

    function valid()
    {
        return ($this->pos < $this->numRows);
    }

}
