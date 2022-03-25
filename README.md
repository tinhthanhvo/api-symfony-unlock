# Symfony 5 REST API 

### Prerequisites

What things you need to install the software and how to install them.
- PHP 8.0
- [composer](https://getcomposer.org/download/)
- [symfony](https://symfony.com/doc/current/setup.html)
- docker

### Installing project

```bash
git clone git@github.com:tinhthanhvo/api-symfony-unlock.git
cd api-symfony-unlock
docker-compose up -d
```

#### Run require
```bash
docker exec -it application bash
composer install
```

Example - GET collection: GET http://127.0.0.1:8080/api/products
```json
{
    "status": "success",
    "code": 200,
    "message": "OK",
    "data": [
        {
          "name": "Product name",
          "description": "Product description"
        }
    ]
}
``` 