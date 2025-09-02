import { escapeAttribute } from "@wordpress/escape-html";
const el = wp.element.createElement;
const icons = {};
icons.spwpspIcon = el('img', {src: escapeAttribute( sp_wps_load_script.path + '/Admin/GutenbergBlock/assets/wps-block.svg' )})
export default icons;