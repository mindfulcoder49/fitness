import { Node } from '@tiptap/core';

export default Node.create({
    name: 'embed',

    group: 'block',

    atom: true,

    addAttributes() {
        return {
            url: { default: null },
            platform: { default: null },
        };
    },

    parseHTML() {
        return [
            {
                tag: 'div[data-embed]',
            },
        ];
    },

    renderHTML({ HTMLAttributes }) {
        const platformLabels = {
            twitter: 'Twitter/X',
            instagram: 'Instagram',
            facebook: 'Facebook',
        };

        return [
            'div',
            {
                'data-embed': '',
                'data-embed-url': HTMLAttributes.url,
                'data-embed-platform': HTMLAttributes.platform,
                class: 'embed-wrapper my-4 p-4 border border-theme-border rounded-lg bg-theme-elevated',
            },
            [
                'div',
                { class: 'flex items-center gap-2 text-sm text-theme-text-muted' },
                [
                    'span',
                    { class: 'font-medium' },
                    platformLabels[HTMLAttributes.platform] || 'Embed',
                ],
                ['span', {}, ': '],
                [
                    'a',
                    { href: HTMLAttributes.url, target: '_blank', rel: 'noopener', class: 'text-theme-link truncate' },
                    HTMLAttributes.url,
                ],
            ],
        ];
    },

    addCommands() {
        return {
            setEmbed: (options) => ({ commands }) => {
                return commands.insertContent({
                    type: this.name,
                    attrs: options,
                });
            },
        };
    },
});
