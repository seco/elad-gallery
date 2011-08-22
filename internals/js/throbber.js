//Throbber code by Alex Gawley, http://ablog.gawley.org/2009/05/randomness-throbbers-and-tag.html
 
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
