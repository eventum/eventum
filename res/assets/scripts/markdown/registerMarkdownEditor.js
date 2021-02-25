import { MarkdownView } from "./MarkdownView";
import { ProseMirrorView } from "./ProseMirrorView";

/**
 * @param {HTMLTextAreaElement} textarea
 */
const register = (textarea) => {
    // Create container zone
    const container = document.createElement("div");
    container.classList = textarea.classList;
    if (textarea.nextSibling) {
        textarea.parentElement.insertBefore(container, textarea.nextSibling);
    } else {
        textarea.parentElement.appendChild(container);
    }

    // Hide textarea
    textarea.style.display = "none";

    const editor_id = textarea.getAttribute("data-editor-id");
    let view = new MarkdownView(textarea, container, textarea.value);

    $(`.md-radio-${editor_id}`).each(function (index, button) {
        button.addEventListener("change", () => {
            if (!button.checked) {
                return;
            }

            const View = button.value === "markdown" ? MarkdownView : ProseMirrorView;
            if (view instanceof View) {
                return;
            }

            const content = view.content;
            view.destroy();
            view = new View(textarea, container, content);
            view.focus();
        });
    });
};

/**
 * @param {jQuery} $elements
 */
const registerElements = ($elements) => {
    $elements.each(function (index, textarea) {
        register(textarea);
    });
};

export default registerElements;
