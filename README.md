# 📊 Zoho CRM Integration

This project implements partial integration with **Zoho CRM** for creating, managing, and bulk importing **Deals** based on local data.  
The architecture consists of a **Laravel** backend and a **Vue 3 + TypeScript** frontend interface for convenient deal management via a web UI.

---

## 🚀 Bulk Deal Import

A console command is provided for batch creation of a large number of deals:

```bash
php artisan zoho:push-deals --all --chunk=20
```

📁 **File:** `app/Console/Commands/ZohoPushDeals.php`

### Available options:

- `--all` — process **all deals** from the database  
- `--chunk=20` — send deals **in batches of 20** for stable performance  
- `--delay=250` — delay between individual deals (ms)  
- `--pause=1500` — delay between batches (ms)  
- `--ids=1,2,3` — send only the specified deals

✅ **Recommended production usage:**

```bash
php artisan zoho:push-deals --all --chunk=20 --delay=250 --pause=1500
```

The command processes deals in chunks, automatically creates an **Account** if it doesn’t exist, and sends the deals to Zoho CRM.  
In case of rate-limit errors (`429 Too Many Requests`), automatic retries with delay are implemented.

---

## 👥 Deal Assignment Logic

The logic for assigning a responsible manager is encapsulated in a dedicated service:

📁 **File:** `app/Services/ManagerAssignmentService.php`

- The service automatically assigns a manager to each deal based on business rules.  
- It is triggered on deal creation or update via an **observer**:

📁 **File:** `app/Observers/DealObserver.php`

`DealObserver` subscribes to the `Deal` model and invokes `ManagerAssignmentService` whenever a deal is created or updated.

---

## 🧑‍💻 Frontend Interface

The frontend is built with **Vue 3 + TypeScript** using the **Composition API**.  
Main form logic is separated into composable modules for cleaner structure and easier testing.

### 📁 Key Components

- `resources/js/zoho/deals/components/DealCreateForm.vue` — main deal creation form  
- `resources/js/zoho/deals/composables/useDealForm.ts` — form state management, validation, submission  
- `resources/js/zoho/deals/composables/useCustomers.ts` — fetching existing customers  
- `resources/js/zoho/deals/constants/strings.ts` — UI text constants

### ✨ Features

- Create a new deal with fields:
  - **Deal Name**
  - **Source**
  - **Customer Data** (name, surname, email)
- Select existing customers from a dropdown list
- Auto-fill customer data on selection
- Field validation (including email)
- Server response handling with success/error messages

---

## 🛠️ Project Architecture

- **Backend:** Laravel 12  
- **Frontend:** Vue 3 + TypeScript (Composition API)  
- **CRM:** Zoho API v3 (`/crm/v3/Accounts`, `/crm/v3/Deals`)

---

## ✅ Review Checklist

- [ ] Verify `zoho:push-deals` command with `--all --chunk=20`  
- [ ] Review manager assignment logic in `ManagerAssignmentService.php` and observer integration  
- [ ] Test frontend behavior: validation, customer loading, deal submission, and response handling

---

## 📦 Setup and Usage

### 1. Install dependencies

```bash
composer install
npm install
```

### 2. Configure environment

Create a `.env` file and add your Zoho API credentials:

```env
ZOHO_CLIENT_ID=your_client_id
ZOHO_CLIENT_SECRET=your_client_secret
ZOHO_REFRESH_TOKEN=your_refresh_token
```

### 3. Start the development server

```bash
php artisan serve
npm run dev
```

### 4. Run the bulk import

```bash
php artisan zoho:push-deals --all --chunk=20 --delay=250 --pause=1500
```

---

## 📄 License

© 2025 — Zoho CRM Integration Project. All rights reserved.
