import React from "react";
import { render } from "react-dom";
import Game from "./components/Game";
import config from "./config";

const container = document.createElement("div");
document.body.appendChild(container);

const ws = new WebSocket(config.websocketUrl);

render((
    <Game ws={ws}/>
), container);