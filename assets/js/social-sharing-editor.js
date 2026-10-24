/**
 * WordPress dependencies
 */
(function() {
    const { __ } = wp.i18n;

    wp.domReady( function() {

        // Add a class to the body for custom styling
        document.body.classList.add('wpzoom-social-sharing-editor');
        
        // Lock the template to prevent unwanted block manipulation
        setTimeout(function() {
            // Check if there's an "invalid block" message
            const hasInvalidBlock = document.querySelector('.block-editor-warning');
            
            if (hasInvalidBlock) {
                // Try to find the Attempt Recovery button and click it
                const recoveryButton = document.querySelector('.block-editor-warning__action button');
                if (recoveryButton) {
                    recoveryButton.click();
                } else {
                    // If recovery button not found, replace the block
                    replaceWithValidBlock();
                }
            }
            
            // Check if social sharing block exists in the editor
            const hasSocialSharingBlock = wp.data.select('core/block-editor')
                .getBlocks()
                .some(block => block.name === 'wpzoom-blocks/social-sharing');
            
            // If no social sharing block exists, insert one
            if (!hasSocialSharingBlock) {
                replaceWithValidBlock();
            }

            // Remove the post title field
            const titlePanel = document.querySelector('.edit-post-visual-editor__post-title-wrapper');
            if (titlePanel) {
                titlePanel.style.display = 'none';
            }
            
            // Add a custom header with instructions
            // Try different selectors used in various WordPress versions
            addInstructionsPanel();

            // Focus the editor
            wp.data.dispatch('core/block-editor').clearSelectedBlock();
        }, 500);

        /**
         * Add instructions panel above the editor content
         */
        function addInstructionsPanel() {
            // Try multiple potential selectors for the editor canvas
            const editorSelectors = [
                '.edit-post-visual-editor__content-area',
                '.interface-interface-skeleton__content',
                '.block-editor-block-list__layout',
                '.editor-styles-wrapper'
            ];

            let editorElement = null;

            // Try each selector until we find a matching element
            for (let selector of editorSelectors) {
                const element = document.querySelector(selector);
                if (element) {
                    editorElement = element;
                    break;
                }
            }

            // If we found an editor element
            if (editorElement) {
                // Create the instructions panel
                const instructionsPanel = document.createElement('div');
                instructionsPanel.className = 'wpzoom-sharing-editor-instructions';
                instructionsPanel.style.backgroundColor = 'color-mix(in srgb, var(--wp-admin-theme-color, #0073aa) 7%, #fff)';
                instructionsPanel.style.padding = '24px';
                instructionsPanel.style.borderLeft = '4px solid var(--wp-admin-theme-color, #0073aa)';
                instructionsPanel.style.boxShadow = '0 1px 1px rgba(0, 0, 0, 0.04)';

                instructionsPanel.innerHTML = `
                    <h2 style="margin-top: 0; font-size: 18px; font-weight: 500; margin-bottom: 12px; color: #23282d;">${ __( 'Configure Your Social Sharing Buttons', 'social-icons-widget-by-wpzoom' ) }</h2>
                    <p style="margin-bottom: 0; color: #646970;">${ __( 'These buttons will automatically appear on your content based on the settings in the sidebar. Customize the buttons below, then use the "Display Settings" panel to control where they appear.', 'social-icons-widget-by-wpzoom' ) }</p>
                `;

                // Try to find the best place to insert the panel
                if (editorElement.classList.contains('interface-interface-skeleton__content')) {
                    // For newer WordPress versions, insert at the top of the content area
                    editorElement.insertBefore(instructionsPanel, editorElement.firstChild);
                } else if (editorElement.parentNode) {
                    // For older WordPress versions, insert before the editor canvas
                    editorElement.parentNode.insertBefore(instructionsPanel, editorElement);
                }

                console.log('WPZOOM: Added instructions panel');
            } else {
                console.log('WPZOOM: Could not find editor element to add instructions');
            }
        }
        
        /**
         * Replace any existing content with a valid social sharing block
         */
        function replaceWithValidBlock() {
            // Replace all blocks with a new social sharing block
            wp.data.dispatch('core/block-editor').resetBlocks([]);
            
            // Create block with default attributes
            const block = wp.blocks.createBlock('wpzoom-blocks/social-sharing', {
                platforms: [
                    { id: 'facebook', name: 'Facebook', enabled: true, color: '#0866FF' },
                    { id: 'x', name: 'Share on X', enabled: true, color: '#000000' },
                    { id: 'threads', name: 'Threads', enabled: false, color: '#000000' },
                    { id: 'linkedin', name: 'LinkedIn', enabled: true, color: '#0966c2' },
                    { id: 'pinterest', name: 'Pinterest', enabled: false, color: '#E60023' },
                    { id: 'reddit', name: 'Reddit', enabled: false, color: '#FF4500' },
                    { id: 'pocket', name: 'Pocket', enabled: false, color: '#EF3F56' },
                    { id: 'telegram', name: 'Telegram', enabled: false, color: '#0088cc' },
                    { id: 'whatsapp', name: 'WhatsApp', enabled: true, color: '#25D366' },
                    { id: 'bluesky', name: 'Bluesky', enabled: false, color: '#1DA1F2' },
                    { id: 'email', name: 'Email', enabled: true, color: '#333333' },
                    { id: 'copy-link', name: 'Copy Link', enabled: true, color: '#333333' }
                ]
            });
            
            wp.data.dispatch('core/block-editor').insertBlocks(block);
        }
    });
})(); 