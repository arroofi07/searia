<?php
/**
 * Hanya dipakai jika SELURUH project ada di public_html
 * dan document root TIDAK bisa diubah ke public_html/public.
 *
 * Letakkan file ini sebagai: public_html/index.php
 * Lalu salin juga .htaccess dari public/ ke public_html/.htaccess
 * (atau pakai public_html_root.htaccess di repo).
 *
 * Lebih aman: di cPanel ubah Document Root domain ke public_html/public.
 */

require __DIR__.'/public/index.php';
