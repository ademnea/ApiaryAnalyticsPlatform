import json
import os
import uuid
from datetime import datetime, timezone

import redis
from flask import Flask, request, jsonify

app = Flask(__name__)

REDIS_HOST = os.environ.get("ADEMNEA_REDIS_HOST", "127.0.0.1")
REDIS_PORT = int(os.environ.get("ADEMNEA_REDIS_PORT", 6379))
QUEUE_NAME = os.environ.get("ADEMNEA_IOT_QUEUE_NAME", "ademnea-iot-queue")

# TO THIS:
r = redis.Redis(host=REDIS_HOST, port=REDIS_PORT, db=0, protocol=2)


def build_envelope(resource: str) -> dict | None:
    """
    Constructs the same JSON shape a real API Gateway integration mapping
    template would produce for an SQS SendMessage action: request context
    fields alongside the untouched device payload. This exact shape is
    what the Laravel-side worker consumes — not a Laravel job object.
    """
    payload = request.get_json(silent=True)
    if payload is None:
        return None

    return {
        "requestId": str(uuid.uuid4()),
        "requestTime": datetime.now(timezone.utc).isoformat(),
        "resource": resource,
        "httpMethod": request.method,
        "sourceIp": request.remote_addr,
        "headers": {
            "X-Api-Key": request.headers.get("X-Api-Key"),
            "Content-Type": request.headers.get("Content-Type"),
        },
        "body": payload,
    }


def enqueue(resource: str):
    envelope = build_envelope(resource)
    if envelope is None:
        return jsonify({"error": "Invalid or missing JSON body"}), 400

    # LPUSH mirrors SQS SendMessage — the Laravel worker consumes with
    # BRPOP, giving FIFO-ish ordering for this mock (SQS standard queues
    # don't guarantee strict ordering either, so this is a fair analogue).
    r.lpush(QUEUE_NAME, json.dumps(envelope))

    return jsonify({"status": "queued", "requestId": envelope["requestId"]}), 202


@app.route("/ingest", methods=["POST"])
def ingest():
    return enqueue("/ingest")


@app.route("/heartbeat", methods=["POST"])
def heartbeat():
    return enqueue("/heartbeat")


@app.route("/media/confirm", methods=["POST"])
def media_confirm():
    return enqueue("/media/confirm")


if __name__ == "__main__":
    app.run(port=8085, debug=False)