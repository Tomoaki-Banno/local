<?php

//  採番テーブルアクセスロジック
//
// ユニークな順序値を採番する必要がある場合、もっとも簡単なのはシーケンスを使用すること。
// ただし次のような場合はシーケンスが使えない。
//    ・欠番が許容されない場合
//          シーケンスはrollbackすると欠番が発生する。
//          また、WALを使用したバックアップやレプリケーションでsub側を使用した場合に自動的に番号が進むことがある。
//    ・番号に意味を持たせたい場合（年月 + 月内の連番　など）
//    ・自動採番のほか、手動でも番号を指定したい場合
//        手動指定された場合にはsetvalする必要があるが、シーケンスにはロックがないので、setval時の競合回避が難しい。
//        また手動指定だと、シーケンスの範囲を超える非常に大きい値が指定されたときの対処が難しい。
// 上記のような場合は、この採番ロジックを使用する。
//
// 採番テーブル（number_table）のカラムはtext型であるが、これは10桁を超えるような大きな値を扱えるようにするため。
// 値としては数値しか扱えないことに注意。
// 文字を含む値の場合、利用側で工夫する必要がある（例：受注番号）

class Logic_NumberTable
{

    //************************************************
    // 採番テーブルから次の値を読み出す。
    //************************************************
    // 第2引数は外部で値が指定されたとき用。これが指定されている場合は・・
    //      その値がそのまま戻り値となる。
    //      その値が採番テーブルの現在値より大きければ、その値を新たな現在値とする。

    static function nextval($numberName, $setVal)
    {
        global $gen_db;

        $gen_db->begin();

        // 現在値を取得。
        // for update により他トランザクションとの競合を防いでいる。
        //  （このトランザクションが終了するまで、他のトランザクションはこのレコードにアクセス(insert/update/select for update)できない。
        //   ただし単純select は可能なので要注意。採番はかならずこのロジックを使うか、このロジックと同じ手順で行う必要がある。）
        $query = "select curr_number from number_table where number_name = '{$numberName}' for update";
        $val = $gen_db->queryOneValue($query);

        //  採番テーブルには該当numberNameのレコードが存在していることが前提。
        //  （採番の矛盾を避けるため、自動レコード作成は行わない）
        if ($val == "") {
            throw new Exception("number_table に レコード {$numberName} が存在していません。");
        }

        if ($setVal == "") {
            // 第2引数が指定されていない場合：　次の値を現在値としてセット。
            $val = self::_incNum($val);
            $query = "update number_table set curr_number = '{$val}' where number_name = '{$numberName}' ";
            $gen_db->query($query);
        } else {
            if (!is_numeric($val)) {
                throw new Exception("number_tableに非数値が登録されています。");
            }
            if (!is_numeric($setVal)) {
                throw new Exception("number_tableに非数値を登録しようとしています。");
            }

            // 第2引数が指定されている場合：　現在値より大きければ第2引数を新たな現在値としてセット。いずれにせよ戻り値は第2引数
            if ($val < $setVal) {
                $query = "update number_table set curr_number = '{$setVal}' where number_name = '{$numberName}' ";
                $gen_db->query($query);
            }
            $val = $setVal;
        }

        $gen_db->commit();

        return $val;
    }

    //************************************************
    // 年月別連番形式で自動採番
    //************************************************
    // 「prefix」+「年月4桁」+「連番」の形式で自動採番を行う。手動指定にも対応する。
    // これを使用する場合、Modelの_getColumns()の番号カラムで「"lockNumber" => true」の指定が必要であることに注意。
    // 
    // 以下で使用。()内はprefix（gen_config.ymlで変更可能）
    //  ・見積番号(M)
    //  ・受注番号(A)
    //  ・納品書番号(S) 15i
    //  ・請求書番号(B)
    //  ・注文書番号(P) 15i
    //  ・製造指示書 オーダー番号(B)
    //  ・注文書 オーダー番号(C)
    //  ・外製指示書 オーダー番号(D)
    //  ・実績/受入のロット番号(任意prefix) 15i
    //
    // $prefix        自動採番される番号の先頭につけるプレフィックス
    // $numberName    number_table の番号名（number_name）
    // $seqLength     連番の桁数
    // $handNumber    手動指定された番号（自動採番の場合は空文字にする）

