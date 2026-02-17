import { Node, mergeAttributes } from '@tiptap/core';

export default Node.create({
    name: 'audio',

    group: 'block',

    atom: true,

    addAttributes() {
        return {
            src: { default: null },
        };
    },

    parseHTML() {
        return [
            {
                tag: 'div[data-audio]',
            },
        ];
    },

    renderHTML({ HTMLAttributes }) {
        return [
            'div',
            { 'data-audio': '', class: 'audio-wrapper my-4 p-4 bg-theme-elevated rounded-lg' },
            [
                'audio',
                mergeAttributes(HTMLAttributes, {
                    controls: true,
                    class: 'w-full',
                }),
            ],
        ];
    },

    addCommands() {
        return {
            setAudio: (options) => ({ commands }) => {
                return commands.insertContent({
                    type: this.name,
                    attrs: options,
                });
            },
        };
    },
});
