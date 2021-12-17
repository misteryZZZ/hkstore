const mix = require('laravel-mix');
const { exec } = require('child_process');

mix
.sass('resources/sass/valexa-front-ltr.scss', 'public/assets/front/valexa-ltr.css')
.sass('resources/sass/tendra-front-ltr.scss', 'public/assets/front/tendra-ltr.css')
.sass('resources/sass/default-front-ltr.scss', 'public/assets/front/default-ltr.css')
.sass('resources/sass/back-ltr.scss', 'public/assets/admin/app-ltr.css')
.sass('resources/sass/affiliate-ltr.scss', 'public/assets/front/affiliate-ltr.css')

.js('resources/js/tendra-front.js', 'public/assets/front/tendra.js')
.js('resources/js/valexa-front.js', 'public/assets/front/valexa.js')
.js('resources/js/default-front.js', 'public/assets/front/default.js')
.js('resources/js/back.js', 'public/assets/admin/app.js')
.options({ processCssUrls: false });

exec("rtlcss public/assets/front/tendra-ltr.css ./public/assets/front/tendra-rtl.css");
exec("rtlcss public/assets/front/valexa-ltr.css ./public/assets/front/valexa-rtl.css");
exec("rtlcss public/assets/front/default-ltr.css ./public/assets/front/default-rtl.css");
exec("rtlcss public/assets/admin/app-ltr.css ./public/assets/admin/app-rtl.css");