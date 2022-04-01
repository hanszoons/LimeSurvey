/**
 * Compiled with Babel in the browser.
 */

/**
 * Example
 */
class LikeButton extends React.Component {
    constructor(props) {
        super(props);
        this.state = { liked: false };
    }

    render() {
        if (this.state.liked) {
            return 'You liked this.';
        }

        return React.createElement(
            'button',
            { onClick: () => this.setState({ liked: true }) },
            'Like'
        );
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
    console.log('that', that);
    console.log('ev', ev);
    console.log('questionId', questionId);
    //$('#question' + questionId).replaceWith(`<div>Hello</div>`);
    const $element = $('#' + elementId);
    const value = $element.text().trim();
    const originalHtml64 = btoa($element.html());
    console.log('value', value);
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
    return false;
}

/**
 * @param {Event} ev
 * @param {string} elementId
 * @param {string} org, base64 encoded
 */
function editInPlaceCancel(ev, elementId, org)
{
    console.log('editInPlaceCancel');
    ev.preventDefault();
    const html = atob(org);
    console.log('html', html);
    const $element = $('#' + elementId);
    $element.html(html);
    return false;
}

/**
 * @param {event} ev - ev
 * @return {void}
 */
function hoverText(ev)
{
    const target     = ev.target;
    const id         = target.id;
    const parts      = id.split('-');
    const sgqa       = parts[3];
    const sgqaParts  = sgqa.split('X');
    const questionId = sgqaParts[2];

    if (questionId == undefined) {
        throw 'Could not find questionId';
    }

    $(target).append(`
        <div class="edit-in-place-buttons" style="position: absolute; top: 0px; left: 0px;">
            <i onclick="editInPlaceEdit(this, event, ${questionId}, '${id}');" role="button" class="fa fa-pencil btn btn-default btn-xs"></i>
        </div>`);
}

/**
 * @return {void}
 */
function initEditInPlace() {
    const container = document.getElementById('question1371');
    const root = ReactDOM.createRoot(container);
    //root.render(<LikeButton />);
    //console.log('after root render');

    $('.question-text').mouseenter(function (ev) { hoverText(ev); });
    $('.question-text').mouseleave(function () { $('.edit-in-place-buttons').remove(); });
}

// This will be ready after the jQuery is ready, due to Babel.
initEditInPlace();
