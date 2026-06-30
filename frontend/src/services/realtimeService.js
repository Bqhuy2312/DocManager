import { io } from "socket.io-client";

const realtimeUrl = import.meta.env.VITE_REALTIME_URL || "http://127.0.0.1:3001";

let socket = null;
const activityHandlers = new Set();

const ensureSocket = () => {
  if (!socket) {
    socket = io(realtimeUrl, {
      autoConnect: false,
      transports: ["websocket", "polling"],
    });

    socket.on("activity:new", (payload) => {
      activityHandlers.forEach((handler) => handler(payload));
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

export const disconnectRealtime = () => {
  if (!socket) return;
  socket.emit("user:leave");
  socket.disconnect();
  activityHandlers.clear();
  socket = null;
};

export const isRealtimeConnected = () => Boolean(socket?.connected);
