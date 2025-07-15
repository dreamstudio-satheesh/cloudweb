#!/bin/sh

# Wait for nginx to be ready
sleep 10

# Check if certificates exist
if [ ! -f "/etc/letsencrypt/live/cloudwebservers.com/fullchain.pem" ]; then
    echo "Obtaining SSL certificates..."
    
    # Get certificate for main domain and API subdomain
    certbot certonly \
        --webroot \
        --webroot-path=/var/www/certbot \
        --email admin@cloudwebservers.com \
        --agree-tos \
        --no-eff-email \
        --force-renewal \
        -d cloudwebservers.com \
        -d www.cloudwebservers.com \
        -d api.cloudwebservers.com
        
    echo "SSL certificates obtained!"
else
    echo "SSL certificates already exist, renewing..."
    certbot renew --webroot --webroot-path=/var/www/certbot
fi

# Keep container running for renewals
while true; do
    sleep 12h
    certbot renew --webroot --webroot-path=/var/www/certbot
done