{include file="common_header.tpl"}

{*************** Javascript ***************}

<script type="text/javascript">
{literal}
    var cal1;
    $(function() {
        cal1 = new YAHOO.widget.Calendar("cal1","cal1Container");

        cal1.title = "{/literal}{gen_tr}_g("休業日を選択してください"){/gen_tr}{literal}";
        cal1.selectEvent.subscribe(entryHoliday, cal1, true);
        cal1.changePageEvent.subscribe(renderCal, cal1, true);
        calendar_setProperty();
        renderCal();
    });

    function calendar_setProperty() {
        var monthArr = ["1月", "2月", "3月", "4月", "5月", "6月", "7月", "8月", "9月", "10月", "11月", "12月"]
        var weekdayArr = ["日", "月", "火", "水", "木", "金", "土"];

        cal1.cfg.setProperty("MONTHS_SHORT", monthArr);
        cal1.cfg.setProperty("MONTHS_LONG", monthArr);
        cal1.cfg.setProperty("WEEKDAYS_1CHAR", weekdayArr);
        cal1.cfg.setProperty("WEEKDAYS_SHORT", weekdayArr);
        cal1.cfg.setProperty("WEEKDAYS_MEDIUM", weekdayArr);
        cal1.cfg.setProperty("WEEKDAYS_LONG", weekdayArr);

        //cal1.cfg.setProperty("pagedate", "12/2006");      // デフォルトで表示される月
        //cal1.cfg.setProperty("selected","12/30/2006-01/03/2007, 12/24/2006");   // デフォルト選択期間
        //cal1.cfg.setProperty("mindate", "12/01/2006");      // 利用可能な期間
        //cal1.cfg.setProperty("maxdate", "01/12/2007");
        //cal1.cfg.setProperty("title", "{/literal}{gen_tr}_g("カレンダー"){/gen_tr}{literal}");             // タイトル
        //cal1.cfg.setProperty("close", true);                // 閉じるボタン
        cal1.cfg.setProperty("iframe", false);           // IE6のバグ対応。省略するとIE6のときのみフレームになる
        //cal1.cfg.setProperty("MULTI_SELECT", true);     // 日付の複数選択
        cal1.cfg.setProperty("SHOW_WEEKDAYS", true);        // 曜日表示
        cal1.cfg.setProperty("LOCALE_MONTHS", "short");   //　月表示（"short", "medium", and "long"）
        cal1.cfg.setProperty("LOCALE_WEEKDAYS", "1char");  // 曜日表示（"1char", "short", "medium", and "long"）
        cal1.cfg.setProperty("START_WEEKDAY", 0);       // 週の初めを何曜日にするか。0が日曜
        //cal1.cfg.setProperty("SHOW_WEEK_HEADER", true); // 週の初めに何週目かを表示
        //cal1.cfg.setProperty("SHOW_WEEK_FOOTER", true); // 週の終わりに何週目かを表示
        cal1.cfg.setProperty("HIDE_BLANK_WEEKS", true); // デフォルト値のfalseでは必ず6週表示になるが、trueにすると最小4週表示になる
        //cal1.cfg.setProperty("NAV_ARROW_LEFT", "left_image_path"); // 左矢印画像
        //cal1.cfg.setProperty("NAV_ARROW_LEFT", "right_image_path"); // 右矢印画像
    }

    function renderCal() {
        //var date1 = cal1.pageDate;
        var date1 = cal1.cfg.getProperty("pageDate");

        if (!date1)
            date1 = new Date();

        var year1 = date1.getFullYear();
        var month1 = date1.getMonth()+1;

        gen.ajax.connect('Master_Holiday_AjaxHolidayRead', {year : year1, month : month1}, 
            function(j){
                cal1.removeRenderers();
                cal1.deselectAll();
                if (j != '') {
                    var holidayLen = j.holiday.length;
                    for(var i=0; i<holidayLen; i++) {
                        var dateParts = j.holiday[i].split("-");
                        var dateStr = dateParts[1] + '/' + dateParts[2] + '/' + dateParts[0];
                        cal1.addRenderer(dateStr, cal1.renderCellStyleHighlight3);
                    }
                } else {
                    alert('{/literal}{gen_tr}_g("データの取得に失敗しました。"){/gen_tr}{literal}');
                }
                cal1.render();
                return;
            });
    }

    // カレンダー選択時イベント
    function entryHoliday() {
        if ({/literal}{$form.gen_isNotEntry|escape}{literal}) return;
        var selDate = cal1.getSelectedDates()[0];
        gen.ajax.connect('Master_Holiday_AjaxHolidayEntry', {selYear : selDate.getFullYear(), selMonth : selDate.getMonth()+1, selDay : selDate.getDate()}, renderCal);
    }
{/literal}
</script>

{*************** CSS ***************}
{literal}
<style TYPE="text/css">
<!--
#main {
    width: 100%;
    min-height: 640px;
}
-->
</style>
{/literal}

{*************** Contents ***************}
<div id="main" align='center'>
<table width="800">
    <tr style="height:7px"><td></td></tr>
    <tr>
        <td align="center">
            <div class="listTitle">
                <span style="color: #475966; font-size:16px; font-weight: bold;letter-spacing: 0.5em;">｜{gen_tr}_g("カレンダーマスタ"){/gen_tr}｜</span>
            </div>
        </td>
    </tr>
    <tr style="height: 35px;"><td></td></tr>
</table>

{if $form.gen_isNotEntry == 'true'}
<span style='font-size:12px'><font color="red">{gen_tr}_g("登録を行う権限がありません。"){/gen_tr}</font></span><br>
{else}
<span style='font-size:12px'>{gen_tr}_g("日付をクリックすると、稼働と休業が切り替わります。"){/gen_tr}</span><br>
{/if}

<form name="form1" method="POST">
<table>
    <tr>
        <td>
        <div class='yui-skin-sam'>
        <div id="cal1Container"></div>
        </div>
        </td>
    </tr>
</table>
</form>

</div>
{include file="common_footer.tpl"}

