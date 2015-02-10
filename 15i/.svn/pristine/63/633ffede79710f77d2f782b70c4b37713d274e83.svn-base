<?php

/**
 * 削除（Delete）系クラスの共通処理を記述した基底クラス
 * 抽象クラス（abstract）。インスタンスを生成できない。
 */
abstract class Base_DeleteBase
{

    var $listAction;
    var $logMsg;
    var $afterEntryMessage;
    var $deleteId;

    //************************************************
    // 抽象メソッド
    //************************************************
    // 子クラスで必ず実装しなければならない。
    //   PHP4の時はコメントアウトすること

    abstract function dataExistCheck(&$form);

    abstract function setParam(&$form);

    abstract function deleteExecute(&$form);



    //************************************************
    // メイン
    //************************************************

    /**
     * リロードチェック
     *
     * リロードかどうかをチェックする関数。フレームワークから呼び出される。
     * convertor / validator の前に呼ばれる(リロード時にvalidatorエラーになるのを防ぐため)。
     * リロードエラー時は、リダイレクト先Action名を返す。
     *
     * @access  public
     * @param   array    $form
     * @return  string   リロードエラー時はリダイレクト先action名、セーフのときは空文字
     */
    function reloadCheck(&$form)
    {
        // Deleteのリロードチェックは、EntryのようにGen_Reload::reloadCheck()を使用する方法は
        // 使えない（ページリクエストIDを埋め込むことができないので）。
        // 削除対象データが存在するかどうかで判断する（実装は子クラスに任せる）。
        // リロードでなくとも対象データが存在しないことはありえるが、処理を中止すべきという点では同じなので問題ない。

        if (!$this->dataExistCheck($form)) {
            $form['gen_restore_search_condition'] = 'true';
            return "action:" . $this->listAction;
        }

        return "";
    }

    /**
     * 削除クラスのメイン処理
     *
     * @access  public
     * @param   array    $form
     * @return  string   処理後のリダイレクト先action名
     */
    // メイン
    function execute(&$form)
    {
        global $gen_db;

        //------------------------------------------------------
        //  クエリ用パラメータをクラス変数に設定（子クラスで実装）
        //------------------------------------------------------
        // 更新許可チェックより先に行う （更新許可エラー時に「$this->listAction」が必要になるため）
        $this->setParam($form);

        //------------------------------------------------------
        //  更新許可のチェック
        //------------------------------------------------------
        if ($form['gen_readonly'] == 'true') {
            $form['gen_restore_search_condition'] = 'true';
            return "action:" . $this->listAction;
        }

        //------------------------------------------------------
        //  削除処理
        //------------------------------------------------------
        $this->deleteExecute($form);

        //------------------------------------------------------
        //　添付ファイルとトークボードの削除
        //------------------------------------------------------
        if (isset($form['gen_action_group']) && $this->deleteId != "") {
            $idCsv = $this->deleteId;

            // 添付ファイル
            $query = "select file_name from upload_file_info where action_group = '{$form['gen_action_group']}' and record_id in ({$idCsv})";
            $fileNameArr = $gen_db->getArray($query);
            if ($fileNameArr) {
                $storage = new Gen_Storage("Files");
                foreach($fileNameArr as $fileName) {
                    $storage->delete($fileName['file_name']);
                }
            }
            $query = "delete from upload_file_info where action_group = '{$form['gen_action_group']}' and record_id in ({$idCsv})";
            $gen_db->query($query);
            
            // トークボード
            $query = "select chat_header_id from chat_header where action_group = '{$form['gen_action_group']}' and record_id in ({$idCsv})";
            $headerIdArr = $gen_db->getArray($query);
            if ($headerIdArr) {
                foreach($headerIdArr as $row) {
                    Logic_Chat::deleteChat($row["chat_header_id"]);
                }
            }
        }

        //------------------------------------------------------
        //  更新後メッセージ
        //------------------------------------------------------
        $form['gen_afterEntryMessage'] = $this->afterEntryMessage;

        //------------------------------------------------------
        // 更新フラグ
        //------------------------------------------------------
        // データ更新が行われたことを示すフラグを立てる。遷移先クラスやtplにおいて、List画面に更新メッセージを表示するか
        // どうかの判断に使われる。
        // $form['gen_hilight_id']でもよさそうだが、そちらは設定されない場合がある（detailなど）
        $form['gen_updated'] = "true";

        //------------------------------------------------------
        //  データアクセスログ
        //------------------------------------------------------
        Gen_Log::dataAccessLog($this->logTitle, _g("削除"), $this->logMsg);

        //------------------------------------------------------
        //  リターン
        //------------------------------------------------------
        $form['gen_restore_search_condition'] = 'true';
        return "action:" . $this->listAction;
    }

}