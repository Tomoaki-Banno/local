if (!gen) var gen = {};

// WaitDialog
// Needs: yui/animation.js, yui/dragdrop.js, yui/container.js, yui/container.css
// 使い方：
//   表示： gen.waitdialog.show('お待ちください..');
//   閉じる： gen.waitdialog.hide();

gen.waitDialog = {
	dialog: null,
	count: 0,

	_init: function(msg) {
        this.dialog = new YAHOO.widget.Panel(
	        "gen_wait_div",
	        {
	            width: "240px",
	            fixedcenter: true,
	            close: false,
	            draggable: false,
	            zindex: 4,
	            //modal: true,
	            visible: false
	        }
	    );

	    this.dialog.setHeader(msg);
	    this.dialog.setBody('<center><img src="img/loading.gif"><center>');
	    this.dialog.render(document.body);
        
            $('#gen_wait_div').wrap("<div class='yui-skin-sam'>");
	},

	show: function(msg) {
            if (!msg) {     // 引数省略時のエラー回避
                msg = 'Now loading...'; 
            }
	    if (this.dialog == null) {
	        gen.waitDialog._init(msg);
	    }

	    this.count++;
	    this.dialog.show();
	},

	hide: function() {
            if (this.count > 1) {
                this.count--;
                return;
            }
            this.count = 0;
            if (this.dialog == null) return;
            this.dialog.hide();
	}
};
