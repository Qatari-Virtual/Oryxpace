"""
Oryxpace Discord Bot - Notification HTTP Server

This module adds an HTTP endpoint to your Discord bot that accepts
task notification requests from the Laravel application and sends
DMs to the appropriate Discord users.

Integration:
    1. Import and start the server alongside your bot:
    
        from notification_server import start_notification_server
        import threading
        
        # Start HTTP server in background thread
        server_thread = threading.Thread(
            target=start_notification_server,
            args=(bot, "0.0.0.0", 8080),
            daemon=True
        )
        server_thread.start()
        
        # Run your bot normally
        bot.run(TOKEN)

    2. Set DISCORD_BOT_ENDPOINT in Laravel .env:
    
        DISCORD_BOT_ENDPOINT=http://127.0.0.1:8080

    3. Ensure your bot has the "Message Content" intent and permissions
       to send DMs to users in shared servers.

Dependencies:
    - discord.py
    - Flask (pip install flask)
"""

import logging
from typing import Optional

import discord
from flask import Flask, jsonify, request

logger = logging.getLogger("oryxpace.notifications")


def create_notification_app(bot: discord.Client) -> Flask:
    """Create Flask app with notification endpoint."""
    app = Flask(__name__)

    @app.route("/health", methods=["GET"])
    def health():
        return jsonify({"status": "ok", "bot_ready": not bot.is_closed()})

    @app.route("/notify", methods=["POST"])
    def notify():
        if bot.is_closed():
            return jsonify({"error": "Bot is not connected"}), 503

        data = request.get_json()
        if not data:
            return jsonify({"error": "No JSON body provided"}), 400

        discord_user_id = data.get("discord_user_id")
        if not discord_user_id:
            return jsonify({"error": "discord_user_id is required"}), 400

        task_title = data.get("task_title", "Unknown Task")
        task_url = data.get("task_url", "")
        project_name = data.get("project_name", "")
        message = data.get("message", "")
        event_type = data.get("event_type", "task_updated")

        try:
            user_id = int(discord_user_id)
        except (ValueError, TypeError):
            return jsonify({"error": "Invalid discord_user_id format"}), 400

        embed = _build_embed(
            task_title=task_title,
            task_url=task_url,
            project_name=project_name,
            message=message,
            event_type=event_type,
        )

        bot.loop.create_task(_send_dm(bot, user_id, embed))

        return jsonify({"status": "queued", "discord_user_id": discord_user_id})

    return app


async def _send_dm(bot: discord.Client, user_id: int, embed: discord.Embed) -> None:
    """Send DM to user, handling cases where DMs are disabled."""
    try:
        user = bot.get_user(user_id)
        if user is None:
            user = await bot.fetch_user(user_id)

        if user is None:
            logger.warning(f"Could not find Discord user: {user_id}")
            return

        await user.send(embed=embed)
        logger.info(f"Sent notification DM to {user} ({user_id})")

    except discord.Forbidden:
        logger.warning(
            f"Cannot DM user {user_id} - DMs may be disabled for this server"
        )
    except discord.HTTPException as e:
        logger.error(f"Failed to send DM to {user_id}: {e}")
    except Exception as e:
        logger.error(f"Unexpected error sending DM to {user_id}: {e}")


def _build_embed(
    task_title: str,
    task_url: str,
    project_name: Optional[str],
    message: str,
    event_type: str,
) -> discord.Embed:
    """Build a rich embed for the notification."""
    color_map = {
        "task_assigned": 0x6366F1,
        "task_updated": 0x3B82F6,
        "task_moved": 0xF59E0B,
    }
    color = color_map.get(event_type, 0x6366F1)

    embed = discord.Embed(
        title="Oryxpace Task Notification",
        description=message,
        color=color,
    )

    embed.add_field(name="Task", value=f"[{task_title}]({task_url})" if task_url else task_title, inline=False)

    if project_name:
        embed.add_field(name="Project", value=project_name, inline=True)

    embed.set_footer(text="Oryxpace")

    return embed


def start_notification_server(bot: discord.Client, host: str = "0.0.0.0", port: int = 8080) -> None:
    """Start the notification HTTP server (blocking)."""
    app = create_notification_app(bot)
    logger.info(f"Starting notification server on {host}:{port}")
    app.run(host=host, port=port, use_reloader=False)
