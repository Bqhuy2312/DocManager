const express = require("express");

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
    const { scope, action, payload = {}, occurred_at: occurredAt } = req.body;

    if (!scope || !action) {
      return res.status(422).json({ message: "scope and action are required." });
    }

    io.emit("data:changed", {
      scope,
      action,
      ...payload,
      occurred_at: occurredAt,
    });

    return res.json({ ok: true });
  });

  return router;
};
