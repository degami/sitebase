# --- Dockerfile ngrok ---
FROM alpine:latest

# Install utilities
RUN apk add --no-cache curl bash jq unzip

ARG NGROK_VERSION="3.7.0"
RUN curl -sL https://bin.equinox.io/c/bNyj1mQVY4c/ngrok-v${NGROK_VERSION}-linux-amd64.tgz -o /tmp/ngrok.tgz \
    && tar -xzf /tmp/ngrok.tgz -C /usr/local/bin \
    && chmod +x /usr/local/bin/ngrok \
    && rm /tmp/ngrok.tgz

EXPOSE 4040

ENTRYPOINT ["sh", "-c", "ngrok config add-authtoken $NGROK_AUTHTOKEN && ngrok http web:80 --log=stdout"]

