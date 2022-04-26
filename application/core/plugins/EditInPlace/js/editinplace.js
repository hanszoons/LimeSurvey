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

class Queue {
    constructor() {
        this.messages = [];
    }

    add(message) {
        this.messages.push(message);
    }

    getNewMessages(containerId) {
        const newMessages = [];
        for (let i = 0; i < this.messages.length; i++) {
            const message = this.messages[i];
            if (message.containerId === containerId) {
            }
        }
        return newMessages;
    }
}

// Messages of type QueueMessage
let editInPlaceQueue = new Queue();

class QueueMessage {
    constructor(containerId, message) {
        this.containerId = containerId;
        this.message = message;
    }
}

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
            <i className={"fa fa-fw fa-" + this.props.icon}></i>
        </button>
    }
}

class MoveButton extends BaseButton {
    onclick() {
        this.props.flipState('saving');
        const that = this;
        const data = {};
        data[editInPlaceGlobalData.csrfTokenName] = editInPlaceGlobalData.csrfToken;
        data.lang = editInPlaceGlobalData.lang;
        data.surveyId = editInPlaceGlobalData.surveyId;
        // NB: Container id is "question" + question id
        data.questionId = this.props.containerId.replace('question', '');

        $.post(
            this.props.moveUrl,
            data,
            function(data, textStatus, jqXHR) {
                const id = $('#' + that.props.containerId).parents('.group-outer-container').get(0).id
                resetGroupHtml(id).then(() => showSuccessMessage(that.props.containerId, "Question moved"));
            }
        );
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

        this.props.flipState('saving');

        const data = {};
        data[editInPlaceGlobalData.csrfTokenName] = editInPlaceGlobalData.csrfToken;
        data.lang = editInPlaceGlobalData.lang;
        data.surveyId = editInPlaceGlobalData.surveyId;
        // NB: Container id is "question" + question id
        data.questionId = this.props.containerId.replace('question', '');

        $('#' + this.props.containerId + ' input').each(function(i, el) {
            data[el.name] = el.value;
        });

        // Post form and then reload the entire HTML
        $.post(
            editInPlaceGlobalData.saveUrl,
            data,
            function(data, textStatus, jqXHR) {
                console.log(data);
                resetContainerHtml(that.props.containerId)
                    .then(() => showSuccessMessage(that.props.containerId, "Question saved"));
            }
        )
            .fail(function(jqXHR) {
                const alertText = JSON.parse(jqXHR.responseText);
                const text = jqXHR.status + ": " + alertText;
                showErrorMessage(that.props.containerId, text);
                // Restore question, help, qid content
                // TODO: Need to resetContainerHtml here, some stuff might have been saved, other not
                for (const id in that.props.content) {
                    $(id).text(that.props.content[id]);
                }
                that.props.flipState('base');
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
            '#' + this.props.containerId + ' .ls-questionhelp'
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
                                <i class="fa fa-close"></i>
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
                    <i className="fa fa-fw fa-file"></i>
                </button>
                <div className="fade" style={{position: "absolute", width: "300px", marginLeft: "25px", opacity: 1, background: "white", marginTop: "-25px"}}>
                    <i className="fa fa-fw bold"><strong>&#123;</strong></i>
                    <input />
                    <i className="fa fa-fw bold"><strong>&#125;</strong></i>
                    <button type="button" className="btn btn-xs" data-dismiss="modal">
                        <i className="fa fa-fw fa-save"></i>
                    </button>
                    &nbsp;
                    <button
                        type="button"
                        className="btn btn-xs"
                        id="save-empty-token"
                        onClick={() => this.setState({page: 'base'})}
                    >
                        <i className="fa fa-fw fa-close"></i>
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

class MandatoryButtonGroup extends React.Component {
    render() {
        const mandatory = this.props.value;
        if (mandatory === undefined) {
            return "";
        } else {
            return <>
                <i className="fa fa-fw fa-exclamation" title="Mandatory" data-toggle="tooltip"></i>
                <div className="btn-group btn-group-toggle" data-toggle="buttons">
                    <button className={"btn btn-xs " + (mandatory === "N" && "active")}>
                        <input value="N" type="radio" name="mandatory" id="option1" defaultChecked={mandatory === "N"} /> Off
                    </button>
                    <button className={"btn btn-xs " + (mandatory === "S" && "active")}>
                        <input value="S" type="radio" name="mandatory" id="option2" defaultChecked={mandatory === "S"} /> Soft
                    </button>
                    <button className={"btn btn-xs " + (mandatory === "Y" && "active")}>
                        <input value="Y" type="radio" name="mandatory" id="option3" defaultChecked={mandatory === "Y"} /> On
                    </button>
                </div>
            </>;
        }
    }
}

class EncryptedButtonGroup extends React.Component {
    render() {
        const encrypted = this.props.value;
        if (encrypted === undefined) {
            return "";
        } else {
            return <>
                <i className="fa fa-fw fa-lock" title="Encrypted" data-toggle="tooltip"></i>
                <div className="btn-group btn-group-toggle" data-toggle="buttons">
                    <button className={"btn btn-xs " + (encrypted === "Y" && "active")}>
                        <input value="Y" type="radio" name="encrypted" id="encrypted-option1" defaultChecked={encrypted === "Y"} /> On
                    </button>
                    <button className={"btn btn-xs " + (encrypted === "N" && "active")}>
                        <input value="N" type="radio" name="encrypted" id="encrypted-option1" defaultChecked={encrypted === "N"} /> Off
                    </button>
                </div>
            </>;
        }
    }
}

class SaveAdvancedForm extends BaseButton {
    onclick(event) {
        event.preventDefault();
        this.props.flipState('saving');

        const inputs = $(this.props.parent.current).find('input, select, textarea')
        const values = $(inputs).serializeArray();
        const data = {}
        const that = this;

        data[editInPlaceGlobalData.csrfTokenName] = editInPlaceGlobalData.csrfToken;
        data.questionId = this.props.containerId.replace('question', '');
        values.forEach((el) => data[el.name] = el.value);

        $.post(
            editInPlaceGlobalData.saveAdvUrl,
            data,
            function(data, textStatus, jqXHR) {
                resetContainerHtml(that.props.containerId)
                    .then(() => showSuccessMessage(that.props.containerId, "Question saved"));
            }
        )
            .fail(function(jqXHR) {
                const alertText = JSON.parse(jqXHR.responseText);
                const text = jqXHR.status + ": " + alertText;
                showErrorMessage(that.props.containerId, text);
                // Restore question, help, qid content
                // TODO: Need to resetContainerHtml here, some stuff might have been saved, other not
                for (const id in that.props.content) {
                    $(id).text(that.props.content[id]);
                }
                that.props.flipState('base');
            });
    }
}

class ToolButtons extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            // Page can be 'base', 'edit', 'adv'
            page: 'base',
            // Content saves original text while editing
            content: {},
            // Loaded lazily when entering advanced form.
            questionAttributes: {},
            showSuccess: false
        };
        this.ref = React.createRef()
    }

    getAttributes() {
        const that = this;
        const data = {};
        data[editInPlaceGlobalData.csrfTokenName] = editInPlaceGlobalData.csrfToken;
        data.questionId = this.props.containerId.replace('question', '');
        $.get(
            editInPlaceGlobalData.getAttributesUrl,
            data,
            function(data, textStatus, jqXHR) {
                console.log('data', data);
                that.setState({questionAttributes: data});
            }
        );
    }

    componentDidUpdate() {
        $('.tooltip').hide()
        $('[data-toggle="tooltip"]').tooltip()
        this.recalculateWidth();
        if (this.state.page === 'adv' && $.isEmptyObject(this.state.questionAttributes)) {
            this.getAttributes();
        }
    }

    componentDidMount() {
        this.recalculateWidth();
        const messagesForMe = editInPlaceQueue.getNewMessages(this.props.containerId);
        //document.body.dispatchEvent(new CustomEvent("edit-in-place-mounted", {detail: {containerId: this.props.containerId}}));
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
                className="edit-in-place-buttons text-left"
                style={{marginLeft: '-30px', position: 'absolute'}}
            >
                <SaveButton
                    tooltipTitle="Save"
                    icon="save"
                    containerId={this.props.containerId}
                    content={this.state.content}
                    flipState={(p) => this.setState({page: p})}
                />
                <CancelButton
                    tooltipTitle="Cancel"
                    icon="close"
                    content={this.state.content}
                    flipState={() => this.setState({page: 'base'})}
                />
            </div>;
        } else if (this.state.page === 'adv') {
            const mandatory = this.state.questionAttributes.mandatory;
            const encrypted = this.state.questionAttributes.encrypted;
            return <div
                ref={this.ref}
                className="edit-in-place-buttons text-left"
                style={{marginLeft: '-30px', position: 'absolute'}}
            >
                <div>
                    <i className="fa fa-fw"></i>
                    <SaveAdvancedForm
                        parent={this.ref}
                        icon="save"
                        tooltipTitle="Save"
                        flipState={(p) => this.setState({page: p})}
                        containerId={this.props.containerId}
                    />
                    <button onClick={() => this.setState({page: "base"})} className="btn btn-xs" title="Cancel" data-toggle="tooltip">
                        <i className="fa fa-fw fa-close"></i>
                    </button>
                </div>
                <MandatoryButtonGroup value={mandatory} />
                <br/>
                <EncryptedButtonGroup value={encrypted} />
                <br/>
                <div>
                    <i className="fa fa-fw fa-file" title="Condition" data-toggle="tooltip"></i>
                    <i className="fa fa-fw bold"><strong>&#123;</strong></i>
                    <input name="relevance" defaultValue={this.state.questionAttributes.relevance} />
                    {/*<textarea rows="1" value={this.state.questionAttributes.relevance}></textarea>*/}
                    <i className="fa fa-fw bold"><strong>&#125;</strong></i>
                </div>
                <div style={{margin: "2px"}} >
                    <i className="fa fa-fw fa-cog" title="Advanced attribute" data-toggle="tooltip"></i>
                    <select style={{width: "80px"}}>
                        {Object.entries(this.state.questionAttributes).map(([key, value]) => <option>{key}</option>)}
                    </select>
                    &nbsp;
                    <input />
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
                    <i className="fa fa-fw fa-ellipsis-h"></i>
                </button>
                <br/>

                <MoveButton
                    tooltipTitle="Move up"
                    icon="arrow-up"
                    containerId={this.props.containerId}
                    content={this.state.content}
                    flipState={(p) => this.setState({page: p})}
                    moveUrl={editInPlaceGlobalData.moveUpUrl}
                />
                <br/>
                <MoveButton
                    tooltipTitle="Move down"
                    icon="arrow-down"
                    containerId={this.props.containerId}
                    content={this.state.content}
                    flipState={(p) => this.setState({page: p})}
                    moveUrl={editInPlaceGlobalData.moveDownUrl}
                />
                <br/>
                { this.state.showSuccess && <span style={{padding: "1px 5px", opacity: 0.6}} data-toggle="tooltip" title="Question saved">
                    <i className="fa fa-fw fa-check text-primary"></i>
                </span>
                }
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

// TODO: Remove code duplication
function showSuccessMessage(containerId, text) {
    document.body.addEventListener('edit-in-place-mounted', function(event) {
        const alertId = "alert_" + Math.floor(Math.random() * 999999);
        const containerId = event.detail.containerId;
        const search = "#" + containerId + " .edit-in-place-buttons";
        $(search).append(`
            <br/>
            <span
                id="${alertId}"
                class="bg-primary btn btn-xs"
                style="display: none;"
                role="button"
                data-toggle="tooltip"
                title="${text}"
            >
                <i class="fa fa-fw fa-check"></i>
            </span>
        `);
        $('[data-toggle="tooltip"]').tooltip()
        $("#" + alertId).fadeIn().delay(3000).fadeOut().remove(3000);
    }, {once: true});
}

function showErrorMessage(containerId, text) {
    const alertId = "alert_" + Math.floor(Math.random() * 999999);
    $('#' + containerId).prepend(`
        <div
            id="${alertId}"
            class="alert alert-dismissible bg-danger well-sm text-center"
            style="color: white; margin-top: -50px; display: none; position: absolute;"
            data-dismiss="alert"
            role="button"
        >
            <strong><i class="fa fa-exclamation-triangle"></i></strong>&nbsp;${text}
        </div>
    `);
    $("#" + alertId).fadeIn().delay(3000).fadeOut();
}

/**
 * Fetch survey HTML from URL and replace div with {id}
 *
 * @param {string} id
 * @return {Promise}
 * @todo Deal with failure
 */
function resetContainerHtml(id) {
    const url = window.location.href;
    return $.get(
        url,
        {},
        function(newHtml, textStatus, jqXHR) {
            const doc = new DOMParser().parseFromString(newHtml, "text/html");
            const div = doc.querySelector("#" + id);
            $("#" + id).replaceWith(div);
            initEditInPlaceMisc(div);
        }
    );
}

// TODO: Can use resetContainerHtml
function resetGroupHtml(id) {
    const url = window.location.href;
    return $.get(
        url,
        {},
        function(newHtml, textStatus, jqXHR) {
            const doc = new DOMParser().parseFromString(newHtml, "text/html");
            const div = doc.querySelector("#" + id);
            $("#" + id).replaceWith(div);
            initEditInPlace();
        }
    );
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
