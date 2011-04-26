/*
    This file is part of elad-gallery.

    elad-gallery is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    elad-gallery is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with elad-gallery.  If not, see <http://www.gnu.org/licenses/>.
*/
var inhibitHashChange=false, hashTimeout;
var dir="@@";
var ScriptURI;
var transition=false;
var thumbScale=1;
var rootDisableAjax=false; //If this is true, we are in the root folder, and can disable ajax folder loading.
var origTitle;
var loaded=false;
var me,len;
var big=false;
var transitionKillTimeout;
var throbContainer, throbObj;
var thumbOriginX=0, thumbOriginY=0;
var slideshowTimerID;
function ShowInfo(element, event) {
	if (event) {
		event.preventDefault();	
	}
	if (element==null || !loaded || element==undefined)
		return false;
	var info=document.getElementById("info");
	if (info!=undefined) {
		document.body.removeChild(info);
	}
	changeHash("img", element.id, true);
	info = document.createElement("div");
	info.style.opacity=0;
	info.id="info";
	document.body.appendChild(info);
	var content=document.createElement("div");
	content.id='content';
	fillContent(element, content);
	var toolbox=document.createElement("div");
	toolbox.className="toolbox";
	var close = document.createElement("span");
	close.className="close";
	close.innerHTML="X";
	closeInfo=function() {
		function func(e) {
			this.removeEventListener("transitionend", func, false);
			this.removeEventListener("webkitTransitionEnd", func, false);
			this.removeEventListener("oTransitionEnd", func, false);
			var oldnode=document.body.removeChild(info);
			info=null;
		};
		info.addEventListener("transitionend", func, false);
		info.addEventListener("webkitTransitionEnd", func, false);
		info.addEventListener("oTransitionEnd",  func, false);
		info.style.opacity=0;
		changeHash('img', "", true);
	};
	close.onclick=closeInfo;
	toolbox.appendChild(close);
	var slideshow=document.createElement("span");
	slideshow.className="slideshow";
	slideshow.innerHTML="▣";
	slideshow.onclick=function() {
		var oldnode=document.body.removeChild(info);
		info=null;
		changeHash('img', "", true);
		startSlideShow(element);	
	};
	toolbox.appendChild(slideshow);
	info.appendChild(toolbox);
	var images=document.getElementsByClassName('image');
	len=images.length;
	me=parseInt(element.id, 10);
	images=null;
	var transit=function(me, direction) {
		content=document.getElementById('content');
		var imagesList=document.getElementsByClassName('image');
		var newcontent=document.createElement("div");
		var image=imagesList[me];
		imagesList=null;
		content.id="oldcontent";
		if (direction=="next") {
			content.className="nextcontent";
			newcontent.className="transitionN";
			if (me<len-1)
				next.classList.add("trans");
		}else {
			content.className="prevcontent";
			newcontent.className="transition";
			//content.children[2].setAttribute("style", "max-width: 23%; overflow:visible;");
			if (me>0)
				prev.classList.add("trans");
		}
		newcontent.id='content';
		newcontent.style.display="none";
		transition=true;
		info.appendChild(newcontent);
		fillContent(image, newcontent);
		//newcontent.children[2].setAttribute("style", "max-height: 33%; max-width: 40%; overflow:visible;");
		//newcontent.firstChild.style.display="none";
		if (direction=="next")
			newcontent.children[1].setAttribute("style", "margin-right: -"+newcontent.children[1].offsetWidth+"px");	
		var test=content.addEventListener("transitionend", ContentTransitionComplete, false);
		content.addEventListener("webkitTransitionEnd", ContentTransitionComplete, false);
		content.addEventListener("oTransitionEnd",  ContentTransitionComplete, false);
		var testA=newcontent.addEventListener("transitionend", newContentTransitionComplete, false);
		newcontent.addEventListener("webkitTransitionEnd",newContentTransitionComplete, false);
		newcontent.addEventListener("oTransitionEnd",newContentTransitionComplete, false);
		var a=0;
		func=function() {
			if (transition==true && a==2) {
				ContentTransitionComplete(null);
				newContentTransitionComplete(null);
			} else {
				a++;
				transitionKillTimeout=setTimeout("func()", 1200);
			}
		};
		transitionKillTimeout=setTimeout("func()", 1200);
		changeHash("img", image.id, true);
	};
	var next=document.createElement("span");
	next.className="next";
	next.innerHTML="«";
	next.onclick=function() {
		me++;
		if(me==len-1) {
			var oldnode=info.removeChild(next);
		}
		if (me>0) {
			info.appendChild(prev);				
		}
		transit(me, "next");
	};
	if (me<len-1) {
		info.appendChild(next);
	}
	var prev=document.createElement("span");
	prev.className="prev";
	prev.innerHTML="»";
	prev.onclick=function() {
		me--;
		if(me==0) {
			var oldnode=info.removeChild(prev);
		}
		if (me<len-1) {
			info.appendChild(next);			
		}
		transit(me, "prev");
	};
	if (me>0) {
		info.appendChild(prev);	
	}
	document.onkeyup=function(e) {
		if (e.keyCode==27) {
			closeInfo();
		} else if (e.keyCode==70) {
			var content=document.getElementById('content');
			content.firstChild.onclick();		
		}
		if (thumbScale<=1) {
			if (e.keyCode==37 && me>0)
				prev.onclick();
			else if (e.keyCode==39 && me<len-1)
				next.onclick();
		}
	}
	info.appendChild(content);
	info.style.opacity=1;
	return false;
}
function ContentTransitionComplete(e) {
	clearTimeout(transitionKillTimeout);
	if (e!=null && (e.propertyName=="margin-right" || e.propertyName=="margin-left")) {
		var content=this;
		var info=content.parentNode;
		var newcontent=content.parentNode.lastChild;
		newcontent.style.display="block";
		if (content.firstChild.className=="showdata" || content.offsetHeight>=window.innerHeight-50) {
			newcontent.firstChild.onclick();
		}
		if (newcontent.firstChild.className=="hidedata" && newcontent.children[2].className=="big") {
			newcontent.children[2].className="";
		}
		var oldnode=info.removeChild(content);
		setTimeout(function() { newcontent.classList.remove('transition'); newcontent.classList.remove('transitionN'); }, 2); // newcontent.firstChild.setAttribute("style", "margin-right: 0"); 
		content.removeEventListener("transitionend", ContentTransitionComplete, false);
		content.removeEventListener("webkitTransitionEnd", ContentTransitionComplete, false);
		content.removeEventListener("oTransitionEnd", ContentTransitionComplete, false);
		content=null;
		transition=false;
	} else if (e==undefined) {
		var content=document.getElementById('oldcontent');
		var info=content.parentNode;
		var newcontent=content.parentNode.lastChild;
		newcontent.style.display="block";
		var oldnode=info.removeChild(content);
		setTimeout(function() { newcontent.classList.remove('transition'); newcontent.classList.remove('transitionN');  }, 2); //newcontent.firstChild.setAttribute("style", "margin-right: 0");
		content.removeEventListener("transitionend", ContentTransitionComplete, false);
		content.removeEventListener("webkitTransitionEnd", ContentTransitionComplete, false);
		content.removeEventListener("oTransitionEnd", ContentTransitionComplete, false);
		content=null;
	}
}
function newContentTransitionComplete(e) {
	if (e==null|| (e.propertyName=="margin-right" || e.propertyName=="margin-left")) {
		var newcontent=this;
		newcontent.removeEventListener("transitionend", newContentTransitionComplete, false);
		newcontent.removeEventListener("webkitTransitionEnd", newContentTransitionComplete, false);
		newcontent.removeEventListener("oTransitionEnd", newContentTransitionComplete, false);
		//this.children[2].setAttribute("style", ""); 
		var infochildren=newcontent.parentNode.children;
		if (me<len-1 )
			infochildren[2].classList.remove("trans");
		if (me>0)
			infochildren[1].classList.remove("trans");
	}
}
function fillContent(element, content) {
	thumbScale=1;
	var thumbStyle="";
	var link=document.createElement("a");
	link.href=element.firstChild.href;
	link.className="non"
	var data = document.createElement("div");
	data.className="data";
	data.innerHTML="טוען...";
	var tools=document.createElement("div");
	tools.id="tools";
	showdata= document.createElement("div");
	showdata.innerHTML="▢";
	showdata.className="hidedata";
	var showdataT, showdataKillT;
	content.appendChild(showdata);
	content.appendChild(data);
	content.appendChild(link);
	showdata.onclick=function() {
		if (data.className=="data" || (link.className=="big" && showdata.className!="showdata")) {
			big=true;
			data.className="dataHidden";
			showdata.className="showdata";
			var margin=data.offsetWidth+39;
			//if (margin<330)
				//margin=360;
			data.style.marginRight="-"+margin+"px"; //I wish i could do that with CSS only
			content.classList.add("bigger");
			function func(e) {
				clearTimeout(showdataKillT);
				data.removeEventListener("transitionend", func, false);
				data.removeEventListener("webkitTransitionEnd", func, false);
				data.removeEventListener("oTransitionEnd", func, false);
				if (content.offsetHeight>=window.innerHeight-50) {
					data.style.display="none";	
				}
				showdataT=setTimeout(function() { link.className="big"; }, 3);
			};
			data.addEventListener("transitionend", func, false);
			data.addEventListener("webkitTransitionEnd", func, false);
			data.addEventListener("oTransitionEnd",  func, false);
			showdataKillT=setTimeout("func()", 1200);

		}
		else {
			clearTimeout(showdataT);
			link.className="non";
			big=false;
			data.className="data";
			showdata.className="hidedata";
			data.style.marginRight="";
			content.classList.remove("bigger");
		}
	};
	var req;
	var thumb=document.createElement("img");
	if (window.XMLHttpRequest) { // Any modern browser
    	req = new XMLHttpRequest();
	} else if (window.ActiveXObject) { // IE
    	req = new ActiveXObject("Microsoft.XMLHTTP");
	}
	req.open('GET', ScriptURI+'?exif='+link.href, true);
	req.onreadystatechange = function (aEvt) {
		if (req.readyState == 4) {
			if(req.status == 200) {
				data.innerHTML="";
				tbl=document.createElement("table");
				data.appendChild(tbl);
				tbl.innerHTML=req.responseText;
				var filesize=tbl.firstChild.firstChild.firstChild.innerHTML;
				if (parseInt(filesize)/1024<1024)
					switchHQ(content, element, thumb);
				if (content.offsetHeight>=window.innerHeight-50 && data.className=="data") {
					showdata.onclick();
				}
			}
			else
				data.innerHTML="Error getting data";
		}
	};
	req.send(null);
	throbContainer=document.createElement("div");
	throbContainer.id="throbContainer";
	content.appendChild(throbContainer);
	throbObj=new Throbber(throbContainer);
	throbObj.throb();
	content.appendChild(tools);
	var btn90r=document.createElement("span");
	btn90r.innerHTML="↻";
	btn90r.className="rotateR";
	var thumbAngle=0;
	btn90r.onclick=function() {
		thumbAngle=thumbAngle+90;
		thumbRotate(thumb, thumbAngle);
	};
	var btn90l=document.createElement("span");
	btn90l.innerHTML="↺";
	btn90l.className="rotateL";
	btn90l.onclick=function() {
		thumbAngle=thumbAngle-90;
		thumbRotate(thumb, thumbAngle);
	};
	thumb.style.opacity="0";
	var hq=document.getElementById("hq");
	if (hq.checked==false) {
		thumb.src=ScriptURI+"?thumb="+element.firstChild.href;
	} else {
		switchHQ(content,element,thumb);
	}
	hq.addEventListener("click", function() {
		if(document.body.lastChild.id=="info") {
			if (hq.checked==false) {
				thumb.src=ScriptURI+"?thumb="+element.firstChild.href;
				thumb.style.resize="none";
			}
			else {
				switchHQ(content,element,thumb);		
			}
		}
	}, false);
	thumb.onload=function() {
		throbObj.stop();
		var oldnode=content.removeChild(throbContainer);
		thumb.style.opacity="1";
		tools.appendChild(btn90r);
		tools.appendChild(btn90l);
		thumb.className="norm";
		if (content.offsetHeight>=window.innerHeight-50 && data.className=="data") {
			showdata.onclick();
		}
	};
	link.appendChild(thumb);
}
function thumbRotate(thumb, angle) {
	if (thumb.style.MozTransform!=undefined) {
		thumb.style.MozTransformOrigin="center";
		thumb.style.MozTransform="rotate("+angle+"deg)";
	} else if (thumb.style.webkitTransform!=undefined) {
		thumb.style.webkitTransformOrigin="center";
		thumb.style.webkitTransform="rotate("+angle+"deg)";
	} else {
		thumb.setAttribute("style", "-o-transform: rotate("+angle+"deg);");
	}
} 
function switchHQ(content,element, thumb) {
		var info=document.lastChild;
		var tools=content.lastChild;
		if (!document.getElementById('throbContainer')) {
			content.appendChild(throbContainer);
		}
		if (!throbObj.throb) { 
			throbObj=new Throbber(throbContainer);
			throbObj.throb();
		} else if (!throbObj.timer) {
			throbObj.throb();
		}
		var link=content.children[2];
		thumb.src=element.firstChild.href;
		/*var zoomIn=document.createElement('span');
		zoomIn.className="zoom";
		zoomIn.id="zoomIn";
		zoomIn.innerHTML="+";
		zoomIn.onclick=function() {
			thumbScale=thumbScale+0.5;
			thumbUpdate(thumb, 0, 0);
		};
		var zoomOut=document.createElement('span');
		zoomOut.className="zoom";
		zoomOut.id="zoomOut";
		zoomOut.innerHTML="-";
		zoomOut.onclick=function() {
			thumbScale=thumbScale-0.5;
			thumbUpdate(thumb, 0, 0);
		};*/
		document.addEventListener("keydown", thumbZoomHandler, false);
		thumb.addEventListener("mousemove", thumbZoomHandler, false);
		/*tools.appendChild(zoomIn); //FIXME
		tools.appendChild(zoomOut);*/
}
function thumbZoomHandler(e) {
	var localThmubScale=thumbScale;
	var PrevThumbScale=localThmubScale;
	var localOriginX=thumbOriginX, localOriginY=thumbOriginY;
	var prevOriginX=localOriginX, prevOriginY=localOriginY;
	var thumb=this;
	if (e.type=="keydown") {
		thumb=document.getElementById('content').children[2].firstChild;
		switch (e.keyCode) {
			case 61:
				localThmubScale=localThmubScale+0.5;
				break;
			case 109:
				localThmubScale=localThmubScale-0.5;
				break;
			case 37:
				localOriginX=localOriginX-10;
				break;
			case 39:
				localOriginX=localOriginX+10;
				break;
			case 38:
				localOriginY=localOriginY-10;
				break;
			case 40:
				localOriginY=localOriginY+10;
				break;
			case 48:
				localThmubScale=1;
			break;
		}
	} else {	
		if (e.offsetX) { localOriginX = e.offsetX; localOriginY = e.offsetY; }
		else if (e.layerX) { localOriginX = e.layerX; localOriginY = e.layerY }
	}
	if (thumb.style.MozTransform!=undefined) {
		if (PrevThumbScale!=localThmubScale) {
			thumb.style.MozTransform="scale("+localThmubScale+")";
			thumbScale=localThmubScale;
		}
		if ((prevOriginX!=localOriginX || prevOriginY!=localOriginY) && localThmubScale>1) {
			thumb.style.MozTransformOrigin=localOriginX+"px "+localOriginY+"px ";
			thumbOriginY=localOriginY;
			thumbOriginX=localOriginX;
		}
	} else if (thumb.style.webkitTransform!=undefined) {
		if (PrevThumbScale!=localThmubScale) {
			thumb.style.webkitTransform="scale("+localThmubScale+")";
			thumbScale=localThmubScale;
		}
		if ((prevOriginX!=localOriginX || prevOriginY!=localOriginY) && localThmubScale>1) {
			thumb.style.webkitTransformOrigin=localOriginX+"px "+localOriginY+"px ";
			thumbOriginY=localOriginY;
			thumbOriginX=localOriginX;
		}
		} else {
			thumb.setAttribute("style", "-o-transform: scale("+localThmubScale+");-o-transform-origin: "+originX+"px "+originY+"px;");
			thumbScale=localThmubScale;
			thumbOriginY=localOriginY;
			thumbOriginX=localOriginX;
		}
}
/**
 * @constructor
 */
