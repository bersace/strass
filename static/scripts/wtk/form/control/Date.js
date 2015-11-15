dojo.provide("wtk.form.control.Date");
dojo.require("dijit._Widget");
dojo.require("dijit._TimePicker");
dojo.require("dojox.widget.Calendar");

// Gère un widget composé de plusieurs champs pour chaque partie d'une
// date : année, mois, jour, heure et minute. Utilise
// dojox.widget.Calendar et dijit._TimePicker pour faciliter la
// sélection de la date par l'utilisateur.
dojo.declare("wtk.form.control.Date",[dijit._Widget],{
    _dateParts: ["year","month","day"],
    _timeParts: ["hour","min","second"],

    _firstInputs: null,
    _popupWidths: null,
    _inputs: null,

    _target: null,
    _targetPart: null,
    _targetClass: null,
    _popupClass: null,
    _popup: null,
    _opened: false,
    _value: null,

    postCreate: function(){
	var query = "#"+this.domNode.getAttribute("id")+" span input";
	this._inputs		= {};
	this._firstInputs	= {};
	this._popupWidths	= {};
	this._value		= new Date();

	dojo.query(query).forEach(function(node){
	    var part = this._getPartName(node);
	    var type = this._getInputClass(node);

	    if (!this._firstInputs[type])
		this._firstInputs[type] = node

	    var cf = dojo.coords(this._firstInputs[type]);
	    var cn = dojo.coords(node);
	    this._popupWidths[type] = cn.l+cn.w-cf.l;

	    this._inputs[part] = node;
	    dojo.connect(node, "onclick", this, this._onClick);
	    dojo.connect(node, "onclick", this, this._onFieldChange);
            this._onFieldChange({"target": node});
	}, this);
	this._value.setSeconds(0);
    },

    _onFieldChange: function (e) {
        var node = e.target;
	var part = this._getPartName(node);
	var val = node.value;

	if (part == "year")
	    this._value.setYear(val);
	else if (part == "month")
	    this._value.setMonth(val-1);
	else if (part == "day")
	    this._value.setDate(val);
	else if (part == "hour")
	    this._value.setHours(val);
	else if (part == "min")
	    this._value.setMinutes(val);

        if (this._popup) {
            this._popup.setValue(new Date(this._value));
        }
    },

    _onClick: function(e){
        console.log("wtk.form.controle.Date", "Clic");
	this._open(e.target);
    },

    onBlur: function(e){
        console.log("wtk.form.controle.Date", "Blur");
	this._close();
    },

    _open: function(target){
        // Détecter si c'est le popup calendrier ou horlge.
	this._target = target;
	this._targetPart = this._getPartName(this._target);
	var targetClass = this._getInputClass(this._target);
	if (targetClass != this._targetClass) {
            console.log("wtk.form.controle.Date", "Changement de popup");
	    this._close();
	    this._targetClass = targetClass;
            if (this._popup) {
                this._popup.destroy();
                this._popup = null;
            }
	}

	if (!this._popup) {
            console.log("wtk.form.controle.Date", "Création de popup");
	    this._popup = this._getPopup();
        }

	if (this._opened) {
            console.log("wtk.form.controle.Date", "Popup déjà ouverte");
	    return;
        }

        console.log("wtk.form.controle.Date", "Ouverture du popup", {
            "input": this._firstInputs[this._targetClass]});
	dojo.marginBox(this._popup.domNode,{ w:this._popupWidths[this._targetClass] });
	this._opened = true;
	dijit.popup.open({
	    parent: this,
	    popup: this._popup,
	    around: this._firstInputs[this._targetClass],
	    onCancel: dojo.hitch(this, this._close),
	});
    },

    _cancel: function(){
	console.log("wtk.form.controle.Date", "cancel");
    },

    _close: function(){
	if (this._opened) {
            console.log("wtk.form.controle.Date", "Fermeture du popup");
	    dijit.popup.close(this._popup);
	    this._opened = false;
	}
        else {
            console.log("wtk.form.controle.Date", "Déjà fermé");
        }
    },

    _set: function(part, val){
	if (part == "year")
	    this._value.setYear(val);
	else if (part == "month")
	    this._value.setMonth(val++);
	else if (part == "day")
	    this._value.setDate(val);
	else if (part == "hour")
	    this._value.setHours(val);
	else if (part == "min")
	    this._value.setMinutes(val);

	this._inputs[part].value = val;
	this._inputs[part].blur();
    },

    _select: function(value){
	if (this._targetClass == "date") {
	    this._set("year",value.getFullYear());
	    this._set("month",value.getMonth());
	    this._set("day", value.getDate());
	}
	else if (this._targetClass == "time") {
	    this._set("hour", value.getHours());
	    this._set("min", value.getMinutes());
	}

	this._close();
    },

    _getPopup: function(){
	var clazz = this._targetClass == "date" ? "dojox.widget.Calendar" : this._targetClass == "time" ? "dijit._TimePicker" : null;
	clazz = dojo.getObject(clazz, false);
	var popup = new clazz({
	    value: this._value,
	    onValueSelected: dojo.hitch(this, this._select),
	});

	// configurer le sélecteur d'heure.
	if (this._targetClass == "time") {
	    popup.visibleRange = "T03:00:00";
	    popup.constraints.timePattern = "HH:mm";
	    popup.constraints.selector = this._targetClass;
	    popup.attr("value", this._value);
	}
	dojo.connect(popup, "blur", this, this._close);
	return popup;
    },

    _getFirstInput: function(node){
	var type = this._getInputClass(node);
	return this._firstInputs[type] || node;
    },

    _getPartName: function(node){
	return node.getAttribute("name").match(/\[(\w+)\]$/)[1];
    },

    _getInputClass: function(node){
	var partName = this._getPartName(node);

	if (dojo.indexOf(this._dateParts, partName) != -1)
	    return "date";
	else if (dojo.indexOf(this._timeParts, partName) != -1)
	    return "time";
	else
	    return null;
    },
});
