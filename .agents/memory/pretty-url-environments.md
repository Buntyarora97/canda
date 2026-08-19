---
name: Pretty URL environments
description: The project supports both Hostinger Apache and Replit's PHP built-in preview.
---

The production server should continue using the existing Apache `.htaccess`.
The Replit preview uses a small PHP router because the built-in PHP server does
not process `.htaccess`.

**Why:** Product, category, blog, and support links are generated as clean
paths, so testing only direct `.php` files can hide broken preview navigation.

**How to apply:** Keep production routing in `.htaccess`; update the preview
router when a new clean route is added, without changing the application's
PHP/MySQL structure.