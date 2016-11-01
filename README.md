# Externals.io

## Setup

Externals requires [Puli](http://docs.puli.io/en/latest/) and expects it to be
available on $PATH.  If you do not have it installed you may notice a
"Plugin initialization failed" error.

```bash
$ composer install
$ cp res/config/parameters.php.dist res/config/parameters.php

# Configure database and IMAP settings

$ ./console db --force
$ ENV=dev php -S localhost:8000 -t web

# Browse to http://localhost:8000
```

[![](http://i.imgur.com/BrCb8gu.png)](http://externals.io/)

[![](http://i.imgur.com/gD7Let2.png)](http://externals.io/)
