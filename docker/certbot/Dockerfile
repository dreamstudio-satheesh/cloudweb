FROM certbot/certbot:latest

# Install nginx for webroot challenges
RUN apk add --no-cache nginx

# Create directories
RUN mkdir -p /var/www/certbot

# Copy renewal script
COPY renew-certs.sh /usr/local/bin/renew-certs.sh
RUN chmod +x /usr/local/bin/renew-certs.sh

# Entry point
ENTRYPOINT ["/usr/local/bin/renew-certs.sh"]