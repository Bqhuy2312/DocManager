import { io } from "socket.io-client";

const realtimeUrl = import.meta.env.VITE_REALTIME_URL || "http://127.0.0.1:3001";

let socket = null;

export const connectRealtime = (userId, handlers = {}) => {
  if (!userId) return null;

  if (!socket) {
    socket = io(realtimeUrl, {
      autoConnect: false,
      transports: ["websocket", "polling"],
    });
  }

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
    socket.connect();
  }

  return socket;
};

export const disconnectRealtime = () => {
  if (!socket) return;
  socket.emit("user:leave");
  socket.disconnect();
  socket = null;
};

export const isRealtimeConnected = () => Boolean(socket?.connected);
