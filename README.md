# Phoenix-Symfony

Projekt demonstracyjny łączący Phoenix (Elixir) jako backend API z Symfony (PHP) jako panel administracyjny.

## 🏗️ Architektura

- **Phoenix API** (Elixir 1.18 + Phoenix 1.8.1) - REST API dla zarządzania użytkownikami
- **Symfony Admin** (PHP 8.4 + Symfony 7.3) - Panel administracyjny z interfejsem web
- **PostgreSQL 17** - Baza danych z polską lokalizacją
- **Docker Compose** - Orkiestracja środowiska

## 📋 Wymagania

- **Docker** >= 20.10
- **Docker Compose** >= 2.0
- **Git**
- **Minimum 4GB RAM** (dla wszystkich kontenerów)

## 🚀 Szybki start

### 1. Klonowanie repozytorium

```bash
git clone git@github.com:MichaelAngelo12321/Phoenix-Symfony.git
cd Phoenix-Symfony
```

### 2. Uruchomienie środowiska

```bash
# Budowanie i uruchomienie wszystkich serwisów
docker-compose up -d

# Sprawdzenie statusu kontenerów
docker-compose ps
```

### 3. Instalacja zależności w kontenerach

```bash
# Instalacja zależności Phoenix (Elixir) w kontenerze
docker-compose exec phoenix mix deps.get

# Instalacja zależności Symfony (PHP) w kontenerze
docker-compose exec symfony composer install

# Skopiowanie pliku konfiguracyjnego środowiska
docker-compose exec symfony cp .env.dev .env
```

### 4. Inicjalizacja bazy danych

```bash
# Migracje Phoenix API
docker-compose exec phoenix mix ecto.migrate
```

### 5. Dostęp do aplikacji

- **Phoenix API**: http://localhost:4000
- **Symfony Admin**: http://localhost:8080
- **PostgreSQL**: localhost:5432 (postgres/postgres)

## 📁 Struktura projektu

```
Phoenix-Symfony/
├── phoenix-api/              # Backend API (Elixir/Phoenix)
│   ├── lib/
│   │   ├── phoenix_api/      # Konteksty biznesowe
│   │   └── phoenix_api_web/  # Kontrolery, widoki, routery
│   ├── config/               # Konfiguracja środowisk
│   ├── priv/repo/           # Migracje i seedy
│   ├── test/                # Testy
│   ├── mix.exs              # Zależności Elixir
│   └── Dockerfile           # Kontener Phoenix
├── symfony-admin/           # Panel administracyjny (PHP/Symfony)
│   ├── src/
│   │   ├── Controller/      # Kontrolery
│   │   ├── Entity/          # Encje Doctrine
│   │   ├── Service/         # Serwisy biznesowe
│   │   └── Repository/      # Repozytoria
│   ├── templates/           # Szablony Twig
│   ├── config/              # Konfiguracja Symfony
│   ├── migrations/          # Migracje Doctrine
│   ├── composer.json        # Zależności PHP
│   └── Dockerfile           # Kontener Symfony
├── docker-compose.yml       # Orkiestracja kontenerów
└── README.md               # Ta dokumentacja
```

## 🔧 Konfiguracja środowiska

### Zmienne środowiskowe

#### Phoenix API
- `DATABASE_URL`: Połączenie z PostgreSQL
- `SECRET_KEY_BASE`: Klucz szyfrowania (zmień w produkcji!)
- `PHX_HOST`: Host aplikacji
- `PORT`: Port serwera (domyślnie 4000)

#### Symfony Admin
- `DATABASE_URL`: Połączenie z PostgreSQL
- `PHOENIX_API_URL`: URL do Phoenix API
- `APP_ENV`: Środowisko aplikacji (dev/prod)
- `APP_SECRET`: Klucz aplikacji Symfony (zmień w produkcji!)

### Pliki konfiguracyjne

- `phoenix-api/config/dev.exs` - Konfiguracja Phoenix dla developmentu
- `symfony-admin/.env` - Zmienne środowiskowe Symfony
- `symfony-admin/.env.dev` - Nadpisania dla środowiska dev

## 🛠️ Rozwój aplikacji

### Praca z Phoenix API

```bash
# Wejście do kontenera Phoenix
docker-compose exec phoenix bash

# Uruchomienie testów
mix test

# Konsola interaktywna
iex -S mix

# Generowanie nowej migracji
mix ecto.gen.migration create_users

# Formatowanie kodu
mix format
```

### Praca z Symfony Admin

