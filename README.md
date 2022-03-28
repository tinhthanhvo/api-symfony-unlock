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

### Run in container docker environment
```bash
docker exec -it application bash
```

#### Run require
```bash
composer install
bin/console doctrine:migrations:migrate
```

#### Run test
```bash
bin/phpunit
```

###Example - GET collection: 
GET http://127.0.0.1:8080/api/products
```json
{
  "id": 1,
  "name": "HIGH HEEL SHOE EVERY LEATHER HIGH HEEL",
  "price": "400000",
  "gallery": [
    "cover.jpg",
    "notCover.jpg",
    "notCover.jpg",
    "notCover.jpg",
    "notCover.jpg"
  ]
}
``` 

###Example - GET collection: 
GET http://127.0.0.1:8080/api/products/1
```json
{
  "id": 1,
  "name": "HIGH HEEL SHOE EVERY LEATHER HIGH HEEL",
  "description": "DESCRIPTION: HIGH HEEL SHOES LEATHER HIGH HEEL - BLACK",
  "price": "400000",
  "gallery": [
    "cover.jpg",
    "notCover.jpg",
    "notCover.jpg",
    "notCover.jpg",
    "notCover.jpg"
  ],
  "color": "Black",
  "items": [
    {
      "id": 1,
      "amount": 10,
      "size": "35"
    },
    {
      "id": 9,
      "amount": 3,
      "size": "36"
    }
  ]
}
``` 
