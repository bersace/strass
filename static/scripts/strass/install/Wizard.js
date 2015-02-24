dojo.provide("strass.install.Wizard");

dojo.require("wtk.Form");

dojo.declare("strass.install.Wizard",[wtk.Form],{
    startup: function () {
	this.inherited(arguments);
	var form = this;
	dojo.query("button[value=terminer]", this.domNode).forEach(function(node){
	    dojo.connect(node, 'onclick', form, '_onSubmit');
	});
    },
    _onSubmit: function (event) {
	// dojo.stopEvent(event);
	dojo.addClass(dojo.byId("wait"), "show");
	dojo.style(this.domNode, "display", "none");
    }
});
