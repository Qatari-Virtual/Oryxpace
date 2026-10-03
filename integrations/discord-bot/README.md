# Discord Bot Notification Server

HTTP endpoint for your Python Discord bot to receive task notifications from Laravel and send DMs to users.

## Architecture

```
┌─────────────────┐         HTTP POST         ┌─────────────────────┐
│   Laravel       │ ──────────────────────────► │  Python Bot         │
│   (Oryxpace)    │   /notify                   │  (notification_     │
│                 │   {discord_user_id,          │   server.py)        │
│                 │    task_title, ...}          │                     │
└─────────────────┘                             └──────────┬──────────┘
                                                           │
                                                           │ Discord API
                                                           ▼
                                                   ┌───────────────┐
                                                   │  Discord DM   │
                                                   │  to User      │
                                                   └───────────────┘
```

## Setup

### 1. Install dependencies

```bash
cd integrations/discord-bot
pip install -r requirements.txt
```

### 2. Integrate with your bot

Add to your bot's main file:

```python
import discord
import threading
from notification_server import start_notification_server

intents = discord.Intents.default()
intents.members = True

bot = discord.Client(intents=intents)

# Start notification server in background
server_thread = threading.Thread(
    target=start_notification_server,
    args=(bot, "0.0.0.0", 8080),
    daemon=True
)
server_thread.start()

@bot.event
async def on_ready():
    print(f"Bot ready as {bot.user}")

bot.run("YOUR_BOT_TOKEN")
```

### 3. Configure Laravel

Add to your Laravel `.env`:

```env
DISCORD_BOT_ENDPOINT=http://127.0.0.1:8080
```

### 4. Verify Discord IDs

Pilots should have their Discord user ID set in the `discordid` column of the `pilots` table. They can update this in the notification settings page, or you can verify existing data:

```sql
SELECT id, callsign, name, discordid FROM pilots WHERE discordid IS NOT NULL;
```

## API Endpoint

### POST /notify

Send a task notification DM to a Discord user.

**Request:**
```json
{
  "discord_user_id": "123456789012345678",
  "pilot_id": 1,
  "pilot_name": "John Doe",
  "task_id": 42,
  "task_title": "Fix login bug",
  "task_url": "https://oryxpace.example.com/tasks/42",
  "project_name": "Backend Improvements",
  "event_type": "task_assigned",
  "message": "You have been assigned to this task."
}
```

**Response:**
```json
{
  "status": "queued",
  "discord_user_id": "123456789012345678"
}
```

### GET /health

Check if the bot is connected and ready.

**Response:**
```json
{
  "status": "ok",
  "bot_ready": true
}
```

## Event Types

| Event Type | Description | Embed Color |
|------------|-------------|-------------|
| `task_assigned` | User was assigned to a task | Purple (#6366F1) |
| `task_updated` | Task was updated | Blue (#3B82F6) |
| `task_moved` | Task moved between columns | Amber (#F59E0B) |

## Troubleshooting

**DMs not being received:**
- Ensure users have DMs enabled for the server
- Bot must share a server with the user to send DMs
- Check bot has proper intents enabled in Discord Developer Portal

**Connection refused from Laravel:**
- Verify `DISCORD_BOT_ENDPOINT` matches the host/port
- Ensure the bot process is running
- Check firewall rules if using non-localhost binding
