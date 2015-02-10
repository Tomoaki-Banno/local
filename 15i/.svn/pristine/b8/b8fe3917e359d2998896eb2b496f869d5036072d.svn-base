if (!gen) var gen = {};

// **************************************
//  汎用
// **************************************

gen.util = {
    isUnderIE8: !-[1,],

    isUnderIE9: (!-[1,]) || (navigator.userAgent.toLowerCase().indexOf("msie 9.") != -1),

    isUnderIE10: (/*@cc_on!@*/0) !== 0,

    isIE: document.uniqueID !== undefined,

    isGecko: window.sidebar !== undefined,

    isWebkit: navigator.userAgent.indexOf("AppleWebKit") != -1,

    // 数値丸め（小数点以下を桁数指定で丸める。切り捨て）
    round: function(val, place) {
        val = String(val);
        if (!gen.util.isNumeric(val.replace(/,/g, ""))) return val;    // 桁区切りを除去してからisNum
        if (val.indexOf('.') == -1) return val;

        if (place==0) {        // 整数丸め
            val = val.split(".")[0];
        } else if (place==-1) {        // 自然丸め
            if (val.indexOf(".")!=-1) {
                val = val.replace(/0+$/g, "").replace(/\.$/g, "");
            }
        } else {            // 小数点以下桁数指定丸め。toFixed() でもいい
            zeroStr = '';
            for(i=0; i<place; i++) zeroStr += '0';
            under = val.split('.')[1];
            underStr = ((isNaN(under) ? '' : under) + zeroStr).substring(0,place);
            val = val.split(".")[0] + (place==0 ? "" : "." + underStr);
        }
        return val;
    },

    // 数値判断
    //    isNaNだとら空文字・スペース・16進（0x1,&H1）・指数（1e6）等が数値と判断されてしまう
    isNumeric: function(val) {
        if (val===null || val==undefined) return false;
        return val.toString().match(/^[-]?([1-9]|0\.|0$)[0-9]*(\.[0-9]+)?$/);
    },

    // 非数値をゼロにする
    nz: function(val) {
        return (gen.util.isNumeric(val) ? val : 0);
    },

    // トリム
    trim: function(str) {
        if (str === null) {
            return "";
        }
        return str.toString().replace(/^\s+|\s+$/g, "");
    },

    // トリム（全角スペースを含む）
    trimEx: function(str) {
        if (str === null) {
            return "";
        }
        return str.toString().replace(/^(\s|　)+|(\s|　)+$/g, "");
    },

    // 3桁カンマ区切り
    addFigure: function(str) {
        var num = new String(str).replace(/,/g, "");
        while(num != (num = num.replace(/^(-?\d+)(\d{3})/, "$1,$2")));
        return num;
    },

    // 3桁区切りの削除
    delFigure: function(str) {
        return str.replace(/,/g, "");
    },

    // 全角数字を半角数字に変換
    fullNumToHalfNum: function(val) {
        return val.replace(/[．０-９]/g, function (wc) {var f="．。０１２３４５６７８９",h="..0123456789";return h[f.indexOf(wc)];});
    },

    // 全角を2、半角を1と数える文字数カウント
    lengthEx: function(str) {
        var len = 0;
        for (var i = 0; i < str.length; i++) {
            var c = str.charCodeAt(i);
            // Shift_JIS: 0x0 ～ 0x80, 0xa0 , 0xa1 ～ 0xdf , 0xfd ～ 0xff
            // Unicode : 0x0 ～ 0x80, 0xf8f0, 0xff61 ～ 0xff9f, 0xf8f1 ～ 0xf8f3
            len += ((c >= 0x0 && c < 0x81) || (c == 0xf8f0) || (c >= 0xff61 && c < 0xffa0) || (c >= 0xf8f1 && c < 0xf8f4) ? 1 : 2);
        }
        return len;
    },

    bracketToElmValue: function(str) {
        var match = str.match(/\[[^\]]*\]/g);    // [...]にマッチ。gは複数マッチを示すオプション
        if (match == null) return str;

        for(i=0;i<match.length;i++) {
            var matchStr = match[i].toString().replace('[', '').replace(']', '');
            sourceStr = document.getElementById(matchStr).value;
            str = str.replace(match[i], sourceStr);
        }
        return str;
    },

    // 小数を含む値を正確に四則演算する
    //    小数を含む値を四則演算すると誤差が発生することがある。(10 * 10.04 = 100.3999999..   0.3 - 0.2 = 0.099999..  など。ブラウザにより異なる)
    //    必要な結果桁数が決まっていれば toFixed() で四捨五入丸めしてしまうという手もあるが、自然まるめで表示したい場合はこの関数を使う。
    //    桁区切りカンマは呼び出し側で除去しておく。
    decCalc: function(val1, val2, calc) {
        // 整数化して計算
        val1 = String(val1);
        val2 = String(val2);
        var p1 = val1.indexOf('.');
        var p2 = val2.indexOf('.');
        if (p1==-1 && p2==-1) return eval(val1+calc+'('+val2+')');  // val2のカッコは値がマイナスだったときのため
        var rp1, rp2;
        if (p1==-1) {
            rp1 = 0;
        } else {
            rp1 = val1.length - p1 -1;
            val1 = val1.replace('.', '');
        }
        if (p2==-1) {
            rp2 = 0;
        } else {
            rp2 = val2.length - p2 -1;
            val2 = val2.replace('.', '');
        }
        // 乗算以外の場合は桁数を合わせておく必要がある
        if (calc!='*') {
            if (rp1 > rp2) {
                dif = rp1 - rp2;
                for (i=0; i<dif; i++) val2 += '0';
                rp2 = 0;
            } else if (rp1 < rp2) {
                dif = rp2 - rp1;
                for (i=0; i<dif; i++) val1 += '0';
                rp1 = 0;
            } else {
                rp1 = 0;
            }
            if (calc=='/') {
                rp1 = 0;
                rp2 = 0;
            }
        }
        val1 = parseFloat(val1);
        val2 = parseFloat(val2);

        switch (calc) {
        case '+' :v = (val1 + val2);break;
        case '-' :v = (val1 - val2);break;
        case '*' :v = (val1 * val2);break;
        case '/' :v = (val1 / val2);break;
        default:return false;
        }
        return v / Math.pow(10, (rp1 + rp2));
    },

//    dectest: function() {    // OK
//        console.log(String(gen.util.decCalc(10,11,'+'))=="21");
//        console.log(String(gen.util.decCalc(10,11,'-'))=="-1");
//        console.log(String(gen.util.decCalc(10,11,'*'))=="110");
//        console.log(String(gen.util.decCalc(15,10,'/'))=="1.5");
//
//        console.log(String(gen.util.decCalc(10,10.04,'+'))=="20.04");
//        console.log(String(gen.util.decCalc(10,10.04,'-'))=="-0.04");
//        console.log(String(gen.util.decCalc(10,10.04,'*'))=="100.4");
//        console.log(String(gen.util.decCalc(10.5,10,'/'))=="1.05");
//
//        console.log(String(gen.util.decCalc(0.3,0.2,'+'))=="0.5");
//        console.log(String(gen.util.decCalc(0.3,0.2,'-'))=="0.1");
//        console.log(String(gen.util.decCalc(0.3,0.2,'*'))=="0.06");
//        console.log(String(gen.util.decCalc(0.3,0.2,'/'))=="1.5");
//
//        console.log(String(gen.util.decCalc(0.4,0.201,'+'))=="0.601");
//        console.log(String(gen.util.decCalc(0.4,0.201,'-'))=="0.199");
//        console.log(String(gen.util.decCalc(0.4,0.201,'*'))=="0.0804");
//        console.log(String(gen.util.decCalc(0.4,0.02,'/'))=="20");
//    },

    // Cookieの取得
    getCookie: function(name) {
        name += "=";
        var cookie = document.cookie + ";";    // 検索時最終項目で-1になるのを防ぐ
        var start = cookie.indexOf(name);
        if (start != -1) {
            var end = cookie.indexOf(";",start);
            return unescape(cookie.substring(start + name.length, end));
        }
        return "";
    },

    escape: function(str) {
        return $("<pre/>").text(str).html();
    }
};

//**************************************
// 日付
//**************************************

gen.date = {
    // 日付判定
    isDate: function(str) {
        if (!str) return false;

        var arr = str.split('-');
        if (arr.length!=3) {
            arr = str.split('/');
            if (arr.length!=3) return false;
        }

        if (isNaN(arr[0]) || isNaN(arr[1]) || isNaN(arr[2])) return false;
        if (arr[0].length!=4 || arr[1].length>2 || arr[2].length>2) return false;

        if (arr[0] < 1900 || arr[0] > 3000) return false;
        if (arr[1] < 1 || arr[1] > 12) return false;

        var maxDay = 31;
        if (arr[1] == 2) {
            if (((arr[0]%4)==0 && (arr[0]%100)!=0) || (arr[0]%400)==0) {
                maxDay = 29;
            } else {
                maxDay = 28;
            }
        }
        if (arr[1] == 4 || arr[1] == 6 || arr[1] == 9 || arr[1] == 11)
            maxDay = 30;
        if (arr[2] < 1 || arr[2] > maxDay)
            return false;

        return true;
    },

    // 時刻判定
    isTime: function(str) {
        if (!str) return false;

        var arr = str.split(':');

        if (arr.length<2 || arr.length>3 || isNaN(arr[0]) || isNaN(arr[1]) || (arr.length==3 && isNaN(arr[2]))) {
            return false;
        }

        if (arr[0] < 0 || arr[0] > 23)
            return false;
        if (arr[1] < 0 || arr[1] > 59)
            return false;
        if (arr.length==3) {
            if (arr[2] < 0 || arr[2] > 59)
                return false;
        }

        return true;
    },

    // Dateオブジェクト -> 日付文字列（yyyy-mm-dd）
    getDateStr: function(d) {
        var mo = ("0" + (d.getMonth()+1)).slice(-2);    // 0パディング
        var da = ("0" + d.getDate()).slice(-2);
        return d.getFullYear() + '-' + mo + '-' + da;
    },

    // 日付文字列（yyyy-mm-dd） -> Dateオブジェクト　（日付解釈できなければfalse）
    parseDateStr: function(str) {
        var time;
        if (isNaN(time = Date.parse(str.replace("-","/").replace("-","/")))) return false;
        var d = new Date();
        d.setTime(time);
        return d;
    },

    // 日付文字列（yyyy-mm-dd） -> 年・月・日の配列　（日付解釈できなければfalse）
    getDatePart: function(str) {
        if (!this.isDate(str)) return false;
        return str.replace("/","-").replace("/","-").split('-');
    },

    // 今日からn日後（マイナスも可）の日付を日付文字列（yyyy-mm-dd）で返す
    getCalcDateStr: function(addDays) {
        return gen.date.getDateStr(gen.date.calcDate(new Date(), addDays));
    },

    // 日付文字列のn日後（マイナスも可）の日付を日付文字列（yyyy-mm-dd）で返す　（日付解釈できなければfalse）
    calcDateStr: function(str, addDays) {
        var d;
        if (!(d = gen.date.parseDateStr(str))) return false;
        return gen.date.getDateStr(gen.date.calcDate(d, addDays));
    },

    // Dateオブジェクトのn日後（マイナスも可）の日付をDateオブジェクトで返す
    calcDate: function(d, addDays) {
        d.setTime(d.getTime() + (addDays * 3600 * 1000 * 24));
        return d;
    },

    // 今週の始め（日曜日）を日付文字列（yyyy-mm-dd）で返す
    getWeekBeginDateStr: function() {
        d = new Date();
        w = d.getDay();
        return gen.date.getCalcDateStr(-w);
    }
};

//**************************************
// 通信（Ajax）
//**************************************

gen.ajax = {
    isInProgress: false,

    // ここで取得する値（callback funcのパラメータ）はHTMLエスケープされていない。
    //    取得値を単独で表示する場合：
    //            jQuery の .text() を使用する。（HTMLエスケープされる）
    //            【重要】.innerHTML や jQuery の .html() を使用しないこと。
    //
    //    取得値をHTMLタグと組み合わせて表示する場合：
    //            jQuery の .html() を使用することになるが、タグの中に取得値を埋め込む際、必ず
    //            gen.util.escape() する。
    //
    //    取得値をタグ属性値とする場合（.value .text .label、jQuery の .val()で設定）：
    //            エスケープせず、そのまま使用する。
    //
    //    取得値をalert()の表示メッセージに埋め込む場合：
    //            エスケープせず、そのまま使用する。
    //
    //    ※HTMLタグを含む値をAjaxで取得するような処理は極力避ける。
    //      やむを得ない場合、HTMLタグ部分とパラメータ（ユーザー入力値・DB取得値）を別の値として
    //      取得し、パラメータに関しては必ず gen.util.escape() する。
    connect: function(action, postData, func, dialogNotShow) {
        if (dialogNotShow) {}
        else gen.waitDialog.show(_g("しばらくお待ちください..."));

        var pStr = "gen_ajax_token=" + $('#gen_ajax_token').val();
        for (var p in postData) {
            pStr += "&" + p + "=" + encodeURIComponent(postData[p]);
        }
        var succFunc = function(o) {
            if (o.responseText == 'sessionError') {
                location.href = "index.php?action=Login&gen_ajax_error=true";
            } else if (o.responseText == 'tokenError') {
                alert(_g("操作に失敗しました。画面を更新して再実行してください。") + "(" + pStr + ")");
            } else if (o.responseText == 'permissionError') {
                alert(_g("この操作に対するアクセス権がありません。"));
            } else {
                var param;
                if (gen.util.trim(o.responseText) != '') {
                    // &quot を " に変換 してから eval
                    param = JSON.parse(o.responseText.replace(/&quot;/g,'"'));
                    //param = eval("(" + (o.responseText.replace(/&quot;/g,'"')) + ")");
                } else {
                    param = "";
                }
                func(param);
            }
            gen.waitDialog.hide();
            gen.ajax.isInProgress = false;
        };
        var failFunc = function(o) {
            // statusは、リクエストがサーバーに届かなかったときは0、届いたが正常に処理されなかったときは
            // HTTPステータスコードとなる。
            alert(_g("通信エラーが発生しました。") + "\n\nstatus:" + o.status + "(" + o.statusText + ")\naction:" + action + "\nparam:" + pStr);
            gen.waitDialog.hide();
            gen.ajax.isInProgress = false;
        };

        gen.ajax.isInProgress = true;

        YAHOO.util.Connect.asyncRequest(
            'POST' ,
            'index.php?action=' + action ,
            {success: succFunc, failure: failFunc},
            pStr);
    }
};


//**************************************
// ウィンドウ
//**************************************

gen.window = {
    // Enterキーでフォーカス移動（クロスブラウザ版）
    onkeydown: function(e) {
        //  IE8以前の場合は、event.keyCode に任意のキーを設定できるため、EnterをTabキーにすげ替えるという方法が使える。
        //  しかしIE9以降、またIE以外の場合は、event.keyCode は readOnly。
        //  そのためテンプレートで formのsubmitを防止（formタグのonSubmitで）した上で、以下のような方法をとっている
        if (gen.util.isUnderIE8) {
            if (event.keyCode == 13) {event.keyCode = 9;}
            return true;
        }

        if (e.keyCode != 13) return true;
        gen.window.nextfocus(e.target);
        return false;
    },

    // フォーカスを次へ移動（TABキーの動作をエミュレート）
    nextfocus: function(thisElm) {
        if (thisElm == null) return;
        if (thisElm.type == "textarea") return;
        if (document.forms[0] == undefined) return;
        var elms = document.forms[0].elements;
        var flag = false;
        for (i=0;i<elms.length;i++){
            var tagName = elms[i].tagName.toLowerCase();
            var type = elms[i].type;
            if (flag && elms[i].tabIndex != -1 && ((tagName == "input" && type == "text") || tagName == "select") && elms[i].parentNode.style.display != "none") {
                try {    // 対象がdisableだったり、Sliderが閉じていたときのためのハンドリング
                    var id = elms[i].id;
                    elms[i].focus();
                    if (tagName != "select" && elms[i].id == id)    // 後半の条件はIE不具合回避。ag.cgi?page=ProjectDocView&pid=1574&did=212875
                        elms[i].select();
                    return;
                } catch(e) {
                }
            }
            if (elms[i] == thisElm){
                flag = true;
            }
        }
    },

    // ブラウザ表示幅
    getBrowserWidth: function() {
       if ( parent.window.innerWidth ) {return parent.window.innerWidth;}
       else if ( parent.document.documentElement && parent.document.documentElement.clientWidth != 0 ) {return parent.document.documentElement.clientWidth;}
       else if ( parent.document.body ) {return parent.document.body.clientWidth;}
       return 0;
    },

    // ブラウザ表示高さ
    getBrowserHeight: function() {
       if ( parent.window.innerHeight ) {return parent.window.innerHeight;}
       else if ( parent.document.documentElement && parent.document.documentElement.clientHeight != 0 ) {return parent.document.documentElement.clientHeight;}
       else if ( parent.document.body ) {return parent.document.body.clientHeight;}
       return 0;
    },

    // マウスイベント（e）からマウス座標（ドキュメント座標）を取得
    mouseEventToPos: function(e) {
        var pos = {};
        if (e == null) e = window.event;    // IE
        if (e.pageX || e.pageY){
            pos.left = e.pageX;pos.top = e.pageY;
        } else {
            pos.left = e.clientX + (document.body.scrollLeft || document.documentElement.scrollLeft);
            pos.top = e.clientY + (document.body.scrollTop || document.documentElement.scrollTop);
        }
        return pos;
    },

    // ボックス（ダイアログ等）の表示位置とサイズを調整
    //   表示位置とサイズを渡すと、それが画面内に収まるかどうかを判断する。
    //   収まらなければ、位置を調整する。幅・高さが画面サイズを超える場合は、幅と高さを調整する（引数で許可されている場合）。
    //    引数：
    //        params: width, height, top, left。  デフォルトのサイズと位置。これが調整されたものが返る
    //        sizeAdjust: サイズ変更を許可するかどうか。falseなら位置調整のみ。
    adjustBoxPosAndSize: function(params, sizeAdjust) {
        var browserW = gen.window.getBrowserWidth();
        var browserH = gen.window.getBrowserHeight();
        if (sizeAdjust) {
            if (params.width > browserW - 20) {
                params.width = browserW - 20;
            }
            if (params.height > browserH - 20) {
                params.height = browserH - 20;
            }
        }
        if (params.left + params.width > browserW - 10) {
            params.left = browserW - params.width - 10;
        }
        if (params.left < 10) {
            params.left = 10;
        }
        if (params.top + params.height > browserH - 10) {
            params.top = browserH - params.height - 10;
        }
        if (params.top < 10) {
            params.top = 10;
        }
        return params;
    },

    // バルーン系エレメントの表示位置を調整
    //    エレメントと基準点を渡すと、基準点の右下にエレメントを表示した場合に、画面内に収まるかどうかを判断する。
    //    収まらなければ、基準点の左上・右上・左下のうち、表示できそうな位置に移動する。
    //    チップヘルプ・ツールチップ（在庫推移リストなどのバルーン）・コンテキストメニューの表示位置を決めるのに使用する。
    //    adjustBoxPosAndSizeとの違い：
    //      　・エレメントの位置は基準点の右下・左上・右上・左下のいずれかとなる。（必ず基準点の周囲に表示される）
    //      　・エレメントそのものを渡す必要がある。結果としてエレメントの位置が調整される。
    //    引数：
    //        elm        表示するエレメント
    //        pos        座標オブジェクト。left, top
    adjustBaloonElmPos: function(elm, pos) {
        // エレメントのサイズを測るため、仮に表示
        var notExist = (document.getElementById(elm.id)==null);
        if (notExist) document.body.appendChild(elm);
        var width = parseInt(elm.offsetWidth);
        var height = parseInt(elm.offsetHeight);
        if (notExist) document.body.removeChild(elm);

        // ウィンドウ（フレーム）端とエレメントの位置を画面座標で取得
        var elmPos = {};
        var winPos = {};
        var frame = parent.window.document.getElementById('gen_modal_frame');
        if (frame == null) {
            // エレメント位置の座標変換（ドキュメント⇒画面）
            elmPos.left = pos.left - (document.body.scrollLeft || document.documentElement.scrollLeft);
            elmPos.top = pos.top - (document.body.scrollTop || document.documentElement.scrollTop);
            // ウィンドウ端（画面座標）
            winPos.left = 0;
            winPos.right = gen.window.getBrowserWidth();
            winPos.top = 0;
            winPos.bottom = gen.window.getBrowserHeight();
        } else {
            var frame2 = window.document.getElementById('gen_edit_area');
            // エレメント位置の座標変換（ドキュメント⇒画面）
            elmPos.left = pos.left - (document.body.scrollLeft || document.documentElement.scrollLeft)
                - frame2.scrollLeft + frame.parentNode.offsetLeft;
            elmPos.top = pos.top - (document.body.scrollTop || document.documentElement.scrollTop)
                - frame2.scrollTop + frame.parentNode.offsetTop;
            // ウィンドウ端（画面座標）
            winPos.left = frame.parentNode.offsetLeft;
            winPos.right = winPos.left + frame.clientWidth;
            winPos.top = frame.parentNode.offsetTop;
            winPos.bottom = winPos.top + frame.clientHeight;
        }

        var delta = {left:0, top:0};
        // 右側へはみ出す場合
        if (winPos.right <= elmPos.left + width) {
            // 基準点の左右のうち、ウィンドウ端までの距離が広い方に表示
            delta.left = (elmPos.left - winPos.left > winPos.right - elmPos.left ? width - 10 : 0);
        }
        // 下側へはみ出す場合
        if (winPos.bottom <= elmPos.top + height) {
            // 基準点の上下のうち、ウィンドウ端までの距離が広い方に表示
            delta.top = (elmPos.top - winPos.top > winPos.bottom - elmPos.top ? height - 10 : 0);
        }

        // エレメントの位置を移動
        elm.style.left= (pos.left - delta.left) + "px";
        elm.style.top = (pos.top - delta.top) + "px";
    },

    addNewWindow: function(action) {
        var no = 1;
        while($('#gen_newWindow'+no).length!=0) {
            no++;
        }
        var elmId = 'gen_newWindow' + no;
        var initHeight = 400;
        $('#gen_body').prepend("<div class='yui-skin-sam'><div id='"+elmId+"' style='border-style:none'></div></div>");
        var p1 = new YAHOO.widget.Panel(
            elmId,
            {
            draggable: true,
            autofillheight: "body",
            underlay: "none",
            x: (gen.window.getBrowserWidth() / 2) - 250,
            y: (gen.window.getBrowserHeight() / 2) - 200,
            width: '500px',
            height: initHeight+'px',
            zIndex: 999999    // gen_modal.js open() で設定しているモーダルフレームのzIndex(99999)より大きくする
            }
        );
        p1.setHeader("<a href='javascript:gen.window.newWindowAlign(false)' style='color:#000'><img src='img/application-tile-vertical.png'></a>&nbsp;&nbsp;<a href='javascript:gen.window.newWindowAlign(true)' style='color:#000'><img src='img/application-tile-horizontal.png'></a>");
        // 本来は自actionを表示させたいが、IE/FFではiframe内に親と同じURLを表示させると正常に表示されない
        var url = document.URL.substr(0,document.URL.indexOf('?')) + '?action=Menu_Home';
        p1.setBody("<iframe id='iframe_"+elmId+"' src='"+url+"' style='width:98%; height:100%;'></iframe>");
        p1.render();

        // スタイル
        // YUI Panelのスタイルを上書きする
        var titleHeight = 14;
        $('#'+elmId+' .hd')
            .css('height',titleHeight+'px')
            .css('background','#ccc')
            .css('border','none')
            .css('border-style','none')
            .css('overflow','hidden');
        $('#'+elmId+' .bd')
            .css('height',(initHeight-titleHeight-15)+'px')
            .css('background','#ccc')
            .css('border','none')
            .css('border-style','none')
            .css('padding-left','0px')
            .css('padding-right','0px')
            .css('padding-bottom','0px')
            .css('overflow','hidden');

        // リサイズ
        var resize = new YAHOO.util.Resize(elmId, {
            proxy: false,
            handles: ['b','r','l','br','bl'],
            minWidth: 150,
            minHeight: 70
        });
        resize.on('endResize', function() {});
        resize.on("resize", function(args) {
            if (args.width > 0)
                this.cfg.setProperty("width", args.width + "px");
            // パネル内のbody部の高さを調整。これをしておかないと、高さを広げたときに下のほうがonclickに反応しない
            if (args.height > 0)
                this.cfg.setProperty("height", args.height + "px");
        }, p1, true);
        resize.resize();    // いったんリサイズイベント発火。スクロールバーの分だけ高さがはみ出す現象を解消するため
   },

   newWindowAlign: function(isHorizontal) {
        var cnt = $('[id^=iframe_gen_newWindow]').length;
        var bWidth = gen.window.getBrowserWidth();
        var bHeight = gen.window.getBrowserHeight();
        var wWidth = (isHorizontal ? bWidth / cnt : bWidth) - 12;
        var wHeight = (isHorizontal ? bHeight : bHeight / cnt) - 20;
        var top = 5;
        var left = 0;
        $('[id^=iframe_gen_newWindow]').each(function() {
            var panelId = this.id.replace('iframe_','');
            $('#'+panelId+'_c')
            .css('width', wWidth)
            .css('height', wHeight)
            .css('top', top)
            .css('left', left);
            $('#'+panelId)
            .css('width', wWidth)
            .css('height', wHeight - 5)
            .find('.bd')
                .css('width', wWidth-5)
                .css('height', wHeight-40);
            $(this)
            .css('top', 0)
            .css('left', 0)
            .css('width', wWidth)
            .css('height', wHeight-40);

            if (isHorizontal) {
                left += wWidth;
            } else {
                top += wHeight;
            }
        }
        );
   }

};

