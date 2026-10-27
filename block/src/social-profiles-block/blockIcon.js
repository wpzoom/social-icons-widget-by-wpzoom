/**
 * WordPress dependencies
 */
import { SVG, Path } from '@wordpress/primitives';

// A list of profiles: an avatar dot and a line, three times.
export default (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
		<Path d="M4 4.5h16A1.5 1.5 0 0 1 21.5 6v2A1.5 1.5 0 0 1 20 9.5H4A1.5 1.5 0 0 1 2.5 8V6A1.5 1.5 0 0 1 4 4.5Zm0 1.5v2h16V6H4Zm0 3.5h16A1.5 1.5 0 0 1 21.5 11v2a1.5 1.5 0 0 1-1.5 1.5H4A1.5 1.5 0 0 1 2.5 13v-2A1.5 1.5 0 0 1 4 9.5ZM4 11v2h16v-2H4Zm0 3.5h16a1.5 1.5 0 0 1 1.5 1.5v2a1.5 1.5 0 0 1-1.5 1.5H4A1.5 1.5 0 0 1 2.5 18v-2A1.5 1.5 0 0 1 4 14.5ZM4 16v2h16v-2H4Z" />
		<Path d="M6 7a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm0 5a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm0 5a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z" />
	</SVG>
);
