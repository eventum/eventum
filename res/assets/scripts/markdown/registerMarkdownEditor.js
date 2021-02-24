import { MarkdownView } from "./MarkdownView";
import { ProseMirrorView } from "./ProseMirrorView";

/**
 * @param Document document
 */
const register = (document) => {
    const place = document.querySelector("#editor");
    let view = new MarkdownView(place, document.querySelector("#content").value);

    document.querySelectorAll("input[type=radio]").forEach(button => {
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
            view = new View(place, content);
            view.focus();
        });
    });
};

export default register;
