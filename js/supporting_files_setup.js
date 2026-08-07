init_setup();
handle_mode(element_view);


// Handlers for the controls in supporting_files.php.  These were inline
// onclick=/oninput= attributes, which CSP blocks.  This file is loaded at the
// bottom of the page and after init_setup() above, so every element exists and
// the controls can be bound directly rather than delegated -- none of this
// markup is replaced at runtime.
[
    [ 'sf_prev',   'click', sf_prev_doc     ],
    [ 'sf_next',   'click', sf_next_doc     ],
    [ 'sf_save',   'click', save_document   ],
    [ 'sf_update', 'click', update_document ],
    [ 'sf_delete', 'click', delete_document ],
    [ 'sf_upload', 'click', upload_document ]
].forEach( function( [ id, type, handler ] ) {
    const ele = document.getElementById( id );
    if ( ele ) {
        ele.addEventListener( type, handler );
    } else {
        console.error( `supporting_files_setup: no element #${id} to bind ${type}` );
    }
});

// These two pass the element itself through to their handler
[ 'sf_view', 'sf_new' ].forEach( function( id ) {
    const ele = document.getElementById( id );
    if ( ele ) {
        ele.addEventListener( 'click', function() { handle_mode( this ); } );
    } else {
        console.error( `supporting_files_setup: no element #${id} to bind click` );
    }
});

const sf_desc = document.getElementById( 'sf_desc' );
if ( sf_desc ) {
    sf_desc.addEventListener( 'input', function() { filter_text( this ); } );
} else {
    console.error( 'supporting_files_setup: no element #sf_desc to bind input' );
}
