CREATE OR REPLACE FUNCTION gen_round_precision(numeric, text, int)
  RETURNS numeric AS
$BODY$
    select
        case $2
            /* floor関数ではなく trunc関数を使用していることに注意。両者は負数の扱いが異なる。詳細は Gen_Math::round() のコメント2番を参照 */
            when 'floor' then trunc($1, $3)
            /* 標準のceil関数との違いは、丸め桁数を指定できることと、負数の扱いが異なること。詳細は Gen_Math::round() のコメント2番を参照 */
            when 'ceil'  then trunc((case when $1 >=0 then ceil($1 * cast(pow(10, $3) as numeric)) else floor($1 * cast(pow(10, $3) as numeric)) end) / cast(pow(10, $3) as numeric), $3)
              /* (13i以前）*/
              /* when 'ceil' then trunc(trunc($1 * cast(pow(10, $3) as numeric) + case when $1 >=0 then 1 else -1 end * 0.9) / cast(pow(10, $3) as numeric), $3) */
            else round($1, $3)
        end
$BODY$
  LANGUAGE 'sql' VOLATILE;

ALTER FUNCTION gen_round_precision(numeric, text, int) OWNER TO postgres;