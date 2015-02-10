if (!gen) var gen = {};

gen.stickynote = {
    // x位置は画面左端を0とする
    createNote: function(action, x_pos, y_pos) {
        gen.ajax.connect('Config_Setting_AjaxStickynote', {op:'add', action_name:action, x_pos: gen.stickynote.leftXtoCenterX(x_pos), y_pos: y_pos}, 
            function(j) {
                gen.stickynote.showNote(j.note_id, j.x_pos, j.y_pos, j.width, j.height, j.show_all_user=='true', j.allow_edit_all_user=='true', j.show_all_action=='true', false, j.user_id, j.user_id, j.user_name, '', j.color);
            });
    },

    getTitle: function(showAllUser, authorName) {
        return showAllUser ? _g("全員 [作成者:%1]").replace('%1',authorName) : _g("%1 のみ").replace('%1',authorName);
    },
            
    // X座標系の変換（画面左端0 ⇒ 画面センター0）            
    leftXtoCenterX: function(leftX) {
        return parseInt(leftX - (gen.window.getBrowserWidth() / 2));
    },

    // X座標系の変換（画面センター0 ⇒ 画面左端0）            
    centerXtoLeftX: function(centerX) {
        return parseInt(centerX + (gen.window.getBrowserWidth() / 2));
    },

    // x位置は画面センターを0とする
    showNote: function(noteId, x, y, width, height, showAllUser, allowEditAllUser, showAllAction, isSystemNote, userId, authorId, authorName, content, color) {
        var noteElmId = 'gen_stickynote_'+noteId;

        // パネルの作成
        $('#gen_body').prepend("<div class='yui-skin-sam'><div id='" + gen.util.escape(noteElmId) +"' style='border-style:none; background-color:" + gen.util.escape(color) +"; line-height:18px'></div></div>");
        var p1 = new YAHOO.widget.Panel(
            noteElmId,
            {
            draggable: true,
            autofillheight: "body",
            x: gen.stickynote.centerXtoLeftX(x),
            y: y,
            width: width + 'px',
            height: height + 'px',
            zIndex: 8000    // チャットのzIndex(9000-)より小さくする
            }
        );
        p1.setHeader(gen.stickynote.getTitle(showAllUser, authorName));
        p1.setBody(gen.stickynote.addLinkTag(content));
        p1.render();

        // 移動イベント
        if (gen_iPad) {
            $('#'+noteElmId+'_c').draggable({
                stop: function( event, ui ) {gen.stickynote.onEndMove(noteId, noteElmId);}               
            });  // use touch-punch
        } else {
            p1.dragEvent.subscribe(function(type, args, me){
                if (args[0]=='endDrag') gen.stickynote.onEndMove(noteId, noteElmId);
            });
        }

        // 編集権限
        var allowEdit = (allowEditAllUser || userId == authorId);

        // ボディクリックイベント（編集モード）
        //  システムふせんの場合は編集不可（ただし削除はできる）
        if (allowEdit && !isSystemNote) {
            $('#'+noteElmId+' .bd').on('click', function() {gen.stickynote.onBodyClick(noteId, noteElmId)});
        }

        // 閉じるボタンのclickハンドラをオーバーライド。
        // 標準の動作では、イベントをキャンセルすることができないので。
        var closeButton = $('#'+noteElmId+' .container-close');
        YAHOO.util.Event.removeListener(closeButton.get(0), "click");
        closeButton.on('click', function(){gen.stickynote.onClose(noteId, noteElmId)});

        // 編集権限がない場合、閉じるボタンを消す
        if (!allowEdit) {
            closeButton.css('display','none');
        }

        // スタイル
        // YUI Panelのスタイルを上書きする
        $('#'+noteElmId+' .hd')
            .css('background','none')
            .css('background-color',color)
            .css('border','none')
            .css('border-style','none')
            .css('height','26px')
            .css('overflow','hidden');
        $('#'+noteElmId+' .bd')
            .css('background','none')
            .css('background-color',color)
            .css('border','none')
            .css('border-style','none')
            .css('line-height','17px')
            .css('overflow','hidden');

        // リサイズ
        var resize = new YAHOO.util.Resize(noteElmId, {
            proxy: false,
            handles: ['br'],
            minWidth: 150,
            minHeight: 70
        });
        resize.on('endResize', function() {gen.stickynote.onEndResize(noteId, noteElmId)});
        resize.on("resize", function(args) {
                this.cfg.setProperty("width", args.width + "px");
                // パネル内のbody部の高さを調整。これをしておかないと、高さを広げたときに下のほうがonclickに反応しない
                this.cfg.setProperty("height", args.height + "px");
            }, p1, true);

        // 設定値をhiddenで持っておく
        $('#'+noteElmId).append(
            "<input type='hidden' id='" + gen.util.escape(noteElmId) + "_hidden_authorName' value='" + gen.util.escape(authorName) + "'>"+
            "<input type='hidden' id='" + gen.util.escape(noteElmId) + "_hidden_showAllUser' value='" + gen.util.escape(showAllUser) + "'>"+
            "<input type='hidden' id='" + gen.util.escape(noteElmId) + "_hidden_allowEditAllUser' value='" + gen.util.escape(allowEditAllUser) + "'>"+
            "<input type='hidden' id='" + gen.util.escape(noteElmId) + "_hidden_showAllAction' value='" + gen.util.escape(showAllAction) + "'>"+
            "<input type='hidden' id='" + gen.util.escape(noteElmId) + "_hidden_color' value='" + gen.util.escape(color) + "'>"+
            // 編集可能かどうか。サーバー側でもチェックする
            "<input type='hidden' id='" + gen.util.escape(noteElmId) + "_hidden_allowEdit' value='" + gen.util.escape(allowEdit) + "'>"
        );
    },

    onClose: function(noteId, noteElmId) {
        if (!window.confirm(_g("メモパッドを削除してもよろしいですか？"))) return;

        gen.ajax.connect('Config_Setting_AjaxStickynote', {op:'del', noteId: noteId}, 
            function(j) {
                $('#' + noteElmId).remove();
                $('#gen_stickynote_' + noteId + '_c').remove();
            });
    },

    onEndMove: function(noteId, noteElmId) {
        var note = $('#'+noteElmId);
        var offset = note.offset();
        // X位置は画面センターを0とする
        gen.ajax.connect('Config_Setting_AjaxStickynote', {op:'mov', noteId: noteId, x_pos: gen.stickynote.leftXtoCenterX(offset.left), y_pos: offset.top}, 
            function(j) {
            });
    },

    onEndResize: function(noteId, noteElmId) {
        var note = $('#'+noteElmId);
        var offset = note.offset();
        // X位置は画面センターを0とする
        gen.ajax.connect('Config_Setting_AjaxStickynote', {op:'resize', noteId: noteId, width: note.width(), height: note.height(), x_pos: gen.stickynote.leftXtoCenterX(offset.left), y_pos: offset.top}, 
            function(j) {
            });
    },

    onBodyClick: function(noteId, noteElmId) {
        var note = $('#'+noteElmId);
        var textElmId = noteElmId + '_text';
        var width = note.width();
        var height = note.height();
        var body = $('#'+noteElmId+' .bd');

        // サイズが小さいときは拡大する（編集終了時にもとにもどす）
        if (width<200) gen.stickynote.setNoteWidth(noteElmId, 200);
        if (height<200) gen.stickynote.setNoteHeight(noteElmId, 200);

        // ヘッダ
        $('#'+noteElmId+' .hd')
            .html('')
            .prepend(
                "<input type='button' value='"+_g("登録")+"' onclick=\"gen.stickynote.onRegistButton('"+noteId+"','"+noteElmId+"','"+textElmId+"')\">"+
                "<input type='button' value='"+_g("ｷｬﾝｾﾙ")+"' onclick=\"gen.stickynote.onCancelButton('"+noteId+"','"+noteElmId+"','"+textElmId+"')\">"+
                "<span style='10px'>&nbsp;</span>"+
                "<span id='"+noteElmId+"_editModeLink'>"+
                ""+_g("編集")+" | "+
                "<a href=\"javascript:gen.stickynote.onConfigModeLink('"+noteId+"','"+noteElmId+"','"+textElmId+"')\" style='color:blue'>"+_g("設定")+"</a>"+
                "</span>"+
                "<span id='"+noteElmId+"_configModeLink' style='display:none'>"+
                "<a href=\"javascript:gen.stickynote.onEditModeLink('"+noteId+"','"+noteElmId+"','"+textElmId+"')\" style='color:blue'>"+_g("編集")+"</a>"+
                " | "+_g("設定")+""+
                "</span>"
                );

        // 閉じるボタンを消す
        $('#'+noteElmId+' .container-close').css('display','none');

        // ボディ
        var orgContent = body.html();
        var editContent = gen.stickynote.deleteLinkTag(orgContent.replace(/<br>/gi, "\r\n"));    // <br>は改行に置換、自動リンクは外す
        var showAllUser = ($('#'+noteElmId+'_hidden_showAllUser').val()=='true');
        var allowEditAllUser = ($('#'+noteElmId+'_hidden_allowEditAllUser').val()=='true');
        var showAllAction = ($('#'+noteElmId+'_hidden_showAllAction').val()=='true');
        var colors = {
            yellow:'#FFFF00',
            lavender:'#E6E6FA',
            lightcyan:'#E0FFFF',
            ivory:'#FFFFF0',
            beige:'#F5F5DC',
            lightyellow:'#FFFFE0',
            mistyrose:'#FFE4E1',
            lightgray:'#D3D3D3',
            skyblue:'#87CEEB',
            aquamarine:'#7FFFD4',
            lightgreen:'#90EE90',
            khaki:'#F0E68C',
            pink:'#FFC0CB',
            lightpink:'#FFB6C1',
            silver:'#C0C0C0',
            deepskyblue:'#1E90FF',
            cyan:'#00FFFF',
            lime:'#00FF00',
            greenyellow:'#ADFF2F',
            gold:'#FFD700',
            orange:'#FFA500',
            lightsalmon:'#FFA07A',
            tomato:'#FF6347',
            red:'#FF0000',
            violet:'#EE82EE',
            magenta:'#FF00FF',
            mediumpurple:'#9370DB',
            gray:'#808080',
            darkseagreen:'#8FBC8F',
            green:'#008000',
            chocolate:'#D2691E',
            purple:'#800080'
        };
        var colorOpts = '';
        var color = $('#'+noteElmId+'_hidden_color').val();
        $.each(colors, function(key, val) {
            colorOpts+="<option value='"+val+"' style='background-color:"+val+"'"+(color==val ? " selected" : "")+">"+key+"</option>";
        });

        body
            .off('click')
            .html(
                "<textarea id='"+textElmId+"' style='border:none;background-color:"+color+"'"+
                    // テキストエリア内でのEnterを有効にするための処理
                    "onFocus='window.document.onkeydown=null' "+
                    "onBlur='window.document.onkeydown=gen.window.onkeydown' "+
                    ">"+editContent+"</textarea>"+
                // キャンセル時のために元のコンテンツをとっておく
                "<div id='"+textElmId+"_beforecontent' style='display:none'>"+orgContent+"</div>"+
                "<input type='hidden' id='"+noteElmId+"_width' value='"+width+"'>"+
                "<input type='hidden' id='"+noteElmId+"_height' value='"+height+"'>"+
                "<input type='hidden' id='"+noteElmId+"_color_org' value='"+color+"'>"
                )
            .prepend(        // 設定画面。最初は非表示
                "<span id='"+ noteElmId+"_configArea' style='display:none;'>"+
                ""+_g("表示ユーザー：")+"<select id='"+noteElmId+"_showAllUser'><option value='true'"+(showAllUser ? ' selected':'')+">"+_g("全員")+"</option><option value='false'"+(showAllUser ? '':' selected')+">"+_g("自分のみ")+"</option></select><br>"+
                ""+_g("編集ユーザー：")+"<select id='"+noteElmId+"_allowEditAllUser'><option value='true'"+(allowEditAllUser ? ' selected':'')+">"+_g("全員")+"</option><option value='false'"+(allowEditAllUser ? '':' selected')+">"+_g("自分のみ")+"</option></select><br>"+
                ""+_g("表示画面：")+"<select id='"+noteElmId+"_showAllAction'><option value='true'"+(showAllAction ? ' selected':'')+">"+_g("全画面")+"</option><option value='false'"+(showAllAction ? '':' selected')+">"+_g("作成画面のみ")+"</option></select><br>"+
                ""+_g("背景色：")+"<select id='"+noteElmId+"_color' onchange=\"gen.stickynote.onColorChange('"+noteElmId+"')\">"+colorOpts+"</select>"+
                "</span>");

        // テキストエリアの設定
        var bodyWidth = body.width();
        var bodyHeight = body.height();
        $('#'+textElmId)
            .css({
                'width': bodyWidth-4,
                'height': bodyHeight-4
            })
            .focus();
    },

    onColorChange: function(noteElmId) {
        var color = $('#'+noteElmId+'_color').val();
        $('#'+noteElmId+' .hd')
            .css('background-color',color);
        $('#'+noteElmId+' .bd')
            .css('background-color',color);
        $('#'+noteElmId+'_text')
            .css('background-color',color);
    },

    onRegistButton: function(noteId, noteElmId, textElmId) {
        var textElm = $('#'+textElmId);
        var showAllUser = $('#'+noteElmId+'_showAllUser').val();
        var allowEditAllUser = $('#'+noteElmId+'_allowEditAllUser').val();
        var showAllAction = $('#'+noteElmId+'_showAllAction').val();
        var color = $('#'+noteElmId+'_color').val();
        $('#'+noteElmId+'_hidden_showAllUser').val(showAllUser);
        $('#'+noteElmId+'_hidden_allowEditAllUser').val(allowEditAllUser);
        $('#'+noteElmId+'_hidden_showAllAction').val(showAllAction);
        $('#'+noteElmId+'_hidden_color').val(color);
        // 改行は<br>に置換。URLエンコードして渡す
        var content = textElm.val();
        var postContent = encodeURIComponent(content.replace(/\r\n/g, '<br>').replace(/(\n|\r)/g, '<br>'));
        gen.ajax.connect('Config_Setting_AjaxStickynote', {op:'reg', noteId: noteId, show_all_user: showAllUser, allow_edit_all_user: allowEditAllUser
                , show_all_action: showAllAction, content: postContent, color:color}, 
            function(j) {
                gen.stickynote.exitEdit(j.content, noteId, noteElmId, textElmId);
            });
    },

    onCancelButton: function(noteId, noteElmId, textElmId) {
        if (!window.confirm(_g("編集した内容を破棄してもよろしいですか？"))) {
            $('#'+textElmId).focus();
            return;
        }
        $('#'+noteElmId+'_color').val($('#'+noteElmId+'_color_org').val());
        gen.stickynote.onColorChange(noteElmId);
        var content = $('#'+textElmId+'_beforecontent').html();
        gen.stickynote.exitEdit(content, noteId, noteElmId, textElmId);
    },

    onConfigModeLink: function(noteId, noteElmId, textElmId) {
        $('#'+textElmId).css('display','none');
        $('#'+noteElmId+'_configArea').css('display','');
        $('#'+noteElmId+'_configModeLink').css('display','');
        $('#'+noteElmId+'_editModeLink').css('display','none');
    },

    onEditModeLink: function(noteId, noteElmId, textElmId) {
        $('#'+textElmId)
            .css('display','')
            .focus();
        $('#'+noteElmId+'_configArea').css('display','none');
        $('#'+noteElmId+'_configModeLink').css('display','none');
        $('#'+noteElmId+'_editModeLink').css('display','');
    },

    exitEdit: function(content, noteId, noteElmId, textElmId) {
        var showAllUser = $('#'+noteElmId+'_hidden_showAllUser').val();
        var authorName = $('#'+noteElmId+'_hidden_authorName').val();
        var allowEdit = $('#'+noteElmId+'_hidden_allowEdit').val();

        // サイズの復元
        var orgWidth = $('#'+noteElmId+'_width').val();
        gen.stickynote.setNoteWidth(noteElmId, orgWidth);
        var orgHeight = $('#'+noteElmId+'_height').val();
        gen.stickynote.setNoteHeight(noteElmId, orgHeight);

        // ヘッダとボディの復元
        $('#'+noteElmId+' .hd')
            .html(gen.stickynote.getTitle(showAllUser=='true', authorName));
        $('#'+noteElmId+' .bd')     // この処理で、テキストエリアや元データ保存div等が消される
            .html(gen.stickynote.addLinkTag(content));          // サーバー側で危険なHTMLタグと属性が取り除かれている

        // 編集権限がある場合、閉じるボタンとクリックイベントの復元
        if (allowEdit=='true') {
            $('#'+noteElmId+' .bd').on('click', function() {gen.stickynote.onBodyClick(noteId, noteElmId)});
            $('#'+noteElmId+' .container-close').css('display','');
        }
    },
    
    // 自動リンク
    addLinkTag: function(content) {
        return content.replace(/(http[s]?\:\/\/[\w\+\$\;\?\.\%\,\!\#\~\*\/\:\@\&\\\=\_\-]+)/g, "<a href='$1' style='word-break:break-all;display:inline-block;' target='_blank' onclick='event.stopPropagation()'>$1</a>");
    },
    deleteLinkTag: function(content) {
        return content.replace(/<a href=.*?>(.*?)<\/a>/g, "$1");
    },

    // プログラムからリサイズするときはこれを使用する
    setNoteWidth: function(noteElmId, width) {
        $('#'+noteElmId).width(width+'px');
    },
    setNoteHeight: function(noteElmId, height) {
        // heightについては、全体だけでなくbody部もリサイズする必要がある
        var note = $('#'+noteElmId);
        var noteHeight = note.height();
        var delta = height - noteHeight;
        var body = $('#'+noteElmId+' .bd');
        body.height(body.height() + delta);
        note.height(height+'px');
    }
};
