dojo.provide("strass.install.Wizard");

dojo.require("wtk.Form");

dojo.declare("strass.install.Wizard",[wtk.Form],{
    startup: function () {
	this.inherited(arguments);
	dojo.connect(this.domNode, 'onsubmit', this, '_onSubmit');
    },
    _onSubmit: function (event) {
	// dojo.stopEvent(event);
	dojo.style(dojo.byId("wait"), "display", "block");
	dojo.style(this.domNode, "display", "none");
    }
});
