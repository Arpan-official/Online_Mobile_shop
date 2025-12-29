# Online_Mobile_shop
a php based online mobile store application



# 🛒 ShopEase – E-Commerce Web Application

ShopEase is a web-based e-commerce application developed using **PHP, MySQL, HTML, and CSS**.  
The project provides basic online shopping functionality with **secure database operations** and **role-based access control** for users and administrators.

---

## 📌 Project Objective

The main objective of this project is to design and develop a simple, secure, and user-friendly e-commerce platform where:
- Users can browse available products
- Administrators can manage product inventory
- All database interactions are handled securely using prepared statements

---

## 🛠️ Technology Stack

- **Frontend:** HTML, CSS  
- **Backend:** PHP  
- **Database:** MySQL  
- **Database Connectivity:** PDO (PHP Data Objects)  
- **Server Environment:** XAMPP / MAMP / Localhost  

---

## 📂 Project Features

### 👤 User Features
- View available products
- Browse product details
- Add products to cart
- Secure session-based access

### 🔑 Admin Features
- Add new products
- Edit existing products
- Delete products
- Restricted admin-only access using role validation

---

## 🗄️ Database Design

The project uses a relational database structure with tables such as:
- `users`
- `admins`
- `products`
- `orders` / `cart` (if applicable)

Each table is designed with appropriate primary keys and relationships to maintain data integrity.

---

## 🔐 Security Implementation

- **Prepared Statements (PDO):** Prevents SQL injection attacks  
- **Input Sanitization:** Protects against XSS using functions like `htmlspecialchars()`  
- **Session Management:** Ensures authorized access to admin functionalities  

---

## ⚙️ Installation & Setup

1. Clone the repository:
   ```bash
   git clone https://github.com/your-username/shopease.git
