# BOS (Business OS)

## Getting started

### Install backend

Open a terminal and navigate to the `backend` directory:

```sh
cd backend
cp .env.example .env

composer install
php artisan key:generate
php artisan migrate --seed

npm install
npm run build
php artisan serve
```

```sh
php atisan test
```

### Install frontend

1. Open a new terminal and navigate to the `frontend` directory:

```sh
cd frontend

npm install

npm run build

# or

npm run dev
```

```sh
npm run test:run

# or

npm run test:run tests/utils/ tests/integration/
```

The frontend will be available at [http://localhost:3000](http://localhost:3000) by default.
