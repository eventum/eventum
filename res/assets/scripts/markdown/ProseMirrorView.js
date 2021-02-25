import { EditorView } from "prosemirror-view";
import { EditorState } from "prosemirror-state";
import { defaultMarkdownParser, defaultMarkdownSerializer, schema } from "prosemirror-markdown";
import { exampleSetup } from "prosemirror-example-setup";

export class ProseMirrorView {
    constructor(source, target, content) {
        this.area = source;
        this.view = new EditorView(target, {
            state: EditorState.create({
                doc: defaultMarkdownParser.parse(content),
                plugins: exampleSetup({ schema }),
            }),
            dispatchTransaction: (tr) => {
                const { state } = this.view.state.applyTransaction(tr);
                this.view.updateState(state);
                // Update textarea only if content has changed
                if (tr.docChanged) {
                    this.area.value = defaultMarkdownSerializer.serialize(tr.doc);
                }
            },
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