//**************************************
// ダイアログ
//**************************************

gen.dialog = {
    create: function(parentId, dialogId, left, top, width, height, header, body, posAdjust, sizeAdjust) {
        if (posAdjust) {
            params = gen.window.adjustBoxPosAndSize({left:left, top:top, width:width, height:height}, sizeAdjust);
            left = params.left;
            top = params.top;
            width = params.width;
            height = params.height;
        }
        $('#' + parentId).prepend("<div class='yui-skin-sam gen_dialog'><div id='" + dialogId + "'></div></div>");
        var p1 = new YAHOO.widget.Panel(
            dialogId,
            {
            draggable: true,
            autofillheight: "body",
            x: left,
            y: top,
            width: width + 'px',
            height: height + 'px',
            zIndex: 9000 + $('.gen_dialog').length    // 後から開くダイアログほど大きく。ただしgen_modal.js open() で設定しているモーダルフレームのzIndex(99999)より小さくする
            }
        );
        p1.setHeader(header);
        p1.setBody(body);
        p1.render();
        return p1;
    }
};

//**************************************
// UI関連
//**************************************

gen.ui = {
    // エレメント（jQueryオブジェクト）を有効（enabled）にする
    enabled: function(jo) {
        jo.removeAttr('disabled');
        var cache = jo.attr('tabIndex_cache');
        if (gen.util.isNumeric(cache)) jo.attr('tabIndex', cache);
    },

    // エレメント（jQueryオブジェクト）を無効（disabled）にする
    disabled: function(jo) {
        // tabIndexをキャッシュした上で-1にする（フォーカス移動がそこで停止しないように）
        jo.attr('disabled', 'disabled').attr('tabIndex_cache', jo.attr('tabIndex')).attr('tabIndex', '-1');
    },

    // エレメント（jQueryオブジェクト）のenabled/disabledの切り替え
    alterDisabled: function(jo, disable) {
        if (disable) {
            gen.ui.disabled(jo);
        } else {
            gen.ui.enabled(jo);
        }
    },

    alterDisplay: function(jo) {
        jo.css('display', jo.css('display')=='none' ? '' : 'none');
    },

    onFocus: function(elm, existPlaceholder) {
        elm.style.backgroundColor = '#8dc4fc';
        elm.style.border = '1px solid #7b9ebd';
        var e = $('#'+elm.id+'_placeholder');
        if (e.length!=0) {
            e.css('display','none');
        }
    },

    onBlur: function(elm, isRequire, existPlaceholder) {
        elm.style.backgroundColor = (isRequire ? '#e4f0fd' : '#ffffff');
        var e = $('#'+elm.id+'_placeholder');
        if (e.length!=0 && elm.value=='') {
            e.css('display','');
        }
    },

    onFocusZoom: function(elm, direction, width, height) {
        var jElm = $(elm);
        var pElm = jElm.parent();
        pElm.css('display','').css('height',pElm.height()); // 現在の高さを維持
        var anchor;
        if (direction == 'right') {
            anchor = 'left';
        } else {
            anchor = 'right';
        }
        jElm.css('position','absolute').css(anchor,'0').css('min-width',width + 'px').css('min-height',height + 'px').css('z-index','999');
    },

    onBlurZoom: function(elm) {
        $(elm).css('position','').css('min-width','').css('min-height','');
    },

    initChipHelp: function() {
        // listのlazyloadではセクション表示のたびにこれが実行される。在庫推移リストでの速度低下を避けるためinit未実行のエレメントのみに限定。
        $('.gen_chiphelp').not('.gen_chiphelp_init_done').each(function(){$(this).cluetip({local: true, cluezIndex: 9999, hoverIntent: {interval:250}}).addClass('gen_chiphelp_init_done');});
    }
};

//**************************************
// ピン
//**************************************

gen.pin = function() {
    // private
    var onoff = function(action, name1, name2, turnOn) {
        var o = {
            action_name : action,    // actionにpageModeを追加した値。pageModeは入出庫のclassの区別に使用している
            control_name1 : name1,
            control_name2 : name2
        };
        var name1j = name1.replace('#','\\#');
        var name2j = name2.replace('#','\\#');
        if (turnOn) {
            $('#gen_pin_on_' + name1j).show();
            $('#gen_pin_off_' + name1j).hide();
            o.turnOn = true;
            var e = $('#' + name1j);
            o.control_value1 = e.val();
            if (name2 != '')
                o.control_value2 = $('#' + name2j).val();
        } else {
            $('#gen_pin_on_' + name1j).hide();
            $('#gen_pin_off_' + name1j).show();
        }
        gen.ajax.connect('Config_Setting_AjaxPin', o,
            function(){
            });
    };

    // public
    return {
        turnOn: function(action, name1, name2) {
            onoff(action, name1, name2, true);
        },

        turnOff: function(action, name1, name2) {
            onoff(action, name1, name2, false);
        }
    };
}();

//**************************************
// PostSubmit
//**************************************

// 呼び出し元からFormを渡すこともできる。
//   frm = new gen.postSubmit(document.getElementById('form1'));
//   frm.submit(url);
gen.postSubmit = function(frmObject) {
    this.isCreateElement = (frmObject === undefined);
    this.frmObject = frmObject || document.createElement('form');
    this.frmObject.method = 'post';
};
gen.postSubmit.prototype = {
    add: function(elementname, elementvalue) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = elementname;
        input.value = elementvalue;
        this.frmObject.appendChild(input);
    },

    submit: function(url, targetFrame) {
        if (targetFrame) {
          this.frmObject.target = targetFrame;
        }
        if (url) {
          this.frmObject.action = url;
          this.add('gen_page_request_id', $('#gen_reqid').val());

          if (this.isCreateElement)
              document.body.appendChild(this.frmObject);
          this.frmObject.submit();
          if (this.isCreateElement)
              document.body.removeChild(this.frmObject);
          return true;
        } else {
            return false;
        }
    }
};

// PostSubmitのラッパー
gen.post = function(action, p, frmObject, targetFrame) {
    var f = new gen.postSubmit(frmObject),
    url = window.location.href.split('?')[0] + '?action=' + action;
    if (p) {
        for (var i in p) f.add(i, p[i]);
    }
    f.submit(url, targetFrame);
};

//**************************************
// メニュー関連
//**************************************

gen.menu = {
    // メニューバーのSliderのinit処理
    initMenuSlider: function(showMsg, hideMsg, isOpen) {
        gen.slider.init(
            'gen_menubar',
            'gen_menubar_link',
            showMsg,
            hideMsg,
            isOpen,
            true,
            (document.getElementById('gen_resizeTable') == null ? '' : 'gen.list.table.onSlideFrame'),
            'gen_menubar_switch_icon'
        );
    }
};

gen.myMenu = {
    regist: function (action, page, failMsg) {
        gen.ajax.connect('Config_Setting_AjaxMyMenu', {op: 'reg', action_name: action, page_name: page},
            function(j) {
                if (j.result=='success') {
                    var span = document.getElementById('gen_menu_' + action); // jQueryの$() ではダメ（「&」が入ると正しく処理できない）
                    if (span == undefined) {
                        var elm = $('#gen_myMenu');
                        elm.html(elm.html() + "<span id='gen_menu_" + action + "' class='my_menu' style='margin-right:10px;cursor:pointer'>" + page + "<a class='my_menu_link' href=\"javascript:delMyMenu('" + action + "','" + page + "')\" tabindex='-1' style='margin-left:10px'>☓</a></span>");
                    } else {
                        span.style.display = '';
                    }
                    $('#gen_addMyMenu').css("display", "none");
                    $('#gen_myMenu').sortable("refresh");
                } else {
                    alert(failMsg);
                }
            });
     },

     deleteMenu: function (action, page, thisAction, delMsg, failMsg) {
        if (!confirm(delMsg.replace('%s',page))) return;

        gen.ajax.connect('Config_Setting_AjaxMyMenu', {op: 'delete', action_name: action, page_name: page},
            function(j) {
                if (j.result=='success') {
                    document.getElementById('gen_menu_' + action).style.display = 'none'; // jQueryの$() ではダメ（「&」が入ると正しく処理できない）
                    if (action == thisAction) {
                        $('#gen_addMyMenu').css("display", "");
                    }
                } else {
                    alert(failMsg);
                }
            });
     },

     reset: function (action, allDelMsg) {
        if (!confirm(allDelMsg)) return;
        gen.ajax.connect('Config_Setting_AjaxMyMenu', {op: 'reset'},
            function() {
                location.href='index.php?action='+action+'&gen_search_restore_condition=true';
            });
     },

     sortInit: function () {
        $('#gen_myMenu').sortable({update:
            function(){
                gen.ajax.connect('Config_Setting_AjaxMyMenu', {op: 'sortreg', ids: $('#gen_myMenu').sortable('toArray')},
                    function() {
                    });
            }});
     }
};

//**************************************
// ファイル登録 共通
//**************************************
// ファイル登録、CSVインポート、帳票テンプレ登録で使用
// この部分をカスタマイズする際は、Config_Setting_FileUpload の冒頭の解説をよく読むこと
gen.fileUpload = {
    init: function(url, beforeFunc, afterFunc, afterFuncParam, buttonText, buttonWidth) {
        var divName = 'gen_fileUploadDiv';
        document.write("<div id='" + divName + "'></div>");
        $(function(){
            gen.fileUpload.init2(divName, url, beforeFunc, afterFunc, afterFuncParam, buttonText, buttonWidth);
        });
    },
    init2: function(divName, url, beforeFunc, afterFunc, afterFuncParam, buttonText, buttonWidth, quickUpload) {
        if (gen.util.isUnderIE9) {
            quickUpload = false;    // IE9以前はfileタグの仕様が異なるためquickUpload機能は使用できない
        }
        if (buttonWidth === undefined) {
            buttonWidth = 100;
        }
        if (quickUpload === undefined || quickUpload) {
            quickUpload = true;
            if (buttonText === undefined) {
                buttonText = _g("ファイルを選択");
            }
            iframeWidth = buttonWidth * 1.2;
            iframeHeight = 37;
        } else {
            if (buttonText === undefined) {
                buttonText = _g("アップロード");
            }
            iframeWidth = 400;
            iframeHeight = 80;
        }
        var number = 0;
        var frameName = "gen_fileUploadFrame";
        while($('#' + frameName + number).length > 0) {
            number++;
        }
        frameName += number;
        $('#' + divName).append("<script src='scripts/jquery.upload/jquery.upload.min.js' type='text/javascript'></script>" +
                "<iframe id='" + frameName + "' style='border:0px; width:" + iframeWidth + "px; height:" + iframeHeight + "px; border:0'></iframe>");
        if (quickUpload) {
            // ファイルを選択すると即時アップロードするモード（デフォルト）
            // 透明にしたfileタグの参照ボタンを、登録ボタンの上に重ねている。詳細はConfig_Setting_FileUpload の「クライアント部分」の解説を参照
            var html =
            "<div style='float:right'>" +
            "<div id='fileUploadButtonArea' style='overflow:hidden; position:relative; width:" + buttonWidth + "px; height:25px;  margin: 0 auto'>" +
            "<input type='file' name='uploadFile' id='fileUploadTag' style='height:300px; font-size:300px; right:0; position:absolute; opacity:0.01' onchange=\"parent.gen.fileUpload.doUpload('" + frameName + "','" + gen.util.escape(url) + "','" + gen.util.escape(beforeFunc) + "','" + gen.util.escape(afterFunc) + "','" + gen.util.escape(afterFuncParam) + "')\">" +
            "<input type='button' id='fileUploadSelectButton' value='" + gen.util.escape(buttonText) + "' style='width:100%; height:100%;'>" +
            "</div></div>";
        } else {
            // ファイルを選択後、ボタンを押すとアップロードするモード（CSV）
            var html =
            "<div style='text-align:center'>" +
            "<input type='file' name='uploadFile' id='fileUploadTag' style=''><br>" +
            "<input type='button' style=\"width:" + buttonWidth + "px\" value=\"" + gen.util.escape(buttonText) + "\" onclick=\"parent.gen.fileUpload.doUpload('" + frameName + "','" + gen.util.escape(url) + "','" + gen.util.escape(beforeFunc) + "','" + gen.util.escape(afterFunc) + "','" + gen.util.escape(afterFuncParam) + "')\">";
        }
        if (gen.util.isIE) {
            $('#' + frameName).load(function(){$(this).contents().find('body').html(html)});
        } else {
            $('#' + frameName).contents().find('body').html(html);
        }
    },
    doUpload: function(frameName, url, beforeFunc, afterFunc, afterFuncParam) {
        var frame = $('#' + frameName)
        var cons = frame.contents();
        var fileTag = cons.find('#fileUploadTag');
        if (fileTag.val() == '')
            return;
        if (gen.util.isUnderIE9) {
            var files = fileTag.val();
        } else {
            var files = fileTag.get(0).files;
        }
        var maxSize = parseInt($('#gen_max_upload_file_size').val());
        for(var i=0; i<files.length; i++){
            if (maxSize < files[i].size) {
                var showFileSize = Math.ceil(files[i].size / 1024 / 1024);
                var showMaxSize = Math.ceil(maxSize / 1024 / 1024);
                alert(_g("%1 MBを超えるファイルはアップロードできません。（選択されたファイルのサイズ：%2 MB）").replace('%1', showMaxSize).replace('%2', showFileSize));
                fileTag.val('');
                return;
            }
        }
        if(beforeFunc !== '') {
            f = new Function('files', 'return ' + beforeFunc + "(files)");
            var beforeRes = f(files);
            if (!beforeRes) {
                gen.fileUpload.clearFileTag(fileTag);
                return;
            }
        }
        gen.waitDialog.show();
        url = gen.util.bracketToElmValue(url);
        url += "&gen_ajax_token=" + $('#gen_ajax_token').val();
        frame.css('visibility','hidden');
        // URLのうち、&以降はPOSTデータとして送信する。
        // IE10以前では、URL内の日本語がSJISとして送信されてしまうことへの対処。
        var pos = url.indexOf('&');
        var postData = "";
        if (pos > -1) {
            postData = url.substr(pos+1);
            url = url.substr(0,pos);
        }
        fileTag.upload(url, postData,
            function(res) {
                frame.css('visibility','');
                gen.fileUpload.clearFileTag(fileTag);
                if (res.status == 'storageError') {
                    alert(_g("ファイルストレージの容量が足りません。既存のファイルを削除するか、ジェネシスの契約プランを変更してください。"));
                    gen.waitDialog.hide();
                    return;
                }
                if(afterFunc !== '') {
                    f = new Function('res', 'afterFuncParam', afterFunc + "(res, afterFuncParam)");
                    f(res, afterFuncParam);
                }
                gen.waitDialog.hide();
            }, 'json');
    },
    clearFileTag: function(fileTag) {
        // 消しておかないと次回ファイル選択時にonchangeが働かない
        if (gen.util.isUnderIE10 && !gen.util.isUnderIE9) {
            // IE10ではこのようにしないとダメ
            var parent = fileTag.parent();
            var html = fileTag.parent().html();
            parent.html('').html(html);
        } else {
            fileTag.val('');
        }
    },
    deleteUploadFile: function(file, originalName, afterFunc, afterFuncParam) {
        if (!confirm(_g("%dを削除します。削除したファイルは、バックアップを復元しても元に戻りません。本当に削除しますか？").replace('%d', originalName))) {
            return;
        }
        gen.ajax.connect('Config_Setting_FileUpload', {file : file, 'delete': true},
            function(j) {
                if (j.success && afterFunc !== '') {
                    f = new Function('afterFuncParam', afterFunc + "(afterFuncParam)");
                    f(afterFuncParam);
                }
                if (j.msg != '')
                    alert(j.msg);
            });
    }
};

//**************************************
// 画像登録 共通
//**************************************
// 自社ロゴ、品目画像、プロフィール画像登録で使用
// この部分をカスタマイズする際は、Config_Setting_FileUpload の冒頭の解説をよく読むこと
gen.imageUpload = {
    init: function(imageFileName, cat, id) {
        var divName = 'gen_imageUploadDiv';
        document.write("<div id='" + divName + "'></div>");
        $(function(){
            gen.imageUpload.init2(divName, imageFileName, cat, id);
        });
    },
    init2: function(divName, imageFileName, cat, id) {
        var number = 0;
        var frameName = "gen_imageUploadFrame";
        while($('#' + frameName + number).length > 0) {
            number++;
        }
        frameName += number;
        $('#' + divName).append("<script src='scripts/jquery.upload/jquery.upload.min.js' type='text/javascript'></script>" +
                "<div id='imageArea'>" + (imageFileName == "" ? _g("画像が登録されていません") : "<img src='index.php?action=download&cat=" + cat + "&file=" + imageFileName + "'>") + "</div>" +
                "<iframe id='" + frameName + "' style='border:0px; width:260px; height:50px'></iframe>");
        var html =
            "<table id='imageUploadArea' style='text-align:center; margin: 0 auto; width:200px'>" + // margin 0 auto は boxのセンタリング
            "<tr>" +
            "<td style='width:100px; text-align:center'>" +
            // 透明にしたfileタグの参照ボタンを、登録ボタンの上に重ねている。詳細はConfig_Setting_FileUpload の「クライアント部分」の解説を参照
            "<div id='imageUploadButtonArea' style='overflow:hidden; position:relative; width:100px; height:25px;  margin: 0 auto'>" +
            "<input type='file' name='uploadFile' id='imageUploadTag' style='height:300px; font-size:300px; right:0; position:absolute; opacity:0.01' onchange=\"parent.gen.imageUpload.doUpload('" + frameName + "', '" + cat + "','" + id + "')\">" +
            "<input type='button' value='" + (imageFileName == "" ? _g("画像を登録") : _g("画像を変更")) + "' style='width:100%; height:100%'>" +
            "</div>" +
            "</td>" +
            "<td id='imageDeleteButtonArea' style='width:100px; text-align:center" + (imageFileName == "" ? ";display:none" : "") + "'>" +
            "<input type='hidden' id='imageFileName' value='" + imageFileName + "'>" +
            "<a href=\"javascript:parent.gen.imageUpload.deleteUploadImage('" + frameName + "','" + cat + "', document.getElementById('imageFileName').value, '" + _g("画像") + "')\" style='color:#666; font-size:14px'>" + _g("画像の削除") + "</a>" +
            "</td>" +
            "</tr></table>";
        if (gen.util.isIE) {
            $('#' + frameName).load(function(){$(this).contents().find('body').html(html)});
        } else {
            $('#' + frameName).contents().find('body').html(html);
        }
    },
    doUpload: function(frameName, cat, id) {
        var url = 'index.php?action=Config_Setting_FileUpload&cat=' + cat + '&id=' + id + '&gen_ajax_token=' + $('#gen_ajax_token').val();
        var cons = $('#' + frameName).contents();
        var fileTag = cons.find('#imageUploadTag');
        if (fileTag.val() == '')
            return;
        if (gen.util.isUnderIE9) {
            var files = fileTag.val();
        } else {
            var files = fileTag.get(0).files;
        }
        var maxSize = parseInt($('#gen_max_upload_file_size').val());
        for(var i=0; i<files.length; i++){
            if (maxSize < files[i].size) {
                var showFileSize = Math.ceil(files[i].size / 1024 / 1024);
                var showMaxSize = Math.ceil(maxSize / 1024 / 1024);
                alert(_g("%1 MBを超えるファイルはアップロードできません。（選択されたファイルのサイズ：%2 MB）").replace('%1', showMaxSize).replace('%2', showFileSize));
                fileTag.val('');
                return;
            }
        }

        gen.waitDialog.show();
        cons.find('#imageUploadArea').css('visibility','hidden'); // FFちらつき対策
        fileTag.upload(url,
            function(res) {
                cons.find('#imageUploadArea').css('visibility','');
                if (res.status == 'storageError') {
                    alert(_g("ファイルストレージの容量が足りません。既存のファイルを削除するか、ジェネシスの契約プランを変更してください。"));
                }
                if (res.success) {
                    var fileName = gen.util.escape(res.fileName);
                    $('#imageArea').html("<img src='index.php?action=download&cat=" + cat + "&file=" + fileName + "'>");
                    cons.find('#imageDeleteButtonArea').css('display','');
                    cons.find('#imageFileName').val(fileName);
                }
                gen.waitDialog.hide();
                if (res.msg != '')
                    alert(res.msg);
            }, 'json');
    },
    deleteUploadImage: function(frameName, cat, file, originalName) {
        if (!confirm(_g("%dを削除します。削除したファイルは、バックアップを復元しても元に戻りません。本当に削除しますか？").replace('%d', originalName))) {
            return;
        }
        gen.ajax.connect('Config_Setting_FileUpload', {cat : cat, file : file, 'delete': true},
            function(j) {
                $('#imageArea').text(_g("画像が登録されていません"));
                $('#' + frameName).contents().find('#imageDeleteButtonArea').css('display','none');
                if (j.msg != '')
                    alert(j.msg);
            });
    }
};

