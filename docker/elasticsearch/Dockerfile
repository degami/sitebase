# Partiamo dall'immagine ufficiale OpenSearch 2.9.0
FROM opensearchproject/opensearch:2.9.0

# Disabilitiamo i plugin di sicurezza per lo sviluppo locale
ENV OPENSEARCH_JAVA_OPTS="-Xms512m -Xmx512m"

# Configurazioni base
COPY opensearch.yml /usr/share/opensearch/config/opensearch.yml

# Espone la porta HTTP standard
EXPOSE 9200

# L'utente è già configurato nell'immagine ufficiale
# Non serve creare utenti o gruppi

# Comando di avvio
CMD ["opensearch"]
