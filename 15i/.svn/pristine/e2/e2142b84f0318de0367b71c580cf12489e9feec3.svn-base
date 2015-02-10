if (!gen) var gen = {};

gen.slider = {
	slideInUse: new Array(),

	// スライダのイニシャライズ
	init: function(divName, linkDivName, showText, hideText, isDefaultShow, isHorizontal, callback, switchIconID) {
	    var state = this._getStateFromCookie(divName);
	    var isShow = (state==0 ? true : (state==1 ? false : isDefaultShow));

	    linkText = (isShow ? hideText : showText);
	    document.getElementById(linkDivName).innerHTML = "<span id='" + linkDivName + "_link'>" + (isShow ? hideText : showText) + '</span>';
	    document.getElementById(divName).style.display = (isShow ? '' : 'none');

	    document.getElementById(linkDivName).onclick = function() {
                gen.slider._switching(divName, linkDivName, showText, hideText, isHorizontal, callback);
	    };
            if (switchIconID !== undefined && switchIconID !== "") {
                document.getElementById(switchIconID).onclick = function() {
                    gen.slider._switching(divName, linkDivName, showText, hideText, isHorizontal, callback);
                };
            }
	},

	// スライダの開閉
	_switching: function(divName, linkDivName, showText, hideText, isHorizontal, callback) {
	    var elm = document.getElementById(divName);
	    var els = elm.style;
            var orgOverflow = els.overflow;
            var orgOverflowX = els.overflowX;
            var orgOverflowY = els.overflowY;
	    if (els.display == 'none' || els.display == null) { // null判定は Safari対応
	        // 非表示のままdivの高さを測る
	        var originalVisibility = els.visibility;
	        var originalPosition = els.position;
	        var originalDisplay = els.display;
	        els.overflow='hidden';    // スライド完了後に無効にしていることに注意
	        var onComp_show = function() {
	            els.overflow = orgOverflow;
	            els.overflowX = orgOverflowX;
	            els.overflowY = orgOverflowY;
	        };
	        els.visibility = 'hidden';
	        els.position = 'absolute';
	        els.display = 'block';
	        var h = elm.clientHeight;
                if (gen.util.isIE) {
                    var w = elm.offsetWidth;
                } else {
                    var w = elm.clientWidth;
                }
	        els.display = originalDisplay;
	        els.position = originalPosition;
	        els.visibility = originalVisibility;
	        this.doSlide(divName,h,w,isHorizontal,{duration:(gen_iPad ? 0.1 : 0.3), onComplete:onComp_show, callback:callback}).down();
	        document.getElementById(linkDivName).innerHTML = "<span id='" + linkDivName + "_link'>" + hideText + '</span>';
	        this._save(divName, true);
	    } else {
	        var h = elm.offsetHeight;
                var w = elm.offsetWidth;
	        els.overflow='hidden';    // offsetHeight測定後に行う必要がある。スライド完了後に無効にしていることに注意
	        var onComp_hide = function() {
	            els.overflow = orgOverflow;
	            els.overflowX = orgOverflowX;
	            els.overflowY = orgOverflowY;
	        };
	        this.doSlide(divName,h,w,isHorizontal,{duration:(gen_iPad ? 0.1 : 0.3), onComplete:onComp_hide, callback:callback}).up();
	        document.getElementById(linkDivName).innerHTML = "<span id='" + linkDivName + "_link'>" + showText + '</span>';
	        this._save(divName, false);
	    }
	},

	// 開閉状態の保存
	_save: function(divName, isShow) {
	    this._setStateToCookie(divName, isShow);
	    // DBにも保存（Setting）
	    gen.ajax.connect('Config_Setting_AjaxSlider', {name : divName, isOpen : isShow}, 
                function(j){
                });
	},

	// 開閉状態をCookieから取得
	_getStateFromCookie: function(divName) {
	    var show = gen.util.getCookie("gen_slider_show");
	    if (show.indexOf(divName) != -1) {
	        return 0;   // show
	    }
	    var hide = gen.util.getCookie("gen_slider_hide");
	    if (hide.indexOf(divName) != -1) {
	        return 1;   // hide
	    }
	    return 2;   // Unknown
	},

	// 開閉状態をCookieに保存
	_setStateToCookie: function(divName, isShow) {
	    var showValue = gen.util.getCookie("gen_slider_show").replace(divName + ":", "");
	    var hideValue = gen.util.getCookie("gen_slider_hide").replace(divName + ":", "");
	    // ブラウザのCookie容量制限により既存Cookieが失われるのを避けるため、サイズオーバーのときは保存しないようにする。
	    if (showValue.length + hideValue.length <= 1000) {
		    if (isShow) {
		        showValue += divName + ":";
		    } else {
		        hideValue += divName + ":";
		    }
		 }
	    document.cookie = "gen_slider_show=" + showValue + ";";
	    document.cookie = "gen_slider_hide=" + hideValue + ";";
	},

	// スライダ　メイン
	doSlide: function(objId, objHeight, objWidth, isHorizontal, options) {
	    this.obj = document.getElementById(objId);
	    this.duration = 1;
	    this.height = parseInt(objHeight);
            this.width = parseInt(objWidth);

	    if (typeof options != 'undefined') { this.options = options; } else { this.options = {}; }
	    if (this.options.duration) { this.duration = this.options.duration; }

	    this.up = function() {
	        if (this.slideInUse[objId] != true) {
                    if (isHorizontal) {
                        this.curHeight = this.height;
                        this.newHeight = (gen.util.isIE ? "1" : "0");;
                        var finishTime = this.slide();
                        window.setTimeout("gen.slider.doSlide('"+objId+"').finishup("+this.height+");",finishTime);
                    } else {
                        this.curWidth = this.width;
                        this.newWidth = (gen.util.isIE ? "1" : "0");;
                        var finishTime = this.slide();
                        window.setTimeout("gen.slider.doSlide('"+objId+"').finishLeft("+this.width+");",finishTime);
                    }
	        }
	    };

	    this.down = function() {
	        if (this.slideInUse[objId] != true) {
                    if (isHorizontal) {
                        this.curHeight = (gen.util.isIE ? "1" : "0");
                        this.newHeight = this.height;
                        this.obj.style.height = this.curHeight + "px";
                    } else {
                        this.curWidth = (gen.util.isIE ? "1" : "0");
        	        this.newWidth = this.width;
                        this.obj.style.width = this.curWidth + "px";
                    }
	            this.obj.style.display = 'block';
	            this.slide();
	        }
	    };

	    this.slide = function() {
	    	this.slideInUse[objId] = true;
	        var frames = 30 * this.duration; // Running at 30 fps

	        var tIncrement = (this.duration*1000) / frames;
	        tIncrement = Math.round(tIncrement);
	        var sIncrement = (isHorizontal ? (this.curHeight-this.newHeight) : (this.curWidth-this.newWidth)) / frames;
	        var frameSizes = new Array();
	        var totalSize = (isHorizontal ? (this.curHeight-this.newHeight) : (this.curWidth-this.newWidth));
	        var fSizeSum = 0;
	        var f = 0;
	        for (var i=0; i < frames; i++) {
	            if (i < frames/2) {
	                f = (sIncrement * (i/frames))*4;
	            } else {
	                if (i==(frames-1)) {
	                    f = totalSize - fSizeSum;
	                } else {
	                    f = (sIncrement * (1-(i/frames)))*4;
	                }
	            }
	            frameSizes[i] = Math.round(f);
	            fSizeSum += frameSizes[i];
	        }

	        var deltaTotal = 0;
	        for (var i=0; i < frames; i++) {
                    if (isHorizontal) {
	            this.curHeight -= frameSizes[i];
	            s = "document.getElementById('"+objId+"').style.height='"+(this.curHeight)+"px';";
                    } else {
                        this.curWidth -= frameSizes[i];
                        s = "document.getElementById('"+objId+"').style.width='"+(this.curWidth)+"px';";
                    }
	            if (this.options.callback) {
	                deltaTotal += frameSizes[i];
	                s+= this.options.callback + "(" + deltaTotal + "," + (i+1 >= frames) + "," + isHorizontal + ")";
	            }
	            window.setTimeout(s, tIncrement * i);
	        }

	        window.setTimeout("delete(gen.slider.slideInUse['"+objId+"']);",tIncrement * i);

	        if (this.options.onComplete) {
	            window.setTimeout(this.options.onComplete, tIncrement * i);
	        }

	        return tIncrement * i;
	    };

	    this.finishup = function(height) {
	        this.obj.style.display = 'none';
	        this.obj.style.height = height + 'px';
	    };

	    this.finishLeft = function(width) {
	        this.obj.style.display = 'none';
	        this.obj.style.width = width + 'px';
	    };

	    return this;
	}
};

// *******************
//  tab
// *******************

gen.tab = {
	// イニシャライズ
	init: function(tabObj, tabId) {
	    var tabIndex = gen.util.getCookie("gen_tab_"+tabId);
	    if (tabIndex == '') tabIndex = 0;
	    tabObj.set('activeIndex', tabIndex);

	    tabObj.addListener('activeIndexChange',function(){gen.tab.save(tabId, tabObj.get('activeIndex'));});
	},

	// タブ選択状態の保存
	save: function(tabId, tabIndex) {
	    // Cookieに保存
            document.cookie = "gen_tab_" + tabId + "=" + tabIndex + ";";
	}
};
