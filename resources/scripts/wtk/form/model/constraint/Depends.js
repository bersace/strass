dojo.provide("wtk.form.model.constraint.Depends");
dojo.require("wtk.form.model.Constraint");

// TODO: récursion des modification 
dojo.declare("wtk.form.model.constraint.Depends",[wtk.form.model.Constraint],{
	// domNode dont dépend le widget contraint.
	depends: null,
	init: function(){
		var clazz = this.domNode.getAttribute("class");
		var depid = clazz.match(/depends-([^ ]+)/)[1];
		this.depends = dojo.byId(depid);
		dojo.connect(this.depends,"change",this,this._onChange);
		this._onChange({});
	},
	_onChange: function(e){
		var set = false;
		if (!this.depends.hasAttribute("disabled"))
			if (this.depends.getAttribute("class").search(/(entry|select)/)>-1)
				set = Boolean(this.depends.value);
			else
				set = this.depends.checked;

		var nodes = dojo.query("*[name]", this.domNode);
		if (set)
			nodes.forEach(function(node){node.removeAttribute("disabled");});
		else
			nodes.forEach(function(node){node.setAttribute("disabled","disabled");});
	},
});
