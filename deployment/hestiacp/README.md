# Epic EHR - HestiaCP Deployment Guide

## Installing the Nginx Templates

### 1. Copy Templates to HestiaCP

```bash
# SSH into your server as root
sudo cp epic-ehr.tpl /usr/local/hestia/data/templates/web/nginx/
sudo cp epic-ehr.stpl /usr/local/hestia/data/templates/web/nginx/

# Set correct permissions
sudo chmod 644 /usr/local/hestia/data/templates/web/nginx/epic-ehr.tpl
sudo chmod 644 /usr/local/hestia/data/templates/web/nginx/epic-ehr.stpl
```

### 2. Deploy the Application

```bash
# Navigate to your domain's public_html
cd /home/YOUR_USER/web/YOUR_DOMAIN/public_html

# Clone or copy the frontend files
# Option A: Clone entire repo and symlink
git clone https://github.com/YOUR_REPO/EPIC-PHP-EHR-System.git /opt/epic-ehr
ln -sf /opt/epic-ehr/frontend/* .

# Option B: Just copy frontend files
cp -r /path/to/EPIC-PHP-EHR-System/frontend/* .
```

### 3. Set Up Python Backend as a Service

Create a systemd service file:

```bash
sudo nano /etc/systemd/system/epic-ehr-api.service
```

Add the following content:

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

# Security hardening
NoNewPrivileges=true
ProtectSystem=strict
ProtectHome=true
ReadWritePaths=/opt/epic-ehr/backend

[Install]
WantedBy=multi-user.target
```

Enable and start the service:

```bash
# Install Python dependencies
cd /opt/epic-ehr/backend
python3 -m venv venv
source venv/bin/activate
pip install -r requirements.txt

# Enable and start service
sudo systemctl daemon-reload
sudo systemctl enable epic-ehr-api
sudo systemctl start epic-ehr-api

# Check status
sudo systemctl status epic-ehr-api
```

### 4. Apply the Template in HestiaCP

1. Log into HestiaCP admin panel
2. Go to **WEB** section
3. Edit your domain
4. Under **Advanced Options**, find **Nginx Template**
5. Select **epic-ehr** from the dropdown
6. Save changes

### 5. Configure Frontend

Edit the config file to match your setup:

```bash
nano /home/YOUR_USER/web/YOUR_DOMAIN/public_html/includes/config.php
```

Update the API URL:

```php
<?php
// For same-domain setup (recommended)
define('API_BASE_URL', '/api');

// Or if accessing directly (not recommended for production)
// define('API_BASE_URL', 'http://127.0.0.1:5000/api');
```

### 6. Test the Setup

```bash
# Test Python backend directly
curl http://127.0.0.1:5000/health

# Test through nginx proxy
curl https://YOUR_DOMAIN/api/patients/

# Check nginx error logs if issues
tail -f /var/log/nginx/domains/YOUR_DOMAIN.error.log
```

## Troubleshooting

### 502 Bad Gateway
- Check if Python backend is running: `systemctl status epic-ehr-api`
- Check backend logs: `journalctl -u epic-ehr-api -f`
- Verify port 5000 is listening: `netstat -tlnp | grep 5000`

### PHP Pages Not Loading
- Check PHP-FPM is running: `systemctl status php*-fpm`
- Check permissions: Files should be owned by the HestiaCP user

### API Returns 404
- Ensure the Python backend routes are registered
- Check nginx config: `nginx -t`
- Restart nginx: `systemctl restart nginx`

### Session Issues
- Ensure `/tmp` is writable
- Check PHP session settings in the template

## Production Recommendations

1. **Use SSL** - Always enable HTTPS in HestiaCP
2. **Firewall** - Block direct access to port 5000 from outside
   ```bash
   ufw deny 5000
   ```
3. **Database** - Switch from SQLite to PostgreSQL for production
4. **Backups** - Set up regular database backups
5. **Monitoring** - Set up health check monitoring for the API

## File Structure After Deployment

```
/home/USER/web/DOMAIN/
├── public_html/           # PHP frontend (nginx docroot)
│   ├── index.php
│   ├── login.php
│   ├── includes/
│   ├── activities/
│   ├── admin/
│   └── assets/
│
/opt/epic-ehr/
├── backend/               # Python API (separate location)
│   ├── app.py
│   ├── config.py
│   ├── models/
│   ├── routes/
│   ├── venv/
│   └── epic_ehr.db
```
