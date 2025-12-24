# Epic EHR - HestiaCP Deployment Guide

These templates add Python Flask API proxying to the standard HestiaCP nginx configuration.

## Installing the Nginx Templates

### 1. Copy Templates to HestiaCP

```bash
sudo cp epic-ehr.tpl /usr/local/hestia/data/templates/web/nginx/
sudo cp epic-ehr.stpl /usr/local/hestia/data/templates/web/nginx/
```

### 2. Set Up Python Backend

```bash
# Create directory and copy backend files
sudo mkdir -p /opt/epic-ehr/backend
sudo cp -r /path/to/EPIC-PHP-EHR-System/backend/* /opt/epic-ehr/backend/

# Set up virtual environment
cd /opt/epic-ehr/backend
python3 -m venv venv
source venv/bin/activate
pip install -r requirements.txt
```

### 3. Create Systemd Service

```bash
sudo nano /etc/systemd/system/epic-ehr-api.service
```

```ini
[Unit]
Description=Epic EHR Python API Backend
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/opt/epic-ehr/backend
Environment="PATH=/opt/epic-ehr/backend/venv/bin"
ExecStart=/opt/epic-ehr/backend/venv/bin/python app.py
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable epic-ehr-api
sudo systemctl start epic-ehr-api
```

### 4. Apply Template in HestiaCP

1. Log into HestiaCP admin panel
2. Go to **WEB** section
3. Edit your domain
4. Under **Advanced Options**, select **epic-ehr** as the Nginx template
5. Save changes

### 5. Deploy Frontend

Copy the frontend files to your domain's public_html:

```bash
cp -r /path/to/EPIC-PHP-EHR-System/frontend/* /home/USER/web/DOMAIN/public_html/
```

Update the API URL in `includes/config.php`:

```php
define('API_BASE_URL', '/api');
```

## What These Templates Do

The only difference from the default HestiaCP template is adding:

```nginx
location /api/ {
    proxy_pass http://127.0.0.1:5000/api/;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}
```

This proxies all `/api/*` requests to the Python Flask backend running on port 5000.

## Troubleshooting

### 502 Bad Gateway
```bash
# Check if backend is running
systemctl status epic-ehr-api

# Check logs
journalctl -u epic-ehr-api -f
```

### API Returns 404
```bash
# Verify port 5000 is listening
netstat -tlnp | grep 5000

# Test backend directly
curl http://127.0.0.1:5000/health
```
