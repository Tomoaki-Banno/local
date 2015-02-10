<?php

// 構成表の最大深度。これ以上の階層はエラーとなる。
// これを変更するときは、Logic_Mrp::seiban() で設定している値も変更すること
define("MAX_LC", 30);

class Logic_Bom
{

    //************************************************
    // LLC計算
    //************************************************
    // 品目マスタのローレベルコード(LLC)を再計算する。
    // 構成表を元に計算する。構成表に出てこない品目はLLCを0とする
    //
    // 階層の深さの最大値を超えた場合、構成にループがあったと判断して処理を中止する。
    // （完全クローズドループについては別途チェック。後のコメント参照）
    // 戻り値は、正常に計算できたらTRUE、構成ループ発生により中断、もしくはエラー発生したときはFALSE
    //
    // ロジックとしてはBOMの1レコードずつを順次展開していくほうが分かりやすいが、それだと
    // 構成品目数が増えたときにかなり時間がかかる恐れがある。以下では全品目を一括処理する
    // ようにすることにより、パフォーマンスを上げている

    static function calcLLC()
    {
        global $gen_db;

        //------------------------------------------------------
        // 構成上で最上位の品目（llc = 0）をリストアップしてテンポラリテーブルに入れる
        //------------------------------------------------------

        $query = "
        create temp table temp_bom_calc (item_id, lc) as
        select
            item_id, 0
        from
            (select item_id from bom_master group by item_id) as parent_item_list
            left join (select child_item_id from bom_master group by child_item_id) as child_item_list
                on parent_item_list.item_id = child_item_list.child_item_id
        where
            child_item_id is null
        ";
        $gen_db->query($query);

        //------------------------------------------------------
        // 構成表をレベルバイレベル展開し、結果をテンポラリテーブルに挿入していく
        //------------------------------------------------------

        $lc = 0;
        while (true) {

            $query = "
            insert into temp_bom_calc (item_id, lc)
            select
                bom_master.child_item_id, {$lc}
            from
                temp_bom_calc
                inner join bom_master on temp_bom_calc.item_id = bom_master.item_id
            where
                temp_bom_calc.lc = ({$lc} - 1);
            ";

            $gen_db->query($query);

            // 展開終了の判断。
            $query = "select count(*) from temp_bom_calc where lc = {$lc}";
            if ($gen_db->queryOneValue($query) == 0) {
                break;
            }

            $lc++;

            // 階層限度を超えた（構成がループしている）かどうかの判断。
            if ($lc > MAX_LC) {
                return false;
            }
        }

        //------------------------------------------------------
        // 各品目のllcを決めて別のテンポラリテーブルに入れる。
        //------------------------------------------------------
        // その品目が出現する一番下の階層がその品目のllcとなる。
        // 構成表に出てこない品目は、llcを0としていることに注意。
        // update に fromを使うやり方はPostgresの独自構文。

        $query = "
        update
            item_master
        set
            llc = COALESCE(t1.llc, 0)
        from
            item_master as t0
            left join (
                select
                    item_id, max(lc) as llc
                from
                    temp_bom_calc
                group by
                    item_id
            ) as t1 on t0.item_id = t1.item_id
        where
            item_master.item_id = t0.item_id
            /* 以下の条件の有無で処理速度が相当違う */
            and item_master.llc <> COALESCE(t1.llc, 0)
        ";

        $gen_db->query($query);

        //------------------------------------------------------
        // 完全クローズドループのチェック
        //------------------------------------------------------
        // 構成ループのチェックは上のLLC計算の際にも行っているが、完全クローズドループで、
        // しかもループ内の品目が構成表の他の部分に出現しないケースは発見できない。
        // 完全なクローズドループというのは以下のようなパターン。
        //   品目A
        //   + 品目B
        //     + 品目C
        //       + 品目A  ← 同じ品目が再登場してループになっているが発見できない
        // ※ 以下のように「入り口」がある場合は完全なクローズドループではない。
        //   品目A
        //   + 品目B
        //     + 品目C
        //       + 品目B  ← 品目Aが入り口になっているためループチェックが働く。
        // 完全クローズドループが発見できないのは、最上位品目を特定できないため。
        // LLC計算の際には、親品目をもたない品目を最上位としてリストアップし、そこを基点としている。
        // 完全クローズドループになっていると最上位が見つからないため、そのツリーは計算が行われず、
        // 結果としてループも発見されない。
        // 以下の部分で、そのようなループの存在をチェックしている。
        // LLC = 0 なのに親品目を持つ品目があれば、それはループである。
        // LLC = 0 になるのは、親品目を持たない（最上位）か、構成表に登録されていない品目か（この場合も
        // 親品目はないはず）、クローズドループになっているためLLC計算が行われなかったかのいずれか
        // であるため。
        // ※この上のセクションで、LLC計算に含まれなかった品目はすべてLLC=0にしていることに注意
        // LLC = 0なのに子品目として登場する品目があるかどうかをチェック
        // 品目数が多いときにも速いSQLを求め、いろいろなパターンを試して以下に落ち着いた。
        // 平和サーバー（2万品目、構成8万件、Pen3 1.5G/Mem 2G）で 0.5sec。
        // ただし最後のWhere句の条件を書く順序を入れ替えただけで 10secもかかるようになる。
        $query = "select item_id from item_master where (item_id in (select child_item_id from bom_master)) and llc=0";

        if ($gen_db->existRecord($query))
            return false;        // クローズドループ発生

        return true;
    }