```bash
# Wejście do kontenera Symfony
docker-compose exec symfony bash

# Cache clear
php bin/console cache:clear

# Sprawdzenie jakości kodu
vendor/bin/php-cs-fixer fix
```

### Logi aplikacji

```bash
# Wszystkie logi
docker-compose logs -f

# Logi konkretnego serwisu
docker-compose logs -f phoenix
docker-compose logs -f symfony
docker-compose logs -f postgres
```

## 📡 API Endpoints

### Phoenix API (http://localhost:4000/api)

| Metoda | Endpoint | Opis |
|--------|----------|------|
| GET | `/api/users` | Lista wszystkich użytkowników |
| POST | `/api/users` | Tworzenie nowego użytkownika |
| GET | `/api/users/:id` | Szczegóły użytkownika |
| PUT | `/api/users/:id` | Aktualizacja użytkownika |
| DELETE | `/api/users/:id` | Usunięcie użytkownika |
| GET | `/api/health` | Status zdrowia API |

### Przykłady użycia

```bash
# Lista użytkowników
curl http://localhost:4000/api/users

# Tworzenie użytkownika
curl -X POST http://localhost:4000/api/users \
  -H "Content-Type: application/json" \
  -d '{"user":{"name":"Jan Kowalski","email":"jan@example.com"}}'

# Szczegóły użytkownika
curl http://localhost:4000/api/users/1
```

## 🖥️ Panel administracyjny

**URL**: http://localhost:8080

Panel administracyjny Symfony komunikuje się z Phoenix API poprzez HTTP Client. Oferuje:

- 👥 Zarządzanie użytkownikami (CRUD)
- 🔐 System uwierzytelniania
- 📱 Responsywny interfejs
- 🎨 Nowoczesny UI/UX

## 🗄️ Baza danych

### Połączenie z PostgreSQL

```bash
# Przez Docker
docker-compose exec postgres psql -U postgres -d phoenix_symfony_dev

# Lokalnie (jeśli masz zainstalowany psql)
psql -h localhost -p 5432 -U postgres -d phoenix_symfony_dev
```

## 🧪 Testowanie

### Phoenix API

```bash
# Wszystkie testy
docker-compose exec phoenix mix test

# Konkretny test
docker-compose exec phoenix mix test test/phoenix_api_web/controllers/user_controller_test.exs
```

## 🚀 Deployment

### Produkcja

1. **Zmień sekrety** w `docker-compose.yml`:
   - `SECRET_KEY_BASE` dla Phoenix
   - `APP_SECRET` dla Symfony
   - Hasła do bazy danych

2. **Ustaw zmienne środowiskowe**:
   ```bash
   export APP_ENV=prod
   export DATABASE_URL=postgresql://user:pass@host:5432/dbname
   ```

3. **Uruchom w trybie produkcyjnym**:
   ```bash
   docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d
   ```

### Monitoring

- **Health checks**: Wbudowane w docker-compose.yml
- **Logi**: Centralizowane przez Docker
- **Metryki**: Dostępne przez Phoenix LiveDashboard

## 🔧 Rozwiązywanie problemów

### Częste problemy

1. **Kontener nie startuje**:
   ```bash
   docker-compose logs [service_name]
   docker-compose down && docker-compose up -d
   ```

2. **Błędy bazy danych**:
   ```bash
   # Reset bazy danych
   docker-compose exec phoenix mix ecto.reset
   ```

3. **Problemy z zależnościami**:
   ```bash
   # Rebuild kontenerów
   docker-compose build --no-cache
   ```

4. **Porty zajęte**:
   ```bash
   # Sprawdź zajęte porty
   lsof -i :4000
   lsof -i :8080
   lsof -i :5432
   ```

### Czyszczenie środowiska

```bash
# Zatrzymanie i usunięcie kontenerów
docker-compose down

# Usunięcie wolumenów (UWAGA: usuwa dane!)
docker-compose down -v

# Czyszczenie obrazów
docker system prune -a
```

## 📚 Dodatkowe zasoby

- [Phoenix Framework](https://phoenixframework.org/)
- [Symfony Documentation](https://symfony.com/doc/current/index.html)
- [Elixir Lang](https://elixir-lang.org/)
- [Docker Compose](https://docs.docker.com/compose/)
- [PostgreSQL](https://www.postgresql.org/docs/)

## 📄 Licencja

Projekt jest dostępny na licencji MIT. Zobacz plik `LICENSE` dla szczegółów.

---

**Autor**: Adrian Kemski  
**Wersja**: 1.0.0  
**Ostatnia aktualizacja**: 2025-01-12