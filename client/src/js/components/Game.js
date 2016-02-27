import React from "react";
import ReactDOM from "react-dom";
import classNames from "classnames";

/**
 * @author Niklas Keller
 */
class Game extends React.Component {
    constructor() {
        super();

        this.state = {
            waiting: true,
            aborted: false,
            finished: false,
            turn: false,
            fields: [
                [0, 0, 0],
                [0, 0, 0],
                [0, 0, 0]
            ]
        };
    }

    componentDidMount() {
        this.props.ws.addEventListener("message", this.onMessage.bind(this));

        let fn = () => {
            this.props.ws.send('{"get": null}');
            this.props.ws.removeEventListener("open", fn);
        };

        if (this.props.ws.readyState === WebSocket.OPEN) {
            fn();
        } else {
            this.props.ws.addEventListener("open", fn);
        }
    }

    componentWillUnmount() {
        this.props.ws.removeEventListener("message", this.onMessage.bind(this));
    }

    onMessage(e) {
        try {
            let data = JSON.parse(e.data);

            if (data.type === "game.state") {
                if (data.data.fields === null) {
                    this.setState({
                        waiting: true
                    });

                    this.props.ws.send('{"start": null}');

                    return;
                }

                this.setState({
                    waiting: false,
                    fields: data.data.fields,
                    turn: data.data.turn
                });
            } else if (data.type === "game.end") {
                this.setState({
                    waiting: false,
                    turn: false,
                    finished: true,
                    aborted: false
                });
            } else if (data.type === "game.abort") {
                this.setState({
                    waiting: false,
                    aborted: true,
                    finished: true,
                    turn: false
                });
            }
        } catch (e) {
            console.error(e);
        }
    }

    render() {
        let rows = this.state.fields.map((row, y) => {
            let items = row.map((item, x) => {
                return <span key={"item:" + x + ":" + y + ":" + item} className={classNames({
                    "cell": true,
                    "cell-one": item === 1,
                    "cell-two": item === 2,
                    "cell-pick": item === 0 && this.state.turn
                })} onClick={(e) => this.onClick(x, y)}>&nbsp;</span>
            });

            return (
                <div key={"row:" + y}>
                    {items}
                </div>
            );
        });

        let reload = null;

        if (this.state.finished) {
            reload = (
                <div className="reload" onClick={this.onRefresh.bind(this)}>
                    <i className="fa fa-refresh"/>

                    {this.state.aborted ? <div>Your opponent left.</div> : null}
                </div>
            );
        }

        let waiting = null;

        if (this.state.waiting) {
            waiting = (
                <div className="reload">
                    <i className="fa fa-clock-o"/>
                    <div>Waiting for<br/>another player.</div>
                </div>
            );
        }

        return (
            <div className="game">
                <div className={classNames({
                    "fields": true,
                    "fields-turn": this.state.turn
                })}>
                    {rows}
                    {reload}
                    {waiting}
                </div>
            </div>
        );
    }

    onClick(x, y) {
        if (this.state.finished) {
            return;
        }

        this.props.ws.send(JSON.stringify({
            set: [x, y]
        }));
    }

    onRefresh() {
        this.setState({
            waiting: true,
            finished: false,
            turn: false,
            fields: [
                [0, 0, 0],
                [0, 0, 0],
                [0, 0, 0]
            ]
        });

        this.props.ws.send('{"start": null}');
    }
}

export default Game;