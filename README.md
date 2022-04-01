# Symfony 5 REST API 

### Prerequisites

What things you need to install the software and how to install them.
- PHP 8.0
- [composer](https://getcomposer.org/download/)
- [symfony](https://symfony.com/doc/current/setup.html)
- docker

### Step 1: Create project already existed
```bash
git clone git@github.com:tinhthanhvo/api-symfony-unlock.git
cd api-symfony-unlock
```
### Step 2: Start docker + use container docker environment
```bash
docker-compose up -d
docker exec -it application bash
```
### Step 3: Install require
```bash
composer install
```
### Step 4: Create tables for database
```bash
bin/console doctrine:migrations:migrate
```
### Step 5: Generate the SSL keys
```bash
bin/console lexik:jwt:generate-keypair
```
### Step 6: Example - call api
#### 1. Example - Get Product list without filter options: 
#####GET http://127.0.0.1:8080/api/products
``Response:``
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
...
```
#### 2. Example - Get Product list with filter options:
##### POST http://127.0.0.1:8080/api/products/filter
``Payload:``
```json
{
  "category": 1,
  "color": 1,
  "priceFrom": "400000",
  "priceTo": "500000"
}
```
``Response:``
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
...
```
#### 3. Example - Get Product Detail by Id:
#####GET http://127.0.0.1:8080/api/products/1
``Response:``
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
