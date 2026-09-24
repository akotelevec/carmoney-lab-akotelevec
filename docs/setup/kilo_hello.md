готов

1) Учебный сервис предварительной оценки заявки на заём под ПТС (carmoney-lab, практикум М3): принимает заявку (VIN, год, пробег, оценочная стоимость, сумма, срок), считает LTV и возвращает решение approve / review / reject.
2) Makefile: `make up` (docker compose up -d --build), `make down`, `make test` (PHPUnit локально или в контейнере), `make lint` (php -l), `make seed` (mysql < db/seed.sql), `make logs`, `make ps`, `make install`, `make help`; docker-compose.yml: сервис `backend` (php -S 0.0.0.0:8080) и `db` (mysql:8.0), порт хоста через APP_PORT (по умолчанию 8080), healthcheck БД, том db-data.
3) В `backend/src/Domain/` — классы правил (`LtvCalculator`, `DecisionEngine`, `AssessmentService`, `ApplicationValidator`, `VehicleAge`, `VinValidator`, `ValidationException`); пороги и лимиты — в `backend/config/rules.php` (сборка решения там же).

модель: training-2026-09-minimax-m3