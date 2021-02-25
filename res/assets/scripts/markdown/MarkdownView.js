export class MarkdownView {
    constructor(source, target, content) {
        const textarea = document.createElement("textarea");
        textarea.value = content;
        // copy width and rows/cols from source element
        textarea.style.width = source.style.width;
        textarea.rows = source.rows;
        textarea.cols = source.cols;
        textarea.tabIndex = source.tabIndex;
        this.textarea = target.appendChild(textarea);
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
