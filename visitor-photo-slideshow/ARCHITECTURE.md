
# SYSTEM ARCHITECTURE

## Project Name
Visitor Moments - Visitor Photo Slideshow System

## 1. Introduction

Visitor Moments is a web-based application that allows visitors to register and upload their photographs. An administrator reviews uploaded photographs and approves them for display on a public slideshow screen.

## 2. Technology Stack

| Technology | Purpose |
|---|---|
| HTML5 | Web page structure |
| CSS3 | Website styling |
| Bootstrap 5 | Responsive design |
| JavaScript | Slideshow functionality |
| PHP | Backend development |
| MySQL | Database management |
| XAMPP | Local server environment |
| Chart.js | Admin dashboard charts |

## 3. Project Folder Structure

```text
visitor-photo-slideshow/
│
├── admin/
│   ├── dashboard.php
│   ├── visitors.php
│   ├── photos.php
│   ├── settings.php
│   ├── login.php
│   ├── logout.php
│   └── create-admin.php (temporary setup file)
│
├── config/
│   ├── database.php
│   └── config.php
│
├── visitor/
│   ├── register.php
│   ├── upload.php
│   └── success.php
│
├── display/
│   └── index.php
│
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   └── slideshow.js
│   └── images/
│
├── uploads/
│   └── visitor uploaded photos
│
├── index.php
├── README.md
├── ASSUMPTIONS.md
├── AI_LOG.md
├── ARCHITECTURE.md
└── .gitignore
```

## 4. Main System Modules

### A. Visitor Module
- Visitor registration.
- Returning visitor identification.
- Visitor information management.
- Multiple photo uploads.
- Upload success confirmation.

### B. Admin Module
- Secure administrator login.
- Dashboard statistics.
- Visitor management.
- Photo approval and rejection.
- Photo deletion.
- Slideshow settings management.

### C. Display Module
- Displays approved photographs.
- Automatically changes photographs.
- Shows visitor names.
- Supports configurable display duration.
- Supports slideshow ON/OFF control.

### D. Database Module
- Stores visitor information.
- Stores uploaded photo information.
- Maintains photo approval status.
- Stores slideshow settings.
- Stores administrator information.

## 5. Database Architecture

The application uses a MySQL database named `visitor_slideshow`.

### Tables

**1. visitors**
- id
- name
- email
- phone
- consent
- consent_at
- created_at

**2. photos**
- id
- visitor_id
- image_path
- status
- uploaded_at

**3. settings**
- id
- setting_key
- setting_value

**4. admins**
- id
- name
- email
- password
- created_at

### Database Relationship

- One visitor can upload multiple photos.
- Each photo belongs to one visitor.
- The photos table references the visitors table through visitor_id.
- Admin information is stored separately.
- Slideshow configuration is stored in the settings table.

## 6. System Workflow

1. Visitor opens the website.
2. Visitor registers using personal contact details.
3. System identifies whether the visitor already exists.
4. Visitor provides consent and uploads photographs.
5. Uploaded photographs are saved in the uploads folder.
6. Photo information is stored in the database with Pending status.
7. Administrator logs into the admin panel.
8. Administrator reviews uploaded photographs.
9. Administrator approves or rejects photographs.
10. Only approved photographs are displayed on the slideshow screen.
11. The slideshow automatically changes photographs according to the configured duration.

## 7. Security Measures

- Password hashing using PHP password_hash().
- Password verification using password_verify().
- Image MIME-type validation.
- Maximum image size restriction.
- Random image filenames.
- HTML output escaping.
- Admin session authentication.
- Upload directory protection using .htaccess.

## 8. Conclusion

The Visitor Moments system provides a centralized way to collect, manage and display visitor photographs. Its modular structure makes the application easier to maintain and extend in the future.
```
