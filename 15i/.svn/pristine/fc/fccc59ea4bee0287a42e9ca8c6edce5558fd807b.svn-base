<?php

class Logic_Tax
{

    // 消費税率マスタ未設定の場合の税率を返す。
    // 法令で消費税率が変わったら、それ以降のリビジョンではここの値を変えること。
    // そうすれば、消費税率マスタを登録しなくても、その時期の税率が適用される。
    // ただ、リビジョンアップで運用中のユーザーのデフォルト税率が書き換わってしまうと困る
    // と思うので、リビジョンアップ前に各ユーザーに過去期間も含めた消費税率マスタを登録してもらう必要があるだろう。
    static function getDefaultTaxRate()
    {
        return 0;
    }

    // 消費税率マスタから該当日の適用税率を取得する。
    // マスタ未登録の日付の場合は、上の getDefaultTaxRate() の税率を適用する。
    static function getTaxRate($date)
    {
        global $gen_db;

        $query = "
        select
            tax_rate
        from
            tax_rate_master
        where
            coalesce(apply_date,'1970-01-01') =
                coalesce(
                (select
                    max(apply_date)
                from
                    tax_rate_master
                where
                    apply_date <= '{$date}'::date)
                ,'1970-01-01')
        ";
        $rate = $gen_db->queryOneValue($query);

        // 消費税マスタ未登録の場合
        if ($rate == '')
            $rate = self::getDefaultTaxRate();

        return $rate;
    }

}
