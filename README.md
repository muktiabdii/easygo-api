<h2 align="center">
  <b>EasyGo API 🚀 - Backend for EasyGo Web</b>
</h2>

<p align="center">
  A secure Laravel-based backend powering EasyGo — provides RESTful API for user auth, places, ratings, chat, and admin panel integration.
</p>

---

## ✨ Key Features & Benefits

- 🔐 **User Authentication**  
  Secure registration, login, OTP, and profile management.

- 🏞️ **Place Management**  
  Add/edit places with facilities and images.

- ⭐ **Rating System**  
  Leave reviews and ratings with image support.

- 💬 **Chat Rooms**  
  Real-time chat system.

- 🛡️ **Role-Based Access Control**  
  Admin and user separation.

- ☁️ **Dropbox Integration**  
  Store image uploads via Dropbox API.

- ⚙️ **API Consumption Ready**  
  Fully integrated with [EasyGo Frontend](https://github.com/muktiabdii/easygo-web).

---

## ⚙️ Prerequisites & Dependencies

- **PHP** `>= 8.1`
- **Composer**
- **MySQL**
- **Git**

---

## 🚀 Installation & Setup Instructions

1. **Clone the repository:**

   ```bash
   git clone git@github.com:muktiabdii/easygo-api.git
   cd easygo-api
   ```

2. **Install dependencies:**

   ```bash
   composer install
   ```

3. **Set up environment:**

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

   Configure `.env`:
   - `DB_*`, `DROPBOX_*`, `APP_URL`, etc.

4. **Run migrations:**

   ```bash
   php artisan migrate
   ```

5. **(Optional) Seed data:**

   ```bash
   php artisan db:seed
   ```

6. **Start server:**

   ```bash
   php artisan serve
   ```

   Accessible at: `http://localhost:8000`

---

## 📚 API Documentation

API docs in progress.  
Refer to routes in `routes/api.php` and controller logic in `app/Http/Controllers/`.

---

## 🔗 Integration

This API powers the [EasyGo Web Frontend](https://github.com/muktiabdii/easygo-web), providing all necessary data and actions for:

- Auth
- Place discovery
- Ratings
- Chat
- Admin dashboard

---

## 👨‍💻 Developer Team

- [@Ade](https://www.linkedin.com/in/adenugroho/) — Frontend
- [@Gilang](https://www.linkedin.com/in/gilang-hafizh/) — Frontend
- [@Dzikri](https://www.linkedin.com/in/dzikri-murtadlo/) — Fullstack
- [@Abdi](https://www.linkedin.com/in/muktiabdii/) — Backend



## 📦Project Strcture

``` 
├─ .editorconfig
├─ .env.example
├─ .gitattributes
├─ .gitignore
├─ README.md
├─ app
│  ├─ Events
│  │  └─ MessageSent.php
│  ├─ Http
│  │  ├─ Controllers
│  │  │  ├─ ChatController.php
│  │  │  ├─ Controller.php
│  │  │  ├─ PlaceController.php
│  │  │  ├─ ReviewController.php
│  │  │  └─ UserController.php
│  │  ├─ Kernel.php
│  │  └─ Middleware
│  │     └─ VerifyCsrfToken.php
│  ├─ Mail
│  │  └─ ResetOtpMail.php
│  ├─ Models
│  │  ├─ ChatRoom.php
│  │  ├─ Facility.php
│  │  ├─ Message.php
│  │  ├─ Place.php
│  │  ├─ PlaceImage.php
│  │  ├─ Rating.php
│  │  ├─ RatingImage.php
│  │  └─ User.php
│  ├─ Providers
│  │  ├─ AppServiceProvider.php
│  │  └─ RouteServiceProvider.php
│  └─ Services
│     └─ DropboxService.php
├─ artisan
├─ bootstrap
│  ├─ app.php
│  ├─ cache
│  │  └─ .gitignore
│  └─ providers.php
├─ composer.json
├─ composer.lock
├─ config
│  ├─ app.php
│  ├─ auth.php
│  ├─ broadcasting.php
│  ├─ cache.php
│  ├─ cors.php
│  ├─ database.php
│  ├─ filesystems.php
│  ├─ logging.php
│  ├─ mail.php
│  ├─ queue.php
│  ├─ sanctum.php
│  ├─ services.php
│  └─ session.php
├─ database
│  ├─ .gitignore
│  ├─ factories
│  │  └─ UserFactory.php
│  ├─ migrations
│  │  ├─ 0001_01_01_000000_create_users_table.php
│  │  ├─ 0001_01_01_000001_create_cache_table.php
│  │  ├─ 0001_01_01_000002_create_jobs_table.php
│  │  ├─ 2025_04_17_041320_create_places_table.php
│  │  ├─ 2025_04_17_041328_create_facilities_table.php
│  │  ├─ 2025_04_17_041354_create_place_fasility_table.php
│  │  ├─ 2025_04_17_041402_create_ratings_table.php
│  │  ├─ 2025_04_17_041413_create_chat_rooms_table.php
│  │  ├─ 2025_04_17_041420_create_messages_table.php
│  │  ├─ 2025_04_17_041428_create_place_images_table.php
│  │  ├─ 2025_04_17_043236_create_rating_confirmed_facility_table.php
│  │  ├─ 2025_04_17_101746_create_personal_access_tokens_table.php
│  │  ├─ 2025_04_24_064245_add_reset_otp_to_users_table.php
│  │  ├─ 2025_05_11_064324_add_profile_image_to_users_table.php
│  │  ├─ 2025_05_11_114554_create_rating_images_table.php
│  │  ├─ 2025_05_18_074308_add_status_to_places_table.php
│  │  ├─ 2025_05_24_085326_add_role_to_users_table.php
│  │  ├─ 2025_06_01_165210_add_reviews_count_and_last_active_to_users_table.php
│  │  ├─ 2025_06_03_160232_rename_description_to_comment_in_places_table.php
│  │  └─ 2025_06_03_164220_add_user_id_to_places_table.php
│  └─ seeders
│     ├─ AdminUserSeeder.php
│     ├─ DatabaseSeeder.php
│     └─ FacilitiesSeeder.php
├─ package.json
├─ phpunit.xml
├─ public
│  ├─ .htaccess
│  ├─ favicon.ico
│  ├─ images
│  │  ├─ facilities
│  │  │  ├─ interpreter_isyarat.png
│  │  │  ├─ jalur_guiding_block.png
│  │  │  ├─ jalur_kursi_roda.png
│  │  │  ├─ lift_braille_suara.png
│  │  │  ├─ menu_braille.png
│  │  │  ├─ parkir_disabilitas.png
│  │  │  ├─ pintu_otomatis.png
│  │  │  └─ toilet_disabilitas.png
│  │  └─ logo_easygo.png
│  ├─ index.php
│  └─ robots.txt
├─ resources
│  ├─ css
│  │  └─ app.css
│  ├─ js
│  │  ├─ app.js
│  │  └─ bootstrap.js
│  └─ views
│     ├─ reset_otp_mail.blade.php
│     └─ welcome.blade.php
├─ routes
│  ├─ api.php
│  ├─ channels.php
│  ├─ console.php
│  └─ web.php
├─ storage
│  ├─ app
│  │  ├─ .gitignore
│  │  ├─ private
│  │  │  └─ .gitignore
│  │  └─ public
│  │     └─ .gitignore
│  ├─ framework
│  │  ├─ .gitignore
│  │  ├─ cache
│  │  │  ├─ .gitignore
│  │  │  └─ data
│  │  │     └─ .gitignore
│  │  ├─ sessions
│  │  │  └─ .gitignore
│  │  ├─ testing
│  │  │  └─ .gitignore
│  │  └─ views
│  │     └─ .gitignore
│  └─ logs
│     └─ .gitignore
├─ tests
│  ├─ Feature
│  │  └─ ExampleTest.php
│  ├─ TestCase.php
│  └─ Unit
│     └─ ExampleTest.php
└─ vite.config.js
