/*
elad-gallery is a free, open sourced, lightweight and fast gallery that utilizes PHP, CSS3 and HTML5.
	Copyright (C) 2010-2012  Elad Alfassa <elad@fedoraproject.org>

	This file is part of elad-gallery.

	elad-gallery is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	elad-gallery is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with elad-gallery. If not, see <http://www.gnu.org/licenses/>.
*/
var local_config=config;
function showDirInfo(element, event) {
	if (element==null || !loaded || element==undefined || local_config.showDirInfoTooltip == false)
		return false;
	var dir_info=document.querySelector("div.dir_info[data-for='"+element.id+"']");
	if (!dir_info.classList.contains("visible")) {
		moveDirInfo(event);
		dir_info.classList.add("visible");
	}
	element.addEventListener('mouseout',hideDirInfo,false);
	element.addEventListener('mousemove',moveDirInfo,false);
}
function hideDirInfo(event) {
	element=event.originalTarget;
	if (!element.classList.contains('folder'))
		element=element.parentNode;
	var dir_info=document.querySelector("div.dir_info[data-for='"+element.id+"']");
	if (dir_info)
		dir_info.classList.remove("visible");
}
function moveDirInfo(event) {
	element=event.currentTarget;
	var dir_info=document.querySelector("div.dir_info[data-for='"+element.id+"']");
	if(parseInt(dir_info.style.left)!=event.clientX)
		dir_info.style.left=event.clientX+"px";
	if(parseInt(element.style.top)!=event.clientY)
		dir_info.style.top=event.clientY+"px";
}
