/*
elad-gallery is a free, open sourced, lightweight and fast gallery that utilizes PHP, CSS3 and HTML5.
	Copyright (C) 2010-2011  Elad Alfassa <elad@fedoraproject.org>

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

This page contains some code snippsets from html5rocks.com
*/

var retryCount=0;

// Check if a new cache is available on page load.
window.addEventListener('load', function(e) {
	window.applicationCache.addEventListener('updateready', function(e) {
		handleCacheEvent();
		if (window.applicationCache.status == window.applicationCache.UPDATEREADY) {
			window.applicationCache.swapCache();
			window.location.reload();
		}
	}, false);
	if (navigator.onLine) {
	  online();
	} else {
	  offline();
	}
	handleCacheEvent();

}, false);
window.addEventListener("offline", offline, false);
window.addEventListener("online", online, false);

window.applicationCache.addEventListener('cached', handleCacheEvent, false);
window.applicationCache.addEventListener('checking', handleCacheEvent, false);
window.applicationCache.addEventListener('downloading', handleCacheEvent, false);
window.applicationCache.addEventListener('obsolete', handleCacheEvent, false);
window.applicationCache.addEventListener('error', handleCacheErr, false);
window.applicationCache.addEventListener('noupdate', handleCacheEvent, false);
window.applicationCache.addEventListener('progress', handleCacheEvent, false);


function offline() {
	status=document.getElementById('status');
	status.classList.add('offline');
	status.innerHTML="Offline";
	handleCacheEvent();
}
function online() {
	handleCacheEvent();
	status=document.getElementById('status');
	status.classList.remove('offline');
	status.innerHTML="Online";
	updateCache();
}
function handleCacheEvent() {
	var appCache = window.applicationCache;
	status=document.getElementById('cache_status');
	switch (appCache.status) {
		case appCache.UNCACHED: // UNCACHED == 0
			status.innerHTML='Cache: Uncached';
		break;

		case appCache.IDLE: // IDLE == 1
			status.innerHTML='Cache: OK'; 
		break;

		case appCache.CHECKING: // CHECKING == 2
			if (navigator.onLine) {
				status.innerHTML='Cache: Checking';
			}
		break;
		case appCache.DOWNLOADING: // DOWNLOADING == 3
			status.innerHTML='Cache: Downloading';
		break;

		case appCache.UPDATEREADY:  // UPDATEREADY == 4
			status.innerHTML='Cache: Update ready';
		break;

		case appCache.OBSOLETE: // OBSOLETE == 5
			status.innerHTML='Cache: Obsolete';
		break;

		default:
			status.innerHTML='Cache: Unkown status';
		break;
	}
}
function handleCacheErr() {
	status=document.getElementById('cache_status');
	if (retryCount<5 && navigator.onLine) {
		handleCacheEvent();
		window.setTimeout(updateCache, 5000);
		retryCount++;
	} else {
		status.innerHTML='';
	}
}
function updateCache() {
	if (navigator.onLine)
	  	window.applicationCache.update();
	handleCacheEvent();
}
