dojo.provide("wtk.fx.Resize");
dojo.require("dojo.fx");

wtk.fx.Resize = function(/* Object */args){
	// summary: Create an animation that will size a node
	// description:
	//	Returns an animation that will size "node" 
	//	defined in args Object to
	//	a width and height defined by (args.width, args.height), 
	//	supporting an optional method: chain||combine mixin
	//	(defaults to chain).
	//
	//	
	// example:
	// |	// size #myNode to 400px x 200px over 1 second
	// |	wtk.fx.Resize({ node:'myNode',
	// |		duration: 1000,
	// |		width: 400,
	// |		height: 200,
	// |		method: "chain"
	// |	}).play();
	//
	var node = (args.node = dojo.byId(args.node));

	var method = args.method || "chain"; 
	if(!args.duration){ args.duration = 500; } // default duration needed
	if (method=="chain"){ args.duration = Math.floor(args.duration/2); } 
	
	var width, height = null;

	var init = (function(n){
		return function(){
			var cs = dojo.getComputedStyle(n);
			width = parseInt(cs.width);
			height = parseInt(cs.height);
		}
	})(node);
	init(); 

	var anim1 = dojo.animateProperty(dojo.mixin({
		properties: {
			height: { start: height, end: args.height || 0, unit:"px" },
		}
	}, args));
	var anim2 = dojo.animateProperty(dojo.mixin({
		properties: {
			width: { start: width, end: args.width || 0, unit:"px" },
		}
	}, args));

	var anim = dojo.fx[((args.method == "combine") ? "combine" : "chain")]([anim1,anim2]);
	dojo.connect(anim, "beforeBegin", anim, init);
	return anim; // dojo._Animation
};