//**************************************
// プロフィール画像登録ダイアログ
//**************************************
gen.profileImage = {
    showUploadDialog: function() {
        if (this.profileImageUploadDialog == null) {
            // デフォルト。ダイアログ生成時にウィンドウサイズにより自動調整される
            var x = gen.window.getBrowserWidth() / 2 - 250;
            var y = gen.window.getBrowserHeight() / 2 - 150;
            var width = 500;
            var height = 350;

            var number = 0;
            var divName = "gen_imageUploadDiv";
            while($('#' + divName + number).length > 0) {
                number++;
            }
            divName += number;

            var html = "";
            html += "<div style='text-align:center'>";
            html += "<div style='height:10px'></div>";
            html += "<div id='" + divName + "'></div>"; // 後の gen.imageUpload.init2()で中身を埋め込み
            html += _g("JPG, GIF, PNG 画像を登録できます。画像のサイズは自動的に調整されます。") + "<br>";
            html += _g("（あらかじめ画像編集ソフトで40×40pxに編集しておくときれいな画像になります。）");
            html += "<br><br>";
            html += "</div>";

            this.profileImageUploadDialog = gen.dialog.create('gen_body', 'gen_profileImageUploadDialog', x, y, width, height, _g("プロフィール画像の登録"), html, true, true);
            gen.imageUpload.init2(divName, $('#gen_profileImageTag').attr('src'), 'profileimage', '');
        }
        this.profileImageUploadDialog.show();
    },
};

//**************************************
// CSVインポートダイアログ
//**************************************
gen.csvImport = {
    showImportDialog: function(reqId, action, maxCount, importMsg, encoding, allowUpdateCheck, allowUpdateLabel) {
        if (this.importDialog != null) {
            this.importDialog.destroy();
        }
        // デフォルト。ダイアログ生成時にウィンドウサイズにより自動調整される
        var pos = $('#gen_importButton').offset();
        pos.top += 20;
        pos.left -= 300;
        var width = 450;
        var height = 500;

        var number = 0;
        var divName = "gen_fileUploadDiv";
        while($('#' + divName + number).length > 0) {
            number++;
        }
        divName += number;

        var html = "";
        html += "<div style='width:100%; height:100%; overflow-y:auto'>";
        html += "<table cellpadding='5' align='center' style='width:100%'><tr><td align='left' bgcolor='#f4f4f4'>";
        html += _g("※一回の処理で登録できるのは最大 %s 件までです。").replace('%s',maxCount);
        if (importMsg != "")
            html += "<br><br>";
        html += importMsg;
        html += "</td></tr></table>";
        html += "<br><br>";
        html += _g("インポートするCSVデータ（%s）を指定してください。").replace('%s',encoding)+ "<br><br>";
        if (allowUpdateCheck) {
            if (allowUpdateLabel == "") allowUpdateLabel = _g("既存データを上書きする");
            html += "<input type='checkbox' name='allowUpdate' id='gen_allowUpdate' onchange=\"$('#gen_allowUpdateValue').val($('#gen_allowUpdate').is(':checked') ? 'true' : 'false')\">"+allowUpdateLabel+"<br><br>";
            html += "<input type='hidden' id='gen_allowUpdateValue' value='false'>";
        }
        html += "<div id='" + divName + "'></div>"; // 後の gen.fileUpload.init2()で中身を埋め込み
        html += "<br><div id='importMsgArea'/>"
        html += "</div><div id='formatArea'></div>";

        this.importDialog = gen.dialog.create('gen_body', 'gen_importDialog', pos.left, pos.top, width, height, _g("CSVインポート"), html, true, true);

        var url = "index.php?action=" + action
            + '&gen_csv_page_request_id=' + reqId
            + ($('#gen_allowUpdate').length > 0 ? '&allowUpdate=[gen_allowUpdateValue]' : '');
        $('#' + divName).append("<input type='hidden' id='gen_importAction' value='" + action + "'>");
        // 最後の引数をtrueにすると、ファイル選択時に即時アップロードされる
        gen.fileUpload.init2(divName, url, "", "gen.csvImport.importCallback", divName, undefined, undefined, false);
        this.importDialog.show();

        var actionArr = action.split('&');
        var csvAction = actionArr[0];   // classification以外のパラメータを取り除いたaction
        for (var i=1; i<actionArr.length; i++) {
            if (actionArr[i].substr(0,14) == "classification") {
                csvAction += "&" + actionArr[i];
            }
        }
        gen.ajax.connect('Config_Setting_AjaxCsvFormatInfo', {csvAction: csvAction},
            function(j){
                if (!j) {
                    return;
                }
                var html2 = "<hr><br><br>"
                + "<div style='text-align:left;width:95%;padding-left:5px'>"
                + "■" + _g("使用するフォーマット") + "<br><br>"
                + "<div id='gen_csv_format_list'>"
                + gen.csvImport.getFormatList(j, csvAction)
                + "</div>"
                + "<br><br>"
                + "<center><a id='gen_csv_format_show_area_link' href=\"javascript:gen.csvImport.showFormatArea()\" style='color:#000'>" + _g("フォーマットを作成もしくは編集する") + "</a></center>"
                + "<div id='gen_csv_format_area' style='display:none'>"
                + "<br><br>"
                + "■" + _g("フォーマットの作成と編集") + "<span style='margin-right:50px'></span>"
                + "<br><br>"
                + _g("下のフォーマットを編集して「フォーマットを登録」ボタンを押してください。") + "<br>"
                + _g("「フォーマット名」に既存フォーマットの名称を入力すると、そのフォーマットが上書きされます。別名を入力すると新規登録となります。") + "<br><br>"
                + "<table>"
                + "<tr><td>" + _g("フォーマット名（必須）") + "</td><td><input type='textbox' id='gen_csv_format_name'></td></tr>"
                + "<tr><td>" + _g("説明（省略可）") + "</td><td><input type='textbox' id='gen_csv_format_desc'></td></tr>"
                + "<tr><td>" + _g("データ形式") + "</td>"
                + "<td><select id='gen_csv_format_separete_type'>"
                + "<option value='0'>" + _g("カンマ区切り(CSV)") + "</option>"
                + "<option value='1'>" + _g("タブ区切り(TSV)") + "</option>"
                + "</select></td></tr>"
                + "<tr><td>" + _g("ヘッダ（読み飛ばし）行数") + "</td><td><input type='textbox' id='gen_csv_format_header_number_of_lines'></td></tr>"
                + "</table><br><br>"
                + "<table>"
                + "<tr>"
                + "<td align='center' width='200'>" + _g("項目") + "</td>"
                + "<td align='center' width='150'>" + _g("項目タイプ") + "</td>"
                + "<td align='center'>" + _g("項目番号または値") + "<a class='gen_chiphelp' href='#' rel='p.chiphelp_gen_csv_column_type' title='" + _g("項目番号または値") + "'><img class='imgContainer sprite-question' src='img/space.gif' style='border:none'></a>"
                + "<p class='chiphelp_gen_csv_column_type'>"
                + _g("■項目タイプが「参照」のとき") + "<br><br>" + _g("データファイル内の指定された項目の値をそのまま使用します。「列番号または項目」には、項目番号を数値で指定してください。") + "<br><br>"
                + _g("■項目タイプが「固定値/数式」のとき") + "<br><br>" + _g("「項目番号または値」の先頭が「=」である場合は数式、それ以外は固定値とみなされます。") + "<br>"
                + _g("数式においては、[項目番号] の形式でデータファイル内の指定された項目の値を参照します。また、if, mid, trim などのエクセル関数を使用できます。") + "<br><br>"
                + _g("例1：") + "「=if([5]=\"fix\",0,1)」 ⇒ " + _g("データファイル5項目めの値が「fix」なら「0」、それ以外なら「1」に変換する。") + "<br><br>"
                + _g("例2：") + "「=mid([10],3,4)」 ⇒ " + _g("データファイル10項目めの値の、3文字目から4文字を取り出す。") + "<br><br>"
                + _g("例3：") + "「=year(today()+5) & \"/\" & month(today()+5) & \"/\" & day(today()+5)」 ⇒ " + _g("データをインポートした日付の5日後。")
                + "</p></td>"
                + "</tr>";
                $.each(j.csv_array, function(i, column) {
                    var label =  gen.util.escape(column.label);
                    var addLabel =  gen.util.escape(column.addLabel);
                    var field = gen.util.escape(column.field);
                    html2 += "<tr>"
                    + "<td>" + label + (column.addLabel == undefined ? "" : "<br>" + addLabel) + "</td>"
                    + "<td>"
                    + "<select id='gen_csv_format_type_" + field + "' onchange=\"gen.csvImport.formatTypeChange('" + field + "')\">"
                    + "<option value='0'>" + _g("列参照") + "</option>"
                    + "<option value='1'>" + _g("固定値/数式") + "</option>"
                    + "<option value='2'>" + _g("ブランク") + "</option>"
                    + "<option value='3'>" + _g("ファイル名") + "</option>"
                    + "</select>"
                    + "</td>"
                    + "<td>" + "<input type='textbox' id='gen_csv_format_value_" + field + "' size='11' value='" + (i + 1) + "'></td>"
                    + "</tr>";
                });
                html2 += "</table><br><br>"
                + "<center><input type='button' value=" + _g("フォーマットを登録") + " style='width:150px' onclick=\"gen.csvImport.regFormat('" + csvAction + "')\"></center>"
                + "</div><br>"
                + "</div>";
                $('#formatArea').html(html2);
                gen.ui.initChipHelp();
                gen.csvImport.rowSelect(csvAction);
            }
        );
    },
    showFormatArea: function() {
        $('#gen_csv_format_area').css('display','');
        $('#gen_csv_format_show_area_link').css('display','none');
        $('#gen_importDialog').css('height','700px').find('.bd').css('height','680px');
        $('[id^=gen_csv_format_type_]:first').focus();
    },
    getFormatList: function(j, csvAction) {
        var html = ""
        + "<table border='0' cellspacing='1' cellpadding='2' style='background:#696969;'>"
        + "<tr style='background-color:#D0D0D0;text-align: center'>"
        + "<th width='35px'>" + _g("選択") + "</th>"
        + "<th width='170px'>" + _g("フォーマット") + "</th>"
        + "<th width='260px'>" + _g("説明") + "</th>"
        + "<th width='80px'>" + _g("登録者") + "</th>"
        + "<th width='80px'>" + _g("登録日時") + "</th>"
        + "<th width='40px'>" + _g("削除") + "</th>"
        + "</tr>"
        + "<tr bgcolor='#ffffff' id='gen_csv_format_row_-1'>"
        + "<td align='center'><input type='radio' name='gen_csv_format_number' value='-1' onclick=\"gen.csvImport.rowSelect('" + csvAction + "')\" " + (j.selected_format == "" ? "checked" : "") + "></td>"
        + "<td align='center' id='gen_csv_format_name_-1'>" + _g("標準") + "</td>"
        + "<td></td>"
        + "<td></td>"
        + "<td></td>"
        + "<td></td>"
        + "</tr>";
            $.each(j.format_info, function(i, info) {
                var format = gen.util.escape(info.format);
                var description = gen.util.escape(info.description);
                var uploader = gen.util.escape(info.uploader);
                var date = gen.util.escape(info.date);

                html+= "<tr bgcolor='#ffffff' id='gen_csv_format_row_" + i + "'>"
                + "<td align='center'><input type='radio' name='gen_csv_format_number' value='" + i + "' onclick=\"gen.csvImport.rowSelect('" + csvAction + "')\" " + (j.selected_format == format ? "checked" : "") + "></td>"
                + "<td align='center' id='gen_csv_format_name_" + i + "'>" + format + "</td>"
                + "<td align='left' id='gen_csv_format_desc_" + i + "'>" + description + "</td>"
                + "<td align='center' style='font-size:10px'>" + uploader + "</td>"
                + "<td align='center' style='font-size:10px'>" + date + "</td>"
                + "<td align='center'><a href=\"javascript:gen.csvImport.formatDelete('" + format + "','" + csvAction + "')\"><img class='imgContainer sprite-cross' src='img/space.gif' border='0' tabindex='-1'></a></td>"
                + "</tr>";
            });
        html += "</table>"
        return html;
    },
    rowSelect: function(csvAction) {
        var no = $('input[name="gen_csv_format_number"]:checked').val();
        var name = no == -1 ? "" : $("#gen_csv_format_name_" + no).html();
        var desc = no == -1 ? "" : $("#gen_csv_format_desc_" + no).html();
        $("[id^=gen_csv_format_row_]").css('background', '#fff');
        $("#gen_csv_format_row_" + no).css('background', '#8dc4fc');

        gen.ajax.connect('Config_Setting_AjaxCsvFormatInfo', {csvAction: csvAction, formatName : name, dataMode : true},
            function(j) {
                var data;
                if (j.format_data == "") {
                    data = {};
                } else {
                    data = JSON.parse(j.format_data);
                }
                $('[id^=gen_csv_format_type_]').each(function(i, typeElm) {
                    var field = typeElm.id.replace('gen_csv_format_type_', '');
                    if (data[field] === undefined) {
                        $('#' + typeElm.id).val('0');
                        $('#gen_csv_format_value_' + field).val(i + 1);
                    } else {
                        var arr = data[field].split('[sep]');
                        $('#' + typeElm.id).val(arr[0]);
                        $('#gen_csv_format_value_' + field).val(arr[1]);
                    }
                    gen.csvImport.formatTypeChange(field);
                });
                $('#gen_csv_format_separete_type').val((data['gen_isTab'] !== undefined && data['gen_isTab']) ? "1" : "0");
                $('#gen_csv_format_header_number_of_lines').val(data['gen_headerNumberOfLines'] !== undefined && gen.util.isNumeric(data['gen_headerNumberOfLines']) ? data['gen_headerNumberOfLines'] : "1");
                $('#gen_csv_format_name').val(name);
                $('#gen_csv_format_desc').val(desc);
            });
    },
    importCallback: function(res, callbackParam) {
        // reqIdが変わっているためアップロードセクションを再作成
        var action = $('#gen_importAction').val();
        $('#' + callbackParam)
            .html('')
            .append("<input type='hidden' id='gen_importAction' value='" + action + "'>");
        var url = "index.php?action=" + action
                + '&gen_csv_page_request_id=' + res.reqId
                + ($('#gen_allowUpdate').length > 0 ? '&allowUpdate=[gen_allowUpdateValue]' : '');
        gen.fileUpload.init2(callbackParam, url, "", "gen.csvImport.importCallback", callbackParam, undefined, undefined, false);

        var color = (res.success ? "99ffff" : "ffcccc");
        var html = "<table width='100%'><tr><td bgcolor='#" + color + "' align='center'>";
        if (res.msg instanceof Array) {
            html += _g("下記のエラーが発生しました。データは1件も登録されませんでした。")+"<br><br>";
            html += "<table border=1 cellspacing='0' cellpadding='2'>";
            html += "<tr bgcolor='#cccccc'><td width='50px' align='center'>" + _g("行") + "</td><td align='center' nowrap>" + _g("メッセージ") + "</td></tr>";
            $.each(res.msg, function(i, val) {
                html += "<tr bgcolor='#ffffff'><td align='center'>" + gen.util.escape(val[0]) + "</td><td>" + gen.util.escape(val[1]) + "</td></tr>";
            });
            html += "</table>";
        } else {
            html += gen.util.escape(res.msg);
        }
        html += "</td></tr></table>";
        if (res.success && !res.notShowOnlyCheck) {
            html += "<br><br><input type='checkbox' id='gen_importOnlyChk' onclick='javascript:gen.csvImport.updateListAfterImport()'>"+_g("いまインポートしたデータだけを表示する");
        }
        $('#importMsgArea').html(html);
        if (res.success) {
            if (res.isBOM) {
                $('#parent_item_id_show').val(res.showItemCode).trigger('change');
            } else {
                gen.csvImport.updateListAfterImport();
            }
        }
        $('#import').attr('disabled',false);
    },
    updateListAfterImport: function() {
        var mode = ($('#gen_importOnlyChk').is(':checked') ? '1' : '0');
        if ($('[name=gen_importDataShowMode]').length == 0) {
            $('#form1').append("<input type='hidden' name='gen_importDataShowMode' value='"+mode+"'>");
        } else {
            $('[name=gen_importDataShowMode]').val(mode);
        }
        var elm = document.getElementById('gen_searchButton');
        if (elm != 'undefined' && elm != null) elm.click();
    },
    regFormat: function(csvAction) {
        var name = $('#gen_csv_format_name');
        if (name.val() == '') {
            name
                .before("<div class='gen_formatNameErrArea' style='background-color:#ffcccc'>" + _g('フォーマット名を入力してください。') + "</div>")
                .focus();
            return;
        } else if (name.val() == _g("標準")) {
            name
                .before("<div class='gen_formatNameErrArea' style='background-color:#ffcccc'>" + _g('「標準」フォーマットは上書きできません。') + "</div>")
                .focus();
        } else {
            $('.gen_formatNameErrArea').remove();
        }
        // csvActionのclassificationは、CSV項目としてのclassificationと区別するため名前を変える
        var csvActionForReg = csvAction.replace("&classification=", "&gen_classification=");
        var p = {csvAction : csvActionForReg, name : name.val(), desc : $('#gen_csv_format_desc').val(), isTab : $('#gen_csv_format_separete_type').val() == "1", headerNumberOfLines : $('#gen_csv_format_header_number_of_lines').val()};
        var success = true;
        $('[id^=gen_csv_format_type_]').each(function(){
            var field = this.id.substr(20, this.id.length - 20);
            var valueElm = $('#gen_csv_format_value_' + field);
            var value = valueElm.val();
            var err = "";
            if (this.value == '0') {
                if (value == '') {
                    err = _g('列番号を入力してください。');
                } else if (!gen.util.isNumeric(value)) {
                    err = _g('列番号を数値で入力してください。');
                }
            } else if (this.value == '1') {
                if (value == '') {
                    err = _g('固定値/数式を入力してください。');
                }
            } else {
                value = "";
            }
            if (err != "") {
                valueElm
                    .before("<div class='gen_formatNameErrArea' style='background-color:#ffcccc'>" + err + "</div>")
                    .focus();
                success = false;
            }
            p[field] = this.value + "[sep]" + value;
        });
        if (!success) {
            return;
        }
        var cancel = false;
        $('[id^=gen_csv_format_name_]').each(function(i, nameElm){
            if (name.val() == $(nameElm).html()) {
                if (!confirm(_g("指定されたフォーマット名はすでに存在します。フォーマットを上書きしますか？"))) {
                    cancel = true;
                    return;
                }
            }
        });
        if (cancel) {
            return;
        }
        gen.ajax.connect('Config_Setting_AjaxCsvFormatRegist', p,
            function(j) {
                // リスト更新
                if (j.success) {
                    $('#gen_csv_format_list').html(gen.csvImport.getFormatList(j, csvAction));
                    gen.csvImport.rowSelect(csvAction);
                    $('[name=gen_csv_format_number]' + '[value=-1]').focus();
                } else {
                    $('#gen_csv_format_value_' + gen.util.escape(j.field))
                        .before("<div class='gen_formatNameErrArea' style='background-color:#ffcccc'>" + gen.util.escape(j.msg) + "</div>")
                        .focus();
                    alert(_g("エラーが発生しました。入力内容を確認してください。"));
                }
            });
    },
    formatDelete: function(format, csvAction) {
        if (!confirm(_g("フォーマットを削除してもよろしいですか？"))) {
            return;
        }
        gen.ajax.connect('Config_Setting_AjaxCsvFormatRegist', {csvAction : csvAction, name : format, delete : true},
            function(j) {
                // リスト更新
                if (j.success) {
                    $('#gen_csv_format_list').html(gen.csvImport.getFormatList(j, csvAction));
                    gen.csvImport.rowSelect(csvAction);
                    $('[name=gen_csv_format_number]' + '[value=-1]').focus();
                }
            });
    },
    formatTypeChange: function(field) {
        var type = $('#gen_csv_format_type_' + field).val();
        var valElm = $('#gen_csv_format_value_' + field);
        var isDisabled = (type == "2" || type == "3");
        if (isDisabled) {
            valElm.val('');
        }
        gen.ui.alterDisabled(valElm, isDisabled);
        valElm.css('background', isDisabled ? '#ccc' : '#fff');
    },
};