    static function getMonthlyAutoNumber($prefix, $numberName, $seqLength, $handNumber = '')
    {
        if ($handNumber != "") {
            // 番号がユーザーによって指定されている場合。
            $preLen = strlen($prefix);
            // 指定された番号が「prefix」 + 「年月4桁」 + 「連番(指定桁数以上)」形式なら、採番テーブルを更新しておく
            if (strlen($handNumber) >= ($preLen + 4 + $seqLength) && substr($handNumber, 0, $preLen) == $prefix
                    && is_numeric(substr($handNumber, $preLen))) {
                $ym = substr($handNumber, $preLen, 4);          // 年月部分
                $seq = (int) substr($handNumber, $preLen + 4);  // 連番部分
                // 採番テーブルに当月分レコードがない場合は、レコードを作成する。
                self::_registNewRecord($ym, $numberName);
                // 指定された番号が既存の最大番号より大きい場合、採番テーブルを更新する
                self::nextval($numberName . $ym, $seq);
            } else {
                // 指定された番号が規定の形式ではないなら、採番テーブルには影響なし。なにもしない
            }
            // 指定値をそのまま受注番号とする
            $number = $handNumber;
        } else {
            // 番号が指定されていない場合（自動採番）
            $ym = date('ym');

            // 採番テーブルに当月分レコードがない場合は、レコードを作成する。
            self::_registNewRecord($ym, $numberName);

            // 採番処理
            $num = self::nextval($numberName . $ym, "");    // 自動採番
            if (strlen($num) >= $seqLength) {
                $number = $prefix . $ym . $num;
            } else {
                $number = $prefix . $ym . substr("0000000000000000000" . $num, -1 * $seqLength);
            }
        }

        return $number;
    }

    static function _registNewRecord($ym, $numberName)
    {
        global $gen_db;

        $query = "select * from number_table where number_name = '{$numberName}{$ym}'";
        if (!$gen_db->existRecord($query)) {
            // 複数トランザクションが同時実行されたときのために例外処理しておく。
            try {
                $query = "insert into number_table (number_name, curr_number) values ('{$numberName}{$ym}', '0')";
                $gen_db->query($query);
            } catch (Exception $e) {
                // 何もしない
                var_dump($e);
            }
        }
    }

    //************************************************
    // 手動指定値の競合チェック処理
    //************************************************
    // 受注番号・注文書番号・オーダー番号など、「ユーザー指定できるが全体としてユニークでなければならない」値について、
    // 同時実行トランザクションによる値の競合を回避するための処理。
    // たとえば受注番号の場合、Entryのvalidatorで received_detailテーブルを調べて既存値チェックを行うが、
    // 既存値チェックからreceived_detailへのinsertまでの間に、同じ値を指定する別のトランザクションが既存値チェックを実行
    // するとチェックをすり抜けてしまうことになる（まだ先行トランザクションによるreceived_detailへの値の登録が行われていないので）。
    // 結果として値が競合する。
    // そこで、ユーザー指定値のユニークチェックを行う場合は、validateの直前にこの処理を行って、値をロックしておく。
    // 同じ値を登録しようとする後発トランザクションは、先行トランザクションが終了するまでここで足止めとなる。
    // この処理を行った場合は、登録処理すべてが終わった段階で、必ず unlockNumber() を実行する必要がある。

    static function lockNumber($numberName, $val)
    {
        global $gen_db;

        // トランザクション開始
        $gen_db->begin();

        // number_table に値ロック用レコードを挿入する。
        // トランザクションブロック内なので、後発トランザクションが同じ値をロックしようとしても重複エラーにはならず、
        // 先行トランザクションの終了待ちとなる。
        $numberNameExt = $numberName . "_lock_" . $val;

        $query = "insert into number_table (number_name, curr_number) values ('{$numberNameExt}', '')";
        $gen_db->query($query);
    }

    // 上記ロックの開放処理。登録処理がすべて終了した時点で実行する。

    static function unlockNumber($numberName, $val)
    {
        global $gen_db;

        // 値ロック用レコードを削除する。
        $numberNameExt = $numberName . "_lock_" . $val;
        $query = "delete from number_table where number_name='{$numberNameExt}'";
        $gen_db->query($query);

        // トランザクション終了
        $gen_db->commit();
    }

    //************************************************
    // 数値のインクリメント（採番用）
    //************************************************
    // 受注番号・注文書番号・オーダー番号は自動採番するが、ユーザー指定もできるため、値が非常に大きくなる可能性がある。
    // PHPの整数型で扱える範囲を超えたときのトラブルを防ぐため、処理を工夫している。

    static function _incNum($val)
    {
        if (!is_numeric($val)) {
            $val = "1";
        } else {
            if ($val < 1000000000) {
                // 整数型の範囲内で扱える場合（PHPの整数型は一般的には約20億だがプラットフォーム依存）
                $val++;
            } else {
                // 整数型の範囲を超える場合
                // 浮動小数点にキャストされると情報落ちが発生するし、浮動小数の範囲も超えるとエラーになるため
                // 文字型として扱う。
                // （組み込み関数bcaddを使えば正確な演算ができるが、PHPコンパイル時にオプションが必要。）
                $len = strlen($val);
                $highVal = substr($val, 0, $len - 9);   // 上位桁
                $lowVal = substr($val, $len - 9, 9);    // 下位9桁（intで扱える）
                $lowVal++;
                if ($lowVal == 1000000000) {
                    $highVal++;
                    $lowVal = 0;
                }
                $val = (string) $highVal . str_pad($lowVal, 9, "0", STR_PAD_LEFT);
            }
        }
        return (string) $val;
    }

}
