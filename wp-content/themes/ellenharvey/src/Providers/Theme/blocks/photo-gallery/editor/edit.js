import { useBlockProps } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';
import metadata from '../block.json';

/**
 * The Photo Gallery has no settings — it always renders every Photos post in
 * menu order. Preview the real server output (ServerSideRender) so authors see
 * the actual poster grid in the editor.
 */
export default function Edit() {
    const blockProps = useBlockProps();
    return (
        <div {...blockProps}>
            <ServerSideRender block={metadata.name} />
        </div>
    );
}
