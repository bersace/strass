dojo.provide("wtk.form.model.Constraint");

dojo.declare("wtk.form.model.Constraint",null,{
	// Element représentant la valeur sur laquelle s'applique la
	// contrainte.
	domNode: null,

	constructor: function(node){
		this.domNode = node;
		this.init();
	},

	init: function(){
	},
});