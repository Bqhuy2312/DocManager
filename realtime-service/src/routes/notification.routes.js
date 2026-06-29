const express = require("express");
const { emitToUser } = require("../socket");

const authorizeLaravel = (req, res, next) => {
  const configuredSecret = process.env.REALTIME_SERVICE_SECRET;
  const requestSecret = req.header("x-realtime-secret");

  if (!configuredSecret || requestSecret !== configuredSecret) {
    return res.status(401).json({ message: "Unauthorized realtime request." });
  }

  return next();
};

module.exports = (io) => {
  const router = express.Router();

  router.post("/", authorizeLaravel, (req, res) => {
    const { user_id: userId, event = "notification:new", notification, unread_count: unreadCount } = req.body;

    if (!userId) {
      return res.status(422).json({ message: "user_id is required." });
    }

    emitToUser(io, userId, event, {
      notification,
      unread_count: unreadCount,
    });

    return res.json({ ok: true });
  });

  router.post("/state", authorizeLaravel, (req, res) => {
    const { user_id: userId, unread_count: unreadCount } = req.body;

    if (!userId) {
      return res.status(422).json({ message: "user_id is required." });
    }

    emitToUser(io, userId, "notification:state", {
      unread_count: unreadCount,
    });

    return res.json({ ok: true });
  });

  return router;
};