//**************************************
// 帳票テンプレートダイアログ
//**************************************
gen.reportEdit = {
    showReportEditDialog: function(reportAction) {
        if (this.reportEditDialog != null) {
            this.reportEditDialog.destroy();
        }

        // デフォルト。ダイアログ生成時にウィンドウサイズにより自動調整される
        var pos = $('#gen_reportEditButton_' + reportAction).offset();
        pos.top += 20;
        pos.left -= 250;
        var width = 800;
        var height = 600;

        var thisObj = this;
        gen.ajax.connect('Config_Report_AjaxTemplateInfo', {reportAction: reportAction},
            function(j){
                var html = "<table style='padding:5px'>"
                + "<tr>"
                + "<td align='left'>"
                + "■" + _g("登録済みテンプレート")
                + "<br><br>" + _g("※選択されている行は、帳票印刷で実際に使用されるテンプレートです。")
                + "<br>" + _g("※選択行を切り替えることにより、使用するテンプレートを変更できます。")
                + (reportAction == "Delivery_Delivery_Report" || reportAction == "Monthly_Bill_Report" || reportAction == "Partner_Order_Report" || reportAction == "Subcontract_Order_Report" ?
                    "<span style='color:blue'>" + _g("ただし取引先マスタで帳票を指定している場合、そちらが優先されます。") + "</span>" : "")
                + "<br>" + _g("※システム標準のテンプレートは削除および上書きできません。")
                + "<div id='gen_template_list'>"
                + gen.reportEdit.getTemplateList(j, reportAction)
                + "</div>"
                + "</td>"
                + "</tr>"
                + "<tr style='height:30px'><td></td></tr>";

                if (j.permission == 2) {
                    html += ""
                    + "<tr>"
                    + "<td align='left'>"
                    + "■" + _g("テンプレートの登録") + ""
                    + "<div style='height:20px'></div>"
                    + "<table border='0'>"
                    + "<tr align='left'>"
                    + "<td id='upload_section'>"
                    + "<div id='gen_template_msg'></div>"
                    + _g("説明") + "：<input type='text' id='gen_template_comment' style='width:400px'>"
                    + "<div id='gen_template_upload_div'></div>" // 後の gen.fileUpload.init2()で中身を埋め込み
                    + "</td>"
                    + "<td id='upload_section2' style='display:none; background-color:#ffcccc'>"
                    + _g("登録処理中。パソコンに手を触れずにお待ちください...") + ""
                    + "</td>"
                    + "</tr>"
                    + "</table>"
                    + "</td>"
                    + "</tr>"
                    + "<tr style='height:30px'><td></td></tr>"

                    + "<tr>"
                    + "<td align='left'>"
                    + "■" + _g("テンプレート内で使用できるタグ") + "<span style='margin-right:50px'></span>"
                    + _g("タグの絞込み") +" <input type='textbox' id='gen_reportEditSearchText' style='width:150px' onkeyup=\"gen.reportEdit.searchReportEditDialog()\">"
                    + "<br><br>"
                    + "<table border='0' cellspacing='0' cellpadding='0'>"
                    + "<tr><td>"
                    + "<table border='0' cellspacing='1' cellpadding='2' bgcolor='#666666'>"
                    + "<tr bgcolor='#D0D0D0'>"
                    + "<td align='center' width='250'>" + _g("タグ") + "</td>"
                    + "<td align='center' width='500'>" + _g("説明") + "</td>"
                    + "</tr>";
                    var i = 0;
                    $.each(j.tag_list, function(i, tag) {
                        html += "<tr bgcolor='#ffffff'" + (tag.length > 1 ? "id='gen_reportTagTr_" + i + "'" : "") + ">"
                        + (tag.length == 1 ? "<td colspan='2' bgcolor='#D0D0D0'>" + gen.util.escape(tag[0]) + "</td>"
                            : "<td align='left' id='gen_reportTagText1_" + i + "'>[[" + gen.util.escape(tag[0]) + "]]</td><td align='left' id='gen_reportTagText2_" + i + "'>" + gen.util.escape(tag[1]) + "</td>")
                        + "</tr>";
                        i++;
                    });
                    html += "</table>"
                    + "</td></tr>"
                    + "</table>"
                    + "</td>"
                    + "</tr>";
                }
                html += "</table>";

                thisObj.reportEditDialog  = gen.dialog.create('gen_body', 'gen_reportEditDialog', pos.left, pos.top, width, height, _g("レポート・クリエイター"), html, true, true);
                $('#gen_reportEditDialog .bd').css('overflow-y','scroll');

                gen.reportEdit.rowSelect(j.selected_no - 1, j.report, true);

                var url = "index.php?action=Config_Report_Upload"
                        + "&reportAction=" + reportAction
                        + "&report=" + j.report
                        + "&comment=[gen_template_comment]"
                        + "&reportTitle=" + encodeURIComponent(j.report_title);
                gen.fileUpload.init2("gen_template_upload_div", url, "gen.reportEdit.beforeUpload", "gen.reportEdit.afterUpload", reportAction);

                thisObj.reportEditDialog.show();
            });
    },
    searchReportEditDialog: function() {
        var s = $('#gen_reportEditSearchText').val();
        $('[id^=gen_reportTagTr_]').each(function(){
            var textId1 = this.id.replace('Tr', 'Text1');
            var textId2 = this.id.replace('Tr', 'Text2');
            $('#' + this.id).css('display', document.getElementById(textId1).innerHTML.indexOf(s) > -1 || document.getElementById(textId2).innerHTML.indexOf(s) > -1 ? '' : 'none');
        });
    },
    getTemplateList: function(j, reportAction) {
        var html = ""
        + "<table border='0' cellspacing='1' cellpadding='2' style='background:#696969;'>"
        + "<tr style='background-color:#D0D0D0;text-align: center'>"
        + "<th width='35px'>" + _g("選択") + "</th>"
        + "<th width='170px'>" + _g("テンプレート") + "</th>"
        + "<th width='260px'>" + _g("説明") + "</th>"
        + "<th width='80px'>" + _g("登録者") + "</th>"
        + "<th width='70px' style='font-size:10px'>" + _g("サンプル表示") + "</th>"
        + "<th width='70px' style='font-size:10px'>" + _g("テンプレート") + "</th>"
        + "<th width='40px'>" + _g("削除") + "</th>"
        + "</tr>";
        $.each(j.template_file_info, function(i, info) {
            var file = gen.util.escape(info.file);
            var comment = gen.util.escape(info.comment);
            var uploader = gen.util.escape(info.uploader);

            html+= "<tr bgcolor='#ffffff' id='reportedit_row_" + i + "'>"
            + "<td align='center'><input type='radio' id='reportedit_number_" + i + "' name='number' value='" + i + "' onclick=\"gen.reportEdit.rowSelect(" + i + ",'" + gen.util.escape(j.report) + "')\" " + (j.selected_no - 1 == i ? "checked" : "") + "></td>"
            + "<td align='center' id='reportedit_file_" + i + "' data-isdefault='" + (info.isDefault!='true' ? "0" : "1") + "'>" + file + "</td>"
            + "<td align='left'>" + comment + "&nbsp;</td>"
            + "<td align='center' style='font-size:10px'>" + uploader + "&nbsp;</td>"
            + "<td align='center'><a href='index.php?action=Config_Report_Sample&gen_template=" + encodeURIComponent(info.file) + "&reportAction=" + reportAction + "' style='font-size:10px'>" + _g("サンプル表示") + "</a></td>"
            + "<td align='center'><a href='" + info.url + "' style='font-size:10px'>" + _g("ダウンロード") + "</a></td>"
            + "<td align='center' id='reportedit_isdefault_" + i + "'>" + (info.isDefault!='true' && j.permission == 2 ? "<a href=\"javascript:gen.reportEdit.templateDelete('" + gen.util.escape(j.report) + "','" + file + "','" + gen.util.escape(j.report_title) + "','" + reportAction + "')\"><img class='imgContainer sprite-cross' src='img/space.gif' border='0' tabindex='-1'></a>" : "&nbsp;") + "</td>"
            + "</tr>";
        });
        html += "</table>"
        return html;
    },
    rowSelect: function(no, report, isInit) {
        $("[id^=reportedit_row_]").css('background', '#fff');
        $("#reportedit_row_" + no).css('background', '#8dc4fc');
        if (isInit) return;

        var file = $('#reportedit_file_'+no).html();
        gen.ajax.connect('Config_Report_AjaxChange', {report : report, file : file},
            function(j) {
            });
    },
    beforeUpload: function(files) {
        if (files.length == 0) {
            alert(_g("ファイルを指定してください。"));
            return false;
        }
        var fileName = files[0].name;
        var isOK = true;
        $('[id^=reportedit_file_]').each(function(i, file){
            var f = $(file).html();
            var isDef = $(file).attr('data-isdefault') == '1';
            if (f == fileName) {
                if (isDef) {
                    alert(_g("システム標準のテンプレートを上書きすることはできません。ファイル名を変更してください。"));
                    isOK = false;
                } else {
                    if (!confirm(_g("指定されたファイル名のテンプレートはすでに存在します。上書きしてもよろしいですか？"))) {
                        isOK = false;
                    }
                }
                return false;
            }
        });

        if (!isOK)
            return false;

        $('#upload_section').hide();
        $('#upload_section2').show();
        return true;
    },
    afterUpload: function(res, reportAction) {
        var success = !(res.msg instanceof Array);
        var color = (success ? "99ffff" : "ffcccc");
        var html = "<table width='100%'><tr><td bgcolor='#"+color+"' align='center'>";
        if (success) {
            html += _g("テンプレートを登録しました。");
        } else {
            html += _g("下記のエラーが発生しました。")+"<br><br>";
            html += "<table border=1 cellspacing='0' cellpadding='2'>";
            html += "<tr bgcolor='#cccccc'><td width='50px' align='center'>" + _g("セル") + "</td><td align='center' nowrap>" + _g("メッセージ") + "</td></tr>";
            $.each(res.msg, function(i, val) {
                if (val instanceof Array) {
                    html += "<tr bgcolor='#ffffff'><td align='center'>" + val[0] + "</td><td>" + val[1] + "</td></tr>";
                } else {
                    html += "<tr bgcolor='#ffffff'><td colspan='2' align='center'>" + val + "</td></tr>";
                }
            });
            html += "</table>";
        }
        html += "</td></tr></table><br><br>";
        $('#gen_template_msg').html(html);
        $('#upload_section').show();
        $('#upload_section2').hide();

        if (success) {
            gen.ajax.connect('Config_Report_AjaxTemplateInfo', {reportAction: reportAction},
                function(j){
                    $('#gen_template_list').html(gen.reportEdit.getTemplateList(j, reportAction));
                    gen.reportEdit.rowSelect(j.selected_no - 1, j.report, true);
                });
        }
    },
    templateDelete: function(report, file, reportTitle, reportAction) {
        if (!confirm(_g("テンプレート %s を削除します。この操作を取り消すことはできません。実行してもよろしいですか？").replace('%s', gen.util.escape(file)))) {
            return;
        }
        gen.ajax.connect('Config_Report_AjaxDelete', {report:report, file:file, reportTitle: reportTitle},
            function(j){
                if (j.msg == "") {
                    gen.ajax.connect('Config_Report_AjaxTemplateInfo', {reportAction: reportAction}, function(j){
                        $('#gen_template_list').html(gen.reportEdit.getTemplateList(j, reportAction));
                        gen.reportEdit.rowSelect(j.selected_no - 1, j.report, true);
                    });
                } else {
                    alert(j.msg);
                }
            });
    }
};

//**************************************
// グラフ関連
//**************************************

// require: visualize.css, visualize-light.css, excanvas.js, visualize.jQuery.js
gen.chart = {
    init: function() {
        $('.gen_chartSource').each(function(){
            var cs = $(this);
            var type = cs.attr('data-charttype');
            var width = cs.attr('data-chartwidth');
            if (!gen.util.isNumeric(width))
                width = 300;
            var height = cs.attr('data-chartheight');
            if (!gen.util.isNumeric(height))
                width = 250;
            var appendKey = false;
            if (cs.attr('data-appendkey') == 'true')
                appendKey = true;
            var config = {type: 'bar', width: width + 'px', height: height + 'px', parseDirection: 'y', appendKey: appendKey};
            switch(type) {
            case 'pie':
                config.type = 'pie';
                config.parseDirection = 'x';
                config.appendKey = 'true';
                break;
            default:
                break;
            }
            config.type = cs.attr('data-charttype');
            var noneFlag = false;
            var csParent = cs.parent();
            if (csParent.css('display')=='none') {
                csParent.css('display', '');
                noneFlag = true;
            }
            cs.visualize(config).attr('id',cs.attr('id')+'_div').insertBefore(this);
            if (noneFlag) {
                csParent.css('display','none');
            }

            // リスト縦幅の再設定を行う。
            // リスト画面は gen.list.init で縦幅が調整されるが、その時点ではグラフ表示が終わっておらず、リストの上端位置が
            // 正確にとれないため。
            if (gen.list.table != undefined) {
                gen.list.table.setListSize();
            }
        });
    }
};

//**************************************
// デスクトップ通知
//**************************************

gen.desktopNotification = {
    show: function() {
        if (!window.Notification) {
            return;
        }
        switch(Notification.permission){
            case "granted":
                gen.ajax.connect('Config_Setting_AjaxDesktopNotification', {op:'get'},
                    function(j) {
                        if (j.msg != '') {
                            noti = new Notification(_g("Genesiss"), {
                             icon: "img/15i_favicon.ico",
                             body: j.msg.replace(/\[br\]/g, '\n'),
                             tag: "Genesiss" + location.host + '_' + location.pathname.split('/')[1], // サイト別に通知
                           });
                           noti.onclick = function() {
                               noti.close(); // Chromeで通知本体クリック後に通知が行われなくなる問題に対処
                               location.href = location.href.replace(location.search, '?action=Menu_Chat');
                               window.focus();  // これは Chromeのみ動作
                           }
                       }
                   }, true);
                var timeSpan = gen.desktopNotification.timeSpan;    // common_header
                if (timeSpan === undefined) {
                    timeSpan = 10;
                }
                setTimeout(gen.desktopNotification.show, timeSpan * 60000);
                break;
            case "default": // 未承認
                // ここで Notification.requestPermission() を実行したいところだが、
                // Chromeの場合、同APIはユーザー操作起点でないと動作しないという制限がある。
            case "denied": // 拒否
        }
    },
    // この関数は必ずユーザー操作によるイベントから呼ぶこと
    chageState: function(cat, isOn) {
        if (isOn && Notification.permission == "default") {
            // Chromeの場合、このAPIはユーザー操作起点でないと動作しないという制限があることに注意。
            Notification.requestPermission(function(){
                gen.desktopNotification.changeStateSub(cat, isOn);
            });
        } else {
            gen.desktopNotification.changeStateSub(cat, isOn);
        }
    },
    changeStateSub: function(cat, isOn) {
        gen.ajax.connect('Config_Setting_AjaxDesktopNotification', {op:'change', cat:cat, val:isOn},
            function(j) {
                if (isOn) {
                    // オフからオンになったときは通知処理を開始する。
                    // 逆の場合はサーバー側で通知を止めるのでなにもしなくていい。
                    gen.desktopNotification.show();
                }
            }
        );
    }
};

//**************************************
// 郵便番号
//**************************************

gen.zip = {
    toAddress: function(zip,callback) {
        if (zip == "") {
            return false;
        }
        var succFunc = function(res) {
            if (res.status == "OK") {
                var adrObj = res.results[0].address_components;
                var adrSize = adrObj.length - 1;
                if (adrObj[adrSize].short_name != "JP") {
                    callback(false);
                    return;
                }
                var adr = "";
                for (var i=adrSize-1;i>0;--i) {
                    adr += adrObj[i].long_name;
                }
                callback(adr);
            } else {
                callback(false);
            }
        };
        // クロスドメインなのでYUIは×
        $.ajax({
            type : 'get',
            url : 'https://maps.googleapis.com/maps/api/geocode/json',
            crossDomain : true,
            dataType : 'json',
            data : {
                address : zip,
                language : 'ja',
                sensor : false
            },
            success : succFunc
        });
    }
};


// **************************************
//  For List
// **************************************

// List画面オブジェクト
gen.list = {
    // List画面のonLoad
    init: function(isDetailMode, dataCount, fixColCount, colCount, listAction, reqId, fixWidth,
        titleRowHeight, actionWithColumnMode, actionWithPageMode, isPageLoad, existWrapOn) {

        this.listAction = listAction;
        this.reqId = reqId;
        this.actionWithColumnMode = actionWithColumnMode;
        this.actionWithPageMode = actionWithPageMode;
        this.existWrapOn = existWrapOn;

        if (!isDetailMode) {    // list
            this.table = new gen.listDataTable(dataCount, fixColCount, colCount, fixWidth, titleRowHeight);
            this.table.init();
            if (isPageLoad) {
                this.table.shortcutInit();
            }
        } else {                // detail
            this.table = new gen.detailDataTable(dataCount, fixColCount, colCount, fixWidth, titleRowHeight);
            this.table.init();
            this.table.shortcutInit();
        }

        gen.colwidth.init(actionWithColumnMode);  // gen_colwidth.js
        if (isPageLoad) {
            window.document.onkeydown = gen.window.onkeydown;
        }
        this.ddFlag = false;    // 列移動のときリロードされなくなったので、ここでフラグを消しておく
        this.columnAddDialog = null;

        // チップヘルプの初期化 (jquery.cluetip.js）。
        // 以前はここ（onDomContentLoadedのタイミング）でそのまま実行していた。しかしリストのサイズが大きい時は意外に
        // 時間がかかり、そのぶんリストのレンダリングが遅れるため、レンダリング終了後（onLoad）に実行するようにした。
        if (isPageLoad) {
            $(function(){
                gen.ui.initChipHelp();
            });
        } else {
            gen.ui.initChipHelp();
        }

        $('#F1').mousewheel(function(eo, delta, deltaX, deltaY) {
            gen.list.table.onDivScrollForF1(deltaY);
        });
    },

    // 再表示ボタン
    postForm: function() {
        var p = {};
        var elms = document.getElementById('form1').elements;
        for (i=0; i<elms.length; i++) {
            p[elms[i].name] = elms[i].value;
        }
        p['gen_search_page'] = 1;   // 1ページ目をセット
        listUpdate(p, false);       // list.tpl
    },

    // 印刷
    printReport: function(action, checkboxPrefix) {
        var frm;
        if (checkboxPrefix=='') {
            // チェックボックスなし。表示条件に合致するすべてのデータを印刷する
            frm = new gen.postSubmit(document.getElementById("form1"));
        } else {
            // チェックされているデータのみ印刷する
            frm = gen.list.table.getCheckedPostSubmit(checkboxPrefix);
            if (frm.count == 0) {
                alert(_g("印刷するデータを指定してください。"));
                return;
            }
        }
        frm.submit('index.php?action=' + action);
        // 画面更新とwaitダイアログ表示。
        // listUpdateによるAjax更新はReportクラスの処理が終わるまでsession_start()で足止めになるので、
        // 結果として帳票処理が終わるまでダイアログが出たままとなる。ただしChromeはダイアログが出ない
        listUpdate(null, false, true);
    },

    multiEdit: function(editUrl, checkboxPrefix, maxCount) {
        var count = 0;
        var params = '';
        $('[name^='+checkboxPrefix+'_]').each(function() {
            if (this.checked) {
                if (params!='') params += ',';
                params += this.value;
                count++;
            }
        });
        if (count > maxCount) {
            alert(_g("%s1件を超えるレコードを同時に編集することはできません。（現在の選択件数：%s2件）").replace('%s1',maxCount).replace('%s2',count));
            return;
        }
        if (count==0) {
            alert(_g("編集するデータを選択してください。"));
            return;
        }
        gen.modal.open(editUrl + params);
    },

    newRecord: function() {
        var tr = $('.gen_tr_new_record');
        tr.css('display', (tr.css('display')=='table-row' ? 'none' : 'table-row'));
    },

    // 文字列範囲検索ロック
    strPatternChange: function(colName, actionWithPageMode) {
        var val = $('#gen_strPattern_' + colName).val();
        var elm = $('#' + colName + '_to');
        var pin = $('#gen_pin_off_' + colName + '_to');
        if (val=='-1') {
            elm.css('background-color','');
            gen.ui.enabled(elm);
            pin.removeAttr('disabled');
        } else {
            elm.css('background-color','#cccccc');
            gen.ui.disabled(elm);
            elm.val('');
            gen.pin.turnOff(actionWithPageMode, colName + '_to', '');
            pin.attr('disabled', 'disabled');
        }
        return;
    },

    // ページヘルプダイアログ
    showPageHelpDialog: function(word) {
        if (this.pageHelpDialog == null) {
            // デフォルト。ダイアログ生成時にウィンドウサイズにより自動調整される
            var pos = $('#gen_pageHelpDialogParent').offset();
            pos.left -= 220;
            var width = 210;
            var height = 200;

            var url = $('#gen_support_link').val();
            var html =
                  "<div style='height:15px'></div>"
                  + "<input type='textbox' id='gen_pagehelp_searchBox' style='ime-mode:active; width:150px;' value='" + word + "'>"
                  + "<input type='button' value='" + _g("検索") + "' onclick='gen.list.searchHelp()' style='font-size:11px; padding: 3px;'>"
                  + "<div style='height:5px'></div>"
                  + "<span style='padding:5px;font-size:11px;'>"
                    + _g('※キーワードを検索すると、FAQサイトの回答が表示されます。')
                    + "<div style='height:30px'></div>"
                    + (url == "" ? "" : "<a href='" + url + "' style='font-size:11px;color:black' target='_blank'>" + _g("サポートサイト（マニュアル）へ") + "</a>")
                  + "</span>";

            this.pageHelpDialog = gen.dialog.create('gen_body', 'gen_pageHelpDialog', pos.left, pos.top, width, height, _g("ヘルプ"), html, true, true);
        }
        this.pageHelpDialog.show();
    },
    searchHelp: function() {
        var url = $('#gen_faq_search_link').val();
        if (url != '') {
            url += encodeURIComponent($('#gen_pagehelp_searchBox').val());
            window.open(url);
        }
        this.pageHelpDialog.hide();
    }

    ,saveSearchCondition: function() {
        var name = window.prompt(_g('現在の表示条件をパターンとして保存します。パターン名を入力してください。（例：「今月の未完了データ」）'),"");
        if (name==null) return;
        var cancel = false;
        var labels = $('#gen_search_gen_savedSearchCondition').text().split("\n");
        $.each(labels, function(i,val){
            if (val==name) {
                cancel = !window.confirm(_g('指定された名前はすでに使用されています。上書きしてもよろしいですか？'));
                return false;
            }
        });
        if (cancel) return;

        $('#form1').append("<input type='hidden' name='gen_search_gen_savedSearchConditionName' value='"+name+"'>");
        gen.post(gen.list.listAction, {}, document.getElementById('form1'));
    }
    ,deleteSavedSearchCondition: function() {
        var id = $('#gen_search_gen_savedSearchCondition option:selected').val();
        if (id=='') {
            alert(_g('パターンを選択してから削除ボタンを押してください。'));
            return;
        }
        if (id >= 1000) {
            alert(_g('システム標準のパターンは削除できません。'));
            return;
        }
        if (!window.confirm(_g('現在選択されているパターンを削除します。よろしいですか？'),"")) {
            return;
        }
        var name = $('#gen_search_gen_savedSearchCondition option:selected').text();
        $('#form1').append("<input type='hidden' name='gen_search_gen_deleteSavedSearchConditionName' value='"+name+"'>");
        gen.post(gen.list.listAction, {}, document.getElementById('form1'));
    },

    swapCrossAxis: function() {
        var horiz = $('#gen_search_gen_crossTableHorizontal').val();
        $('#gen_search_gen_crossTableHorizontal').val($('#gen_search_gen_crossTableVertical').val());
        $('#gen_search_gen_crossTableVertical').val(horiz);
        gen.list.postForm();
    }
};

// データテーブルのベースオブジェクト（ListとDetailのデータテーブルの共通部分）
gen.dataTable = function(dataCount, fixColCount, colCount, fixWidth, titleRowHeight) {
    // コンストラクタ
    this.dataCount = dataCount;
    this.fixColCount = fixColCount;
    this.colCount = colCount;
    this.fixWidth = fixWidth;
    this.titleRowHeight = titleRowHeight;

    this.f0 = (fixColCount>0 ? document.getElementById('F0') : null);
    this.f1 = (fixColCount>0 ? document.getElementById('F1') : null);
    this.d0 = document.getElementById('D0');
    this.d1 = document.getElementById('D1');
    this.marker = undefined;
    this.lastTop = 0;    // for onDivScroll
    this.contextCreated = false;    // for contextMenu
    this.columnAddDialog = null;    // for columnAddDialog
    this.filterDialog = null;    // for filterDialog
};

