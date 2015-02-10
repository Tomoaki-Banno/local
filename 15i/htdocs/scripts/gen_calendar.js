if (!gen) var gen = {};

gen.calendar = {
    cal1: null,
    isInAjax: false,
    
    init: function(containerId, textboxId) {
        if (this.isInAjax) {    // ajax中に次のカレンダーを開くとJSエラーになる
            return;
        }
        
        var container = document.getElementById(containerId);
        var textbox = document.getElementById(textboxId);
        var config = {};

        var containerObj = $(container);
        if (!containerObj.parent().hasClass('yui-skin-sam')) {
            // position:relativeは表示条件欄において縦スクロールした状態でも正しい位置に表示するため。EditListではこれがあると表示が乱れる
            containerObj.wrap("<div class='yui-skin-sam'" + (containerId.substring(0,11) == 'gen_search_' ? " style='position:relative'" : "") + " tabindex=-1>");
        }
        if (this.cal1 !== null) {
            // 前のカレンダーを閉じる
            this.close();            
        }
        
        // テキストボックス内に日付文字列があれば、その日を当日として
        // カレンダーを初期化
        if (gen.date.isDate(textbox.value)) {
            config.today = gen.date.parseDateStr(textbox.value);
        }
        // カレンダーを新規作成
        this.cal1 = new YAHOO.widget.Calendar('cal1', containerId, config);
        this.cal1.containerId = containerId;
        this.cal1.container = container;
        this.cal1.textbox = textbox;

        this.cal1.container.style.position="absolute";
        this.cal1.selectEvent.subscribe(this.entryHoliday, this.cal1, true);
        this.cal1.changePageEvent.subscribe(this.renderCal, this.cal1, true);
        this._setProperty();

        this.renderCal();
        this.cal1.container.style.zIndex = 9999;    // List全体をResize化したことでカレンダーが隠れるようになった現象への対処
    },
    
    _setProperty: function() {
        var monthArr = ["1月", "2月", "3月", "4月", "5月", "6月", "7月", "8月", "9月", "10月", "11月", "12月"];
        var weekdayArr = ["日", "月", "火", "水", "木", "金", "土"];

        this.cal1.cfg.setProperty("MONTHS_SHORT", monthArr);
        this.cal1.cfg.setProperty("MONTHS_LONG", monthArr);
        this.cal1.cfg.setProperty("WEEKDAYS_1CHAR", weekdayArr);
        this.cal1.cfg.setProperty("WEEKDAYS_SHORT", weekdayArr);
        this.cal1.cfg.setProperty("WEEKDAYS_MEDIUM", weekdayArr);
        this.cal1.cfg.setProperty("WEEKDAYS_LONG", weekdayArr);
        this.cal1.cfg.setProperty("close", true);                // 閉じるボタン
        this.cal1.cfg.setProperty("SHOW_WEEKDAYS", true);        // 曜日表示
        this.cal1.cfg.setProperty("LOCALE_MONTHS", "short");   //　月表示（"short", "medium", and "long"）
        this.cal1.cfg.setProperty("LOCALE_WEEKDAYS", "1char");  // 曜日表示（"1char", "short", "medium", and "long"）
        this.cal1.cfg.setProperty("START_WEEKDAY", 0);       // 週の初めを何曜日にするか。0が日曜
        this.cal1.cfg.setProperty("HIDE_BLANK_WEEKS", true); // デフォルト値のfalseでは必ず6週表示になるが、trueにすると最小4週表示になる
    },

    renderCal: function() {
        this.rendarCalInProgress = true;
        if (this.cal1 === null || this.cal1 === undefined) {    //▼を押した時はthis=gen.calendar。月を切り替えた時はthis=gen.calendar.cal1
            this.cal1 = gen.calendar.cal1;
        }
        var date1 = this.cal1.cfg.getProperty("pageDate");

        if (!date1) {
            date1 = new Date();
        }

        var year1 = date1.getFullYear();
        var month1 = date1.getMonth()+1;
        var cal1 = this.cal1;    // for clouser
        this.isInAjax = true;
        gen.ajax.connect('Master_Holiday_AjaxHolidayRead', {year : year1, month : month1},
            function (j) {
                if (j !== '') {
                    var arr = j.holiday;
                    for (var i in arr) {
                        var dateParts = arr[i].split("-");
                        var dateStr = dateParts[1] + '/' + dateParts[2] + '/' + dateParts[0];
                        cal1.addRenderer(dateStr, cal1.renderCellStyleHighlight3);
                    }
                }
                cal1.render();
                gen.calendar.isInAjax = false;
            });

        this.cal1.render();
        this.cal1.container.style.display = '';
    },

    close: function() {
        this.cal1.destroy();    // これがないと次のカレンダーの一部セルがクリックできなくなることがある
        this.cal1.container.style.display='none';
        this.cal1.container.innerHTML ="";
        this.cal1 = null;
    },

    // カレンダー選択時イベント
    entryHoliday: function() {
        var cal1 = gen.calendar.cal1;    // カレンダーコントロールがthisになっているので、this.cal1 とは書けない
        cal1.container.style.display='none';
        var selDate =cal1.getSelectedDates()[0];
        cal1.textbox.value = gen.date.getDateStr(selDate);
        var cElm = document.getElementById(cal1.containerId.replace('_calendar',''));
        if (cElm.onchange !== null) {
            cElm.onchange();
        }
        $(cElm).change();   // jQueryの.change()で設定したイベント
        gen.window.nextfocus(cElm);
    }
};



