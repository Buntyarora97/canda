# Voltiva Electric Mobility — PHP + MySQL

This is a shared-hosting-ready PHP 8 storefront with an enquiry-led buying flow.
There is no online payment or Node.js build step: customers browse products,
click **Buy Now**, submit an enquiry, and the lead is saved in MySQL and shown
inside the admin CRM.

## cPanel / phpMyAdmin installation

1. Create a MySQL database and database user in cPanel. Grant the user all
   privileges on the database.
2. Upload the complete project into the domain's `public_html` directory.
3. Make sure `uploads/products`, `uploads/banners`, `uploads/categories`,
   `uploads/posts`, `uploads/manuals`, and `uploads/reviews` are writable by PHP
   (normally `755` or `775`, depending on the host).
4. Import `database.sql` using phpMyAdmin. Import it into the database you
   created; do not add a `CREATE DATABASE` statement on shared hosting.
5. Configure the database connection using environment variables, a
   `gio-config.php` file one directory above the public web root, or
   `includes/config.local.php`. The supported variables are:
   `GIO_DB_HOST`, `GIO_DB_NAME`, `GIO_DB_USER`, `GIO_DB_PASS`, and optional
   `GIO_SITE_URL`.
6. Set a long random `GIO_APP_KEY` in the hosting environment, or define
   `APP_KEY` in the outside-web-root config file. Never publish credentials in
   a public PHP file.
7. Open `/admin/`, sign in with the admin account created by the SQL seed, and
   change the password immediately under **Settings**. Configure the business
   notification address before accepting enquiries.

## Enquiries and email

The Buy Now button does not charge a card. It opens the lead form with the
selected product, colour, variant, source URL, and campaign fields attached.
The server validates the product again, saves the enquiry before attempting
email, and records email delivery status if SMTP is missing or fails.

Configure SMTP from **Admin → Settings**, or put SMTP values in the
outside-web-root config. The database lead is never lost because an email
transport is unavailable.

## Shared hosting requirements

- PHP 8.0+ with PDO MySQL, mbstring, GD (recommended for image uploads), and
  standard session support.
- MySQL 8+ or MariaDB 10.4+.
- Apache with `mod_rewrite`; the included `.htaccess` provides the clean product,
  shop, category, support, and blog URLs.
- HTTPS enabled on the domain. Uncomment the HTTPS redirect in `.htaccess`
  after SSL is active.

## Admin checklist

- Products: edit product copy, price, stock status, variants, specs, images,
  related products, SEO and homepage flags.
- Enquiries: open the lead, update its status, add internal notes, and review
  email delivery warnings.
- Settings: configure the store contact details, announcement bar, business
  email, acknowledgement email, SMTP, and social links.
- Banners, FAQs, manuals, blog posts, categories and reviews are editable in
  the admin area.

## Local preview

The project is intentionally plain PHP and can be previewed with:

```text
php -S 0.0.0.0:5000
```

It needs a reachable MySQL/MariaDB database for catalogue pages and forms.
For production, use the database and credentials supplied by your shared host.

## Going live

- Replace demo product copy, imagery, prices and specifications with verified
  client-approved information.
- Replace the seeded admin password and configure SMTP.
- Set `GIO_SITE_URL`, enable HTTPS, test the enquiry form, and confirm the lead
  appears in Admin → Enquiries even when email delivery is intentionally
  unavailable.
- Take a database backup before importing future schema changes.

For a complete Hostinger + phpMyAdmin upload and configuration walkthrough,
see [HOSTINGER_DEPLOYMENT.md](HOSTINGER_DEPLOYMENT.md).