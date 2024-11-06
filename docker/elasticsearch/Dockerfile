# Utilizziamo l'immagine Alpine più recente
FROM alpine:latest

# Definiamo le variabili per l'utente e il gruppo
ENV USER=sitebase
ENV GROUPNAME=$USER
ENV UID=1000
ENV GID=1000
ENV OPENSEARCH_VERSION=2.9.0

# Installiamo OpenJDK separatamente
RUN apk add --no-cache openjdk11 bash curl

# Configura JAVA_HOME per usare il JDK di sistema
ENV JAVA_HOME=/usr/lib/jvm/java-11-openjdk
ENV PATH="${JAVA_HOME}/bin:${PATH}"

# Creiamo il gruppo e l'utente con UID e GID specifici
RUN addgroup -g $GID $GROUPNAME && \
    adduser -u $UID -G $GROUPNAME -s /bin/bash -D $USER

# Scarichiamo e installiamo OpenSearch nella directory desiderata
RUN curl -L https://artifacts.opensearch.org/releases/bundle/opensearch/$OPENSEARCH_VERSION/opensearch-$OPENSEARCH_VERSION-linux-x64.tar.gz \
    -o opensearch.tar.gz && \
    mkdir -p /usr/share/opensearch && \
    tar -xzf opensearch.tar.gz --strip-components=1 -C /usr/share/opensearch && \
    rm opensearch.tar.gz && \
    chown -R $USER:$GROUPNAME /usr/share/opensearch

# Configurazione per disabilitare SSL nel trasporto e il plugin di sicurezza
RUN echo "plugins.security.disabled: true" >> /usr/share/opensearch/config/opensearch.yml && \
    echo "plugins.security.ssl.transport.enabled: false" >> /usr/share/opensearch/config/opensearch.yml && \
    echo "plugins.security.ssl.http.enabled: false" >> /usr/share/opensearch/config/opensearch.yml

# Passiamo all'utente configurato
USER $USER

# Impostiamo la directory di lavoro
WORKDIR /usr/share/opensearch

# Espone la porta 9200 (default per OpenSearch)
EXPOSE 9200

# Eseguiamo OpenSearch
CMD ["./bin/opensearch"]