    //************************************************
    // 品目が構成表に含まれているかどうかを返す
    //************************************************
    static function existBom($item_id)
    {
        global $gen_db;

        $query = "select item_id from bom_master where item_id = {$item_id} or child_item_id = {$item_id}";
        return $gen_db->existRecord($query);
    }

    //************************************************
    // 構成表の展開
    //************************************************
    // 構成表の展開を行い、ツリー内の品目を一覧にしてテーブルに挿入する。
    // 親品目コードを渡すと、下位品目と員数のリストが 一時テーブル temp_bom_expand に入る。
    //
    // 員数は、引数で指定した品目から見ての数字。たとえば 親A - 子B(員数2) - 孫C(員数2)で、
    // 引数$itemIdに親Aを指定した場合、孫Cの員数は、子Bからみた員数(2)ではなく、親Aから
    // みた員数(4)である。
    // ただし第4引数をtrueにすれば、階層ごとの員数になる（上の例では孫Cの員数は2）。
    //
    // 定数定義している階層の深さの最大値を超えた場合、構成にループがあったと判断して処理を中止する。
    // 戻り値は、正常に計算できたらTRUE、ループ発生により中断、もしくはエラー発生したときはFALSE。
    //
    // 異なる階層で同一品目が出てくるような構成の場合は、結果表の中でも同一品目が複数回出てくることに注意。
    // 必要に応じて group by して使うこと。
    //
    // item_code_key にはツリー先頭からその品目までの経路上の品目のseq（構成登録で指定した、親品目内での子品目の順）4桁 + 品目ID 8桁がつながった形で入る。
    // たとえば
    //   000000000001
    //       000000000002
    //       000100000003
    //           000000000004
    // のようなツリーの場合、最下位のところの item_code_key は「000000000001000100000003000000000004」となる。
    // この項目は構成表の表示順を決めるためのもので、この項目をorder byする形で取り出せば、
    // イメージどおりの構成表ができあがる。
    //
    // 第5引数($realChildOnly)
    //      trueの場合、標準手配先が「注文」「外製（支給なし）」であると子品目を無視する。
    //      見積書・原価リストはtrue。構成表Excelはfalse。
    //      2007iではすべてfalseの状態（常に子品目を展開）だった。

    static function expandBom($itemId, $quantity, $reverse, $notAccum, $realChildOnly)
    {
        global $gen_db;

        // 最上位階層（LC = 0）
        $query = "
        select
            item_id
            /* このcastがないと、quantity が整数だったときにカラムが整数型になってしまい、その後の計算で小数が出てきても切り捨てられてしまう。*/
            ,cast({$quantity} as numeric) as quantity
            ,0 as lc
            ,lpad(cast(item_id as text), 10, '0') as item_code_key
        from
            item_master
        where
            item_id = {$itemId}
        ";

        // 1セッション中で同じテーブルを複数回作成する可能性があるときは、create temp table文ではなくこのメソッドを使う
        $gen_db->createTempTable("temp_bom_expand", $query, true);

        // 2階層目以下を展開
        $lc = 1;
        while (true) {
            $query = "
            insert into temp_bom_expand (item_id, quantity, lc, item_code_key)
            select
                bom_master." . ($reverse ? "item_id" : "child_item_id") .
                    // 員数は引数$notAccumがtrueなら直接の親に対する員数、false（デフォルト）なら最上位品目に対する員数
                    ($notAccum ? ",bom_master.quantity" : ",temp_bom_expand.quantity * bom_master.quantity") . "
                ,{$lc}
                ,item_code_key || lpad(coalesce(cast(seq as text),''), 4, '0') || lpad(cast(" . ($reverse ? "bom_master.item_id" : "child_item_id") . " as text), 8, '0')
            from
                temp_bom_expand
                inner join bom_master on temp_bom_expand.item_id = bom_master." . ($reverse ? "child_item_id" : "item_id") .
                    // $realChildOnly が true のときは、内製・外製支給ありの場合のみ子品目展開する
                    ($realChildOnly ?
                            " inner join item_order_master on temp_bom_expand.item_id = item_order_master.item_id " .
                            "   and item_order_master.line_number = 0 " .
                            "   and item_order_master.partner_class in (2,3) " : "") .
                    " where
                temp_bom_expand.lc = " . ($lc - 1);

            $gen_db->query($query);

            $query = "select item_id from temp_bom_expand where lc = {$lc}";
            if (!$gen_db->existRecord($query)) {
                break;
            }

            $lc++;
            if ($lc > MAX_LC) {
                // 階層の深さが規定値を超えた
                return false;
            }
        }

        return true;
    }

