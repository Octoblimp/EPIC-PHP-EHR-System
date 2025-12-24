#========================================================================
# Epic EHR System - HestiaCP Nginx Template
# PHP Frontend + Python Flask API Backend
#========================================================================
# Installation:
# 1. Copy this file to: /usr/local/hestia/data/templates/web/nginx/
# 2. Copy epic-ehr.stpl to the same directory (for SSL)
# 3. In HestiaCP, edit the domain and select "epic-ehr" as the nginx template
# 4. Ensure Python backend is running on port 5000
#========================================================================

server {
    listen      %ip%:%web_port%;
    server_name %domain_idn% %alias_idn%;
    root        %docroot%;
    index       index.php index.html index.htm;
    access_log  /var/log/nginx/domains/%domain%.log combined;
    access_log  /var/log/nginx/domains/%domain%.bytes bytes;
    error_log   /var/log/nginx/domains/%domain%.error.log error;

    include %home%/%user%/conf/web/%domain%/nginx.forcessl.conf*;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml application/json application/javascript application/xml+rss application/atom+xml image/svg+xml;

    # Static assets caching
    location ~* \.(jpg|jpeg|gif|png|ico|cur|gz|svg|svgz|mp4|ogg|ogv|webm|htc|woff|woff2|ttf|eot)$ {
        expires 1M;
        access_log off;
        add_header Cache-Control "public";
        try_files $uri =404;
    }

    location ~* \.(css|js)$ {
        expires 7d;
        access_log off;
        add_header Cache-Control "public";
        try_files $uri =404;
    }

    #========================================================================
    # Python Flask API Proxy - Routes /api/* to Python backend
    #========================================================================
    location /api/ {
        proxy_pass http://127.0.0.1:5000/api/;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-Host $host;
        proxy_set_header X-Forwarded-Port $server_port;
        
        # WebSocket support (if needed)
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        
        # Timeouts for long-running requests
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
        
        # Buffer settings
        proxy_buffering on;
        proxy_buffer_size 4k;
        proxy_buffers 8 4k;
        proxy_busy_buffers_size 8k;
        
        # Don't cache API responses
        add_header Cache-Control "no-store, no-cache, must-revalidate";
    }

    # FHIR API endpoint
    location /api/fhir/ {
        proxy_pass http://127.0.0.1:5000/api/fhir/;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
        add_header Cache-Control "no-store, no-cache, must-revalidate";
    }

    # Health check endpoint (direct to Python)
    location /health {
        proxy_pass http://127.0.0.1:5000/health;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        access_log off;
    }

    #========================================================================
    # PHP Processing
    #========================================================================
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_pass %backend_lsnr%;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PHP_VALUE "
            open_basedir=%docroot%:/tmp:/var/tmp:/usr/share/pear:/usr/share/php
            session.save_path=/tmp
        ";
        include fastcgi_params;
        fastcgi_intercept_errors on;
        
        # PHP timeout settings
        fastcgi_connect_timeout 60;
        fastcgi_send_timeout 300;
        fastcgi_read_timeout 300;
    }

    #========================================================================
    # Security - Block sensitive files
    #========================================================================
    location ~ /\.ht {
        deny all;
    }

    location ~ /\.git {
        deny all;
    }

    location ~ /\.env {
        deny all;
    }

    location ~ /(config\.php|config\.py|requirements\.txt)$ {
        deny all;
    }

    # Block access to backend directory if it's in docroot
    location ^~ /backend/ {
        deny all;
    }

    #========================================================================
    # Error pages
    #========================================================================
    error_page 502 503 504 /50x.html;
    location = /50x.html {
        root /usr/share/nginx/html;
        internal;
    }

    include %home%/%user%/conf/web/%domain%/nginx.conf_*;
}
