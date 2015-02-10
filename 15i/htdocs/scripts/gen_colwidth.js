if (!gen) var gen = {};

gen.colwidth = {
    leftColumnIndex: -1,
    leftColumnName: "",
    leftCellOrgWidth: -1,
    fixOrgWidth: -1,
    d0OrgWidth: -1,
    d1OrgWidth: -1,
    mouseX: 0,
    mouseStartDragX: -1,   // ドラッグ開始X位置。ドラッグ中フラグも兼ねている（-1以外ならドラッグ中）
    minimumCellWidth: 10,  // セルの最小値。固定
    actionWithColumnMode: null,
    divMargin: 0,

    // イニシャライズ
    init: function(actionWithColumnMode) {
        gen.colwidth.actionWithColumnMode = actionWithColumnMode;
        // bodyにイベントを追加
        $("body").mousemove(function(event){gen.colwidth.mouseX=event.clientX;});
        $('body').mouseup(gen.colwidth.onMouseUp);
    },

    // オートフィット
    autofit: function(colNum) {
        // 列のなかでの最大長を調べる
        var maxw = 0;
        var f= function() {
            // テキストの長さを調べるためテンポラリエレメントを作る
            var te = document.createElement("span");
            te.position = "absolute";
            te.style.whiteSpace="nowrap";
            te.id = "gen_cw_temp";
            document.body.appendChild(te);
            te.innerHTML = this.innerHTML;
            // インプット要素の時は要素に表示されているテキストの長さを取得
            var mag = 1;
            var tempType = $('#gen_cw_temp :first-child').attr('type');
            if (tempType!="") {
                var sourseId = $('#gen_cw_temp :first-child').attr('id');
                if (tempType == 'text') {
                    te.innerHTML = $("#" + sourseId).val();
                    mag = 1.2;
                }
            }
            // 列幅計算
            var w = parseInt($('#gen_cw_temp').get(0).offsetWidth * mag);
            document.body.removeChild(te);
            if (w > maxw) maxw = w;
        };
        $(".gen_xd" + gen.colwidth.leftColumnIndex + "_title").each(f);
        $(".gen_xd" + gen.colwidth.leftColumnIndex).each(f);
        if (maxw==0) return;

        // 列幅調整
        gen.colwidth.initWidthChange(colNum);
        gen.colwidth._updateColumnWidthCore(maxw + 10);
        gen.colwidth.endWidthChange(colNum);
    },

    // 非表示（幅を0にする）
    hide: function(colNum) {
        var hideColWidth = parseInt($("#gen_td_" + colNum + "_title").width()) + 8;
        // 該当列のtdタグ（CSSクラス名で指定）を非表示に
        $(".gen_x" + colNum).css("display", "none");
        // 固定行のときは、全体のサイズを調整
        if (colNum < 1000) {
            //var hideColWidth = parseInt($("#gen_td_" + colNum + "_title").css("width")) + 8;
            var f0 = $("#F0");
            var f0Width = parseInt(f0.css("width"));
            f0Width -= parseInt(hideColWidth);
            $("#F0").css("width",f0Width + "px");
            $("#F1").css("width",f0Width + "px");
            var d0 = $("#D0");
            var d0Width = parseInt(d0.css("width"));
            d0Width += parseInt(hideColWidth);
            $("#D0").css("width",d0Width + "px");
            $("#D1").css("width",d0Width + "px");
        }
        if (gen.list.table.getLocalColumnInfo(colNum, 'col_wrapon') == "1") {
            gen.list.table.adjustRowHeight(0);
        }
    },

    // 列幅変更開始処理（オートフィット・ドラッグ共通）
    initWidthChange: function(colNum) {
        gen.colwidth.leftColumnIndex = colNum;
        gen.colwidth.leftColumnName = "gen_td_" + colNum;
        gen.colwidth.leftCellOrgWidth = parseInt($("#" + gen.colwidth.leftColumnName + "_title").css("width"));
        var div = $("#gen_div_" + colNum + "_innerTitle");
        gen.colwidth.divMargin = parseInt(div.css("margin-left")) + parseInt(div.css("margin-right"));
        if (colNum < 1000) {
            gen.colwidth.fixOrgWidth = parseInt($("#F0").css("width")) ;
            gen.colwidth.d0OrgWidth = parseInt($("#D0").css("width"));
            gen.colwidth.d1OrgWidth = parseInt($("#D1").css("width"));
        }
    },

    // 列幅変更終了処理（オートフィット・ドラッグ共通）
    endWidthChange: function() {
        gen.colwidth._afterUpdateForIE();
        gen.colwidth._saveColwidth(gen.colwidth.leftColumnName + "_title");
        gen.colwidth.leftCellOrgWidth = -1;
        if (gen.list.table.getLocalColumnInfo(gen.colwidth.leftColumnIndex, 'col_wrapon') == "1") {
            gen.list.table.adjustRowHeight(0);
        }
    },

    // MouseDown（ドラッグ開始）
    onStartDrag: function(colNum) {
        $("body").bind("mousemove.cw", function(event){gen.colwidth._updateColumnWidth(event);});  // .cwは名前空間。削除時に削除対象となるハンドラを限定するため
        gen.colwidth.initWidthChange(colNum);
        gen.colwidth.mouseStartDragX = gen.colwidth.mouseX;
    },

    // MouseUp（ドラッグ終了）
    onMouseUp: function() {
        if (gen.colwidth.mouseStartDragX == -1) return;
        $("document").unbind("mousemove.cw");
        gen.colwidth.endWidthChange();
        gen.colwidth.mouseStartDragX = -1;
    },

    // 列幅更新
    _updateColumnWidth: function(event) {
        if (gen.colwidth.mouseStartDragX ==-1) return;

        var delta = event.clientX - gen.colwidth.mouseStartDragX;
        if ((gen.colwidth.leftCellOrgWidth + delta) < gen.colwidth.minimumCellWidth) return;

        gen.colwidth._updateColumnWidthCore(gen.colwidth.leftCellOrgWidth + delta);
    },

    _updateColumnWidthCore: function(length) {
        // 列のtdをリサイズ
        $("#" + gen.colwidth.leftColumnName + "_title").css("width", length + "px");
        var elm = $("#" + gen.colwidth.leftColumnName + "_data");
        if (elm != null) elm.css("width", (length)+"px");

        // 列の全セルの内部divをリサイズ(IE以外) 。
        //   IE以外では、セル内にdivがあるとそれ以上セルが縮まないため。
        //   本当はIEの場合もこの処理をしておけば列幅変更中の表示が改善されるが、ドラッグがかなり重くなる。
//        if (!gen.util.isIE) {
            $(".gen_xd" + gen.colwidth.leftColumnIndex + "_title").css("width",(length - gen.colwidth.divMargin - 3)+"px"); // -3は隙間列(3px)の分
            $(".gen_xd" + gen.colwidth.leftColumnIndex).css("width",(length - gen.colwidth.divMargin)+"px");
//        }
        // 固定列の列幅を変更したときに、固定部とスクロール部の比率を変える処理
        if (gen.colwidth.leftColumnIndex < 1000) {
            $("#F0").css("width",(gen.colwidth.fixOrgWidth + (length - gen.colwidth.leftCellOrgWidth)) + "px");
            $("#F1").css("width",(gen.colwidth.fixOrgWidth + (length - gen.colwidth.leftCellOrgWidth)) + "px");
            $("#D0").css("width",(gen.colwidth.d0OrgWidth - (length - gen.colwidth.leftCellOrgWidth)) + "px");
            $("#D1").css("width",(gen.colwidth.d1OrgWidth - (length - gen.colwidth.leftCellOrgWidth)) + "px");
        }
    },

    // 列幅変更後の処理（IE専用）。
    //  IEでは列幅変更中のセル内div変更処理を省いているため、ここで行う。
    _afterUpdateForIE: function() {
        if (gen.util.isIE) {
            var w = parseInt(document.getElementById(gen.colwidth.leftColumnName+"_title").style.width);
            $(".gen_xd" + gen.colwidth.leftColumnIndex + "_title").css("width",(w-7)+"px"); // 両側2pxずつ、隙間列3px
            $(".gen_xd" + gen.colwidth.leftColumnIndex).css("width",(w-4)+"px");
        }
    },

    // 列幅保存。gen_script.jsに依存
    _saveColwidth: function(id) {
        var o = {
            // 自action名を引数として渡しているが、その際に gen_listAction ではなく、gen_actionWithColumnMode という特別な
            // 値を使用している。これは ListBase で作成している。
            // gen_listAction との違いは、action名の最後にcolumnModeが付加されている（columnModeごとに別Actionとみなす）ことと、
            // action名の「&...」の部分を維持している点。
            action_name : gen.colwidth.actionWithColumnMode,
            col_num : id.replace('gen_td_','').replace('_title',''),
            col_width : parseInt(document.getElementById(id).style.width),
            is_cross : ($('#gen_crossTableShow').length > 0)
        };
        gen.ajax.connect('Config_Setting_AjaxListColInfo', o, 
            function(){
            });    // gen_script.js
    }
};
