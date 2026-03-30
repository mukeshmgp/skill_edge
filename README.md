# 🎓 Skill Edge – TNPSC Exam Portal
**Full-stack PHP + MySQL exam preparation platform**

---

## 📁 Folder Structure
```
skilledge/
├── index.php              ← Homepage (Hero + Group Cards)
├── login.php              ← Animated Login
├── register.php           ← Animated Register
├── test.php               ← Mock Test (Timer + Palette)
├── result.php             ← Animated Results + Review
├── profile.php            ← Dashboard + Analytics
├── syllabus.php           ← Syllabus viewer
├── logout.php
├── setup.sql              ← Run this first!
│
├── includes/
│   ├── db.php             ← Database config
│   └── auth.php           ← Session helpers
│
├── assets/
│   ├── css/style.css      ← Global theme (maroon + gold)
│   └── js/main.js         ← Animations, dark mode, utils
│
├── ajax/
│   ├── bookmark.php
│   └── dark_mode.php
│
└── admin/
    └── manage_questions.php ← Add/Edit/Delete questions
```

---

## ⚙️ XAMPP Setup (3 steps)

### Step 1 – Copy to htdocs
```
C:\xampp\htdocs\skilledge\
```

### Step 2 – Create Database
1. Start **Apache + MySQL** in XAMPP Control Panel
2. Open `http://localhost/phpmyadmin`
3. Click **SQL** tab
4. Paste contents of `setup.sql` → click **Go**

### Step 3 – Open the app
| Page       | URL                                       |
|------------|-------------------------------------------|
| Homepage   | http://localhost/skilledge/               |
| Login      | http://localhost/skilledge/login.php      |
| Register   | http://localhost/skilledge/register.php   |
| Mock Tests | http://localhost/skilledge/test.php       |
| Dashboard  | http://localhost/skilledge/profile.php    |
| Syllabus   | http://localhost/skilledge/syllabus.php   |
| Admin      | http://localhost/skilledge/admin/manage_questions.php |

---

## 👤 Demo Admin Login
| Field    | Value              |
|----------|--------------------|
| Email    | admin@skilledge.local |
| Password | Admin@1234         |

---

## 🎨 Color Theme
| Variable        | Value     |
|-----------------|-----------|
| Maroon          | `#800000` |
| Maroon Deep     | `#5C0000` |
| Gold            | `#F4C430` |
| Gold Dark       | `#D4A820` |

---

## ✨ Features
- 🏠 **Homepage** – Hero with animated floating cards + group selector
- 📝 **Mock Tests** – Timed tests, keyboard shortcuts, question palette
- 🔖 **Bookmarks** – Save questions for later
- 📊 **Profile Dashboard** – Analytics, weekly chart, subject performance
- 🌙 **Dark Mode** – Toggle persisted across sessions
- 🔐 **Admin Panel** – Full CRUD for questions with modal editor
- 📱 **Responsive** – Mobile-first design
