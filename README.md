# ✂️ The Style Studio - Salon Management Web App

Welcome to **The Style Studio**, a full-stack PHP-based Salon Management Web Application designed to simplify salon bookings for both users and admins.  
🌐 Live at: [thestylestudio.kesug.com](http://thestylestudio.kesug.com)

---

## 🖥️ Project Overview

This project enables:
- Users to register, log in, and book slots for salon services
- Admins to manage appointments via a secure login interface

💡 Built with simplicity and usability in mind, it serves as a practical tool for salon appointment scheduling and management.

---

## 📌 Features

✅ User Registration & Login  
✅ Admin Login  
✅ Book Available Slots  
✅ View & Manage Bookings  
✅ Session-Based Access Control  
✅ Image Assets for Salon UI  
✅ Custom 404 Error Page

---

## 🛠️ Tech Stack

| Frontend | Backend | Database | Hosting |
|----------|---------|----------|---------|
| HTML5, CSS3 | PHP | MySQL (phpMyAdmin) | InfinityFree |

---

## 📁 Project Structure

📦 Salon Web App
├── admin/
│ ├── admin_login.php
│ ├── index.php
│ └── logout.php
├── public/
│ ├── index.php
│ ├── login.php
│ ├── logout.php
│ └── register.php
├── includes/
│ └── db.php
├── images/
│ ├── logo.png, salon1.jpg ... salon9.jpg
├── styles/
│ ├── index.css
│ └── login_style.css
├── .gitignore
└── README.md

yaml
Copy
Edit

## 🧪 How to Run Locally

1. Clone the repository:
   ```bash
   git clone https://github.com/yourusername/salon-management-webapp.git
   cd salon-management-webapp

Set up a local server using XAMPP:

Place the project in the htdocs/ folder.

Start Apache and MySQL.

Import the database:

Open phpMyAdmin at localhost/phpmyadmin

Create a database and import the provided .sql file (if available).

Access the app:

http://localhost/salon-management-webapp/public
