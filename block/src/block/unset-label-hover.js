/**
 * Blocks saved with no label hover color.
 *
 * The "without canvas" styles in PRO used to set iconsLabelHoverColor to
 * null. The markup was then saved without its CSS variable, but on the next
 * load WordPress swaps the null for the attribute's default, the save adds the
 * variable back, and the block shows "unexpected or invalid content".
 *
 * Each version given here is repeated with that attribute left without a
 * default, so the null stays unset and the markup matches. migrate() then
 * stores 'inherit', which is how a missing variable looks anyway.
 *
 * @param {Array} versions The current version ({ attributes, save }) and the deprecations.
 * @return {Array} Deprecations.
 */
export default function unsetLabelHover( versions ) {
	return versions.map( ( { attributes, migrate, isEligible, ...version } ) => ( {
		...version,
		attributes: {
			...attributes,
			iconsLabelHoverColor: { type: 'string' },
		},
		migrate( blockAttributes, innerBlocks ) {
			const fixed = {
				...blockAttributes,
				iconsLabelHoverColor: blockAttributes.iconsLabelHoverColor || 'inherit',
			};
			return migrate ? migrate( fixed, innerBlocks ) : fixed;
		},
	} ) );
}