gen.dateBox = {

    // 日付入力テキストボックスに今日の日付をセット
    setToday: function(elmName) {
        $('#'+elmName).val(gen.date.getCalcDateStr(0));
        var oc = document.getElementById(elmName).onchange;
        if (oc != null) {
            oc();
        }
    },

    // 日付入力テキストボックスの日付を一日前、もしくは後に
    dayChange: function(elmName, isBack) {
        var elm = document.getElementById(elmName);
        var ds;
      if (!(ds = gen.date.calcDateStr(elm.value, (isBack ? -1 : 1)))) {
        return;
      }
        elm.value = ds;
      if (elm.onchange !== null) {
        elm.onchange();
      }
    },

    // 日付入力テキストボックスのonKeyDownイベント
    onKeyDown: function(elmName) {
      if (!gen.util.isIE) {
        return; // IE Only
      }
      if (event.keyCode === '84') {
        return false;  // 't' onKeyUpイベントでセット
      }
        if (event.keyCode === '37' && event.shiftKey) {this.dayChange(elmName, true); return false;} // '←'
        if (event.keyCode === '39' && event.shiftKey) {this.dayChange(elmName, false); return false;} // '→'
    },

    // 日付入力テキストボックスのonKeyUpイベント
    onKeyUp: function(elmName) {
        if (!gen.util.isIE) return; // IE Only
        if (event.keyCode == '84') {this.setToday(elmName); return false;}  // 't'
        return false;
    },

    // 「mmdd」「yymmdd」「yyyymmdd」「mm-dd」「yy-mm-dd」 を yyyy-mm-dd に変換
    dateFormat: function(elmName) {
        var val = $('#'+elmName).val().replace(/\//g,'-');
        var arr = val.split('-');
        if (arr.length > 3) return;
        if (arr.length > 1) {
            for (var i=(arr.length == 3 ? 1 : 0);i<arr.length; i++) {
                if (arr[i].length > 2) return;
                if (arr[i].length == 1) {
                    arr[i]  = '0' + arr[i];
                }
            }
            val = arr.join('');
        }

        if (val.indexOf('-')!=-1) val = val.replace(/-/g,'');

        // isNumericの前にparseIntしておかないと、先頭が0のとき非数値と判断されてしまう
        if (!gen.util.isNumeric(parseInt(val,10)) || (val.length != 4 && val.length != 6 && val.length != 8)) return;

        var d = new Date();
        var year = d.getFullYear().toString();
        if (val.length==4) {    // mmdd -> yyyymmdd
            val = year + val;
        } else if (val.length==6) {    // yymmdd -> yyyymmdd
            val = year.substr(0,2) + val;
        }
        // yyyymmdd -> yyyy-mm-dd
        val = val.substr(0,4) + '-' + val.substr(4,2) + '-' + val.substr(6,2);
        if (!gen.date.isDate(val)) return;

        $('#'+elmName).val(val);
    },

    // 日付入力テキストボックスのフォーマットのチェック
    checkDateFormat: function(elmName, msg) {
        var val = $('#'+elmName).val();
        if (val!='' && !gen.date.isDate(val)) {
            $('#'+elmName+'_button').focus();
            $('#'+elmName).focus().select();
            alert(msg);
            return false;
        }
    },

    // 日付範囲テキストボックスのフォーマットのチェック
    checkDateFromToFormat: function(elmName, msg) {
        // From日付チェック
        var val = $('#'+elmName+'_from').val();
        if (val!='' && !gen.date.isDate(val)) {
            $('#'+elmName+'_from_button').focus();
            $('#'+elmName+'_from').focus().select();
            alert(msg);
            return false;
        }

        // To日付チェック
        val = $('#'+elmName+'_to').val();
        if (val!='' && !gen.date.isDate(val)) {
            $('#'+elmName+'_to_button').focus();
            $('#'+elmName+'_to').focus().select();
            alert(msg);
            return false;
        }
    },

    // 日付範囲セレクタのプリセットパターンの処理
    // 選択肢を追加・削除・変更するときは、ListBase と function.gen_search_control も変更が必要（'datePattern'で検索）
    setDatePattern: function(name) {
        val = $('#gen_datePattern_'+name+'').val();
        switch(val) {
        case '0':    // なし
            from = ''; to = ''; break;
        case '1':    // 今日
            from = gen.date.getCalcDateStr(0);
            to = from;
            break;
        case '2':    // 昨日
            from = gen.date.getCalcDateStr(-1);
            to = from;
            break;
        case '3':    // 今週
            from = gen.date.getWeekBeginDateStr();
            to = gen.date.calcDateStr(from, 6);
            break;
        case '4':    // 先週
            from = gen.date.calcDateStr(gen.date.getWeekBeginDateStr(), -7);
            to = gen.date.calcDateStr(from, 6);
            break;
        case '5':    // 今月
            d = new Date();
            d.setDate(1);
            from = gen.date.getDateStr(d);
            d.setMonth(d.getMonth() + 1);
            d.setDate(0);
            to = gen.date.getDateStr(d);
            break;
        case '6':    // 先月
            d = new Date();
            d.setDate(1);   // 先にsetDateする。先にsetMonthすると、3/31の1か月前が3/3になってしまう
            d.setMonth(d.getMonth() - 1);
            from = gen.date.getDateStr(d);
            d = new Date();
            d.setDate(0);
            to = gen.date.getDateStr(d);
            break;
        case '7':    // 今年
            d = new Date();
            d.setMonth(0);
            d.setDate(1);
            from = gen.date.getDateStr(d);
            d.setMonth(11);
            d.setDate(31);
            to = gen.date.getDateStr(d);
            break;
        case '8':    // 昨年
            d = new Date();
            y = d.getYear();
            if (y < 2000) y += 1900;    // ブラウザによってはこの調整が必要
            d.setYear(y-1);
            d.setMonth(0);
            d.setDate(1);
            from = gen.date.getDateStr(d);
            d.setMonth(11);
            d.setDate(31);
            to = gen.date.getDateStr(d);
            break;
        case '9':    // 明日
            from = gen.date.getCalcDateStr(1);
            to = from;
            break;
        case '10':    // 来週
            from = gen.date.calcDateStr(gen.date.getWeekBeginDateStr(), 7);
            to = gen.date.calcDateStr(from, 6);
            break;
        case '11':    // 来月
            d = new Date();
            d.setDate(1);
            d.setMonth(d.getMonth() + 1);
            from = gen.date.getDateStr(d);
            d = new Date();
            d.setMonth(d.getMonth() + 2);
            d.setDate(0);
            to = gen.date.getDateStr(d);
            break;
        case '12':    // 来年
            d = new Date();
            y = d.getYear();
            if (y < 2000) y += 1900;    // ブラウザによってはこの調整が必要
            d.setYear(y+1);
            d.setMonth(0);
            d.setDate(1);
            from = gen.date.getDateStr(d);
            d.setMonth(11);
            d.setDate(31);
            to = gen.date.getDateStr(d);
            break;
        case '13':    // 今年度
        case '14':    // 昨年度
        case '15':    // 来年度
            startM = $('#gen_starting_month').val();
            if (isNaN(startM)) {
                startM = 1;
            }
            d = new Date();
            y = d.getYear();
            if (y < 2000) y += 1900;    // ブラウザによってはこの調整が必要
            m = d.getMonth() + 1;
            if (val == '13') {
                if (startM > m) {
                    --y;
                }
            } else if (val == '14') {
                if (startM <= m) {
                    --y;
                } else {
                    y-=2;
                }
            } else {
                if (startM <= m) {
                    ++y;
                }
            }
            d.setYear(y);
            d.setMonth(startM - 1);
            d.setDate(1);
            from = gen.date.getDateStr(d);
            d.setYear(y+1);
            d = gen.date.calcDate(d, -1);
            to = gen.date.getDateStr(d);
            break;
        case '16':    // 今日以前
            from = '';
            to = gen.date.getCalcDateStr(0);
            break;
        default:
            return;
        }
        $('#'+name+'_from').val(from);
        $('#'+name+'_to').val(to);
    }
};