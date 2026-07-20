const http = require("http");
const express = require("express");
const cors = require("cors");
const dotenv = require("dotenv");
const activityRoutes = require("./routes/activity.routes");
const notificationRoutes = require("./routes/notification.routes");
const dataRoutes = require("./routes/data.routes");
const { initSocket } = require("./socket");

dotenv.config({ path: `${__dirname}/.env` });

const app = express();
const server = http.createServer(app);
const io = initSocket(server);

const frontendOrigins = (process.env.FRONTEND_URL || "http://localhost:5173")
  .split(",")
  .map((origin) => origin.trim())
  .filter(Boolean);
const port = Number(process.env.PORT || 3001);

app.use(cors({ origin: frontendOrigins, credentials: true }));
app.use(express.json());

app.get("/health", (req, res) => {
  res.json({ status: "ok" });
});

app.use("/api/activities", activityRoutes(io));
app.use("/api/notifications", notificationRoutes(io));
app.use("/api/data-changes", dataRoutes(io));

server.listen(port, () => {
  console.log(`Realtime service listening on http://127.0.0.1:${port}`);
});
