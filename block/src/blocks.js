/**
 * Gutenberg Blocks
 *
 * All blocks related JavaScript files should be imported here.
 * You can create a new block folder in this dir and include code
 * for that block here as well.
 *
 * All blocks should be included here since this is the file that
 * Webpack is compiling as the input file.
 */

import './block/block.js';
import './social-sharing-block/block.js';
import './preferred-source-block/block.js';
import wpzoomCategoryIcon from './block/wpzoomCategoryIcon';

import { updateCategory } from '@wordpress/blocks';

( function() {
	updateCategory( 'wpzoom-blocks', { icon: wpzoomCategoryIcon } );
}() );
