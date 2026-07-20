import { io } from "socket.io-client";

const realtimeUrl = import.meta.env.VITE_REALTIME_URL || "http://127.0.0.1:3001";

let socket = null;
const activityHandlers = new Set();
const dataHandlers = new Set();

const ensureSocket = () => {
  if (!socket) {
    socket = io(realtimeUrl, {
      autoConnect: false,
      transports: ["websocket", "polling"],
    });

    socket.on("activity:new", (payload) => {
      activityHandlers.forEach((handler) => handler(payload));
    });

    socket.on("data:changed", (payload) => {
      dataHandlers.forEach((subscription) => {
        if (!subscription.scopes.has("*") && !subscription.scopes.has(payload?.scope)) return;

        window.clearTimeout(subscription.timer);
        subscription.timer = window.setTimeout(() => subscription.handler(payload), subscription.delay);
      });
    });
  }

  return socket;
};

export const connectRealtime = (userId, handlers = {}) => {
  if (!userId) return null;

  const activeSocket = ensureSocket();

  socket.off("notification:new");
  socket.off("notification:state");
  socket.off("connect");

  socket.on("connect", () => {
    socket.emit("user:join", { userId });
    handlers.onConnect?.();
  });

  socket.on("notification:new", (payload) => {
    handlers.onNotification?.(payload);
  });

  socket.on("notification:state", (payload) => {
    handlers.onNotificationState?.(payload);
  });

  if (socket.connected) {
    socket.emit("user:join", { userId });
    handlers.onConnect?.();
  } else {
    activeSocket.connect();
  }

  return activeSocket;
};

export const subscribeRealtimeActivity = (handler) => {
  if (typeof handler !== "function") return () => {};

  const activeSocket = ensureSocket();
  activityHandlers.add(handler);

  if (!activeSocket.connected) {
    activeSocket.connect();
  }

  return () => {
    activityHandlers.delete(handler);
  };
};

export const subscribeRealtimeData = (scopes, handler, delay = 350) => {
  if (typeof handler !== "function") return () => {};

  const activeSocket = ensureSocket();
  const normalizedScopes = Array.isArray(scopes) ? scopes : [scopes];
  const subscription = {
    scopes: new Set(normalizedScopes.filter(Boolean)),
    handler,
    delay,
    timer: null,
  };

  dataHandlers.add(subscription);

  if (!activeSocket.connected) {
    activeSocket.connect();
  }

  return () => {
    window.clearTimeout(subscription.timer);
    dataHandlers.delete(subscription);
  };
};

export const disconnectRealtime = () => {
  if (!socket) return;
  socket.emit("user:leave");
  socket.disconnect();
  activityHandlers.clear();
  dataHandlers.forEach((subscription) => window.clearTimeout(subscription.timer));
  dataHandlers.clear();
  socket = null;
};

export const isRealtimeConnected = () => Boolean(socket?.connected);
