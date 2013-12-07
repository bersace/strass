dojo.provide("wtk.form.control.TableRow");
dojo.require("dijit._Widget");
dojo.require("dojo.dnd.move");
dojo.require("dojo.fx");
dojo.require("wtk.fx.Resize");

// workaround dojo.parser bug.
var wtkFomControlTableRowCtr = 0;
wtkFomControlTableRowUniqueId = function(){
	return "wtk_form_control_TableRow_new"+(++wtkFomControlTableRowCtr);
}

dojo.declare("wtk.form.control.TableRow",[dijit._Widget],{
	vanillaClone:	null,
	moveable:	null,
	table:		null,
	postscript: function(params, srcNodeRef){
		this.vanillaClone = srcNodeRef.cloneNode(true);
		this.table = dijit.registry.byId(srcNodeRef.parentNode.parentNode.getAttribute("widgetId"));
		this.inherited("postscript", arguments);
	},

	postCreate: function(){
		if (this.table.reorderable) {
			this.table.source.insertNodes(false,[this.domNode],true,this.domNode);
		}

		if (this.table.extensible) {
			this._buildButton("+","add","first","_add");
			this._buildButton("×","remove","last","_remove");
		}

		if (this.table.reorderable && this.table.extensible) {
			this._changed();
			dojo.connect(this.domNode.parentNode, "DOMNodeInserted", this, "_changed");
			dojo.connect(this.domNode.parentNode, "DOMNodeRemoved", this, "_changed");
		}
	},

	_buildButton: function(label,cssClass,position,callback){
		var td = document.createElement("td");
		var b = document.createElement("button");
		b.setAttribute("type", "button");
		b.setAttribute("class", cssClass)
		b.innerHTML = label;
		td.appendChild(b);
		dojo.place(td, this.domNode, position);
		dojo.connect(b, "click", this, callback);
	},

	// callbacks

	_changed: function(e){
		// ne tenir compte que des changement relatif au tbody lui-même
		if (e && !e.relatedNode.nodeName.match(/(tbody|TBODY)/))
			return;

		// décrémenter le rowIndex des lignes suivants la ligne supprimée
		var ri = this.domNode.sectionRowIndex;
		var removing = e && e.type == "DOMNodeRemoved";
		if (removing && this.domNode.sectionRowIndex > e.target.sectionRowIndex)
			ri--;

		// class de ligne
		var clazz = ri%2 ? "odd" : "even";
		dojo.removeClass(this.domNode,"even");
		dojo.removeClass(this.domNode,"odd");
		dojo.addClass(this.domNode,clazz);

		// numérotation des champs
		var nodes = dojo.query("*[name]", this.domNode);
		i = this.domNode.rowIndex-1;
		dojo.forEach(nodes,function(node){
			var name = node.getAttribute("name");
			name = name.replace(/\[\d+\]\[(.*)\]/, "["+i+"][\$1]");
			node.setAttribute("name", name);
		});
		if(removing && this.table.domNode.tBodies[0].rows.length == 2)
			dojo.query("button.remove",this.domNode)[0].setAttribute("disabled","disabled");
		else
			dojo.query("button.remove",this.domNode)[0].removeAttribute("disabled");
	},

	_add: function(){
		var tr = this.vanillaClone.cloneNode(true);
		var id = wtkFomControlTableRowUniqueId();
		tr.setAttribute("id", id);
		tr.style.opacity = "0";

 		var tc = dojo.coords(this.domNode);
		var ftr = document.createElement("tr");
		var td = document.createElement("td");
		ftr.appendChild(td);
		dojo.place(ftr,this.domNode,"after");

		var anim1 = dojo.animateProperty({
			node: td,
			duration: 200,
			properties: { height: { start: 0, end: tc.h}}
		});
		var ctx = {section: this.domNode.parentNode,
			   id: id,
			   from: ftr,
			   to: tr,
			   ref: this.domNode}
		dojo.connect(anim1,"onEnd",ctx,function(){
			this.section.replaceChild(this.to,this.from);
			dojo.parser.instantiate([this.to]);
		});
		var anim2 = dojo.fadeIn({
			node: tr,
			duration: 200
		});
		var anim = dojo.fx.chain([anim1,anim2]);
		anim.play();
	},
	_remove: function(){
		dojo.query("button",this.domNode).forEach(function(node){node.setAttribute("disabled","disabled")});
 		tc = dojo.coords(this.domNode);
		anim1 = dojo.fadeOut({
			node: this.domNode,
			duration: 200
		});
		var tr = document.createElement("tr");
		var td = document.createElement("td");
		tr.appendChild(td);

		anim2 = dojo.animateProperty({
			node: td,
			duration: 200,
			properties: { height: { start: tc.h, end: 0}}
		});
		var ctx = {section: this.domNode.parentNode,
			   from: this.domNode,
			   to: tr}
		dojo.connect(anim1,"onEnd",ctx,function(){
			this.section.replaceChild(this.to,this.from);
		});
		dojo.connect(anim2,"onEnd",ctx.to,function(){
			this.parentNode.removeChild(this);
		});
		anim = dojo.fx.chain([anim1,anim2]);
		anim.play();
	},
});
