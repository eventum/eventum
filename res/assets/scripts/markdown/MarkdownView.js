export class MarkdownView {
    constructor(source, target, content) {
        this.textarea = target.appendChild(document.createElement("textarea"));
        this.textarea.value = content;
    }

    get content() {
        return this.textarea.value;
    }

    focus() {
        this.textarea.focus();
    }

    destroy() {
        this.textarea.remove();
    }
}
