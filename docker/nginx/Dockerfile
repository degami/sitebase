FROM nginx:alpine

ENV USER=sitebase
ENV GROUPNAME=$USER
ENV UID=1000
ENV GID=1000

RUN addgroup \
    --gid "$GID" \
    "$GROUPNAME" \
&&  adduser \
    --disabled-password \
    --gecos "" \
    --home "$(pwd)" \
    --ingroup "$GROUPNAME" \
    --no-create-home \
    --uid "$UID" \
    $USER

RUN apk add composer openssl
RUN mkdir -p /etc/nginx/certs/sitebase/
RUN openssl req -subj '/CN=local.sitebase/O=SiteBase/C=US' -new -newkey rsa:2048 -sha256 -days 365 -nodes -x509 -keyout /etc/nginx/certs/sitebase/server.key -out /etc/nginx/certs/sitebase/server.crt

CMD ["nginx"]

EXPOSE 80 443
