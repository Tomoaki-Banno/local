if (!gen) var gen = {};

gen.chat = {
    // チャットページではチャットダイアログ表示禁止（エレメントID重複などの問題あり）
    mode: '',   // d: dialog, s: smartphone, t: tablet

    init: function(mode, headerId, detailId, x, y, width, height, noUpdShowStat) {
        gen.chat.mode = mode;
        switch(mode) {
            case 'd':   // dialog
                // 当初はダイアログを再利用していたが、チャットアイコンをクリックした時は常にトップ（スレッドリスト）が
                // 表示されてほしいという要望があったのに加え、ダイアログ再利用時に二重投稿が発生するという不具合があったため仕様変更
                if (this.chatDialog != null) {
                    gen.chat.removeShortcut();
                    this.chatDialog.destroy();
                }
                // デフォルト。ダイアログ生成時にウィンドウサイズにより自動調整される
                var po = $('#gen_showChat').offset();
                if (x === '') {
                    x = po.left - 250;
                }
                if (y === '') {
                    y = po.top + 20;
                }
                if (width === '') {
                    width = 460;
                }
                if (height === '') {
                    height = 550;
                }

                // パネルの作成。タイトルを仮設定しておかないとレイアウトが乱れる
                this.chatDialog  = gen.dialog.create('gen_body', 'gen_chatDialog', parseInt(x), parseInt(y), parseInt(width), parseInt(height)
                    , "dummy", "<div id='gen_chat_panel' style='text-align:left; height: 100%'></div>", true, true);

                if (headerId == undefined || headerId == "") {
                    gen.chat.showChatList();
                } else {
                    gen.chat.showChat(headerId, detailId);
                }

                // 移動イベント
                this.chatDialog.dragEvent.subscribe(function(type, args, me){
                    if (args[0]=='endDrag')
                        gen.chat.onEndMove();
                });

                // リサイズイベント
                var resize = new YAHOO.util.Resize('gen_chatDialog', {
                    proxy: false,
                    handles: ['br'],
                    minWidth: 150,
                    minHeight: 70
                });
                resize.on('endResize', function() {
                    if ($('#gen_chat_list').length > 0) {
                        $('#gen_chat_list').css("height", $('#gen_chatDialog').height() - 100);
                    } else {
                        gen.chat.adjustContentHeight();
                    }
                    gen.chat.onEndResize();
                });
                resize.on("resize", function(args) {
                    this.cfg.setProperty("width", args.width + "px");
                    // パネル内のbody部の高さを調整。これをしておかないと、高さを広げたときに下のほうがonclickに反応しない
                    this.cfg.setProperty("height", args.height + "px");
                }, this.chatDialog, true);

                // hideイベント
                this.chatDialog.hideEvent.subscribe(function(){
                    gen.chat.removeShortcut();
                    gen.ajax.connect('Config_Setting_AjaxChat', {op:'hide'},
                        function(j) {
                        });
                });
                this.chatDialog.show();
                if (noUpdShowStat === undefined || noUpdShowStat === false) {
                    gen.ajax.connect('Config_Setting_AjaxChat', {op:'show'},
                        function(j) {
                        });
                }
                gen.chat.addShortcut();
                break;

            case 's':   // smartphone
                $('#gen_mobile_page .ui-content').css('padding','0');
                $('#gen_chat_panel').css('height','');
                if (gen.util.isNumeric(headerId)) {
                    gen.chat.showChat(headerId, detailId);
                } else {
                    gen.chat.showChatList();
                }
                break;

            case 't':   // tablet
                $('#gen_chat_page_listPanelParent').css('width', '280px');
                $('#gen_chat_page_listPanel').css('height', '');
                gen.chat.showChatList();
                if (gen.util.isNumeric(headerId)) {
                    gen.chat.showChat(headerId, detailId);
                } else {
                    gen.chat.clearChat();
                }
                break;

            default:    // page
                gen.chat.mode = 'p';
                $('#gen_chat_page_listPanelParent').css('width', '370px');
                gen.chat.adjustPageHeight();
                gen.chat.addShortcut();
                gen.chat.showChatList();
                if (gen.util.isNumeric(headerId)) {
                    gen.chat.showChat(headerId, detailId);
                } else {
                    gen.chat.clearChat();
                }
                break;
        }
    },

    // ------ for page mode ------

    adjustPageHeight: function() {
        var browserH = gen.window.getBrowserHeight();
        var pElm = $('#gen_chat_page_listPanel');
        var rh = browserH - parseInt(pElm.offset().top) - 3;
        var rhm = (rh >= 0 ? rh : 0) + 'px';
        pElm.css('height', rhm);
        $('#gen_chat_page_chatPanel').css('height', rhm);
        gen.chat.adjustContentHeight();
    },

    onEndMove: function() {
        var offset = $('#gen_chatDialog').offset();
        gen.ajax.connect('Config_Setting_AjaxChat', {op:'mov', x_pos: offset.left, y_pos: offset.top},
            function(j) {
            });
    },

    onEndResize: function() {
        var elm = $('#gen_chatDialog');
        gen.ajax.connect('Config_Setting_AjaxChat', {op:'resize', width: elm.width(), height: elm.height(),},
            function(j) {
            });
    },

    // ------ common ------

    addShortcut: function() {
        // F1とF10の両方で同じ動作。
        // F1は、リストを更新したら同時にチャットも更新されるようにするため。
        // F10は、リストを更新せずチャットだけ更新したい場合もあるため。
        gen.shortcut.add("F1", function() {
            if ($('#gen_chat_postHeaderId').length > 0) {
                gen.chat.readChat($('#gen_chat_postHeaderId').val(), true);
            }
            if ($('#gen_chat_list').length > 0) {
                gen.chat.showChatList();
            }
        });
        if (gen.util.isIE) {
            window.onhelp = function() {
                return false;
            }
        }
        gen.shortcut.add("F10", function() {
            if ($('#gen_chat_postHeaderId').length > 0) {
                gen.chat.readChat($('#gen_chat_postHeaderId').val(), true);
            }
            if ($('#gen_chat_list').length > 0) {
                gen.chat.showChatList();
            }
        });
        gen.shortcut.add("Shift+Enter", function() {
            if ($('#gen_chat_postContent').length > 0) {
                gen.chat.regContent($('#gen_chat_postHeaderId').val());
            }
        });
    },

    removeShortcut: function() {
        gen.shortcut.remove("F1");
        gen.shortcut.remove("F10");
        gen.shortcut.remove("Shift+Enter");
    },

    showChatList: function(searchWord,groupId) {
        if (gen.chat.mode == 'd' || gen.chat.mode == 's')
            $('#gen_chatDialog_h').html(_g("トークボード"));
        var o = {op:'list'};
        var search = "";
        if (searchWord != undefined && searchWord != "") {
            search = searchWord;
        } else if ($('#gen_chat_search_text').length > 0) {
            search = $('#gen_chat_search_text').val();
        }
        if (search != '') {
            o['search'] = search;
        }
        if (groupId === undefined || groupId === '0') {
            o['groupId'] = $('#gen_chat_group').val();
        } else {
            o['groupId'] = groupId; 
        }
        if (gen.chat.mode == 'd' || gen.chat.mode == 's') {
            o['clearLastHeaderId'] = true;
        }
        gen.ajax.connect('Config_Setting_AjaxChat', o,
            function(j) {
                var groups = "<option value='-1'" + (j.groupId == "-1" ? " selected" : "") + ">(" + _g("未読のみ") + ")</option>"
                    + "<option value='-2'" + (j.groupId == "-2" ? " selected" : "") + ">(" + _g("スター付き") + ")</option>";
                if (j.groups !== undefined) {
                    $.each(j.groups, function(key, val){
                        groups += "<option value='" + gen.util.escape(key) + "'";
                        if (j.groupId == key) {
                            groups += " selected"
                        }
                        groups += ">" + gen.util.escape(val) + "</option>";
                    });
                }
                var html = "<div id='gen_chat_list' style='position:relative; width:100%; height:" + (gen.chat.mode == 'd' ? '93' : '95') + "%; overflow-x:hidden; overflow-y:auto'>"
                + "<div style='padding:5px'>"
                    + "<table>"
                        + "<tr><td><input type='button' value='" + (gen.chat.mode == 's' ? _g('新規') : _g('新規スレッドを作成')) + "' onclick='gen.chat.showChatConfig()'></td></tr>"
                        + "<tr><td><input type='text' id='gen_chat_search_text' style='width:150px' value='" + $("<pre/>").text(search).html() + "' title='" + _g("スペース区切りでand検索。先頭に「title:」をつけるとスレッドタイトルのみ検索。") + "'></td>"
                        + "<td><img src='img/magnifier.png' onclick='gen.chat.showChatList()'></td>"
                        + "<td width='10px'></td>"
                        + "<td><select id='gen_chat_group' style='max-width:150px' onchange='gen.chat.showChatList()'><option value=''>(" + _g("すべて") + ")</option>" + groups + "</select>"
                        + j.catPinHtml + "</td>"
                        + "</tr>"
                    + "</table>"
                    + "<div style='position: absolute; right:10px; top:10px;'>"
                        + "<table><tr><td><img src='img/arrow-circle-double-135.png' style='border:none'></td><td><a href='javascript:gen.chat.showChatList()' style='font-size:13px;color:black'>" + _g("更新") + (gen.chat.mode == 's' || gen.chat.mode == 't' ? "" : "(F10)") + "</a></td></tr></table>"
                    + "</div>"
                    + (gen.chat.mode == 'd' ? "<div style='position: absolute; right:10px; top:35px;'>"
                            + "<table><tr><td><img src='img/application-resize-full.png' style='border:none'></td><td><a href='index.php?action=Menu_Chat' style='font-size:13px;color:black'>" + _g("全画面") + "</a></td></tr></table>"
                        + "</div>" : "")
                    + "<table padding='3px' cellspacing='0px' width='100%'>";
                    if (j.data) {
                        var sep = false;
                        var first = true;
                        var firstId = null;
                        $.each(j.data, function(key, val){
                            var isSearch = (val.detail_search != undefined && val.detail_search != '');
                            if (first)
                                firstId = val.chat_header_id;
                            if (!isSearch) {
                                if (!sep && val.is_pin != "1") {
                                    if (!first) {
                                        html += "<tr><td colspan='4'><hr></td></tr>";
                                    }
                                    sep = true;
                                }
                            }
                            first = false;
                            html += "<tr style='height:7px'><td colspan='4'></td></tr>";
                            if (gen.chat.mode == 's') {
                                html += "<tr align='left'>"
                                    + "<td>"
                                    + "<a href='javascript:gen.chat.showChat(" + gen.util.escape(val.chat_header_id) + ",null," + (search != '' ? '1' : '0') + ")' style='font-size:13px;color:black'>" + gen.util.escape(val.title) + "</a>" + (val.is_readed == '0' ? "<span class='gen_number_icon'" + (val.is_ecom == 1 ? " style='background-color:green'" : (val.is_system == 1 ? " style='background-color:blue'" : "")) + ">UP</span>" : "")
                                    + "<br><span style='font-size:12px'>"
                                    + gen.util.escape(val.chat_time) + " "
                                    + gen.util.escape(val.user_name)
                                    + "<img id='gen_chat_list_pin_" + gen.util.escape(val.chat_header_id) + "' src='img/pin" + (val.is_pin == "1" ? "01" : "02") + ".png' style='vertical-align: middle; cursor:pointer' onclick='gen.chat.pinChatList(" + gen.util.escape(val.chat_header_id) + ")'>"
                                    + "</span></td></tr>";
                            } else {
                                html += "<tr align='left'>"
                                    + "<td><a href=\"javascript:" + (gen.chat.mode == 'p' && val.is_readed == '0' ? "$('#gen_chat_up_id_" + gen.util.escape(val.chat_header_id) + "').remove();" : "") + "gen.chat.showChat(" + gen.util.escape(val.chat_header_id) + ",null," + (search != '' ? '1' : '0') + ")\" style='font-size:13px;color:black'>" + gen.util.escape(val.title) + "</a>"
                                    + (val.is_readed == '0' ? "<span " + (gen.chat.mode == 'p' ? "id='gen_chat_up_id_" + gen.util.escape(val.chat_header_id) + "' " : "") + "class='gen_number_icon'" + (val.is_ecom == 1 ? " style='background-color:green'" : (val.is_system == 1 ? " style='background-color:blue'" : "")) + ">UP</span>" : "")
                                    + (val.group_name == null ? "" : "<span style='font-size:10px;padding-left:20px'>[" + gen.util.escape(val.group_name) + "]</span>")
                                    + "</td>"
                                    + "<td style='width:80px; font-size: 10px; overflow:hidden; white-space: nowrap;'>" + gen.util.escape(val.chat_time) + "<br>" + gen.util.escape(val.user_name) + "</td>"
                                    + "<td style='width:18px;'><img id='gen_chat_list_pin_" + gen.util.escape(val.chat_header_id) + "' src='img/pin" + (val.is_pin == "1" ? "01" : "02") + ".png' style='vertical-align: middle; cursor:pointer' onclick='gen.chat.pinChatList(" + gen.util.escape(val.chat_header_id) + ")'></td>"
                                    + "</tr>";
                            }
                            if (isSearch) {
                                arr = val.detail_search.split("[gen_sep]");
                                $.each(arr, function(i, cont) {
                                    if (cont != "") {
                                        var arr2 = cont.split("[gen_no]");
                                        var no = arr2[0];
                                        var cont2 = arr2[1];
                                        html += "<tr align='left'>"
                                            + "<td colspan='3' style='width:100%;word-break:break-all;padding-left:20px;background-color:#ffffcc'><a href='javascript:gen.chat.showChat(" + gen.util.escape(val.chat_header_id) + "," + gen.util.escape(no) + ",1)' style='font-size:12px;color:black'>" + gen.util.escape(cont2) + "</a></td>"
                                            + "</tr>"
                                            + "<tr><td style='height:10px'></td></tr>";
                                    }
                                });
                            } else {
                                if (!sep && val.is_pin != "1") {
                                    html += "<tr><td colspan='4'><hr></td></tr>";
                                    sep = true;
                                }
                            }
                        });
                        if (!gen.chat.mode == 'd' && !gen.chat.mode == 's' && gen.util.trim($('#gen_chat_page_chatPanel').html()) == '' && firstId !== null) {
                            gen.chat.showChat(firstId);
                        }
                    } else {
                        html += "<tr><td>";
                        if (search == "" && groups == "") {
                            html += "<br>" + _g("トークボードは、社内コミュニケーションに役立つ強力なツールです。") + "<br>"
                               + _g("メール・電話・グループウェアに代わる情報共有・ファイル共有の手段になります。") + "<br><br>"
                               + _g("自分専用のメモやファイルサーバーとして使用することもできます。") + "<br>"
                               + _g("データはクラウド上に保存され、複数のPCやiPhone/iPadで見ることができます。") + "<br><br>"
                               + _g("まずは上のボタンを押して、スレッドを作成してみてください。");

                        } else {
                            html += _g("該当するスレッドがありません。");
                        }
                        html += "</td></tr>";
                    }
                html += "</table>";
                html += "</div>";
                html += "</div>";
                if (gen.chat.mode == 'd') {
                    html += "<div style='position:absolute; bottom:5px; right:10px; font-size:10px; color:#999'>" + _g("ダイアログの右下をドラッグするとサイズを変更できます。") + "</div>";
                }
                if (gen.chat.mode != 't' && gen.chat.mode != 's') {
                    html += "<div style='position:absolute; bottom:" + (gen.chat.mode == 'd' ? '1' : '5') + "px; left:5px'" + (gen.util.isIE ? " title='" + _g("デスクトップ通知機能は Internet Explorer では使用できません。") + "'" : "") + ">"
                        + "<table cellspacing=0 cellpadding=0><tr>"
                        + "<td><input type='checkbox' id='gen_chat_desktop_notification' value='true' onchange='gen.chat.onDesktopNotificationChange()'" + (j.desktop ? 'checked' : '') + (gen.util.isIE ? " disabled" : "") + "></td>"
                        + "<td style='font-size:11px;" + (gen.util.isIE ? "color:#ccc" : "") + "'>" + _g("デスクトップ通知") + "</td>"
                        + "</tr></table></div>";
                }
                var panel = "gen_chat_page_listPanel";
                if (gen.chat.mode == 'd' || gen.chat.mode == 's') {
                    panel = "gen_chat_panel";
                }
                $('#' + panel).html(html);
                var sb = $('#gen_chat_search_text');
                if (sb.length > 0) {
                    sb.get(0).onkeydown = gen.chat.searchBoxKeyDown;
                }
                if (gen.chat.mode == 's') {
                    $('#gen_chat_header').html('').css('display','none').css('height','0px');
                    $('#gen_chat_content').css('padding-top','');
                    $('#gen_mobile_header').css('display','').css('height','');
                    $('#gen_chat_footer').html('').css('display','none');
                    $('#gen_wait_div_c').remove();
                    document.body.scrollTop = 0;
                }
            });
    },

    searchBoxKeyDown: function(e) {
        if (gen.util.isUnderIE8) {
            if (event.keyCode != 13) return true;
        } else {
            if (e.keyCode != 13) return true;
        }
        gen.chat.showChatList();
    },

    pinChatList: function(headerId, isInThread) {
        if (isInThread === undefined) {
            isInThread = false;
        }
        var pin = $('#gen_chat_' + (isInThread ? 'thread' : 'list') + '_pin_' + headerId);
        var src = pin.attr('src');
        var val = (src == "img/pin01.png" ? 0 : 1);
        gen.ajax.connect('Config_Setting_AjaxChat', {op:'listPin', headerId:headerId, val:val},
            function(j) {
                if (j.status == "success") {
                    pin.attr('src', "img/pin" + (val == 0 ? "02" : "01") + ".png");
                    if (gen.chat.mode == 'p' || !isInThread) {
                        gen.chat.showChatList();
                    }
                    if (gen.chat.mode == 'p' && !isInThread) {
                        var inPin = $('#gen_chat_thread_pin_' + headerId);
                        if (inPin.length > 0) {
                            inPin.attr('src', "img/pin" + (val == 0 ? "02" : "01") + ".png");
                        }
                    }
                }
            });
    },

    onDesktopNotificationChange: function() {
        var isChecked = $('#gen_chat_desktop_notification').is(':checked');
        gen.desktopNotification.chageState('chat', isChecked);
    },

    showChat: function(headerId, detailId, isSearch) {
        var searchWord = "";
        if (isSearch == 1) {
            searchWord = $('#gen_chat_search_text').val();
        }
        var listGroupId = 0;
        var groupElm = $('#gen_chat_group');
        if (groupElm.length > 0) {
            listGroupId = groupElm.val();
        }
        var html =
            "<div style='position: relative;" + (gen.chat.mode == 't' || gen.chat.mode == 's' ? "" : "height:100%") + "'>";
                if (gen.chat.mode != 's') {
                    html +=
                    "<div style='" + (gen.chat.mode == 't' || gen.chat.mode == 's' ? "" : "position: absolute; left:10px; top:10px") + "'>";
                        if (gen.chat.mode == 'd' || gen.chat.mode == 's') {
                            html += "<a href=\"javascript:gen.chat.showChatList('" + searchWord + "','" + listGroupId + "')\" style='font-size:13px;color:black'><< " + _g("リストに戻る") + "</a>";
                        } else {
                            html += "<span id='gen_chat_titleArea' style='font-weight:bold; font-size:14px'></span><span id='gen_chat_groupArea' style='font-size:12px'></span>";
                        }
                      html +=
                      "&nbsp;&nbsp;<a href='javascript:gen.chat.insertLink(" + headerId + ")' style='font-size:11px; color:#999'>[" + _g("スレッドリンク") + "]</a>"
                    + "</div>"
                    + "<div style='position: absolute; right:15px;top:" + (gen.chat.mode == 't' || gen.chat.mode == 's' ? "0" : "5") + "px'>"
                        + "<table><tr>"
                        + "<td id='gen_chat_thread_pin_area'></td>"
                        + (gen.chat.mode == 'd' ? "<td><img src='img/application-resize-full.png' style='border:none'></td><td><a href='index.php?action=Menu_Chat' style='font-size:13px;color:black'>" + _g("全画面") + "</a></td><td width='3px'></td>" : "")
                        + "<td><img src='img/arrow-circle-double-135.png' style='border:none'></td><td><a href='javascript:gen.chat.readChat(" + headerId + ",true)' style='font-size:13px;color:black'>" + _g("更新") + (gen.chat.mode == 's' || gen.chat.mode == 't' ? "" : "(F10)") + "</a></td>"
                        + "</tr></table>"
                    + "</div>";
                }
                html += ""
                + "<div id='gen_chat_content_parent' style='" + (gen.chat.mode == 't' || gen.chat.mode == 's' ? "" : "position: absolute; left:0px; top:30px;") + "width:100%; height:0px; overflow-x:hidden; overflow-y:auto; background-color:#FAFCF0'>"
                    + "<div id='gen_chat_content' style='padding: 5px'>"
                    + "</div>"
                + "</div>";

                if (gen.chat.mode != 's') {
                    html += "<div id='gen_chat_postContent_parent' style='" + (gen.chat.mode == 't' ? "" : "position: absolute; left:0px; bottom:3px") + ";width:100%; min-height:" + (gen.chat.mode == 'd'  ? "125" : "175") + "px; text-align:center;'>"
                        + "<table><tr>"
                        + "<td><span class='gen_number_icon gen_stamp_tab gen_stamp_tabt' style='background:#475966;font-size:12px;cursor:pointer' onclick='gen.chat.hideStampBox()'>text</span></td>"
                        + "<td><span class='gen_number_icon gen_stamp_tab gen_stamp_tabr' style='background:#BCBCBC;font-size:12px;cursor:pointer' onclick='gen.chat.showStampBox(-1," + headerId + ")'>recent</span></td>";
                        var stamps = gen.chat.getStampList();
                        for (var i=0; i<stamps.length; i++) {
                            html += "<td><span class='gen_number_icon gen_stamp_tab gen_stamp_tab" + i + "' style='background:#BCBCBC;font-size:12px;cursor:pointer' onclick='gen.chat.showStampBox(" + i + "," + headerId + ")'>&nbsp;stp" + (i + 1) + "&nbsp;</span></td>";
                        }
                        html += "</tr></table>"
                        + "<div id='gen_chat_postContent_stamp' style='width:" + (gen.chat.mode == 't' ? (gen.window.getBrowserWidth() - 310) + "px" : "97%") + ";display:none; overflow-x:scroll; overflow-y:hidden'></div>"
                        + "<textarea id='gen_chat_postContent' style='width:97%; height:" + (gen.chat.mode == 'd'  ? "70" : "100") + "px;z-index:100'" // zindexはGOOD件数表示より上に表示するため
                            // テキストエリア内でのEnterを有効にするための処理
                            + "onFocus='window.document.onkeydown=null' "
                            + "onBlur='window.document.onkeydown=gen.window.onkeydown' "
                            + ">"
                        + "</textarea>"
                        + "<table id='gen_chat_postContent_button' border='0' cellpadding='0' style='width:98%'><tr>"
                            + "<td><input style='width:100%;height:24px' type='button' value='" + _g('送信') + (gen.chat.mode == 't' ? '' :  _g('(Shift+Enter)')) + "' onclick='gen.chat.regContent(" + headerId + ")'></td>"
                            + "<td width='24px'><div id='gen_fileUploadDiv'></div></td>"
                            + (gen.chat.mode == 't' ? "<td width='12px'><img src='img/arrow-circle-double-135.png' style='border:none'></td><td width='30px'><a href='javascript:gen.chat.readChat(" + headerId + ",true)' style='font-size:13px;color:black'>" + _g("更新") + "</a></td>" : "")
                        + "</tr></table>"
                        + "</div>";
                }
                html += "<input id='gen_chat_postHeaderId' type='hidden' value='" + headerId + "'>"
            + "</div>";
        var panel = "gen_chat_page_chatPanel";
        if (gen.chat.mode == 'd' || gen.chat.mode == 's') {
            panel = "gen_chat_panel";
        }
        $('#' + panel).html(html);

        if (gen.chat.mode == 's') {
            html = "<table width='100%'><tr>"
              + "<td width='1px'><a href=\"javascript:gen.chat.showChatList('" + searchWord + "','" + listGroupId + "')\" style='font-size:15px;color:black;white-space:nowrap'><< " + _g("戻る") + "</a></td>"
              + "<td id='gen_mobileChatTitle' style='max-width:100px;font-size:13px;color:black;text-align:center;overflow:hidden;white-space:nowrap'></td>"
              + "<td width='16px'><img src='img/arrow-circle-double-135.png' style='border:none'></td>"
              + "<td width='1px'><a href='javascript:gen.chat.readChat(" + headerId + ",true)' style='font-size:15px;color:black;white-space:nowrap'>" + _g("更新") + "</a></td>"
              + "</tr></table>";
            $('#gen_chat_header').html(html).css('display','').css('height','25px');
            $('#gen_chat_content').css('padding-top','26px').css('padding-bottom','90px').css('min-height',$('#gen_mobile_page').height()-120+'px');
            $('#gen_mobile_header').css('display','none').css('height','0px');

            html = "<div id='gen_chat_postContent_parent' style='width:100%; text-align:center;'>"
            + "<table><tr>"
            + "<td><span class='gen_number_icon gen_stamp_tab gen_stamp_tabt' style='background:#475966;font-size:12px;cursor:pointer' onclick='gen.chat.hideStampBox()'>text</span></td>";
            var stamps = gen.chat.getStampList();
            for (var i=0; i<stamps.length; i++) {
                html += "<td><span class='gen_number_icon gen_stamp_tab gen_stamp_tab" + i + "' style='background:#BCBCBC;font-size:12px;cursor:pointer' onclick='gen.chat.showStampBox(" + i + "," + headerId + ")'>stp" + (i + 1) + "</span></td>";
            }
            html += "</tr></table>"
            + "<div id='gen_chat_postContent_stamp' style='width:97%; display:none; overflow-x:scroll; overflow-y:hidden'></div>"
            + "<table><tr>"
            + "<td width='55px'>"
                + "<div id='gen_fileUploadDiv'></div>"
            + "</td>"
            + "<td width='100%'>"
                + "<textarea id='gen_chat_postContent' style='width:97%;height:50px'>"
                + "</textarea>"
            + "</td>"
            + "<td width='55px'>"
                + "<input id='gen_chat_postContent_button' style='width:50px;height:25px' type='button' value='" + _g('送信') + "' onclick='gen.chat.regContent(" + headerId + ")'>"
            + "</td>"
            + "</tr></table>"
            + "</div>";
            $('#gen_chat_footer').html(html).css('display','');
            // Mobile Chromeではスクロール位置が最下部以外のときにキーボードが出現するとフッタの位置がおかしくなるので、最下部へ強制スクロールさせる
            $('#gen_chat_postContent').on('tap',function(){
                if (window.navigator.userAgent.toLowerCase().indexOf('crios') != -1) {
                    gen.chat.scrollContents(false, false);
                }
            });
        } else {
            $('#gen_chat_postContent').expanding();
            $('.expanding-clone').css('max-height',(gen.chat.mode == 'd' ? '300px' : '500px'));    // テキストボックスが画面上端からはみ出すのを避ける
        }

        gen.fileUpload.init2("gen_fileUploadDiv", "index.php?action=Config_Setting_FileUpload&cat=chatfile&headerId=" + headerId, "", "gen.chat.afterFileUpload", "", (gen.chat.mode == 's' ? _g("写真") : _g("ファイルを送信")), (gen.chat.mode == 's' ? 45 : 100));

        gen.chat.adjustContentHeight();

        gen.chat.readChat(headerId, false, detailId, searchWord);

        if (!gen.chat.mode == 't')
            $('#gen_chat_postContent').focus();
    },

    showStampBox: function(no, headerId) {
        var stamp = null;
        if (no == -1) {
            var storage = localStorage;
            var recent = storage.getItem('gen_stamp_recent');
            if (recent !== null) {
               stamp = JSON.parse(recent);
            }
        } else {
            var stamps = gen.chat.getStampList();
            stamp = stamps[no];
        }
        var html = "<table><tr style='cursor:pointer'>";
        if (stamp === null) {
            html += "<td height='50px'>" + _g("最近使用したスタンプがありません。") + "</td>";
        } else {
            for (var i = 0; i < stamp.length; i++){
                html += "<td><img src='img/stamp/" + stamp[i][0] + "' style='width:50px;height:50px' onclick=\"gen.chat.sendStamp(" + headerId + ", '" + stamp[i][0] + "')\" onmouseover=\"gen.chat.showZoomStamp(this,'" + stamp[i][0] + "')\" onmouseout=\"gen.chat.hideZoomStamp()\")\"></td>";
            }
        }
        html += "</tr></table>"
        $('#gen_chat_postContent_stamp')
                .html(html)
                .css('display','');
        $('#gen_chat_postContent')
                .css('display','none');
        $('#gen_chat_postContent_button')
                .css('display','none');
        if (gen.chat.mode == "s") {
            $('#gen_fileUploadDiv')
                    .css('display','none');
        }
        $('.gen_stamp_tab')
                .css('background','#BCBCBC');
        $('.gen_stamp_tab' + (no == -1 ? 'r' : no))
                .css('background','#475966');
        $('.expanding-clone')
                .css('min-height','0px');
    },

    hideStampBox: function() {
        $('#gen_chat_postContent_stamp')
                .css('display','none')
        $('#gen_chat_postContent')
                .css('display','');
        $('#gen_chat_postContent_button')
                .css('display','');
        if (gen.chat.mode == "s") {
            $('#gen_fileUploadDiv')
                    .css('display','');
        }
        $('.gen_stamp_tab')
                .css('background','#BCBCBC');
        $('.gen_stamp_tabt')
                .css('background','#475966');
        $('.expanding-clone')
                .css('min-height','70px');
    },

    getStampList: function() {
        return [
            [
                ['s22.png',100,124],
                ['s23.png',100,129],
                ['s24.png',100,152],
                ['s25.png',100,109],
                ['s27.png',100,92],
                ['s28.png',100,138],
                ['s29.png',100,109],
                ['s30.png',100,109],
                ['s31.png',100,91],
                ['s32.png',100,102],
                ['s33.png',100,101],
                ['s34.png',100,101],
                ['s35.png',100,123],
                ['s36.png',100,124],
                ['s37.png',100,118],
                ['s38.png',100,90],
                ['s39.png',100,141],
                ['s41.png',100,135],
                ['s40.png',100,119],
                ['s26.png',100,106],
            ],
            [
                ['s100.png',100,100],
                ['s107.png',100,100],
                ['s115.png',100,100],
                ['s101.png',100,100],
                ['s102.png',100,100],
                ['s103.png',100,100],
                ['s104.png',100,100],
                ['s105.png',100,100],
                ['s106.png',100,100],
                ['s108.png',100,100],
                ['s109.png',100,100],
                ['s110.png',100,100],
                ['s111.png',100,100],
                ['s112.png',100,100],
                ['s113.png',100,100],
                ['s114.png',100,100],
                ['s116.png',100,100],
                ['s117.png',100,100],
                ['s118.png',100,100],
                ['s119.png',100,100],
            ],
            [
                ['s200.png',100,100],
                ['s201.png',100,100],
                ['s202.png',100,100],
                ['s203.png',100,100],
                ['s219.png',100,100],
                ['s204.png',100,100],
                ['s205.png',100,100],
                ['s206.png',100,100],
                ['s207.png',100,100],
                ['s208.png',100,100],
                ['s209.png',100,100],
                ['s210.png',100,100],
                ['s211.png',100,100],
                ['s212.png',100,100],
                ['s213.png',100,100],
                ['s214.png',100,100],
                ['s215.png',100,100],
                ['s216.png',100,100],
                ['s217.png',100,100],
                ['s218.png',100,100],
            ],
            [
                ['s300.png',100,100],
                ['s301.png',100,100],
                ['s302.png',100,100],
                ['s303.png',100,100],
                ['s304.png',100,100],
                ['s305.png',100,100],
                ['s306.png',100,100],
                ['s307.png',100,100],
                ['s308.png',100,100],
                ['s309.png',100,100],
                ['s310.png',100,100],
                ['s311.png',100,100],
                ['s312.png',100,100],
                ['s313.png',100,100],
                ['s314.png',100,100],
                ['s315.png',100,100],
                ['s316.png',100,100],
                ['s317.png',100,100],
                ['s318.png',100,100],
                ['s319.png',100,100],
            ],
            [
                ['s330.png',100,100],
                ['s331.png',100,100],
                ['s332.png',100,100],
                ['s333.png',100,100],
                ['s334.png',100,100],
                ['s335.png',100,100],
                ['s336.png',100,100],
                ['s337.png',100,100],
                ['s338.png',100,100],
                ['s339.png',100,100],
                ['s340.png',100,100],
                ['s341.png',100,100],
                ['s342.png',100,100],
                ['s343.png',100,100],
                ['s344.png',100,100],
                ['s345.png',100,100],
                ['s346.png',100,100],
                ['s347.png',100,100],
                ['s348.png',100,100],
                ['s349.png',100,100],
            ],
            [
                ['s400.png',100,100],
                ['s401.png',100,100],
                ['s402.png',100,100],
                ['s403.png',100,100],
                ['s404.png',100,100],
                ['s405.png',100,100],
                ['s406.png',100,100],
                ['s407.png',100,100],
                ['s408.png',100,100],
                ['s409.png',100,100],
                ['s410.png',100,100],
                ['s411.png',100,100],
                ['s412.png',100,100],
                ['s413.png',100,100],
                ['s414.png',100,100],
                ['s415.png',100,100],
                ['s416.png',100,100],
                ['s417.png',100,100],
                ['s418.png',100,100],
                ['s419.png',100,100],
            ],
            [
                ['s430.png',100,100],
                ['s431.png',100,100],
                ['s432.png',100,100],
                ['s433.png',100,100],
                ['s434.png',100,100],
                ['s435.png',100,100],
                ['s436.png',100,100],
                ['s437.png',100,100],
                ['s438.png',100,100],
                ['s439.png',100,100],
                ['s440.png',100,100],
                ['s441.png',100,100],
                ['s442.png',100,100],
                ['s443.png',100,100],
                ['s444.png',100,100],
                ['s445.png',100,100],
                ['s446.png',100,100],
                ['s447.png',100,100],
                ['s448.png',100,100],
                ['s449.png',100,100],
            ],
            [
                ['s1.gif',100,100],
                ['s2.png',100,100],
                ['s15.png',150,70],
                ['s16.png',150,70],
                ['s17.png',150,70],
                ['s18.png',150,70],
                ['s19.png',150,70],
                ['s20.png',150,70],
                ['s3.png',64,64],
                ['s4.png',64,64],
                ['s5.gif',100,100],
                ['s6.gif',100,100],
                ['s7.gif',100,110],
                ['s8.gif',100,110],
                ['s9.gif',100,110],
                ['s10.gif',112,112],
                ['s11.gif',112,112],
                ['s12.gif',112,112],
                ['s13.gif',90,90],
                ['s14.gif',100,100],
            ],
      ];
    },

    getOldStampList: function() {
        return [
            [
                ['s42.png',100,100],
                ['s43.png',100,100],
                ['s44.png',100,100],
                ['s45.png',100,100],
                ['s46.png',100,100],
                ['s47.png',100,93],
                ['s48.png',100,109],
                ['s49.png',100,112],
                ['s51.png',100,121],
                ['s52.png',100,130],
                ['s53.png',100,130],
                ['s54.png',100,160],
                ['s55.png',100,100],
                ['s56.png',100,100],
                ['s57.png',100,100],
                ['s58.png',100,100],
                ['s59.png',100,100],
                ['s60.png',100,100],
                ['s61.png',100,100],
                ['s62.png',100,100],  
                ['s63.png',100,88],
                ['s64.png',100,100],
                ['s65.png',100,100],
                ['s66.png',100,79],
                ['s67.png',100,100],
                ['s68.png',100,106],
                ['s69.png',100,58],
                ['s70.png',100,91]
            ],
        ];
    },
 
    sendStamp: function(headerId, file) {
        $('#gen_chat_postContent').val('[[' + file + ']]');
        gen.chat.regContent(headerId);

        var stamps = gen.chat.getStampList();
        var curStamp = null;
        sloop: for (var i = 0; i < stamps.length; i++) {
            for (var j = 0; j < stamps[i].length; j++) {
                if (stamps[i][j][0] == file) {
                    curStamp = stamps[i][j];
                    break sloop;
                }
            }
        }
        if (curStamp === null) {
           return;
       }
        var storage = localStorage;
        var recent = storage.getItem('gen_stamp_recent');
        var robj = JSON.parse(recent);
        if (robj === null) {
            robj = [];
            robj.push(curStamp);
        } else {
            robj.unshift(curStamp);
            for (var i = 1; i < robj.length; i++) {
                if (robj[i][0] == file) {
                    robj.splice(i,1);
                    break;
                }
            }
            if (robj.length > 10) {
                robj.splice(10,1);
            }
        }
        storage.setItem('gen_stamp_recent', JSON.stringify(robj));
    },

    showZoomStamp: function(srcElm, stamp) {
        gen.chat.hideZoomStamp();
        var po = $(srcElm).offset();
        $('body').append("<div id='gen_chat_zoom_stamp' style='position:absolute;top:"+ (po.top - 100) + "px;left:"+ (po.left - 25) + "px;background:white;border:1px solid #ccc;z-index:10000'><img src='img/stamp/" + stamp + "' style='width:100px;height:100px'></div>");
    },

    hideZoomStamp: function() {
        var lElm = $('#gen_chat_zoom_stamp');
        if (lElm.length > 0) {
            lElm.remove();
        }
    },

    clearChat: function() {
        var panel;
        if (gen.chat.mode == 'd' || gen.chat.mode == 's') {
            panel = "gen_chat_panel";
        } else {
            panel = "gen_chat_page_chatPanel";
        }
        $('#' + panel).html("<center>" + _g("リストでスレッドを選択するか、新規作成してください。") + "</center>");
    },

    insertLink: function(headerId) {
        var elm = $('#gen_chat_postContent');
        if (elm.css('display') == 'none') {
            return;
        }
        elm.val(elm.val() + location.protocol + '//' + location.host + location.pathname + '?action=Menu_Chat&chat_header_id=' + headerId);
    },

    insertDetailLink: function(detailId) {
        var elm = $('#gen_chat_postContent');
        if (elm.css('display') == 'none') {
            return;
        }
        elm.val(elm.val() + location.protocol + '//' + location.host + location.pathname + '?action=Menu_Chat&chat_detail_id=' + detailId);
    },

    afterFileUpload: function() {
        gen.chat.showChat($('#gen_chat_postHeaderId').val());
    },

    adjustContentHeight: function() {
        var conP = $('#gen_chat_content_parent');
        if (conP.length > 0)
            conP.css("height" ,(gen.chat.mode == 't' || gen.chat.mode == 's' ? "" : $('#gen_chat_postContent_parent').offset().top - conP.offset().top - 10));
    },

    showChatConfig: function(headerId) {
        var isNew = (headerId == undefined);
        if (gen.chat.mode == 'd' || gen.chat.mode == 's')
            $('#gen_chatDialog_h').html(isNew ? _g("スレッドを新規作成") : _g("スレッド情報を編集"));
        gen.ajax.connect('Config_Setting_AjaxChat', {op:'showConfig', headerId:headerId},
            function(j) {
                var members = "";
                $.each(j.members, function(key, val){
                    members += gen.chat.configGetLeftBoxLine(key, val, (key != j.myId));
                });
                var notMembers = "";
                $.each(j.notMembers, function(key, val){
                    notMembers += gen.chat.configGetRightBoxLine(key, val);
                });
                var groups = "";
                if (j.groups !== undefined) {
                    $.each(j.groups, function(key, val){
                        groups += "<option value='" + gen.util.escape(key) + "'";
                        if (j.groupId == key) {
                            groups += " selected"
                        }
                        groups += ">" + gen.util.escape(val) + "</option>";
                    });
                }
                var sections = "";
                if (j.sections!== undefined) {
                    $.each(j.sections, function(key, val){
                        sections += "<option value='" + gen.util.escape(key) + "'";
                        sections += ">" + gen.util.escape(val) + "</option>";
                    });
                }
                gen.chat.sectionMembers = j.sectionMembers;
                var html =
                    "<div id='gen_chat_config_content' style='width:100%; height:100%; overflow:hidden'>"
                    + "<div style='padding:10px'>";
                        if (gen.chat.mode == 'd' || gen.chat.mode == 's') {
                            html += "<a href=\"javascript:gen.chat.showChatList()\" style='font-size:13px;color:black'><< " + _g("リストに戻る") + "</a>"
                                + "<div style='height:20px'></div>";
                        }
                        html +=  "<table>"
                        + "<tr><td style='width:80px'>" + _g("スレッド名") + ":</td><td colspan='2'><input id='gen_chat_config_name' type='text' style='width:300px' value='" + gen.util.escape(j.title) + "'></td>"
                        + "<tr><td style='width:80px'>" + _g("カテゴリ") + ":</td><td style='width:200px'><select id='gen_chat_group_config' style='max-width:150px'><option value=''>(" + _g("なし") + ")</option>" + groups + "</select></td>"
                        + "<td align='right'><a href='javascript:gen.chat.createChatGroup()' style='font-size:11px;color:black'>" + _g("カテゴリ作成") + "</a>"
                        + "&nbsp;&nbsp;<a href='javascript:gen.chat.deleteChatGroup()' style='font-size:11px;color:black'>" + _g("削除") + "</a></td></tr>"
                        + "</table>"
                        + "<div style='height:20px'></div>"
                        + "<table><tr>"
                            + "<td width='200px' valign='top' style='border:1px'>" + _g("参加ユーザー") + "<br>"
                                // heightを高くするとダイアログが小さい時やiPhone版で「スレッドを作成」ボタンが画面外に出てしまう
                                + "<div style='height:270px;overflow-y:auto; border:solid 1px #cccccc'>"
                                    + "<table id='gen_chat_config_leftBox'>" + members + "</table>"
                                + "</div>"
                            + "</td>"
                            + "<td width='10px'></td>"
                            + "<td width='200px' valign='top'>" + _g("追加可能なユーザー") + "<br>"
                                + "<div style='width:100%; text-align:left'>"
                                    + "<input id='gen_chat_config_filter' type='text' style='width:120px'>"
                                    + "<input type='button' style='width:60px' value='" + _g("絞込み") + "' onclick='javascript:gen.chat.configUserFilter()'>"
                                + "</div>"
                                + "<div style='width:100%; text-align:left'>"
                                    + _g("部門") + "<select id='gen_chat_section_config' style='max-width:120px' onchange='gen.chat.configSectionChange()'><option value=''>(" + _g("なし") + ")</option>" + sections + "</select>"
                                + "</div>"
                                + "<div style='height:220px; overflow-y:auto; border:solid 1px #cccccc'>"
                                    + "<table id='gen_chat_config_rightBox'>" + notMembers + "</table>"
                                + "</div>"
                                + "<div style='height:3px'></div>"
                                + "<div style='width:100%; text-align:center'>"
                                    + "<input type='button' style='width:" + (gen.chat.mode == "s" ? "70" : "100") + "px' value='<< " + _g("追加") + "' onclick='javascript:gen.chat.configAddUser()'>"
                                    + "<input id='gen_chat_config_alterCheckAll' type='checkbox' onclick='javascript:gen.chat.configAlterCheckAll()'><span style='font-size:11px'>" + _g("全チェック") + "</span>"
                                + "</div>"
                            + "</td>"
                        + "</tr></table>"
                        + "<div style='height:10px'></div>"
                        + "<div style='padding-left:100px'>"
                            + "<input type='button' style='width:200px' value='" + (isNew ? _g("スレッドを作成") : _g("スレッドを更新")) + "' onclick='gen.chat.configChat(" + gen.util.escape(headerId) + ")'>"
                        + "</div>"
                    + "</div>"
                    + "</div>"
                var panel = "gen_chat_page_chatPanel";
                if (gen.chat.mode == 'd' || gen.chat.mode == 's') {
                    panel = "gen_chat_panel";
                }
                $('#' + panel).html(html);
            });
    },

    configSectionChange: function() {
        var sectionId = $('#gen_chat_section_config').val();
        if (gen.chat.sectionMembers === undefined) {
            return;
        }
        var members = 'all';
        if (sectionId != '') {
            members = gen.chat.sectionMembers[sectionId];
        }
        console.log(members);
        $.each($('[id^=gen_chat_config_rightBoxTr_]'), function() {
            var userId = this.id.replace('gen_chat_config_rightBoxTr_','');
            console.log(userId, $.inArray(userId, members));
            if (members == 'all' || $.inArray(userId, members) != -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    },

    configGetLeftBoxLine: function(userId, userName, allowDelete) {
        var line = "<tr id='gen_chat_config_leftBoxTr_" + gen.util.escape(userId) + "' style='height:20px'>"
            + "<td id='gen_chat_config_leftBoxTd_" + gen.util.escape(userId) + "' style='width:130px; font-size: 12px; overflow:hidden'>" + gen.util.escape(userName) + "</td>"
            + "<td>" + (allowDelete ? "<img class='imgContainer sprite-close' src='img/space.gif' style='vertical-align: middle; cursor:pointer;' title='" + _g("削除") + "' onclick='gen.chat.configDeleteUser(" + gen.util.escape(userId) + ")'>" : "") + "</td>"
            + "</tr>";
        return line;
    },

    configGetRightBoxLine: function(userId, userName) {
        var line = "<tr id='gen_chat_config_rightBoxTr_" + gen.util.escape(userId) + "' style='height:20px'>"
            + "<td><input id='gen_chat_config_rightBoxCheck_" + gen.util.escape(userId) + "' type='checkbox'></td>"
            + "<td id='gen_chat_config_rightBoxTd_" + gen.util.escape(userId) + "' style='width:100px; font-size: 12px; overflow:hidden' onclick='javascript:gen.chat.configAlterCheckUser(" + gen.util.escape(userId) + ")'>" + gen.util.escape(userName) + "</td>"
            + "</tr>";
        return line;
    },

    configUserFilter: function() {
        var filter = $('#gen_chat_config_filter').val();
        $('[id^=gen_chat_config_rightBoxTr_]').each(function(){
            var userId = this.id.replace('gen_chat_config_rightBoxTr_', '');
            var name = $('#gen_chat_config_rightBoxTd_' + userId).html();
            this.style.display = (name.indexOf(filter) >= 0 ? "" : "none");
        });
    },

    configAlterCheckUser: function(userId) {
        var elm = $('#gen_chat_config_rightBoxCheck_' + userId);
        elm.attr('checked', !elm.is(':checked'));
    },

    configAddUser: function() {
        $('[id^=gen_chat_config_rightBoxCheck_]').each(function(){
            if (this.checked) {
                var userId = this.id.replace('gen_chat_config_rightBoxCheck_', '');
                var name = $('#gen_chat_config_rightBoxTd_' + userId).html();
                $('#gen_chat_config_leftBox').append(gen.chat.configGetLeftBoxLine(userId, name, true));
                $('#gen_chat_config_rightBoxTr_' + userId).remove();
            }
        });
    },

    configAlterCheckAll: function() {
        var chk = $('#gen_chat_config_alterCheckAll').is(':checked');
        $('[id^=gen_chat_config_rightBoxTr_]').each(function(){
            var userId = this.id.replace('gen_chat_config_rightBoxTr_', '');
            if (this.style.display != "none") {
                document.getElementById('gen_chat_config_rightBoxCheck_' + userId).checked = chk;
            }
        });
   },

    configDeleteUser: function(userId) {
        var name = $('#gen_chat_config_leftBoxTd_' + userId).html();
        $('#gen_chat_config_rightBox').append(gen.chat.configGetRightBoxLine(userId, name));
        $('#gen_chat_config_leftBoxTr_' + userId).remove();
    },

    configChat: function(headerId) {
        var title = $('#gen_chat_config_name').val();
        if (title == null) {
            alert(_g("スレッド名を入力してください。"));
            return;
        }
        var users = '';
        var cnt = 0;
        $('[id^=gen_chat_config_leftBoxTd_]').each(function(){
            if (users != '')
                users += ',';
            users += this.id.replace('gen_chat_config_leftBoxTd_', '');
            cnt++;
        });
        if (cnt <= 1) {
            if (!confirm(_g("このスレッドを見ることができるのは自分だけです。よろしいですか？"))) {
                return;
            }
        }
        var groupId = $('#gen_chat_group_config').val();
        gen.ajax.connect('Config_Setting_AjaxChat', {op:'regConfig', headerId:headerId, groupId:groupId, title:title, users:users},
            function(j) {
                if (j.status == 'success') {
                    if (gen.chat.mode == 'p') {
                        gen.chat.showChatList();
                    }
                    gen.chat.showChat(j.header_id);
                } else {
                    if (j.badTitleMsg != '') {
                        alert(j.badTitleMsg);
                    }
                }
            });
    },

    createRecordChat: function(actionGroup, recordId, tempUserId, title) {
        gen.ajax.connect('Config_Setting_AjaxChat', {op:'regConfig', groupId:'record', title:title, users:'all', actionGroup:actionGroup, recordId:recordId, tempUserId:tempUserId},
            function(j) {
                if (j.status == 'success') {
                    var area = $($('#gen_editFrame').get(0).contentWindow.document.getElementById('gen_record_chat_link'));
                    if (area.length > 0) {
                       area.html("<a href=\"javascript:parent.gen.chat.init('d', " + j.header_id + ", '', '', '', '', '')\">" + _g("スレッド表示") + "</a>");
                    }
                    gen.chat.init('d', j.header_id, '', '', '', '', '');
                } else {
                    if (j.badTitleMsg != '') {
                        alert(j.badTitleMsg);
                    }
                }
            });
    },

    createChatGroup: function() {
        var name = window.prompt(_g("作成するカテゴリ名を入力してください。"));
        if (name == "" || name === null) {
            return;
        }
        gen.ajax.connect('Config_Setting_AjaxChat', {op:'createGroup', name:name},
            function(j) {
                if (gen.util.isNumeric(j.id)) {
                    $('#gen_chat_group_config')
                        .append($("<option value='" + j.id + "'>" + gen.util.escape(name) + "</option>"))
                        .val(j.id);
                } else {
                    if (j.badNameMsg != '') {
                        alert(j.badNameMsg);
                    }
                }
            });
    },

    deleteChatGroup: function() {
        var groupId = $('#gen_chat_group_config').val();
        if (groupId == '') {
            alert(_g("削除するカテゴリを選択してから「削除」リンクをクリックしてください。"));
            return;
        }
        var name = $('#gen_chat_group_config option:selected').text();
        if (!confirm(_g("カテゴリ「%s」を削除します。よろしいですか？").replace('%s',gen.util.escape(name)))) {
            return;
        }
        gen.ajax.connect('Config_Setting_AjaxChat', {op:'deleteGroup', groupId:groupId},
            function(j) {
                $('#gen_chat_group_config option[value=' + groupId + ']').remove();
            });
    },

    readChat: function(headerId, isUnreadOnly, detailId, searchWord) {
        var con = $('#gen_chat_content');
        gen.ajax.connect('Config_Setting_AjaxChat', {op:'read', headerId:headerId, isUnreadOnly:isUnreadOnly, imgMaxWidth: con.get(0).clientWidth - 50},
            function(j) {
                if (j.status == 'permissionError') {
                    con.html(_g("このスレッドは存在しないか、閲覧する権限がありません。"));
                    return;
                }
                var html = "";

                if (!isUnreadOnly) {
                    if (j.is_ecom || j.is_system) {
                        html += "<span id='gen_chat_ecomsystem_flag'></span>";
                    } else {
                        var users = '';
                        if (j.users.length > 0) {
                            $.each(j.users, function(key, val){
                                if (users != '')
                                    users += ', ';
                                users += gen.util.escape(val.user_name);
                            });
                        }
                        var group = "";
                        if (j.group !== undefined && j.group !== null) {
                            group = j.group;
                        }
                        html += "<div style='width:100%;background:#e3eaef'>"
                            + "<table><tr align='left'><td width='60px' valign='top'><b>" + _g("オーナー") + "</b></td><td>" + gen.util.escape(j.author)
                            + "<img src='img/space.gif' style='width:30px;height:1px'><a href='javascript:gen.chat.showChatConfig(" + gen.util.escape(headerId) + ")' style='font-size:11px;color:#000000'>" + _g("タイトル・メンバーを変更") + "</a>";
                        if (j.is_mine) {
                            html += "&nbsp;&nbsp;<a href='javascript:gen.chat.deleteChat(" + gen.util.escape(headerId) + ")' style='font-size:11px;color:#000000'>" + _g("スレッドを削除") + "</a>";
                        }
                        html +=
                            "</td></tr>"
                            + "<tr align='left'><td valign='top'><b>" + _g("メンバー") + "</b></td><td>" + users + "</td></tr>"
                            + "<tr align='left'><td valign='top'><b>" + _g("カテゴリ") + "</b></td><td>" + gen.util.escape(group)
                            // gen_noChatShowは 全画面モードからリンクデータを表示し、そこからまたスレッドを表示しようとした時の不具合対処
                            + (j.edit_action == "" ? "" : "<a href=\"javascript:gen.modal.open('index.php?action=" + gen.util.escape(j.edit_action) + "&gen_noChatShow');\" style='padding-left:30px;'>" + _g("リンクデータ表示") + "</a>")
                            + "</td></tr>"
                            + "</table>"
                            + "</div>";
                    }
                }

                var isSearch = (searchWord != undefined && searchWord != "");
                if (isSearch) {
                    searchWord = searchWord.replace(/([\\\*\+\.\?\{\}\(\)\[\]\^\$\-\|\/])/g, "\\$1").replace(" ", "|").replace("　", "|");
                }
                var unreadedTopId = false;
                if (j.contents.length > 0) {
                    var isEcomOrSystem = (isUnreadOnly ? $('#gen_chat_ecomsystem_flag').length > 0 : (j.is_ecom || j.is_system));
                    $.each(j.contents, function(key, val){
                        if (isSearch) {
                            val.content = gen.util.escape(val.content).replace(new RegExp("(" + searchWord + ")", "ig"), "<span style='color:red;font-weight:bold'>$1</span>");
                        }
                        var isReaded = (j.readedId !='' && parseInt(j.readedId) >= parseInt(val.id));
                        if (!isReaded && !unreadedTopId) {
                            unreadedTopId = val.id;
                        }
                        var bgColor = "";
                        if (val.id == detailId) {
                            bgColor = "8DC4FC"; // 明細指定（通知センターリンク等）
                        } else if (!isReaded && !val.is_mine) {
                            bgColor = "ffff66"; // 未読
                        }
                        html += gen.chat.writeContent(gen.util.escape(val.id), gen.util.escape(val.user_id), gen.util.escape(val.name)
                         , gen.util.escape(val.time), val.content, gen.util.escape(val.file), gen.util.escape(val.org_file), gen.util.escape(val.file_size)
                         , gen.util.escape(val.img_w), gen.util.escape(val.img_h), bgColor, val.is_mine, val.is_star, gen.util.escape(val.like_count), val.is_like
                         , isEcomOrSystem);
                    });
                }

                if (isUnreadOnly) {
                    $('.gen_chat_content_detail_color').css("background","#FAFCF0");   // 差分更新時は未読/明細指定時の背景色をクリア
                    con.append(html);
                } else {
                    $('#gen_chat_thread_pin_area').html("<img id='gen_chat_thread_pin_" + gen.util.escape(headerId) + "' src='img/pin" + (j.is_pin == "1" ? "01" : "02") + ".png' style='vertical-align: middle; cursor:pointer; padding-right:10px' onclick='gen.chat.pinChatList(" + gen.util.escape(headerId) + ",true)' title='" + _g("このスレッドをピン留め") + "'>")
                    if (gen.chat.mode == 'd') {
                        $('#gen_chatDialog_h').text(j.title);
                    } else if (gen.chat.mode == 's') {
                        $('#gen_mobileChatTitle').text(j.title);
                    } else {
                        $('#gen_chat_titleArea').text(j.title);
                        if (j.group != null && j.group != '') {
                            $('#gen_chat_groupArea').text(' [' + j.group + ']');
                        }
                    }
                    con.css('visibility','hidden');
                    con.html(html);
                    if (j.is_ecom || j.is_system) {
                        $('#gen_chat_postContent_parent').css('min-height','0px').css('height','0px').html('');
                        gen.chat.adjustContentHeight();
                    }
                }

                // LazyLoad と scrollContents。
                //  FFの場合、lazyload ⇒ scrollContents の順である必要がある。
                //  jQuery mobileの場合、scrollContents ⇒ lazyload の順である必要がある。
                //  IE/Ch はどちらでも可。
                if (gen.chat.mode != 's') {
                    $("img.gen_chat_image").lazyload({container:$('#gen_chat_content_parent')});
                }
                gen.chat.scrollContents(gen.util.isNumeric(detailId) ? detailId : unreadedTopId, isUnreadOnly);
                if (gen.chat.mode == 's') {
                    $("img.gen_chat_image").lazyload();
                }

                if (!isUnreadOnly) {
                    // span追加はchromeでのlike数アイコン乱れ対策。表示後をDOMを何か操作するとなぜか正常になる。
                    con.css('visibility','').append('<span></span>');
                }

                badge = $('#gen_chatUnreadCount');
                if (badge.length > 0) {
                    if (j.unreadCount > 0) {
                        badge.text(j.unreadCount);
                    } else {
                        badge.remove();
                    }
                }
                badge = $('#gen_chatUnreadCountEcom');
                if (badge.length > 0) {
                    if (j.unreadCountEcom > 0) {
                        badge.text(j.unreadCountEcom);
                    } else {
                        badge.remove();
                    }
                }
                badge = $('#gen_chatUnreadCountSystem');
                if (badge.length > 0) {
                    if (j.unreadCountSystem > 0) {
                        badge.text(j.unreadCountSystem);
                    } else {
                        badge.remove();
                    }
                }
            });
    },

    scrollContents: function(contentId, isAnimate) {
        var scrollElm = null;
        if (gen.chat.mode == 't' || gen.chat.mode == 's') {
            scrollElm = document.body;
        } else {
            scrollElm = document.getElementById('gen_chat_content_parent');
        }
        var scrollTo = scrollElm.scrollHeight;
        if (contentId) {
            if (gen.chat.mode == 't' || gen.chat.mode == 's') {
                scrollTo = $('#gen_chat_content_' + contentId).offset().top;
                if (gen.chat.mode == 's') {
                    scrollTo -= 30;
                }
            } else {
                scrollTo = scrollElm.scrollTop + $('#gen_chat_content_' + contentId).offset().top - $(scrollElm).offset().top;
            }
        }
        if (scrollElm.scrollTop != scrollTo) {
            if (isAnimate) {
                $(scrollElm).animate({
                  scrollTop: scrollTo,
                },{
                  easing: "linear",
                  duration: 300
                });
            } else {
                scrollElm.scrollTop = scrollTo;
            }
        }
    },

    writeContent: function(detailId, userId, user, time, content, fileName, originalFileName, fileSize, imgWidth, imgHeight, bgColor, isMine, isStar, likeCount, isLike, isEcomOrSystem) {
        var stampTag = "";
        if (content.substr(0,2) == "[[" && content.substr(content.length - 2,2) == "]]") {
            var stampFile = content.substr(2,content.length - 4);
            var stamps = gen.chat.getStampList();
            for (var no = 0; no < stamps.length; no++){
                for (var i = 0; i < stamps[no].length; i++){
                    if (stampFile == stamps[no][i][0]) {
                        stampTag = "<img src='img/stamp/" + stamps[no][i][0] + "' style='width:" + stamps[no][i][1] + "px;height:" + stamps[no][i][2] + "px'>";
                        break;
                    }
                }
                if (stampTag != "") {
                    break;
                }
            }
            if (stampTag == "") {
                stamps = gen.chat.getOldStampList();
                for (var no = 0; no < stamps.length; no++){
                    for (var i = 0; i < stamps[no].length; i++){
                        if (stampFile == stamps[no][i][0]) {
                            stampTag = "<img src='img/stamp/" + stamps[no][i][0] + "' style='width:" + stamps[no][i][1] + "px;height:" + stamps[no][i][2] + "px'>";
                            break;
                        }
                    }
                    if (stampTag != "") {
                        break;
                    }
                }
           }
        }
        var html =
            "<div id='gen_chat_content_" + detailId + "'>"
            + "<hr>"
            + (bgColor == "" ? "" : "<div class='gen_chat_content_detail_color' style='background:#" + bgColor + "'>")
            + "<table><tr><td valign='top'>";
            if (isEcomOrSystem) {
                html += "<img src='img/header/header_gen.png' style='width:32px'>";
            } else {
                html += "<img src='index.php?action=download&cat=userprofileimage&userId=" + userId + "' style='width:32px;height:32px' onclick='gen.chat.insertDetailLink("+ detailId + ")'>"
            }
            html += "</td><td align='left'>"
            + "<span id='gen_chat_content_user_" + detailId + "' style='color:blue; font-weight:bold'>" + user + "</span>"
            + "<img src='img/space.gif' style='width:20px;height:1px'>"
            + "<span style='color:#888;'>" + time + "</span>";
            if (!isEcomOrSystem) {
                html += "<img src='img/space.gif' style='width:30px;height:1px'>"
                + ((fileName == undefined || fileName) == "" && stampTag == "" ? "<a href='javascript:gen.chat.reply(" + detailId + ")' style='font-size:12px;color:black;padding-right:10px'>" + _g("返信") + "</a>" : "")
                + (isMine ? "<a href='javascript:gen.chat.deleteContent(" + detailId + ")' style='font-size:12px;color:black;padding-right:10px'>" + _g("削除") + "</a>" : "")
                + "<span id='gen_chat_stararea_" + detailId + "'>" + gen.chat.getStarHtml(detailId, isStar) + "</span>"
                + "<span id='gen_chat_likearea_" + detailId + "'>" + gen.chat.getLikeHtml(detailId, isLike, likeCount) + "</span>";
            }
            html += "<div style='height:7px'></div>";
            if (fileName == undefined || fileName == "") {
                if (stampTag == "") {
                    html += "<span id='gen_chat_content_text_" + detailId + "' style='font-size:13px'>"
                         + content.replace(/\[br\]/gi, "<br>")
                            .replace(/(http[s]?\:\/\/[\w\+\$\;\?\.\%\,\!\#\~\*\/\:\@\&\\\=\_\-]+)/g, "<a href='$1' style='word-break:break-all;display:inline-block;' target='_blank'>$1</a>")
                            .replace(/\[chat-detail-link:(.*):\]/g, "<a href='index.php?action=" + (gen.chat.mode == "s" ? "Mobile" : "Menu") + "_Chat&chat_detail_id=$1' style='color:black;word-break:break-all;display:inline-block;' target='_blank'>[" + _g("見る") + "]</a>")
                            .replace(/\[record-link:(.*):\]/g, (gen.chat.mode == "s" ? "" : "<a href=\"javascript:gen.modal.open('index.php?action=$1')\" style='color:black;word-break:break-all;display:inline-block;'>[" + _g("見る") + "]</a>"))
                            .replace(/\[list-link:(.*):\]/g, (gen.chat.mode == "s" ? "" : "<a href='index.php?action=$1' style='color:black;word-break:break-all;display:inline-block;' target='_blank'>[" + _g("見る") + "]</a>"))
                         + "</span>";
                } else {
                    html += stampTag;
                }
            } else {
                var urlBase = "index.php?action=download&cat=chatFiles&file=" + fileName;
                if (gen.util.isNumeric(imgWidth) && gen.util.isNumeric(imgHeight)) {
                    // 画像のサイズをあえて指定している理由についてはサーバー側 img_w の設定部分を参照
                    html += "<img class='gen_chat_image' data-original='" + urlBase + "' style='width:" + imgWidth + "px;height:" + imgHeight + "px;max-width:100%'>&nbsp;&nbsp;&nbsp;" + fileSize;
                } else {
                    html += "<a href='" + urlBase + "'>" + originalFileName + "</a>&nbsp;&nbsp;&nbsp;" + fileSize;
                }
            }
            html += "</td></tr></table>"
            + (bgColor == "" ? "" : "</div>")
            + "</div>";
        return html;
    },

    getStarHtml: function(detailId, isStar) {
        return "<a href='javascript:gen.chat." + (isStar ? "del" : "reg") + "Star(" + detailId + ")' style='padding-right:5px;font-size:15px;text-decoration:none;color:#" + (isStar ? "FFD76E" : "B8B8BE") + "'>★</a>"
    },

    getLikeHtml: function(detailId, isLike, likeCount) {
        var likeMsg = _g("Good！");
        return ""
            + (isLike ? "<span class='gen_like_done_button'>" + likeMsg + "</span>"
                : "<a href='javascript:gen.chat.regLike(" + detailId + ")' class='gen_like_button' style='padding-right:3px'>" + likeMsg + "</a>")
            + (likeCount == 'null' || likeCount <= 0 ? ""
                : "<div style='display:inline-block' onmouseover='gen.chat.showLikeUsers(" + detailId + ")' onmouseout='gen.chat.hideLikeUsers()'><div id='gen_chat_likecount_" + detailId + "' class='gen_like_count'>" + likeCount + "</div><div class='gen_like_count_sub'><s></s><i></i></div></div>")
            + (isLike ? "<a href='javascript:gen.chat.delLike(" + detailId + ")' style='font-size:11px;color:black;padding-left:5px'>" + _g("取消") + "</a>"
                : "");
    },

    deleteChat: function(headerId) {
        if (!confirm(_g("このスレッドを削除します。すべてのデータが失われます。本当に削除しますか？"))) {
            return;
        }
        gen.chat.clearChat();
        gen.ajax.connect('Config_Setting_AjaxChat', {op:'deleteChat', headerId:headerId},
            function(j) {
                gen.chat.showChatList();
            });
    },

    regContent: function(headerId) {
        var contentElm = $('#gen_chat_postContent');
        var content = contentElm.val();
        if (content == "")
            return;
        var brContent = content.replace(/\r\n/g, '[br]').replace(/(\n|\r)/g, '[br]');
        var postContent = encodeURIComponent(brContent);
        gen.ajax.connect('Config_Setting_AjaxChat', {op:'reg', headerId:headerId, content: postContent},
            function(j) {
                if (j.status == 'success') {
                    contentElm.val("");
                    contentElm.trigger("change.expanding");
                    gen.chat.readChat(headerId, true);
                } else {
                    alert(_g("登録に失敗しました。"));
                }
            });
    },

    deleteContent: function(detailId) {
        if (!window.confirm(_g("発言を削除してもよろしいですか？"))) {
            return;
        }
        gen.ajax.connect('Config_Setting_AjaxChat', {op:'delete', detailId:detailId},
            function(j) {
                if (j.status == 'success') {
                    $('#gen_chat_content_' + detailId).remove();
                } else {
                    alert(_g("削除に失敗しました。"));
                }
            });
    },

    regStar: function(detailId) {
        gen.ajax.connect('Config_Setting_AjaxChat', {op:'star', detailId:detailId},
            function(j) {
                if (j.status == 'success') {
                    gen.chat.updateStar(detailId, true);
                } else if (j.msg != "") {
                    alert(j.msg);
                } else {
                    alert(_g("登録に失敗しました。"));
                }
            });
    },

    delStar: function(detailId) {
        gen.ajax.connect('Config_Setting_AjaxChat', {op:'delStar', detailId:detailId},
            function(j) {
                if (j.status == 'success') {
                    gen.chat.updateStar(detailId, false);
                } else if (j.msg != "") {
                    alert(j.msg);
                } else {
                    alert(_g("登録に失敗しました。"));
                }
            });
    },

    updateStar: function(detailId, isStar) {
        $('#gen_chat_stararea_' + detailId).html(gen.chat.getStarHtml(detailId, isStar));
    },

    regLike: function(detailId) {
        gen.ajax.connect('Config_Setting_AjaxChat', {op:'like', detailId:detailId},
            function(j) {
                if (j.status == 'success') {
                    gen.chat.updateLike(detailId, true);
                } else if (j.msg != "") {
                    alert(j.msg);
                } else {
                    alert(_g("登録に失敗しました。"));
                }
            });
    },

    delLike: function(detailId) {
        gen.ajax.connect('Config_Setting_AjaxChat', {op:'delLike', detailId:detailId},
            function(j) {
                if (j.status == 'success') {
                    gen.chat.updateLike(detailId, false);
                } else if (j.msg != "") {
                    alert(j.msg);
                } else {
                    alert(_g("登録に失敗しました。"));
                }
            });
    },

    updateLike: function(detailId, isLike) {
        var likeCount = 0;
        var elm = $('#gen_chat_likecount_' + detailId);
        if (elm.length > 0) {
            var cnt = elm.html();
            if (gen.util.isNumeric(cnt)) {
                likeCount = parseInt(cnt) + (isLike ? 1 : -1);
            }
        } else {
            if (isLike) {
                likeCount = 1;
            }
        }
        $('#gen_chat_likearea_' + detailId).html(gen.chat.getLikeHtml(detailId, isLike, likeCount));
    },

    showLikeUsers: function(detailId) {
        gen.chat.hideLikeUsers();
        $('body').append("<div id='gen_chat_likeusers' style='position:absolute;top:0;left:0;width:120px;max-height:300px;padding:8px;background:white;border:1px solid #ccc;z-index:10000'>" + _g("読み込み中") + "...</div>");
        var elm = $('#gen_chat_likeusers');
        var po = $('#gen_chat_likecount_' + detailId).offset();
        var params = {left: po.left, top: po.top + 20, width:elm.width(), height:elm.height()}
        params = gen.window.adjustBoxPosAndSize(params, false);
        elm.css({left: params.left, top: params.top});
        gen.ajax.connect('Config_Setting_AjaxChat', {op:'getLikeUsers', detailId:detailId},
            function(j) {
                var h = "<span style='font-size:11px'>" + _g("ユーザー名") + "</span><br><br>";
                $.each(j.users, function(key, val){
                    h += gen.util.escape(val) + '<br>';
                });
                elm.html(h);
                params.width = elm.width();
                params.height = elm.height();
                params = gen.window.adjustBoxPosAndSize(params, false);
                elm.css({left: params.left, top: params.top});
            });
    },

    hideLikeUsers: function() {
        var lElm = $('#gen_chat_likeusers');
        if (lElm.length > 0) {
            lElm.remove();
        }
    },

    reply: function(detailId) {
        var elm = $('#gen_chat_postContent');
        if (elm.css('display') == 'none') {
            return;
        }
        var user = $("#gen_chat_content_user_" + detailId).html();
        var txt = $("#gen_chat_content_text_" + detailId).html();
        txt = _g("%s さん").replace("%s", user) + "\n\n"
            + txt.replace(/<br>/g,"\n").replace(/&lt;/g,"<").replace(/&gt;/g,">").replace(/<a .*?>/g,"").replace(/<\/a>/g,"").replace(/(^.*$)/gm,"> $1") + "\n";
        elm.focus();
        elm.val(txt);
        elm.trigger("change.expanding");
    },

};
