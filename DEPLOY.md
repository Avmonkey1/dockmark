# Deploy Dockmark on a VPS

Dockmark is a plain PHP app, so deployment is simple: upload it, point a domain/subdomain at it, and make the JSON data file writable.

## Option A: Subdomain on an existing VPS

1. Create a subdomain such as `start.williamlodge.com`, `desk.williamlodge.com`, or `dock.williamlodge.com`.
2. Point the subdomain's DNS `A` record to your VPS IP.
3. Create a web root, for example:

```bash
sudo mkdir -p /var/www/dockmark
```

4. Upload the project files into that folder.
5. Make the data folder writable by the web server:

```bash
sudo chown -R www-data:www-data /var/www/dockmark/data
sudo chmod 775 /var/www/dockmark/data
```

6. Copy the config file and set a real password:

```bash
cd /var/www/dockmark
cp config.example.php config.php
nano config.php
```

7. Create an Nginx site:

```nginx
server {
    server_name start.example.com;
    root /var/www/dockmark;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    }

    location ~* /(data|config\.php) {
        deny all;
    }
}
```

8. Enable HTTPS:

```bash
sudo certbot --nginx -d start.example.com
```

## Option B: Dedicated app domain

A `.app` domain is nice because it signals "product," but `.app` requires HTTPS. That is fine with Certbot or Cloudflare.

Good naming directions to check at a registrar:

- `dockmark.app`
- `usedockmark.com`
- `dockmark.dev`
- `startdock.app`
- `opendock.app`
- `markdock.app`

Check trademark/search results before buying. Domain availability changes constantly.

## Project website

The static project page lives in `website/`. For a public repo, it can be served as:

```text
https://dockmark.app/
```

while the app itself can live at:

```text
https://app.dockmark.app/
```

or, for personal hosting:

```text
https://dock.williamlodge.com/
```
