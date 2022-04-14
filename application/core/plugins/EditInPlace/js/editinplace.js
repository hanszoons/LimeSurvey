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
        return <button style={{float: "right"}} onClick={this.onclick} className="btn btn-xs" data-toggle="tooltip" title={this.props.tooltipTitle}>
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
        const that = this;

        this.props.flipState();

        const data = {};
        data[editInPlaceGlobalData.csrfTokenName] = editInPlaceGlobalData.csrfToken;
        data.lang = editInPlaceGlobalData.lang;
        data.surveyId = editInPlaceGlobalData.surveyId;

        $('#' + this.props.containerId + ' input').each(function(i, el) {
            data[el.name] = el.value;
        });

        // Post form and then reload the entire HTML
        $.post(
            editInPlaceGlobalData.editInPlaceBaseUrl,
            data,
            function(data, textStatus, jqXHR) {
                console.log(data);
            }
        ).then(function() {
            const url = window.location.href;
            $.get(
                url,
                {},
                function(newHtml, textStatus, jqXHR) {
                    const doc = new DOMParser().parseFromString(newHtml, "text/html");
                    const div = doc.querySelector("#" + that.props.containerId);
                    $("#" + that.props.containerId).replaceWith(div);
                    initEditInPlaceMisc(div);
                }
            )
        });
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
            '#' + this.props.containerId + ' .ls-question-help, ' + '#' + this.props.containerId + ' .ls-questionhelp'
        ];
        // TODO: Should be keys in ids array?
        const names = ['text', 'code', 'help'];
        const content = {};
        const replaceWithInput = function(id, i) {
            const text = $(id).text().trim();
            content[id] = text;
            const width = Math.min($(id).innerWidth(), 500);
            //console.log('width', width);
            const name = names[i];
            $(id).html(`<input value="${text}" name="${name}" style="width: ${width}px;" />`);
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
    constructor(props) {
        super(props);
        this.state = {edit: false}
    }

    onclick(event) {
        event.preventDefault();
        // Added input in question container
        //$('#' + this.props.containerId).append('<div class="question-title-container col-xs-12">Condition: <input /></div>');

        /*
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
        */
        this.setState({page: 'edit'});
        return false;
    }

    render() {
        if (this.state.edit) {
            // Popup input field
            return <div>
                <button className="btn btn-xs disabled">
                    <i className="fa fa-file"></i>
                </button>
                <div className="fade" style={{position: "absolute", width: "300px", marginLeft: "25px", opacity: 1, background: "white", marginTop: "-25px"}}>
                    <i className="fa bold"><strong>&#123;</strong></i>
                    <input />
                    <i className="fa bold"><strong>&#125;</strong></i>
                    <button type="button" className="btn btn-xs" data-dismiss="modal">
                        <i className="fa fa-save"></i>
                    </button>
                    &nbsp;
                    <button
                        type="button"
                        className="btn btn-xs"
                        id="save-empty-token"
                        onClick={() => this.setState({page: 'base'})}
                    >
                        <i className="fa fa-ban"></i>
                    </button>
                </div>
            </div>;
        } else {
            return super.render();
        }
    }
}

class EllipsisButton extends BaseButton {
    constructor(props) {
        super(props);
        this.state = {expanded: false};
    }

    onclick() {
        this.setState({expanded: true});
    }

    componentDidUpdate() {
        //this.props.recalculateWidth();
        $('.tooltip').hide()
        $('[data-toggle="tooltip"]').tooltip()
    }

    render() {
        if (this.state.expanded) {
        } else {
            return super.render();
        }
    }
}

