<?php

class Gen_Pager
{

    var $data;
    var $nav;
    var $isLastPage;
    var $totalCount;

    // $perPage     1ページあたりの件数。デフォルトは100
    // $showPage    表示するページ。ページは1はじまり。
    function Gen_Pager($query, $colArr, $aggregateType, $perPage, $showPage, $orderbyStr)
    {
        global $gen_db;

        // ページ
        if (!is_numeric($perPage))
            $perPage = 100;
        if (!is_numeric($showPage))
            $showPage = 1;

        // 取得すべき件数範囲を計算する
        // $from, $to は 1はじまり
        $from = $perPage * ($showPage - 1) + 1;
        if ($from <= 1)
            $from = 1;
        $to = $from + ($perPage - 1);
        $getCount = $to - $from + 1;
        $offset = $from - 1;
        
        //------------------------------------------------------
        //  集計行用のSQL Selectを組み立てる
        //------------------------------------------------------

        $selectList = "";
        $sameCellJoinArr = null;
        if (is_array($colArr) && $aggregateType != "") {
            foreach ($colArr as $col) {
                if (isset($col['field']) && $col['field'] != ""
                        && $col['type'] != "label"  // label は fieldにSQLカラム以外がくることがあるので集計を表示しない
                        && $col['type'] != "edit"
                        && $col['type'] != "delete_check"
                        && $col['type'] != "checkbox"
                        && $col['type'] != "literal"
                        && $col['type'] != "textbox"    // textboxがあると製番展開でエラー（SQLにないtextbox列がある）
                        && (!isset($col['visible']) || $col['visible'] === true)
                        && (!isset($col['hide']) || $col['hide'] === false)) {

                    $field = $col['field'];
                    $name = "gen_aggregate_{$col['field']}";
                    
                    // 集計対象のsameCellJoin列
                    if (isset($col['sameCellJoin']) && $col['sameCellJoin']
                            // gen_cross_key は sameCellJoin処理不要。groupbyにより必ずユニークになっているはずだし、parentColumnがあるとSQLエラーになる
                            && $col['field'] != "gen_cross_key"
                            // max/min/distinctはsameCellJoin処理をしなくても集計できる。
                            && ((($aggregateType == "sum" || $aggregateType == "avg") && $col['type'] == "numeric")    
                                || $aggregateType == "count")) {
                        $sameCellJoinArr[] = array($field, isset($col['parentColumn']) ? $col['parentColumn'] : "");
                        $field = "gen_join_{$field}";
                    }
                    
                    // countでスペースも1と数えてしまう現象への対処。castはカラムが数字型だったときのため
                    if (($aggregateType == "count" || $aggregateType == "distinct") && $col['type'] != "numeric") {
                        $field = "case when cast({$field} as text)='' then null else {$field} end";
                    }
                    
                    switch ($aggregateType) {
                        case "sum":
                        case "avg":
                            // gen_cross_key は text に cast されているため sumで集計できない
                            if ($col['type'] == "numeric" && $col['field'] != "gen_cross_key") {
                                $selectList .= ",{$aggregateType}({$field}) over() as {$name}";
                            }
                            break;
                        case "max":
                        case "min":
                            // 文字型データで最大・最小を表示すると、数字を含んだデータや表示側で加工しているデータが不自然な結果になったりする
                            if ($col['type'] == "numeric" || $col['type'] == "date") {
                                $selectList .= ",{$aggregateType}({$field}) over() as {$name}";
                            }
                            break;
                        case "count":
                            $selectList .= ",{$aggregateType}({$field}) over() as {$name}";
                            break;
                        case "distinct":
                            // window関数では distinct を使用できない。
                            // そのため「上から数えた順位（重複なし）」+「下から数えた順位（重複なし）」- 1（自分自身）
                            // というややトリッキーな方法でカウントしている。
                            $selectList .= ",dense_rank() over(order by {$field}) + dense_rank() over(order by {$field} desc) -1 as {$name}";
                            break;
                        default:
                            throw new Exception("不正な aggregateType");
                    }
                }
            }
        }

        // SQLの組み立て
        $trimQuery = trim($query);
        
        // 集計対象のsameCellJoin列が存在するかどうかによって処理を分ける
        if ( $aggregateType == "max" || $aggregateType == "min" || $aggregateType == "distinct" || $sameCellJoinArr === null) {
            // 集計対象のsameCellJoin列が存在しない（もしくは集計方法が max/min/distinctの）場合。
            // 　この場合は単純な集計でよい。また、SQLは2階層でよい。

            // 元SQLにGROUP BYが入っていたときのため、サブクエリにする。（2階層）
            // （元SQLの Select List に count(*) を追加しただけだと、元SQLにGROUP BYがあったときに結果が1行にならない）
            // ちなみに、08iでは高速化のためSELECT句を「select 1」に置き換えていたが、実際はあまり効果がなく、
            // 　かえって不具合の原因になりやすかった。そのため置き換えせずそのまま実行するように変更した。
            $dataQuery = "select *, count(*) over() as gen_totalcount {$selectList} from ({$trimQuery}) as gen_table_count";
            
        } else {
            // 集計対象のsameCellJoin列が存在し、なおかつ集計方法が sum/avg/count（max/min/distinct以外）の場合。
            // 　この場合はsameCellJoinの処理を行うため、window関数を使用する必要がある。
            // 　また、window関数は集計関数の中に入れることができないため、SQLを3階層にする必要がある。
            
            // 2階層目のSelect Listを作成。
            //  sameCellJoin列は、window関数（lag(...) over()）を使用して、一つ前の列の値と同じ場合に値をnullにする。
            $sameCellJoinSelectList = "*";
            foreach($sameCellJoinArr as $col) {
                list($joinCol, $parentCol) = $col;
                $sameCellJoinSelectList .= ",case when {$joinCol} = lag({$joinCol}) over() " 
                    . ($parentCol == "" ? "" : " and {$parentCol} = lag({$parentCol}) over() ") 
                    . " then null else {$joinCol} end as gen_join_{$joinCol}";
            }
            
            // 元SQLにGROUP BYが入っていたときのため、サブクエリにする。
            // また、window関数は他の集計関数の中に入れることができないため、さらにサブクエリにする。（3階層）
            $dataQuery = "select *, count(*) over() as gen_totalcount {$selectList} from (select {$sameCellJoinSelectList} from ({$trimQuery}) as gen_table_count1) as gen_table_count2";
        }
        
        if ($aggregateType == "distinct") {
            // distinct の場合、selectリストで dense_rank() over(order by ...) を使用しているため、
            // 内側SQLの order by が効かなくなってしまう。そのため、ここであらためてorder byする。
            // ag.cgi?page=ProjectDocView&pid=1574&did=220072

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
            
            $dataQuery .= " " . $orderbyStr;
        }
        $dataQuery .=  " limit {$getCount} offset {$offset}";

        // SQLを実行
        $this->data = $gen_db->getArray($dataQuery);

        $totalCount = $this->data[0]['gen_totalcount'];

        if (!is_numeric($totalCount))
            return false;

        if ($totalCount > 0) {
            $lastPage = ((int) ceil($totalCount / $perPage));
        } else {
            $lastPage = 1;
            $showPage = 1;
        }

        //------------------------------------------------------
        //  ナビゲータHTMLの作成
        //------------------------------------------------------

        $separater = "&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;";

        // 先頭へ | 前の○件
        if ($showPage > 1) {
            // リンク有効
            $this->nav .=
                    "<a href='javascript:listUpdate({gen_search_page:1},false)' style='color:#0094ff'>" . _g("先頭へ") . "</a>{$separater}" .
                    "<a href='javascript:listUpdate({gen_search_page:" . ($showPage - 1) . "},false)' style='color:#0094ff'>&lt;&lt; " . sprintf(_g("前の%s件"), $perPage) . "</a>";
        } else {
            // リンク無効（すでに先頭ページ）
            $this->nav .=
                    "<font color='#cccccc'>" . _g("先頭へ") . $separater .
                    "&lt;&lt; " . sprintf(_g("前の%s件"), $perPage) . "</font>";
        }

        // セパレータ
        if ($lastPage == 1) {
            $pageWithLink = "page";
        } else {
            $pageWithLink = "<a href=\"javascript:gen.list.table.pageJump({$lastPage});\" style='color:#0094ff'>page</a>";
        }
        $this->nav .= "&nbsp;&nbsp;&nbsp;[{$pageWithLink} {$showPage} / {$lastPage}]&nbsp;&nbsp;&nbsp;";

        // トータル件数表示
        $this->nav .= sprintf(_g("全%s件"), $totalCount) . "&nbsp;&nbsp;&nbsp";

        // 次の○件 | 最後へ
        if ($showPage < $lastPage) {
            // リンク有効
            $this->nav .=
                    "<a href='javascript:listUpdate({gen_search_page:" . ($showPage + 1) . "},false)' style='color:#0094ff'>" . sprintf(_g("次の%s件"), $perPage) . " &gt;&gt;</a>{$separater}" .
                    "<a href='javascript:listUpdate({gen_search_page:{$lastPage}},false)' style='color:#0094ff'>" . _g("最後へ") . "</a>";
            $this->isLastPage = false;
        } else {
            // リンク無効（すでに最後のページ）
            $this->nav .=
                    "<font color='#cccccc'>" .
                    sprintf(_g("次の%s件"), $perPage) . " &gt;&gt;{$separater}" .
                    _g("最後へ") . "</font>";
            $this->isLastPage = true;
        }

        $this->totalCount = $totalCount;
    }

    function getData()
    {
        return $this->data;
    }

    function getNavigator()
    {
        return $this->nav;
    }

    function isLastPage()
    {
        return $this->isLastPage;
    }

    function getTotalCount()
    {
        return $this->totalCount;
    }

}