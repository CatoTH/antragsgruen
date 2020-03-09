/**
 * @license Copyright (c) 2003-2019, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */

( function() {

    function getSelectedLis( selection ) {
        if ( !selection ) {
			return [];
		}

        var ranges = selection.getRanges();
        var retval = [];
        for ( var i = 0; i < ranges.length; i++ ) {
            var range = ranges[i];

            if (!range.collapsed) {
                console.warn('not sure if this works with collaped === false');
            }

            var startNode = range.getCommonAncestor(),
                nearestLi = startNode.getAscendant({li: 1}, true);

            if (nearestLi) {
                retval.push(nearestLi);
            }
        }

        return retval;
    }

    function insertLi( selectionOrLi, insertBefore ) {
        var firstLi = selectionOrLi[ 0 ],
			list = firstLi.getAscendant( {ul: 1, ol: 1} ),
			doc = firstLi.getDocument();

        for (var i = 0; i < selectionOrLi.length; i++) {
            var el = new CKEDITOR.dom.element( selectionOrLi[i] );
            var newLi = el.$.clone();
            newLi.appendBogus();
            insertBefore ? newLi.insertBefore( selectionOrLi[i] ) : newLi.insertAfter( selectionOrLi[i] );
        }
    }

	CKEDITOR.plugins.listitemstyle = {
		requires: 'dialog,contextmenu',
		lang: 'de,en',
		init: function( editor ) {
			if ( editor.blockless )
				return;

			var def, cmd;

			def = new CKEDITOR.dialogCommand( 'numberedListStyle', {
				requiredContent: 'ol',
				allowedContent: 'ol{list-style-type}[start]; li{list-style-type}[value]',
				contentTransformations: [
					[ 'ol: listTypeToStyle' ]
				]
			} );
			cmd = editor.addCommand( 'numberedListStyle', def );
			editor.addFeature( cmd );
			CKEDITOR.dialog.add( 'numberedListStyle', this.path + 'dialogs/liststyle.js' );

			def = new CKEDITOR.dialogCommand( 'numberedListItemStyle', {
				requiredContent: 'li',
				allowedContent: 'li[value]',
				contentTransformations: [
					[ 'li: listTypeToStyle' ]
				]
			} );
			cmd = editor.addCommand( 'numberedListItemStyle', def );
			editor.addFeature( cmd );
			CKEDITOR.dialog.add( 'numberedListItemStyle', this.path + 'dialogs/listitemstyle.js' );

			cmd = editor.addCommand( 'liInsertBefore', {
				requiredContent: 'li',
				exec: function( editor ) {
					var selection = editor.getSelection(),
						lis = getSelectedLis( selection );

					insertLi( lis, true );
				}
			} );
			editor.addFeature( cmd );

			cmd = editor.addCommand( 'liInsertAfter', {
				requiredContent: 'li',
				exec: function( editor ) {
					var selection = editor.getSelection(),
						lis = getSelectedLis( selection );

					insertLi( lis, false );
				}
			} );
			editor.addFeature( cmd );

			//Register map group;
			editor.addMenuGroup( 'list', 108 );

			editor.addMenuItems( {
				numberedlist: {
					label: editor.lang.listitemstyle.numberedTitle,
					group: 'list',
					command: 'numberedListStyle'
				},
                numberedlistItem: {
					label: editor.lang.listitemstyle.numberedItemTitle,
					group: 'list',
					command: 'numberedListItemStyle'
				},
                liInsertBefore: {
					label: editor.lang.listitemstyle.liInsertBefore,
					group: 'list',
					command: 'liInsertBefore'
				},
                liInsertAfter: {
					label: editor.lang.listitemstyle.liInsertAfter,
					group: 'list',
					command: 'liInsertAfter'
				}
			} );

			editor.contextMenu.addListener( function( element ) {
				if ( !element || element.isReadOnly() )
					return null;

				while ( element ) {
				    var name = element.getName();
					if ( name === 'ol' )
						return { numberedlist: CKEDITOR.TRISTATE_OFF, liInsertBefore: CKEDITOR.TRISTATE_OFF, liInsertAfter: CKEDITOR.TRISTATE_OFF };
					else if ( name === 'li' && element.getParent().getName() === 'ol' ) {
						return { numberedlist: CKEDITOR.TRISTATE_OFF, numberedlistItem: CKEDITOR.TRISTATE_OFF, liInsertBefore: CKEDITOR.TRISTATE_OFF, liInsertAfter: CKEDITOR.TRISTATE_OFF };
					} else if ( name === 'li' && element.getParent().getName() === 'ul' ) {
					    return { liInsertBefore: CKEDITOR.TRISTATE_OFF, liInsertAfter: CKEDITOR.TRISTATE_OFF };
                    }

					element = element.getParent();
				}
				return null;
			} );
		}
	};

	CKEDITOR.plugins.add( 'listitemstyle', CKEDITOR.plugins.listitemstyle );
} )();
