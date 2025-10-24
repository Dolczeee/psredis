# psredis - Redis Cache & Performance Optimizer dla PrestaShop 8.x / 9.x

![PrestaShop](https://img.shields.io/badge/PrestaShop-8.x%20%7C%209.x-blue)
![PHP](https://img.shields.io/badge/PHP-8.1%2B-8892BF)
![Redis](https://img.shields.io/badge/Redis-6%2B-orange)
![License](https://img.shields.io/badge/License-MIT-green)

## Opis Projektu

**psredis** to zaawansowany moduł optymalizacyjny dla platformy e-commerce PrestaShop (wersje 8.x i 9.x), mający na celu znaczne zwiększenie wydajności sklepu poprzez integrację systemu buforowania Redis.

Moduł integruje Redis jako główny adapter pamięci podręcznej Symfony (DI Container) i Smarty, co pozwala na:

- Redukcję obciążenia bazy danych MySQL/MariaDB.
- Drastyczne skrócenie czasu ładowania strony (TTFB).
- Efektywne skalowanie sklepu pod dużym obciążeniem.

## Główne Funkcje

- **Pełne Przejęcie Cache**: Przesłania standardowy adapter pamięci podręcznej PrestaShop (`prestashop.cache.adapter`) oraz Smarty na rzecz Redis.  
- **Konfiguracja Połączenia**: Bezpieczne zarządzanie parametrami połączenia Redis (Host, Port, Hasło) przez dedykowany interfejs administracyjny.  
- **Wygoda Konsoli**: Dodanie własnej komendy konsoli Symfony (`psredis:cache:warmup`) do wstępnego podgrzewania pamięci podręcznej sklepu (cache warmup).  
- **Wykluczanie Kontrolerów**: Możliwość wyłączenia buforowania Redis dla wybranych, krytycznych kontrolerów (np. koszyk, zamówienie, checkout), aby zapewnić integralność danych.  
- **Ochrona API**: Opcjonalne wyłączanie buforowania dla żądań Web Service API, co jest standardową praktyką w środowiskach integracyjnych.  

## Wymagania

- PrestaShop w wersji 8.1.0 lub nowszej.  
- Zainstalowany i działający serwer Redis.  
- Rozszerzenie PHP `php-redis` (lub alternatywne, w zależności od użytego adaptera Redis).  

## Instalacja

1. **Klonowanie Repozytorium**  

   Sklonuj repozytorium do katalogu `modules/` w Twojej instalacji PrestaShop:

   ```bash
   git clone https://github.com/TwojaNazwa/psredis.git modules/psredis


2. **Instalacja w Panelu** 
   Przejdź do Panelu Administracyjnego PrestaShop (Moduły -> Menedżer modułów), znajdź Redis Cache & Performance Optimizer i zainstaluj go.

3. **Konfiguracja** 
   Przejdź do strony konfiguracji modułu, aby wprowadzić dane dostępowe do serwera Redis i włączyć buforowanie.

4. **Wyczyszczenie Cache** 
   Wyczyść cache Symfony, aby moduł mógł przejąć kontrolę nad systemem buforowania:
   Zaawansowane -> Wydajność -> Wyczyść cache.
   
## Licencja

MIT © DolceCX
