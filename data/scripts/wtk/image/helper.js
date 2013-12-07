dojo.addOnLoad(function(){alert("lol");});

function wtkImageHelperInit()
{
    if (!document.all) {
        var images = wtkGetElementsByClass(document, new Array('-helper'));
        var i;
        for(i = 0; i < images.length; i++) {
            wtkImageHelperSwitch(images[i]);
            images[i].setAttribute("onclick", "wtkImageHelperSwitch(this)");
            images[i].setAttribute("ondblclick", "wtkImageHelperOpen(this)");
	    images[i].setAttribute("title",
				   "cliquer pour agrandir.\n"+
				   "double-cliquer pour ouvrir dans une nouvelle fenêtre");
        }
    }
}

function wtkImageHelperOpen(img)
{
    window.open(img.src, "image-helper-popup",
		"menubar=0,toolbar=0,location=0,directory=0,status=0,"+
		"resizable=1,scrollbars=1");
    return false;
}

function wtkImageHelperSwitch(img)
{
    var wh = document.defaultView.innerHeight - 128;
    var ww = document.defaultView.innerWidth - 128;
    var r;
    var w;
    var h;
    var ih = img.getAttribute("height");
    var iw = img.getAttribute("width");
    var landscape = ih < iw;
    if (img.resized) {
	    if (iw < ww) {
	        w = iw+"px";
	        h = ih+"px";
	    }
	    else {
	        w = "100%";	
	        h = "auto";
	    }
	    img.resized = false;
    }
    else {
	    if (landscape) {
	        r = Math.min(iw, ww)/iw;
	        if (ih*r > wh) {
		        r = Math.min(ih, wh)/ih;
	        }
	    }
	    else {
	        r = Math.min(ih, wh)/ih;
	        if (iw*r > ww) {
		        r = Math.min(iw, ww)/iw;
	        }
	    }
	    h = parseInt(ih*r)+"px";
	    w = parseInt(iw*r)+"px";
	    img.resized = true;
    }
    img.style.width = w;
    img.style.height = h;
}

