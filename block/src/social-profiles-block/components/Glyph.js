/**
 * Internal dependencies
 */
import IconGlyph from '../../block/components/IconGlyph';

/**
 * A profile's icon, drawn as in the Social Icons block (the PRO copy of this
 * file draws PRO's own SVG artwork instead).
 *
 * @param {Object} props
 * @param {string} props.iconKit Kit.
 * @param {string} props.icon    Icon name.
 */
export default function Glyph( { iconKit, icon } ) {
	return <IconGlyph iconKit={ iconKit } icon={ icon } />;
}
