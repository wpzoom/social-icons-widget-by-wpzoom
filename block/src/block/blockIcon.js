/**
 * WordPress dependencies
 */
import { SVG, Path } from '@wordpress/primitives';

/*
 * Four rounded tiles with heart, play, ring and chat cut-outs: a grid of
 * social icons. Drawn on the 24px grid used by the Sharing Buttons block icon.
 */
export default (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
		<Path
			fillRule="evenodd"
			d="M4.4 2h4.2A2.4 2.4 0 0 1 11 4.4v4.2A2.4 2.4 0 0 1 8.6 11H4.4A2.4 2.4 0 0 1 2 8.6V4.4A2.4 2.4 0 0 1 4.4 2zm11 0h4.2A2.4 2.4 0 0 1 22 4.4v4.2a2.4 2.4 0 0 1-2.4 2.4h-4.2A2.4 2.4 0 0 1 13 8.6V4.4A2.4 2.4 0 0 1 15.4 2zm-11 11h4.2a2.4 2.4 0 0 1 2.4 2.4v4.2A2.4 2.4 0 0 1 8.6 22H4.4A2.4 2.4 0 0 1 2 19.6v-4.2A2.4 2.4 0 0 1 4.4 13zm11 0h4.2a2.4 2.4 0 0 1 2.4 2.4v4.2a2.4 2.4 0 0 1-2.4 2.4h-4.2a2.4 2.4 0 0 1-2.4-2.4v-4.2a2.4 2.4 0 0 1 2.4-2.4zM6.5 9.2l-.53-.48C4.9 7.75 4.15 7.06 4.15 6.13c0-.72.56-1.28 1.28-1.28.4 0 .8.19 1.07.49.27-.3.67-.49 1.07-.49.72 0 1.28.56 1.28 1.28 0 .93-.75 1.62-1.82 2.6zm9.25-5v4.6l4.28-2.3zM6.5 15a2.5 2.5 0 1 0 0 5 2.5 2.5 0 1 0 0-5zm0 1.35a1.15 1.15 0 1 1 0 2.3 1.15 1.15 0 1 1 0-2.3zm9.6-1.05h2.8a1.2 1.2 0 0 1 1.2 1.2V18a1.2 1.2 0 0 1-1.2 1.2h-2l-1.3 1.3.4-1.3h-.1a1.2 1.2 0 0 1-1.2-1.2v-1.5a1.2 1.2 0 0 1 1.2-1.2z"
		/>
	</SVG>
);
