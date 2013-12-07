dojo.provide("wtk.image.Photo");
dojo.require("dijit._Widget");
dojo.require("wtk.fx.Resize");
dojo.require("dojo.fx.easing");

dojo.declare("wtk.image.Photo",[dijit._Widget],{
	margin: 128,
	maxwidth: 0,
	owidth: 0,
	oheight: 0,
	resized: false,
	animate: false,

	postCreate: function(){
		this.owidth = parseInt(this.domNode.getAttribute("width"));
		this.oheight = parseInt(this.domNode.getAttribute("height"));

		this._onResize();
		this.switchState(0);

		this.domNode.style.cursor = "pointer";

		dojo.connect(this.domNode, "onclick", this, this._onClick);
		dojo.connect(window,"onresize",this,this._onResize);
		this.animate = true;
	},

	switchState: function(duration){
		var viewport = dijit.getViewport();
		var mw = viewport.w - this.margin;
		var mh = viewport.h - this.margin;

		coords = dojo.coords(this.domNode);

		var r;
		if (this.resized) {
			r  = this.maxwidth/this.owidth;
		}
		else {
			var rh = mw/this.owidth;
			var rv = mh/this.oheight;
			r = Math.min(rh, rv);
		}

		var nw = this.owidth*r;
		var nh = this.oheight*r;

		if (this.animate) {
			wtk.fx.Resize({node: this.domNode,
				       duration: duration,
				       width: nw, height: nh,
				       method: "combine",
				       easing: dojo.fx.easing.circOut
				      }).play();
		}
		else {
			this.domNode.style.width = nw+"px";
			this.domNode.style.height = nh+"px";
		}

		this.resized = !this.resized;
	},

	_onClick: function(){
		this.switchState(500);
	},

	_onResize: function(){
		// calcul de la largeur maximale.
		var s = dojo.style(this.domNode);
		var p = parseInt(s.paddingTop);
		var b = parseInt(s.borderTopWidth);
		var s = dojo.style(this.domNode.parentNode.parentNode);
		var w = parseInt(s.width);
		this.maxwidth = w - 2*(p+b);

		this.resized = !this.resized;
		this.switchState(250);
	}
});