    //************************************************
    // ダミー品目をスキップした構成表の作成
    //************************************************
    // ダミー品目をスキップした構成表を一時テーブルとして作成する。
    //
    // 基本的には bom_master と同じだが、子品目がダミー品目である場合はその子品目を含めず、
    // さらにその子品目を取得する。
    // 員数は指定した品目から見てのもので、ダミー品目の分も含む。
    // また、同じ品目が複数回出てくる場合は1レコードにまとめられる。
    //
    // たとえば
    //    dummy_a(ダミー品目)
    //        item_b（員数2）
    //        dummy_c (ダミー品目、員数2)
    //            dummy_d (ダミー品目、員数2)
    //              　item_b（員数1）
    //                item_e（員数2）
    // の場合、
    // 結果は item_b 員数6(2回出てくる分の合計。2+4) と、item_e 員数8 の2レコードとなる。
    //
    // オーダー時の order_child_item や use_plan の登録などに使用。

    static function createTempRealChildItemTable($itemId)
    {
        global $gen_db;

        // 最上位階層
        $query = "
        select
            bom_master.item_id
            ,bom_master.child_item_id
            -- このcastがないと、quantityが整数だったときにフィールドが整数型になってしまい、
            -- その後の計算で小数が出てきても切り捨てられてしまう。
            ,cast(bom_master.quantity as numeric) as quantity
            ,0 as lc
            ,coalesce(item_master.dummy_item, false) as child_is_dummy_item
        from
            bom_master
            inner join item_master on bom_master.child_item_id = item_master.item_id
        where
            bom_master.item_id = {$itemId}
        ";

        // 1セッション中で同じテーブルを複数回作成する可能性があるときは、create temp table文ではなくこのメソッドを使う
        $gen_db->createTempTable("temp_real_child_item_pre", $query, true);

        // ダミー品目があればさらにその下階層を展開
        $lc = 0;
        while (true) {
            $query = "select * from temp_real_child_item_pre where child_is_dummy_item and lc = {$lc}";
            if (!$gen_db->existRecord($query)) {
                break;
            }

            $lc++;
            if ($lc > MAX_LC) {
                // 階層の深さが規定値を超えた
                return false;
            }

            $query = "
            insert into temp_real_child_item_pre (item_id, child_item_id, quantity, lc, child_is_dummy_item)
            select
             	temp_real_child_item_pre.child_item_id as item_id
             	,bom_master.child_item_id
             	,temp_real_child_item_pre.quantity * bom_master.quantity as quantity
                ,{$lc} as lc
                ,coalesce(item_master.dummy_item, false) as child_is_dummy_item
            from
                temp_real_child_item_pre
                inner join bom_master on temp_real_child_item_pre.child_item_id = bom_master.item_id
                inner join item_master on bom_master.child_item_id = item_master.item_id
            where
                temp_real_child_item_pre.child_is_dummy_item
                and lc = " . ($lc - 1) . "
            ";
            $gen_db->query($query);
        }

        // すべてのダミー品目を消す
        $query = "delete from temp_real_child_item_pre where child_is_dummy_item";
        $gen_db->query($query);

        // 親品目はすべて指定された品目とする
        $query = "update temp_real_child_item_pre set item_id = '{$itemId}'";
        $gen_db->query($query);
        
        // 同じ品目をひとつにまとめて、結果テーブルを作成
        $query = "        
            select
                item_id
                ,child_item_id
                ,sum(quantity) as quantity
                ,max(lc) as lc
                ,case when max(case when child_is_dummy_item then 1 else 0 end) = 1 then true else false end as child_is_dummy_item
            from
                temp_real_child_item_pre
            group by
                item_id
                ,child_item_id
        ";
        $gen_db->createTempTable("temp_real_child_item", $query, true);

        return true;
    }

}