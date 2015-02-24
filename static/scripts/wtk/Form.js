dojo.provide("wtk.Form");
dojo.require("dijit._Widget");

dojo.declare("wtk.Form",[dijit._Widget],{
	// Contraintes du models
	constraints: [],
	postCreate: function(){
		form = this;
		dojo.query("div.constrained", this.domNode).forEach(function(node){
			var clazz = node.getAttribute("class");
			var match = clazz.match(/(wtk\.form\.model\.constraint\.[^ ]+)/);
			var clazz = match[1];
			dojo.require(clazz);
			clazz = eval(clazz);
			constraint = new clazz(node);
			form.constraints.push(constraint);
		});
	},
});
