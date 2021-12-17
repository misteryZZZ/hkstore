"use strict";

window.Vue  = require('vue');

window.queryString = require('query-string');
window.Push = require('push.js');

window.resizeAllGridItems = function(selector)
{
	$(selector).removeClass('ui').addClass('masonry');
  
	let grid = $(selector)[0];

	let rowHeight = parseInt(window.getComputedStyle(grid).getPropertyValue('grid-auto-rows'));
  let rowGap = parseInt(window.getComputedStyle(grid).getPropertyValue('grid-row-gap'));

  let allItems = grid.querySelectorAll(".masonry-item");

  for(let x=0; x<allItems.length; x++)
  {
    let rowSpan = Math.ceil((allItems[x].querySelector('.card').getBoundingClientRect().height+rowGap)/(rowHeight+rowGap));
  	allItems[x].style.gridRowEnd = "span "+rowSpan;
  }
}