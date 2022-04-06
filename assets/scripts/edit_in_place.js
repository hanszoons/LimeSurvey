/**
 * Compiled with Babel in the browser.
 *
 * Editor has two states:
 *   1) Show edit-buttons on hover
 *   2) Inject widget, hide hover buttons
 *   3) Save with success/fail, or cancel, return to 1
 */

// 'hover' or 'edit'
let editInPlaceState = 'hover';

class EditButtons extends React.Component {
    constructor(props) {
        super(props);
        console.log('props', props);
    }

    doSomething(event) {
        event.preventDefault();
        console.log('clicked');
        return false;
    }

    componentDidMount() {
        $('[data-toggle="tooltip"]').tooltip()
    }

    render() {
        return <div
            className="edit-in-place-buttons"
            style={{marginLeft: '-30px', position: 'absolute'}}
            title="Edit question"
            data-toggle="tooltip"
        >
            <button
                className="btn btn-xs"
                onClick={this.doSomething}
                role="button"
            >
                <i className="fa fa-pencil"></i>
            </button>
        </div>;
    }
}

/**
 * Fired when edit-button is clicked
 *
 * @param {HTMLElement} that
 * @param {Event} ev
 * @param {number} questionId
 * @param {string} elementId
 * @return {void}
 */
function editInPlaceEdit(that, ev, questionId, elementId)
{
    if (editInPlaceState !== 'hover') {
        throw 'editInPlaceState must be hover when clicking on edit';
    }
    editInPlaceState = 'edit';

    //$('#question' + questionId).replaceWith(`<div>Hello</div>`);
    const $element = $('#' + elementId);
    const value = $element.text().trim();
    const originalHtml64 = btoa($element.html());
    // TODO: Deal with weird chars and escaping
    // TODO: How does this interact with clicking "Next" or "Back"?
    $element.replaceWith(`
        <div class="row" id="${elementId}">
            <div class="form-group col-md-6">
                <input id="" value="${value}" type="text" class="form-control col-6" />
            </div>
            <button class="btn btn-default btn-sm" onclick="editInPlaceSave(event);">Save</button>
            <button class="btn btn-default btn-sm" onclick="editInPlaceCancel(event, '${elementId}', '${originalHtml64}');">Cancel</button>
        </div>
    `);
}

function editInPlaceSave(ev)
{
    ev.preventDefault();
    $.ajax(
    );
    editInPlaceState = 'hover';
    return false;
}

/**
 * @param {Event} ev
 * @param {string} elementId
 * @param {string} org, base64 encoded
 */
function editInPlaceCancel(ev, elementId, org)
{
    ev.preventDefault();
    const html = atob(org);
    const $element = $('#' + elementId);
    $element.html(html);
    editInPlaceState = 'hover';
    return false;
}

/**
 * @return {void}
 */
function initEditInPlace() {
    // Loop all question containers and insert the edit buttons.
    $('.question-container').each(function(i, el) {
        const id         = el.id;
        const questionId = id.replace('question', '');
        const container = document.createElement('div');
        $(el).append(container);
        const root = ReactDOM.createRoot(container);
        root.render(<EditButtons questionId={questionId} />);
    });
}

// This will be ready after the jQuery is ready, due to Babel.
initEditInPlace();
