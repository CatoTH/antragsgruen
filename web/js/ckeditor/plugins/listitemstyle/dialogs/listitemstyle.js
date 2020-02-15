( function() {
	function getListItemElement( editor, listTag ) {
		var range;
		try {
			range = editor.getSelection().getRanges()[ 0 ];
		} catch ( e ) {
			return null;
		}

		range.shrink( CKEDITOR.SHRINK_TEXT );
		return editor.elementPath( range.getCommonAncestor() ).contains( listTag, 1 );
	}

	function listStyle( editor, startupPage ) {
		var lang = editor.lang.listitemstyle;

        return {
            title: lang.numberedItemTitle,
            minWidth: 300,
            minHeight: 50,
            getModel: generateModelGetter( editor, 'li' ),
            contents: [ {
                id: 'info',
                accessKey: 'I',
                elements: [ {
                    type: 'hbox',
                    widths: [ '25%', '75%' ],
                    children: [ {
                        label: lang.value,
                        type: 'text',
                        id: 'value',
                        setup: function( element ) {
                            var value = element.getAttribute( 'value' ) || '';
                            value && this.setValue( value );
                        },
                        commit: function( element ) {
                            var val = this.getValue();
                            if ( val.trim() === '' )
                                element.removeAttribute( 'value' );
                            else
                                element.setAttribute( 'value', val );
                        }
                    }]
                } ]
            } ],
            onShow: function() {
                var editor = this.getParentEditor(),
                    element = getListItemElement( editor, 'li' );

                element && this.setupContent( element );
            },
            onOk: function() {
                var editor = this.getParentEditor(),
                    element = getListItemElement( editor, 'li' );

                element && this.commitContent( element );
            }
        };
	}

	CKEDITOR.dialog.add( 'numberedListItemStyle', function( editor ) {
		return listStyle( editor, 'numberedListItemStyle' );
	} );

	function generateModelGetter( editor, tagName ) {
		return function() {
			return getListItemElement( editor, tagName ) || null;
		};
	}
} )();
