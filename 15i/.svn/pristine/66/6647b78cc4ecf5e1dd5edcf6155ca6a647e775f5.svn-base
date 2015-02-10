if (!gen) var gen = {};

gen.modal = {
    rowId: null,
    editDialog: null,

    open: function(src){
        if (this.editDialog != null) {
            this.editDialog.destroy();
            this.editDialog = null;
        }
        // デフォルト。ダイアログ生成時にウィンドウサイズにより自動調整される
        var width = 1000;
        var height = 800;
        var left = (gen.window.getBrowserWidth() / 2) - (width / 2);
        var top = (gen.window.getBrowserHeight() / 2) - (height / 2);

        // gen_dialogModeは、permissionerror画面でヘッダ・フッタを表示させないようにするためのフラグ
        var html = "<iframe id='gen_editFrame' name='gen_editFrame' src='" + src + "&gen_dialogMode=true' style='width:100%; height:100%; border:0'/>";

        this.editDialog = gen.dialog.create('gen_body', 'gen_modal_frame', left, top, width, height, _g("データ編集"), html, true, true);
        this.editDialog.show();
    },

    close: function(doReload, nextPageReport){
        // 登録後のリスト更新が行われた場合、画面上にダイアログは残るが、ハンドル（this.editDialog）は失われている。
        if (this.editDialog == undefined) {
            $('#gen_editDialog_c').remove();
        } else {
            this.editDialog.destroy();
            this.editDialog = null;
        }
        
        // 「登録して印刷」。修正モード用。ちなみに新規モード用の処理は gen_script.js の gen.edit.init() にある。
        //      以前は単純にlocation.hrefで印刷していたが、帳票発行が完了する前に画面遷移を行うと動作が異常になる
        //      問題があるため、不可視フレームを作成してそこで処理するようにした。
        //location.href = location.protocol + '//' + location.host + location.pathname + '?action=' + nextPageReport;
        if (nextPageReport != null && nextPageReport != '') {
            var iframe = document.getElementById('gen_printFrame');
            if (iframe==undefined) {
                iframe = document.createElement('iframe');
                iframe.id = 'gen_printFrame';
                iframe.style.display = "none";
                document.body.appendChild(iframe);
            }
            iframe.src = location.protocol + '//' + location.host + location.pathname + '?action=' + nextPageReport;
        }
        
        if (doReload) {
            // Listを再表示する
            var elm = parent.document.getElementById('gen_searchButton');
            if (elm != 'undefined' && elm != null) {
                elm.click();
                return;
            }
            elm = parent.document.getElementById('gen_homeScheduleBegin');
            if (elm != 'undefined' && elm != null) {
                parent.scheduleUpdate(elm.value);
                return;
            }
            elm = parent.document.getElementById('gen_dashboardFlag');
            if (elm != 'undefined' && elm != null) {
                location.href = "index.php?action=Menu_Home2";
                return;
            }
        } else {
            // Listを再表示しない
            
            // Firefoxでのスクロール乱れ対策
            f1 = document.getElementById('F1');
            if (f1 != null) {	
                f1.scrollTop = document.getElementById('D1').scrollTop;
            }
            var errorElm = document.getElementById('gen_error');
            if (errorElm !== null && errorElm.length > 0) {
                errorElm.innerHTML = '';
            }
        }
    },

    // 親Listページの更新処理（更新メッセージ表示と更新行の色づけ）
    showModMsg: function(modMsg) {
        // メッセージ
        var elm = document.getElementById('gen_modMsg');
        if (elm != undefined) {
            elm.innerHTML = "<font color='red'>" + modMsg + "</font>";
            gen.list.table.setListSize();
        }

        if (this.rowId == null) return;

        // 更新行の色つけ
        var fixRow = document.getElementById('gen_tr_fixtable_' + this.rowId);
        var scrRow = document.getElementById('gen_tr_scrtable_' + this.rowId);
        var hColor = "#ffff99";
        if (fixRow != null) {
            fixRow.style.background = hColor;
            if (fixRow.onmouseout != "") {
                if (scrRow == null) {
                    fixRow.onmouseout = function(){fixRow.style.background=hColor;};
                } else {
                    fixRow.onmouseout = function(){fixRow.style.background=hColor;scrRow.style.background=hColor;};
                }
            }
        }
        if (scrRow != null) {
            scrRow.style.background = hColor;
            if (scrRow.onmouseout != "") {
                if (fixRow == null) {
                    scrRow.onmouseout = function(){scrRow.style.background=hColor;};
                } else {
                    scrRow.onmouseout = function(){fixRow.style.background=hColor;scrRow.style.background=hColor;};
                }
            }
        }
    }
};
