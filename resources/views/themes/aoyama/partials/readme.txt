Theme screens (Blade):
  pages/landing.blade.php   — homepage (template: landing)
  pages/default.blade.php   — inner pages
  dynamic/blog.blade.php    — お知らせ archive
  dynamic/post.blade.php    — single news
  dynamic/404.blade.php     — not found

Partials (include with @include('themes.aoyama.partials.NAME')):
  header, footer, hero

Assets (public via /themes/aoyama/assets/...):
  assets/theme.css  assets/theme.js  assets/images/*

Layout component:
  resources/views/components/layout/aoyama.blade.php  → <x-layout.aoyama>
