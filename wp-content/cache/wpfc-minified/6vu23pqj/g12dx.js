// source --> https://new.trabajosjovenes.cl/wp-content/plugins/woocommerce/assets/js/frontend/add-to-cart.min.js?ver=7.5.1 
jQuery(function(o){if("undefined"==typeof wc_add_to_cart_params)return!1;var t=function(){this.requests=[],this.addRequest=this.addRequest.bind(this),this.run=this.run.bind(this),o(document.body).on("click",".add_to_cart_button",{addToCartHandler:this},this.onAddToCart).on("click",".remove_from_cart_button",{addToCartHandler:this},this.onRemoveFromCart).on("added_to_cart",this.updateButton).on("ajax_request_not_sent.adding_to_cart",this.updateButton).on("added_to_cart removed_from_cart",{addToCartHandler:this},this.updateFragments)};t.prototype.addRequest=function(t){this.requests.push(t),1===this.requests.length&&this.run()},t.prototype.run=function(){var t=this,a=t.requests[0].complete;t.requests[0].complete=function(){"function"==typeof a&&a(),t.requests.shift(),0<t.requests.length&&t.run()};const e=this.requests[0];window.fetch(e.url,{method:e.type,headers:{"Content-Type":"application/x-www-form-urlencoded; charset=UTF-8"},body:e.data}).then(t=>{if(t.ok)return t.json();throw new Error(t.statusText)}).then(e.success)["catch"](t=>e.error&&e.error())["finally"](()=>e.complete&&e.complete())},t.prototype.onAddToCart=function(t){var e,a=o(this);if(a.is(".ajax_add_to_cart"))return!a.attr("data-product_id")||(t.preventDefault(),a.removeClass("added"),a.addClass("loading"),!1===o(document.body).triggerHandler("should_send_ajax_request.adding_to_cart",[a])?(o(document.body).trigger("ajax_request_not_sent.adding_to_cart",[!1,!1,a]),!0):(e={},o.each(a.data(),function(t,a){e[t]=a}),o.each(a[0].dataset,function(t,a){e[t]=a}),o(document.body).trigger("adding_to_cart",[a,e]),void t.data.addToCartHandler.addRequest({type:"POST",url:wc_add_to_cart_params.wc_ajax_url.toString().replace("%%endpoint%%","add_to_cart"),data:o.param(e),success:function(t){t&&(t.error&&t.product_url?window.location=t.product_url:"yes"===wc_add_to_cart_params.cart_redirect_after_add?window.location=wc_add_to_cart_params.cart_url:o(document.body).trigger("added_to_cart",[t.fragments,t.cart_hash,a]))},dataType:"json"})))},t.prototype.onRemoveFromCart=function(t){var a=o(this),e=a.closest(".woocommerce-mini-cart-item");t.preventDefault(),e.block({message:null,overlayCSS:{opacity:.6}}),t.data.addToCartHandler.addRequest({type:"POST",url:wc_add_to_cart_params.wc_ajax_url.toString().replace("%%endpoint%%","remove_from_cart"),data:new URLSearchParams({cart_item_key:a.data("cart_item_key")}).toString(),success:function(t){t&&t.fragments?o(document.body).trigger("removed_from_cart",[t.fragments,t.cart_hash,a]):window.location=a.attr("href")},error:function(){window.location=a.attr("href")},dataType:"json"})},t.prototype.updateButton=function(t,a,e,r){(r=void 0!==r&&r)&&(r.removeClass("loading"),a&&r.addClass("added"),a&&!wc_add_to_cart_params.is_cart&&0===r.parent().find(".added_to_cart").length&&r.after('<a href="'+wc_add_to_cart_params.cart_url+'" class="added_to_cart wc-forward" title="'+wc_add_to_cart_params.i18n_view_cart+'">'+wc_add_to_cart_params.i18n_view_cart+"</a>"),o(document.body).trigger("wc_cart_button_updated",[r]))},t.prototype.updateFragments=function(t,a){a&&(o.each(a,function(t){o(t).addClass("updating").fadeTo("400","0.6").block({message:null,overlayCSS:{opacity:.6}})}),o.each(a,function(t,a){o(t).replaceWith(a),o(t).stop(!0).css("opacity","1").unblock()}),o(document.body).trigger("wc_fragments_loaded"))},new t});
// source --> https://new.trabajosjovenes.cl/wp-content/plugins/js_composer/assets/js/vendors/woocommerce-add-to-cart.js?ver=6.10.0 
(function ( $ ) {
	'use strict';

	$( document ).ready( function () {
		$( 'body' ).on( 'adding_to_cart', function ( event, $button, data ) {
			if ( $button && $button.hasClass( 'vc_gitem-link' ) ) {
				$button
					.addClass( 'vc-gitem-add-to-cart-loading-btn' )
					.parents( '.vc_grid-item-mini' )
					.addClass( 'vc-woocommerce-add-to-cart-loading' )
					.append( $( '<div class="vc_wc-load-add-to-loader-wrapper"><div class="vc_wc-load-add-to-loader"></div></div>' ) );
			}
		} ).on( 'added_to_cart', function ( event, fragments, cart_hash, $button ) {
			if ( 'undefined' === typeof ($button) ) {
				$button = $( '.vc-gitem-add-to-cart-loading-btn' );
			}
			if ( $button && $button.hasClass( 'vc_gitem-link' ) ) {
				$button
					.removeClass( 'vc-gitem-add-to-cart-loading-btn' )
					.parents( '.vc_grid-item-mini' )
					.removeClass( 'vc-woocommerce-add-to-cart-loading' )
					.find( '.vc_wc-load-add-to-loader-wrapper' ).remove();
			}
		} );
	} );
})( window.jQuery );