gen.dataTable.prototype = {

    // Listテーブルスクロールイベント
    onDivScroll: function() {
        if (this.f0!=null) {
            //chrome対策。 chrome(1.x) は scrollTopの設定が異常に遅いので、scrollTopは縦スクロールのときのみ設定するようにしている。（せめて横スクロールだけでも速くなるように）
            var d1st = this.d1.scrollTop;
            var span = d1st - this.lastTop;
            if (span > 1 || span < -1) {this.f1.scrollTop = d1st;this.lastTop = d1st;}

            this.f0.scrollLeft = this.f1.scrollLeft;
        }
        this.d0.scrollLeft = this.d1.scrollLeft;
    },
    onDivScrollForF1: function(deltaY) {
        this.d1.scrollTop -= (deltaY * 100);
    },

    // 列タイトルセルのD&D用のオブジェクトと右クリックメニューを生成する
    makeTableTitleElm: function() {
        var thisObj = this;
        //列のid名はfunction.gen_data_listのタイトル生成部にあわせること
        for (var i=0;i<this.fixColCount;i++) {
            thisObj.makeDdElm('gen_td_' + i, i);
            thisObj.regContextMenu($('#gen_td_' + i + '_title').get(0),i);

            // 下記を有効にするとデータ行でも右クリックメニューが有効になるが、かなり画面が重くなる。
            // タイトル行・集計行・データ行のtdにはすべて「gen_x[colNum]」というクラスが設定されている。
            //$('.gen_x'+i).each(function(){thisObj.regContextMenu(this,i);});
        }
        for (var i=0;i<this.colCount;i++) {
            thisObj.makeDdElm('gen_td_' + (1000+i), (1000+i));
            thisObj.regContextMenu($('#gen_td_' + (1000+i) + '_title').get(0),(1000+i));

            // 下記を有効にするとデータ行でも右クリックメニューが有効になるが、かなり画面が重くなる。
            //$('.gen_x'+(1000+i)).each(function(){thisObj.regContextMenu(this,(1000+i));});
        }
    },

     // 上記のsub
    makeDdElm: function(idBase, num) {
         var dd = new YAHOO.util.DDProxy(idBase + '_innerTitle');
         new YAHOO.util.DDTarget(idBase + '_title');
         var rightLimit;
         var scrollLeft;
         dd.d0 = this.d0;
         dd.marker = this.marker;
         //dd.setYConstraint(0, 0);  左記を有効にすると横方向にしか動かなくなるが、メニューバーや検索ウィンドウを開閉したあとでのD&Dで動きがおかしくなる
         dd.startDrag = function() {
             this.marker = document.createElement('div');
             this.marker.innerHTML = '▼';
             this.marker.style.color ='#999999';
             this.marker.style.position = 'absolute';
             var d0pos = $(this.d0).offset();
             this.marker.style.top = (d0pos.top - 15) + 'px';
             this.marker.style.visibility = 'hidden';
             document.body.appendChild(this.marker);
             rightLimit = parseInt(d0pos.left) + parseInt(this.d0.style.width);
             scrollLeft = parseInt(d0pos.left);
         };
         dd.onDragEnter = function(e, targetId) {
             targetNum = parseInt(targetId.replace('gen_td_','').replace('_title',''),10);
             if (num == targetNum) return; // 自分自身
             var targetElm = document.getElementById(targetId);
             var targetPos = $(targetElm).offset();
             var pos = (targetPos.left -7);
             if (pos > rightLimit) return;
             if (targetPos.left < scrollLeft && targetNum >= 1000) return; //横スクロールによりfix列とscroll列が重なった状態のとき、背後にあるscroll列側のイベントはキャンセルする
             this.marker.style.left = pos + 'px';
             this.marker.style.visibility = 'visible';
         };
         dd.onDragOut = function(e, targetId) {
             targetNum = parseInt(targetId.replace('gen_td_','').replace('_title',''),10);
             var targetElm = document.getElementById(targetId);
             var targetPos = $(targetElm).offset();
             if (targetPos.left < scrollLeft && targetNum >= 1000) return; //横スクロールによりfix列とscroll列が重なった状態のとき、背後にあるscroll列側のイベントはキャンセルする
             this.marker.style.visibility = 'hidden';
         };
         dd.onDragDrop = function(e, targetId) {
             if (targetId.indexOf('_title')==-1) return;
             if (gen.list.ddFlag) return; //DragDropイベントは1度しか受け付けないようにする。横スクロールによりfix列とscroll列が重なった状態のとき、fix列とscroll列の両方でイベントが発生するため。最初に発生するfix列のイベントを処理する
             targetNum = parseInt(targetId.replace('gen_td_','').replace('_title',''),10);
             if (num == targetNum) return; // 自分自身
             gen.list.ddFlag = true;
             listUpdate({gen_page_request_id:gen.list.reqId, gen_dd_num:num, gen_ddtarget_num:targetNum},false);
         };
         dd.endDrag = function(e) {
             document.body.removeChild(this.marker);
             this.marker = null;
         };
    },

    // fixとscrの行の高さを揃える（wrapOn列があるとき用）
    adjustRowHeight: function(startRow, endRow) {
        var no = startRow, fix, scr, fixH, scrH;
        while(endRow === undefined || endRow >= no) {
            fix = document.getElementById('gen_tr_fixtable_' + no);
            if (fix === null)
                break;
            scr = document.getElementById('gen_tr_scrtable_' + no);
            if (scr === null)
                break;
            fixH = fix.offsetHeight;
            scrH = scr.offsetHeight;
            if (fixH < scrH) {
                fix.style.height = scrH + "px";
                scr.style.height = scrH + "px"; // 無意味ではない。この設定をしないと、ブラウザによってはpxの小数点以下の処理の関係でfixとscrがずれる
            } else if (fixH > scrH) {
                scr.style.height = fixH + "px";
                fix.style.height = fixH + "px";
            }
            no++;
        }
    },

    // 列状態リセット
    columnReset: function() {
        if (confirm(_g("この画面のすべての列の並び順・列幅・小数点以下の桁数・横方向の位置・非表示を初期状態に戻します。よろしいですか？"))) {
            location.href='index.php?action=' + gen.list.listAction +
            '&gen_restore_search_condition=true&gen_columnReset' +
            '&gen_page_request_id=' + gen.list.reqId;
        }
    },

    // 表示条件リセット
    searchColumnReset: function() {
        if (confirm(_g("表示条件を初期状態に戻します。実行してもよろしいですか？"))) {
            location.href='index.php?action=' + gen.list.listAction +
            '&gen_restore_search_condition=true&gen_searchColumnReset' +
            '&gen_page_request_id=' + gen.list.reqId;
        }
    },

    // 項目の列幅自動調整
    autoAllFit: function(msg) {
        if (confirm(msg)) {
            // 各列毎に列幅を自動調整する
            $('td[id$="_innerTitle"]').each(function() {
                var colNum = (this.id).replace("gen_td_", "").replace("_innerTitle", "");
                if (gen.util.isNumeric(colNum)) {
                    gen.colwidth.onStartDrag(colNum);
                    gen.colwidth.autofit(colNum);
                    gen.colwidth.onMouseUp();
                }
            });
        }
    },

    // ソートリセット
    sortReset: function(msg) {
        if (confirm(msg)) {
            location.href='index.php?action=' + gen.list.listAction +
            '&gen_restore_search_condition=true&gen_sortReset' +
            '&gen_page_request_id=' + gen.list.reqId;
        }
    },

    // 「直接編集」チェックボックス
    directEditChange: function() {
        gen_isDE = ($('#gen_directEdit').is(':checked'));
        gen.list.table.updateCursor();
        if (gen_isDE) {
            $('#gen_listClickEnable').attr("disabled", "disabled");
        } else {
            $('#gen_listClickEnable').removeAttr("disabled");
        }
        $('#gen_listClickEnableLabel').css('color', gen_isDE ? "#ccc" : "");
        gen.ajax.connect('Config_Setting_AjaxDirectEdit', {directEdit : gen_isDE},
            function(){
            });
    },

    // 「リスト行クリックで明細画面を開く」チェックボックスの値をキャッシュ
    listClickEnableCache: function() {
        gen_isLCE = ($('#gen_listClickEnable').is(':checked'));
        gen.list.table.updateCursor();
        gen.ajax.connect('Config_Setting_AjaxListClickEnable', {listClickEnable : gen_isLCE},
            function(){
            });
    },

    updateCursor: function() {
        $('.gen_listTR').css('cursor',gen_isLCE && !gen_isDE ? 'pointer' : 'default');
    },

    // チェックボックスの状態を切り替え
    alterCheckbox: function(name) {
        if (typeof gen_domArr_D != "undefined" && gen_domArr_D != null) {
            alert(_g("すべての行が表示されるまでお待ちください。"));
            return;
        }
        var value = true;
        var mode = $('#gen_hidden_'+name+'_checkbox').val();
        if (mode == 1) {
            $('#gen_hidden_'+name+'_checkbox').val('0');
            value = false;
        } else {
            $('#gen_hidden_'+name+'_checkbox').val('1');
        }
        $('[name^='+name+'_]').each(function() {
            this.checked = value;
        });
    },

    // オンになったチェックボックスを登録済みのpostSubmitオブジェクトを返す
    // 第2引数が指定されていれば、オンになったチェックボックスと同じ行のエレメント
    // （nameの「_」以降が行IDとみなされる）も登録される。
    // 使用例： var frm = gen.list.table.getCheckedPostSubmit('received_detail_id', new Array('received_quantity'));
    getCheckedPostSubmit: function(checkboxName, otherNameArr, frmObject) {
        var frm = new gen.postSubmit(frmObject);
        var count = 0;
        $('[name^='+checkboxName+'_]').each(function() {
            if (this.checked) {
                frm.add(this.name, this.value);
                if (otherNameArr instanceof Array) {
                    lineId = this.name.substr(checkboxName.length+1);
                    $.each(otherNameArr, function(i, name) {
                        elm = $('#' + name + '_' + lineId);
                        if (elm!=null) {
                            if (elm.attr('type')=='checkbox') {
                                if (elm.is(':checked')) {
                                    frm.add(elm.attr('name'), elm.val());
                                }
                            } else {
                                frm.add(elm.attr('name'), elm.val());
                            }
                        }
                    });
                }
                count++;
            }
        });
        frm.count = count;
        return frm;
    },

    // 削除確認
    deleteItem: function(url, msg) {
        if (window.confirm(msg)) {
            location.href = url;
        }
    },

    // 削除チェックボックスの状態を切り替え
    alterDeleteCheck: function() {
        if (typeof gen_domArr_D !== "undefined" && gen_domArr_D != null) {
            alert(_g("すべての行が表示されるまでお待ちください。"));
            return;
        }
        var value = true;
        var mode = $('#gen_hidden_delete_check').val();
        if (mode == 1) {
            $('#gen_hidden_delete_check').val('0');
            value = false;
        } else {
            $('#gen_hidden_delete_check').val('1');
        }
        var elms = document.getElementById('form1').elements;
        for (i=0; i<elms.length; i++) {
            if (elms[i].name.substr(0,7) == 'delete_' && elms[i].checked != value) {
                elms[i].checked = value;
                elms[i].onclick();
            }
        }
    },

    // 一括削除
    bulkDelete: function(action, msg1, msg2, msg3, beforeAction, beforeDetail) {
        var count = 0;
        var postUrl = 'index.php?action=' + action;
        var frm = new gen.postSubmit();
        var elms = document.getElementById('form1').elements;
        var ids = '';
        for (i=0; i<elms.length; i++) {
            if (elms[i].name.substr(0,7) == 'delete_' &&
              elms[i].checked == true) {
                frm.add(elms[i].name, elms[i].value);
                if (ids != '') ids += ',';
                ids += elms[i].name;
                count++;
            }
        }
        if (count == 0) {
            alert(msg1);    // データを選択してください
        } else {
            if (beforeAction != '') {
                gen.ajax.connect(beforeAction, {ids:ids, detail:beforeDetail},
                    function(j) {
                        if (j.status == 'success') {
                            if (!confirm(msg3)) {
                                return;
                            } else {
                                if (!confirm(msg2))
                                    return; // 取り消すことはできません。実行してよろしいですか？
                                frm.submit(postUrl, null);
                            }
                        } else {
                            if (!confirm(msg2))
                                return; // 取り消すことはできません。実行してよろしいですか？
                            frm.submit(postUrl, null);
                        }
                    });
            } else {
                if (!confirm(msg2))
                    return; // 取り消すことはできません。実行してよろしいですか？
                frm.submit(postUrl, null);
            }
        }
    },

    // ページジャンプ
    pageJump: function(lastPage) {
        while (true) {
            number = window.prompt(_g("ジャンプするページを指定してください") + " (1-" + lastPage + ")", "");
            if (number == null) {
                return;
            }

            if (!isNaN(number)) {
                if (number >= 1 && number <= lastPage) {
                    number = parseInt(number,10);
                    listUpdate({gen_search_page:number},false);
                    return;
                }
            }
            alert(_g("ページ指定が正しくありません。"));
        }
    },

    // 表示行数変更
    changeNumberOfItems: function(url, minNumber, maxNumber, msg1, msg2) {
        while (true) {
            number = window.prompt(msg1, "");
            if (number == null) {
                return;
            }

            if (gen.util.isNumeric(number)) {
                if (number >= minNumber && number <= maxNumber) {
                    number = parseInt(number,10);
                    // 1ページ目を表示する。行数を増やした場合にページ数が減る可能性があるので
                    location.href = url + "&gen_search_page=1&gen_numberOfItems=" + number;
                    return;
                }
            }
            alert(msg2);
        }
    },

    // エクスポート（CSV,Excel）
    exportData: function(url, dataCount, dataLimit, msg1, msg2) {
        if (dataCount > dataLimit) {
            while (true) {
                number = window.prompt(msg1, 1);
                if (number == null) {
                    return;
                }

                if (gen.util.isNumeric(number)) {
                    if (number > 0) {
                        break;
                    }
                }
                alert(msg2);
            }
            url += "&gen_csvOffset=" + number;
        }
        location.href = url;
        return;
    },

    // 右クリックメニューの登録
    regContextMenu: function(elm, colNum) {
        var info = $('#gen_hidden_' + colNum + '_info').val();
        if (info == undefined) return;

        if ( !this.contextCreated ) {
            var html =
                "<ul id='gen_contextMenu' class='contextMenu' style='display:none'>" +
                "<span class='gen_context_column_filter'><li class=''><a href='#column_filter'>"+_g("フィルタ")+"...</a></li></span>" +
                "<span class='gen_context_subsum_criteria'><li class='separator'><a href='#subsum_criteria'>"+_g("小計基準にする")+"</a></li></span>" +
                "<span class='gen_context_subsum_criteria_date'><li class='separator'><a href='#subsum_criteria_year'>"+_g("小計基準にする（年）")+"</a></li>" +
                "<li class='norm'><a href='#subsum_criteria_month'>"+_g("小計基準にする（月）")+"</a></li>" +
                "<li class='norm'><a href='#subsum_criteria_day'>"+_g("小計基準にする（日）")+"</a></li></span>" +
                "<span class='gen_context_subsum_criteria_delete'><li class='separator'><a href='#subsum_criteria_delete'>"+_g("小計基準を解除")+"</a></li></span>" +
                "<li class='separator'><a href='#orderby'>"+_g("並べ替え")+" (▲)</a></li>" +
                "<li class='norm'><a href='#orderby_desc'>"+_g("並べ替え")+" (▼)</a></li>" +
                "<span class='gen_context_orderby_delete'><li class='norm'><a href='#orderby_delete'>"+_g("並べ替え解除")+"</a></li></span>" +
                "<li class='separator'><img src='img/space.gif' class='imgContainer sprite-tick gen_ctx_align_left_tick' align='left' style='display:none;position:absolute;'><a href='#align_left'>"+_g("左寄せ")+"</a></li>" +
                "<li class='norm'><img src='img/space.gif' class='imgContainer sprite-tick gen_ctx_align_center_tick' align='left' style='display:none;position:absolute;'><a href='#align_center'>"+_g("中央")+"</a></li>" +
                "<li class='norm'><img src='img/space.gif' class='imgContainer sprite-tick gen_ctx_align_right_tick' align='left' style='display:none;position:absolute;'><a href='#align_right'>"+_g("右寄せ")+"</a></li>" +
                "<li class='separator'><img src='img/space.gif' class='imgContainer sprite-tick gen_ctx_wrap_on_tick' align='left' style='display:none;position:absolute;'><a href='#wrap_on'>"+_g("折り返して全体を表示")+"</a></li>" +
                "<li class='separator'><a href='#column_autofit'>"+_g("列幅の自動調整")+"</a></li>" +
                "<span class='gen_context_column_hide'><li class='separator'><a href='#column_hide'>"+_g("列を非表示にする")+"</a></li></span>" +
                "<li class='separator'><a href='#quit'>"+_g("閉じる")+"</a></li>" +
                "</ul>" +

                "<ul id='gen_contextMenuForNum' class='contextMenu' style='display:none'>" +
                "<span class='gen_context_column_filter'><li class=''><a href='#column_filter'>"+_g("フィルタ")+"...</a></li></span>" +
                "<span class='gen_context_subsum_criteria'><li class=''><a href='#subsum_criteria'>"+_g("小計基準にする")+"</a></li></span>" +
                "<span class='gen_context_subsum_criteria_delete'><li class='separator'><a href='#subsum_criteria_delete'>"+_g("小計基準を解除")+"</a></li></span>" +
                "<li class='separator'><img src='img/space.gif' class='imgContainer sprite-tick gen_ctx_align_left_tick' align='left' style='display:none;position:absolute;'><a href='#align_left'>"+_g("左寄せ")+"</a></li>" +
                "<li class=''><img src='img/space.gif' class='imgContainer sprite-tick gen_ctx_align_center_tick' align='left' style='display:none;position:absolute;'><a href='#align_center'>"+_g("中央")+"</a></li>" +
                "<li class=''><img src='img/space.gif' class='imgContainer sprite-tick gen_ctx_align_right_tick' align='left' style='display:none;position:absolute;'><a href='#align_right'>"+_g("右寄せ")+"</a></li>" +
                "<li class='separator'><a href='#orderby'>"+_g("並べ替え")+" (▲)</a></li>" +
                "<li class='norm'><a href='#orderby_desc'>"+_g("並べ替え")+" (▼)</a></li>" +
                "<span class='gen_context_orderby_delete'><li class='norm'><a href='#orderby_delete'>"+_g("並べ替え解除")+"</a></li></span>" +
                "<li class='separator'><img src='img/space.gif' class='imgContainer sprite-tick gen_ctx_kanma_show_tick' align='left' style='display:none;position:absolute;'><a href='#show_kanma'>"+_g("3桁区切りを表示")+"</a></li>" +
                "<li class='separator'><img src='img/space.gif' class='imgContainer sprite-tick gen_ctx_keta0_tick' align='left' style='display:none;position:absolute;'><a href='#keta0'>"+_g("小数点以下を0桁に")+"</a></li>" +
                "<li class=''><img src='img/space.gif' class='imgContainer sprite-tick gen_ctx_keta1_tick' align='left' style='display:none;position:absolute;'><a href='#keta1'>"+_g("小数点以下を1桁に")+"</a></li>" +
                "<li class=''><img src='img/space.gif' class='imgContainer sprite-tick gen_ctx_keta2_tick' align='left' style='display:none;position:absolute;'><a href='#keta2'>"+_g("小数点以下を2桁に")+"</a></li>" +
                "<li class=''><img src='img/space.gif' class='imgContainer sprite-tick gen_ctx_keta3_tick' align='left' style='display:none;position:absolute;'><a href='#keta3'>"+_g("小数点以下を3桁に")+"</a></li>" +
                "<li class=''><img src='img/space.gif' class='imgContainer sprite-tick gen_ctx_keta4_tick' align='left' style='display:none;position:absolute;'><a href='#keta4'>"+_g("小数点以下を4桁に")+"</a></li>" +
                "<li class=''><img src='img/space.gif' class='imgContainer sprite-tick gen_ctx_keta-1_tick' align='left' style='display:none;position:absolute;'><a href='#keta-1'>"+_g("小数点以下を自然丸め")+"</a></li>" +
                "<li class='separator'><img src='img/space.gif' class='imgContainer sprite-tick gen_ctx_wrap_on_tick' align='left' style='display:none;position:absolute;'><a href='#wrap_on'>"+_g("折り返して全体を表示")+"</a></li>" +
                "<li class='separator'><a href='#column_autofit'>"+_g("列幅の自動調整")+"</a></li>" +
                "<span class='gen_context_column_hide'><li class='separator'><a href='#column_hide'>"+_g("列を非表示にする")+"</a></li></span>" +
                "<li class='separator'><a href='#quit'>"+_g("閉じる")+"</a></li>" +
                "</ul>" +
                "<ul id='gen_contextMenuForCross' class='contextMenu' style='display:none'>" +
                "<li class='separator'>"+_g("※クロス集計時は列の設定を変更できません。")+"</li>" +
                "<li class='separator'><a href='#quit'>"+_g("閉じる")+"</a></li>" +
                "</ul>";
            $('#gen_dataTable').append(html);
            this.contextCreated = true;
        }

        var type = info.split(",")[0];
        // コンテキストメニューを設定するエレメントにidがついていない場合、仮idをつける。
        // （htmlサイズ縮小のため、listのデータ行のtdには、2行目以降idがついていない。）
        // gen.contextMenu.init の第一引数にidを指定する必要があるため。
        // 引数にidではなくエレメントやjQueryオブジェクトを指定するようにすればいいように思えるが、
        // それだと gen.contextMenu内の標準コンテキストメニューの削除処理がうまく動作しない。
        if (elm.id=='') elm.id = 'gen_context_temp';
        // クロス集計時は列の設定を行えないようにする。リスト列が通常とは異なるため
        gen.contextMenu.init(elm.id, ($('#gen_crossTableShow').length > 0 ? 'gen_contextMenuForCross' : (type=='numeric' ? 'gen_contextMenuForNum' : 'gen_contextMenu')),
          // メニュー表示時に実行される
          function() {
                // コンテキストメニューの選択状態の表示
                gen.list.table.showContextSelectedIcon(colNum);
                // denyMove列は選択肢「列を非表示にする」を表示しない
                var denyMove = (info.split(",")[5] == '1');
                $('.gen_context_column_hide').css('display', (denyMove ? 'none' : ''));

                var type = info.split(",")[0];
                var filterShow = (type == 'data' || type == 'numeric' || type == 'date' || type == 'datetime');
                $('.gen_context_column_filter').css('display', (filterShow ? '' : 'none'));
                var existOrderby = (gen.list.table.getLocalColumnInfo(colNum, 'col_existOrderby') != '');
                $('.gen_context_orderby_delete').css('display', existOrderby ? '' : 'none');
                var existSubsum = (gen.list.table.getLocalColumnInfo(colNum, 'col_existSubsum') == '1');
                $('.gen_context_subsum_criteria').css('display', (!existSubsum && (type == 'data' || type == 'numeric') ? '' : 'none'));
                $('.gen_context_subsum_criteria_date').css('display', (!existSubsum && (type == 'date' || type == 'datetime') ? '' : 'none'));
                $('.gen_context_subsum_criteria_delete').css('display', existSubsum ? '' : 'none');
          },
          // メニュー選択時に実行される
          function(action) {
              switch(action) {
                case "column_filter":
                        gen.list.table.filterColumn(colNum, elm.id);
                        break;
                case "subsum_criteria":
                        gen.list.table.subSumColumn(colNum, null);
                        break;
                case "subsum_criteria_year":
                        gen.list.table.subSumColumn(colNum, 0);
                        break;
                case "subsum_criteria_month":
                        gen.list.table.subSumColumn(colNum, 1);
                        break;
                case "subsum_criteria_day":
                        gen.list.table.subSumColumn(colNum, 2);
                        break;
                case "subsum_criteria_delete":
                        listUpdate({gen_subSumCriteriaColNum : colNum, gen_subSumCriteriaClear : true},false,false);
                        break;
                case "show_kanma":
                        gen.list.table.kanmaColumn(colNum, true);
                        break;
//                case "hide_kanma":
//                        gen.list.table.kanmaColumn(colNum, false);
//                        break;
                case "keta0":
                        gen.list.table.ketaColumn(colNum, 0);
                        break;
                case "keta1":
                        gen.list.table.ketaColumn(colNum, 1);
                        break;
                case "keta2":
                        gen.list.table.ketaColumn(colNum, 2);
                        break;
                case "keta3":
                        gen.list.table.ketaColumn(colNum, 3);
                        break;
                case "keta4":
                        gen.list.table.ketaColumn(colNum, 4);
                        break;
                case "keta-1":
                        gen.list.table.ketaColumn(colNum, -1);
                        break;
                case "align_left":
                        gen.list.table.alignColumn(colNum, "left");
                        break;
                case "align_center":
                        gen.list.table.alignColumn(colNum, "center");
                        break;
                case "align_right":
                        gen.list.table.alignColumn(colNum, "right");
                        break;
                case "orderby":
                        gen.list.table.doOrderby(colNum, "");
                        break;
                case "orderby_desc":
                        gen.list.table.doOrderby(colNum, "desc");
                        break;
                case "orderby_delete":
                        gen.list.table.doOrderby(colNum, "delete");
                        break;
                case "wrap_on":
                        gen.list.table.wrapOn(colNum);
                        break;
                case "column_autofit":
                        gen.colwidth.onStartDrag(colNum);
                        gen.colwidth.autofit(colNum);
                        gen.colwidth.onMouseUp();
                        break;
                case "column_hide":
                        gen.list.table.hideColumn(colNum);
                        break;
                default:
              }
          });
        // テンポラリid解除
        if (elm.id=='gen_context_temp') elm.id = '';
    },

    // 列情報の保存（右クリックメニュー用）
    saveColumnInfo: function(colNum, infoName, val, callback) {
        // サーバーへの保存
        var o = {
            action_name : gen.list.actionWithColumnMode,
            col_num : colNum,
            is_cross : ($('#gen_crossTableShow').length > 0)
        };
        o[infoName] = val;
        if (callback == undefined)
            callback = function(){};
        gen.ajax.connect('Config_Setting_AjaxListColInfo', o, callback);

        // ローカル保存（HTML内の列情報を更新）
        gen.list.table.updateLocalColumnInfo(colNum, infoName, val);
    },

    // ローカル列情報（html hidden）を更新
    updateLocalColumnInfo: function(colNum, infoName, val) {
        var elm = $("#gen_hidden_" + colNum + "_info");
        var arr = elm.val().split(",");
        var infoNum = gen.list.table.getHtmlInfoNum(infoName);
        var str = "";
        if (infoNum>0) {
            for (var i=0;i<infoNum;i++) {
                str += arr[i] + ",";
            }
        }
        str += val + ",";
        if (infoNum+1 < arr.length-1) {
            for (var i=infoNum+1;i<arr.length-1;i++) {
                str += arr[i] + ",";
            }
        }
        elm.val(str);
    },

    // ローカル列情報（html hidden）を読取り
    getLocalColumnInfo: function(colNum, infoName) {
        var elm = $("#gen_hidden_" + colNum + "_info");
        var arr = elm.val().split(",");
        var infoNum = gen.list.table.getHtmlInfoNum(infoName);
        return arr[infoNum];
    },

    // ローカル列情報（html hidden）の情報名を情報番号に変換
    getHtmlInfoNum: function(infoName) {
        // function.gen_data_list の hidden書き出し部とあわせること
        if (infoName=='col_dataType')  return 0;
        if (infoName=='col_keta')  return 1;
        if (infoName=='col_kanma')  return 2;
        if (infoName=='col_align')  return 3;
        if (infoName=='col_bgcolor')  return 4;
        if (infoName=='col_hide')  return 5;
        if (infoName=='col_entryField')  return 6;
        if (infoName=='col_editType')  return 7;
        if (infoName=='col_editOptions')  return 8;
        if (infoName=='col_wrapon')  return 10;
        if (infoName=='col_orderby')  return 11;
        if (infoName=='col_existOrderby')  return 12;
        if (infoName=='col_existSubsum')  return 13;

        alert('infoNameが不正：gen_script.js');
        return -1;
    },

    // ローカル列情報（html hidden）をもとに、コンテキストメニューの選択済アイコンを表示する
    showContextSelectedIcon: function(colNum) {
        // いったんすべて非表示
        $('[class*=gen_ctx_]').hide();    // imgに複数クラスを指定しているので ^= は不可

        var keta = gen.list.table.getLocalColumnInfo(colNum, 'col_keta');
        switch (keta) {
        case '0':$('.gen_ctx_keta0_tick').show();break;
        case '1':$('.gen_ctx_keta1_tick').show();break;
        case '2':$('.gen_ctx_keta2_tick').show();break;
        case '3':$('.gen_ctx_keta3_tick').show();break;
        case '4':$('.gen_ctx_keta4_tick').show();break;
        case '-1':$('.gen_ctx_keta-1_tick').show();break;
        }

        var kanma = gen.list.table.getLocalColumnInfo(colNum, 'col_kanma');
        if (kanma == "1") {
            $('.gen_ctx_kanma_show_tick').show();
        }

        var align = gen.list.table.getLocalColumnInfo(colNum, 'col_align');
        switch (align) {
        case '0':$('.gen_ctx_align_left_tick').show();break;
        case '1':$('.gen_ctx_align_center_tick').show();break;
        case '2':$('.gen_ctx_align_right_tick').show();break;
        }

        var wrapon = gen.list.table.getLocalColumnInfo(colNum, 'col_wrapon');
        if (wrapon == "1") {
            $('.gen_ctx_wrap_on_tick').show();
        }
    },

    // 右クリックメニューの処理を表示中のカラムに適用
    contextApply: function(colNum, f) {
        //$("div.gen_xd" + colNum + "_title").each(f);    // 集計行
        $("div.gen_xd" + colNum).each(f);                // データ行
    },

    // ***** ここから 各メニューの処理

    filterColumn: function(colNum, parentElmId) {
        gen.list.table.showFilterDialog(colNum, parentElmId);
    },

    subSumColumn: function(colNum, dateType) {
        listUpdate({gen_subSumCriteriaColNum : colNum, gen_subSumCriteriaDateType : dateType},false,false);
    },

    // リスト列の3桁区切りカンマ有無（右クリックメニュー用）
    kanmaColumn: function(colNum) {
        var current = gen.list.table.getLocalColumnInfo(colNum, 'col_kanma');
        var isShow = (current == "1" ? false : true);
        var f = function() {
            var elm = $(this);
            var val = elm.html();
            if (isShow) {
                if (gen.util.isNumeric(val)) elm.html(gen.util.addFigure(val));
            } else {
                val = val.replace(/,/g, "");
                if (gen.util.isNumeric(val)) elm.html(val);
            }
        };
        gen.list.table.contextApply(colNum, f);
        gen.list.table.saveColumnInfo(colNum, "col_kanma", (isShow ? 1 : 0));
    },

    // リスト列の数字の丸め（右クリックメニュー用）
    // 表示桁数が増えた時のため、再表示を行う必要がある。
    ketaColumn: function(colNum, place) {
        gen.list.table.saveColumnInfo(colNum, "col_keta", place, function(){gen.list.postForm();});
    },

    // リスト列の表示位置（右クリックメニュー用）
    alignColumn: function(colNum, align) {
        var f = function() {
            $(this).css("text-align", align);
        };
        gen.list.table.contextApply(colNum, f);
        var alignNum = (align=="left" ? 0 : (align=="center" ? 1 : 2));    // 0:左寄せ 1:中央 2:右寄せ
        gen.list.table.saveColumnInfo(colNum, "col_align", alignNum);
    },

    // リスト列の非表示（右クリックメニュー用）
    hideColumn: function(colNum) {
        gen.colwidth.hide(colNum);
        // 列の追加ダイアログが未存在のときにダイアログの内容が更新されるようにするため、再表示を行う
        gen.list.table.saveColumnInfo(colNum, "col_hide", true, function(){gen.list.postForm()});
        // 列の追加ダイアログが存在したら更新しておく。この場合、上の再表示処理では更新されないので
        if ($('#gen_columntr_'+colNum).length > 0) {
            $('#gen_columntr_'+colNum).attr("bgcolor","#cccccc");
            $('#gen_columnadd_'+colNum).attr("checked",false);
        }
    },

    // 並べ替え
    doOrderby: function(colNum, opt) {
        var p = gen.list.table.getLocalColumnInfo(colNum, 'col_orderby');
        var obj = eval("(" + p + ")");
        var col = obj['gen_search_orderby'].replace(' desc','');
        if (opt == "delete") {
            obj['gen_orderby_delete'] = col;
            obj['gen_search_orderby'] = null;
        } else {
            obj['gen_search_orderby'] = col + (opt == "desc" ? " desc" : "");
        }
        listUpdate(obj, false);
    },

    // 折り返して全体を表示（右クリックメニュー用）
    wrapOn: function(colNum) {
        var current = gen.list.table.getLocalColumnInfo(colNum, 'col_wrapon');
        var wrapon = (current == "1" ? "0" : "1");
        var ws = (wrapon == "1" ? "normal" : "nowrap");
        var ba = (wrapon == "1" ? "break-all" : "normal");
        var f = function() {
            $(this).css("white-space", ws).css("word-break", ba);
        };
        gen.list.table.contextApply(colNum, f);
        gen.list.table.adjustRowHeight(0);
        if (wrapon == "1") {
            gen.list.existWrapOn = true;
        }
        gen.list.table.saveColumnInfo(colNum, "col_wrapon", wrapon);
    },

    // 列フィルタダイアログ
    showFilterDialog: function(colNum, parentElmId) {
        if (this.filterDialog != null) {
            this.filterDialog.destroy();
        }

        // デフォルト。ダイアログ生成時にウィンドウサイズにより自動調整される
        var parentElm = $('#'+parentElmId);
        var pos = parentElm.offset();
        pos.top += 20;
        var width = 300;
        var height = 200;

        var type = "";
        var info = $('#gen_hidden_' + colNum + '_info').val();
        if (info != undefined) {
            var infoArr = info.split(",");
            type = infoArr[0];
        }
        var content = "<div style='height:10px'></div>";
        switch(type) {
            case 'data':
                var optHtml = "";
                optHtml += "<option value='0'>"+_g("を含む")+"</option>";
                optHtml += "<option value='1'>"+_g("で始まる")+"</option>";
                optHtml += "<option value='2'>"+_g("で終わる")+"</option>";
                optHtml += "<option value='3'>"+_g("と一致")+"</option>";
                optHtml += "<option value='4'>"+_g("を含まない")+"</option>";
                optHtml += "<option value='5'>"+_g("で始まらない")+"</option>";
                optHtml += "<option value='6'>"+_g("で終わらない")+"</option>";
                optHtml += "<option value='98'>("+_g("空欄")+")</option>";
                optHtml += "<option value='99'>("+_g("空欄以外")+")</option>";

                content += "<div style='width:100%; height:100px;'>";
                content += "<input type='hidden' id='gen_filterType' value='data'>";
                content += "<input type='textbox' id='gen_filterSearch1' style='width:150px'>";
                content += "<select id='gen_filterSearchMatchType1' onchange='gen.list.table.filterDialogSelecterChange(1)'>";
                content += optHtml;
                content += "</select><br><br>";
                content += "<input type='radio' name='gen_filterBool' id='gen_filterBool_and' value='and' checked>AND";
                content += "<input type='radio' name='gen_filterBool' id='gen_filterBool_or' value='or'>OR";
                content += "<br><br>";
                content += "<input type='textbox' id='gen_filterSearch2' style='width:150px'>";
                content += "<select id='gen_filterSearchMatchType2' onchange='gen.list.table.filterDialogSelecterChange(2)'>";
                content += optHtml;
                content += "</select>";
                content += "</div>";
                break;

            case 'numeric':
                optHtml += "<option value='0'>"+_g("以上")+"</option>";
                optHtml += "<option value='1'>"+_g("以下")+"</option>";
                optHtml += "<option value='2'>"+_g("と等しい")+"</option>";
                optHtml += "<option value='3'>"+_g("と等しくない")+"</option>";
                optHtml += "<option value='98'>("+_g("空欄")+")</option>";
                optHtml += "<option value='99'>("+_g("空欄以外")+")</option>";

                // 以下はdataと同じ
                content += "<div style='width:100%; height:100px;'>";
                content += "<input type='hidden' id='gen_filterType' value='numeric'>";
                content += "<input type='textbox' id='gen_filterSearch1' style='width:150px'>";
                content += "<select id='gen_filterSearchMatchType1' onchange='gen.list.table.filterDialogSelecterChange(1)'>";
                content += optHtml;
                content += "</select><br><br>";
                content += "<input type='radio' name='gen_filterBool' id='gen_filterBool_and' value='and' checked>AND";
                content += "<input type='radio' name='gen_filterBool' id='gen_filterBool_or' value='or'>OR";
                content += "<br><br>";
                content += "<input type='textbox' id='gen_filterSearch2' style='width:150px'>";
                content += "<select id='gen_filterSearchMatchType2' onchange='gen.list.table.filterDialogSelecterChange(2)'>";
                content += optHtml;
                content += "</select>";
                content += "</div>";
                break;

            case 'date':
            case 'datetime':
                content += "<div style='width:100%; height:80px;'>";
                content += "<input type='hidden' id='gen_filterType' value='date'>";
                content += "<select id=\"gen_datePattern_gen_filterDate\" onchange=\"gen.dateBox.setDatePattern('gen_filterDate');gen.list.table.filterDialogSelecterChangeForDate()\">";
                content += "<option value='-1'></option>";
                content += "<option value='0'>"+_g("なし")+"</option>";
                content += "<option value='1'>"+_g("今日")+"</option>";
                content += "<option value='16'>"+_g("今日以前")+"</option>";
                content += "<option value='3'>"+_g("今週")+"</option>";
                content += "<option value='5'>"+_g("今月")+"</option>";
                content += "<option value='7'>"+_g("今年")+"</option>";
                content += "<option value='13'>"+_g("今年度")+"</option>";
                content += "<option value='2'>"+_g("昨日")+"</option>";
                content += "<option value='4'>"+_g("先週")+"</option>";
                content += "<option value='6'>"+_g("先月")+"</option>";
                content += "<option value='8'>"+_g("昨年")+"</option>";
                content += "<option value='14'>"+_g("昨年度")+"</option>";
                content += "<option value='9'>"+_g("明日")+"</option>";
                content += "<option value='10'>"+_g("来週")+"</option>";
                content += "<option value='11'>"+_g("来月")+"</option>";
                content += "<option value='12'>"+_g("来年")+"</option>";
                content += "<option value='15'>"+_g("来年度")+"</option>";
                content += "<option value='98'>("+_g("空欄")+")</option>";
                content += "<option value='99'>("+_g("空欄以外")+")</option>";
                content += "</select>";
                content += "<div id='gen_filterDate_from_calendar'></div>";
                content += "<input type='text' id='gen_filterDate_from' onchange=\"gen.dateBox.dateFormat('gen_filterDate_from');gen.dateBox.dateFormat('gen_filterDate_to');gen.dateBox.checkDateFromToFormat('gen_filterDate','"+_g("日付が正しくありません。")+"');\" style=\";ime-mode:inactive;; width:80px\">";
                content += "<input type='button' class='mini_button' id='gen_filterDate_from_button' value='▼' onclick=\"gen.calendar.init('gen_filterDate_from_calendar', 'gen_filterDate_from');\">";
                content += "から";
                content += "<div id='gen_filterDate_to_calendar'></div>";
                content += "<input type='text' id='gen_filterDate_to' onchange=\"gen.dateBox.dateFormat('gen_filterDate_to');gen.dateBox.dateFormat('gen_filterDate_from');gen.dateBox.checkDateFromToFormat('gen_filterDate','"+_g("日付が正しくありません。")+"');\" style=\";ime-mode:inactive;; width:80px\">";
                content += "<input type='button' class='mini_button' id='gen_filterDate_to_button' value='▼' onclick=\"gen.calendar.init('gen_filterDate_to_calendar', 'gen_filterDate_to');\">";
                content += "まで";
                content += "</div>";
                break;

            default:    // edit,copy,checkbox,label etc
                return;
        }
        var html = "";
        html += content;
        html += "<br>";
        html += "<input type='button' id='gen_filter_dialog_ok_button' value='OK' style='width:100px' onclick=\"gen.list.table.saveFilterDialog('"+colNum+"');\">";
        html += "&nbsp;&nbsp;<input type='button' value='"+_g("フィルタ解除")+"' onclick=\"gen.list.table.clearFilterDialog('"+colNum+"');\">";

        this.filterDialog  = gen.dialog.create('gen_body', 'gen_filterDialog', pos.left, pos.top, width, height, _g("フィルタ"), html, true, true);

        if (infoArr != undefined) {
            var fp =infoArr[9].split(':::');
            switch (infoArr[0]) {
                case "data":
                case "numeric":
                    $('#gen_filterSearch1').val(fp[1]);
                    $('#gen_filterSearchMatchType1').val(fp[2]);
                    $('#gen_filterBool_'+(fp[3]=='or' ? 'or' : 'and')).attr('checked','checked');
                    $('#gen_filterSearch2').val(fp[4]);
                    $('#gen_filterSearchMatchType2').val(fp[5]);
                    gen.list.table.filterDialogSelecterChange(1);
                    gen.list.table.filterDialogSelecterChange(2);
                    break;
                case "date":
                case "datetime":
                    if (fp[1] == '98' || fp[1] == '99') {
                        $('#gen_datePattern_gen_filterDate').val(fp[1]);
                        gen.list.table.filterDialogSelecterChangeForDate();
                    } else {
                        $('#gen_datePattern_gen_filterDate').val("-1");
                        $('#gen_filterDate_from').val(fp[1]);
                        $('#gen_filterDate_to').val(fp[2]);
                    }
                    break;
            }
        }

        gen.shortcut.add("Enter", function() {$('#gen_filter_dialog_ok_button').click()});
        gen.shortcut.add("ESC", function() {gen.list.table.closeFilterDialog();});
        this.filterDialog.hideEvent.subscribe(function(){
            gen.list.table.closeFilterDialog();
            return true;
        });
        $('#gen_filterSearch1').focus();
    },
    filterDialogSelecterChange: function(no) {
        var sel = $('#gen_filterSearchMatchType' + no);
        var txt = $('#gen_filterSearch' + no);
        if (sel.val() == '98' || sel.val() == '99') {
            gen.ui.disabled(txt);
            txt.css('background-color','#cccccc').val('');
        } else {
            gen.ui.enabled(txt);
            txt.css('background-color','#ffffff');
        }
    },
    filterDialogSelecterChangeForDate: function() {
        var sel = $('#gen_datePattern_gen_filterDate');
        var txt1 = $('#gen_filterDate_from');
        var txt1b = $('#gen_filterDate_from_button');
        var txt2 = $('#gen_filterDate_to');
        var txt2b = $('#gen_filterDate_to_button');
        if (sel.val() == '98' || sel.val() == '99') {
            gen.ui.disabled(txt1);
            gen.ui.disabled(txt1b);
            gen.ui.disabled(txt2);
            gen.ui.disabled(txt2b);
            txt1.css('background-color','#cccccc').val('');
            txt2.css('background-color','#cccccc').val('');
        } else {
            gen.ui.enabled(txt1);
            gen.ui.enabled(txt1b);
            gen.ui.enabled(txt2);
            gen.ui.enabled(txt2b);
            txt1.css('background-color','#ffffff');
            txt2.css('background-color','#ffffff');
        }
    },
    clearFilterDialog: function(colNum) {
        switch ($('#gen_filterType').val()) {
            case "data":
            case "numeric":
                $('#gen_filterSearch1').val('');
                $('#gen_filterSearchMatchType1').val('0');
                $('#gen_filterBool_and').attr('checked','checked');
                $('#gen_filterSearch2').val('');
                $('#gen_filterSearchMatchType2').val('0');
                break;
            case "date":
            case "datetime":
                $('#gen_datePattern_gen_filterDate').val("-1");
                $('#gen_filterDate_from').val('');
                $('#gen_filterDate_to').val('');
                break;
        }

        gen.list.table.saveFilterDialog(colNum);
    },
    closeFilterDialog: function() {
        // ここではdestroyしない。×ボタンでhideイベントが発生したときに、ショートカット削除のためにここが呼ばれるため
        this.filterDialog.hide();
        gen.shortcut.remove("Enter");
        gen.shortcut.remove("ESC");
    },
    saveFilterDialog: function(colNum) {
        var o = {};
        var type = $('#gen_filterType').val();
        switch (type) {
            case "data":
            case "numeric":
                o = {
                    action_name : gen.list.actionWithColumnMode,
                    col_num : colNum,
                    filter_type : type,
                    search1 : $('#gen_filterSearch1').val(),
                    match1 : $('#gen_filterSearchMatchType1').val(),
                    bool : $('input[name="gen_filterBool"]:checked').val(),
                    search2 : $('#gen_filterSearch2').val(),
                    match2 : $('#gen_filterSearchMatchType2').val()
                };
                break;
            case "date":
            case "datetime":
                var selVal = $('#gen_datePattern_gen_filterDate').val();
                o = {
                    action_name : gen.list.actionWithColumnMode,
                    col_num : colNum,
                    filter_type : 'date',
                    date_from : (selVal == '98' || selVal == '99') ? selVal : $('#gen_filterDate_from').val(),
                    date_to : $('#gen_filterDate_to').val()
                };
                break;
        }
        o['is_cross'] = ($('#gen_crossTableShow').length > 0);
        gen.ajax.connect('Config_Setting_AjaxListColInfo', o,
            function(j){
                gen.list.postForm();
            });
        this.filterDialog.destroy();
        gen.shortcut.remove("Enter");
        gen.shortcut.remove("ESC");
    },
    filterReset: function() {
        if (!window.confirm(_g("フィルタをすべて解除してもよろしいですか？"))) {
            return;
        }
        var  o = {
            action_name : gen.list.actionWithColumnMode,
            is_cross : ($('#gen_crossTableShow').length > 0),
        };
        gen.ajax.connect('Config_Setting_AjaxListFilterReset', o,
            function(j){
                gen.list.postForm();
            });
    },

    // 列・表示条件の追加ダイアログ
    showColumnAddDialog: function(obj, isSearch) {
        // 毎回ダイアログを再作成する。列用と表示条件用を兼ねているため
        if (this.columnAddDialog != null) {
            this.columnAddDialog.destroy();
        }

        // デフォルト。ダイアログ生成時にウィンドウサイズにより自動調整される
        var btn = $('#gen_add' + (isSearch ? 'Search' : '') + 'ColumnButton');
        var pos = btn.offset();
        pos.top += (isSearch ? -400: 20);
        pos.left -= (isSearch ? -0: 120);
        var width = 200;
        var height = 420;

        var html = "";
        html += "<div style='width:100%; height:310px; overflow-y:scroll'>";
        html += "<table width='100%' cellpadding='0' cellspacing='0'>";
        $.each(obj, function(key, val) {
            checked = true;
            if (val.substr(0,1)=='@') {    // 非表示列
                checked = false;
                val = val.substr(1);
            }
            html += "<tr id='gen_columntr_"+key+"'"+(checked ? "" : " bgcolor='#cccccc'")+"><td><input type='checkbox' id='gen_columnadd_"+key+"' value='true'"+(checked ? " checked" : "")+"></td><td width='5px'></td><td id='gen_columntext_"+key+"' align='left'>"+val+"</td></tr>";
        });
        html += "</table></div>";
        html += "<input type='checkbox' id='gen_columnAddDialogAlterCheck' value='true' onchange='gen.list.table.alterCheckColumnAddDialog();'>"+ _g("全チェック/全解除") +"<br>";
        html += _g("絞込み") +" <input type='textbox' id='gen_columnAddDialogSearchText' style='width:50%' onkeyup=\"gen.list.table.searchColumnAddDialog()\">";
        html += "<input type='button' style='width:40%' value='"+ _g("登録") +"' onclick=\"gen.list.table.saveColumnAddDialog("+isSearch+");\">";
        if (isSearch) html += "<input type='button' style='width:40%' value='"+ _g("リセット") +"' onclick=\"gen.list.table.searchColumnReset();\">";
        html += "</div>";

        this.columnAddDialog = gen.dialog.create('gen_body', 'gen_columnAddDialog', pos.left, pos.top, width, height, _g("表示する項目を選択"), html, true, true);
    },
    searchColumnAddDialog: function() {
        var s = $('#gen_columnAddDialogSearchText').val();
        $('[id^=gen_columntext_]').each(function(){
            var colId = this.id.replace('text', 'tr');
            $('#' + colId).css('display', this.innerHTML.indexOf(s) > -1 ? '' : 'none');
        });
    },
    saveColumnAddDialog: function(isSearch) {
        var o = {action_name : isSearch ? gen.list.actionWithPageMode : gen.list.actionWithColumnMode, isSearch : isSearch};
        var f = false;
        $('[id^=gen_columnadd_]').each(function(){
            o[this.id] = (this.checked ? 1 : 0);if (this.checked) f = true;
        });
        if (!f) {
            alert(_g("すべてのチェックボックスがオフになっています。1つ以上のチェックボックスをオンにしてください。"));
            return;
        }
        gen.ajax.connect('Config_Setting_AjaxColHide', o,
            function(j){
                if (isSearch) {
                    var frm = new gen.postSubmit(document.getElementById('form1'));
                    frm.submit('index.php?action='+gen.list.listAction);
                } else {
                    gen.list.postForm();
                }
            });
        this.columnAddDialog.destroy(); // POSTの時点でポインタが失われるので、オブジェクト残存防止のためここでdestroyしておく必要がある
    },
    alterCheckColumnAddDialog: function() {
        var checked = ($('#gen_columnAddDialogAlterCheck').is(':checked'));
        $('[id^=gen_columnadd_]').val(checked ? [true] : [false]);
    },

    // リスト設定変更ダイアログ
    showListSettingDialog: function(listUrl, aggregateType, isClickableTable, isDirectEditableTable, isReadOnly, customColumnClassGroup) {
        if (this.listSettingDialog == null) {
            // デフォルト。ダイアログ生成時にウィンドウサイズにより自動調整される
            var btn = $('#gen_listSettingButton');
            var pos = btn.offset();
            pos.top += 20;
            pos.left -= 300;
            var width = 300;
            var height = 300;

            var html = "";
            html += "<div style='width:100%; height:300px; overflow-y:auto'>";
            html += "<div style='height:10px;'></div>";
            html += "<table width='100%' cellpadding='0' cellspacing='0' style='text-align:center'>";

            var changeNumberTitle = _g("表示件数変更");
            var changeNumberMsg = _g("一画面に表示する件数を入力してください（1から500まで）。※件数が多くなると表示が遅くなるため、100以下にすることをお勧めします。");
            var changeNumberError = _g("入力が正しくありません。");
            html += "<tr><td><input type='button' style='width:220px' value='"+changeNumberTitle+"' onClick=\"gen.list.table.changeNumberOfItems('"+listUrl+"',1,500,'"+changeNumberMsg+"','"+changeNumberError+"')\"></td></tr>";

            var allFitTitle = _g("列幅の自動調整");
            var allFitMsg = _g("この画面のすべての列幅を自動調整します。よろしいですか？");
            html += "<tr><td><input type='button' style='width:220px' value='"+allFitTitle+"' onClick=\"gen.list.table.autoAllFit('"+allFitMsg+"')\"></td></tr>";

            var columnResetTitle = _g("列のリセット");
            html += "<tr><td><input type='button' style='width:220px' value='"+columnResetTitle+"' onClick=\"gen.list.table.columnReset()\"></td></tr>";

            var sortResetTitle = _g("並べ替えのリセット");
            var sortResetMsg = _g("この画面の行の並べ替え（ソート）を初期状態に戻します。よろしいですか？");
            html += "<tr><td><input type='button' style='width:220px' value='"+sortResetTitle+"' onClick=\"gen.list.table.sortReset('"+sortResetMsg+"')\"></td></tr>";

            if (customColumnClassGroup != '') {
                html += "<tr><td><input type='button' style='width:220px' value='"+_g("フィールド・クリエイターの設定")+"' onClick=\"javascript:window.open('index.php?action=Config_CustomColumn_Edit&classGroup="+customColumnClassGroup+"')\"></td></tr>";
            }

            html += "<tr><td height='25px'></td></tr>";
            var aggregateTypeTitle = _g("集計行の表示");
            var aggregateTypeLabels = {
                sum: _g("合計")
                ,max: _g("最大")
                ,min: _g("最小")
                ,avg: _g("平均")
                ,count: _g("データの数")
                ,distinct: _g("データの数(重複を除く)")
                ,nothing: _g("なし")
            };
            html += "<tr><td>" + aggregateTypeTitle;
            html += "<select id='gen_aggregateType' onchange=\"javascript:listUpdate({gen_aggregateType:$('#gen_aggregateType').val()},false)\">";
            // ここのカテゴリはGen_Pagerで処理している
            html += "<option value='sum'" + (aggregateType == 'sum' ? ' selected' : '') + ">"+aggregateTypeLabels.sum+"</option>";
            html += "<option value='max'" + (aggregateType == 'max' ? ' selected' : '') + ">"+aggregateTypeLabels.max+"</option>";
            html += "<option value='min'" + (aggregateType == 'min' ? ' selected' : '') + ">"+aggregateTypeLabels.min+"</option>";
            html += "<option value='avg'" + (aggregateType == 'avg' ? ' selected' : '') + ">"+aggregateTypeLabels.avg+"</option>";
            html += "<option value='count'" + (aggregateType == 'count' ? ' selected' : '') + ">"+aggregateTypeLabels.count+"</option>";
            html += "<option value='distinct'" + (aggregateType == 'distinct' ? ' selected' : '') + ">"+aggregateTypeLabels.distinct+"</option>";
            html += "<option value='nothing'" + (aggregateType == 'nothing' ? ' selected' : '') + ">"+aggregateTypeLabels.nothing+"</option>";
            html += "</select>";
            html += "</td></tr>";

            html += "<tr><td height='25px'></td></tr>";

            if (isDirectEditableTable == 'true') {
                var directEditTitle = _g("セルの内容を直接編集");
                html += "<tr><td><input type='checkbox' id='gen_directEdit' value='true' onchange='gen.list.table.directEditChange()'"+(gen_isDE ? ' checked' : '')+(isReadOnly=='true' ? ' disabled' : '')+">";
                html += "<span style='color:#"+(isReadOnly=='true' ? 'cccccc' : '000000')+"'>"+directEditTitle+"</span></td></tr>";
            }
            if (isClickableTable == 'true') {
                var listClickEnableTitle = _g("リスト行のクリックで明細画面を開く");
                html += "<tr><td><input type='checkbox' id='gen_listClickEnable' value='true' onchange='gen.list.table.listClickEnableCache()'"+(gen_isLCE ? ' checked' : '')+(gen_isDE ? ' disabled' : '')+">";
                html += "<span id='gen_listClickEnableLabel' style='"+(gen_isDE ? 'color:#ccc' : '')+"'>" + listClickEnableTitle + "</span></td></tr>";
            }

            html += "</table></div>";
            html += "</div></div>";

            this.listSettingDialog  = gen.dialog.create('gen_body', 'gen_listSettingDialog', pos.left, pos.top, width, height, _g("設定変更"), html, true, true);
        }
        this.listSettingDialog.show();
    }
};

