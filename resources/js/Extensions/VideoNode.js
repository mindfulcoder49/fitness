import { Node, mergeAttributes } from '@tiptap/core';

export default Node.create({
    name: 'video',

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
                tag: 'div[data-video]',
            },
        ];
    },

    renderHTML({ HTMLAttributes }) {
        return [
            'div',
            { 'data-video': '', class: 'video-wrapper my-4' },
            [
                'video',
                mergeAttributes(HTMLAttributes, {
                    controls: true,
                    class: 'w-full rounded-lg',
                }),
            ],
        ];
    },

    addCommands() {
        return {
            setVideo: (options) => ({ commands }) => {
                return commands.insertContent({
                    type: this.name,
                    attrs: options,
                });
            },
        };
    },
});
