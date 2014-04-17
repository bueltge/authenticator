/**
 * Enhance the Admin Interface
 *
 * @package Authenticator
 * @since 1.1.0
 */

( function ( $ ) {

	var AuthenticatorAdminUI = {

		init : function() {
			var tokenButton = $( authenticatorUI.tokenButtonMarkup );
			tokenButton.on(
				'click',
				AuthenticatorAdminUI.regenerateToken
			);

			$( '#' + authenticatorUI.tokenCheckboxWrapper )
				.replaceWith( tokenButton );

		},

		regenerateToken : function() {
			if ( confirm( authenticatorUI.confirmMessage ) ) {
				var data = {
					'action'              : authenticatorUI.actionHook,
					'nonce' : authenticatorUI.nonce
				};
				$.post(
					authenticatorUI.ajaxURL,
					data,
					function( data ) {
						AuthenticatorAdminUI.replaceNewToken( data );
					}
				);

			}
			return false;
		},

		replaceNewToken : function( newToken ) {
			$( '#' + authenticatorUI.tokenFieldId )
				.attr( 'value', newToken );

			$( '#' + authenticatorUI.exampleURLTokenId )
				.text( newToken );
		}
	}

	$( document ).ready( AuthenticatorAdminUI.init );

} )( jQuery );
