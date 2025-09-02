import icons from "./shortcode/blockIcon";
import DynamicShortcodeInput from "./shortcode/dynamicShortcode";
import { escapeAttribute, escapeHTML } from "@wordpress/escape-html";
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { PanelBody, PanelRow } from '@wordpress/components';
import { Fragment, createElement } from '@wordpress/element';
import { InspectorControls } from '@wordpress/block-editor';
const ServerSideRender = wp.serverSideRender;
const el = createElement;

/**
 * Register: Woo Product Slider Gutenberg Block.
 */
registerBlockType("woo-product-slider-pro/shortcode", {
  title: escapeHTML( __("Woo Product Slider", "woo-product-slider") ),
  description: escapeHTML( __(
    "Use Woo Product Slider to insert a shortcode in your page.",
    "woo-product-slider"
  )),
  icon: icons.spwpspIcon,
  category: "common",
  supports: {
    html: true,
  },
  edit: (props) => {
    const { attributes, setAttributes } = props;
    var shortCodeList = sp_wps_load_script.shortCodeList;

    let scriptLoad = ( shortcodeId ) => {
      let sp_wpsp_BlockLoaded = false;
      let sp_wpsp_BlockLoadedInterval = setInterval(function () {
        let uniqId = jQuery("#sp-woo-product-slider-" + shortcodeId).parents().parents().attr('id');
        if (document.getElementById(uniqId)) {
          // Preloader JS
          jQuery('#wpspro-preloader-' + shortcodeId).css({ 'opacity' : 0, 'display' : 'none'});
          jQuery('#wpspro-preloader-' + shortcodeId).animate({ opacity: 1 }, 600);

          jQuery.getScript(sp_wps_load_script.loadScript);
          sp_wpsp_BlockLoaded = true;
          uniqId = '';
        }
        if (sp_wpsp_BlockLoaded) {
          clearInterval(sp_wpsp_BlockLoadedInterval);
        }
        if ( 0 == shortcodeId ) {
          clearInterval(sp_wpsp_BlockLoadedInterval);
        }
      }, 10);
    }

    let updateShortcode = ( updateShortcode ) => {
      setAttributes({shortcode: escapeAttribute( updateShortcode.target.value )});
    }

    let shortcodeUpdate = (e) => {
      updateShortcode(e);
      let shortcodeId = escapeAttribute( e.target.value );
      scriptLoad(shortcodeId);	
    }

    document.addEventListener('readystatechange', event => {
      if (event.target.readyState === "complete") {
        let shortcodeId = escapeAttribute( attributes.shortcode );
        scriptLoad(shortcodeId);
      }
    });

	if (jQuery('.wps-slider-section:not(.wps-slider-section-loaded)').length > 0) {
		let shortcodeId = escapeAttribute(attributes.shortcode);
		scriptLoad(shortcodeId);
	}

    if( attributes.preview ) {
      return (
        el('div', {className: 'sp_wpsp_shortcode_block_preview_image'},
          el('img', { src: escapeAttribute( sp_wps_load_script.path + "Admin/GutenbergBlock/assets/wps-block-preview.svg" )})
        )
      )
    }

    if ( shortCodeList.length === 0 ) {
      return (
        <Fragment>
          {
            el('div', {className: 'components-placeholder components-placeholder is-large'}, 
              el('div', {className: 'components-placeholder__label'}, 
              el('img', {className: 'block-editor-block-icon', src: escapeAttribute( sp_wps_load_script.path + '/Admin/GutenbergBlock/assets/wps-block.svg' )}),
                escapeHTML( __("Woo Product Slider", "woo-product-slider") )
              ),
              el('div', {className: 'components-placeholder__instructions'}, 
                escapeHTML( __("No shortcode found. ", "woo-product-slider") ),
                el('a', {href: escapeAttribute( sp_wps_load_script.url )}, 
                  escapeHTML( __("Create a shortcode now!", "woo-product-slider") )
                )
              )
            )
          }
        </Fragment>
      );
    }

    if ( ! attributes.shortcode || attributes.shortcode == 0 ) {
      return (
        <Fragment>
          <InspectorControls>
            <PanelBody title="Select a shortcode">
                <PanelRow>
                  <DynamicShortcodeInput
                    attributes={attributes}
                    shortCodeList={shortCodeList}
                    shortcodeUpdate={shortcodeUpdate}
                  />
                </PanelRow>
            </PanelBody>
          </InspectorControls>
          {
            el('div', {className: 'components-placeholder components-placeholder is-large'}, 
              el('div', {className: 'components-placeholder__label'},
              el('img', {className: 'block-editor-block-icon', src: escapeAttribute( sp_wps_load_script.path + '/Admin/GutenbergBlock/assets/wps-block.svg' )}),
                escapeHTML( __("Woo Product Slider", "woo-product-slider") )
              ),
              el('div', {className: 'components-placeholder__instructions'}, escapeHTML( __("Select a shortcode", "woo-product-slider") ) ),
              <DynamicShortcodeInput
                attributes={attributes}
                shortCodeList={shortCodeList}
                shortcodeUpdate={shortcodeUpdate}
              />
            )
          }
        </Fragment>
      );
    }

    return (
      <Fragment>
        <InspectorControls>
            <PanelBody title="Select a shortcode">
                <PanelRow>
                  <DynamicShortcodeInput
                    attributes={attributes}
                    shortCodeList={shortCodeList}
                    shortcodeUpdate={shortcodeUpdate}
                  />
                </PanelRow>
            </PanelBody>
        </InspectorControls>
        <ServerSideRender block="woo-product-slider-pro/shortcode" attributes={attributes} />
      </Fragment>
    );
  },
  save() {
    // Rendering in PHP
    return null;
  },
});
