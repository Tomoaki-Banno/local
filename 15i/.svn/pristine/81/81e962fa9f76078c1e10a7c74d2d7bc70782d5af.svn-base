if (!gen) var gen = {};

gen.dropdown = {
    parentElmName: null,
    dropdownElm: null,

    //  Dropdownフレームの表示
    show: function(parentElmName, category, param, showCondition, showConditionAlert, hideNewButton) {
        if (showCondition != undefined && showCondition != "") {
            if (!eval(gen.dropdown.bracketToElmValue(showCondition.replace(/\[gen_quot\]/g, "'")))) {
            	if (showConditionAlert != undefined && showConditionAlert != "") {
            		alert(showConditionAlert.replace(/\[gen_quot\]/g, "'"));
            	}
            	return;
            }
        }

        if (this.dropdownElm != null) {
              document.body.removeChild(this.dropdownElm);
              this.dropdownElm = null;
        }
        this.parentElmName = parentElmName;

        if (param != undefined && param != "") {
            param = gen.dropdown.bracketToElmValue(param);
        }

        url = 'index.php?action=Dropdown_Dropdown&category=' + category + 
            '&param=' + encodeURIComponent(param) +
            '&source_control=' + parentElmName;
        if (hideNewButton) {
            url += '&hide_new=true';
        }

        this.dropdownElm = document.createElement('iframe');
        this.dropdownElm.name = "gen_dropdown";
        this.dropdownElm.src = url;

        // 親コントロールの位置
        var o = $('#' + this.parentElmName.replace(/#/g, "\\#")).offset();
        var x = o.left;
        var y = o.top;

        // ドロップダウンの表示
        this.dropdownElm.id = "gen_dropdown";
        this.dropdownElm.width = "0px";    // この幅は、後で調整される（dropdown.tplのjsにて）
        this.dropdownElm.height = "0px";   // この高さは、後で調整される（dropdown.tplのjsにて）
        this.dropdownElm.frameBorder = 0;              // IE用
        var st = this.dropdownElm.style;
        st.position = "absolute";
        st.left = x + "px";         // この位置は、あとで調整される可能性がある（dropdown.tplのjsにて）
        st.top = (y + 20) + "px";
        st.border = "1px solid gray";
        st.visibility = "hidden";   // dropdown.tplのJSで再表示
        document.body.appendChild(this.dropdownElm);
    },

    //  Dropdownフレームを閉じる
    close: function(doOnChange) {
    	var parentElm = document.getElementById(this.parentElmName);
        // 次のエレメントへフォーカスを移動
        gen.window.nextfocus(parentElm);
        // イベントハンドラの呼出
        if (doOnChange) {
            if (this.parentElmName.substr(this.parentElmName.length-5)=='_show') {
                eval(this.parentElmName + "_onchange();");
            } else if (parentElm.onchange != undefined) {
                parentElm.onchange();
            }
        } else if (typeof(window[this.parentElmName + "_onclose"])=="function") {
            eval(this.parentElmName + "_onclose();");
        }
        // ドロップダウンのクローズ
        document.body.removeChild(this.dropdownElm);
        this.dropdownElm = null;
    },

    //  Dropdownテキストボックス更新時の処理
    //    textboxの内容（code）を元にAjaxでidとsubtextを取得し、
    //    idをhiddenに、またsubtextをsubtext用テキストボックスに埋め込む。
    //  引数：
    //        category    	カテゴリ
    //        textboxId    	codeを取得するtextboxのid
    //        hiddenId    	idを埋め込むhiddenタグのid
    //        subtextId    	subtextを表示するtextboxのid
    //        afterScript   処理後に実行するスクリプト。不要なら空文字にする
    //        param         (15i)dropdownParam
    //        isDisableNew  (15i)マスタダイレクト登録の非許可

    //  ちなみにhiddenId、subtextIdは指定したものがAjaxからオウム返しに返ってくる。
    // （オウム返しでも無意味ではない。複数のリクエストが錯綜したときの区別のために必要）
    // （ただし subtextIdについては、サブテキストがないcategoryの場合は空文字が返る）
    onTextChange: function(category, textboxId, hiddenId, subtextId, afterScript, param, isDisableNew) {
        // 次のエレメントへフォーカスを移動
    	gen.window.nextfocus(document.getElementById(hiddenId + '_show'));

    	var p = {
            hiddenId: hiddenId,
            subtextId: subtextId,
            category: category,
            code: encodeURIComponent(document.getElementById(textboxId).value),
            afterScript: afterScript
        };
        if (param != undefined && param != "") {
            p.param = gen.dropdown.bracketToElmValue(param);
        }
        if (isDisableNew) {
            p.disableNew = true;
        }
        gen.ajax.connect('Dropdown_AjaxDropdownParam', p,
            function(j) {
                if (j != '') {
                    // idをhiddenに、またsubtextをsubtext用テキストボックスに埋め込む。
                    // カテゴリによってはidが取得できない（seiban_stockなど）。また該当データが存在しない場合もある。
                    // その場合、idとして-1が返ってくる。afterScriptの実行のみ行う
                    // idConvert=true(id変換可能)の場合はhiddenにidを埋め込む
                    if (j.id != -1 || j.idConvert) {
                        document.getElementById(j.hiddenId).value = j.id;
                        if (j.subtextId != '') 
                            document.getElementById(j.subtextId).value = j.subtext;
                    }

                    // afterScriptの実行
                    if (j.afterScript != '') {
                        eval(j.afterScript.replace(/&quot;/g, "\"").replace(/&#039;/g, "'").replace(/&amp;/g, "&"));
                    }
                    
                    $('#gen_searchButton').click();
                }
            }, false, true);
    },

    // 文字列内の[...]をエレメントの値に置き換える
    bracketToElmValue: function(str) {
        var match = str.match(/\[[^\]]*\]/g);    // [...]にマッチ。gは複数マッチを示すオプション
        if (match == null) return str;

        for(i=0;i<match.length;i++) {
            var matchStr = match[i].toString().replace('[', '').replace(']', '');
            sourceStr = document.getElementById(matchStr).value;
            str = str.replace(match[i], sourceStr);
        }
        return str;
    }
};
