/**
 * Internal dependencies
 */
import { createSave } from './components/Save';

/**
 * Blocks saved by WPZOOM Connect Pro, for sites that go back to the free
 * plugin. PRO writes every icon's SVG into the post; free saves empty icon
 * spans and draws them on the page. Without these, those blocks show
 * "unexpected or invalid content".
 *
 * Free doesn't have PRO's artwork, so each icon's markup is read back from the
 * saved block (proIconsHtml, sourced from the HTML) and saved as it was; the
 * block then validates, migrate() drops proIconsHtml, and the next save is in
 * the free format.
 *
 * @param {Object} attributes The block's attributes.
 * @return {Array} Deprecations.
 */
export default function proDeprecations( attributes ) {
	const proAttributes = {
		...attributes,
		proIconsHtml: {
			type: 'array',
			source: 'query',
			selector: '.social-icon-link > .social-icon',
			query: {
				html: {
					type: 'string',
					source: 'html',
				},
			},
		},
	};

	const migrate = ( { proIconsHtml, ...rest } ) => rest;

	return [
		// PRO with icons left without a link (saved as spans).
		{
			attributes: proAttributes,
			save: createSave( { pro: true } ),
			migrate,
		},
		// PRO before that: an icon with no URL was saved as `<a href="">`.
		{
			attributes: proAttributes,
			save: createSave( { pro: true, linkless: false } ),
			migrate,
		},
	];
}
