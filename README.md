# Xmapenzi — PHP + MySQL

Full website ya video, status na malipo ya Selcom Mobile (Tanzania).
Imejengwa kwa PHP saf + MySQL, tayari kwa cPanel hosting.

## Mahitaji ya Server
- PHP 8.0+ (na `pdo_mysql`, `curl`, `openssl` extensions)
- MySQL 5.7+ au MariaDB 10.3+
- Apache na `mod_rewrite`, `mod_headers`
- SSL (HTTPS) — inashauriwa sana

## Hatua za Kufunga (cPanel)

### 1. Pakia files
- Pakua faili la `xmapenzi-php.zip`.
- Kwenye cPanel → **File Manager** → ingia kwenye folder ya domain (mfano `xmapenzi.flatbet.online`).
- Bonyeza **Upload**, chagua `xmapenzi-php.zip`, kisha **Extract**.
- Hakikisha content zote zinaingia kwenye **root** ya domain hiyo (sio ndani ya `xmapenzi-php/` ndogo).

### 2. Tengeneza Database
- cPanel → **MySQL Databases**.
- Tengeneza database mpya (mfano `xmapenzi_db`).
- Tengeneza user mpya na password kali; mpe **ALL PRIVILEGES** kwenye database hiyo.

### 3. Hariri Config
Fungua `includes/config.php` kupitia **Edit** kisha badili thamani hizi:
```
DB_HOST   = 'localhost'
DB_NAME   = 'xmapenzi_db'        (jina kamili ikiwemo prefix ya cPanel mfano teslo_xmapenzi)
DB_USER   = 'xmapenzi_user'
DB_PASS   = 'password yako'
SITE_URL  = 'https://xmapenzi.flatbet.online'   (bila slash mwishoni)
```

### 4. Endesha Installer
- Tembelea: `https://yoursite/install.php`
- Weka admin username + password (min char 8).
- Bonyeza **Sakinisha**.
- **FUTA `install.php` mara moja** baada ya kukamilika.

### 5. Ingia kwa Admin
- `https://yoursite/admin/login.php`
- Nenda **Settings** → weka Selcom API Key, Secret, na Vendor ID.
- Nakili **Webhook URL** kisha iweke kwenye Selcom Merchant Portal kama callback URL.

### 6. Permissions
- Hakikisha folder `uploads/` na sub-folders zake (`videos/`, `thumbnails/`, `status-photos/`) zina permission **755** (au 775).
- Files zote ziwe **644**.

## Muundo wa Faili
```
/
├── index.php                ← Frontend (homepage)
├── install.php              ← Installer (futa baada ya setup)
├── install.sql              ← Schema ya database
├── .htaccess                ← Apache config
├── assets/
│   ├── logo.png
│   ├── style.css
│   └── app.js               ← Payment + video player + splash
├── includes/
│   ├── config.php           ← Database & site config
│   ├── db.php               ← PDO connection helpers
│   ├── auth.php             ← Admin session/auth
│   └── selcom.php           ← Selcom HMAC signing & API calls
├── api/
│   ├── initiate-payment.php ← Anza malipo (PUSH USSD)
│   ├── poll-payment.php     ← Angalia status ya malipo
│   ├── selcom-webhook.php   ← Callback ya Selcom
│   └── video-url.php        ← Toa URL ya video (free au baada ya malipo)
├── admin/
│   ├── login.php / logout.php
│   ├── index.php            ← Dashboard
│   ├── videos.php           ← CRUD ya video (upload + bei)
│   ├── statuses.php         ← CRUD ya status (call/chat prices)
│   ├── payments.php         ← Historia ya malipo
│   └── settings.php         ← Selcom credentials & password
└── uploads/                 ← Files zilizopakiwa (videos/thumbnails/photos)
```

## Selcom Integration

- **PUSH USSD**: `POST /v1/wallet/pushussd` na HMAC-SHA256 signing.
- **Query status**: `GET /v1/c2b/query-status?transid=...`.
- **Webhook**: Selcom akituma callback, server inathibitisha token kisha ina-update payment status na ku-generate `unlock_token`.

Mfumo wa malipo:
1. User anabofya video/status ya kulipia → anaweka namba ya simu.
2. Server inaita Selcom Push USSD → user anapokea prompt ya PIN simuni.
3. Frontend ina-poll kila sekunde 4 hadi `paid` au `failed`.
4. Akilipa: video inafungua, na unlock inahifadhiwa **localStorage** kwa **wiki 2** (siku 14). Baada ya hapo lazima alipe tena.
5. Status: baada ya malipo, WhatsApp inafunguka moja kwa moja na message tayari.

## Usalama
- Password ya admin imehifadhiwa na `password_hash(BCRYPT)`.
- Forms zote za admin zina CSRF token.
- `includes/` haifikiki kupitia HTTP (htaccess Deny).
- `uploads/*.php` zime-block (haziwezi ku-execute).
- Webhook ya Selcom ina-verify token kabla ya ku-update payments.
- Inashauriwa kuwasha HTTPS na kuwa-restrict cPanel kwa IP yako tu.

## Matatizo ya Kawaida

**"Database connection failed"** → Hakikisha credentials kwenye `includes/config.php` ni sahihi.

**Video haipakii** → Angalia `MAX_VIDEO_MB` kwenye config, na `upload_max_filesize` kwenye `.htaccess`. Baadhi ya hosts huzuia mabadiliko kupitia .htaccess; tumia **MultiPHP INI Editor** kwenye cPanel.

**Malipo hayakamiliki** → Hakikisha Webhook URL umeiweka kwenye Selcom Portal na token inalingana.

**Permissions error wakati wa upload** → `chmod 755 uploads -R` kupitia cPanel.

---

© Xmapenzi