class ToolButtons extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            // Page can be 'base', 'edit', 'adv'
            page: 'base',
            // Content saves original text while editing
            content: {}
        };
        this.ref = React.createRef()
    }

    componentDidUpdate() {
        $('.tooltip').hide()
        $('[data-toggle="tooltip"]').tooltip()
        this.recalculateWidth();
    }

    componentDidMount() {
        this.recalculateWidth();
    }

    recalculateWidth() {
        const negWidth = this.ref.current ? -this.ref.current.offsetWidth : -30;
        const newWidth = negWidth - 8;
        this.ref.current.style.marginLeft = newWidth + 'px';
        $('#' + this.props.containerId).animate({marginLeft: (-newWidth) + 'px'}, 250);
    }

    render() {
        if (this.state.page === 'edit') {
            return <div
                ref={this.ref}
                className="edit-in-place-buttons text-right"
                style={{marginLeft: '-30px', position: 'absolute'}}
            >
                <CancelButton
                    tooltipTitle="Cancel"
                    icon="ban"
                    content={this.state.content}
                    flipState={() => this.setState({page: 'base'})}
                />
                <SaveButton tooltipTitle="Save" icon="save" containerId={this.props.containerId} flipState={() => this.setState({page: 'saving'})} />
            </div>;
        } else if (this.state.page === 'adv') {
            return <div
                ref={this.ref}
                className="edit-in-place-buttons text-right"
                style={{marginLeft: '-30px', position: 'absolute'}}
            >
                <div>
                    <button className="btn btn-xs"><i className="fa fa-save"></i></button>
                    <button onClick={() => this.setState({page: "base"})} className="btn btn-xs"><i className="fa fa-ban"></i></button>
                </div>
                <div className="btn-group" role="group">
                    <button className="btn btn-xs">Off</button>
                    <button className="btn btn-xs">Soft</button>
                    <button className="btn btn-xs">On</button>
                    <i className="fa fa-fw fa-exclamation"></i>
                </div>
                <br/>
                <div className="btn-group" role="group">
                    <button className="btn btn-xs">On</button>
                    <button className="btn btn-xs">Off</button>
                    <i className="fa fa-fw fa-lock"></i>
                </div>
                <br/>
                <div>
                    <i className="fa bold"><strong>&#123;</strong></i>
                    <input />
                    <i className="fa bold"><strong>&#125;</strong></i>
                    <i className="fa fa-fw fa-file"></i>
                </div>
                <div style={{margin: "2px"}} >
                    <select style={{width: "80px"}}>
                        <option>Adv</option>
                        <option>Something longer</option>
                    </select>
                    &nbsp;
                    <input style={{width: "50%"}} />
                    <i className="fa fa-fw fa-cog"></i>
                </div>
            </div>;
        } else if (this.state.page === 'base') {
            return <div
                ref={this.ref}
                className="edit-in-place-buttons"
                style={{marginLeft: '-30px', position: 'absolute'}}
            >
                <EditButton
                    tooltipTitle="Edit question"
                    icon="pencil"
                    flipState={() => this.setState({page: 'edit'})}
                    setContent={(c) => this.state.content = c}
                    containerId={this.props.containerId}
                />
                <br/>

                <button onClick={() => this.setState({page: 'adv'})} className="btn btn-xs" title="Expand" data-toggle="tooltip">
                    <i className="fa fa-ellipsis-h"></i>
                </button>
                <br/>

                <button className="btn btn-xs" title="Move up" data-toggle="tooltip">
                    <i className="fa fa-arrow-up"></i>
                </button>
                <br/>
                <button className="btn btn-xs" title="Move down" data-toggle="tooltip">
                    <i className="fa fa-arrow-down"></i>
                </button>
                <br/>
                {/*
                <button className="btn btn-xs" title="Toggle mandatory" data-toggle="tooltip">
                    <i className="fa fa-exclamation-circle"></i>
                </button>
                <EditConditionButton icon="file" tooltipTitle="Edit condition" containerId={this.props.containerId} />
                <button className="btn btn-xs" title="Toggle encryption" data-toggle="tooltip">
                    <i className="fa fa-lock fa-lg"></i>
                </button>
                <button className="btn btn-xs" title="Change advanced attribute" data-toggle="tooltip">
                    <i className="fa fa-cog"></i>
                </button>
                <button className="btn btn-xs" title="Move up" data-toggle="tooltip">
                    <i className="fa fa-arrow-up"></i>
                </button>
                <button className="btn btn-xs" title="Move down" data-toggle="tooltip">
                    <i className="fa fa-arrow-down"></i>
                </button>
                */}
            </div>;
        } else if (this.state.page === 'saving') {
            return <div
                ref={this.ref}
                className="edit-in-place-buttons"
                style={{marginLeft: '-30px', position: 'absolute'}}
            >
                <i className="fa fa-spinner fa-spin"></i>
            </div>;
        }
    }
}

function initEditInPlaceMisc(el) {
    const id         = el.id;
    const questionId = id.replace('question', '');
    const container = document.createElement('div');
    $(el).append(container);
    const root = ReactDOM.createRoot(container);
    root.render(<ToolButtons questionId={questionId} containerId={id} />);
}

/**
 * @return {void}
 */
function initEditInPlace() {
    // Loop all question containers and insert the edit buttons.
    $('.question-container').each(function(i, el) {
        initEditInPlaceMisc(el);
    });
}

// This will be ready after the jQuery is ready, due to Babel.
initEditInPlace();