// データテーブルオブジェクト(for List)
//    dataTableを継承
gen.listDataTable = function(dataCount, fixColCount, colCount, fixWidth, titleRowHeight) {
    // dataTableを継承
    jQuery.extend(this, new gen.dataTable(dataCount, fixColCount, colCount, fixWidth, titleRowHeight));

    // プロパティ
    this.resizeTableElm =  document.getElementById('gen_resizeTable');
};

gen.listDataTable.prototype = {

    init: function(isPageLoad) {
        // 表示条件とListの幅と高さ（ブラウザサイズにあわせる）
        this.setListSize();
        // Listの幅（ブラウザ幅にあわせる）
        //var rw = this.setListWidth();
        if (this.fixColCount>0) this.f0.style.width = this.fixWidth + 'px';

        // fixとscrの行の高さを揃える（wrapOn列があるとき用）
        // lazyLoad する場合は、追加処理の際にもこの処理が実行される（gen_data_list参照）
        if (gen.list.existWrapOn) {
            this.adjustRowHeight(0);
        }

        // スクロール位置の復元（横）
        var slf0 = $('#gen_scrollLeftF0').val();
        if (gen.util.isNumeric(slf0) && this.f1 != null) {
            this.f1.scrollLeft = slf0;    // ちなみにf0部はonDivScrollで処理されるので復元不要
        }
        var sld0 = $('#gen_scrollLeftD0').val();
        if (gen.util.isNumeric(sld0) && this.d1 != null) {
            this.d1.scrollLeft = sld0;    // ちなみにd0部はonDivScrollで処理されるので復元不要
        }
        // スクロール位置の復元（縦）
        // 縦位置を復元すべきかどうかは微妙だが、表示条件を変えての再表示の場合はレコード件数が変わることが多いし、
        // ページングの場合は先頭に戻ったほうがいいと思われる。それでコメントアウトとした
        //var st = $('#gen_scrollTop').val();
        //if (gen.util.isNumeric(st)) {
        //    this.d1.scrollTop = st;    // ちなみにf1部はonDivScrollで処理されるので復元不要
        //}

        // ここでgen_page_divを表示（ここまでは隠しておく。表示ががたつくのを避けるため。13iまではresizeTableを隠していた）
        $('#gen_page_div').css('visibility', 'visible');
        //table.style.visibility = 'visible';

        var listObj = this;
        var f = function() {
            // リストのタイトル部の Drag&Dropエレメントを作成
            listObj.makeTableTitleElm();
        }

        // ブラウザウィンドウリサイズイベント
        var thisElm = this;
        window.onresize =
            function () {
                thisElm.setListSize();    // ブラウザサイズにあわせて表示条件・Listの幅と高さを調整
            };

        // 以前は、以下の処理をここ（onDomContentLoadedのタイミング）でそのまま実行していた。しかしリストのサイズが
        // 大きい時は意外に時間がかかり、そのぶんリストのレンダリングが遅れるため、レンダリング終了後（onLoad）に実行
        // するようにした。
        if (isPageLoad) {
            $(f);
        } else {
            f();
        }
    },

    // Listリサイズ時に必要な処理（内部tableのサイズ調整） - これを実行しないとサイズが変化しない
    resizeDataTable: function() {
        var ts = this.resizeTableElm.style;
        var dataH = parseInt(ts.height) - this.titleRowHeight;
        var innerW = parseInt(ts.width) - 5;
        var scrollW = innerW;
        var d1ScrollBarW = this.d1.offsetWidth - this.d1.clientWidth;   // borderがないことが前提

        if (this.fixColCount > 0) {
            if (this.f1 != null) this.f1.style.height = dataH + 'px';    // データなしのとき用
            scrollW -= this.fixWidth;
            scrollW = (scrollW >= 0 ? scrollW : 0);
        }
        this.d0.style.width = (scrollW >= d1ScrollBarW ? scrollW - d1ScrollBarW : 0) + 'px';

        var d1s = this.d1.style;
        if (this.dataCount > 0) {
            d1s.width = (scrollW >= 0 ? scrollW : 0) + 'px';
        } else {
            d1s.width = (innerW >= 30 ? innerW - 30 : 0) + 'px';
        }
        if (!gen_iPad) {
            d1s.height = dataH + 'px';
        }
    },

    // スクロール位置の保存
    saveScroll: function() {
        if (this.d1 != null) $('#gen_scrollTop').val(this.d1.scrollTop);
        if (this.f0 != null) $('#gen_scrollLeftF0').val(this.f0.scrollLeft);
        if (this.d0 != null) $('#gen_scrollLeftD0').val(this.d0.scrollLeft);
    },

    // ショートカット List用
    shortcutInit: function() {
        gen.shortcut.add("F3", function() {
            var elm = document.getElementById('gen_newRecordButton');
            if (elm == 'undefined' || elm == null) return;
            elm.click();
        });
        gen.shortcut.add("F1", function() {
            var elm = document.getElementById('gen_searchButton');
            if (elm == 'undefined' || elm == null) return;
            elm.click();
        });
        if (gen.util.isIE) {
            window.onhelp = function() {
                return false;
            }
        }
        gen.shortcut.add("F4", function() {
            var elm = document.getElementById('gen_excelExportButton');
            if (elm == 'undefined' || elm == null) return;
            elm.click();
        });
        gen.shortcut.add("F6", function() {
            var elm = document.getElementById('gen_search_area_link');
            if (elm == 'undefined' || elm == null) return;
            elm.click();
        });
        gen.shortcut.add("F7", function() {
            var elm = document.getElementById('gen_inlineNewRecordButton');
            if (elm == 'undefined' || elm == null) return;
            elm.click();
        });
    },

    // 表示条件とListのサイズをブラウザウィンドウにあわせて調整する処理。
    setListSize: function() {
        var sArea = document.getElementById('gen_search_area');
        var sAreaStyle = sArea.style;
        var sAreaPos = $(sArea).offset();

        // リストの高さ
        var listMinH = 200; // 最低高さ
        if (gen_iPad) {
            this.resizeTableElm.style.height = '100%';
        } else {
            var browserH = gen.window.getBrowserHeight();
            var rh = browserH - parseInt($(this.resizeTableElm).offset().top) - 30; // 25
            this.resizeTableElm.style.height = (rh >= listMinH ? rh : listMinH) + 'px';
        }

        // リストの幅
        var rw = gen.window.getBrowserWidth() - 20;
        if (sAreaStyle.display != 'none') rw -= sArea.offsetWidth;
        if (!gen_iPad && rh < listMinH) {
            rw -= 10;   // 画面縦幅が狭くリスト最低高分のスペースがない場合、縦スクロールバーが出るのでそのぶん横幅減らす
        }
        this.resizeTableElm.style.width = (rw >= 0 ? rw : 0) + 'px';

        // 表示条件エリアの高さ
        sAreaStyle.height = (gen.window.getBrowserHeight() - sAreaPos.top) + 'px';

        // これを実行しないとリストのリサイズが行われない。
        this.resizeDataTable()

        return rw;
    },

    // スライダー1フレームごとのコールバック。gen.slider.initの最後の引数で設定。
    //  検索ウィンドウやメニューバーのスライドに合わせて、リストの高さを調整。
    //  delta: 元の高さからの変化量(px)、isFinish: 最終コールバックかどうか
    slideBeforeHeight: 0,
    slideBeforeBottom: 0,
    slideOver: 0,

    onSlideFrame: function(delta, isFinish, isHorizontal) {

        if (gen.util.isWebkit) {
            // Chromeではなぜかこの処理がないとうまくいかない
            $('#gen_search_area_all').css('width',($('#gen_search_area').css('width')));
        }
        if (isFinish) {
            gen.list.table.setListSize();
        }
        return;
    }
};

