# Phoenix-Symfony

Projekt demonstracyjny łączący Phoenix (Elixir) jako backend API z Symfony (PHP) jako panel administracyjny.

## Architektura

- **Phoenix API** (Elixir 1.19 + Phoenix 1.8.1) - REST API dla zarządzania użytkownikami
- **Symfony Admin** (PHP 8.4 + Symfony 7.3) - Panel administracyjny z interfejsem web
- **PostgreSQL 18** - Baza danych
- **Docker Compose** - Orkiestracja środowiska

## Wymagania

- Docker & Docker Compose
- Git

## Instalacja

```bash
# Klonowanie repozytorium
git clone <repository-url>
cd Phoenix-Symfony

# Uruchomienie środowiska
docker-compose up -d

# Migracje bazy danych
docker-compose exec phoenix mix ecto.migrate
```

## Struktura projektu

```
├── phoenix-api/          # Backend API (Elixir/Phoenix)
├── symfony-admin/        # Panel administracyjny (PHP/Symfony)
├── docker-compose.yml    # Konfiguracja Docker
└── README.md            # Dokumentacja
```

## API Endpoints

- `GET /api/users` - Lista użytkowników
- `POST /api/users` - Tworzenie użytkownika
- `GET /api/users/:id` - Szczegóły użytkownika
- `PUT /api/users/:id` - Aktualizacja użytkownika
- `DELETE /api/users/:id` - Usunięcie użytkownika

## Panel administracyjny

Dostępny pod adresem: http://localhost:8080