function Throbber(container) {
  this.options = {
    speedMS: 100,
    center: 4,
    thickness: 1,
    spokes:8,
    color: [0,0,0],
    style: "balls", //set to "balls" for a different style of throbber
	width: 128,
	height: 128
  };
  this.t = container;
  this.c = document.createElement('canvas');
  var self = this;
  var o = self.options;
  this.c.width = o.width;
  this.c.height = o.height;
  this.t.appendChild(this.c);
  this.throb = function() {
	if (this.c.getContext)
    	var ctx = this.c.getContext("2d");
	else
		return false;
    ctx.translate(this.c.width/2, this.c.height/2);
    var w = Math.floor(Math.min(this.c.width,this.c.height)/2);
    var draw = function() {
      ctx.clearRect(-self.c.width/2,-self.c.height/2,self.c.width,self.c.height)
      ctx.restore();
      ctx.shadowOffsetX = ctx.shadowOffsetY = 1;
        ctx.shadowBlur = 2;
        ctx.shadowColor = "rgba(220, 220, 220, 0.5)";
        for (var i = 0; i < o.spokes; i++) {
        r = 255-Math.floor((255-o.color[0]) / o.spokes * i);
        g = 255-Math.floor((255-o.color[1]) / o.spokes * i);
        b = 255-Math.floor((255-o.color[2]) / o.spokes * i);
          ctx.fillStyle = "rgb(" + r + "," + g + "," + b + ")";
        if(o.style == "balls") {
          ctx.beginPath();
          ctx.moveTo(w,0)
          ctx.arc(w-Math.floor(Math.PI*2*w/o.spokes/3),0,Math.floor(Math.PI*2*w/o.spokes/3),0,Math.PI*2,true);
          ctx.fill();
        } else { ctx.fillRect(o.center, -Math.floor(o.thickness/2), w-o.center, o.thickness); }
        ctx.rotate(Math.PI/(o.spokes/2))
        if(i == 0) { ctx.save(); }  
      }
    };
    draw();
    this.timer = setInterval(draw,this.options.speedMS);  
  };
  this.stop = function() {
	if (this.c.getContext) {
    	clearInterval(this.timer);
    	this.c.getContext("2d").clearRect(-this.c.width/2,-this.c.height/2,this.c.width,this.c.height);
	}
  };
};
/* Slideshow */
function startSlideShow(element) {
	document.body.style.overflow='hidden';
	document.getElementById('galleryContainer').style.display="none";
	var slideshowBG=document.createElement('div');
	slideshowBG.className="slideshow";
	var image=document.createElement('img');
	image.src=element.firstChild.href;
	var controls=document.createElement('div');
	controls.className="controls";
	var imgNum=document.createElement('span');
	imgNum.innerHTML=element.id;
	imgNum.className="imgNum";
	controls.appendChild(imgNum);
	var imgLen=document.createElement('span');
	imgLen.innerHTML="/"+len;
	imgLen.className="imgLen";
	controls.appendChild(imgLen);
	var playPause=document.createElement('span');
	playPause.className="Pause";
	playPause.innerHTML="▮▮";
	playPause.onclick=toggleSlideShow;
	controls.appendChild(playPause);
	var stop=document.createElement('span');
	stop.className="stop";
	stop.innerHTML="X";
	stop.onclick=closeSlideShow;
	controls.appendChild(stop);
	var info=document.createElement('span');
	info.className="slideStatus";
	controls.appendChild(info);
	slideshowBG.appendChild(controls);
	slideshowBG.appendChild(image);
	document.body.appendChild(slideshowBG);
	controls.scrollIntoView(false);
	slideshowTimerID=window.setTimeout(slideshowNext, 2000);
}
function closeSlideShow(e) {
	e.preventDefault();
	var slideshowBG=e.target.parentNode.parentNode;
	window.clearTimeout(slideshowTimerID);
	document.body.style.removeProperty('overflow');
	document.getElementById('galleryContainer').style.removeProperty('display');
	document.body.removeChild(slideshowBG);
}
function toggleSlideShow(e) {
	var playPause=e.target;
	e.preventDefault();
	if (playPause.className=="Pause") {
		playPause.innerHTML="▶";
		playPause.className="Play";
		window.clearTimeout(slideshowTimerID);
	} else if (playPause.className=="Play") {
		playPause.className="Pause";
		playPause.innerHTML="▮▮";
		slideshowTimerID=window.setTimeout(slideshowNext, 2000);
	}
}
function slideshowNext() {
	window.clearTimeout(slideshowTimerID);
	var slideshowBG=document.body.lastChild;
	var oldImage=slideshowBG.lastChild;
	var controls=slideshowBG.firstChild
	var imageNumSpan=controls.firstChild;
	var imageID=parseInt(imageNumSpan.innerHTML)+1;
	if (imageID>=len)
		imageID=0;
	var newImageSrc=document.getElementById(imageID).firstChild.href;
	var newImage=document.createElement('img');
	var info=controls.lastChild;
	newImage.src=newImageSrc;
	oldImage.style.zIndex=1;
	newImage.style.display="none";
	slideshowBG.appendChild(newImage);
	info.innerHTML="טוען...";
	imageNumSpan.innerHTML=imageID;
	newImage.className="fadeIn";
	var fadeEnd=function() {
		slideshowBG.removeChild(this);
		this.removeEventListener("transitionend", fadeEnd, false);
		this.removeEventListener("webkitTransitionEnd", fadeEnd, false);
		this.removeEventListener("oTransitionEnd",  fadeEnd, false);
		newImage.className="";
		if (controls.children[2].className!="Play") {
			slideshowTimerID=window.setTimeout(slideshowNext, 2000);
		}
	};
	newImage.onload=function() {
		info.innerHTML="";
		newImage.style.removeProperty('display');
		oldImage.className="fadeOut";
		oldImage.addEventListener("transitionend", fadeEnd, false);
		oldImage.addEventListener("webkitTransitionEnd", fadeEnd, false);
		oldImage.addEventListener("oTransitionEnd",  fadeEnd, false);
	}
}
/* Initilazation */
function init(Script) {
	ScriptURI=Script;
	origTitle=document.title;
	if (window.addEventListener)
		window.addEventListener("hashchange",hashChangeFunc,false);
	else
		window.hashchange=hashChangeFunc;
	loaded=true;
	hashChangeFunc();
	var hq=document.getElementById("hq");
	var hashimg=document.getElementById("hashimg");
	hq.addEventListener("click", checkboxClickHandler, false);
	hashimg.addEventListener("click", checkboxClickHandler, false);
	if (window.localStorage.getItem("hq")=="1")
		hq.checked=true;
	else
		hq.checked=false;
	if (window.localStorage.getItem("hashimg")=="1" || window.localStorage.getItem("hashimg")==undefined)
		hashimg.checked=true;
	else
		hashimg.checked=false;
	var dialog=document.getElementById("settings");
	var close = document.createElement("span");
	close.className="close";
	close.innerHTML="X";
	close.onclick=toggleSettingsDialog;
	dialog.appendChild(close);
}
function checkboxClickHandler(e) {
		var checkbox=e.target;
		if (checkbox.checked)
			window.localStorage.setItem(checkbox.id, "1");
		else
			window.localStorage.setItem(checkbox.id, "0");
}
function toggleSettingsDialog() {
	var dialog=document.getElementById("settings");
	if (dialog.style.display=="none") {
		dialog.style.display="block";
		setTimeout(function() { dialog.style.opacity=1; },5);
	} else {
		function func() {
			dialog.removeEventListener("transitionend", func, false);
			dialog.removeEventListener("webkitTransitionEnd", func, false);
			dialog.removeEventListener("oTransitionEnd", func, false);
			dialog.style.display="none";
		};
		dialog.addEventListener("transitionend", func, false);
		dialog.addEventListener("webkitTransitionEnd", func, false);
		dialog.addEventListener("oTransitionEnd",  func, false);
		dialog.style.opacity=0;
	}
}
function hashChangeFunc() {
		var parms=HashParmeterParser();
		if (!inhibitHashChange && !rootDisableAjax) {
			if (parms[0]!="" && parms[1]=="") {
				var image=document.getElementById(parms[0]);
				ShowInfo(image);
			}
			if (parms[1]!="" && parms[1]!=dir) {
				dir=parms[1];
				changeHash("img", "", true);
				var container=document.getElementById('galleryContainer');
				container.innerHTML="<span class='loading'>טוען...</span>";
				var throbContainer=document.createElement("div");
				throbContainer.id="throbContainer";
				container.appendChild(throbContainer);
				throbObj=new Throbber(throbContainer);
				throbObj.throb();
				var req;
				if (window.XMLHttpRequest) { // Any modern browser
    				req = new XMLHttpRequest();
				} else if (window.ActiveXObject) { // IE
    				req = new ActiveXObject("Microsoft.XMLHTTP");
				}
				req.open('GET', ScriptURI+'?ajaxDir='+parms[1], true);
				req.onreadystatechange = function (aEvt) {
					if (req.readyState == 4) {
						if(req.status == 200) {
							throbObj.stop();
							container.innerHTML=req.responseText;
							if (parms[0]!="") {
								var image=document.getElementById(parms[0]);
								if(ShowInfo(image)) {
									var info=document.getElementById('info');
									info.removeChild(info.firstChild);
									info.removeChild(info.children[1]);							
								}
							}
						}
						else
							container.innerHTML="Error getting data";
					}
				};
				req.send(" ");
			}
		}
		else if (rootDisableAjax && !inhibitHashChange) {
			rootDisableAjax=false;
			if (parms[1]!='.' || parms[1]!="") {
				hashChangeFunc();	
			}
		} else if (inhibitHashChange) {
			clearTimeout(hashTimeout);
			hashTimeout=undefined;
			inhibitHashChange=false;
		}
}
function HashParmeterParser() {
	var hash=window.location.hash.replace('#', '');
	var returnarray=new Array();	
	if (hash.indexOf('img')!=-1)
		returnarray[0]=hash.split('img!')[1].split('dir!')[0];
	else
		returnarray[0]="";
	if (hash.indexOf('dir')!=-1)
		returnarray[1]=hash.split('dir!')[1].split('img!')[0];
	else
		returnarray[1]="";
	return returnarray;
}
function changeHash(param, value, inhibit) {
	inhibitHashChange=inhibit;
	var hashArray=HashParmeterParser();
	if (param=="img" && document.getElementById("hashimg").checked==true) {
		if (hashArray[1]!="") {
			if (value!="")	{
				document.title=origTitle+": "+hashArray[1]+"/"+value;
				window.location.hash="img!"+value+"dir!"+hashArray[1];
			} else {
				document.title=origTitle+": "+hashArray[1];
				window.location.hash="dir!"+hashArray[1];
			}
		}
		else {
			if (value!="") {
				document.title=origTitle+": "+value;
				window.location.hash="img!"+value;
			} else {
				document.title=origTitle;
				window.location.hash="";
			}
		}
	}
	else if (param=="dir") {
		if (hashArray[0]!="" && document.getElementById("hashimg").checked==true) {
			document.title=origTitle+": "+value+"/"+hashArray[0];	
			window.location.hash="img!"+hashArray[0]+"dir!"+value;
		} else {
			document.title=origTitle+": "+value
			window.location.hash="dir!"+value;
		}
	}
	hashTimeout=setTimeout(function() { inhibitHashChange=false; }, 1000);
	return false;
}
function toggleKeyboardList() {
	var kbd=document.getElementById('keyboard');
	if (kbd.className=="hidden") {
		kbd.style.removeProperty('display');
		setTimeout(function () { kbd.classList.remove('hidden'); }, 100);
	} else {
		kbd.className="hidden"
		var kbdTransitionEnd=function() {
			kbd.style.display="none";
			kbd.removeEventListener("transitionend", kbdTransitionEnd, false);
			kbd.removeEventListener("webkitTransitionEnd", kbdTransitionEnd, false);
			kbd.removeEventListener("oTransitionEnd",  kbdTransitionEnd, false);
		}
		kbd.addEventListener("transitionend", kbdTransitionEnd, false);
		kbd.addEventListener("webkitTransitionEnd", kbdTransitionEnd, false);
		kbd.addEventListener("oTransitionEnd",  kbdTransitionEnd, false);
	}
}