// データテーブルオブジェクト(for Detail)
gen.detailDataTable = function(dataCount, fixColCount, colCount, fixWidth, titleRowHeight) {
    // dataTableを継承
    jQuery.extend(this, new gen.dataTable(dataCount, fixColCount, colCount, fixWidth, titleRowHeight));
};
gen.detailDataTable.prototype = {
    // ショートカット detail用
    shortcutInit: function() {
        gen.shortcut.add("Ctrl+Down", function() {
            var elm = document.activeElement;
            if (elm == 'undefined') return;
            if (elm.id.length >= 6) {
              if (elm.id.substr(elm.id.length - 5) == '_show') {
                var baseId = elm.id.substr(0, elm.id.length - 5);
                document.getElementById(baseId + '_dropdown').click();
              } else if (elm.id.substr(elm.id.length - 9) == '_dropdown') {
                elm.click();
              }
            }
        });
        gen.shortcut.add("Ctrl+E", function() {
            document.getElementById('submit1').click();
        });
        gen.shortcut.add("Esc", function() {
            document.getElementById('gen_cancelButton').click();
        });
    }
};


// List Cell Click
gen.listcell = {
    focusCellId: "",
    editCellId: "",
    orgText: "",
    parentHtml: null,
    windowClickCancel: false,

    focus: function(cellId, isClick) {
        if (isClick) {
            gen.listcell.windowClickCancel = true;  // セルがクリックされた場合、このfuncの後にwindow.onclick（この下に記述）が発生する。それを無視するためのフラグ
        }
        if (this.editCellId != "" && this.editCellId == cellId) return; // 編集中のセル内でのクリックは無視

        var focusElm = $('#gen_focus_border_element');
        var d1Elm = $('#D1');
        var d1ElmDOM = d1Elm.get(0);
        var d1Pos = d1Elm.offset();
        if (this.focusCellId == "") {
            gen.listcell.addShortcut();
            $('body').on('click.listcell' ,function() {   // リスト以外をクリックしたらフォーカスロストさせる
                if (gen.listcell.windowClickCancel) {
                    gen.listcell.windowClickCancel = false;
                    return;
                }
                $('body').off('click.listcell');
                d1Elm.off('scroll.listcell');
                $('#gen_focus_border_element').remove();
                gen.listcell.focusCellId = "";
                gen.listcell.removeShortcut();
            });
            d1Elm.on('scroll.listcell', function() {
                focusElm = $('#gen_focus_border_element');  // 再取得が必要
                var tdPos = $('#' + gen.listcell.focusCellId).parent().offset();
                focusElm.css('top', tdPos.top - 1);
                focusElm.css('left', tdPos.left);
                var isOver = false;
                var focusTop = parseInt(focusElm.css("top"));
                if (focusTop < d1Pos.top) {
                    isOver = true;
                } else {
                    var focusBottom = focusTop + focusElm.height();
                    var d1Bottom = d1Pos.top + d1Elm.height() - 20;  // 20はスクロールバー
                    if (focusBottom > d1Bottom) {
                        isOver = true;
                    }
                }
                if (!isOver) {
                    var focusLeft = parseInt(focusElm.css("left"));
                    if (focusLeft < d1Pos.left && colNo >= 1000) {
                        isOver = true;
                    } else {
                        var focusRight = focusLeft + focusElm.width();
                        var d1Right = d1Pos.left + d1Elm.width() - 20;   // 20はスクロールバー
                        if (focusRight > d1Right) {
                            isOver = true;
                        }
                    }
                }
                focusElm.css('visibility', isOver ? 'hidden' : '');
            });
        } else {
            if (this.focusCellId == cellId) {   // focus cellをさらにクリックしたらEdit
                gen.listcell.edit(cellId);
                return;
            }
            focusElm.remove();
        }
        this.focusCellId = cellId;
        gen.listcell.showFocusBorder(cellId);
        focusElm = $('#gen_focus_border_element');

        // オートスクロール
        if (focusElm.length > 0) {  // Edit中などは処理しない
            var focusTop = parseInt(focusElm.css("top"));
            var focusLeft = parseInt(focusElm.css("left"));
            var d1ScrTop = d1ElmDOM.scrollTop;
            if (focusTop < d1Pos.top) {
                d1ElmDOM.scrollTop = d1ScrTop + focusTop - d1Pos.top;
                focusElm.css("top", d1Pos.top);
            } else {
                var focusBottom = focusTop + focusElm.height();
                var d1Bottom = d1Pos.top + d1Elm.height() - 20;  // 20はスクロールバー
                if (focusBottom > d1Bottom) {
                    d1ElmDOM.scrollTop = d1ScrTop + focusBottom - d1Bottom;
                    focusElm.css("top", d1Bottom - parseInt(focusElm.css("height")));
                } else {
                    var d1ScrLeft = d1ElmDOM.scrollLeft;
                    var colNo = cellId.split('_')[1];
                    if (focusLeft < d1Pos.left && colNo >= 1000) {
                        d1ElmDOM.scrollLeft = d1ScrLeft + focusLeft - d1Pos.left;
                        focusElm.css("left", d1Pos.left);
                    } else {
                        var focusRight = focusLeft + focusElm.width();
                        var d1Right = d1Pos.left + d1Elm.width() - 20;   // 20はスクロールバー
                        if (focusRight > d1Right) {
                            d1ElmDOM.scrollLeft = d1ScrLeft + focusRight - d1Right;
                            focusElm.css("left", d1Right - parseInt(focusElm.css("width")));
                        }
                    }
                }
            }
        }
    },

    showFocusBorder: function(cellId) {
        var elm = $('#' + cellId);
        var tdElm = elm.parent();
        var tdPos = tdElm.offset();
        $("#gen_body").append(
            $(document.createElement("div"))
            .attr("id", "gen_focus_border_element")
            .css({"position":"absolute",  "width":parseInt(tdElm.css("width"))-4+"px", "height":parseInt(tdElm.css("height"))-1+"px", "top": tdPos.top-1, "left": tdPos.left, "border-style":"solid", "border-width":"2px", "border-color":"black"})
            .click(function(){
                gen.listcell.focus(cellId, true);
            })
        );
    },

    edit: function(cellId) {
        if (this.editCellId != "")
            return false;    // テキストボックス等配置中に発生するonclickはキャンセル

        var colNum = cellId.split('_')[1];
        var editType = gen.list.table.getLocalColumnInfo(colNum, 'col_editType');
        if (editType != 'text' && editType != 'select' && editType != 'dropdown') {
            alert(_g("この項目は直接編集できません。"));
            return false;   // none, 未指定
        }

        this.editCellId = cellId;
        var focusCell = $('#' + cellId);
        this.orgText = focusCell.html();
        var focusCellParent = focusCell.parent();
        this.parentHtml = focusCellParent.html();   // td

        $('#gen_focus_border_element').remove();
        gen.listcell.removeShortcut();

        var editOptions, optArr, w;
        switch(editType) {
            case 'text':
                w = parseInt(focusCell.parent().css('width')) * 0.93;
                cellHtml = "<input type='text' id='gen_focusInputBox' value='" + this.orgText + "' style='width:" + w + "px; height:13px' onblur=\"gen.listcell.blur(false)\">";
                focusCellParent.html(cellHtml);
                break;
            case 'select':
                editOptions = gen.list.table.getLocalColumnInfo(colNum, 'col_editOptions');
                optArr = editOptions.split('||');
                w = parseInt(focusCell.parent().css('width')) * 0.95;
                // セレクタの場合、onblurではなくonchangeで登録したほうが使用感がよい
                cellHtml = "<select id='gen_focusInputBox' style='width:" + w + "px; height:100%' onchange=\"gen.listcell.blur(false)\" onblur=\"gen.listcell.blur(true)\">";
                orgText = this.orgText;
                $.each(optArr, function(num, str) {
                   var optArr2 = str.split('__');
                   var optNum = optArr2[0];
                   var optLabel = optArr2[1];
                   cellHtml += "<option value=\"" + optNum + "\"";
                   if (optLabel == orgText) cellHtml += " selected";
                   cellHtml += ">" + optLabel + "</option>";
                });
                cellHtml += "</select>";
                focusCellParent.html(cellHtml);
                // セレクタ選択時における矢印キーの挙動はブラウザによって異なるので、自前で実装する
                var f_prev = function(){ var e=$('#gen_focusInputBox'); var s=e.get(0).selectedIndex; if (s>0) e.prop('selectedIndex', s-1); };
                var f_next = function(){ var e=$('#gen_focusInputBox'); var s=e.get(0).selectedIndex; e.prop('selectedIndex', s+1); };
                gen.shortcut.add('Left', f_prev);
                gen.shortcut.add('Right', f_next);
                gen.shortcut.add('Up', f_prev);
                gen.shortcut.add('Down', f_next);
                break;
            case 'dropdown':
                editOptions = gen.list.table.getLocalColumnInfo(colNum, 'col_editOptions');
                optArr = editOptions.split('__');
                var dropdownCategory = optArr[0];
                var dropdownParam = optArr[1];
                var dropdownShowCondition = optArr[2];
                var dropdownShowConditionAlert = optArr[3];

                // 本来はダイレクト入力もできるといいが、表示時の表示名⇒コード変換や、入力後の処理などが煩雑なので、とりあえずreadonly（ドロップダウンからの選択のみ）。
                // なお、subtextを配置しているのはドロップダウン選択後に表示名を受け取るため
                w = parseInt(focusCell.parent().css('width')) * 0.93;
                cellHtml = "<input type='text' id='gen_focusInputBox_show' value='" + this.orgText+ "' style='width:" + w + "px; height:13px' onchange='gen_focusInputBox_show_onTextChange()' readonly>";
                cellHtml += "<input type='text' id='gen_focusInputBox_sub' style='display:none'>";
                cellHtml += "<input type='hidden' id='gen_focusInputBox' value='" + this.orgText + "'>";

                cellHtml += "<script>";
                cellHtml += "function gen_focusInputBox_show_onTextChange() {gen.dropdown.onTextChange('"+dropdownCategory+"','gen_focusInputBox_show','gen_focusInputBox','gen_focusInputBox_sub','gen_focusInputBox_show_onchange()','',true)}";
                cellHtml += "function gen_focusInputBox_show_onchange() {gen.listcell.blur(false)}";    // gen.dropdown.close() からも呼ばれる
                cellHtml += "function gen_focusInputBox_show_onclose() {gen.listcell.blur(true)}";    // gen.dropdown.close() からも呼ばれる
                cellHtml += "</script>";
                focusCellParent.html(cellHtml);
                gen.dropdown.show('gen_focusInputBox_show', dropdownCategory, dropdownParam, dropdownShowCondition, dropdownShowConditionAlert, true);
                break;
        }
        gen.listcell.addEditShortcut();
        $('#gen_focusInputBox').focus();
    },

    blur: function(isEsc) {
        if (this.editCellId == "") return;

        var showText = this.orgText;
        if (!isEsc) {
            var arr = this.editCellId.split('_');
            var rowNum = arr[0];
            var colNum = arr[1];
            var editType = gen.list.table.getLocalColumnInfo(colNum, 'col_editType');
            val = $('#gen_focusInputBox').val();
            switch (editType) {
                case 'text':
                    showText = val;
                    break;
                case 'select':
                    showText = $('#gen_focusInputBox :selected').text();
                    break;
                case 'dropdown':
                    showText = $('#gen_focusInputBox_sub').val();
                    break;
        }
        }
        cellId = this.editCellId;
        this.editCellId = "";   // ここでeditCellIdを消しておかないと、次のhtml書き換えでもう一度blurが実行されてしまう
        $('#gen_focusInputBox').parent().html(this.parentHtml);
        $('#' + cellId).html(showText);
        if (!isEsc && val != this.orgText) {
            $('#'+cellId).css('background-color','#ffffcc');

            orgText = this.orgText;
            var action = $('#gen_list_edit_action').val();
            var id = $('#gen_row_id_'+rowNum).val();
            var entryField = gen.list.table.getLocalColumnInfo(colNum, 'col_entryField');
            var reqId = $('#gen_reqid').val();
            var o = {editaction: action, id: id, field: entryField, val: val, reqid: reqId, showtext: showText, orgtext: orgText};

            gen.ajax.connect('Config_Setting_AjaxListEntry', o,
                function(j){
                    $('#'+cellId).css('background-color','#ffff00');    // 更新箇所着色をやめるには 'transparent'
                    $('#gen_reqid').val(j.reqid);
                    if (j.status=='success') {
                        //alert('success!');
                    } else {
                        alert(j.status);
                        $('#'+cellId).html(orgText);
                    }
                });
        }
        gen.listcell.removeEditShortcut();

        gen.listcell.removeShortcut();  // いったん削除してあらためて登録
        gen.listcell.addShortcut();
    },

    addShortcut: function() {
        gen.shortcut.add("Up", function() {gen.listcell.move(0);});
        gen.shortcut.add("Down", function() {gen.listcell.move(1);});
        gen.shortcut.add("Left", function() {gen.listcell.move(2);});
        gen.shortcut.add("Right", function() {gen.listcell.move(3);});
        gen.shortcut.add("F2", function() {gen.listcell.edit(gen.listcell.focusCellId);});
    },

    removeShortcut: function() {
        gen.shortcut.remove("Up");
        gen.shortcut.remove("Down");
        gen.shortcut.remove("Left");
        gen.shortcut.remove("Right");
        gen.shortcut.remove("F2");
    },

    addEditShortcut: function() {
        gen.shortcut.add("Esc", function() {gen.listcell.blur(true);gen.listcell.showFocusBorder(gen.listcell.focusCellId)});
        // Enterで下へフォーカスを移動
        gen.shortcut.add("Enter", function() {gen.listcell.blur(false);gen.listcell.move(1);gen.listcell.edit(gen.listcell.focusCellId)});
    },

    removeEditShortcut: function() {
        gen.shortcut.remove("Esc");
        gen.shortcut.remove("Enter");
    },

    move: function(dir) {
        if (this.focusCellId == "" || this.editCellId != "") return;
        var arr = this.focusCellId.split("_");
        var y = parseInt(arr[0],10);
        var x = parseInt(arr[1],10);
        var moveOK = false;
        switch(dir) {
        case 0:
            while (y > 0) {     // sameCellJoin の対応
                y--;
                if (document.getElementById(y + "_" + x) != null) {
                    moveOK = true;
                    break;
                }
            }
            break;
        case 1:
            while (y < 500) {     // sameCellJoin の対応
                y++;
                if (document.getElementById(y + "_" + x) != null) {
                    moveOK = true;
                    break;
                }
            }
            if (y == 500) y = 499;
            break;
        case 2:
            while (x > 0) {     // 番号がスキップしている列や、fix <- scroll の対応
                x--;
                if (document.getElementById(y + "_" + x) != null) {
                    moveOK = true;
                    break;
                }
            }
            break;
        case 3:
            while (x < 1100) {  // 番号がスキップしている列や、fix -> scroll の対応
                x++;
                if (document.getElementById(y + "_" + x) != null) {
                    moveOK = true;
                    break;
                }
            }
            if (x == 1100) x = 1099;
            break;
        }
        if (moveOK) {
            this.focus(y + "_" + x, false);
        }
    }
};

