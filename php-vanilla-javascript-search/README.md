# PHP and Vanilla JS vehicle browser

Simple app to load makes, models, vehicles then details.

To keep your api secret from being public, it should be placed into `api.ini`
(which you can make by copying `app.ini.default` and updating it to use your credentials)

Keep the `api.ini` _outside_ of your public web root, and update the path in the `api.php`
so it's only accessible from PHP on the server side.

Do not expose your api secret publically.