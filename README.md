# roadrunner-ubiquity
RoadRunner and Ubiquity integration

## Installation
```
composer require lapinskas/roadrunner-ubiquity
```

### How to create and test integration
- [ ] Install php-cgi

- [ ] [Install Ubiquity](https://micro-framework.readthedocs.io/en/latest/quickstart/quickstart.html
)

- [ ] Create sample project
```
Ubiquity new firstProject -a
```

- [ ] Edit composer.json
```
    "require": {
        ...
        "lapinskas\/roadrunner-ubiquity" : "0.0.7.x-dev"
    },
```

- [ ] Update composer
```
composer update
```

- [ ] Get RoadRunner
```
vendor/bin/rr get
```

- [ ] Create RoadRunner config file .rr.yml in project root o
```
http:
  address:         ":8090"
  workers.command: "php-cgi app/worker.php"
```

- [ ] Copy worker.php file
```
cp vendor/lapinskas/roadrunner-ubiquity/sample/worker.php worker.php
```

- [ ] Run RoadRunner
```
./rr serve -v -d
```

- [ ] Open admin page of Ubiquity application
```
http://127.0.0.1:8090/Admin
```