// **************************************
//  For Edit
// **************************************

gen.edit = {
    // onLoad
    init: function(actionWithKey, nextPageReport, modMsg, focusId, isEditModal, isReadOnly) {
        // 「登録して印刷」。新規モード用。ちなみに修正モード用の処理は gen_modal.js にある。
        if (nextPageReport!='') {
            gen.edit.outputReport(nextPageReport);
        }

        // ウィンドウ調整
        if (isEditModal) {
            document.getElementById('gen_contents').style.height = (parent.window.document.getElementById('gen_editFrame').offsetHeight -92) + 'px';
        }

        // D&D
        gen.edit.makeTitleElm(actionWithKey);

        // チップヘルプ
        gen.ui.initChipHelp();

        // メッセージ
        if (modMsg!='') parent.gen.modal.showModMsg(modMsg);

        // ショートカットキー
        gen.edit.shortcutInit(isReadOnly);
        window.document.onkeydown = gen.window.onkeydown;    // Enterキーでフォーカス移動

        // フォーカス
        if (focusId!='') document.getElementById(focusId).focus();
    },

    // 帳票の出力（帳票出力actionの呼び出し）。
    //   以前は単純にlocation.hrefで印刷していたが、帳票発行が完了する前に画面遷移を行うと動作が異常になる
    //   問題があるため、不可視フレームを作成してそこで処理するようにした。
    outputReport: function(reportAction) {
        var iframe = document.getElementById('gen_printFrame');
        if (iframe==undefined) {
            iframe = document.createElement('iframe');
            iframe.id = 'gen_printFrame';
            iframe.style.display = "none";
            document.body.appendChild(iframe);
        }
        iframe.src = 'index.php?action='+reportAction;
    },

    // ショートカット Edit用
    shortcutInit: function(isReadOnly) {
        if (isReadOnly!='true')
            gen.shortcut.add("F3", function() {
                // focus()は、現在のエレメントからフォーカスを逃してonchangeイベントを発生させるために必要。
                // ボタンクリックならフォーカスが移動するのでonchangeが発生するが、キー入力では発生しない。
                $('#submit1').focus().trigger('click');
            });
        gen.shortcut.add("Esc", function() {
            $('#gen_cancelButton').trigger('click');
        });
    },

    // クライアントバリデーション
    showError: function(isNotError, name, msg) {
        if ($('#gen_hide_div_'+name).length>0 && !isNotError) {
            alert(_g("非表示の項目でエラーが発生しました")+'：　'+msg);
            return;
        }
        $('#'+name+'_error').html(isNotError ? '' : msg).css('height', isNotError ? '0px' : 'auto');
    },

    // 項目タイトルのD&D用のオブジェクトを生成する
    makeTitleElm: function(action) {
        //列のid名はfunction.gen_edit_controlのタイトル生成部にあわせること
        $('[id^=gen_editLabel_]').each(function(i, val){
            gen.edit.makeDdElm(action, this.id);
        });
    },

    // 上記のsub
    makeDdElm: function(action, id, num) {
         var dd = new YAHOO.util.DDProxy(id);
         new YAHOO.util.DDTarget(id);
         var beforeColor;
         var num = id.replace('gen_editLabel_','');
         dd.startDrag = function() {
         };
         dd.onDragEnter = function(e, targetId) {
             var elm = $('#'+targetId);
             beforeColor = elm.css('background-color');
             elm.css('background-color','#ccffff');
         };
         dd.onDragOut = function(e, targetId) {
             $('#'+targetId).css('background-color',beforeColor);
         };
         dd.onDragDrop = function(e, targetId) {
            targetNum = parseInt(targetId.replace('gen_editLabel_',''),10);
            if (num == targetNum) return; // 自分自身

            gen.waitDialog.show('しばらくお待ちください...');
            var f = document.forms[0];
            // gen_editReloadは、明細リストの行を入れ替えたり、品目コードを変更してからPOSTしたときに表示がおかしくなるのを避けるため
            f.action = 'index.php?action=' + action + '&gen_editReload&gen_dd_num=' + num + '&gen_ddtarget_num=' + targetNum;
            f.submit();
         };
         dd.endDrag = function(e) {
         };
     },

    // 項目の並び順リセット
    columnReset: function(action, msg) {
        if (confirm(msg)) {
            var f = document.forms[0];
            // gen_editReloadは、明細リストの行を入れ替えたり、品目コードを変更してからPOSTしたときに表示がおかしくなるのを避けるため
            f.action = 'index.php?action=' + action + '&gen_editReload&gen_columnReset';
            f.submit();
        }
    },

    // submitボタンを無効に
    submitDisabled: function() {
        gen.ui.disabled($('#submit1'));
        var sp = $('#submitPrint1');
        if (sp.length != 0) {
            gen.ui.disabled(sp);
        }
    },

    // submitボタンを有効に（readonlyのとき以外）
    submitEnabled: function(gen_readonly) {
        if (gen_readonly) return;

        gen.ui.enabled($('#submit1'));
        var sp = $('#submitPrint1');
        if (sp.length != 0) {
            gen.ui.enabled(sp);
        }
    },

    // ********* EditList関連 *********

    // EditListの明細行数変更
    changeNumberOfList: function(action, listId, number) {
        gen.waitDialog.show(_g("しばらくお待ちください..."));

        // Post先を現在表示しているページ（Edit）に書き換え、パラメータを付加してSubmit
        var f = document.forms[0];
        // gen_editReloadは、明細リストの行を入れ替えたり、品目コードを変更してからPOSTしたときに表示がおかしくなるのを避けるため
        f.action = 'index.php?action=' + action + '&gen_editListId=' + listId + '&gen_editReload&gen_editListNumber=' + number;
        f.submit();
    },

    // EditListの行選択時の背景色変更
    editListSelect: function(editListId) {
        radioId = 'gen_editlist_select_'+editListId;
        $('[name='+radioId+']').each(function(){
            tr = $('#gen_editlist_'+editListId+'_'+this.value+',#gen_editlist_'+editListId+'_'+this.value+'_custom');
            if (this.checked) {
                bg = '#8dc4fc';
            } else {
                col = $('#gen_orgRowColor_'+editListId+'_'+this.value).val();
                if (col=='') {
                    bg = (this.value % 2 == 0 ? "#f2f3f4": "#ffffff");    // gen_edit_control の gen_edit_control_showListLine 冒頭とあわせる
                } else {
                    bg = col;
                }
            }
            tr.css('background-color', bg);
        });
    },

    // EditListの行の削除
    editListDelete: function(editListId, controls, idColumn, deleteAction) {
        lineNo = $("input[name='gen_editlist_select_"+editListId+"']:checked").val();
        if (lineNo==undefined) {
            alert(_g("削除する行を選択してください。"));
            return;
        }

        var key = $('#'+controls[0]+'_'+lineNo);
        var isNew = key.length==0;    // controlsの先頭がキーカラム
        if (!window.confirm(_g("選択されている行を削除します。") + (isNew ? '' : _g("削除後、「登録」ボタンを押さないと実際の削除は行われません。")) + _g("削除してもよろしいですか？"))) {
            return;
        }
        if (!isNew) {
            // 修正モードの場合、削除フラグを挿入する
            $("#form1").append("<input type='hidden' name='gen_delete_flag_"+key.val()+"' value='"+key.val()+"'>");
        }

        // 削除と行移動
        lineNo = parseInt(lineNo,10);
        lineCount = $("input[name='gen_editlist_select_"+editListId+"']").length;
        for (i=lineNo; i<lineCount; i++) {
            gen.edit.editListMoveLine(editListId, i+1, i, controls, false);
        }
        gen.edit.editListClearLine(editListId, lineCount, true);
    },

    // EditListに行を挿入
    editListInsert: function(editListId, controls) {
        lineNo = $("input[name='gen_editlist_select_"+editListId+"']:checked").val();
        if (lineNo==undefined) {
            alert(_g("行を選択してください。"));
            return;
        }
        lineNo = parseInt(lineNo,10);

        lineCount = $("input[name='gen_editlist_select_"+editListId+"']").length;

        // 最終行にデータが存在するかどうかを調べる。対象はテキストボックスのみ
        bottomExist = false;
        $.each(controls, function(key, value) {
            if (value=='' || value=='line_no') return true;
            elm = document.getElementById(value+'_'+lineCount);
            if (elm!=null && elm.type=='text' && elm.value!='') {
                bottomExist = true;
                return false;    // break
            }
        });
        if (lineNo == lineCount || bottomExist) {
            alert(_g("行が足りません。行数を増やしてください。"));
            return;
        }
        for (i=lineCount-1; i>=lineNo; i--) {
            gen.edit.editListMoveLine(editListId, i, i+1, controls, false);
        }
        gen.edit.editListClearLine(editListId, lineNo);
    },

    // EditListの行の入れ替え
    editListUpDown: function(editListId, controls, isUp) {
        var radioName = 'gen_editlist_select_'+editListId
        lineNo = $("input[name='"+radioName+"']:checked").val();
        if (lineNo==undefined) {
            alert(_g("行を選択してください。"));
            return;
        }

        newNo = parseInt(lineNo,10) + (isUp ? -1 : 1);
        newOpt = $("input[name='"+radioName+"']"+"[value='"+newNo+"']");
        if (newOpt.length==0) {
            alert(_g("移動できません。"));
            return;
        }

        $("input[name='"+radioName+"']").val([newNo])
        gen.edit.editListSelect(editListId);

        gen.edit.editListMoveLine(editListId, lineNo, newNo, controls, true);
    },


    editListClearLine: function(editListId, lineNo, triggerOnChange) {
        controls = eval('gen_getListCtls_'+editListId+'()');
        $.each(controls, function(key, value) {
            if (value!='' && value != 'line_no') {
                elm = $('#'+value+'_'+lineNo);
                if (elm.length==0) return true;
                elmOrg = elm.get(0);
                if (elmOrg.type=='checkbox') {
                    elm.attr('checked',false);
                } else if (elmOrg.type=='select-one') {
                    elm.prop('selectedIndex',0);
                } else if (elmOrg.value==undefined) {
                    elm.html('');
                } else {
                    elm.val('');
                }
                if (triggerOnChange && elmOrg.onchange != undefined) elm.trigger('change');

                // 拡張DD
                checkName = '#'+value+'_'+lineNo+'_show';
                if ($(checkName).length>0) {
                    $('#'+value+'_'+lineNo+'_show').val('');
                    // 拡張DDプレースホルダ
                    checkName = '#'+value+'_'+lineNo+'_show_placeholder';
                    if ($(checkName).length>0) {
                        $('#'+value+'_'+lineNo+'_show_placeholder').css('display','');
                    }
                } else {
                    // 拡張DD以外のプレースホルダ
                    checkName = '#'+value+'_'+lineNo+'_placeholder';
                    if ($(checkName).length>0) {
                        $('#'+value+'_'+lineNo+'_placeholder').css('display','');
                    }
                }
                // クライアントバリデーションエラーメッセージ
                checkName = '#'+value+'_'+lineNo+'_error';
                if ($(checkName).length>0) {
                    $('#'+value+'_'+lineNo+'_error').html('').css('height','0px');
                }
            }
        });
    },

    editListMoveLine: function(editListId, lineNo, newNo, controls, isSwap) {
        isKey = true;    // controlsの先頭がキーカラム
        $.each(controls, function(key, value) {
            if (isKey) {
                // 新規行にはキーカラム用のhiddenがないので、作っておく必要がある
                if ($('#'+value+'_'+lineNo).length==0) {
                    $("#form1").append("<input type='hidden' id='"+value+'_'+lineNo+"' name='"+value+'_'+lineNo+"' value=''>");
                }
                if ($('#'+value+'_'+newNo).length==0) {
                    $("#form1").append("<input type='hidden' id='"+value+'_'+newNo+"' name='"+value+'_'+newNo+"' value=''>");
                }

                isKey = false;
            }
            if (value!='' && value != 'line_no') {
                oldElm = $('#'+value+'_'+lineNo);
                newElm = $('#'+value+'_'+newNo);

                gen.edit.moveJQueryObjectValue(oldElm, newElm, isSwap);

                // 拡張DDの入れ替え
                checkName = '#'+value+'_'+lineNo+'_show';
                if ($(checkName).length>0) {
                    oldElm = $('#'+value+'_'+lineNo+'_show');
                    newElm = $('#'+value+'_'+newNo+'_show');
                    gen.edit.moveJQueryObjectValue(oldElm, newElm, isSwap);
                    // 拡張DDプレースホルダの入れ替え
                    checkName = checkName + '_placeholder';
                    if ($(checkName).length>0) {
                        oldElm = $('#'+value+'_'+lineNo+'_show_placeholder');
                        newElm = $('#'+value+'_'+newNo+'_show_placeholder');
                        gen.edit.swapDisplayState(oldElm, newElm);
                    }
                } else {
                    // 拡張DD以外のプレースホルダの入れ替え
                    checkName = '#'+value+'_'+lineNo+'_placeholder';
                    if ($(checkName).length>0) {
                        oldElm = $('#'+value+'_'+lineNo+'_placeholder');
                        newElm = $('#'+value+'_'+newNo+'_placeholder');
                        gen.edit.swapDisplayState(oldElm, newElm);
                    }
                }
                // クライアントバリデーションエラーメッセージの入れ替え
                checkName = '#'+value+'_'+lineNo+'_error';
                if ($(checkName).length>0) {
                    oldElm = $('#'+value+'_'+lineNo+'_error');
                    newElm = $('#'+value+'_'+newNo+'_error');
                    gen.edit.moveJQueryObjectValue(oldElm, newElm, isSwap);
                    oldElm.css('height', oldElm.html()=='' ? '0px': 'auto');
                    newElm.css('height', newElm.html()=='' ? '0px': 'auto');
                }
            }
            // 背景色
            newColorElm = $('#gen_orgRowColor_'+editListId+'_'+newNo);
            oldColorElm = $('#gen_orgRowColor_'+editListId+'_'+lineNo);
            if (isSwap) {
                col = newColorElm.val();
                newColorElm.val(oldColorElm.val());
                oldColorElm.val(col);
            } else {
                newColorElm.val(oldColorElm.val());
            }
            gen.edit.editListSelect(editListId);
        });

        // チップヘルプの更新 (jquery.cluetip.js。 jquery.hoverIntent.jsでディレイ表示している(250ms))
        $('.gen_chiphelp').each(function(){$(this).cluetip({local: true, hoverIntent: {interval:250}});});

        // callback
        if (typeof gen_editListMoveLineCallBack == "function") {
            gen_editListMoveLineCallBack(lineNo, newNo, isSwap);
        }
    },

    moveJQueryObjectValue: function(oldElm, newElm, isSwap) {
        if (newElm.get(0).value==undefined) {
            if (isSwap) {
                val = newElm.html();
                newElm.html(oldElm.html());
                oldElm.html(val);
            } else {
                newElm.html(oldElm.html());
            }
        } else {
            if (isSwap) {
                val = newElm.val();
                newElm.val(oldElm.val());
                oldElm.val(val);
            } else {
                newElm.val(oldElm.val());
            }
        }
        if (isSwap) {
            p = newElm.css('backgroundColor');
            newElm.css('backgroundColor', oldElm.css('backgroundColor'));
            oldElm.css('backgroundColor', p);
            p = newElm.attr('readonly');
            newElm.attr('readonly', oldElm.attr('readonly'));
            oldElm.attr('readonly', p);
            p = newElm.attr('disabled');
            newElm.attr('disabled', oldElm.attr('disabled'));
            oldElm.attr('disabled', p);
            p = newElm.is(':checked');
            newElm.attr('checked', oldElm.is(':checked'));
            oldElm.attr('checked', p);
        } else {
            newElm.css('backgroundColor', oldElm.css('backgroundColor'));
            newElm.attr('readonly', oldElm.attr('readonly'));
            newElm.attr('disabled', oldElm.attr('disabled'));
            newElm.attr('checked', oldElm.is(':checked'));
        }
    },

    swapDisplayState: function(oldElm, newElm) {
        oldDsp = oldElm.css('display');
        oldElm.css('display', newElm.css('display'));
        newElm.css('display', oldDsp);
    },

    // 項目の追加ダイアログ
    showControlAddDialog: function(obj, actionWithKey, actionWithPageMode, titleStr, submitStr, cancelStr, checkStr, resetStr, resetMsgStr) {
        if (this.controlAddDialog == null) {
            // デフォルト。ダイアログ生成時にウィンドウサイズにより自動調整される
            var btn = $('#gen_addControlButton');
            var pos = btn.offset();
            pos.top += 20;
            pos.left -= 200;
            var width = 200;
            var height = 400;

            var html = "";
            html += "<div style='width:100%; height:310px; overflow-y:scroll'>";
            html += "<table width='100%' cellpadding='0' cellspacing='0'>";
            jQuery.each(obj, function(key, val) {
                checked = true;
                if (val.substr(0,1)=='@') {    // 非表示列
                    checked = false;
                    val = val.substr(1);
                }
                html += "<tr id='gen_controltr_"+key+"'"+(checked ? "" : " bgcolor='#cccccc'")+"><td><input type='checkbox' id='gen_controladd_"+key+"'"+(checked ? " checked" : "")+"></td><td width='5px'></td><td align='left'>"+val+"</td></tr>";
            });
            html += "</table></div><br>";
            html += "<div style='width:100%; text-align:center'>";
            html += "<input type='checkbox' id='gen_controlAddDialogAlterCheck' value='true' onchange='gen.edit.alterCheckControlAddDialog();'>"+checkStr+"<br>";
            html += "<input type='button' value='"+submitStr+"' onclick=\"gen.edit.saveControlAddDialog('"+actionWithKey+"','"+actionWithPageMode+"');\">";
            html += "<input type='button' value='"+resetStr+"' onclick=\"gen.edit.columnReset('"+actionWithKey+"','"+resetMsgStr+"');\">";
            html += "</div>";

            this.controlAddDialog  = gen.dialog.create('gen_body', 'gen_controlAddDialog', pos.left, pos.top, width, height, titleStr, html, true, true);
        }
        this.controlAddDialog.show();
    },
    saveControlAddDialog: function(actionWithKey, actionWithPageMode) {
        var o = {action_name : actionWithPageMode};
        $('[id^=gen_controladd_]').each(function(){o[this.id] = (this.checked ? 1 : 0);});
        gen.ajax.connect('Config_Setting_AjaxControlHide', o,
            function(j){
                var f = document.forms[0];
                // gen_editReloadは、明細リストの行を入れ替えたり、品目コードを変更してからPOSTしたときに表示がおかしくなるのを避けるため
                f.action = 'index.php?action=' + actionWithKey + '&gen_editReload';
                f.submit();
            });
    },
    alterCheckControlAddDialog: function() {
        var checked = ($('#gen_controlAddDialogAlterCheck').is(':checked'));
        $('[id^=gen_controladd_]').attr('checked', checked);
    },

    // ********* EditTable関連 *********

    // EditTableの最終行Enter押下イベント
    editTableLastLineEnter: function(elm) {
        $('#'+elm).keydown(function(e){
            if (e.keyCode == 13) $('#submit1').focus();
        });
    }
};
