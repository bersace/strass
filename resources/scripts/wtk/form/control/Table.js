dojo.provide("wtk.form.control.Table");
dojo.require("dojo.dnd.Source");
dojo.require("dijit._Widget");

dojo.declare("wtk.form.control.Table",[dijit._Widget],{
	source: null,
	postCreate: function(){
		this.reorderable = this.domNode.className.search(/reorderable/) > 0;
		this.extensible = this.domNode.className.search(/extensible/) > 0;

		if (this.reorderable) {
			this.source = new dojo.dnd.Source(this.domNode,
							  {accept:["wtk.form.control.TableRow"],
							   skipForm: true});
		}

		this.headRow = this.domNode.tHead.rows[0];
		if (this.extensible) {
			this._buildHeader("first");
			this._buildHeader("last");
		}
	},

	_buildHeader: function(position){
		var th = document.createElement("th");
		dojo.place(th, this.headRow, position);
	},
});