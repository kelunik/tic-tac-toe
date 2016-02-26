let config = {
    websocketUrl: "ws://localhost:8765/ws"
};

try {
    Object.assign(config, require("./config.local.js").default);
} catch (e) { }

export default config;
