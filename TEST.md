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

## PDF Generation

To test your templates, you can now:


```
php artisan serve
```

Visit these URLs in your browser to see HTML previews:

http://localhost:8000/test-template/receipt
http://localhost:8000/test-template/invoice
http://localhost:8000/test-template/estimate
http://localhost:8000/test-template/report

```
php artisan pdf:list
php artisan pdf:make estimate

# This will use the default demo data in storage/app/demo-invoice.json
php artisan pdf:make invoice

php artisan pdf:make report --preview

php artisan pdf:make receipt --output=public/downloads --filename=customer_receipt
```
