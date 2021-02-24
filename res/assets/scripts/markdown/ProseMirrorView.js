import { EditorView } from "prosemirror-view";
import { EditorState } from "prosemirror-state";
import { defaultMarkdownParser, defaultMarkdownSerializer, schema } from "prosemirror-markdown";
import { exampleSetup } from "prosemirror-example-setup";

export class ProseMirrorView {
    constructor(target, content) {
        this.view = new EditorView(target, {
            state: EditorState.create({
                doc: defaultMarkdownParser.parse(content),
                plugins: exampleSetup({ schema }),
            })
        });
    }

    get content() {
        return defaultMarkdownSerializer.serialize(this.view.state.doc);
    }

    focus() {
        this.view.focus();
    }

    destroy() {
        this.view.destroy();
    }
}
