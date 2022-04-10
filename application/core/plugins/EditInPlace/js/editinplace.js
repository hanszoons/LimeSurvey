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

class BaseButton extends React.Component {
    constructor(props) {
        super(props);
        this.onclick = this.onclick.bind(this);
    }

    onclick() {
        throw 'not implemented';
    }

    render() {
        return <button onClick={this.onclick} className="btn btn-xs" data-toggle="tooltip" title={this.props.tooltipTitle}>
            <i className={"fa fa-" + this.props.icon}></i>
        </button>
    }
}

class SaveButton extends BaseButton {
    /**
     * Triggered when save-button is clicked
     *
     * @param {Event} event
     * @return {boolean}
     */
    onclick(event) {
        event.preventDefault();

        const data = {};
        data[editInPlaceGlobalData.csrfTokenName] = editInPlaceGlobalData.csrfToken;

        $.post(
            editInPlaceGlobalData.editInPlaceBaseUrl,
            data
        );
        return false;
    }
}

class EditButton extends BaseButton {
    /**
     * Triggered when edit-button is clicked
     *
     * @param {Event} event
     * @return {boolean}
     */
    onclick(event) {
        event.preventDefault();
        // TODO: Mount input fields here, or widget?
        const ids = [
            '#' + this.props.containerId + ' .question-text',
            '#' + this.props.containerId + ' .question-code',
            '#' + this.props.containerId + ' .ls-questionhelp'
        ];
        const content = {};
        const replaceWithInput = function(id, i) {
            const text = $(id).text().trim();
            content[id] = text;
            const width = Math.min($(id).innerWidth(), 500);
            //console.log('width', width);
            $(id).html(`<input value="${text}" name="" style="width: ${width}px;" />`);
        };
        this.props.setContent(content);
        ids.forEach(replaceWithInput);
        this.props.flipState();
        return false;
    }
}

class CancelButton extends BaseButton {
    /**
     * Triggered when cancel-button is clicked
     *
     * @param {Event} event
     * @return {boolean}
     */
    onclick(event) {
        event.preventDefault();
        for (const id in this.props.content) {
            $(id).text(this.props.content[id]);
        }
        this.props.flipState();
        return false;
    }
}

class ConditionInput extends React.Component {
    useEffect() {
        const container = document.createElement('div');
        setContainer(container);
        document.appendChild(container);
    }

    render() {
        return <h1>Hello</h1>
    }
}

class EditConditionButton extends BaseButton {
    onclick(event) {
        event.preventDefault();
        //$('#' + this.props.containerId).append('<div class="question-title-container col-xs-12">Condition: <input /></div>');

        $('#' + this.props.containerId).append(`
            <div class="modal" tabindex="-1" role="dialog" id="tmpModal">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-body">
                            <label>Condition:</label>
                            <i class="fa bold">{</i><input /><i class="fa bold">}</i>
                            <button type="button" class="btn btn-xs" data-dismiss="modal">
                                <i class="fa fa-save"></i>
                            </button>
                            <button type="button" class="btn btn-xs" id="save-empty-token">
                                <i class="fa fa-ban"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `);
        $('#tmpModal').modal();

        return false;
    }

    //render() {
        //return <ConditionInput />
    //}
}

class ToolButtons extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            edit: false,
            // Content saves original text while editing
            content: {}
        };
    }

    componentDidMount() {
        $('[data-toggle="tooltip"]').tooltip()
    }

    render() {
        if (this.state.edit) {
            return <div
                className="edit-in-place-buttons"
                style={{marginLeft: '-30px', position: 'absolute'}}
            >
                <SaveButton tooltipTitle="Save" icon="save" />
                <br/>
                <CancelButton
                    tooltipTitle="Cancel"
                    icon="ban"
                    content={this.state.content}
                    flipState={() => this.setState({edit: false})}
                />
            </div>
        } else {
            return <div
                className="edit-in-place-buttons"
                style={{marginLeft: '-30px', position: 'absolute'}}
            >
                <EditButton
                    tooltipTitle="Edit question"
                    icon="pencil"
                    flipState={() => this.setState({edit: true})}
                    setContent={(c) => this.state.content = c}
                    containerId={this.props.containerId}
                />
                <br/>
                <button className="btn btn-xs" title="Toggle mandatory" data-toggle="tooltip">
                    <i className="fa fa-exclamation-circle"></i>
                </button>
                <br/>
                <EditConditionButton icon="file" tooltipTitle="Edit condition" containerId={this.props.containerId} />
                <br/>
                <button className="btn btn-xs" title="Toggle encryption" data-toggle="tooltip">
                    <i className="fa fa-lock fa-lg"></i>
                </button>
                <br/>
                <button className="btn btn-xs" title="Change advanced attribute" data-toggle="tooltip">
                    <i className="fa fa-cog"></i>
                </button>
                <br/>
                <button className="btn btn-xs" title="Move up" data-toggle="tooltip">
                    <i className="fa fa-arrow-up"></i>
                </button>
                <br/>
                <button className="btn btn-xs" title="Move down" data-toggle="tooltip">
                    <i className="fa fa-arrow-down"></i>
                </button>
            </div>;
        }
    }
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
        root.render(<ToolButtons questionId={questionId} containerId={id} />);
    });
}

// This will be ready after the jQuery is ready, due to Babel.
initEditInPlace();
