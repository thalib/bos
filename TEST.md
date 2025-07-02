```
php artisan cache:clear && php artisan config:clear && php artisan route:clear
```

## tail
```
Get-Content storage/logs/laravel.log -Tail 20
```

Backend Test

```
php artisan test tests/Feature/AuthenticationRoutesTest.php
php artisan test tests/Feature/ResourceRoutesSecurityTest.php

--stop-on-failure
```