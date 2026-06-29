const { Server } = require("socket.io");

const userRoom = (userId) => `user:${userId}`;

const initSocket = (server) => {
  const frontendOrigins = (process.env.FRONTEND_URL || "http://localhost:5173")
    .split(",")
    .map((origin) => origin.trim())
    .filter(Boolean);

  const io = new Server(server, {
    cors: {
      origin: frontendOrigins,
      methods: ["GET", "POST"],
      credentials: true,
    },
  });

  io.on("connection", (socket) => {
    socket.on("user:join", (payload = {}) => {
      const userId = payload.userId || payload.user_id;
      if (!userId) return;

      socket.join(userRoom(userId));
      socket.data.userId = userId;
      socket.emit("user:joined", { userId });
    });

    socket.on("user:leave", () => {
      if (!socket.data.userId) return;
      socket.leave(userRoom(socket.data.userId));
      socket.data.userId = null;
    });
  });

  return io;
};

const emitToUser = (io, userId, event, payload) => {
  io.to(userRoom(userId)).emit(event, payload);
};

module.exports = {
  emitToUser,
  initSocket,
};
